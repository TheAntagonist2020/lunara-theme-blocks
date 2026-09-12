'use strict';
const fs = require('fs'), path = require('path');
const { fixture } = require('./site-studio-browser-fixture');
let chromium; try { ({chromium} = require('playwright')); } catch (error) { ({chromium} = require('playwright-core')); }
let checks = 0;
function assert(value, label, evidence) { if (!value) throw new Error(label+(evidence ? '\n'+JSON.stringify(evidence,null,2) : '')); checks++; }
function equal(actual, expected, label) { assert(JSON.stringify(actual) === JSON.stringify(expected),label,{actual,expected}); }
const clone = value => JSON.parse(JSON.stringify(value));
function barrier() { let release, enter; return {entered:new Promise(resolve => { enter=resolve; }),held:new Promise(resolve => { release=resolve; }),wait(){enter();return this.held;},release(){release();}}; }
async function within(promise,label) { let timer; try { return await Promise.race([promise,new Promise((resolve,reject)=>{timer=setTimeout(()=>reject(new Error(label+' did not finish within 6 seconds.')),6000);})]); } finally {clearTimeout(timer);} }
function replaceState(html, state) { return html.replace(/(<script[^>]+id="lunara-site-studio-state"[^>]*>)[\s\S]*?(<\/script>)/,(_,open,close)=>open+JSON.stringify(state)+close); }
const initial = surface => JSON.parse(fixture(surface).match(/<script[^>]+id="lunara-site-studio-state"[^>]*>([\s\S]*?)<\/script>/)[1]);
function metadata(url) {
 const parse = value => (value || '').split(',').filter(Boolean).map(Number);
 const manualId=Number(url.searchParams.get('lead_id')), curated=parse(url.searchParams.get('curated_ids')), requested=parse(url.searchParams.get('ids'));
 const selectionVersion=Number(url.searchParams.get('selection_version')), mode=url.searchParams.get('lead_mode');
 const q=url.searchParams.get('q'), results=q==='none' ? [] : /^\d+$/.test(q) ? [Number(q)].filter(value=>value!==999) : [100,101,102];
 const lead=mode==='manual' ? (manualId===999 ? 0 : manualId) : mode==='shared' ? 103 : 100;
 const priority=[...new Set((lead ? [lead] : []).concat(curated.filter(value=>value!==999)))];
 const all=[...new Set([...requested,...results,...priority])];
 const items=all.map(id=>({id,title:id===999?'Unavailable story':id===101?'An exceptionally long published story headline that still needs to fit inside a narrow editor without squeezing the artwork or overlapping its controls':`Published story ${id}`,available:id!==999,image_url:id===999||id===102?'':'https://example.test/art.svg',published_date:id===999?'':'September 12, 2026'}));
 const warnings=[];
 if(mode==='manual'&&!lead)warnings.push('manual_lead_unavailable');
 if(mode!=='manual'&&manualId===999)warnings.push('retained_lead_unavailable');
 if(curated.includes(999))warnings.push('curated_selection_unavailable');
 if(!curated.length&&url.searchParams.get('lane_mode')==='curated')warnings.push('curated_selection_empty');
 if(mode==='shared')warnings.push('legacy_shared_lead');
 return {items,results,lead_id:lead,priority_ids:priority,selection_version:selectionVersion,warnings};
}
async function ready(page) { await page.waitForSelector('[data-lunara-site-studio-ready="true"]'); await page.waitForFunction(()=>document.querySelector('[data-archive-status]').textContent===''); }
async function settled(page) { await page.waitForFunction(()=>document.querySelector('.lunara-site-studio-inspector').getAttribute('aria-busy')!=='true'&&document.querySelector('[data-archive-status]').textContent===''); }
async function open(browser, surface, width, start) {
 const state={saved:clone(start || initial(surface)), original:clone(start || initial(surface)),requests:[],failMetadata:false,badMetadata:false,holdMetadata:null,holdPreview:null}, page=await browser.newPage({viewport:{width,height:1200}});
 page.setDefaultTimeout(6000); page.on('dialog',dialog=>dialog.accept());
 await page.route('https://example.test/**',async route=>{
  const request=route.request(), url=new URL(request.url()), action=url.pathname.split('/').pop();
  if(url.pathname==='/wp-admin/admin.php')return route.fulfill({contentType:'text/html',body:replaceState(fixture(surface),state.saved)});
  if(url.pathname==='/art.svg')return route.fulfill({contentType:'image/svg+xml',body:'<svg xmlns="http://www.w3.org/2000/svg" width="640" height="400"><rect width="640" height="400" fill="#193851"/><circle cx="400" cy="150" r="100" fill="#d9b76d"/></svg>'});
  if(url.pathname.startsWith('/wp-json/')) {
   const body=request.method()==='GET'?null:request.postDataJSON();state.requests.push({action,body,url:url.href,headers:request.headers()});
   if(action==='items') {
    const payload=metadata(url), hold=state.holdMetadata;state.holdMetadata=null;if(hold)await hold.wait();
    if(state.failMetadata)return route.fulfill({status:503,json:{message:'Unavailable'}});
    if(state.badMetadata)payload.items[0].image_url='javascript:alert(1)';
    return route.fulfill({json:payload});
   }
   if(action==='revisions')return route.fulfill({json:{revisions:[{id:'old-revision',timestamp:'2026-09-12',action:'save'}]}});
   if(action==='restore'){state.saved=clone(state.original);return route.fulfill({json:{state:state.saved,safety_revision_id:'safety',timestamp:'2026-09-12'}});}
   if(action==='preview'||action==='save') {
    if(body.state.selection_version===1&&body.state.lead_mode==='manual'&&(!body.state.lead_id||body.state.lead_id===999))return route.fulfill({status:422,json:{message:'Choose a published Manual lead.',fields:{lead_id:'Choose a published Manual story or Automatic.'}}});
    if(action==='preview'){const hold=state.holdPreview;state.holdPreview=null;if(hold)await hold.wait();return route.fulfill({json:{url:`https://example.test/${surface==='reviews-archive'?'reviews':'journal'}/?lunara_${surface==='reviews-archive'?'reviews':'journal'}_preview=123e4567-e89b-42d3-a456-426614174111`}});}
    state.saved=clone(body.state);return route.fulfill({json:{state:state.saved,changed_sections:[],revision_id:'saved',timestamp:'2026-09-12'}});
   }
   return route.fulfill({json:{state:state.saved}});
  }
  return route.fulfill({contentType:'text/html',body:'<!doctype html><html><body><h1>Private preview fixture</h1></body></html>'});
 });
 await page.goto(`https://example.test/wp-admin/admin.php?page=lunara-site-studio&surface=${surface}`);await ready(page);
 await page.addStyleTag({content:'body{font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}button,input,select,textarea{font:inherit}'});
 await page.locator('[data-section="stories"] > summary').click();
 return {page,state};
}
async function submit(page,state,action='preview') { await page.locator(`[data-action="${action}"]`).click();await settled(page);return state.requests.filter(entry=>entry.action===action).at(-1).body.state; }
async function choose(page,target,id) { await page.locator(`[data-archive-${target}-search]`).fill(String(id));await page.locator(`[data-archive-${target}-search-button]`).click();await settled(page);await page.locator(`[data-archive-${target}-results] button`).first().click();await settled(page); }
const order=page=>page.locator('[data-archive-priority-list] > li').evaluateAll(rows=>rows.map(row=>Number(row.dataset.archiveStoryId)));
(async()=>{
 assert(process.env.LUNARA_BROWSER_EXECUTABLE&&fs.existsSync(process.env.LUNARA_BROWSER_EXECUTABLE),'A real Chromium executable is required.');
 const browser=await chromium.launch({headless:true,executablePath:process.env.LUNARA_BROWSER_EXECUTABLE});
 try {
  for(const surface of ['reviews-archive','journal-archive'])for(const width of [1440,390]) {
   const {page,state}=await open(browser,surface,width);
   assert(await page.locator('[data-archive-legacy]').isVisible(),`${surface}/${width}: legacy selection is clearly named`);
   assert(await page.locator('[data-archive-controls]').evaluate(node=>node.disabled),'Legacy story controls require explicit activation');
   await page.locator('[data-field-path="kicker"]').fill('A new archive introduction');
   let sent=await submit(page,state,'save');equal(sent.selection_version,0,'Content-only Apply preserves old story rules');equal(sent.curated_ids,state.original.curated_ids,'Content-only Apply preserves remembered priorities');
   await page.locator('[data-archive-activate]').click();await settled(page);
   assert(!(await page.locator('[data-archive-controls]').evaluate(node=>node.disabled)),'Activation enables Stories');
   equal(await page.locator('[data-lunara-site-studio]').getAttribute('data-dirty'),'true','Activation creates a dirty candidate');
   await page.locator('[data-archive-lead-mode]').selectOption('manual');await settled(page);
   assert((await page.locator('[data-archive-lead-status]').innerText()).includes('Preview and Apply'),'Empty Manual explains the blocked transaction');
   await page.locator('[data-action="preview"]').click();await page.waitForSelector('[data-error-key="lead_id"][aria-invalid="true"]');
   equal(await page.locator('[data-error-key="lead_id"]').evaluate(node=>node===document.activeElement),true,'Rejected Manual selection gets keyboard focus');
   await choose(page,'lead',101);assert(!(await page.locator('[data-error-key="lead_id"]').getAttribute('aria-invalid')),'Selecting a published lead clears the old error');
   await page.locator('[data-archive-lane-mode]').selectOption('curated');await settled(page);await choose(page,'priority',102);
   const before=await order(page), last=before.at(-1);
   const removeRead=barrier();state.holdMetadata=removeRead;await page.locator('[data-archive-priority-search-button]').click();await within(removeRead.entered,'Metadata refresh for Remove focus');
   await page.locator(`[data-archive-story-id="${last}"] [data-archive-remove]`).focus();removeRead.release();await settled(page);
   assert(await page.locator(`[data-archive-story-id="${last}"] [data-archive-remove]`).evaluate(node=>node===document.activeElement),'Metadata completion preserves the same story Remove action focus');
   await page.locator(`[data-archive-story-id="${last}"] [data-editor-move="earlier"]`).press('Enter');await settled(page);
   const expected=before.slice();expected.splice(expected.length-1,1);expected.splice(expected.length-1,0,last);equal(await order(page),expected,'Keyboard ordering moves exactly one selected story');
   assert(await page.locator(`[data-archive-story-id="${last}"] [data-editor-move]`).evaluateAll(nodes=>nodes.includes(document.activeElement)),'Metadata refresh retains keyboard ordering focus');
   await page.locator('[data-archive-priority-list] li').first().evaluate(node=>node.dispatchEvent(new DragEvent('dragstart',{bubbles:true,cancelable:true,dataTransfer:new DataTransfer()})));
   await page.locator('[data-archive-priority-list] li').last().evaluate(node=>node.dispatchEvent(new DragEvent('drop',{bubbles:true,cancelable:true,dataTransfer:new DataTransfer()})));await settled(page);
   const reordered=expected.slice(1).concat(expected[0]);equal(await order(page),reordered,'Shared drag ordering preserves the selected IDs');
   await page.locator('[data-archive-lead-mode]').selectOption('automatic');await page.locator('[data-archive-lane-mode]').selectOption('query');await settled(page);
   sent=await submit(page,state);equal(sent.lead_id,101,'Automatic retains the Manual lead');equal(sent.curated_ids,reordered,'Automatic retains Manual priority order');equal(sent.selection_version,1,'Preview submits adopted selection rules');
   assert(state.requests.filter(entry=>entry.action==='items').every(entry=>entry.headers['x-wp-nonce']==='fixture-rest-nonce'),'Story reads use the shared private nonce boundary');
   if(surface==='journal-archive') {await page.locator('[data-archive-lead-mode]').selectOption('shared');await settled(page);assert((await page.locator('[data-archive-lead-status]').innerText()).includes('does not follow the homepage Journal carousel'),'Journal Shared is accurately labeled');await page.locator('[data-archive-lead-mode]').selectOption('automatic');}
   await page.locator('[data-archive-lane-mode]').selectOption('curated');await page.locator('[data-archive-lead-mode]').selectOption('manual');await settled(page);
   if(process.env.LUNARA_ARCHIVE_SCREENSHOTS){fs.mkdirSync(process.env.LUNARA_ARCHIVE_SCREENSHOTS,{recursive:true});await page.locator('[data-section="stories"]').screenshot({path:path.join(process.env.LUNARA_ARCHIVE_SCREENSHOTS,`${surface}-${width}.png`)});}
   const metrics=await page.evaluate(()=>({width:document.documentElement.clientWidth,scroll:document.documentElement.scrollWidth,rows:[...document.querySelectorAll('[data-archive-priority-list] li')].map(row=>{const rect=row.getBoundingClientRect();return [...row.querySelectorAll('img,button,strong')].every(node=>{const r=node.getBoundingClientRect();return r.left>=rect.left-1&&r.right<=rect.right+1;});})}));
   assert(metrics.scroll<=metrics.width&&metrics.rows.every(Boolean),'Narrow and wide editors contain artwork, long headlines and controls',metrics);
   sent=await submit(page,state,'save');equal(state.saved.curated_ids,reordered,'Apply persists priority order');
   await page.reload();await ready(page);await page.locator('[data-section="stories"] > summary').click();assert(!(await page.locator('[data-archive-legacy]').isVisible()),'Reload preserves adopted controls');equal(await order(page),reordered,'Reload preserves priority order');
   await page.locator('[data-archive-lane-mode]').selectOption('query');await settled(page);await page.locator('[data-action="discard"]').click();await settled(page);equal(await page.locator('[data-archive-lane-mode]').inputValue(),'curated','Discard restores saved ordering mode');
   await page.locator('[data-revision-history] > summary').click();await page.locator('[data-action="restore"]').first().click();await settled(page);assert(await page.locator('[data-archive-legacy]').isVisible(),'Restoring a legacy revision restores explicit adoption state');
   await page.close();process.stdout.write(`${surface} ${width}: selection workflow passed\n`);
  }
  for(const surface of ['reviews-archive','journal-archive']) {
   const start=initial(surface);Object.assign(start,{selection_version:1,lead_mode:'automatic',lead_id:999,lane_mode:'curated',curated_ids:[999]});
   const {page,state}=await open(browser,surface,390,start);
   assert((await page.locator('[data-archive-lead-status]').innerText()).includes('remembered Manual story is unavailable'),'Unavailable inactive Manual lead is explained');
   assert((await page.locator('[data-archive-priority-list]').innerText()).includes('Unavailable or unpublished'),'Unavailable priority remains visible and removable');
   await page.locator('[data-archive-remove]').click();await settled(page);assert((await page.locator('[data-archive-priority-status]').innerText()).includes('complete archive remains'),'Zero priorities keep the full archive');
   let sent=await submit(page,state,'save');equal(sent.curated_ids,[],'Zero priorities can Apply');
   await page.locator('[data-archive-lead-mode]').selectOption('manual');await settled(page);assert((await page.locator('[data-archive-lead-status]').innerText()).includes('Preview and Apply'),'Unavailable active Manual blocks with recovery guidance');
   await page.locator('[data-archive-lead-mode]').selectOption('automatic');await settled(page);
   await page.locator('[data-archive-priority-search]').fill('none');await page.locator('[data-archive-priority-search-button]').click();await settled(page);
   equal(await page.locator('[data-archive-priority-results]').innerText(),'No published stories found.','Empty search has a useful result state');
   state.failMetadata=true;await page.locator('[data-archive-lane-mode]').selectOption('query');await page.waitForSelector('[data-archive-retry]:not([hidden])');assert((await page.locator('[data-archive-status]').innerText()).includes('retained'),'Metadata failure retains selection with retry');state.failMetadata=false;await page.locator('[data-archive-retry]').click();await settled(page);
   state.badMetadata=true;await page.locator('[data-archive-lane-mode]').selectOption('curated');await page.waitForSelector('[data-archive-retry]:not([hidden])');assert(await page.locator('[data-archive-status]').isVisible(),'Malformed artwork metadata fails safely');state.badMetadata=false;await page.locator('[data-archive-retry]').click();await settled(page);
   await page.locator('[data-archive-lead-mode]').selectOption('manual');await settled(page);
   const stale=barrier();state.holdMetadata=stale;await page.locator('[data-archive-lead-search]').fill('101');await page.locator('[data-archive-lead-search-button]').click();await within(stale.entered,'Held search');
   await page.locator('[data-archive-lead-mode]').selectOption('automatic');await settled(page);stale.release();await page.waitForTimeout(50);
   assert((await page.locator('[data-archive-lead-selected]').innerText()).includes('Published story 100'),'A stale Manual search cannot replace Automatic details');equal(await page.locator('[data-archive-lead-results] button').count(),0,'A stale search cannot leave selectable results after switching modes');
   const read=barrier();state.holdMetadata=read;await page.locator('[data-archive-lane-mode]').selectOption('query');await within(read.entered,'Held metadata');
   const hold=barrier();state.holdPreview=hold;await page.locator('[data-action="preview"]').click();await within(hold.entered,'Held Preview');
   assert(await page.locator('[data-archive-controls]').evaluate(node=>node.disabled),'Pending Preview freezes selection controls');read.release();hold.release();await settled(page);assert(!(await page.locator('[data-archive-controls]').evaluate(node=>node.disabled)),'Selection controls and metadata reads resume after Preview interrupted a pending read');
   await page.close();
   const max=initial(surface);Object.assign(max,{selection_version:1,lead_mode:'manual',lead_id:100,lane_mode:'curated',curated_ids:Array.from({length:24},(_,index)=>index+1)});
   const full=await open(browser,surface,1440,max);equal(await order(full.page),max.curated_ids,'24 priorities plus a distinct lead accept all 25 metadata priorities');
   await full.page.locator('[data-archive-priority-search-button]').click();await settled(full.page);assert(await full.page.locator('[data-archive-priority-results] button').evaluateAll(nodes=>nodes.length>0&&nodes.every(node=>node.disabled)),'Priority search cannot exceed the 24-story limit');await full.page.close();
  }
  process.stdout.write(`archive-selection browser: ${checks} checks passed\n`);
 } finally {await browser.close();}
})().catch(error=>{process.stderr.write(error.stack+'\n');process.exit(1);});
