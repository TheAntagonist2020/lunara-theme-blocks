'use strict';
const fs=require('fs'),path=require('path'),{fixture}=require('./site-studio-browser-fixture');
let chromium;try{({chromium}=require('playwright'));}catch(error){({chromium}=require('playwright-core'));}
let checks=0;
function assert(value,label,evidence){if(!value)throw new Error(label+(evidence?'\n'+JSON.stringify(evidence,null,2):''));checks++;}
function equal(actual,expected,label){assert(JSON.stringify(actual)===JSON.stringify(expected),label,{actual,expected});}
const clone=value=>JSON.parse(JSON.stringify(value));
function barrier(){let release,enter;return{entered:new Promise(resolve=>{enter=resolve;}),held:new Promise(resolve=>{release=resolve;}),wait(){enter();return this.held;},release(){release();}};}
const htmls={};
function html(surface){return htmls[surface]||(htmls[surface]=fixture(surface));}
function initial(surface){return JSON.parse(html(surface).match(/<script[^>]+id="lunara-site-studio-state"[^>]*>([\s\S]*?)<\/script>/)[1]);}
function replaceState(markup,state){return markup.replace(/(<script[^>]+id="lunara-site-studio-state"[^>]*>)[\s\S]*?(<\/script>)/,(_,open,close)=>open+JSON.stringify(state)+close);}
async function settled(page){await page.waitForSelector('[data-lunara-site-studio-ready="true"]');await page.waitForFunction(()=>document.querySelector('.lunara-site-studio-inspector').getAttribute('aria-busy')!=='true'&&![...document.querySelectorAll('[data-archive-media-status]')].some(node=>node.textContent.includes('Loading')));}
async function open(browser,surface,width,start){
 const page=await browser.newPage({viewport:{width,height:1100}}),state={saved:clone(start||initial(surface)),original:clone(start||initial(surface)),requests:[],holdMedia:null,holdPreview:null,fail:false,bad:false,error:null,imageRead:0};
 page.setDefaultTimeout(6000);page.on('dialog',dialog=>dialog.accept());
 await page.addInitScript(()=>{window.mediaFrames=[];window.wp={media:()=>{const callbacks={},frame={on:(name,fn)=>{callbacks[name]=fn;},open:()=>{},state:()=>({get:()=>({first:()=>({toJSON:()=>frame.item})})}),select:item=>{frame.item=item;callbacks.select();}};window.mediaFrames.push(frame);return frame;}};});
 await page.route('https://example.test/**',async route=>{
  const req=route.request(),url=new URL(req.url()),action=url.pathname.split('/').pop();
  if(url.pathname==='/wp-admin/admin.php')return route.fulfill({contentType:'text/html',body:replaceState(html(surface),state.saved).replace('</head>','<style>body{font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}button,input,select,textarea{font:inherit}</style></head>')});
  if(url.pathname.endsWith('.svg'))return route.fulfill({contentType:'image/svg+xml',body:'<svg xmlns="http://www.w3.org/2000/svg" width="1920" height="1080"><rect width="1920" height="1080" fill="#173648"/><circle cx="1200" cy="500" r="300" fill="#c9a859"/></svg>'});
  if(url.pathname.startsWith('/wp-json/')){
   const body=req.method()==='GET'?null:req.postDataJSON();state.requests.push({action,body,url:url.href,headers:req.headers()});
   if(action==='media'){
    state.imageRead++;const ids=url.searchParams.get('image_ids').split(',').map(Number),payload={images:ids.map(id=>({id,available:id!==999,url:id===999?'':`https://example.test/spotlight-${id}.svg?read=${state.imageRead}`,width:id===999?0:id===998?640:1920,height:id===999?0:id===998?360:1080,default_alt:id===999?'':'Library image '+id}))};
    const hold=state.holdMedia;state.holdMedia=null;if(hold)await hold.wait();
    if(state.fail)return route.fulfill({status:503,json:{message:'Unavailable'}});if(state.bad)payload.images[0].url='javascript:bad';return route.fulfill({json:payload});
   }
   if(action==='items'){const ids=[...new Set([100,...(url.searchParams.get('ids')||'').split(',').filter(Boolean).map(Number)])];return route.fulfill({json:{items:ids.map(id=>({id,title:'Story '+id,available:true,image_url:'',published_date:'September 12, 2026'})),results:[100],lead_id:100,priority_ids:[100],selection_version:Number(url.searchParams.get('selection_version')),warnings:[]}});}
   if(action==='revisions')return route.fulfill({json:{revisions:[{id:'original',timestamp:'2026-09-12',action:'save'}]}});
   if(action==='restore'){state.saved=clone(state.original);return route.fulfill({json:{state:state.saved,safety_revision_id:'safety',timestamp:'2026-09-12'}});}
   if(action==='preview'||action==='save'){
    if(state.error)return route.fulfill({status:422,json:{message:'Correct the selected field.',fields:{[state.error]:'Please correct this artwork setting.'}}});
    if(action==='preview'){const hold=state.holdPreview;state.holdPreview=null;if(hold)await hold.wait();return route.fulfill({json:{url:`https://example.test/${surface==='reviews-archive'?'reviews':'journal'}/?lunara_${surface==='reviews-archive'?'reviews':'journal'}_preview=123e4567-e89b-42d3-a456-426614174111`}});}
    state.saved=clone(body.state);return route.fulfill({json:{state:state.saved,changed_sections:[],revision_id:'saved',timestamp:'2026-09-12'}});
   }
   return route.fulfill({json:{state:state.saved}});
  }
  return route.fulfill({contentType:'text/html',body:'<!doctype html><html><body><h1>Private archive preview</h1></body></html>'});
 });
 await page.goto(`https://example.test/wp-admin/admin.php?page=lunara-site-studio&surface=${surface}`);await settled(page);await page.addStyleTag({content:'body{font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}button,input,select,textarea{font:inherit}'});
 for(const group of ['gallery','retention'])await page.locator(`[data-section="${group}"] > summary`).click();
 return{page,state};
}
const rows=(page,group)=>page.locator(`[data-archive-media-list="${group}"] > li`);
const field=(row,name)=>row.locator(`[data-archive-media-field="${name}"]`);
async function select(page,id,index=-1){await page.evaluate(({id,index})=>{const frames=window.mediaFrames;frames[index<0?frames.length-1:index].select({id,url:'https://example.test/fullsize.svg',alt:'Library alt'});},{id,index});await settled(page);}
async function add(page,id){await page.locator('[data-archive-gallery-add]').click();await select(page,id);}
async function expand(row){if(!(await row.locator('details').evaluate(node=>node.open)))await row.locator('summary').click();}
async function submit(page,state,action='preview'){await page.locator(`[data-action="${action}"]`).click();await settled(page);return state.requests.filter(entry=>entry.action===action).at(-1).body.state;}
async function provenance(row){for(const [key,value]of Object.entries({alt:'A frame from the film',caption:'A cinematic view',credit:'Studio press image',source:'Studio',source_url:'https://example.test/source'}))await field(row,key).fill(value);}
(async()=>{
 assert(process.env.LUNARA_BROWSER_EXECUTABLE&&fs.existsSync(process.env.LUNARA_BROWSER_EXECUTABLE),'A real Chromium executable is required.');
 const browser=await chromium.launch({headless:true,executablePath:process.env.LUNARA_BROWSER_EXECUTABLE});
 try{
  for(const surface of ['reviews-archive','journal-archive'])for(const width of [1440,390]){
   const{page,state}=await open(browser,surface,width),journal=surface==='journal-archive';
   equal(await rows(page,'retention').count(),3,'Exactly three continuation cards are editable');equal(await page.locator('[data-archive-media-group] [data-field-path]').count(),0,'Media uses the shared candidate lifecycle without competing generic bindings');
   let gallery=rows(page,'gallery').first();await expand(gallery);
   equal(await gallery.locator('[aria-label="Image fit"]:visible').count(),0,'Unsupported image fit is hidden');equal(await gallery.locator('[data-editor-field="zoom"]').count(),0,'Unsupported zoom is absent');
   equal(await page.locator('[data-archive-media-heading="gallery"] [data-archive-media-field="title"]').getAttribute('maxlength'),'140','Gallery heading uses canonical limit');equal(await page.locator('[data-archive-media-heading="gallery"] [data-archive-media-field="copy"]').getAttribute('maxlength'),'500','Gallery copy uses canonical limit');
   assert((await gallery.locator('.lunara-editor-image img').getAttribute('src')).includes('spotlight-'),'Image preview uses the public derivative');
   await add(page,71);gallery=rows(page,'gallery').last();await provenance(gallery);
   await gallery.locator('[data-editor-field="focal_x"]').fill('75');await gallery.locator('[data-editor-field="focal_x"]').dispatchEvent('input');
   await gallery.locator('.lunara-editor-image-stage').press('ArrowDown');
   let sent=await submit(page,state);equal(sent.gallery.items.at(-1).focal_x,75,'Horizontal focal point enters Preview state');equal(sent.gallery.items.at(-1).focal_y,55,'Keyboard framing changes vertical focal point');equal(sent.selection_version,0,'Artwork changes retain legacy selection semantics');equal(state.saved.gallery,state.original.gallery,'Preview does not save artwork');
   gallery=rows(page,'gallery').last();await expand(gallery);await gallery.locator('[data-editor-move="earlier"]').press('Enter');await settled(page);
   sent=await submit(page,state);equal(sent.gallery.items.slice().sort((a,b)=>a.order-b.order).map(item=>item.attachment_id),[71,state.original.gallery.items[0].attachment_id],'Keyboard move reorders gallery');
   const first=rows(page,'retention').first();await expand(first);await field(first,'label').fill('Read the latest coverage');await field(first,'destination').selectOption('custom');await field(first,'url').fill('https://example.test/selected-destination');await field(first,'visible').uncheck();
   if(journal){await field(first,'title').fill('A considered next step');await field(first,'copy').fill('Explore the newest published criticism and reporting.');}
   await first.locator('.lunara-editor-image .lunara-editor-actions button').first().click();await select(page,72);
   await first.locator('.lunara-editor-image .lunara-editor-actions button').nth(1).click();
   sent=await submit(page,state);equal(sent.retention[0].image_id,0,'Removing continuation artwork keeps the card');equal(sent.retention[0].image_credit,'Credit','Removing artwork retains attribution for later reuse');equal(sent.retention[0].visible,false,'Hidden card setting is preserved');equal(sent.retention[0].url,'https://example.test/selected-destination','Custom destination is submitted');
   await rows(page,'retention').first().evaluate(node=>node.dispatchEvent(new DragEvent('dragstart',{bubbles:true,cancelable:true,dataTransfer:new DataTransfer()})));
   await rows(page,'retention').last().evaluate(node=>node.dispatchEvent(new DragEvent('drop',{bubbles:true,cancelable:true,dataTransfer:new DataTransfer()})));await settled(page);
   sent=await submit(page,state,'save');equal(sent.retention[0].order,3,'Dragging changes placement order while preserving canonical card identity');equal(state.saved.gallery.items.length,2,'Apply persists gallery');
   await page.reload();await settled(page);for(const group of ['gallery','retention'])await page.locator(`[data-section="${group}"] > summary`).click();equal(await rows(page,'gallery').count(),2,'Reload restores saved gallery');
   await add(page,73);await page.locator('[data-action="discard"]').click();await settled(page);equal(await rows(page,'gallery').count(),2,'Discard removes candidate-only image');
   await page.locator('[data-revision-history] > summary').click();await page.locator('[data-action="restore"]').first().click();await settled(page);equal(await rows(page,'gallery').count(),1,'History restores original gallery');
   await expand(rows(page,'gallery').first());await expand(rows(page,'retention').first());
   if(process.env.LUNARA_ARCHIVE_MEDIA_ARTIFACT_DIR){fs.mkdirSync(process.env.LUNARA_ARCHIVE_MEDIA_ARTIFACT_DIR,{recursive:true});for(const group of ['gallery','retention'])await page.locator(`[data-section="${group}"]`).screenshot({path:path.join(process.env.LUNARA_ARCHIVE_MEDIA_ARTIFACT_DIR,`${surface}-${width}-${group}.png`)});}
   const metrics=await page.evaluate(()=>({width:document.documentElement.clientWidth,scroll:document.documentElement.scrollWidth,rows:[...document.querySelectorAll('[data-archive-media-row]')].map(row=>{const rect=row.getBoundingClientRect();return [...row.querySelectorAll('input,button,textarea,select')].filter(node=>node.getClientRects().length).every(node=>{const r=node.getBoundingClientRect();return r.left>=rect.left-1&&r.right<=rect.right+1;});})}));assert(metrics.scroll<=metrics.width&&metrics.rows.every(Boolean),'Editor image and copy controls fit the viewport',metrics);
   assert(state.requests.filter(entry=>entry.action==='media').every(entry=>entry.headers['x-wp-nonce']==='fixture-rest-nonce'&&new URL(entry.url).searchParams.get('image_ids').split(',').length<=15),'Media requests use private nonce and bounded IDs');
   await page.close();process.stdout.write(`${surface} ${width}: artwork workflow passed\n`);
  }
  const{page,state}=await open(browser,'reviews-archive',390);
  await page.locator('[data-archive-gallery-add]').click();const stalePicker=await page.evaluate(()=>window.mediaFrames.length-1);
  await page.locator('[data-archive-media-heading="gallery"] [data-archive-media-field="title"]').fill('Unsaved heading');await page.locator('[data-action="discard"]').click();await settled(page);await select(page,71,stalePicker);equal(await rows(page,'gallery').count(),1,'Late picker selection after Discard is ignored');
  await expand(rows(page,'gallery').first());await rows(page,'gallery').first().locator('.lunara-editor-image .lunara-editor-actions button').first().click();const historyPicker=await page.evaluate(()=>window.mediaFrames.length-1);
  await page.locator('[data-revision-history] > summary').click();await page.locator('[data-action="restore"]').first().click();await settled(page);await select(page,71,historyPicker);equal(await rows(page,'gallery').first().getAttribute('data-archive-image-id'),'51','Late replacement picker after History is ignored');
  await add(page,998);assert((await rows(page,'gallery').last().locator('.lunara-editor-image-source').innerText()).includes('may look soft'),'Small public derivative shows a nonblocking resolution warning');await rows(page,'gallery').last().locator('[data-archive-gallery-remove]').click();await settled(page);
  await add(page,51);equal(await rows(page,'gallery').count(),1,'Duplicate gallery image selection is rejected without rewriting the row');
  await add(page,999);assert((await page.locator('[data-archive-media-status="gallery"]').innerText()).includes('unavailable'),'Unavailable image is visible and retained');assert((await rows(page,'gallery').last().locator('.lunara-editor-image-source').innerText()).includes('before Preview or Apply'),'Unavailable image gives concrete recovery');
  const missingFrame=await rows(page,'gallery').last().locator('.lunara-editor-image-stage').boundingBox();assert(Math.abs(missingFrame.width/missingFrame.height-16/9)<.02,'Unavailable artwork retains the same 16:9 image frame',missingFrame);
  await rows(page,'gallery').last().locator('[data-archive-gallery-remove]').click();await settled(page);
  state.fail=true;await page.locator('[data-archive-media-heading="gallery"] [data-archive-media-field="title"]').fill('Keep my edits');await submit(page,state);assert(await page.locator('[data-archive-media-retry="gallery"]').isVisible(),'Failed image details expose retry');equal(await page.locator('[data-archive-media-heading="gallery"] [data-archive-media-field="title"]').inputValue(),'Keep my edits','Metadata failure retains typed copy');
  state.fail=false;await page.locator('[data-archive-media-retry="gallery"]').click();await settled(page);state.bad=true;await submit(page,state);assert(await page.locator('[data-archive-media-retry="gallery"]').isVisible(),'Unsafe metadata fails closed');state.bad=false;await page.locator('[data-archive-media-retry="gallery"]').click();await settled(page);
  for(const error of ['gallery','retention','labels.retention_title']){state.error=error;await submit(page,state);assert(await page.locator(`[data-error-key="${error}"]`).evaluate(node=>node===document.activeElement),'Validation error opens its section and focuses '+error);}state.error=null;
  const hold=barrier();state.holdPreview=hold;await page.locator('[data-action="preview"]').click();await hold.entered;
  equal(await page.locator('[data-archive-media-controls="gallery"]').evaluate(node=>node.disabled),true,'Pending preview disables artwork controls');
  const beforeFrames=await page.evaluate(()=>window.mediaFrames.length);await page.locator('[data-archive-gallery-add]').evaluate(node=>{node.disabled=false;node.dispatchEvent(new MouseEvent('click',{bubbles:true}));});equal(await page.evaluate(()=>window.mediaFrames.length),beforeFrames,'Bypassing native disabled state cannot open a busy picker');
  await page.locator('[data-archive-media-heading="gallery"] [data-archive-media-field="title"]').evaluate(node=>{node.value='Bypassed';node.dispatchEvent(new Event('input',{bubbles:true}));});hold.release();await settled(page);let sent=await submit(page,state);equal(sent.gallery.title,'Keep my edits','Bypassed busy input cannot mutate the candidate');
  const held=barrier();state.holdMedia=held;await page.locator('[data-archive-media-retry="gallery"]').evaluate(node=>node.dispatchEvent(new MouseEvent('click',{bubbles:true})));await held.entered;
  await page.locator('[data-action="discard"]').click();await settled(page);const freshRead=state.imageRead;held.release();await page.waitForTimeout(60);equal(await rows(page,'gallery').count(),1,'Late image response cannot restore discarded selections');assert((await rows(page,'gallery').first().locator('.lunara-editor-image img').getAttribute('src')).endsWith('read='+freshRead),'Late metadata cannot overwrite the fresh image generation');
  await expand(rows(page,'gallery').first());const focusRead=barrier();state.holdMedia=focusRead;await page.locator('[data-archive-media-retry="gallery"]').evaluate(node=>node.dispatchEvent(new MouseEvent('click',{bubbles:true})));await focusRead.entered;await field(rows(page,'gallery').first(),'caption').focus();focusRead.release();await settled(page);assert(await field(rows(page,'gallery').first(),'caption').evaluate(node=>node===document.activeElement),'Image refresh preserves the focused caption field');
  await rows(page,'gallery').first().locator('[data-archive-gallery-remove]').click();await settled(page);assert((await page.locator('[data-archive-media-status="gallery"]').innerText()).includes('stays hidden'),'Empty gallery explicitly explains hidden public section');sent=await submit(page,state,'save');equal(sent.gallery.items,[],'Empty gallery can be saved');
  for(let id=101;id<=112;id++)await add(page,id);equal(await rows(page,'gallery').count(),12,'Gallery accepts twelve images');assert(await page.locator('[data-archive-gallery-add]').isDisabled(),'Gallery limit disables Add image');
  await page.close();
 }finally{await browser.close();}
 process.stdout.write(`archive-media browser: ${checks} checks passed\n`);
})().catch(error=>{process.stderr.write((error.stack||String(error))+'\n');process.exitCode=1;});
