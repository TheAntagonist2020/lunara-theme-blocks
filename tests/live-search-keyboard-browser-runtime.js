'use strict';
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path'),{execFileSync}=require('node:child_process'),{chromium}=require('playwright-core');
const root=path.resolve(__dirname,'..');
const fixture=JSON.parse(execFileSync('php',[path.join(__dirname,'live-search-keyboard-fixture.php')],{encoding:'utf8'}));
const script=fs.readFileSync(process.env.LUNARA_SEARCH_SCRIPT_SOURCE||path.join(root,'assets/js/lunara-live-search.js'),'utf8');
const css=fs.readFileSync(path.join(root,'style.css'),'utf8');
const executablePath=process.env.LUNARA_BROWSER_EXECUTABLE||['C:/Program Files/Google/Chrome/Application/chrome.exe','C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe','/usr/bin/chromium','/usr/bin/chromium-browser','/usr/bin/google-chrome'].find(fs.existsSync);
let checks=0;
function check(value,message){++checks;assert.ok(value,message);}
const active=locator=>locator.evaluate(element=>element===document.activeElement);
function markup(scripts){return '<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>*,*:before,*:after{box-sizing:border-box}body{margin:0}</style><style>'+css+'</style>'+fixture.header_css+'</head><body class="lunara-header-takeover">'+fixture.html+'<main><h1>Keyboard fixture</h1><a id="second-trigger" data-lunara-search-open href="https://lunara.test/search/"><span>Search this page</span></a><button id="outside">Outside dialog</button></main>'+(scripts?'<script>window.LUNARA_LIVE_SEARCH={endpoint:"https://lunara.test/wp-json/lunara/v1/search",suggestions:["Sound","Sinners"]};</script><script>'+script+'</script>':'')+'</body></html>';}
async function install(page,scripts=true){
 await page.route('**/*',route=>{
  const url=new URL(route.request().url());
  if(url.origin!=='https://lunara.test')return route.abort();
  if(url.pathname==='/fixture/')return route.fulfill({contentType:'text/html',body:markup(scripts)});
  if(url.pathname==='/wp-json/lunara/v1/search'){
   const q=url.searchParams.get('q');
   if(q==='error')return route.abort();
   return route.fulfill({contentType:'application/json',body:JSON.stringify(q==='empty'?{groups:[]}:{groups:[{label:'Reviews',items:[{title:'First Review',url:'https://lunara.test/reviews/first/'},{title:'Second Review',url:'https://lunara.test/reviews/second/'}]},{label:'Journal',items:[{title:'Journal Story',url:'https://lunara.test/journal/story/'}]}],more_url:'https://lunara.test/search/?q='+encodeURIComponent(q)})});
  }
  if(url.pathname==='/search/'||url.pathname.startsWith('/reviews/'))return route.fulfill({contentType:'text/html',body:'<!doctype html><main>Native destination</main>'});
  return route.abort();
 });
 await page.goto('https://lunara.test/fixture/');
}
async function waitOpen(page){await page.waitForFunction(()=>document.querySelector('#lunara-search-overlay').classList.contains('is-open')&&document.activeElement.id==='lunara-search-overlay-input');}
async function waitClosed(page){await page.waitForFunction(()=>document.querySelector('#lunara-search-overlay').hidden);}
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath});
 try{
  for(const width of [390,1440])for(const reducedMotion of ['reduce','no-preference']){
   const context=await browser.newContext({viewport:{width,height:900},reducedMotion});
   const page=await context.newPage();page.setDefaultTimeout(6000);await install(page);
   const opener=page.locator('.lunara-header-search'),second=page.locator('#second-trigger'),input=page.locator('#lunara-search-overlay-input'),close=page.locator('.lunara-search-overlay-close'),overlay=page.locator('#lunara-search-overlay');
   await opener.focus();await page.keyboard.press('Enter');await waitOpen(page);
   check(await active(input),'Opening focuses the real Search input');
   const lastSuggestion=page.locator('.lunara-search-suggestion').last();
   await page.keyboard.press('Shift+Tab');check(await active(lastSuggestion),'Shift+Tab wraps to the current final suggestion');
   await page.keyboard.press('Tab');check(await active(input),'Tab wraps from the final suggestion to input');
   await page.keyboard.press('Tab');check(await active(close),'Ordinary forward Tab order stays native');
   await input.fill('Lunara');await page.waitForSelector('.lunara-search-hit--more');
   check(await input.inputValue()==='Lunara','Typing remains unchanged while results refresh');
   await page.locator('.lunara-search-hit--more').focus();await page.keyboard.press('Tab');check(await active(input),'New result and More links participate in forward containment');
   await page.keyboard.press('Shift+Tab');check(await active(page.locator('.lunara-search-hit--more')),'Current dynamic More link is the reverse boundary');
   await page.getByRole('button',{name:'Reviews',exact:true}).click();
   await overlay.evaluate(el=>{const disabled=document.createElement('button');disabled.disabled=true;disabled.textContent='Disabled';const hidden=document.createElement('a');hidden.href='#';hidden.style.visibility='hidden';hidden.textContent='Invisible';el.append(disabled,hidden)});
   const tabs=await overlay.locator('input,button,a[href]').evaluateAll(els=>els.filter(el=>!el.disabled&&el.tabIndex>=0&&el.getClientRects().length&&getComputedStyle(el).visibility!=='hidden').map(el=>el.outerHTML));
   check(tabs.length===8,'Filtered Journal hits, disabled and invisible controls are excluded');
   await input.focus();
   for(let i=1;i<=tabs.length;i++){
    await page.keyboard.press('Tab');
    check(await page.evaluate(expected=>document.activeElement.outerHTML===expected,tabs[i%tabs.length]),'Forward Tab visits only current enabled visible controls '+i);
   }
   for(let i=tabs.length-1;i>=0;i--){
    await page.keyboard.press('Shift+Tab');
    check(await page.evaluate(expected=>document.activeElement.outerHTML===expected,tabs[i]),'Reverse Tab visits only current enabled visible controls '+i);
   }
   await close.focus();await page.keyboard.press('Escape');await waitClosed(page);check(await active(opener),'Escape from close button returns focus to its invoking header');
   await second.locator('span').click();await waitOpen(page);await close.click();await waitClosed(page);check(await active(second),'Close returns focus to the latest invoking anchor, including nested clicks');
   await opener.click();await waitOpen(page);await page.locator('.lunara-search-overlay-veil').dispatchEvent('click');await waitClosed(page);check(await active(opener),'Backdrop closing returns focus to its trigger');
   await opener.click();await waitOpen(page);await page.keyboard.press('Escape');await waitClosed(page);check(await active(opener),'Escape from the input restores its trigger');
   await opener.click();await waitOpen(page);await close.dispatchEvent('click');check(await active(opener),'Closing returns focus immediately, before its visual transition ends');
   await page.keyboard.press('Tab');check(!(await overlay.evaluate(el=>el.contains(document.activeElement))),'Closing controls cannot receive Tab during the hidden transition');await waitClosed(page);
   await page.evaluate(()=>{const trigger=document.querySelector('#second-trigger');trigger.focus();trigger.click();document.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}));});
   await page.waitForTimeout(240);check(await overlay.evaluate(el=>el.hidden&&!el.classList.contains('is-open')),'Closing before opening frame cancels delayed opening');check(await active(second),'Pending opening frame never focuses the hidden input');
   await opener.click();await waitOpen(page);
   await page.evaluate(()=>{document.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}));document.querySelector('#second-trigger').click();});
   await waitOpen(page);await page.waitForTimeout(240);check(await overlay.evaluate(el=>!el.hidden&&el.classList.contains('is-open')),'Reopening cancels the old closing deadline');check(await active(input),'Reopened dialog keeps visible input focus');
   await input.fill('empty');await page.waitForSelector('.lunara-search-overlay-empty');await close.focus();await page.keyboard.press('Tab');check(await active(input),'Empty results use the current two-control boundary');
   await input.fill('Lunara');await page.waitForSelector('.lunara-search-hit--more');await page.locator('.lunara-search-hit--more').focus();
   await input.evaluate(el=>{el.value='error';el.dispatchEvent(new Event('input',{bubbles:true}));});await page.waitForSelector('.lunara-search-overlay-empty');
   await page.keyboard.press('Shift+Tab');check(await active(close),'A removed focused result recovers inside the current dialog');
   await page.evaluate(()=>document.querySelector('#second-trigger').remove());await page.keyboard.press('Escape');await waitClosed(page);check(!(await overlay.evaluate(el=>el.contains(document.activeElement))),'A removed trigger never leaves focus in the closed dialog');
   await opener.click();await waitOpen(page);await input.fill('single');await page.waitForSelector('.lunara-search-hit--more');await page.keyboard.press('ArrowDown');check(await page.locator('.lunara-search-hit').first().evaluate(el=>el.classList.contains('is-active')),'Input arrow-key selection is preserved');await page.keyboard.press('Enter');await page.waitForURL('**/reviews/first/');check(true,'Enter still opens the selected result');
   await install(page);await page.locator('.lunara-header-search').click();await waitOpen(page);await page.locator('#lunara-search-overlay-input').fill('fresh');await page.keyboard.press('Enter');await page.waitForURL('**/search/?q=fresh');check(true,'Unselected Enter retains native form search');
   await context.close();
  }
  const context=await browser.newContext({javaScriptEnabled:false});const page=await context.newPage();await install(page,false);const trigger=page.locator('.lunara-header-search');check(await trigger.getAttribute('href')==='https://lunara.test/search/','Actual no-JavaScript trigger keeps its native URL');await trigger.click();await page.waitForURL('https://lunara.test/search/');check(true,'Search remains navigable without JavaScript');await context.close();
  console.log(`Live Search keyboard runtime passed: ${checks} checks.`);
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exitCode=1;});
