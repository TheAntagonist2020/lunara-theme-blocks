/* Homepage Oscar lineups and image framing; the shared host owns Preview / Apply. */
(function () {
 'use strict';
 var controls = window.LunaraEditorControls;
 function keys(value, expected) { return !!value && typeof value === 'object' && !Array.isArray(value) && JSON.stringify(Object.keys(value).sort()) === JSON.stringify(expected.slice().sort()); }
 function validId(id) { return Number.isSafeInteger(id) && id > 0 && id <= 9999999999; }
 function integer(value, min, max) { return Number.isSafeInteger(value) && value >= min && value <= max; }
 function ids(state) { return state.selection.ids ? state.selection.ids.split(',').map(Number) : []; }
 function validFraming(value) {
  return keys(value, ['image_id','fit','focal_x','focal_y','zoom']) && integer(value.image_id,0,9999999999) && ['cover','full'].indexOf(value.fit) >= 0 && integer(value.focal_x,0,100) && integer(value.focal_y,0,100) && integer(value.zoom,100,112);
 }
 function framing(value) { return {image_id:value.image_id,fit:value.fit,focal_x:value.focal_x,focal_y:value.focal_y,zoom:value.zoom}; }
 function overrides(state) { return JSON.parse(state.artwork.overrides); }
 function serializeOverrides(value) { return '{'+Object.keys(value).sort(function (a,b) { return Number(a)-Number(b); }).map(function (id) { return JSON.stringify(id)+':'+JSON.stringify(framing(value[id])); }).join(',')+'}'; }
 function validOverrides(value) {
  if (typeof value !== 'string' || value.length > 12000) { return false; }
  try {
   var parsed = JSON.parse(value), canonical = {};
   if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed) || Object.keys(parsed).length > 48) { return false; }
   if (!Object.keys(parsed).every(function (id) { if (!/^[1-9][0-9]{0,9}$/.test(id) || !validFraming(parsed[id])) { return false; } canonical[id] = framing(parsed[id]); return true; })) { return false; }
   return serializeOverrides(canonical) === value;
  } catch (error) { return false; }
 }
 function validState(state) {
  if (!keys(state,['copy','selection','presentation','artwork']) || !keys(state.copy,['kicker','heading','summary','cta_text']) || !keys(state.artwork,['overrides']) || !validOverrides(state.artwork.overrides)) { return false; }
  var picks = Object.prototype.hasOwnProperty.call(state.selection || {},'ceremony_year');
  if (!keys(state.selection,picks ? ['mode','ids','ceremony_year'] : ['mode','ids']) || !keys(state.presentation,['count','autoplay_interval','density','card_min_height']) || ['legacy','automatic','manual'].indexOf(state.selection.mode) < 0 || typeof state.selection.ids !== 'string') { return false; }
  var limits = {kicker:160,heading:300,summary:1200,cta_text:160}, selected = ids(state), presentation = state.presentation;
  return Object.keys(limits).every(function (key) { return typeof state.copy[key] === 'string' && state.copy[key].length <= limits[key]; }) && (!picks || integer(state.selection.ceremony_year,1929,2100)) && integer(presentation.count,1,16) && integer(presentation.autoplay_interval,0,12000) && ['compact','editorial','showcase'].indexOf(presentation.density) >= 0 && integer(presentation.card_min_height,picks ? 380 : 300,picks ? 720 : 540) && selected.length <= 48 && selected.every(validId) && new Set(selected).size === selected.length && selected.join(',') === state.selection.ids;
 }
 function validDom(root) { return !!controls && ['[data-home-oscars-editor]','[data-home-oscars-manual]','[data-oscars-lineup]','[data-oscars-search]','[data-oscars-search-button]','[data-oscars-results]','[data-oscars-status]'].every(function (selector) { return root.querySelectorAll(selector).length === 1; }); }
 function create(context) {
  var root = context.root, container = root.querySelector('[data-home-oscars-editor]'), parent = root.querySelector('[data-oscars-lineup]');
  var search = root.querySelector('[data-oscars-search]'), results = root.querySelector('[data-oscars-results]'), status = root.querySelector('[data-oscars-status]');
  var node = controls.node, button = controls.button, metadata = {}, images = {}, matches = [], lineup = [], imageEditors = [];
  var generation = 0, requestSequence = 0, controller = null, busy = false, loaded = false, loading = false, failed = false, openItems = new Set();
  var retry = button('Refresh lineup details', container, fetchItems); retry.hidden = true;
  var unused = node('p','',container,'description'), resetUnused = button('Reset unused image framing',container,function () {
   if (!enabled()) { return; } var state = context.getState(), saved = overrides(state);
   unusedIds().forEach(function (id) { delete saved[id]; }); state.artwork.overrides = serializeOverrides(saved); invalidate(); context.changed(); drawUnused(); fetchItems();
  });
  var previewFrame = root.querySelector('iframe');
  function enabled() { return !busy && !context.isBusy(); }
  function manual() { return context.getState().selection.mode === 'manual'; }
  function visibleIds() { return manual() ? ids(context.getState()) : lineup; }
  function unusedIds() { var retained = ids(context.getState()).concat(visibleIds()); return Object.keys(overrides(context.getState())).filter(function (id) { return retained.indexOf(Number(id)) < 0; }); }
  function drawUnused() { var count = unusedIds().length; unused.hidden = resetUnused.hidden = !count; unused.textContent = count+' unused image override(s) retained for items outside this lineup and your saved manual list.'; resetUnused.disabled = !enabled() || loading; }
  function invalidate() { generation += 1; requestSequence += 1; if (controller) { controller.abort(); controller = null; } list.cancelDrag(); }
  function update(next, focus) {
   if (!enabled() || !manual()) { return; }
   invalidate(); context.getState().selection.ids = next.join(','); context.changed(); draw(focus); fetchItems();
  }
  function move(from, to) {
   var next = ids(context.getState()); if (to < 0 || to >= next.length || from === to) { return; }
   var item = next.splice(from,1)[0]; next.splice(to,0,item); update(next,{key:item,direction:to < from ? 'earlier' : 'later'});
  }
  function imageValue(id) {
   var saved = overrides(context.getState())[id], source = metadata[id];
   return saved || {image_id:0,fit:source ? source.fit : 'cover',focal_x:source ? source.focal_x : 50,focal_y:source ? source.focal_y : 50,zoom:source ? source.zoom : 100};
  }
  function writeImage(id, patch) {
   var state = context.getState(), saved = overrides(state);
   if (!Object.prototype.hasOwnProperty.call(saved,id) && Object.keys(saved).length >= 48) { context.announce('Up to 48 items can have custom artwork. Reset another item before customizing this one.'); return false; }
   saved[id] = framing(Object.assign(imageValue(id),patch)); state.artwork.overrides = serializeOverrides(saved); context.changed(); drawUnused(); return true;
  }
  function storyRow(id, index) {
   var row = node('li', '', null, 'lunara-site-studio-order-row lunara-carousel-story'), item = metadata[id], owner = context.getState();
   row.dataset.oscarArtworkId = String(id);
   var header = node('div','',row,'lunara-carousel-story-heading'), thumb = controls.thumbnail(header,''), title = node('strong','',header);
   var warning = node('p','',row,'lunara-carousel-item-warning');
   if (manual()) {
    var actions = node('div','',row,'lunara-editor-actions');
    [['earlier',-1],['later',1]].forEach(function (entry) {
     var control = button(entry[0] === 'earlier' ? '↑' : '↓',actions,function () { move(index,index+entry[1]); });
     control.setAttribute('aria-label','Move '+entry[0]); control.title = 'Move '+entry[0]; control.dataset.editorMove = entry[0];
     control.disabled = !enabled() || index+entry[1] < 0 || index+entry[1] >= ids(context.getState()).length;
    });
    var remove = button('Remove',actions,function () {
     update(ids(context.getState()).filter(function (entry) { return entry !== id; }));
     var next = parent.children[Math.min(index,parent.children.length-1)], target = next && next.querySelector('button:not(:disabled),summary');
     if (target) { target.focus(); } else { search.focus(); }
    }); remove.dataset.oscarRemove = ''; remove.disabled = !enabled();
   }
   var details = node('details','',row,'lunara-carousel-story-editor'); details.open = openItems.has(id);
   node('summary','Edit image framing',details);
   details.addEventListener('toggle',function () { if (details.isConnected) { if (details.open) { openItems.add(id); } else { openItems.delete(id); } } });
   node('p','These image choices affect this homepage placement only.',details,'description');
   var fieldset = node('fieldset','',details); fieldset.style.border = '0'; fieldset.style.padding = '0'; fieldset.style.margin = '0'; fieldset.style.minWidth = '0';
   function imageEnabled() { return enabled() && loaded && !loading && !failed && row.isConnected && owner === context.getState() && visibleIds().indexOf(id) >= 0 && !!metadata[id] && metadata[id].available && metadata[id].image_allowed; }
   var editor = controls.image({parent:fieldset,aspect:'16 / 10',getValue:function () { return imageValue(id); },enabled:imageEnabled,ticket:function () { return generation; },announce:context.announce,
    source:function () { var current = metadata[id]; return {url:current ? current.image_url : '',label:current ? current.image_source : 'Source details unavailable'}; },
    image:function (imageId) { return images[imageId] || ''; },selectedImage:function (imageId,url) { images[imageId] = url; },
    change:function (patch) { if (!imageEnabled() || !writeImage(id,patch)) { return; } updateImage(); if (patch.image_id && !images[patch.image_id]) { fetchItems(); } }
   });
   var reset = button('Reset image framing',details,function () {
    if (!enabled() || owner !== context.getState() || !row.isConnected) { return; } var state = context.getState(), saved = overrides(state); delete saved[id]; state.artwork.overrides = serializeOverrides(saved); invalidate(); context.changed(); updateImage(); editor.render(); drawUnused(); fetchItems();
   });
   function updateImage() {
    item = metadata[id]; var value = imageValue(id), imageUrl = value.image_id ? images[value.image_id] : item && item.image_allowed ? item.image_url : '';
    title.textContent = (index+1)+'. '+(item ? item.title : 'Selection #'+id);
    var nextThumb = controls.thumbnail(null,imageUrl); thumb.replaceWith(nextThumb); thumb = nextThumb;
    warning.textContent = failed ? 'Source details could not refresh. Use Refresh lineup details to retry.' : !loaded || loading ? 'Loading source details…' : !item || !item.available ? 'Unavailable or unpublished — retained here, skipped on the homepage.' : !item.image_allowed ? 'Artwork is held or unverified. Update this Fact’s artwork verification before using an image here.' : '';
    warning.hidden = !warning.textContent; fieldset.disabled = !imageEnabled(); reset.disabled = !Object.prototype.hasOwnProperty.call(overrides(context.getState()),id) || !enabled();
   }
   imageEditors.push({id:id,editor:editor,update:updateImage}); updateImage(); return row;
  }
  var list = controls.orderedList({parent:parent,enabled:function () { return enabled() && manual(); },items:visibleIds,key:function (id) { return id; },move:move,row:storyRow});
  function drawStatus() {
   var state = context.getState(), selected = ids(state);
   retry.hidden = !failed; retry.disabled = !enabled() || loading;
   if (manual() && !selected.length) { status.textContent = 'Manual lineup is empty. This section will be hidden after Apply.'; return; }
   if (failed) { status.textContent = 'Could not load lineup details. Your selections and image choices are retained. Refresh lineup details to retry.'; return; }
   if (loading) { status.textContent = 'Loading lineup details…'; return; }
   if (manual()) {
    var unavailable = loaded ? selected.filter(function (id) { return !metadata[id] || !metadata[id].available; }).length : 0;
    status.textContent = !selected.length ? 'Manual lineup is empty. This section will be hidden after Apply.' : unavailable ? unavailable+' unavailable selection(s) will be skipped.' : selected.length+' selected. Drag to reorder or use Move earlier / Move later.';
   } else { status.textContent = (state.selection.mode === 'legacy' ? 'Existing public selection rules are preserved.' : 'Newest published items appear first. Your manual list is retained.') + (loaded && !lineup.length ? ' No eligible published items are available.' : ''); }
  }
  function drawResults() {
   var selected = ids(context.getState()); results.replaceChildren();
   matches.forEach(function (id) {
    var item = metadata[id]; if (!item || !item.available || selected.indexOf(id) >= 0) { return; }
    var add = button('Add: '+item.title,results,function () { update(ids(context.getState()).concat([id])); }); add.disabled = !enabled() || !manual() || selected.length >= 48;
   });
  }
  function draw(focus) {
   root.querySelector('[data-home-oscars-manual]').hidden = !manual(); imageEditors = []; list.render(focus);
   // orderedList always marks rows draggable; automatic lineups are read-only.
   if (!manual()) { Array.from(parent.children).forEach(function (row) { row.draggable = false; }); }
   imageEditors.forEach(function (entry) { entry.update(); entry.editor.render(); }); drawResults(); drawStatus(); drawUnused(); syncFraming();
  }
  function itemsUrl() {
   var state = context.getState(), url = new URL(context.config.endpoints.save,window.location.href);
   if (url.origin !== window.location.origin || url.username || url.password) { throw new Error('destination'); }
   if (url.searchParams.has('rest_route')) { url.searchParams.set('rest_route',url.searchParams.get('rest_route').replace(/\/save$/,'/items')); } else { url.pathname = url.pathname.replace(/\/save$/,'/items'); }
   url.searchParams.set('ids',state.selection.ids); url.searchParams.set('search',search.value);
   url.searchParams.set('mode',state.selection.mode); url.searchParams.set('count',String(state.presentation.count));
   var saved = overrides(state); url.searchParams.set('image_ids',Array.from(new Set(Object.keys(saved).map(function (id) { return saved[id].image_id; }).filter(Boolean))).join(','));
   if (state.selection.ceremony_year) { url.searchParams.set('year',String(state.selection.ceremony_year)); } return url;
  }
  function validPayload(payload, url) {
   if (!payload || !Array.isArray(payload.items) || !Array.isArray(payload.results) || !Array.isArray(payload.lineup) || !payload.images || typeof payload.images !== 'object' || Array.isArray(payload.images) || payload.items.length > 84 || payload.results.length > 20 || payload.lineup.length > Number(url.searchParams.get('count')) || Object.keys(payload.images).length > 48) { return false; }
   var next = {}, good = payload.items.every(function (item) {
    if (!item || !validId(item.id) || next[item.id] || typeof item.title !== 'string' || typeof item.available !== 'boolean' || typeof item.image_allowed !== 'boolean' || typeof item.image_url !== 'string' || typeof item.image_source !== 'string' || !validFraming(framing(item)) || item.image_url && !controls.imageUrl(item.image_url)) { return false; }
    next[item.id] = item; return true;
   });
   if (!good) { return false; }
   function validList(values) { return values.every(function (id) { return validId(id) && next[id] && next[id].available; }) && new Set(values).size === values.length; }
   var selected = (url.searchParams.get('ids') || '').split(',').filter(Boolean), requestedImages = (url.searchParams.get('image_ids') || '').split(',').filter(Boolean);
   return validList(payload.results) && validList(payload.lineup) && selected.every(function (id) { return !!next[id]; }) && Object.keys(payload.images).every(function (id) { return /^[1-9][0-9]{0,9}$/.test(id) && requestedImages.indexOf(id) >= 0 && typeof payload.images[id] === 'string' && (!payload.images[id] || !!controls.imageUrl(payload.images[id])); }) && requestedImages.every(function (id) { return Object.prototype.hasOwnProperty.call(payload.images,id); });
  }
  function fetchItems() {
   if (!enabled()) { return; }
   requestSequence += 1; var ticket = generation, sequence = requestSequence;
   if (controller) { controller.abort(); } controller = window.AbortController ? new AbortController() : null;
   loading = true; failed = false; drawStatus(); drawUnused(); imageEditors.forEach(function (entry) { entry.update(); entry.editor.render(); });
   var url; try { url = itemsUrl(); } catch (error) { loading = false; failed = true; drawStatus(); return; }
   window.fetch(url.href,{credentials:'same-origin',headers:{'X-WP-Nonce':context.config.nonce},signal:controller ? controller.signal : undefined}).then(function (response) { if (!response.ok) { throw new Error('request'); } return response.json(); }).then(function (payload) {
    if (ticket !== generation || sequence !== requestSequence || !enabled()) { return; }
    if (!validPayload(payload,url)) { throw new Error('shape'); }
    metadata = {}; payload.items.forEach(function (item) { metadata[item.id] = item; }); images = payload.images; matches = payload.results; lineup = payload.lineup; loaded = true; loading = false; failed = false;
    // Preserve keyboard focus if a metadata request finishes after a Move action.
    var active = document.activeElement, row = active && active.closest('[data-editor-key]'), focus = row && active.dataset.editorMove ? {key:row.dataset.editorKey,direction:active.dataset.editorMove} : null;
    draw(focus);
   }).catch(function () {
    if (ticket !== generation || sequence !== requestSequence || !enabled()) { return; }
    loading = false; failed = true; loaded = false; metadata = {}; images = {}; matches = []; drawResults(); drawStatus(); drawUnused(); imageEditors.forEach(function (entry) { entry.update(); entry.editor.render(); });
   });
  }
  function syncFraming() {
   if (!previewFrame) { return; }
   var mobile = Number(previewFrame.getAttribute('width')) <= 820, ratio = !mobile && context.config.surface === 'home-oscar-picks' ? 2.1 : 1.6;
   imageEditors.forEach(function (entry) {
    var width = ratio, height = 1;
    try {
     var row = previewFrame.contentDocument.querySelector('[data-lunara-oscar-item-id="'+entry.id+'"]');
     var media = row && row.querySelector('.lunara-oscar-pick-card-media,.lunara-oscar-fact-card-poster');
     if (media) { var rect = media.getBoundingClientRect(); if (rect.width > 0 && rect.height > 0) { width = rect.width; height = rect.height; } }
    } catch (error) { /* Use the public frame ratio until the private preview is available. */ }
    entry.editor.setAspect(width,height);
   });
  }
  if (previewFrame) { previewFrame.addEventListener('load',syncFraming); if (window.MutationObserver) { new MutationObserver(syncFraming).observe(previewFrame,{attributes:true,attributeFilter:['width','height']}); } }
  var interval = root.querySelector('[data-oscars-interval]');
  interval.addEventListener('change',function () { var value = Math.round(Number(interval.value)*1000); if (enabled() && integer(value,0,12000)) { context.getState().presentation.autoplay_interval = value; context.changed(); } else { interval.value = String(context.getState().presentation.autoplay_interval/1000); } });
  root.addEventListener('change',function (event) { if (event.target.matches('[data-field-path="presentation.count"]')) { Promise.resolve().then(function () { if (enabled()) { render(); } }); } });
  function render() {
   invalidate(); metadata = {}; images = {}; matches = []; lineup = []; loaded = false; loading = false; failed = false;
   interval.value = String(context.getState().presentation.autoplay_interval/1000); draw(); fetchItems();
  }
  root.querySelector('[data-oscars-search-button]').addEventListener('click',fetchItems);
  search.addEventListener('keydown',function (event) { if (event.key === 'Enter') { event.preventDefault(); fetchItems(); } });
  return {render:render,invalidate:invalidate,setBusy:function (value) {
   busy = value; if (value) { invalidate(); } imageEditors.forEach(function (entry) { entry.update(); entry.editor.render(); }); drawResults(); drawStatus(); drawUnused();
   Array.from(parent.children).forEach(function (row,index) { row.querySelectorAll('[data-editor-move],[data-oscar-remove]').forEach(function (control) { var direction = control.dataset.editorMove; control.disabled = !enabled() || direction === 'earlier' && index === 0 || direction === 'later' && index === parent.children.length-1; }); });
   search.disabled = value; interval.disabled = value; root.querySelector('[data-oscars-search-button]').disabled = value; if (!value) { fetchItems(); }
  }};
 }
 window.LunaraSiteStudioHomeOscarsEditor = {validateState:validState,validateDom:validDom,create:create};
}());
