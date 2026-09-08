'use strict';
const fs = require('fs'), path = require('path'), {spawnSync} = require('child_process'), {chromium} = require('playwright-core');
let checks = 0;
function check(value, message) { checks++; if (!value) { throw new Error(message); } }
function barrier() { let release; const wait = new Promise(resolve => { release = resolve; }); return {wait,release}; }
(async () => {
 const executablePath = process.env.LUNARA_BROWSER_EXECUTABLE || ['C:/Program Files/Google/Chrome/Application/chrome.exe','C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe','/usr/bin/chromium','/usr/bin/chromium-browser','/usr/bin/google-chrome'].find(fs.existsSync);
 check(executablePath && fs.existsSync(executablePath), 'A real Chromium executable is required.');
 const browser = await chromium.launch({headless:true, executablePath});
 const controller = ['lunara-editor-controls.js','lunara-site-studio-carousels.js','lunara-site-studio.js'].map(file => fs.readFileSync(path.join(__dirname,'../assets/js',file),'utf8')).join('\n');
 const css = ['lunara-site-studio.css','lunara-editor-controls.css','lunara-site-studio-carousels.css'].map(file => fs.readFileSync(path.join(__dirname,'../assets/css',file),'utf8')).join('\n');
 const art = fs.readFileSync(path.join(__dirname,'fixtures/home-carousel-art.svg'),'utf8');
 try {
  for (const kind of ['hero','journal']) {
   const rendered = spawnSync('php',[path.join(__dirname,'home-carousel-settings-runtime.php'),'--fixture', ...(kind === 'journal' ? ['--journal'] : [])],{encoding:'utf8'});
   check(rendered.status === 0,'Real PHP inspector fixture renders: ' + rendered.stderr);
   const page = await browser.newPage({viewport:{width:1440,height:1000}});
   page.setDefaultTimeout(12000);
   const errors=[]; page.on('pageerror',error=>errors.push(error.message));
   let submitted, saved, saves = 0, failure = false, previewBarrier, searchBarrier, metadataFailure = false, restores = 0;
   const items = [
    {id:10,title:'Published first',type:kind === 'hero' ? 'review' : 'journal',available:true,date_label:'Sep 8, 2026',image_url:'https://example.test/art.svg',image_source:'Review artwork',excerpt:'Inherited first excerpt',kicker:'Film',cta:'Read the review'},
    {id:20,title:'Published second',type:'journal',available:true,date_label:'Sep 7, 2026',image_url:'',image_source:'No source artwork',excerpt:'Inherited second excerpt',kicker:'Journal',cta:'Read the story'}
   ];
   await page.route('https://example.test/**',async route => {
    const request = route.request(), url = new URL(request.url()), endpoint = url.searchParams.get('rest_route') || url.pathname;
    if (url.pathname === '/wp-admin/admin.php') {
     let html = rendered.stdout;
     if(saved) { html=html.replace(/(<script[^>]+id="lunara-site-studio-state"[^>]*>)[\s\S]*?(<\/script>)/,(_match,start,end)=>start+JSON.stringify(saved)+end); }
     const adminBase = 'body{font:14px/1.5 system-ui;background:#f0f0f1}button,input,select,textarea{font:inherit}.button{border:1px solid #a7b0bb;border-radius:4px;padding:6px 10px;background:#f6f7f7}.button-primary{background:#142033;color:#fff}';
     return route.fulfill({contentType:'text/html',body:html.replace('</head>','<meta name="viewport" content="width=device-width,initial-scale=1"><style>'+adminBase+css+'</style></head>')});
    }
    if (url.pathname === '/controller.js') { return route.fulfill({contentType:'application/javascript',body:controller}); }
    if (url.pathname === '/art.svg') { return route.fulfill({contentType:'image/svg+xml',body:art}); }
    if (endpoint.endsWith('/metadata')) {
     check(request.headers()['x-wp-nonce']==='test-nonce','Metadata read carries nonce');
     if(metadataFailure) { return route.fulfill({status:503,json:{message:'Metadata unavailable'}}); }
     return route.fulfill({json:{items:Object.fromEntries(items.map(item=>[item.id,item])),images:{42:'https://example.test/art.svg?custom=42',77:'https://example.test/art.svg?restored=77'},automatic:[10,20]}});
    }
    if (endpoint.endsWith('/search')) {
     check(request.headers()['x-wp-nonce']==='test-nonce','Search carries nonce'); if(searchBarrier){await searchBarrier.wait;}
     return route.fulfill({json:{items}});
    }
    if (endpoint.endsWith('/preview')) {
     if (previewBarrier) { await previewBarrier.wait; }
     return route.fulfill({json:{url:'https://example.test/?lunara_'+kind+'_carousel_preview=123e4567-e89b-42d3-a456-426614174111'}});
    }
    if (endpoint.endsWith('/save')) {
     submitted = request.postDataJSON().state; saves++; if(failure){return route.fulfill({status:422,json:{message:'Simulated rejected settings'}});}
     saved={...submitted,adopted:true}; return route.fulfill({json:{state:saved,revision_id:'safe-revision',timestamp:'2026-09-08 12:00:00',changed_sections:[kind==='hero'?'hero':'dispatch']}});
    }
    if (endpoint.endsWith('/restore')) {
     check(request.postDataJSON().confirm===true,'Restore uses shared explicit confirmation'); restores++;
     saved={...saved,slides:[{...saved.slides[0],post_id:20,image_id:77,headline:'Restored headline'}]};
     return route.fulfill({json:{state:saved,safety_revision_id:'safety-restore',timestamp:'2026-09-08 12:05:00'}});
    }
    if (endpoint.endsWith('/revisions')) { return route.fulfill({json:{revisions:[{id:'safe-revision',timestamp:'2026-09-08 12:00:00',action:'save'}]}}); }
    const mediaClass = kind==='hero' ? 'lunara-cinematic-hero-bg' : 'lunara-home-news-media';
    return route.fulfill({contentType:'text/html',body:'<html><head><style>body{margin:0}.lunara-cinematic-hero-bg{width:100%;height:600px}.lunara-home-news-media{width:400px;height:250px}@media(max-width:600px){.lunara-cinematic-hero-bg{height:420px}.lunara-home-news-media{width:360px;height:225px}}</style></head><body><main style="min-height:1200px;background:#091926;color:#ddbe72"><div class="'+mediaClass+'">Page preview fixture</div></main></body></html>'});
   });
   await page.goto('https://example.test/wp-admin/admin.php'); await page.waitForSelector('[data-lunara-site-studio-ready="true"]');
   check((await page.locator('[data-carousel-items]').innerText()).includes('Unavailable'),'Unavailable selections visibly flagged');
   check(await page.getByRole('button',{name:'Apply changes',exact:true}).count()===1,'Shared Apply action');
   await page.locator('[data-carousel-field="mode"]').selectOption('auto'); await page.waitForSelector('[data-carousel-automatic] li strong');
   check(await page.locator('[data-carousel-automatic] li strong').count()===2,'Automatic lineup is visible');
   await page.locator('[data-carousel-field="mode"]').selectOption('manual'); check(await page.locator('[data-carousel-items]>li').count()===1,'Mode switching retains manual selections');
   await page.locator('[data-carousel-search-button]').click(); await page.getByRole('button',{name:'Add story: Published first',exact:true}).click(); await page.getByRole('button',{name:'Add story: Published second',exact:true}).click();
   const rows=page.locator('[data-carousel-items]>li'); check(await rows.count()===3,'Search selects stories');
   check(await page.getByRole('button',{name:'Selected: Published first',exact:true}).isDisabled(),'Selected results cannot be duplicated');
   await rows.nth(2).getByRole('button',{name:'Move up',exact:true}).click();
   check(await page.locator('[data-editor-key="20"] [data-editor-move="up"]').evaluate(el=>document.activeElement===el),'Focus follows keyboard ordering');
   await rows.nth(0).getByRole('button',{name:'Remove story',exact:true}).click();
   check((await rows.first().innerText()).includes('Published second'),'Accessible move and removal preserve order');
   await rows.first().locator('.lunara-carousel-story-heading').dragTo(rows.nth(1).locator('.lunara-carousel-story-heading'));
   check((await rows.first().innerText()).includes('Published first'),'Pointer drag changes manual order');
   await rows.first().getByRole('button',{name:'Move down',exact:true}).click();
   await rows.first().locator('summary').click();
   await rows.first().getByLabel('Headline',{exact:true}).fill('Override headline'); await rows.first().getByLabel('Excerpt',{exact:true}).fill('Override excerpt');
   check((await rows.first().locator('.lunara-carousel-copy-preview').innerText()).includes('Override headline'),'Copy preview updates while typing');
   await page.evaluate(()=>{window.wp={media:()=>{let callback;return{on:(event,fn)=>{callback=fn;},state:()=>({get:()=>({first:()=>({toJSON:()=>({id:42,url:'https://example.test/art.svg?custom=42'})})})}),open:()=>callback()};}};});
   await rows.first().getByRole('button',{name:'Choose image',exact:true}).click();
   check((await rows.first().locator('.lunara-editor-image-stage img').getAttribute('src')).includes('custom=42'),'Chosen image is visible immediately');
   await rows.first().getByRole('button',{name:/Image framing:/}).press('ArrowRight');
   check(await rows.first().getByLabel('Horizontal focal point',{exact:true}).inputValue()==='55','Arrow keys move visible focal point');
   await rows.first().getByLabel('Image fit',{exact:true}).selectOption('full');
   check(await rows.first().getByLabel('Zoom',{exact:true}).isDisabled(),'Full image fit disables irrelevant crop controls');
   await rows.first().getByLabel('Image fit',{exact:true}).selectOption('cover');
   await page.locator('[data-carousel-field="interval"]').fill('11');
   previewBarrier=barrier(); await page.locator('[data-action="preview"]').click();
   await page.waitForFunction(()=>document.querySelector('.lunara-site-studio-inspector').getAttribute('aria-busy')==='true');
   check(await rows.first().getByLabel('Headline',{exact:true}).isDisabled() && await page.locator('[data-action="save"]').isDisabled(),'Shared request freezes carousel fields and Apply');
   previewBarrier.release();previewBarrier=null;await page.waitForFunction(()=>document.querySelector('[data-lunara-site-studio]').dataset.workspaceState==='preview-current');
   check((await page.locator('iframe').getAttribute('src')).includes('lunara_site_studio_instance') && saves===0,'Private preview binds instance without saving');
   await rows.first().getByLabel('Headline',{exact:true}).fill('Override headline revised');
   check(await page.locator('[data-lunara-site-studio]').getAttribute('data-workspace-state')==='preview-stale','Editing after preview marks it stale');
   await page.getByRole('button',{name:'Mobile',exact:true}).click();
   check(await page.locator('iframe').getAttribute('width')==='390','Shared mobile preview width');
   const geometry=await page.waitForFunction(expected=>{
    const frame=document.querySelector('.lunara-editor-image-stage'), ratio=frame.style.aspectRatio.split('/').map(Number);
    return Math.abs(ratio[0]/ratio[1]-expected)<0.001;
   },kind==='hero'?390/420:360/225);
   check(await geometry.jsonValue(),'Image framing follows the destination preview geometry');
   failure=true;await page.locator('[data-action="save"]').click();await page.waitForFunction(()=>document.querySelector('[data-workspace-status]').textContent.includes('Simulated'));
   check(await rows.first().getByLabel('Headline',{exact:true}).inputValue()==='Override headline revised','Failed save preserves candidate');
   failure=false;await page.locator('[data-action="save"]').click();await page.waitForFunction(()=>document.querySelector('[data-lunara-site-studio]').dataset.workspaceState==='live-saved');
   check(submitted.slides.map(slide=>slide.post_id).join(',')==='20,10' && submitted.slides[0].image_id===42 && submitted.slides[0].focal_x===55 && submitted.slides[0].headline==='Override headline revised' && submitted.interval===11,'Apply submits ordered independent presentation');
   check(!(await page.locator('[data-carousel-adoption-notice]').isVisible()),'Apply removes migration notice');
   await page.reload();await page.waitForSelector('[data-lunara-site-studio-ready="true"]');await rows.first().locator('summary').click();
   check(await rows.first().getByLabel('Headline',{exact:true}).inputValue()==='Override headline revised','Saved settings survive reload');
   await page.evaluate(()=>window.confirm=()=>true);await page.locator('[data-action="preview"]').click();await page.waitForFunction(()=>document.querySelector('[data-lunara-site-studio]').dataset.workspaceState==='preview-current');
   await page.locator('[data-action="save"]').click();await page.waitForFunction(()=>document.querySelector('[data-lunara-site-studio]').dataset.workspaceState==='live-saved');
   await page.locator('[data-revision-summary]').click();await page.locator('[data-action="restore"]').first().click();await page.waitForFunction(()=>document.querySelector('[data-lunara-site-studio]').dataset.workspaceState==='restored');
   if(!(await rows.first().locator('details').evaluate(el=>el.open))){await rows.first().locator('summary').click();}
   await page.waitForFunction(()=>document.querySelector('.lunara-editor-image-stage img').src.includes('restored=77'));
   check(restores===1 && await rows.first().getByLabel('Headline',{exact:true}).inputValue()==='Restored headline','Restore refreshes image and text through common workflow');
   await rows.first().getByRole('button',{name:'Use source image',exact:true}).click();
   check(await rows.first().locator('.lunara-editor-image-empty').isVisible(),'Removing an override returns to the source placeholder');
   await page.locator('[data-action="discard"]').click();
   if(!(await rows.first().locator('details').evaluate(el=>el.open))){await rows.first().locator('summary').click();}
   await page.evaluate(()=>{window.wp={media:()=>{return{on:(event,fn)=>{window.pendingMedia=fn;},state:()=>({get:()=>({first:()=>({toJSON:()=>({id:999,url:'https://example.test/art.svg?stale=999'})})})}),open:()=>{}};}};});
   await rows.first().getByRole('button',{name:'Replace image',exact:true}).click();
   await rows.first().getByLabel('Headline',{exact:true}).fill('Unwanted change'); await page.locator('[data-action="discard"]').click(); await page.evaluate(()=>window.pendingMedia());
   check(!(await rows.first().locator('.lunara-editor-image-stage img').getAttribute('src')).includes('stale=999'),'Discard rejects late Media Library callback');
   await rows.first().getByRole('button',{name:'Remove story',exact:true}).click();check(await page.locator('[data-carousel-empty]').isVisible(),'Empty manual warning');
   searchBarrier=barrier();await page.locator('[data-carousel-search-button]').click();await page.locator('[data-action="discard"]').click();searchBarrier.release();searchBarrier=null;
   check(await rows.count()===1,'Discard retains saved list while late search is in flight');
   metadataFailure=true;await page.locator('[data-carousel-field="mode"]').selectOption('auto');
   await page.waitForFunction(()=>document.querySelector('.lunara-editor-metadata-status').textContent.includes('could not refresh'));
   check(await rows.count()===1,'Metadata failure retains the manual lineup');
   metadataFailure=false;await page.getByRole('button',{name:'Refresh story details',exact:true}).click();
   await page.waitForFunction(()=>document.querySelector('.lunara-editor-metadata-status').textContent==='');
   await page.evaluate(()=>window.confirm=()=>false);await page.getByRole('button',{name:'Use this lineup in Manual',exact:true}).click();
   check(await page.locator('[data-carousel-field="mode"]').inputValue()==='auto' && await rows.count()===1,'Cancelling lineup replacement keeps manual work');
   await page.evaluate(()=>window.confirm=()=>true);await page.getByRole('button',{name:'Use this lineup in Manual',exact:true}).click();
   check(await page.locator('[data-carousel-field="mode"]').inputValue()==='manual' && await rows.count()===2 && (await rows.nth(1).innerText()).includes('Restored headline'),'Explicit adoption copies automatic order while retaining matching overrides');
   await page.locator('[data-action="discard"]').click();
   if (process.env.LUNARA_EDITOR_SCREENSHOT_DIR) {
    fs.mkdirSync(process.env.LUNARA_EDITOR_SCREENSHOT_DIR,{recursive:true});
    if(!(await rows.first().locator('details').evaluate(el=>el.open))){await rows.first().locator('summary').click();}
    await page.screenshot({path:path.join(process.env.LUNARA_EDITOR_SCREENSHOT_DIR,kind+'-desktop.png'),fullPage:true});
   }
   await page.setViewportSize({width:390,height:844});
   check(await page.locator('[data-lunara-site-studio]').evaluate(el=>el.getBoundingClientRect().right<=window.innerWidth+1),'Editor fits mobile viewport');
   check(await page.locator('[data-action="save"]').evaluate(el=>el.getBoundingClientRect().height>=44),'Apply remains a usable touch target');
   if (process.env.LUNARA_EDITOR_SCREENSHOT_DIR) { await page.screenshot({path:path.join(process.env.LUNARA_EDITOR_SCREENSHOT_DIR,kind+'-mobile.png'),fullPage:true}); }
   check(errors.length===0,'No browser errors: '+errors.join('; '));
   await page.close();
  }
 } finally { await browser.close(); }
 console.log(`PASS ${checks} shared carousel editor contracts`);
})().catch(error=>{console.error(error);process.exit(1);});
