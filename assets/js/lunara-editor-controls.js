/* Shared visual controls. Content selection and persistence belong to the caller. */
(function () {
 'use strict';
 function node(tag, text, parent, className) {
  var element = document.createElement(tag);
  if (text !== undefined && text !== null) { element.textContent = text; }
  if (className) { element.className = className; }
  if (parent) { parent.appendChild(element); }
  return element;
 }
 function button(text, parent, action, className) {
  var element = node('button', text, parent, className || 'button');
  element.type = 'button'; element.addEventListener('click', action); return element;
 }
 function imageUrl(value) {
  if (typeof value !== 'string' || !value) { return ''; }
  try { var url = new URL(value, window.location.href); return /^https?:$/.test(url.protocol) && !url.username && !url.password ? url.href : ''; } catch (error) { return ''; }
 }
 function thumb(parent, url, className) {
  var wrap = node('span', '', parent, 'lunara-editor-thumb' + (className ? ' ' + className : ''));
  var src = imageUrl(url);
  if (src) { var img = node('img', '', wrap); img.src = src; img.alt = ''; img.loading = 'lazy'; img.addEventListener('error', function () { wrap.replaceChildren(); node('span', 'No artwork', wrap); }, {once:true}); }
  else { node('span', 'No artwork', wrap); }
  return wrap;
 }
 function range(parent, title, key, min, max, getValue, change) {
  var label = node('label', '', parent, 'lunara-editor-field');
  var caption = node('span', title, label), value = node('output', '', caption);
  var input = node('input', '', label); input.type = 'range'; input.min = min; input.max = max; input.step = 1;
  input.setAttribute('aria-label', title); input.dataset.editorField = key;
  function render() { input.value = getValue()[key]; value.textContent = ' ' + input.value + '%'; }
  input.addEventListener('input', function () { change(key, Number(input.value)); render(); }); render();
  return {input:input, render:render};
 }
 function imageControl(options) {
  var root = node('div', '', options.parent, 'lunara-editor-image');
  var stage = button('', root, function (event) {
   if (!options.enabled() || options.getValue().fit === 'full') { return; }
   var rect = stage.getBoundingClientRect();
   if (!rect.width || !rect.height || event.detail === 0) { return; }
   options.change({focal_x:Math.round(Math.max(0, Math.min(100, (event.clientX - rect.left) / rect.width * 100))), focal_y:Math.round(Math.max(0, Math.min(100, (event.clientY - rect.top) / rect.height * 100)))}); render();
  }, 'lunara-editor-image-stage');
  stage.style.aspectRatio = options.aspect || '16 / 10';
  stage.setAttribute('aria-label', 'Image framing: click to set the focal point, or use the arrow keys');
  var img = node('img', '', stage), placeholder = node('span', 'No artwork selected', stage, 'lunara-editor-image-empty'), target = node('span', '', stage, 'lunara-editor-focal');
  img.alt = ''; target.setAttribute('aria-hidden', 'true');
  var source = node('p', '', root, 'lunara-editor-image-source');
  var actions = node('div', '', root, 'lunara-editor-actions');
  var choose = button('Choose image', actions, function () {
   if (!options.enabled()) { return; }
   if (!window.wp || typeof window.wp.media !== 'function') { options.announce('The Media Library could not load. Your changes are still here.'); return; }
   var ticket = options.ticket();
   var frame = window.wp.media({title:'Choose display image', library:{type:'image'}, multiple:false});
   frame.on('select', function () {
    if (!options.enabled() || ticket !== options.ticket()) { return; }
    var selection = frame.state().get('selection').first(); if (!selection) { return; }
    var item = selection.toJSON(), id = Number(item.id), url = imageUrl(item.url || (item.sizes && item.sizes.large && item.sizes.large.url));
    if (!Number.isSafeInteger(id) || id < 1) { options.announce('Choose an image from the Media Library.'); return; }
    options.selectedImage(id, url); options.change(options.allowHidden ? {image_id:id,hidden:false} : {image_id:id}); render();
   }); frame.open();
  });
  var reset = button('Use source image', actions, function () { if (options.enabled()) { options.change(options.allowHidden ? {image_id:0,hidden:false} : {image_id:0}); render(); } });
  var remove = options.allowHidden ? button('Remove image', actions, function () { if (options.enabled()) { options.change({hidden:true}); render(); } }) : null;
  var fitLabel = node('label', 'Image fit', root, 'lunara-editor-field'), fit = node('select', '', fitLabel);
  fit.setAttribute('aria-label', 'Image fit');
  [['cover','Fill frame'],['full','Show full image']].forEach(function (entry) { var option = node('option', entry[1], fit); option.value = entry[0]; });
  fit.addEventListener('change', function () { if (options.enabled()) { options.change({fit:fit.value}); render(); } });
  var framing = node('div', '', root, 'lunara-editor-framing');
  var sliders = [['Horizontal focal point','focal_x',0,100],['Vertical focal point','focal_y',0,100],['Zoom','zoom',100,112]].map(function (entry) {
   return range(framing, entry[0], entry[1], entry[2], entry[3], options.getValue, function (key, value) { if (options.enabled()) { var patch = {}; patch[key] = value; options.change(patch); render(); } });
  });
  stage.addEventListener('keydown', function (event) {
   var keys = {ArrowLeft:['focal_x',-5],ArrowRight:['focal_x',5],ArrowUp:['focal_y',-5],ArrowDown:['focal_y',5]};
   if (!keys[event.key] || !options.enabled() || options.getValue().fit === 'full') { return; }
   event.preventDefault(); var key = keys[event.key][0], patch = {}; patch[key] = Math.max(0, Math.min(100, options.getValue()[key] + keys[event.key][1])); options.change(patch); render();
  });
  img.addEventListener('error', function () { failedUrl = lastUrl; img.hidden = true; placeholder.hidden = false; placeholder.textContent = 'Artwork unavailable — choose another image'; });
  var lastUrl = '', failedUrl = '';
  function render() {
   var value = options.getValue(), inherited = options.source(), url = value.hidden ? '' : imageUrl(value.image_id ? options.image(value.image_id) : inherited.url);
   if (url !== lastUrl) { lastUrl = url; if (url) { img.src = url; } else { img.removeAttribute('src'); } }
   img.hidden = !url || failedUrl === url; placeholder.hidden = !!url && failedUrl !== url;
   if (!url) { placeholder.textContent = value.hidden ? 'Image hidden for this placement' : value.image_id ? 'Display image unavailable' : 'No source artwork'; }
   img.style.objectFit = value.fit === 'full' ? 'contain' : 'cover';
   img.style.objectPosition = value.focal_x + '% ' + value.focal_y + '%';
   img.style.transform = value.fit === 'full' ? 'none' : 'scale(' + value.zoom / 100 + ')';
   if (options.bleed) { var bleed = value.fit === 'full' ? 0 : options.bleed; img.style.inset = -bleed + '%'; img.style.width = img.style.height = (100 + bleed * 2) + '%'; }
   target.style.left = value.focal_x + '%'; target.style.top = value.focal_y + '%'; target.hidden = !url || value.fit === 'full';
   source.textContent = value.image_id ? 'Custom image for this placement' : 'Source: ' + (inherited.label || 'Article artwork');
   choose.textContent = url || value.image_id ? 'Replace image' : 'Choose image'; reset.disabled = (!value.image_id && !value.hidden) || !options.enabled();
   if (remove) { remove.disabled = value.hidden || !options.enabled(); }
   fit.value = value.fit; sliders.forEach(function (slider) { slider.render(); slider.input.disabled = value.fit === 'full' || !options.enabled(); });
   stage.setAttribute('aria-description', 'Focal point: ' + value.focal_x + '% horizontal, ' + value.focal_y + '% vertical.');
  }
  render(); return {render:render, element:root, setAspect:function (width, height) { if (width > 0 && height > 0) { stage.style.aspectRatio = width + ' / ' + height; } }};
 }
 function orderedList(options) {
  var dragKey = null;
  function cancelDrag() {
   dragKey = null;
   var dragging = options.parent.querySelectorAll('.is-dragging');
   for (var i = 0; i < dragging.length; i += 1) { dragging[i].classList.remove('is-dragging'); }
  }
  function eventRow(event) {
   var row = event.target && event.target.closest ? event.target.closest('[data-editor-key]') : null;
   return row && row.parentNode === options.parent ? row : null;
  }
  options.parent.addEventListener('dragstart', function (event) {
   var row = eventRow(event);
   if (!row || !options.enabled() || event.target.closest('input,select,textarea,button,a,summary')) { if (row) { event.preventDefault(); } return; }
   dragKey = row.dataset.editorKey; row.classList.add('is-dragging'); event.dataTransfer.effectAllowed = 'move'; event.dataTransfer.setData('text/plain', dragKey);
  });
  options.parent.addEventListener('dragover', function (event) { if (eventRow(event) && options.enabled() && dragKey !== null) { event.preventDefault(); event.dataTransfer.dropEffect = 'move'; } });
  options.parent.addEventListener('drop', function (event) {
   var row = eventRow(event), items = options.items(), from = items.findIndex(function (entry) { return String(options.key(entry)) === dragKey; }), to = row ? items.findIndex(function (entry) { return String(options.key(entry)) === row.dataset.editorKey; }) : -1;
   event.preventDefault(); cancelDrag(); if (row && options.enabled() && from >= 0 && to >= 0) { options.move(from, to); }
  });
  options.parent.addEventListener('dragend', cancelDrag);
  function render(focus) {
   cancelDrag(); options.parent.replaceChildren(); var items = options.items();
   items.forEach(function (item, index) {
    var key = String(options.key(item)), row = options.row(item, index);
    row.dataset.editorKey = key; row.draggable = true; options.parent.appendChild(row);
   });
   if (focus) {
    var row = Array.from(options.parent.children).find(function (entry) { return entry.dataset.editorKey === String(focus.key); });
    var target = row && (row.querySelector('[data-editor-move="' + focus.direction + '"]:not(:disabled)') || row.querySelector('[data-editor-move]:not(:disabled)') || row.querySelector('summary'));
    if (target) { target.focus(); }
   }
  }
  return {render:render, cancelDrag:cancelDrag};
 }
 window.LunaraEditorControls = {node:node,button:button,imageUrl:imageUrl,thumbnail:thumb,range:range,image:imageControl,orderedList:orderedList};
}());
