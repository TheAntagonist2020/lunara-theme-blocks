(function () {
 'use strict';
 var root = document.querySelector('[data-lunara-site-studio]');
 var config = window.LunaraSiteStudioWorkspaceConfig;
 if (!root || !config || ['hero-carousel', 'journal-carousel'].indexOf(config.surface) < 0 || !config.endpoints || !config.nonce) { return; }
 var frame = root.querySelector('iframe'), status = root.querySelector('[data-workspace-status]');
 var baseline, candidate, metadata;
 try { baseline = JSON.parse(document.getElementById('lunara-site-studio-state').textContent); metadata = JSON.parse(document.getElementById('lunara-carousel-metadata').textContent); } catch (error) { return; }
 if (!baseline || !Array.isArray(baseline.slides) || !frame || !status) { return; }
 function validState(state) {
  var keys = ['adopted','mode','heading','autoplay','interval','overlay','slides'];
  if (!state || typeof state !== 'object' || JSON.stringify(Object.keys(state).sort()) !== JSON.stringify(keys.sort()) || typeof state.adopted !== 'boolean' || ['auto','manual'].indexOf(state.mode) < 0 || typeof state.heading !== 'string' || [0,1].indexOf(state.autoplay) < 0 || !Number.isInteger(state.interval) || state.interval < 3 || state.interval > 30 || !Number.isInteger(state.overlay) || state.overlay < 20 || state.overlay > 100 || !Array.isArray(state.slides) || state.slides.length > 48) { return false; }
  var seen = {}; return state.slides.every(function (slide) {
   if (!slide || JSON.stringify(Object.keys(slide).sort()) !== JSON.stringify(['post_id','image_id','headline','excerpt','kicker','cta','overlay','focal_x','focal_y','zoom','fit'].sort()) || !Number.isInteger(slide.post_id) || slide.post_id < 1 || seen[slide.post_id] || !Number.isInteger(slide.image_id) || slide.image_id < 0 || ['cover','full'].indexOf(slide.fit) < 0 || !['headline','excerpt','kicker','cta'].every(function (key) { return typeof slide[key] === 'string'; })) { return false; }
   seen[slide.post_id] = true; return ['overlay','focal_x','focal_y','zoom'].every(function (key) { return Number.isInteger(slide[key]) && slide[key] >= (key === 'zoom' ? 100 : 0) && slide[key] <= (key === 'zoom' ? 112 : 100); });
  });
 }
 if (!validState(baseline)) { return; }
 var clone = function (value) { return JSON.parse(JSON.stringify(value)); };
 candidate = clone(baseline);
 var busy = false, generation = 0, searchGeneration = 0, dragId = null, previewFingerprint = '', previewInstance = '', mediaGeneration = 0;
 var list = root.querySelector('[data-carousel-items]'), results = root.querySelector('[data-carousel-results]');
 function text(tag, content, parent) { var node = document.createElement(tag); node.textContent = content; if (parent) { parent.appendChild(node); } return node; }
 function dirty() { return JSON.stringify(candidate) !== JSON.stringify(baseline); }
 function announce(message) { status.textContent = message; root.setAttribute('data-dirty', dirty() ? 'true' : 'false'); root.setAttribute('data-workspace-state', dirty() ? 'dirty' : 'live'); }
 function changed() { announce(previewFingerprint && previewFingerprint !== JSON.stringify(candidate) ? 'Preview is out of date. Preview Changes again.' : 'Unsaved changes. Preview, then Apply when ready.'); }
 function freeze(value) { busy = value; root.querySelectorAll('input,select,textarea,button').forEach(function (node) { node.disabled = value; }); root.querySelector('.lunara-site-studio-inspector').setAttribute('aria-busy', String(value)); }
 function endpoint(name) { var url = new URL(config.endpoints[name], window.location.href); if (url.origin !== window.location.origin) { throw new Error('Invalid workspace destination.'); } return url.href; }
 async function request(name, body) {
  var response = await fetch(endpoint(name), {method: body ? 'POST' : 'GET', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce}, body: body ? JSON.stringify(body) : undefined});
  var payload = await response.json(); if (!response.ok) { throw new Error(payload.message || 'The request failed. Your changes are still here.'); } return payload;
 }
 function button(label, action, parent) { var node = text('button', label, parent); node.type = 'button'; node.addEventListener('click', function () { if (!busy) { action(); } }); return node; }
 function field(parent, slide, key, label, type, min, max) {
  var wrap = text('label', label, parent), input = document.createElement(type === 'textarea' ? 'textarea' : 'input');
  if (type !== 'textarea') { input.type = type || 'text'; } input.value = slide[key];
  if (min !== undefined) { input.min = min; input.max = max; } if (type === 'text') { input.maxLength = key === 'headline' ? 240 : key === 'kicker' ? 60 : 40; } if (type === 'textarea') { input.maxLength = 600; }
  input.addEventListener('input', function () { slide[key] = type === 'number' || type === 'range' ? Number(input.value) : input.value; changed(); }); wrap.appendChild(input);
 }
 function move(index, destination) { if (destination < 0 || destination >= candidate.slides.length) { return; } var slide = candidate.slides.splice(index, 1)[0]; candidate.slides.splice(destination, 0, slide); render(); changed(); }
 function render() {
  root.querySelectorAll('[data-carousel-field]').forEach(function (input) { var key = input.dataset.carouselField; if (input.type === 'checkbox') { input.checked = !!candidate[key]; } else { input.value = candidate[key]; } });
  root.querySelector('[data-carousel-manual]').hidden = candidate.mode !== 'manual'; root.querySelector('[data-carousel-empty]').hidden = candidate.slides.length !== 0;
  list.replaceChildren();
  candidate.slides.forEach(function (slide, index) {
   var row = document.createElement('li'); row.draggable = true; row.dataset.postId = String(slide.post_id); list.appendChild(row);
   var meta = metadata[slide.post_id] || {title: 'Item #' + slide.post_id, available: false}; text('strong', (index + 1) + '. ' + meta.title, row);
   if (!meta.available) { text('p', 'Unavailable or unpublished — retained here, skipped on homepage.', row); }
   var controls = text('div', '', row); controls.className = 'lunara-carousel-item-actions';
   button('Move up', function () { move(index, index - 1); }, controls); button('Move down', function () { move(index, index + 1); }, controls);
   button('Remove', function () { candidate.slides.splice(index, 1); render(); changed(); }, controls);
   var details = document.createElement('details'); row.appendChild(details); text('summary', 'Image and text overrides', details);
   var imageLabel = text('p', slide.image_id ? 'Image override: attachment #' + slide.image_id : 'Uses source image', details);
   button('Choose image', function () {
    if (!window.wp || !wp.media) { announce('Media Library is unavailable.'); return; }
    var currentGeneration = mediaGeneration, picker = wp.media({title: 'Carousel image override', library: {type: 'image'}, multiple: false});
    picker.on('select', function () { if (busy || currentGeneration !== mediaGeneration || candidate.slides.indexOf(slide) < 0) { return; } var image = picker.state().get('selection').first().toJSON(); slide.image_id = Number(image.id); imageLabel.textContent = 'Image override: attachment #' + slide.image_id; changed(); }); picker.open();
   }, details);
   button('Use source image', function () { slide.image_id = 0; imageLabel.textContent = 'Uses source image'; changed(); }, details);
   if (config.surface === 'hero-carousel') { field(details, slide, 'overlay', 'Overlay override (0 inherits global)', 'range', 0, 100); }
   field(details, slide, 'focal_x', 'Horizontal focal point (%)', 'range', 0, 100); field(details, slide, 'focal_y', 'Vertical focal point (%)', 'range', 0, 100); field(details, slide, 'zoom', 'Zoom (%)', 'range', 100, 112);
   var fitLabel = text('label', 'Image fit', details), fit = document.createElement('select'); [['cover','Fill frame'],['full','Show full image']].forEach(function (item) { var option = text('option', item[1], fit); option.value = item[0]; }); fit.value = slide.fit; fit.addEventListener('change', function () { slide.fit = fit.value; changed(); }); fitLabel.appendChild(fit);
   field(details, slide, 'headline', 'Headline (blank inherits)', 'text'); field(details, slide, 'excerpt', 'Excerpt (blank inherits)', 'textarea'); field(details, slide, 'kicker', 'Kicker (blank inherits)', 'text'); field(details, slide, 'cta', 'Button text (blank inherits)', 'text');
   row.addEventListener('dragstart', function (event) { if (busy || event.target.closest('input,textarea,select')) { event.preventDefault(); return; } dragId = slide.post_id; event.dataTransfer.setData('text/plain', String(dragId)); });
   row.addEventListener('dragover', function (event) { if (!busy && dragId !== null) { event.preventDefault(); } });
   row.addEventListener('drop', function (event) { event.preventDefault(); if (busy || dragId === null) { return; } var from = candidate.slides.findIndex(function (item) { return item.post_id === dragId; }); dragId = null; if (from >= 0) { move(from, index); } }); row.addEventListener('dragend', function () { dragId = null; });
  });
 }
 root.querySelectorAll('[data-carousel-field]').forEach(function (input) { input.addEventListener('input', function () { var key = input.dataset.carouselField; candidate[key] = input.type === 'checkbox' ? Number(input.checked) : input.type === 'number' || input.type === 'range' ? Number(input.value) : input.value; if (key === 'mode') { render(); } changed(); }); });
 async function search() {
  var sequence = ++searchGeneration; var url = new URL(endpoint('state')); url.pathname = url.pathname.replace(/\/state$/, '/search'); url.searchParams.set('search', root.querySelector('[data-carousel-search]').value);
  try { var response = await fetch(url.href, {credentials:'same-origin', headers: {'X-WP-Nonce': config.nonce}}); var payload = await response.json(); if (!response.ok || !Array.isArray(payload.items)) { throw new Error('Search unavailable.'); } if (sequence !== searchGeneration) { return; } results.replaceChildren();
   payload.items.forEach(function (item) { button(item.title + ' (' + item.type + ')', function () { if (candidate.slides.length >= 48) { announce('A carousel can contain up to 48 items.'); return; } if (candidate.slides.some(function (slide) { return slide.post_id === item.id; })) { announce('This item is already selected.'); return; } metadata[item.id] = {title:item.title,available:true}; candidate.slides.push({post_id:item.id,image_id:0,headline:'',excerpt:'',kicker:'',cta:'',overlay:0,focal_x:50,focal_y:30,zoom:100,fit:'cover'}); render(); changed(); }, results); }); if (!payload.items.length) { text('p','No published items found.',results); }
  } catch (error) { if (sequence === searchGeneration) { announce(error.message); } }
 }
 root.querySelector('[data-carousel-search-button]').addEventListener('click', search);
 root.querySelector('[data-carousel-search]').addEventListener('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); search(); } });
 async function refreshRevisions() {
  var payload = await request('revisions'), revisionList = root.querySelector('[data-revision-list]'); if (!revisionList || !Array.isArray(payload.revisions)) { return; } revisionList.replaceChildren(); payload.revisions.forEach(function (revision) { var row = text('li', revision.timestamp + ' ', revisionList), restore = button('Restore', function () { perform('restore', revision.id); }, row); restore.dataset.revisionId = revision.id; });
 }
 async function perform(action, revision) {
  if (busy) { return; }
  if (action === 'discard') { candidate = clone(baseline); mediaGeneration++; previewFingerprint = ''; previewInstance = ''; frame.src = config.previewOrigin + config.previewRoute; render(); announce('Changes discarded.'); return; }
  if (action === 'restore' && !window.confirm('Restore this revision to the live site?')) { return; }
  freeze(true); mediaGeneration++; announce(action === 'save' ? 'Applying carousel…' : 'Working…');
  try {
   var payload = await request(action, action === 'restore' ? {revision_id: revision} : {state: clone(candidate)});
   if (action === 'preview') {
    var url = new URL(payload.url); if (url.origin !== config.previewOrigin || url.pathname !== config.previewRoute || !url.searchParams.has(config.previewQueryArg) || Array.from(url.searchParams.keys()).length !== 1 || !/^[0-9a-f-]{36}$/.test(url.searchParams.get(config.previewQueryArg))) { throw new Error('Invalid private preview URL.'); }
    previewInstance = config.pageUuid + ':' + (++generation); url.searchParams.set(config.previewInstanceArg, previewInstance); frame.src = url.href; previewFingerprint = JSON.stringify(candidate); announce('Private preview updated. Check desktop and mobile before Apply.');
   } else {
    if (!validState(payload.state) || typeof payload.timestamp !== 'string' || typeof payload[action === 'restore' ? 'safety_revision_id' : 'revision_id'] !== 'string') { throw new Error('Invalid saved state. Reload this workspace.'); }
    baseline = clone(payload.state); candidate = clone(baseline); previewFingerprint = ''; previewInstance = ''; render(); frame.src = config.previewOrigin + config.previewRoute; announce(action === 'save' ? 'Carousel applied.' : 'Revision restored.'); await refreshRevisions();
   }
  } catch (error) { announce(error.message); } finally { freeze(false); }
 }
 root.querySelectorAll('[data-action]').forEach(function (node) { var action = node.dataset.action; if (['save','preview','discard','restore'].indexOf(action) >= 0) { node.addEventListener('click', function () { perform(action, node.dataset.revisionId); }); } });
 root.querySelector('[data-action="save"]').textContent = 'Apply Carousel';
 root.querySelectorAll('[data-preview-width]').forEach(function (node) { node.addEventListener('click', function () { var width = config.widths[node.dataset.previewWidth]; if (!width) { return; } frame.style.width = width + 'px'; frame.setAttribute('width', String(width)); root.querySelectorAll('[data-preview-width]').forEach(function (button) { button.setAttribute('aria-pressed', String(button === node)); }); scale(); }); });
 function scale() { var viewport = root.querySelector('.lunara-site-studio-preview-viewport') || root.querySelector('.lunara-site-studio-preview'); if (viewport) { var width = Number(frame.getAttribute('width')) || 1440; frame.style.transformOrigin = 'top left'; var ratio = Math.min(1, viewport.clientWidth / width); frame.style.transform = 'scale(' + ratio + ')'; var flow = root.querySelector('.lunara-site-studio-preview-flow'); if (flow) { flow.style.height = (900 * ratio) + 'px'; } } }
 window.addEventListener('resize', scale);
 root.addEventListener('click', function (event) { var link = event.target.closest('[data-lunara-surface-card],[data-workspace-navigation]'); if (link && (busy || (dirty() && !window.confirm('Discard unsaved changes and leave this carousel?')))) { event.preventDefault(); event.stopPropagation(); } }, true);
 window.addEventListener('beforeunload', function (event) { if (dirty() || busy) { event.preventDefault(); event.returnValue = ''; } });
 window.addEventListener('message', function (event) { var data = event.data; if (event.origin !== config.previewOrigin || event.source !== frame.contentWindow || !previewInstance || !data || data.protocol !== config.protocol || data.version !== 1 || data.surface !== config.surface || data.instance !== previewInstance || data.type !== 'select-section' || config.markers.indexOf(data.section) < 0) { return; } root.querySelector('.lunara-site-studio-inspector').scrollIntoView({block:'nearest'}); });
 root.querySelectorAll('[data-lunara-site-studio-section-link]').forEach(function (node) { node.addEventListener('click', function () { root.querySelector('.lunara-site-studio-inspector').scrollIntoView({block:'nearest'}); }); });
 render(); freeze(false); root.setAttribute('data-lunara-site-studio-ready', 'true'); announce('Settings loaded. Preview changes privately, then Apply.'); scale();
}());
