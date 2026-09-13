/* Shared archive artwork controls. Candidate state and transactions belong to Site Studio. */
(function () {
 'use strict';
 var controls = window.LunaraEditorControls, groups = ['gallery','retention'];
 function object(value) { return !!value && typeof value === 'object' && !Array.isArray(value); }
 function integer(value, min, max) { return Number.isSafeInteger(value) && value >= min && value <= max; }
 function validState(state) {
  var journal = window.LunaraSiteStudioWorkspaceConfig.surface === 'journal-archive';
  if (!object(state) || !object(state.gallery) || !object(state.labels) || !['kicker','title','copy'].every(function (key) { return typeof state.gallery[key] === 'string'; }) || !['retention_kicker','retention_title'].concat(journal ? [] : ['retention_copy']).every(function (key) { return typeof state.labels[key] === 'string'; }) || !Array.isArray(state.gallery.items) || state.gallery.items.length > 12 || !Array.isArray(state.retention) || state.retention.length !== 3) { return false; }
  return groups.every(function (group) {
   var items = group === 'gallery' ? state.gallery.items : state.retention;
   return items.every(function (item) {
    var strings = group === 'gallery' ? ['alt','caption','link_url','credit','source','source_url'] : ['label','destination','url','image_alt','image_credit','image_source','image_source_url'].concat(journal ? ['title','copy'] : []);
    return object(item) && strings.every(function (key) { return typeof item[key] === 'string'; }) && integer(item.order,1,Math.max(1,items.length)) && integer(item[group === 'gallery' ? 'attachment_id' : 'image_id'],0,Number.MAX_SAFE_INTEGER) && integer(item.focal_x,0,100) && integer(item.focal_y,0,100) && (group === 'gallery' || typeof item.visible === 'boolean' && (journal ? ['latest','trailer','reviews','journal','custom'] : ['latest','journal','oscars','reviews','custom']).indexOf(item.destination) >= 0);
   }) && new Set(items.map(function (item) { return item.order; })).size === items.length;
  });
 }
 function validDom(root) {
  return !!controls && typeof controls.image === 'function' && typeof controls.orderedList === 'function' && root.querySelectorAll('[data-archive-gallery-add]').length === 1 && groups.every(function (group) { return ['group','controls','heading','list','status','retry'].every(function (part) { return root.querySelectorAll('[data-archive-media-'+part+'="'+group+'"]').length === 1; }); });
 }
 function create(context) {
  var root = context.root, node = controls.node, button = controls.button, journal = context.config.surface === 'journal-archive';
  var generation = 0, sequence = 0, busy = false, controller = null, metadata = {}, loading = false, failed = false;
  var lists = {}, widgets = [], fields = [], identities = new WeakMap(), nextKey = 0, opened = new Set();
  function find(part,group) { return root.querySelector('[data-archive-media-'+part+'="'+group+'"]'); }
  function items(group) { var state = context.getState(); return group === 'gallery' ? state.gallery.items : state.retention; }
  function sorted(group) { return items(group).slice().sort(function (left,right) { return left.order-right.order; }); }
  function key(item) { if (!identities.has(item)) { identities.set(item,String(++nextKey)); } return identities.get(item); }
  function enabled() { return !busy && !context.isBusy(); }
  function imageId(group,item) { return item[group === 'gallery' ? 'attachment_id' : 'image_id']; }
  function invalidate() { generation += 1; sequence += 1; if (controller) { controller.abort(); controller = null; } loading = false; groups.forEach(function (group) { if (lists[group]) { lists[group].cancelDrag(); } }); }
  function owns(group,item,row,owner) { return enabled() && owner === context.getState() && row.isConnected && items(group).indexOf(item) >= 0; }
  function clearErrors(group) {
   find('group',group).querySelectorAll('[aria-invalid],.lunara-site-studio-error').forEach(function (field) { field.removeAttribute('aria-invalid'); if (field.classList.contains('lunara-site-studio-error')) { field.hidden = true; field.textContent = ''; } }); find('group',group).removeAttribute('aria-invalid');
  }
  function changed(group) { clearErrors(group); context.changed(); }
  function field(parent,title,name,objectValue,type,limit,change,choices,errorKey) {
   var wrap = node('label','',parent,'lunara-editor-field'); node('span',title,wrap);
   var input = node(type === 'textarea' ? 'textarea' : type === 'select' ? 'select' : 'input','',wrap); input.dataset.archiveMediaField = name;
   if (type !== 'textarea' && type !== 'select') { input.type = type || 'text'; }
   if (type === 'textarea') { input.rows = 3; }
   if (limit) { input.maxLength = limit; }
   if (choices) { choices.forEach(function (choice) { var option = node('option',choice[1],input); option.value = choice[0]; }); }
   if (type === 'checkbox') { input.checked = objectValue[name]; } else { input.value = objectValue[name]; }
   if (errorKey) { var errorId = 'lunara-archive-media-'+errorKey.replace(/\./g,'-')+'-error'; input.dataset.errorKey = errorKey; input.setAttribute('aria-describedby',errorId); var error = node('span','',parent,'lunara-site-studio-error'); error.id = errorId; error.hidden = true; }
   input.addEventListener(type === 'checkbox' || type === 'select' ? 'change' : 'input',function () { if (enabled() && input.isConnected) { change(type === 'checkbox' ? input.checked : input.value); } }); fields.push(input); return input;
  }
  function heading(group) {
   var parent = find('heading',group), owner = context.getState(), target = group === 'gallery' ? owner.gallery : owner.labels; parent.replaceChildren();
   var entries = group === 'gallery' ? [['kicker','Kicker',80],['title','Heading',140],['copy','Introduction',500]] : [['retention_kicker','Kicker',120],['retention_title','Heading',120]].concat(journal ? [] : [['retention_copy','Introduction',120]]);
   entries.forEach(function (entry) { field(parent,entry[1],entry[0],target,entry[0].indexOf('copy') >= 0 ? 'textarea' : 'text',entry[2],function (value) { if (owner !== context.getState()) { return; } target[entry[0]] = value; changed(group); },null,group === 'retention' ? 'labels.'+entry[0] : ''); });
  }
  function move(group,from,to) {
   if (!enabled()) { return; } var ordered = sorted(group); if (from < 0 || to < 0 || from >= ordered.length || to >= ordered.length || from === to) { return; }
   var item = ordered.splice(from,1)[0]; ordered.splice(to,0,item); ordered.forEach(function (entry,index) { entry.order = index+1; });
   invalidate(); changed(group); draw({group:group,key:key(item),direction:to < from ? 'earlier' : 'later'}); hydrate();
  }
  function row(group,item,index) {
   var owner = context.getState(), row = node('li','',null,'lunara-site-studio-order-row lunara-archive-media-row'); row.dataset.archiveMediaRow = group; row.dataset.archiveImageId = imageId(group,item);
   var title = group === 'gallery' ? 'Gallery image '+(index+1) : 'Card '+(index+1), actions = node('div','',row,'lunara-editor-actions'); node('strong',title,actions);
   [['earlier',-1],['later',1]].forEach(function (direction) { var action = button(direction[1] < 0 ? '↑' : '↓',actions,function () { if (owns(group,item,row,owner)) { move(group,index,index+direction[1]); } }); action.dataset.editorMove = direction[0]; action.setAttribute('aria-label','Move '+title.toLowerCase()+' '+direction[0]); action.disabled = !enabled() || index+direction[1] < 0 || index+direction[1] >= items(group).length; });
   if (group === 'gallery') { var remove = button('Remove image',actions,function () {
    if (!owns(group,item,row,owner)) { return; } owner.gallery.items = sorted(group).filter(function (entry) { return entry !== item; }); owner.gallery.items.forEach(function (entry,i) { entry.order = i+1; });
    invalidate(); changed(group); draw(); hydrate(); var next = find('list',group).children[Math.min(index,items(group).length-1)]; (next ? next.querySelector('summary') : root.querySelector('[data-archive-gallery-add]')).focus();
   }); remove.dataset.archiveGalleryRemove = ''; remove.disabled = !enabled(); }
   var details = node('details','',row,'lunara-archive-media-card'); details.open = opened.has(key(item)); var summary = node('summary',group === 'gallery' ? item.caption || 'Image and caption' : item.label || 'Card details',details); summary.dataset.archiveMediaSummary = '';
   details.addEventListener('toggle',function () { if (details.open) { opened.add(key(item)); } else { opened.delete(key(item)); } });
   var body = node('div','',details,'lunara-archive-media-body');
   function patch(values) {
    if (!owns(group,item,row,owner)) { return; }
    if (Object.prototype.hasOwnProperty.call(values,'image_id')) {
     if (group === 'gallery' && items(group).some(function (entry) { return entry !== item && entry.attachment_id === values.image_id; })) { context.announce('That image is already in this gallery. Choose a different image.'); return; }
     item[group === 'gallery' ? 'attachment_id' : 'image_id'] = values.image_id; row.dataset.archiveImageId = values.image_id; invalidate();
    }
    ['focal_x','focal_y'].forEach(function (name) { if (Object.prototype.hasOwnProperty.call(values,name)) { item[name] = values[name]; } }); changed(group); updateMedia(); if (Object.prototype.hasOwnProperty.call(values,'image_id')) { hydrate(); }
   }
   if (group === 'retention') {
    field(body,'Show this card','visible',item,'checkbox',0,function (value) { if (owns(group,item,row,owner)) { item.visible = value; changed(group); } });
    var destinations = journal ? [['latest','Latest Journal article'],['trailer','Latest trailer'],['reviews','Reviews'],['journal','Journal'],['custom','Custom link']] : [['latest','Latest review'],['journal','Journal'],['oscars','Oscars'],['reviews','Reviews'],['custom','Custom link']];
    field(body,'Destination','destination',item,'select',0,function (value) { if (owns(group,item,row,owner)) { item.destination = value; changed(group); } },destinations);
    field(body,'Custom link (required for Custom link destination)','url',item,'text',2048,function (value) { if (owns(group,item,row,owner)) { item.url = value; changed(group); } });
   }
   var widget = controls.image({parent:body,aspect:'16 / 9',showFit:false,showZoom:false,allowReset:group === 'retention',resetLabel:'Remove image',emptyLabel:'No image selected',enabled:function () { return owns(group,item,row,owner); },ticket:function () { return generation; },announce:context.announce,
    getValue:function () { return {image_id:imageId(group,item),focal_x:item.focal_x,focal_y:item.focal_y,fit:'cover',zoom:100}; },source:function () { return {url:'',label:''}; },image:function (id) { return metadata[id] && metadata[id].available ? metadata[id].url : ''; },selectedImage:function () {},change:patch,
    sourceText:function () { var id = imageId(group,item), entry = metadata[id]; return !id ? journal ? 'This card will display without an image.' : 'This image card stays hidden until an image is chosen. Its saved link and text are retained.' : loading ? 'Loading public image preview…' : failed ? 'Image details could not load. Your selection is retained.' : !entry || !entry.available ? 'Image #'+id+' is unavailable. Replace it'+(group === 'gallery' ? ' or remove this gallery row' : ' or remove the image')+' before Preview or Apply.' : 'Public preview: '+entry.width+' × '+entry.height+'.'+(entry.width < 1920 || entry.height < 1080 ? ' Small image: may look soft on larger screens; 1920 × 1080 or larger is recommended.' : '')+(entry.default_alt ? ' Library alt text: '+entry.default_alt : ''); }
   }); widgets.push(widget);
   var strings = group === 'gallery' ? [['alt','Alt text (required)',180],['caption','Caption',360],['link_url','Optional image link',2048],['credit','Image credit (required)',180],['source','Source name (required)',180],['source_url','HTTPS source link (required)',2048]] : [['label','Button text',80]].concat(journal ? [['title','Card heading',140],['copy','Card description',360]] : []).concat([['image_alt','Alt text',180],['image_credit','Image credit',180],['image_source','Source name',180],['image_source_url','HTTPS source link',2048]]);
   strings.forEach(function (entry) { field(body,entry[1],entry[0],item,entry[0] === 'caption' || entry[0] === 'copy' ? 'textarea' : 'text',entry[2],function (value) { if (!owns(group,item,row,owner)) { return; } item[entry[0]] = value; if (entry[0] === (group === 'gallery' ? 'caption' : 'label')) { summary.textContent = value || (group === 'gallery' ? 'Image and caption' : 'Card details'); } changed(group); }); });
   return row;
  }
  groups.forEach(function (group) { lists[group] = controls.orderedList({parent:find('list',group),items:function () { return sorted(group); },key:key,row:function (item,index) { return row(group,item,index); },move:function (from,to) { move(group,from,to); },enabled:enabled}); find('retry',group).addEventListener('click',function () { hydrate(); }); });
  function updateMedia() {
   widgets.forEach(function (widget) { widget.render(); });
   groups.forEach(function (group) {
    find('controls',group).disabled = !enabled(); var count = items(group).length, unavailable = items(group).filter(function (item) { var id = imageId(group,item); return id && metadata[id] && !metadata[id].available; }).length;
    find('status',group).textContent = (group === 'gallery' ? count ? count+' of 12 images. ' : 'No gallery images selected. The gallery stays hidden. ' : 'Three cards; hidden cards retain their settings. ')+(loading ? 'Loading image details…' : failed ? 'Image details could not refresh. Your selections are retained. Retry image details.' : unavailable ? unavailable+' unavailable image(s). Replace or remove them before Preview or Apply.' : '');
    find('retry',group).hidden = !failed; find('retry',group).disabled = !enabled() || loading;
   }); root.querySelector('[data-archive-gallery-add]').disabled = !enabled() || items('gallery').length >= 12;
  }
  function draw(focus) { widgets = []; fields = []; groups.forEach(function (group) { heading(group); lists[group].render(focus && focus.group === group ? focus : null); }); updateMedia(); }
  function requestedIds() { return Array.from(new Set(items('gallery').map(function (item) { return item.attachment_id; }).concat(items('retention').map(function (item) { return item.image_id; })).filter(Boolean))); }
  function validPayload(payload,ids) {
   return object(payload) && Object.keys(payload).length === 1 && Array.isArray(payload.images) && payload.images.length === ids.length && payload.images.every(function (entry,index) {
    return object(entry) && Object.keys(entry).sort().join(',') === 'available,default_alt,height,id,url,width' && entry.id === ids[index] && typeof entry.available === 'boolean' && typeof entry.url === 'string' && typeof entry.default_alt === 'string' && integer(entry.width,0,100000) && integer(entry.height,0,100000) && (entry.available ? !!controls.imageUrl(entry.url) && entry.width > 0 && entry.height > 0 : !entry.url && !entry.default_alt && entry.width === 0 && entry.height === 0);
   });
  }
  function hydrate() {
   if (!enabled()) { return; } var ids = requestedIds(), ticket = generation, request = ++sequence; if (controller) { controller.abort(); } controller = window.AbortController ? new AbortController() : null;
   failed = false; if (!ids.length) { metadata = {}; loading = false; updateMedia(); return; } loading = true; updateMedia();
   var url; try { url = new URL(context.config.endpoints.state,window.location.href); if (url.origin !== window.location.origin || url.username || url.password) { throw new Error('Invalid destination'); } if (url.searchParams.has('rest_route')) { url.searchParams.set('rest_route',url.searchParams.get('rest_route').replace(/\/state$/,'/media')); } else { url.pathname = url.pathname.replace(/\/state$/,'/media'); } url.searchParams.set('image_ids',ids.join(',')); } catch (error) { loading = false; failed = true; updateMedia(); return; }
   fetch(url.href,{credentials:'same-origin',headers:{'X-WP-Nonce':context.config.nonce},signal:controller ? controller.signal : undefined}).then(function (response) { if (!response.ok) { throw new Error('Request failed'); } return response.json(); }).then(function (payload) {
    if (ticket !== generation || request !== sequence || !enabled()) { return; } if (!validPayload(payload,ids)) { throw new Error('Invalid image details'); } metadata = {}; payload.images.forEach(function (entry) { metadata[entry.id] = entry; }); loading = false; failed = false; updateMedia();
   }).catch(function () { if (ticket !== generation || request !== sequence || !enabled()) { return; } metadata = {}; loading = false; failed = true; updateMedia(); });
  }
  root.querySelector('[data-archive-gallery-add]').addEventListener('click',function () {
   if (!enabled() || items('gallery').length >= 12) { return; } if (!window.wp || typeof window.wp.media !== 'function') { context.announce('The Media Library could not load. Your changes are still here.'); return; }
   var ticket = generation, owner = context.getState(), frame = window.wp.media({title:'Add gallery image',library:{type:'image'},multiple:false});
   frame.on('select',function () {
    if (!enabled() || ticket !== generation || owner !== context.getState() || items('gallery').length >= 12) { return; } var selection = frame.state().get('selection').first(), selected = selection && selection.toJSON(); if (!selected || !integer(Number(selected.id),1,Number.MAX_SAFE_INTEGER)) { context.announce('Choose an image from the Media Library.'); return; }
    var id = Number(selected.id); if (items('gallery').some(function (item) { return item.attachment_id === id; })) { context.announce('That image is already in this gallery. Choose a different image.'); return; }
    var item = {order:items('gallery').length+1,attachment_id:id,alt:'',caption:'',link_url:'',credit:'',source:'',source_url:'',focal_x:50,focal_y:50}; owner.gallery.items.push(item); opened.add(key(item)); invalidate(); changed('gallery'); draw(); hydrate(); var added = find('list','gallery').lastElementChild; if (added) { added.querySelector('[data-archive-media-field="alt"]').focus(); }
   }); frame.open();
  });
  function render() { invalidate(); metadata = {}; failed = false; opened.clear(); draw(); hydrate(); }
  function setBusy(value) { busy = value; if (value) { invalidate(); } updateMedia(); if (!value) { hydrate(); } }
  return {render:render,setBusy:setBusy,invalidate:invalidate};
 }
 window.LunaraSiteStudioArchiveMediaEditor = {validateState:validState,validateDom:validDom,create:create};
}());
