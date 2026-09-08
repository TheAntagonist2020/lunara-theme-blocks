/* Carousel field adapter. The shared Site Studio host owns every save and preview. */
(function () {
 'use strict';
 var controls = window.LunaraEditorControls;
 function validState(state) {
  var keys = ['adopted','mode','heading','autoplay','interval','overlay','slides'];
  if (!state || typeof state !== 'object' || JSON.stringify(Object.keys(state).sort()) !== JSON.stringify(keys.sort()) || typeof state.adopted !== 'boolean' || ['auto','manual'].indexOf(state.mode) < 0 || typeof state.heading !== 'string' || state.heading.length > 160 || [0,1].indexOf(state.autoplay) < 0 || !Number.isInteger(state.interval) || state.interval < 3 || state.interval > 30 || !Number.isInteger(state.overlay) || state.overlay < 20 || state.overlay > 100 || !Array.isArray(state.slides) || state.slides.length > 48) { return false; }
  var seen = {}; return state.slides.every(function (slide) {
   if (!slide || JSON.stringify(Object.keys(slide).sort()) !== JSON.stringify(['post_id','image_id','headline','excerpt','kicker','cta','overlay','focal_x','focal_y','zoom','fit'].sort()) || !Number.isSafeInteger(slide.post_id) || slide.post_id < 1 || seen[slide.post_id] || !Number.isSafeInteger(slide.image_id) || slide.image_id < 0 || ['cover','full'].indexOf(slide.fit) < 0) { return false; }
   var limits = {headline:240,excerpt:600,kicker:60,cta:40};
   if (!Object.keys(limits).every(function (key) { return typeof slide[key] === 'string' && slide[key].length <= limits[key]; })) { return false; }
   seen[slide.post_id] = true; return ['overlay','focal_x','focal_y','zoom'].every(function (key) { return Number.isInteger(slide[key]) && slide[key] >= (key === 'zoom' ? 100 : 0) && slide[key] <= (key === 'zoom' ? 112 : 100); });
  });
 }
 function validDom(root) {
  return !!controls && ['[data-carousel-editor]','[data-carousel-manual]','[data-carousel-items]','[data-carousel-results]','[data-carousel-search]','[data-carousel-search-button]','[data-carousel-empty]','#lunara-carousel-metadata'].every(function (selector) { return root.querySelectorAll(selector).length === 1; });
 }
 function create(context) {
  var root = context.root, config = context.config, container = root.querySelector('[data-carousel-editor]');
  var list = root.querySelector('[data-carousel-items]'), results = root.querySelector('[data-carousel-results]');
  var metadata = {items:{},images:{},automatic:[]}, version = 0, searchSequence = 0, metadataSequence = 0;
  var metadataKey = '', searchItems = [], searchPending = false, openItems = new Set(), frozen = [], busy = false;
  var imageEditors = [], imageRenders = [], node = controls.node, button = controls.button;
  try { mergeMetadata(JSON.parse(root.querySelector('#lunara-carousel-metadata').textContent)); } catch (error) { /* A failed metadata refresh has a visible retry path. */ }
  var automatic = root.querySelector('[data-carousel-automatic]');
  if (!automatic) { automatic = node('div', '', container); automatic.dataset.carouselAutomatic = ''; }
  var automaticList = node('ol', '', automatic, 'lunara-carousel-automatic-list');
  var adoptLineup = button('Use this lineup in Manual', automatic, function () {
   if (!enabled() || !metadata.automatic.length) { return; }
   var current = context.getState();
   if (current.slides.length && !window.confirm('Replace your saved manual lineup with these stories? This stays private until Apply changes.')) { return; }
   var previous = {}; current.slides.forEach(function (slide) { previous[slide.post_id] = slide; });
   current.slides = metadata.automatic.map(function (id) { return previous[id] || blankSlide(id); }); current.mode = 'manual';
   invalidate(); render(); context.changed();
  });
  var metadataStatus = node('p', '', container, 'lunara-editor-metadata-status');
  var retry = button('Refresh story details', container, function () { metadataKey = ''; refreshMetadata(); }); retry.hidden = true;
  function enabled() { return !busy && !context.isBusy(); }
  function blankSlide(id) { return {post_id:id,image_id:0,headline:'',excerpt:'',kicker:'',cta:'',overlay:0,focal_x:50,focal_y:30,zoom:100,fit:'cover'}; }
  function itemMeta(id) { return metadata.items[id] || {id:id,title:'Item #' + id,available:false,image_url:'',date_label:''}; }
  function mergeMetadata(payload) {
   if (!payload || typeof payload !== 'object') { return false; }
   if (payload.items && !Array.isArray(payload.items) && typeof payload.items === 'object') {
    Object.keys(payload.items).forEach(function (key) {
     var item = payload.items[key]; if (item && typeof item.title === 'string' && Number.isSafeInteger(Number(key)) && Number(key) > 0) { metadata.items[key] = item; }
    });
   }
   if (payload.images && typeof payload.images === 'object') {
    Object.keys(payload.images).forEach(function (id) { if (Number.isSafeInteger(Number(id)) && Number(id) > 0) { metadata.images[id] = controls.imageUrl(payload.images[id]); } });
   }
   if (Array.isArray(payload.automatic)) { metadata.automatic = payload.automatic.filter(function (id, i, ids) { return Number.isSafeInteger(id) && id > 0 && ids.indexOf(id) === i; }).slice(0,6); }
   return true;
  }
  function readUrl(action) {
   var url = new URL(config.endpoints.state, window.location.href), route = url.searchParams.get('rest_route');
   if (url.origin !== window.location.origin || url.username || url.password) { throw new Error('The editor destination is invalid.'); }
   if (route) { url.searchParams.set('rest_route', route.replace(/\/state$/, '/' + action)); }
   else { url.pathname = url.pathname.replace(/\/state$/, '/' + action); }
   return url;
  }
  async function refreshMetadata() {
   var state = context.getState(), ids = state.slides.map(function (slide) { return slide.post_id; }), images = state.slides.map(function (slide) { return slide.image_id; }).filter(Boolean);
   var key = JSON.stringify([ids,images]); if (metadataKey === key) { return; } metadataKey = key;
   var sequence = ++metadataSequence, ticket = version;
   try {
    var url = readUrl('metadata'); url.searchParams.set('ids', ids.join(',')); url.searchParams.set('image_ids', images.join(','));
    var response = await fetch(url.href, {credentials:'same-origin',headers:{'X-WP-Nonce':config.nonce}}), payload = await response.json();
    if (sequence !== metadataSequence || ticket !== version) { return; }
    if (!response.ok || !payload || !payload.items || !Array.isArray(payload.automatic)) { throw new Error('Story details could not refresh. Your choices are still here.'); }
    mergeMetadata(payload); metadataStatus.textContent = ''; retry.hidden = true;
    imageRenders.forEach(function (update) { update(); }); imageEditors.forEach(function (editor) { editor.render(); }); renderAutomatic(); if (!searchPending) { renderSearchResults(); }
   } catch (error) { if (sequence === metadataSequence && ticket === version) { metadataKey = ''; metadataStatus.textContent = 'Story details could not refresh. Your choices are still here.'; retry.hidden = false; } }
  }
  function summary(parent, item, index) {
   var header = node('div', '', parent, 'lunara-carousel-story-heading');
   var art = controls.thumbnail(header, item.image_url);
   var copy = node('div', '', header), title = node('strong', (index === null ? '' : (index + 1) + '. ') + item.title, copy);
   var details = node('span', [item.type === 'review' ? 'Review' : item.type === 'journal' ? 'Journal' : '',item.date_label || ''].filter(Boolean).join(' · '), copy, 'lunara-carousel-story-date');
   return {art:art,title:title,details:details};
  }
  function renderAutomatic() {
   automatic.hidden = context.getState().mode !== 'auto'; automaticList.replaceChildren();
   metadata.automatic.forEach(function (id, index) { var row = node('li', '', automaticList); summary(row, itemMeta(id), index); });
   if (!metadata.automatic.length) { node('li', 'No eligible published stories. This section will be hidden when applied.', automaticList); }
   adoptLineup.disabled = !enabled() || metadata.automatic.length === 0;
  }
  function copyField(parent, slide, field, title, max, type, update) {
   var label = node('label', title, parent, 'lunara-editor-field'), input = node(type === 'textarea' ? 'textarea' : 'input', '', label);
   if (type !== 'textarea') { input.type = 'text'; } input.maxLength = max; input.value = slide[field]; input.dataset.carouselOverride = field;
   var inheritedKey = field === 'headline' ? 'title' : field;
   input.placeholder = itemMeta(slide.post_id)[inheritedKey] || 'Use article text';
   input.addEventListener('input', function () { if (!enabled()) { return; } slide[field] = input.value; context.changed(); update(); });
  }
  function currentSlide(slide) { return context.getState().slides.indexOf(slide) >= 0; }
  function storyRow(slide, index) {
   var row = node('li', '', null, 'lunara-carousel-story'); row.dataset.postId = String(slide.post_id);
   var header = summary(row, itemMeta(slide.post_id), index);
   var warning = node('p', '', row, 'lunara-carousel-item-warning');
   var actions = node('div', '', row, 'lunara-editor-actions');
   var up = button('Move up', actions, function () { if (enabled()) { move(index, index - 1, 'up'); } }); up.dataset.editorMove = 'up'; up.disabled = index === 0;
   var down = button('Move down', actions, function () { if (enabled()) { move(index, index + 1, 'down'); } }); down.dataset.editorMove = 'down'; down.disabled = index === context.getState().slides.length - 1;
   button('Remove story', actions, function () {
    if (!enabled()) { return; } context.getState().slides.splice(index, 1); openItems.delete(slide.post_id); invalidate(); render(); context.changed();
    var next = list.children[Math.min(index, list.children.length - 1)]; if (next) { next.querySelector('summary').focus(); } else { root.querySelector('[data-carousel-search]').focus(); }
   });
   var details = node('details', '', row, 'lunara-carousel-story-editor'); details.open = openItems.has(slide.post_id);
   node('summary', 'Edit image and text', details); details.addEventListener('toggle', function () { if (!details.isConnected) { return; } if (details.open) { openItems.add(slide.post_id); } else { openItems.delete(slide.post_id); } });
   node('p', 'These changes affect this homepage placement. Leave text blank to use the article.', details, 'description');
   var copyPreview = node('div', '', details, 'lunara-carousel-copy-preview');
   var previewLabel = node('small', '', copyPreview), previewHeadline = node('strong', '', copyPreview), previewExcerpt = node('p', '', copyPreview), previewButton = node('span', '', copyPreview);
   function update() {
    var meta = itemMeta(slide.post_id), title = slide.headline || meta.title;
    header.title.textContent = (index + 1) + '. ' + title;
    header.details.textContent = [meta.type === 'review' ? 'Review' : meta.type === 'journal' ? 'Journal' : '',meta.date_label || ''].filter(Boolean).join(' · ');
    warning.hidden = !!meta.available; warning.textContent = 'Unavailable or unpublished — retained here, skipped on the homepage.';
    var image = slide.image_id ? metadata.images[slide.image_id] : meta.image_url;
    var nextArt = controls.thumbnail(null, image); header.art.replaceWith(nextArt); header.art = nextArt;
    previewLabel.textContent = slide.kicker || meta.kicker || (meta.type === 'review' ? 'Review' : 'Journal');
    previewHeadline.textContent = title; previewExcerpt.textContent = slide.excerpt || meta.excerpt || '';
    previewButton.textContent = slide.cta || meta.cta || 'Read the story';
   }
   imageRenders.push(update);
   var image = controls.image({parent:details,aspect:config.surface === 'journal-carousel' ? '16 / 10' : '16 / 9',getValue:function () { return slide; },
    enabled:function () { return enabled() && currentSlide(slide); },ticket:function () { return version; },announce:context.announce,
    source:function () { var meta = itemMeta(slide.post_id); return {url:meta.image_url,label:meta.image_source}; },image:function (id) { return metadata.images[id] || ''; },
    selectedImage:function (id, url) { if (url) { metadata.images[id] = url; } },
    change:function (patch) { Object.keys(patch).forEach(function (key) { slide[key] = patch[key]; }); context.changed(); update(); if (patch.image_id && !metadata.images[patch.image_id]) { metadataKey = ''; refreshMetadata(); } }
   }); imageEditors.push(image);
   if (config.surface === 'hero-carousel') { controls.range(details, 'Overlay strength (0 uses section setting)', 'overlay', 0, 100, function () { return slide; }, function (key, value) { if (enabled()) { slide[key] = value; context.changed(); } }); }
   copyField(details, slide, 'headline', 'Headline', 240, 'text', update); copyField(details, slide, 'excerpt', 'Excerpt', 600, 'textarea', update);
   copyField(details, slide, 'kicker', 'Label', 60, 'text', update); copyField(details, slide, 'cta', 'Button text', 40, 'text', update); update(); return row;
  }
  var ordered = controls.orderedList({parent:list,items:function () { return context.getState().slides; },key:function (slide) { return slide.post_id; },row:storyRow,enabled:enabled,move:move});
  var previewFrame = root.querySelector('iframe');
  function syncFraming() {
   if (!previewFrame) { return; }
   try {
    var media = previewFrame.contentDocument.querySelector(config.surface === 'hero-carousel' ? '.lunara-cinematic-hero-bg' : '.lunara-home-news-media');
    if (media) { var rect = media.getBoundingClientRect(); imageEditors.forEach(function (editor) { editor.setAspect(rect.width, rect.height); }); }
   } catch (error) { /* Keep the framing guide until the same-origin preview is ready. */ }
  }
  if (previewFrame) { previewFrame.addEventListener('load', syncFraming); if (window.MutationObserver) { new MutationObserver(syncFraming).observe(previewFrame, {attributes:true,attributeFilter:['width','height']}); } }
  function move(index, destination, direction) {
   var slides = context.getState().slides;
   if (!enabled() || destination < 0 || destination >= slides.length || index === destination) { return; }
   var slide = slides.splice(index, 1)[0]; slides.splice(destination, 0, slide); render({key:slide.post_id,direction:direction || 'up'}); context.changed(); context.announce('Story order updated.');
  }
  function renderSearchResults() {
   results.replaceChildren(); var selected = context.getState().slides.map(function (slide) { return slide.post_id; });
   searchItems.forEach(function (item) {
    var card = node('div', '', results, 'lunara-carousel-search-result'); summary(card, item, null);
    var chosen = selected.indexOf(item.id) >= 0;
    var add = button(chosen ? 'Selected' : 'Add story', card, function () {
     if (!enabled() || context.getState().mode !== 'manual') { return; }
     var slides = context.getState().slides;
     if (slides.length >= 48) { context.announce('A carousel can contain up to 48 stories.'); return; }
     if (slides.some(function (slide) { return slide.post_id === item.id; })) { return; }
     metadata.items[item.id] = Object.assign({available:true},item); slides.push(blankSlide(item.id)); render(); context.changed();
    }); add.disabled = chosen || !enabled(); add.setAttribute('aria-label', (chosen ? 'Selected: ' : 'Add story: ') + item.title);
   });
  }
  async function search() {
   if (!enabled() || context.getState().mode !== 'manual') { return; }
   var sequence = ++searchSequence, ticket = version; searchPending = true; results.replaceChildren(); node('p', 'Searching stories…', results);
   try {
    var url = readUrl('search'); url.searchParams.set('search', root.querySelector('[data-carousel-search]').value);
    var response = await fetch(url.href, {credentials:'same-origin',headers:{'X-WP-Nonce':config.nonce}}), payload = await response.json();
    if (sequence !== searchSequence || ticket !== version) { return; }
    if (!response.ok || !payload || !Array.isArray(payload.items)) { throw new Error('Search could not load. Try again.'); }
    searchItems = payload.items.filter(function (item) { return item && Number.isSafeInteger(item.id) && item.id > 0 && typeof item.title === 'string' && item.available !== false; });
    searchItems.forEach(function (item) { metadata.items[item.id] = Object.assign({available:true}, item); });
    renderSearchResults(); if (!searchItems.length) { node('p', 'No published stories found.', results); }
   } catch (error) { if (sequence === searchSequence && ticket === version) { results.replaceChildren(); node('p', 'Search could not load. Try again.', results); } }
   finally { if (sequence === searchSequence) { searchPending = false; } }
  }
  root.querySelector('[data-carousel-search-button]').addEventListener('click', search);
  root.querySelector('[data-carousel-search]').addEventListener('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); search(); } });
  root.querySelectorAll('[data-carousel-field]').forEach(function (input) {
   input.addEventListener('input', function () {
    if (!enabled()) { return; } var key = input.dataset.carouselField;
    context.getState()[key] = input.type === 'checkbox' ? Number(input.checked) : input.type === 'number' || input.type === 'range' ? Number(input.value) : input.value;
    if (key === 'mode') { invalidate(); render(); } context.changed();
   });
  });
  function invalidate() { version++; searchSequence++; metadataSequence++; metadataKey = ''; ordered.cancelDrag(); searchPending = false; }
  function setBusy(value) {
   busy = value;
   if (value) { frozen = []; container.querySelectorAll('input,select,textarea,button').forEach(function (control) { frozen.push([control,control.disabled]); control.disabled = true; }); }
   else { frozen.forEach(function (entry) { entry[0].disabled = entry[1]; }); frozen = []; renderAutomatic(); if (!searchPending) { renderSearchResults(); } imageEditors.forEach(function (editor) { editor.render(); }); }
  }
  function render(focus) {
   root.querySelectorAll('[data-carousel-field]').forEach(function (input) { var value = context.getState()[input.dataset.carouselField]; if (input.type === 'checkbox') { input.checked = !!value; } else { input.value = value; } });
   root.querySelector('[data-carousel-manual]').hidden = context.getState().mode !== 'manual';
   root.querySelector('[data-carousel-empty]').hidden = context.getState().slides.length !== 0;
   imageEditors = []; imageRenders = []; ordered.render(focus); renderAutomatic(); if (!searchPending) { renderSearchResults(); } syncFraming();
   var notice = root.querySelector('[data-carousel-adoption-notice]'); if (notice) { notice.hidden = context.getState().adopted; }
   refreshMetadata();
  }
  return {render:render,setBusy:setBusy,invalidate:invalidate};
 }
 window.LunaraSiteStudioCarouselEditor = {validateState:validState,validateDom:validDom,create:create};
}());
