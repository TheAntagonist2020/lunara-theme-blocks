/* Method fields and metadata. The common host owns all persistence and preview. */
(function () {
 'use strict';
 var controls = window.LunaraEditorControls;
 function keys(value, expected) { return value && typeof value === 'object' && !Array.isArray(value) && Object.getPrototypeOf(value) === Object.prototype && JSON.stringify(Object.keys(value).sort()) === JSON.stringify(expected.slice().sort()); }
 function validState(state) {
  if (!keys(state,['kicker','title','copy','review_id','backdrop_id','review_mode','backdrop']) || !['kicker','title','copy'].every(function (key) { return typeof state[key] === 'string'; }) || !['review_id','backdrop_id'].every(function (key) { return Number.isSafeInteger(state[key]) && state[key] >= 0; }) || ['automatic','manual'].indexOf(state.review_mode) < 0 || !keys(state.backdrop,['hidden','focal_x','focal_y','fit','zoom'])) { return false; }
  var art = state.backdrop;
  return typeof art.hidden === 'boolean' && ['cover','full'].indexOf(art.fit) >= 0 && ['focal_x','focal_y','zoom'].every(function (key) { return Number.isInteger(art[key]) && art[key] >= (key === 'zoom' ? 100 : 0) && art[key] <= (key === 'zoom' ? 112 : 100); });
 }
 function validDom(root) { return !!controls && typeof controls.image === 'function' && ['editor','manual','mode','search','search-button','results','selected','clear','retry','image'].every(function (key) { return root.querySelectorAll('[data-method-'+key+']').length === 1; }); }
 function create(context) {
  var root = context.root, config = context.config, stateOwner, version = 0, metadataSequence = 0, searchSequence = 0, metadataKey = '', busy = false, frozen = [];
  var item = null, imageUrl = '', searchItems = [], status = 'Loading Review details…';
  function find(key) { return root.querySelector('[data-method-'+key+']'); }
  var container = find('editor'), selected = find('selected'), results = find('results'), mode = find('mode'), retry = find('retry');
  function enabled() { return !busy && !context.isBusy(); }
  function readUrl(action) {
   var url = new URL(config.endpoints.state), route = url.searchParams.get('rest_route');
   if (url.origin !== window.location.origin || url.username || url.password) { throw new Error('Invalid editor destination.'); }
   if (route) { url.searchParams.set('rest_route',route.replace(/\/state$/, '/'+action)); } else { url.pathname = url.pathname.replace(/\/state$/, '/'+action); }
   return url;
  }
  function validItem(value) { return value === null || (keys(value,['id','available','title','date_label','image_url','image_source']) && Number.isSafeInteger(value.id) && value.id > 0 && typeof value.available === 'boolean' && ['title','date_label','image_url','image_source'].every(function (key) { return typeof value[key] === 'string'; })); }
  function summary(parent, entry) {
   if (entry.available) { controls.thumbnail(parent,entry.image_url); controls.node('strong',entry.title,parent); controls.node('p',entry.date_label,parent); }
   else { controls.node('strong','Unavailable Review',parent); }
  }
  function renderDetails() {
   selected.replaceChildren();
   if (status) { controls.node('p',status,selected); }
   else if (item) { summary(selected,item); if (!item.available) { controls.node('p','This Manual Review is unavailable. The Method will be hidden after Apply. Choose another Review or Automatic.',selected); } }
   else { controls.node('p',context.getState().review_mode === 'manual' ? 'No Manual Review selected. The Method will be hidden after Apply.' : 'No eligible published Review with pairings is available.',selected); }
   imageEditor.render();
  }
  var imageEditor = controls.image({parent:find('image'),allowHidden:true,bleed:4,aspect:'1440 / 600',getValue:function () { return Object.assign({image_id:context.getState().backdrop_id},context.getState().backdrop); },source:function () { return {url:item && item.available ? item.image_url : '',label:item && item.available ? item.image_source : status || 'No available Review artwork'}; },image:function () { return imageUrl; },enabled:enabled,ticket:function () { return version; },announce:context.announce,selectedImage:function (id,url) { imageUrl=url; },change:function (patch) {
   if (!enabled()) { return; }
   var state = context.getState(); Object.keys(patch).forEach(function (key) { if (key === 'image_id') { state.backdrop_id=patch[key]; } else { state.backdrop[key]=patch[key]; } });
   context.changed(); refreshMetadata();
  }});
  async function refreshMetadata() {
   if (!enabled()) { return; }
   var state = context.getState(), key = JSON.stringify([state.review_mode,state.review_mode === 'manual' ? state.review_id : 0,state.backdrop_id]);
   if (key === metadataKey) { return; } metadataKey=key;
   var sequence=++metadataSequence, ticket=version; status='Loading Review details…'; retry.hidden=true;
   try {
    var url=readUrl('metadata'); url.searchParams.set('mode',state.review_mode); url.searchParams.set('review_id',state.review_mode === 'manual' ? state.review_id : 0); url.searchParams.set('image_id',state.backdrop_id);
    var response=await fetch(url.href,{credentials:'same-origin',headers:{'X-WP-Nonce':config.nonce}}), payload=await response.json();
    if (ticket !== version || sequence !== metadataSequence || !enabled()) { return; }
    if (!response.ok || !keys(payload,['item','image_url']) || !validItem(payload.item) || typeof payload.image_url !== 'string') { throw new Error('Unavailable details'); }
    item=payload.item; imageUrl=controls.imageUrl(payload.image_url); status=''; renderDetails();
   } catch (error) { if (ticket === version && sequence === metadataSequence && enabled()) { item=null; imageUrl=''; status='Review details could not load. Your choices are still here.'; retry.hidden=false; renderDetails(); } }
  }
  function resetReads() { invalidate(); item=null; imageUrl=''; searchItems=[]; results.replaceChildren(); status='Loading Review details…'; }
  function renderResults() {
   results.replaceChildren();
   searchItems.forEach(function (entry) { var row=controls.node('div','',results); summary(row,entry); var choose=controls.button('Select '+entry.title,row,function () { if (!enabled() || context.getState().review_mode !== 'manual') { return; } context.getState().review_id=entry.id; resetReads(); render(); context.changed(); }); choose.disabled=!enabled(); });
  }
  async function search() {
   if (!enabled() || context.getState().review_mode !== 'manual') { return; }
   var sequence=++searchSequence,ticket=version; results.replaceChildren(); controls.node('p','Searching published Reviews…',results);
   try {
    var url=readUrl('search'); url.searchParams.set('search',find('search').value);
    var response=await fetch(url.href,{credentials:'same-origin',headers:{'X-WP-Nonce':config.nonce}}),payload=await response.json();
    if (ticket !== version || sequence !== searchSequence || !enabled()) { return; }
    if (!response.ok || !payload || !Array.isArray(payload.items) || payload.items.length > 20 || !payload.items.every(function (entry) { return entry && validItem(entry) && entry.available; })) { throw new Error('Search unavailable'); }
    searchItems=payload.items; renderResults(); if (!searchItems.length) { controls.node('p','No published Reviews found.',results); }
   } catch (error) { if (ticket === version && sequence === searchSequence && enabled()) { results.replaceChildren(); controls.node('p','Search could not load. Try again.',results); } }
  }
  mode.addEventListener('change',function () { if (!enabled()) { return; } context.getState().review_mode=mode.value; resetReads(); render(); context.changed(); });
  find('clear').addEventListener('click',function () { if (!enabled()) { return; } context.getState().review_id=0; resetReads(); render(); context.changed(); });
  find('search-button').addEventListener('click',search);
  find('search').addEventListener('keydown',function (event) { if (event.key === 'Enter') { event.preventDefault(); search(); } });
  retry.addEventListener('click',function () { metadataKey=''; refreshMetadata(); });
  var preview=root.querySelector('iframe');
  function syncFraming() { try { var section=preview.contentDocument.querySelector('.lunara-pairing-desk-section'); if (section && preview.contentWindow.innerWidth > 820) { var rect=section.getBoundingClientRect(); imageEditor.setAspect(rect.width,rect.height); } } catch (error) { /* Keep last desktop dimensions until same-origin preview loads. */ } }
  if (preview) { preview.addEventListener('load',syncFraming); if (window.MutationObserver) { new MutationObserver(syncFraming).observe(preview,{attributes:true,attributeFilter:['width','height']}); } }
  function invalidate() { version++; metadataSequence++; searchSequence++; metadataKey=''; }
  function setBusy(value) {
   busy=value;
   if (value) { frozen=[]; container.querySelectorAll('input,select,button').forEach(function (control) { frozen.push([control,control.disabled]); control.disabled=true; }); }
   else { frozen.forEach(function (entry) { entry[0].disabled=entry[1]; }); frozen=[]; imageEditor.render(); refreshMetadata(); }
  }
  function render() {
   if (stateOwner !== context.getState()) { stateOwner=context.getState(); resetReads(); }
   mode.value=context.getState().review_mode; find('manual').hidden=mode.value !== 'manual';
   renderDetails(); syncFraming(); refreshMetadata();
  }
  return {render:render,setBusy:setBusy,invalidate:invalidate};
 }
 window.LunaraSiteStudioMethodEditor={validateState:validState,validateDom:validDom,create:create};
}());
