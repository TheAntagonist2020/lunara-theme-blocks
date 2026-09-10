'use strict';
const fs = require('fs'); const path = require('path'); const { spawnSync } = require('child_process');
let chromium; try { ({ chromium } = require('playwright')); } catch (error) { ({ chromium } = require('playwright-core')); }
const themeRoot = path.resolve(__dirname, '..');
const cssPath = path.join(themeRoot, 'assets/css/lunara-site-studio.css'); const jsPath = path.join(themeRoot, 'assets/js/lunara-site-studio.js');
const css = fs.existsSync(cssPath) ? fs.readFileSync(cssPath, 'utf8') : ''; const editorCss=fs.readFileSync(path.join(themeRoot,'assets/css/lunara-editor-controls.css'),'utf8'); const controller = fs.existsSync(jsPath) ? fs.readFileSync(jsPath, 'utf8') : ''; const editorControls=fs.readFileSync(path.join(themeRoot,'assets/js/lunara-editor-controls.js'),'utf8'); const previewBridge=fs.readFileSync(path.join(themeRoot,'assets/js/lunara-site-studio-preview.js'),'utf8');
function assert(condition, message, metrics) { if (!condition) { if (metrics) process.stderr.write(`${JSON.stringify(metrics, null, 2)}\n`); throw new Error(message); } }
function lastRequestTo(requests, suffix) { for (let index=requests.length-1;index>=0;index-=1) { if (requests[index].path.endsWith(suffix)) return requests[index]; } return null; }
function saveEnvelope(state) { return { state, changed_sections: [], revision_id: 'revision-safe', timestamp: '2026-08-28 12:00:00' }; }
function restoreEnvelope(state) { return { state, safety_revision_id: 'safety-safe', timestamp: '2026-08-28 12:00:00' }; }
function fixture(surface, controllerSource, controlsSource) { const result = spawnSync('php', [path.join(__dirname, 'site-studio-runtime.php'), `--fixture=${surface}`], { encoding: 'utf8' }); if (result.error || result.status !== 0) throw result.error || new Error(result.stderr); const adminCss='<style>#wpcontent{margin-left:160px}#wpbody-content{min-width:0;padding-bottom:40px}@media(max-width:782px){#wpcontent{margin-left:0}}</style>'; const usesOrderedList=['homepage-structure','reviews-archive','journal-archive','lunara-method','oscars-portal'].includes(surface); const sharedSource=typeof controlsSource==='undefined'?editorControls:controlsSource; return result.stdout.replace('</head>', `<style>${css}</style>${usesOrderedList?`<style>${editorCss}</style>`:''}${adminCss}</head>`).replace('<body class="wp-admin">','<body class="wp-admin"><div id="wpwrap"><div id="wpcontent"><div id="wpbody"><div id="wpbody-content">').replace('</body>','</div></div></div></div>'+(usesOrderedList?'<script>'+sharedSource+'</script>':'')+(surface==='lunara-method'?'<script>'+fs.readFileSync(path.join(themeRoot,'assets/js/lunara-site-studio-method.js'),'utf8')+'</script>':'')+'<script>'+(controllerSource||controller)+'</script></body>'); }
async function startDrag(page, selector) { return page.locator(selector).evaluate(node => { const dataTransfer=new DataTransfer(); const started=node.dispatchEvent(new DragEvent('dragstart',{bubbles:true,cancelable:true,dataTransfer})); return {started,draggable:node.draggable,dragging:node.classList.contains('is-dragging')}; }); }
async function dropOn(page, selector) { await page.locator(selector).evaluate(node => node.dispatchEvent(new DragEvent('drop',{bubbles:true,cancelable:true,dataTransfer:new DataTransfer()}))); }
async function pointerDrag(page, source, target) { const from=await source.boundingBox(),to=await target.boundingBox();assert(from&&to,'Pointer drag rows must be visible.');await page.mouse.move(from.x+3,from.y+3);await page.mouse.down();await page.mouse.move(from.x+18,from.y+18,{steps:5});await page.waitForTimeout(50);await page.mouse.move(to.x+3,to.y+3,{steps:16});await page.mouse.up(); }

