/* Shared Reviews / Journal story selection. The workspace owns persistence. */
(function () {
 'use strict';
 var controls = window.LunaraEditorControls, mediaEditor = window.LunaraSiteStudioArchiveMediaEditor;
 function keys(value, names) { return !!value && typeof value === 'object' && !Array.isArray(value) && JSON.stringify(Object.keys(value).sort()) === JSON.stringify(names.slice().sort()); }
 function id(value, allowZero) { return Number.isSafeInteger(value) && value >= (allowZero ? 0 : 1) && value <= 9999999999; }
 function validState(state) {
  var surface = window.LunaraSiteStudioWorkspaceConfig && window.LunaraSiteStudioWorkspaceConfig.surface;
  return !!mediaEditor && mediaEditor.validateState(state) && !!state && [0,1].indexOf(state.selection_version) >= 0 && (surface === 'journal-archive' ? ['automatic','manual','shared'] : ['automatic','manual']).indexOf(state.lead_mode) >= 0 && id(state.lead_id,true) && ['query','curated'].indexOf(state.lane_mode) >= 0 && Array.isArray(state.curated_ids) && state.curated_ids.length <= 24 && state.curated_ids.every(function (value) { return id(value,false); }) && new Set(state.curated_ids).size === state.curated_ids.length;
 }
 function validDom(root) {
  return !!mediaEditor && mediaEditor.validateDom(root) && !!controls && typeof controls.orderedList === 'function' && ['editor','legacy','activate','controls','lead-mode','lead-manual','lead-search','lead-search-button','lead-results','lead-selected','lead-clear','lead-status','lane-mode','priority-manual','priority-search','priority-search-button','priority-results','priority-list','priority-status','status','retry'].every(function (name) { return root.querySelectorAll('[data-archive-'+name+']').length === 1; });
 }
 function create(context) {
  var root = context.root, node = controls.node, button = controls.button, media = mediaEditor.create(context);
  function find(name) { return root.querySelector('[data-archive-'+name+']'); }
  var container = find('editor'), leadMode = find('lead-mode'), laneMode = find('lane-mode'), listParent = find('priority-list');
  var metadata = {}, effectiveLead = 0, matches = [], resultTarget = '', pendingTarget = '', warnings = [];
  var generation = 0, sequence = 0, controller = null, busy = false, loading = false, loaded = false, failed = false;
  function enabled() { return !busy && !context.isBusy(); }
  function active() { return context.getState().selection_version === 1; }
  function manual(target) { return active() && (target === 'lead' ? context.getState().lead_mode === 'manual' : context.getState().lane_mode === 'curated'); }
  function invalidate() { generation += 1; sequence += 1; if (controller) { controller.abort(); controller = null; } list.cancelDrag(); }
  function clearSelectionErrors() {
   ['selection_version','lead_mode','lead_id','lane_mode','curated_ids'].forEach(function (key) {
    var field = container.querySelector('[data-error-key="'+key+'"]'); if (!field) { return; }
    field.removeAttribute('aria-invalid'); (field.getAttribute('aria-describedby') || '').split(/\s+/).forEach(function (name) { var error = document.getElementById(name); if (error && error.classList.contains('lunara-site-studio-error')) { error.hidden = true; error.textContent = ''; } });
   });
  }
  function change(patch, focus) {
   if (!enabled() || !active()) { return; }
   Object.keys(patch).forEach(function (key) { context.getState()[key] = patch[key]; });
   clearSelectionErrors(); invalidate(); matches = []; resultTarget = pendingTarget = ''; loaded = false; loading = false; failed = false; warnings = [];
   context.changed(); draw(focus); fetchItems();
  }
  function move(from, to) {
   if (!manual('priority')) { return; }
   var next = context.getState().curated_ids.slice(); if (from < 0 || from >= next.length || to < 0 || to >= next.length || from === to) { return; }
   var item = next.splice(from,1)[0]; next.splice(to,0,item); change({curated_ids:next},{key:item,direction:to < from ? 'earlier' : 'later'});
  }
  function summary(parent, item, fallback) {
   var header = node('div','',parent,'lunara-archive-story-heading');
   controls.thumbnail(header,item && item.available ? item.image_url : '');
   var copy = node('div','',header,'lunara-archive-story-copy');
   node('strong',item && item.available ? item.title : fallback,copy);
   if (item && item.available && item.published_date) { node('p',item.published_date,copy,'description'); }
  }
  function row(value, index) {
   var entry = metadata[value], owner = context.getState();
   var result = node('li','',null,'lunara-site-studio-order-row lunara-archive-story'); result.dataset.archiveStoryId = String(value);
   summary(result,entry,'Selection #'+value);
   if (!loaded || loading) { node('p',failed ? 'Story details could not load. Use Retry details.' : 'Loading story details…',result,'description'); }
   else if (!entry || !entry.available) { node('p','Unavailable or unpublished. Retained here and skipped in the archive.',result,'lunara-archive-warning'); }
   var actions = node('div','',result,'lunara-editor-actions');
   [['earlier',-1],['later',1]].forEach(function (direction) {
    var control = button(direction[0] === 'earlier' ? '↑' : '↓',actions,function () { if (owner === context.getState() && result.isConnected) { move(index,index+direction[1]); } });
    control.dataset.editorMove = direction[0]; control.setAttribute('aria-label','Move '+direction[0]+': '+(entry && entry.available ? entry.title : 'Selection #'+value)); control.title = 'Move '+direction[0];
    control.disabled = !enabled() || !manual('priority') || index+direction[1] < 0 || index+direction[1] >= context.getState().curated_ids.length;
   });
   var remove = button('Remove',actions,function () {
    if (!enabled() || !manual('priority') || owner !== context.getState() || !result.isConnected) { return; }
    change({curated_ids:context.getState().curated_ids.filter(function (selected) { return selected !== value; })});
    var next = listParent.children[Math.min(index,listParent.children.length-1)], target = next && next.querySelector('button:not(:disabled)');
    (target || find('priority-search')).focus();
   }); remove.dataset.archiveRemove = ''; remove.disabled = !enabled() || !manual('priority'); return result;
  }
  var list = controls.orderedList({parent:listParent,items:function () { return context.getState().curated_ids; },key:function (value) { return value; },row:row,move:move,enabled:function () { return enabled() && manual('priority'); }});
  function drawLead() {
   var state = context.getState(), selected = find('lead-selected'), current = metadata[effectiveLead]; selected.replaceChildren();
   if (!loaded || loading) { node('p',failed ? 'Story details could not load. Your choices are retained.' : 'Loading featured story…',selected); }
   else if (current && current.available) { summary(selected,current,'Featured story'); }
   else { node('p',state.lead_mode === 'manual' ? 'No available Manual story selected.' : 'No eligible published story is available.',selected); }
   var status = '';
   if (active() && state.lead_mode === 'manual') {
    if (!state.lead_id || loaded && (!metadata[state.lead_id] || !metadata[state.lead_id].available)) { status = 'Preview and Apply need a published Manual story. Select one below or choose Automatic.'; }
   } else if (state.lead_mode === 'shared') { status = 'Legacy shared lead uses the older homepage Journal pin. It does not follow the homepage Journal carousel.'; }
   else if (active()) { status = 'Automatic uses the newest published story, independently of the priority list.'; }
   if (warnings.indexOf('retained_lead_unavailable') >= 0) { status += ' Your remembered Manual story is unavailable; Automatic still works.'; }
   find('lead-status').textContent = status;
   find('lead-clear').disabled = !enabled() || !manual('lead') || !state.lead_id;
  }
  function drawResults() {
   ['lead','priority'].forEach(function (target) {
    var parent = find(target+'-results'); parent.replaceChildren();
    if (resultTarget !== target) { return; }
    if (loading) { node('p','Searching published stories…',parent); return; }
    if (failed) { node('p','Search could not load. Use Search again to retry.',parent); return; }
    var visible = matches.filter(function (value) { return target !== 'priority' || context.getState().curated_ids.indexOf(value) < 0; });
    if (!visible.length) { node('p',matches.length ? 'All matching stories are already selected.' : 'No published stories found.',parent); }
    visible.forEach(function (value) {
     var item = metadata[value]; if (!item || !item.available) { return; }
     var owner = context.getState(), ticket = generation, result = node('div','',parent,'lunara-archive-search-result'); result.dataset.archiveResultId = String(value); result.dataset.archiveResultTarget = target; summary(result,item,'Story');
     var choose = button((target === 'lead' ? 'Select ' : 'Add ')+item.title,result,function () {
      if (!enabled() || !manual(target) || owner !== context.getState() || ticket !== generation || !result.isConnected) { return; }
      if (target === 'priority' && (context.getState().curated_ids.length >= 24 || context.getState().curated_ids.indexOf(value) >= 0)) { return; }
      change(target === 'lead' ? {lead_id:value} : {curated_ids:context.getState().curated_ids.concat([value])});
      if (target === 'lead') { find('lead-selected').focus(); } else { find('priority-search').focus(); }
     }); choose.disabled = !enabled() || !manual(target) || target === 'priority' && context.getState().curated_ids.length >= 24;
    });
   });
  }
  function focusedAction() {
   var selected = document.activeElement, selectedRow = selected && selected.closest('[data-archive-story-id]'), result = selected && selected.closest('[data-archive-result-id]');
   if (selectedRow && selected.dataset.editorMove) { return {key:selectedRow.dataset.archiveStoryId,direction:selected.dataset.editorMove}; }
   if (selectedRow && selected.hasAttribute('data-archive-remove')) { return {key:selectedRow.dataset.archiveStoryId,remove:true}; }
   if (result && selected.tagName === 'BUTTON') { return {result:result.dataset.archiveResultId,target:result.dataset.archiveResultTarget}; }
   return null;
  }
  function restoreAction(focus) {
   if (!focus || focus.direction) { return; }
   var parent = focus.remove ? listParent : find(focus.target+'-results');
   var row = Array.from(parent.children).find(function (entry) { return focus.remove ? entry.dataset.archiveStoryId === String(focus.key) : entry.dataset.archiveResultId === String(focus.result); });
   var target = row && row.querySelector(focus.remove ? '[data-archive-remove]:not(:disabled)' : 'button:not(:disabled)');
   if (target) { target.focus(); } else { find((focus.remove ? 'priority' : focus.target)+'-search').focus(); }
  }
  function draw(focus) {
   if (!focus) { focus = focusedAction(); }
   var state = context.getState(); leadMode.value = state.lead_mode; laneMode.value = state.lane_mode;
   find('legacy').hidden = active(); find('controls').disabled = !enabled() || !active();
   find('activate').disabled = !enabled(); find('lead-manual').hidden = !manual('lead'); find('priority-manual').hidden = !manual('priority');
   list.render(focus && focus.direction ? focus : null); drawLead(); drawResults(); restoreAction(focus);
   var count = state.curated_ids.length, skipped = loaded ? state.curated_ids.filter(function (value) { return !metadata[value] || !metadata[value].available; }).length : 0;
   find('priority-status').textContent = state.lane_mode === 'query' ? count+' Manual priority selection(s) retained while the archive uses its normal ordering.' : !count ? 'No priorities selected. The complete archive remains available after Apply.' : count+' priority selection(s). '+(skipped ? skipped+' unavailable selection(s) will be skipped. ' : '')+'The rest of the archive follows these stories.';
   find('status').textContent = failed ? 'Story details could not refresh. Your choices are retained. Retry details to try again.' : loading ? 'Loading story details…' : '';
   find('retry').hidden = !failed; find('retry').disabled = !enabled() || loading;
  }
  function itemsUrl(target) {
   var state = context.getState(), url = new URL(context.config.endpoints.state,window.location.href);
   if (url.origin !== window.location.origin || url.username || url.password) { throw new Error('Invalid destination'); }
   if (url.searchParams.has('rest_route')) { url.searchParams.set('rest_route',url.searchParams.get('rest_route').replace(/\/state$/,'/items')); } else { url.pathname = url.pathname.replace(/\/state$/,'/items'); }
   var requested = state.curated_ids.concat(state.lead_id ? [state.lead_id] : []);
   url.searchParams.set('ids',Array.from(new Set(requested)).join(','));
   ['selection_version','lead_mode','lead_id','lane_mode'].forEach(function (key) { url.searchParams.set(key,String(state[key])); });
   url.searchParams.set('curated_ids',state.curated_ids.join(',')); url.searchParams.set('q',target ? find(target+'-search').value : ''); return url;
  }
  function validPayload(payload, url) {
   if (!keys(payload,['items','results','lead_id','priority_ids','selection_version','warnings']) || payload.selection_version !== Number(url.searchParams.get('selection_version')) || !Array.isArray(payload.items) || payload.items.length > 46 || !Array.isArray(payload.results) || payload.results.length > 20 || !id(payload.lead_id,true) || !Array.isArray(payload.priority_ids) || payload.priority_ids.length > 25 || !Array.isArray(payload.warnings)) { return false; }
   var available = {}, seen = {}, codes = ['manual_lead_unavailable','retained_lead_unavailable','curated_selection_unavailable','curated_selection_empty','legacy_shared_lead'];
   if (!payload.items.every(function (entry) {
    if (!keys(entry,['id','title','available','image_url','published_date']) || !id(entry.id,false) || seen[entry.id] || typeof entry.available !== 'boolean' || !['title','image_url','published_date'].every(function (key) { return typeof entry[key] === 'string'; }) || entry.image_url && !controls.imageUrl(entry.image_url) || !entry.available && (entry.image_url || entry.published_date)) { return false; }
    seen[entry.id] = true; available[entry.id] = entry.available; return true;
   })) { return false; }
   function validList(values) { return new Set(values).size === values.length && values.every(function (value) { return id(value,false) && available[value]; }); }
   return validList(payload.results) && validList(payload.priority_ids) && (!payload.lead_id || available[payload.lead_id]) && (url.searchParams.get('ids') || '').split(',').filter(Boolean).every(function (value) { return seen[value]; }) && payload.warnings.every(function (value) { return codes.indexOf(value) >= 0; });
  }
  function fetchItems(target) {
   if (!enabled()) { return; } if (target && !manual(target)) { return; }
   var ticket = generation, request = ++sequence; if (controller) { controller.abort(); } controller = window.AbortController ? new AbortController() : null;
   loading = true; failed = false; pendingTarget = target || ''; resultTarget = target || ''; draw();
   var url; try { url = itemsUrl(target); } catch (error) { loading = false; failed = true; draw(); return; }
   fetch(url.href,{credentials:'same-origin',headers:{'X-WP-Nonce':context.config.nonce},signal:controller ? controller.signal : undefined}).then(function (response) { if (!response.ok) { throw new Error('Request failed'); } return response.json(); }).then(function (payload) {
    if (ticket !== generation || request !== sequence || !enabled()) { return; }
    if (!validPayload(payload,url)) { throw new Error('Invalid story details'); }
    metadata = {}; payload.items.forEach(function (entry) { metadata[entry.id] = entry; }); effectiveLead = payload.lead_id; matches = payload.results; warnings = payload.warnings; loading = false; loaded = true; failed = false; pendingTarget = '';
    draw();
   }).catch(function () {
    if (ticket !== generation || request !== sequence || !enabled()) { return; }
    metadata = {}; matches = []; effectiveLead = 0; warnings = []; loading = false; loaded = false; failed = true; draw();
   });
  }
  find('activate').addEventListener('click',function () { if (!enabled() || active()) { return; } context.getState().selection_version = 1; change({}); leadMode.focus(); });
  leadMode.addEventListener('change',function () { if (enabled() && active()) { change({lead_mode:leadMode.value}); } });
  laneMode.addEventListener('change',function () { if (enabled() && active()) { change({lane_mode:laneMode.value}); } });
  find('lead-clear').addEventListener('click',function () { if (manual('lead')) { change({lead_id:0}); find('lead-search').focus(); } });
  ['lead','priority'].forEach(function (target) { find(target+'-search-button').addEventListener('click',function () { fetchItems(target); }); find(target+'-search').addEventListener('keydown',function (event) { if (event.key === 'Enter') { event.preventDefault(); fetchItems(target); } }); });
  find('retry').addEventListener('click',function () { fetchItems(resultTarget); });
  function render() { invalidate(); metadata = {}; matches = []; effectiveLead = 0; warnings = []; loaded = false; loading = false; failed = false; resultTarget = pendingTarget = ''; draw(); fetchItems(); }
  function setBusy(value) { busy = value; find('controls').disabled = value || !active(); find('activate').disabled = value; find('retry').disabled = value || loading; if (!value) { loading = false; fetchItems(pendingTarget); } }
  return {render:function () { render(); media.render(); },setBusy:function (value) { setBusy(value); media.setBusy(value); },invalidate:function () { invalidate(); media.invalidate(); }};
 }
 window.LunaraSiteStudioArchiveSelectionEditor = {validateState:validState,validateDom:validDom,create:create};
}());
