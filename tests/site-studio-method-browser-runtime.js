'use strict';
const fs=require('fs'),path=require('path'),{spawnSync}=require('child_process');
let chromium;try{({chromium}=require('playwright'));}catch(error){({chromium}=require('playwright-core'));}
let checks=0;
function check(value,message){checks++;if(!value){throw new Error(message);}}
function barrier(){let entered,release;const entry=new Promise(r=>{entered=r;}),wait=new Promise(r=>{release=r;});return{entry,wait,entered,release};}
(async()=>{
 const executablePath=process.env.LUNARA_BROWSER_EXECUTABLE||['C:/Program Files/Google/Chrome/Application/chrome.exe','C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe','/usr/bin/chromium','/usr/bin/chromium-browser','/usr/bin/google-chrome'].find(fs.existsSync);
 const browser=await chromium.launch({headless:true,executablePath});
 const root=path.join(__dirname,'..');
 const rendered=spawnSync('php',[path.join(__dirname,'site-studio-runtime.php'),'--fixture=lunara-method'],{encoding:'utf8'});check(rendered.status===0,'Real PHP Method inspector renders.');
 const scripts=['lunara-editor-controls.js','lunara-site-studio-method.js','lunara-site-studio.js'].map(f=>fs.readFileSync(path.join(root,'assets/js',f),'utf8')).join('\n');
 const css=['lunara-site-studio.css','lunara-editor-controls.css'].map(f=>fs.readFileSync(path.join(root,'assets/css',f),'utf8')).join('\n');
 const publicCss=fs.readFileSync(path.join(root,'assets/css/lunara-review-components.css'),'utf8');
 const html=rendered.stdout.replace('</head>','<style>body{margin:0;font:14px/1.5 system-ui}button,input,select,textarea{font:inherit}'+css+'</style></head>').replace('</body>','<script>'+scripts+'</script></body>');
 const page=await browser.newPage({viewport:{width:1440,height:1000}});page.setDefaultTimeout(10000);
 const errors=[];page.on('pageerror',e=>errors.push(e.message));
 let saved=null,submitted=null,previewed=null,failed=false,metadataHold=null,searchHold=null,previewHold=null,artVersion=1;
 function item(id){return{id,available:id!==999,title:id===999?'Unavailable Review':'Published Review '+id,date_label:'Sep 10, 2026',image_url:'https://example.test/art.svg?source='+artVersion,image_source:'Review hero artwork'};}
 await page.route('https://example.test/**',async route=>{
  const url=new URL(route.request().url()),endpoint=url.searchParams.get('rest_route')||url.pathname;
  if(url.pathname==='/wp-admin/admin.php'){let body=html;if(saved){body=body.replace(/(<script[^>]+id="lunara-site-studio-state"[^>]*>)[\s\S]*?(<\/script>)/,(_m,a,b)=>a+JSON.stringify(saved)+b);}return route.fulfill({contentType:'text/html',body});}
  if(url.pathname==='/art.svg'){return route.fulfill({contentType:'image/svg+xml',body:fs.readFileSync(path.join(__dirname,'fixtures/home-carousel-art.svg'),'utf8')});}
  if(endpoint.endsWith('/metadata')){check(route.request().headers()['x-wp-nonce']==='fixture-rest-nonce','Metadata sends the REST nonce.');const id=url.searchParams.get('mode')==='automatic'?7:Number(url.searchParams.get('review_id'));const payload={item:id?item(id):null,image_url:['44','77'].includes(url.searchParams.get('image_id'))?'https://example.test/art.svg?custom='+url.searchParams.get('image_id')+'&version='+artVersion:''};const hold=metadataHold;metadataHold=null;if(hold){hold.entered();await hold.wait;}return route.fulfill({json:payload});}
  if(endpoint.endsWith('/search')){check(route.request().headers()['x-wp-nonce']==='fixture-rest-nonce','Search sends the REST nonce.');const hold=searchHold;searchHold=null;if(hold){hold.entered();await hold.wait;}return route.fulfill({json:{items:[item(7),item(107)]}});}
  if(endpoint.endsWith('/preview')){previewed=route.request().postDataJSON().state;const hold=previewHold;previewHold=null;if(hold){hold.entered();await hold.wait;}return route.fulfill({json:{url:'https://example.test/?lunara_method_preview=123e4567-e89b-42d3-a456-426614174111'}});}
  if(endpoint.endsWith('/save')){submitted=route.request().postDataJSON().state;if(failed){return route.fulfill({status:422,json:{message:'Simulated rejected Method settings',fields:{backdrop:'Check backdrop.'}}});}saved=submitted;return route.fulfill({json:{state:saved,revision_id:'revision-safe',timestamp:'2026-09-10 12:00:00',changed_sections:['backdrop']}});}
  if(endpoint.endsWith('/restore')){artVersion++;saved={...saved,title:'Restored Method',backdrop_id:44,backdrop:{...saved.backdrop,focal_x:32}};return route.fulfill({json:{state:saved,safety_revision_id:'safety-safe',timestamp:'2026-09-10 12:05:00'}});}
  if(endpoint.endsWith('/revisions')){return route.fulfill({json:{revisions:[{id:'revision-safe',timestamp:'2026-09-10 12:00:00',action:'save'}]}});}
  return route.fulfill({contentType:'text/html',body:'<style>body{margin:0}.lunara-pairing-desk-section{height:600px}'+publicCss+'</style><section class="lunara-pairing-desk-section"><div class="lunara-pairing-desk-backdrop"></div></section>'});
 });
 async function settled(){await page.waitForFunction(()=>document.querySelector('[data-lunara-site-studio]').dataset.lunaraSiteStudioReady==='true');await page.locator('details[data-section="fine-tune"]').evaluate(n=>{n.open=true;});}
 async function field(key,value){await page.locator('[data-editor-field="'+key+'"]').evaluate((n,v)=>{n.value=v;n.dispatchEvent(new Event('input',{bubbles:true}));},value);}
 async function preview(){const response=page.waitForResponse(r=>r.url().endsWith('/preview'));await page.click('[data-action="preview"]');await response;await page.waitForFunction(()=>document.querySelector('[data-lunara-site-studio]').dataset.workspaceState==='preview-current'&&document.querySelector('.lunara-site-studio-inspector').getAttribute('aria-busy')==='false');}
 async function installMedia(){await page.evaluate(()=>{window.confirm=()=>true;window.__frames=[];window.wp={media:()=>{const f={on:(event,callback)=>{f.select=callback;},open:()=>{},state:()=>({get:()=>({first:()=>({toJSON:()=>({id:77,url:'https://example.test/art.svg?media=77'})})})})};window.__frames.push(f);return f;}};});}
 try{
  await page.goto('https://example.test/wp-admin/admin.php?page=lunara-site-studio&surface=lunara-method');await settled();await installMedia();
  await page.waitForFunction(()=>document.querySelector('[data-method-selected]').textContent.includes('Published Review 7'));
  check(await page.locator('[data-method-selected]').textContent().then(t=>t.includes('Sep 10, 2026')),'Selected item displays its published title and date.');
  check(await page.locator('.lunara-editor-image-stage').evaluate(n=>n.style.aspectRatio)==='1440 / 600','Image guide samples the actual desktop destination frame.');
  await page.selectOption('[data-method-mode]','manual');await page.waitForFunction(()=>document.querySelector('[data-method-selected]').textContent.includes('No Manual Review'));
  await page.fill('[data-method-search]','Published');await page.click('[data-method-search-button]');await page.getByRole('button',{name:'Select Published Review 107',exact:true}).click();
  await page.waitForFunction(()=>document.querySelector('[data-method-selected]').textContent.includes('Published Review 107'));
  await page.selectOption('[data-method-mode]','automatic');await preview();check(previewed.review_mode==='automatic'&&previewed.review_id===107,'Automatic retains the selected manual ID in private preview payload.');
  await page.selectOption('[data-method-mode]','manual');await page.waitForFunction(()=>document.querySelector('[data-method-selected]').textContent.includes('Published Review 107'));
  await page.getByRole('button',{name:'Replace image',exact:true}).click();await page.evaluate(()=>window.__frames.at(-1).select());
  await page.waitForFunction(()=>document.querySelector('.lunara-editor-image-stage img').src.includes('custom=77'));
  await page.getByRole('button',{name:'Remove image',exact:true}).click();check(await page.locator('.lunara-editor-image-empty').textContent()==='Image hidden for this placement','Remove image explicitly hides artwork.');
  await page.getByRole('button',{name:'Use source image',exact:true}).click();await page.waitForFunction(()=>document.querySelector('.lunara-editor-image-stage img').src.includes('source=1'));
  check(await page.locator('.lunara-editor-image-source').textContent()==='Source: Review hero artwork','Source reset displays inherited canonical image provenance.');
  await page.selectOption('.lunara-editor-image select','full');check(await page.locator('[data-editor-field="zoom"]').isDisabled()&&await page.locator('.lunara-editor-image-stage img').evaluate(n=>n.style.objectFit==='contain'&&n.style.transform==='none'&&n.style.inset==='0%'),'Show full image uses contain without zoom or bleed.');await page.selectOption('.lunara-editor-image select','cover');
  await field('focal_x',17);await field('focal_y',81);await field('zoom',109);await preview();
  check(previewed.backdrop_id===0&&previewed.backdrop.focal_x===17&&previewed.backdrop.focal_y===81&&previewed.backdrop.zoom===109&&!previewed.backdrop.hidden,'Private preview includes the exact shared framing and source reset.');
  await page.click('[data-action="save"]');await page.waitForFunction(()=>document.querySelector('[data-lunara-site-studio]').dataset.workspaceState==='live-saved');
  check(JSON.stringify(submitted)===JSON.stringify(previewed),'Apply and Preview receive identical Method candidates.');
  await page.reload();await settled();await installMedia();check(await page.locator('[data-editor-field="focal_x"]').inputValue()==='17','Reload adopts saved framing.');
  // A picker started before replacement must not alter the restored candidate.
  await page.getByRole('button',{name:'Replace image',exact:true}).click();await field('focal_x',28);await page.click('[data-action="discard"]');await page.evaluate(()=>window.__frames.at(-1).select());await preview();
  check(previewed.backdrop_id===0&&previewed.backdrop.focal_x===17,'Discard rejects late Media Library selection.');
  const heldMetadata=barrier();metadataHold=heldMetadata;await page.selectOption('[data-method-mode]','automatic');await heldMetadata.entry;await page.click('[data-action="discard"]');heldMetadata.release();await page.waitForFunction(()=>document.querySelector('[data-method-selected]').textContent.includes('Published Review 107'));
  check(await page.locator('[data-method-mode]').inputValue()==='manual','Discard rejects old Automatic metadata and refreshes the replacement candidate.');
  const heldSearch=barrier();searchHold=heldSearch;await page.click('[data-method-search-button]');await heldSearch.entry;await page.selectOption('[data-method-mode]','automatic');heldSearch.release();await page.waitForTimeout(70);
  check(await page.locator('[data-method-results] button').count()===0,'Late search results cannot repopulate after mode replacement.');
  const interrupted=barrier();metadataHold=interrupted;await page.selectOption('[data-method-mode]','manual');await interrupted.entry;artVersion=2;await preview();await page.waitForFunction(()=>document.querySelector('.lunara-editor-image-stage img').src.includes('source=2'));interrupted.release();await page.waitForTimeout(70);
  check(await page.locator('.lunara-editor-image-stage img').getAttribute('src').then(url=>url.includes('source=2')),'Preview resumes interrupted metadata reads and rejects their late old artwork.');artVersion=1;
  // Busy transitions cancel picker callbacks and resume interrupted metadata reads.
  await page.getByRole('button',{name:'Replace image',exact:true}).click();const heldPreview=barrier();previewHold=heldPreview;await page.click('[data-action="preview"]');await heldPreview.entry;await page.evaluate(()=>window.__frames.at(-1).select());
  check(await page.locator('[data-method-mode]').isDisabled(),'Preview freezes Method selection controls.');heldPreview.release();await page.waitForFunction(()=>document.querySelector('.lunara-site-studio-inspector').getAttribute('aria-busy')==='false');await preview();check(previewed.backdrop_id===0,'Busy preview rejects a late picker result.');
  await field('focal_x',18);await page.click('[data-action="discard"]');await page.locator('details[data-section="revision-history"]').evaluate(n=>{n.open=true;});await page.click('[data-action="restore"]');await page.waitForFunction(()=>document.querySelector('[data-lunara-site-studio]').dataset.workspaceState==='restored');await page.waitForFunction(()=>document.querySelector('.lunara-editor-image-stage img').src.includes('version=2'));
  check(await page.inputValue('[data-field-path="title"]')==='Restored Method'&&await page.locator('[data-editor-field="focal_x"]').inputValue()==='32','Restore replaces fields, framing and current attachment metadata.');
  failed=true;await page.fill('[data-field-path="title"]','Unsaved candidate');await page.click('[data-action="save"]');await page.waitForFunction(()=>document.querySelector('[data-lunara-site-studio]').dataset.workspaceState==='validation-error');
  check(await page.inputValue('[data-field-path="title"]')==='Unsaved candidate'&&await page.locator('[data-lunara-site-studio]').getAttribute('data-dirty')==='true','Failed Apply preserves the candidate and dirty state.');
  check(await page.locator('#lunara-method-framing-error').isVisible()&&await page.locator('#lunara-method-framing-error').textContent()==='Check backdrop.','Failed Apply exposes framing feedback at its accessible field anchor.');
  await page.setViewportSize({width:390,height:1000});await page.locator('details').evaluateAll(nodes=>nodes.forEach(n=>{n.open=true;}));await page.click('[data-preview-width="mobile"]');
  check(await page.evaluate(()=>document.documentElement.scrollWidth<=document.documentElement.clientWidth+1),'Method controls fit a 390px viewport.');
  check(await page.frameLocator('iframe').locator('.lunara-pairing-desk-backdrop').evaluate(n=>getComputedStyle(n).display)==='none','Public decorative backdrop remains hidden at mobile widths.');
  check((await page.locator('details[data-section="mobile"]').textContent()).includes('820px'),'Mobile controls explain the decorative backdrop hiding rule.');
  saved={...saved,review_mode:'manual',review_id:999,backdrop_id:555};await page.reload();await settled();await page.waitForFunction(()=>document.querySelector('[data-method-selected]').textContent.includes('This Manual Review is unavailable'));
  check(await page.locator('.lunara-editor-image-empty').textContent()==='Display image unavailable','Missing attachments clear stale artwork and expose an empty state.');
  check(!(await page.locator('[data-method-selected]').textContent()).includes('PRIVATE'),'Unavailable retained selections expose a warning without private titles.');
  check(errors.length===0,'No browser runtime errors: '+errors.join(', '));
  console.log('Method browser checks: '+checks+' passed.');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