(async()=>{
 const browser=await chromium.launch({headless:true,executablePath:process.env.LUNARA_BROWSER_EXECUTABLE});
 try {
  for(const surface of ['oscars-portal','homepage-structure','reviews-archive','journal-archive']) {
   for(const width of [390,1281]) {
    const layout=await browser.newPage({viewport:{width,height:1000}});
    await layout.route('https://example.test/**',r=>r.fulfill({contentType:'text/html',body:r.request().url().includes('/wp-admin/')?fixture(surface):'<!doctype html><body>Live</body>'}));
    await layout.goto('https://example.test/wp-admin/admin.php?page=lunara-site-studio&surface='+surface);await layout.waitForSelector('[data-lunara-site-studio-ready="true"]');
    await layout.addStyleTag({content:'body{margin:0;font:14px/1.5 system-ui}button,input,select,textarea{font:inherit}'});
    const measured=await layout.locator('.lunara-site-studio-order-row').evaluateAll(rows=>rows.map(row=>{
     const rect=n=>{const r=n.getBoundingClientRect();return{x:r.x,y:r.y,right:r.right,bottom:r.bottom,width:r.width,height:r.height};};
     const overlaps=(a,b)=>Math.min(a.right,b.right)-Math.max(a.x,b.x)>0.5&&Math.min(a.bottom,b.bottom)-Math.max(a.y,b.y)>0.5;
     const children=[...row.children].filter(n=>!n.hidden),bounds=rect(row),boxes=children.map(rect),errors=[];
     boxes.forEach((box,i)=>{if(box.x<bounds.x||box.right>bounds.right||box.y<bounds.y||box.bottom>bounds.bottom)errors.push('child '+i+' escapes row');for(let j=i+1;j<boxes.length;j++)if(overlaps(box,boxes[j]))errors.push('children '+i+'/'+j+' overlap');});
     const moves=[...row.querySelectorAll('[data-editor-move]')];
     moves.forEach(button=>{const box=rect(button);if(box.width<44||box.height<44||box.width>48)errors.push('move button must have a separate 44px target');});
     const title=row.querySelector(':scope > strong,:scope > span:not([data-derived-visibility])'),visibility=row.querySelector('label,[data-derived-visibility]');
     for(const node of [title,visibility]){if(!node){errors.push('missing title/status');continue;}const range=document.createRange();range.selectNodeContents(node);for(const glyph of range.getClientRects()){for(const button of moves){if(overlaps(glyph,rect(button)))errors.push('label text overlaps move button');}const own=rect(node);if(glyph.left<own.x-0.5||glyph.right>own.right+0.5)errors.push('label text escapes its cell');}}
     if(moves.length!==2||Math.abs(rect(moves[0]).y-rect(moves[1]).y)>0.5)errors.push('both move buttons must share a row');
     return {slug:row.dataset.slug,railWidth:row.closest('.lunara-site-studio-section-rail').getBoundingClientRect().width,errors};
    }));
    assert(measured.length>0&&measured.every(row=>row.errors.length===0),'Ordered rows must contain non-overlapping titles, visibility and separate buttons: '+surface+' at '+width,measured);
    if(width===1281)assert(measured[0].railWidth<=220,'Desktop regression must exercise a narrow rail.',measured[0]);
    process.stdout.write('row-boxes '+surface+' viewport='+width+' rail='+measured[0].railWidth+' rows='+measured.length+' overlap=0 escaped=0\n');
    await layout.close();
   }
  }
  process.stdout.write('Ordered row boxes: Oscars, Homepage, Reviews and Journal at 390px and narrow desktop passed.\n');
  for(const width of [1440,390]) {
   const page=await browser.newPage({viewport:{width,height:1000}});page.on('dialog',d=>d.accept());
   let baseline,saved,mode='ok',requests=[];
   await page.route('https://example.test/**',async route=>{
    const u=new URL(route.request().url());
    if(u.pathname==='/wp-admin/admin.php') {let html=fixture('oscars-portal');if(saved)html=html.replace(/(<script[^>]* id="lunara-site-studio-state"[^>]*>)[\s\S]*?(<\/script>)/,(_,a,b)=>a+JSON.stringify(saved)+b);return route.fulfill({contentType:'text/html',body:html});}
    if(u.pathname.includes('/surfaces/')){
     const body=route.request().postDataJSON();requests.push({path:u.pathname,body});
     if(u.pathname.endsWith('/revisions'))return route.fulfill({json:{revisions:[{id:'revision-safe',timestamp:'2026-09-10',action:'save'}]}});
     if(mode==='fail'&&u.pathname.endsWith('/save'))return route.fulfill({status:422,json:{message:'Rejected',fields:{'identity.title':'Review this headline.'}}});
     if(u.pathname.endsWith('/save')){saved=body.state;return route.fulfill({json:saveEnvelope(saved)});}
     if(u.pathname.endsWith('/restore')){saved=baseline;return route.fulfill({json:restoreEnvelope(saved)});}
     return route.fulfill({json:{url:'https://example.test/oscars/?lunara_oscars_preview=123e4567-e89b-42d3-a456-426614174000'}});
    }
    const instance=u.searchParams.get('lunara_site_studio_instance');const markers=['board','hero','navigator','doors','spotlights','titles','research','linked-reviews','winners','deep-cuts','rotating-winners'];
    return route.fulfill({contentType:'text/html',body:'<!doctype html><body>'+markers.map(x=>'<section data-lunara-site-studio-section="'+x+'">'+x+'</section>').join('')+(instance?'<script>window.LunaraSiteStudioPreviewConfig='+JSON.stringify({protocol:'lunara-site-studio/v1',version:1,type:'select-section',surface:'oscars-portal',instance,markers})+'</script><script>'+previewBridge+'</script>':'')+'</body>'});
   });
   await page.goto('https://example.test/wp-admin/admin.php?page=lunara-site-studio&surface=oscars-portal');await page.waitForSelector('[data-lunara-site-studio-ready="true"]');
   baseline=await page.locator('#lunara-site-studio-state').evaluate(n=>JSON.parse(n.textContent));
   assert(await page.locator('[data-field-path^="identity."]').count()===11&&await page.locator('[data-field-path^="presentation."]').count()===7,'Every canonical copy and presentation field is available.');
   const rows=()=>page.locator('[data-section-row]').evaluateAll(ns=>ns.map(n=>n.dataset.slug));
   const hero=page.locator('[data-section-row][data-slug="hero"]');await hero.locator('[data-section-move="later"]').click();
   assert((await rows())[1]==='hero','Keyboard alternative must update visible order.');
   assert(await hero.locator('[data-section-move="later"]').evaluate(n=>n===document.activeElement),'Keyboard move retains focus.');
   if(width===1440){const board=page.locator('[data-section-row][data-slug="board"]'),doors=page.locator('[data-section-row][data-slug="doors"]');await pointerDrag(page,board,doors);assert((await rows()).indexOf('board')>(await rows()).indexOf('doors'),'Pointer drag reorders canonical sections.');}
   await page.locator('[data-section-visible="doors"]').uncheck();assert(await page.locator('[data-derived-visibility="navigator"]').textContent()==='Hidden with doors','Navigator reflects doors immediately.');
   for(const field of Object.keys(baseline.identity)){await page.fill('[data-field-path="identity.'+field+'"]','Draft '+field);}
   await page.fill('[data-field-path="identity.title"]','Private Portal draft');
   await page.locator('details[data-section="fine-tune"]').evaluate(n=>n.open=true);await page.selectOption('[data-field-path="presentation.density"]','showcase');await page.fill('[data-field-path="presentation.hero_min_height"]','410');await page.fill('[data-field-path="presentation.section_gap"]','50');await page.fill('[data-field-path="presentation.card_min_height"]','380');await page.fill('[data-field-path="presentation.winners_min_width"]','210');await page.selectOption('[data-field-path="presentation.lead_prominence"]','gallery');await page.selectOption('[data-field-path="presentation.board_rhythm"]','dense');
   await page.click('[data-action="preview"]');await page.waitForFunction(()=>document.querySelector('[data-lunara-site-studio]').dataset.workspaceState==='preview-current');
   let sent=lastRequestTo(requests,'/preview').body.state;assert(sent.identity.title==='Private Portal draft'&&sent.section_visibility.navigator===false&&sent.section_visibility.board===true&&sent.section_order[1]==='hero','Preview sends exact draft and derived state.');for(const field of Object.keys(baseline.identity)){assert(sent.identity[field]===(field==='title'?'Private Portal draft':'Draft '+field),'Copy field survives preview: '+field);}assert(sent.presentation.section_gap===50&&sent.presentation.card_min_height===380&&sent.presentation.winners_min_width===210&&sent.presentation.lead_prominence==='gallery'&&sent.presentation.board_rhythm==='dense','All presentation controls reach the provider.');
   await page.frameLocator('iframe').locator('[data-lunara-site-studio-section="winners"]').click();await page.waitForSelector('[data-section-control="winners"][data-preview-selected]');
   await page.fill('[data-field-path="identity.title"]','Changed after preview');assert(await page.locator('[data-lunara-site-studio]').getAttribute('data-workspace-state')==='preview-stale','Editing makes preview stale.');
   mode='fail';await page.click('[data-action="save"]');await page.waitForFunction(()=>document.querySelector('[data-lunara-site-studio]').dataset.workspaceState==='validation-error');assert(await page.inputValue('[data-field-path="identity.title"]')==='Changed after preview','Failed save retains draft.');assert(await page.locator('[data-field-path="identity.title"]').getAttribute('aria-invalid')==='true','Failure maps to native field.');
   mode='ok';await page.click('[data-action="save"]');await page.waitForFunction(()=>document.querySelector('[data-lunara-site-studio]').dataset.workspaceState==='live-saved');assert(saved.presentation.hero_min_height===410,'Apply persists bounded presentation.');
   await page.reload();await page.waitForSelector('[data-lunara-site-studio-ready="true"]');assert(await page.inputValue('[data-field-path="identity.title"]')==='Changed after preview','Reload reflects applied state.');
   await page.fill('[data-field-path="identity.title"]','Discard this');await page.click('[data-action="discard"]');assert(await page.inputValue('[data-field-path="identity.title"]')==='Changed after preview','Discard restores loaded baseline.');
   await page.locator('[data-revision-history]').evaluate(n=>n.open=true);await page.locator('[data-action="restore"]').first().click();await page.waitForFunction(()=>document.querySelector('[data-lunara-site-studio]').dataset.workspaceState==='restored');assert(await page.inputValue('[data-field-path="identity.title"]')===baseline.identity.title,'History restore adopts exact provider state.');assert(JSON.stringify(await rows())===JSON.stringify(baseline.section_order),'Restore resets exact order.');await page.click('[data-preview-width="mobile"]');assert(await page.locator('iframe').getAttribute('width')==='390','Mobile preview has a real 390px viewport.');for(const action of ['preview','save','discard']){await page.locator('[data-action="'+action+'"]').scrollIntoViewIfNeeded();const box=await page.locator('[data-action="'+action+'"]').boundingBox();assert(box&&box.x>=0&&box.x+box.width<=width+1,'Essential action remains reachable at '+width+': '+action);}
   await page.close();
  }
  const historical=await browser.newPage();const longTitle='Historical headline '.repeat(20);let historicalHtml=fixture('oscars-portal').replace(/(<script[^>]* id="lunara-site-studio-state"[^>]*>)([\s\S]*?)(<\/script>)/,(_,a,value,b)=>{const state=JSON.parse(value);state.identity.title=longTitle;return a+JSON.stringify(state)+b;});await historical.route('https://example.test/**',r=>r.fulfill({contentType:'text/html',body:r.request().url().includes('/wp-admin/')?historicalHtml:'<!doctype html><body>Live</body>'}));await historical.goto('https://example.test/wp-admin/admin.php?page=lunara-site-studio&surface=oscars-portal');assert(await historical.locator('[data-lunara-site-studio-ready="true"]').count()===1&&await historical.inputValue('[data-field-path="identity.title"]')===longTitle,'Historical canonical copy opens verbatim without an upgrade rewrite.');await historical.close();
  const p=await browser.newPage();await p.route('https://example.test/**',r=>r.fulfill({contentType:'text/html',body:fixture('oscars-portal',controller,'')}));await p.goto('https://example.test/wp-admin/admin.php?page=lunara-site-studio&surface=oscars-portal');assert(await p.locator('[data-lunara-site-studio]').getAttribute('data-workspace-state')==='recovery','Unavailable ordered-list dependency fails closed.');await p.close();
  process.stdout.write('site-studio Oscars browser: desktop/mobile copy, presentation, ordering, derived visibility, preview bridge, stale preview, failed save, apply, discard, reload, restore, dependency recovery passed.\n');
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exit(1)});
