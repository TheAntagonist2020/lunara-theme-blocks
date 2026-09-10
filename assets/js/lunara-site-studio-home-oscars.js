(function () {
 'use strict';
 var controls = window.LunaraEditorControls;
 function ids(state) { return state.selection.ids ? state.selection.ids.split(',').map(Number) : []; }
 function validState(state) {
  if (!state || !state.copy || !state.selection || !state.presentation || ['legacy','automatic','manual'].indexOf(state.selection.mode) < 0 || typeof state.selection.ids !== 'string') { return false; }
  var selected = ids(state);
  return Number.isSafeInteger(state.presentation.autoplay_interval) && state.presentation.autoplay_interval >= 0 && state.presentation.autoplay_interval <= 12000 && selected.length <= 48 && selected.every(function (id) { return Number.isSafeInteger(id) && id > 0 && id <= 9999999999; }) && new Set(selected).size === selected.length && selected.join(',') === state.selection.ids;
 }
 function validDom(root) { return !!controls && ['[data-home-oscars-editor]','[data-home-oscars-manual]','[data-oscars-lineup]','[data-oscars-search]','[data-oscars-search-button]','[data-oscars-results]','[data-oscars-status]'].every(function (selector) { return root.querySelectorAll(selector).length === 1; }); }
 function create(context) {
  var root = context.root, parent = root.querySelector('[data-oscars-lineup]'), search = root.querySelector('[data-oscars-search]'), results = root.querySelector('[data-oscars-results]'), status = root.querySelector('[data-oscars-status]');
  var metadata = {}, matches = [], generation = 0, controller = null, busy = false, loaded = false;
  function enabled() { return !busy && !context.isBusy(); }
  function invalidate() { generation += 1; if (controller) { controller.abort(); controller = null; } list.cancelDrag(); }
  function update(next, focus) { if (!enabled()) { return; } context.getState().selection.ids = next.join(','); context.changed(); draw(focus); }
  function move(from, to) { var next = ids(context.getState()); if (to < 0 || to >= next.length) { return; } var item = next.splice(from,1)[0]; next.splice(to,0,item); update(next, {key:item,direction:to < from ? 'earlier' : 'later'}); }
  var list = controls.orderedList({parent:parent,enabled:enabled,items:function () { return ids(context.getState()); },key:function (id) { return id; },move:move,row:function (id,index) {
   var row = document.createElement('li'), label = document.createElement('span'), item = metadata[id]; row.className='lunara-site-studio-order-row';
   label.textContent = item ? item.title + (item.available ? '' : ' — unavailable; skipped') : 'Selection #' + id + ' — checking availability'; row.appendChild(label);
   [['earlier',-1],['later',1]].forEach(function (entry) { var button = document.createElement('button'); button.type='button'; button.textContent = entry[0] === 'earlier' ? '↑' : '↓'; button.setAttribute('aria-label', 'Move ' + entry[0]); button.title = 'Move ' + entry[0]; button.dataset.editorMove = entry[0]; button.disabled = !enabled() || index+entry[1]<0 || index+entry[1]>=ids(context.getState()).length; button.addEventListener('click',function () { move(index,index+entry[1]); }); row.appendChild(button); });
   var remove = document.createElement('button'); remove.type='button'; remove.textContent='Remove'; remove.disabled=!enabled(); remove.addEventListener('click',function () { update(ids(context.getState()).filter(function (entry) { return entry !== id; })); var first = parent.querySelector('button:not(:disabled)'); if(first){first.focus();}else{search.focus();} }); row.appendChild(remove); return row;
  }});
  function draw(focus) {
   var state = context.getState(), selected=ids(state);
   root.querySelector('[data-home-oscars-manual]').hidden=state.selection.mode!=='manual'; list.render(focus); results.replaceChildren();
   matches.forEach(function (id) { var item=metadata[id]; if(!item || !item.available || selected.indexOf(id)>=0){return;} var button=document.createElement('button'); button.type='button'; button.textContent='Add: '+item.title; button.disabled=!enabled()||selected.length>=48; button.addEventListener('click',function () { update(ids(context.getState()).concat([id])); }); results.appendChild(button); });
   if(state.selection.mode==='manual'){var unavailable=loaded?selected.filter(function(id){return !metadata[id]||!metadata[id].available;}).length:0;status.textContent=!selected.length?'Manual lineup is empty. This section will be hidden after Apply.':unavailable?unavailable+' unavailable selection(s) will be skipped.':selected.length+' selected. Drag to reorder or use Move earlier / Move later.';}else{status.textContent=state.selection.mode==='legacy'?'Existing public selection rules are preserved.':'Newest published items appear first. Your manual list is retained.';}
  }
  function itemsUrl() {
   var url=new URL(context.config.endpoints.save,window.location.href);
   if(url.searchParams.has('rest_route')){url.searchParams.set('rest_route',url.searchParams.get('rest_route').replace(/\/save$/,'/items'));}else{url.pathname=url.pathname.replace(/\/save$/,'/items');}
   url.searchParams.set('ids',context.getState().selection.ids);url.searchParams.set('search',search.value);
   if(context.getState().selection.ceremony_year){url.searchParams.set('year',String(context.getState().selection.ceremony_year));}return url.href;
  }
  function fetchItems() {
   if(!enabled()){return;}invalidate(); var ticket=generation; controller=window.AbortController?new AbortController():null;
   status.textContent='Loading lineup details…';
   window.fetch(itemsUrl(),{credentials:'same-origin',headers:{'X-WP-Nonce':context.config.nonce},signal:controller?controller.signal:undefined}).then(function(response){if(!response.ok){throw new Error('request');}return response.json();}).then(function(payload){
    if(ticket!==generation||!enabled()){return;}if(!payload||!Array.isArray(payload.items)||!Array.isArray(payload.results)||payload.items.length>68||payload.results.length>20){throw new Error('shape');}
    var next={};payload.items.forEach(function(item){if(!item||!Number.isSafeInteger(item.id)||item.id<1||typeof item.title!=='string'||typeof item.available!=='boolean'){throw new Error('item');}next[item.id]=item;});
    if(!payload.results.every(function(id){return Number.isSafeInteger(id)&&next[id]&&next[id].available;})){throw new Error('results');}
    metadata=next;matches=payload.results;loaded=true;draw();
   }).catch(function(){if(ticket===generation&&enabled()){status.textContent='Could not load lineup details. Your selections are retained; use Search to retry.';}});
  }
  var interval=root.querySelector('[data-oscars-interval]');
  interval.addEventListener('change',function(){var value=Math.round(Number(interval.value)*1000);if(enabled()&&Number.isSafeInteger(value)&&value>=0&&value<=12000){context.getState().presentation.autoplay_interval=value;context.changed();}else{interval.value=String(context.getState().presentation.autoplay_interval/1000);}});
  function render() { invalidate();metadata={};matches=[];loaded=false;interval.value=String(context.getState().presentation.autoplay_interval/1000);draw();fetchItems(); }
  root.querySelector('[data-oscars-search-button]').addEventListener('click',fetchItems);
  search.addEventListener('keydown',function(event){if(event.key==='Enter'){event.preventDefault();fetchItems();}});
  return {render:render,invalidate:invalidate,setBusy:function(value){busy=value;if(value){invalidate();}draw();search.disabled=value;interval.disabled=value;root.querySelector('[data-oscars-search-button]').disabled=value;if(!value){fetchItems();}}};
 }
 window.LunaraSiteStudioHomeOscarsEditor={validateState:validState,validateDom:validDom,create:create};
}());
