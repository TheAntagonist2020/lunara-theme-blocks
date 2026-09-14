const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const {chromium}=require('playwright-core');
const root=path.resolve(__dirname,'..');
const captures=process.env.LUNARA_ACTION_ARTIFACT_DIR;
const fixture=JSON.parse(require('node:zlib').gunzipSync(fs.readFileSync(path.join(__dirname,'fixtures/academy-action-cascade.json.gz'))));
const executablePath=process.env.LUNARA_BROWSER_EXECUTABLE||['C:/Program Files/Google/Chrome/Application/chrome.exe','C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe','/usr/bin/chromium','/usr/bin/chromium-browser','/usr/bin/google-chrome'].find(fs.existsSync);assert(executablePath,'A browser executable is required');
// Anonymous PHP responses with every linked stylesheet inlined in original order.
// No appended candidate layer: replace the owning declaration at its exact position.
const owners=[
 ['lunara-shell.css','body.aat-shell-page .aat-btn,body.aat-shell-page .aat-hub-card-action,body.aat-shell-page .aat-winner-circle-action'],
 ['lunara-public-guardrails.css','.aat-decade-pill,.aat-hub-chip,.aat-hub-card-action,.aat-winner-circle-action,.aat-entity-status-tag,.aat-nominee-trail-summary'],
 ['lunara-oscars-late-guardrails.css','body.aat-shell-page .aat-decade-pill'],
 ['lunara-shell.css','body.aat-shell-page a.aat-hub-chip']
].map(([file,selector])=>{const source=fs.readFileSync(path.join(root,'assets/css',file),'utf8');const compact=source.replace(/\s+/g,' ').replace(/\s*,\s*/g,',');const start=compact.indexOf(selector+' {');assert(start>=0,file+' owner');const value=compact.slice(start,compact.indexOf('}',start)).match(/min-height:\s*(\d+)px/)[1];return {file,selector,value:process.env.LUNARA_ACTION_MUTATION==='40'?'40':value};});
const pluginSelector='body .aat-container .aat-premium-category-dossier .aat-ledger-card .aat-winner-circle-action';
const pluginValues=process.env.LUNARA_ACTION_MUTATION==='30'?['30','28']:['44','44'];
(async()=>{const browser=await chromium.launch({headless:true,executablePath});let checks=0;const results=[];const check=(x,m)=>{checks++;assert(x,m)};
try{for(const kind of ['ceremony','title','name','category'])for(const width of [320,390,1440]){
 const page=await browser.newPage({viewport:{width,height:900},javaScriptEnabled:false,reducedMotion:'reduce'});
 await page.route('**/*',route=>route.abort()); // Offline, including images and fonts.
 await page.setContent(fixture.html[kind].replace(/<style([^>]*data-fixture-css="([^"]+)"[^>]*)><\/style>/g,(_,attrs,key)=>'<style'+attrs+'>'+fixture.css[key]+'</style>'),{waitUntil:'domcontentloaded'});
 const changed=await page.evaluate(({owners,pluginSelector,pluginValues})=>{let pluginIndex=0;const norm=s=>s.replace(/\s*,\s*/g,',').replace(/\s+/g,' ').trim();const hits=[];let position=0;function walk(rules){for(const r of [...rules]){position++;if(r.selectorText&&norm(r.selectorText)===pluginSelector&&r.style.minHeight){r.style.setProperty('min-height',pluginValues[pluginIndex++]+'px','important');}if(r.selectorText){for(const o of owners)if(norm(r.selectorText)===o.selector&&r.style.getPropertyValue('min-height')){hits.push({file:o.file,position,old:r.style.minHeight});r.style.setProperty('min-height',o.value+'px','important');}}if(r.selectorText&&norm(r.selectorText)==='body.aat-shell-page .aat-hub-chip,body.aat-shell-page .aat-winner-badge,body.aat-shell-page .aat-winner-circle-category'&&r.style.minHeight){const o=owners[3],sheet=r.parentStyleSheet;const index=[...sheet.cssRules].indexOf(r);if(index>=0){sheet.insertRule(o.selector+'{min-height:'+o.value+'px!important}',index+1);hits.push({file:o.file,position,old:'30px'});}}if(r.cssRules)walk(r.cssRules)}}for(const sheet of document.styleSheets)walk(sheet.cssRules);return hits},{owners,pluginSelector,pluginValues});
 check(owners.every(o=>changed.some(h=>h.file===o.file)),kind+': all three production owners present');
 check(changed.find(h=>h.file===owners[0].file).position<changed.find(h=>h.file===owners[1].file).position&&changed.find(h=>h.file===owners[1].file).position<changed.find(h=>h.file===owners[2].file).position,kind+': shell then public then late owner order');
 const data=await page.evaluate(()=>({width:document.documentElement.scrollWidth,actions:[...document.querySelectorAll('a.aat-btn,a.aat-hub-card-action,a.aat-winner-circle-action,a.aat-decade-pill,a.aat-hub-chip,a.aat-hub-chip-rich,a.aat-ceremony-neighbor-index,.aat-category-history a.aat-timeline-link,.aat-category-history-actions a,.aat-entity-actions a,.aat-ceremony-dossier-actions a,summary.aat-nominee-trail-summary')].filter(e=>e.getBoundingClientRect().width&&e.getBoundingClientRect().height).map(e=>({text:e.textContent.trim().slice(0,90)+" ["+e.className+"]",height:e.getBoundingClientRect().height,width:e.getBoundingClientRect().width,href:e.getAttribute('href')}))}));
 check(data.actions.length>0,kind+': actual actions present');for(const a of data.actions){check(a.height>=43.99,`${kind}/${width}: ${a.text} height ${a.height}`);check(a.width>=43.99,`${kind}/${width}: ${a.text} width ${a.width}`)}
 check(data.width<=width+1,`${kind}/${width}: no overflow`);results.push({kind,width,changed,...data});
 if(captures&&width!==320)await page.screenshot({path:path.join(captures,`${kind}-${width}-candidate.png`),fullPage:false});await page.close();
}if(captures)fs.writeFileSync(path.join(captures,'action-results.json'),JSON.stringify(results,null,2));console.log(`Academy action cascade: ${checks} checks passed (4 actual routes, 320/390/1440, offline/no-JS/reduced motion).`)}finally{await browser.close()}})().catch(e=>{console.error(e);process.exit(1)});

