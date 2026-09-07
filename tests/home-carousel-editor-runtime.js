'use strict';
const fs = require('fs'); const path = require('path'); const {spawnSync} = require('child_process'); const {chromium} = require('playwright-core');
let checks = 0; function check(value, message) { checks++; if (!value) { throw new Error(message); } }
(async () => {
 const executablePath = process.env.LUNARA_BROWSER_EXECUTABLE || ['C:/Program Files/Google/Chrome/Application/chrome.exe','C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe','/usr/bin/chromium','/usr/bin/chromium-browser','/usr/bin/google-chrome'].find(fs.existsSync);
 check(executablePath && fs.existsSync(executablePath), 'LUNARA_BROWSER_EXECUTABLE must name a real Chromium executable.');
 const browser = await chromium.launch({headless:true, executablePath});
 try {
  for (const kind of ['hero','journal']) {
   const rendered = spawnSync('php',[path.join(__dirname,'home-carousel-settings-runtime.php'),'--fixture', ...(kind === 'journal' ? ['--journal'] : [])],{encoding:'utf8'}); check(rendered.status === 0,'Real PHP inspector fixture renders');
   const controller = fs.readFileSync(path.join(__dirname,'../assets/js/lunara-site-studio-carousels.js'),'utf8');
   const page = await browser.newPage({viewport:{width:1440,height:1000}}); let submitted, saves = 0, failure = false, releasePreview, holdPreview = false;
   await page.route('https://example.test/**',async route => {
    const request = route.request(), url = new URL(request.url());
    if (url.pathname === '/admin') { return route.fulfill({contentType:'text/html',body:rendered.stdout}); }
    if (url.pathname === '/controller.js') { return route.fulfill({contentType:'application/javascript',body:controller}); }
    if (url.pathname.endsWith('/search')) { check(request.headers()['x-wp-nonce'] === 'test-nonce','Search sends nonce'); return route.fulfill({json:{items:[{id:10,title:'Published first',type:kind === 'hero' ? 'review' : 'journal'},{id:20,title:'Published second',type:'journal'}]}}); }
    if (url.pathname.endsWith('/preview')) { if (holdPreview) { await new Promise(resolve => {releasePreview=resolve;}); } return route.fulfill({json:{url:'https://example.test/?lunara_'+kind+'_carousel_preview=123e4567-e89b-42d3-a456-426614174111'}}); }
    if (url.pathname.endsWith('/save')) { submitted = request.postDataJSON().state; saves++; if (failure) { return route.fulfill({status:422,json:{message:'Simulated rejected settings'}}); } return route.fulfill({json:{state:{...submitted,adopted:true},revision_id:'safe-revision',timestamp:'2026-09-07 12:00:00',changed_sections:[]}}); }
    if (url.pathname.endsWith('/revisions')) { return route.fulfill({json:{revisions:[]}}); }
    return route.fulfill({contentType:'text/html',body:'Private or live preview'});
   });
   await page.goto('https://example.test/admin'); await page.waitForSelector('[data-lunara-site-studio-ready="true"]');
   check(await page.locator('[data-carousel-items]').innerText().then(t=>t.includes('Unavailable')), 'Missing items flagged');
   await page.locator('[data-carousel-field="mode"]').selectOption('auto'); await page.locator('[data-carousel-field="mode"]').selectOption('manual'); check(await page.locator('[data-carousel-items]>li').count() === 1,'Mode switching retains selection');
   await page.locator('[data-carousel-search-button]').click(); await page.getByRole('button',{name:'Published first',exact:false}).click(); await page.getByRole('button',{name:'Published second',exact:false}).click();
   check(await page.locator('[data-carousel-items]>li').count() === 3,'Search selects published items');
   await page.locator('[data-carousel-items]>li').nth(2).getByRole('button',{name:'Move up',exact:true}).click();
   await page.locator('[data-carousel-items]>li').nth(0).getByRole('button',{name:'Remove',exact:true}).click();
   check(await page.locator('[data-carousel-items]>li').first().innerText().then(t=>t.includes('Published second')),'Accessible ordering and removal preserve order');
   await page.locator('[data-carousel-items]>li').first().dragTo(page.locator('[data-carousel-items]>li').nth(1));
   check(await page.locator('[data-carousel-items]>li').first().innerText().then(t=>t.includes('Published first')),'Pointer drag changes manual order');
   await page.locator('[data-carousel-items]>li').first().getByRole('button',{name:'Move down',exact:true}).click();
   await page.locator('[data-carousel-items]>li').first().locator('summary').click(); await page.getByLabel('Headline (blank inherits)').first().fill('Override headline'); await page.getByLabel('Excerpt (blank inherits)').first().fill('Override excerpt');
   await page.evaluate(()=>{window.wp={media:()=>{let selected; return {on:(event,callback)=>{selected=callback;},state:()=>({get:()=>({first:()=>({toJSON:()=>({id:42})})})}),open:()=>selected()};}};});
   await page.getByRole('button',{name:'Choose image',exact:true}).first().click();
   await page.locator('[data-carousel-field="interval"]').fill('11');
   holdPreview = true; await page.locator('[data-action="preview"]').click(); await page.waitForFunction(()=>document.querySelector('[data-carousel-field="heading"]').disabled);
   check(await page.locator('[data-action="save"]').isDisabled(),'Preview request freezes Apply'); releasePreview(); await page.waitForFunction(()=>!document.querySelector('[data-action="save"]').disabled); holdPreview=false;
   check(await page.locator('iframe').getAttribute('src').then(src=>src.includes('lunara_site_studio_instance')),'Preview binds private instance'); check(saves === 0,'Private preview never saves');
   await page.getByRole('button',{name:'Mobile',exact:true}).click(); check(await page.locator('iframe').getAttribute('width') === '390','Mobile preview width');
   failure=true; await page.locator('[data-action="save"]').click(); await page.waitForFunction(()=>document.querySelector('[data-workspace-status]').textContent.includes('Simulated'));
   check(await page.getByLabel('Headline (blank inherits)').first().inputValue() === 'Override headline','Rejected save retains candidate');
   failure=false; await page.locator('[data-action="save"]').click(); await page.waitForFunction(()=>document.querySelector('[data-workspace-status]').textContent === 'Carousel applied.');
   check(submitted.slides.map(s=>s.post_id).join(',') === '20,10' && submitted.slides[0].image_id === 42 && submitted.slides[0].headline === 'Override headline' && submitted.slides[0].excerpt === 'Override excerpt' && submitted.interval === 11,'Canonical Apply submits ordered independent presentation');
   await page.locator('[data-carousel-items]>li').first().getByRole('button',{name:'Remove',exact:true}).click(); await page.locator('[data-carousel-items]>li').first().getByRole('button',{name:'Remove',exact:true}).click(); check(await page.locator('[data-carousel-empty]').isVisible(),'Empty manual warning');
   await page.close();
  }
 } finally { await browser.close(); }
 console.log(`PASS ${checks} carousel browser editor contracts`);
})().catch(error=>{console.error(error);process.exit(1);});
