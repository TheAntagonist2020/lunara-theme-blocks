'use strict';
const fs=require('fs'),path=require('path'),{execFileSync}=require('child_process'),{chromium}=require('playwright-core');
const root=path.resolve(__dirname,'..');
const executablePath=process.env.LUNARA_BROWSER_EXECUTABLE||['C:/Program Files/Google/Chrome/Application/chrome.exe','C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe','/usr/bin/chromium','/usr/bin/chromium-browser','/usr/bin/google-chrome'].find(fs.existsSync);
const fixtures=JSON.parse(execFileSync('php',[path.join(__dirname,'article-layout-runtime.php')],{encoding:'utf8'}));
const fixture=fixtures['journal-normal'];
const source=fs.readFileSync(path.join(root,'inc/frontend.php'),'utf8');
let script=source.slice(source.indexOf('function lunara_output_journal_image_carousel_js()'),source.indexOf("add_action( 'wp_footer', 'lunara_output_journal_image_carousel_js'" )).match(/<script>([\s\S]*?)<\/script>/)[1];
if(process.env.LUNARA_GALLERY_MUTATION==='old')script=script.replace(/previous.disabled = [^;]+;/,'previous.disabled = false;').replace(/next.disabled = [^;]+;/,'next.disabled = false;');
if(process.env.LUNARA_GALLERY_MUTATION==='boundaries')script=script.replace('fits || track.scrollLeft <= 2','fits').replace('fits || track.scrollLeft >= maximum - 2','fits');
if(process.env.LUNARA_GALLERY_MUTATION==='motion')script=script.replace("window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'","'smooth'");
let css=['style.css','assets/css/lunara-shell.css','assets/css/lunara-review-components.css','assets/css/lunara-public-guardrails.css','assets/css/lunara-journal-single.css'].map(f=>fs.readFileSync(path.join(root,f),'utf8')).join('\n');
if(process.env.LUNARA_GALLERY_MUTATION==='motion-css')css=css.replace('body.single-journal .lunara-journal-image-carousel-track{scroll-behavior:auto;}','body.single-journal .lunara-journal-image-carousel-track{scroll-behavior:smooth;}');
let checks=0;const check=(x,m)=>{checks++;if(!x)throw Error(m)};
for(const count of ['0','1']){
 const variants=JSON.parse(execFileSync('php',[path.join(__dirname,'article-layout-runtime.php')],{encoding:'utf8',env:{...process.env,LUNARA_TEST_GALLERY_COUNT:count}}));
 const html=variants['journal-normal'].html;
 check(!html.includes('data-lunara-carousel-action'),'Actual '+count+'-image renderer emits no unnecessary buttons');
 check(html.includes('data-lunara-journal-carousel')===(count==='1'),'Actual empty renderer omits gallery; single image retains its display');
}
(async()=>{const browser=await chromium.launch({headless:true,executablePath});try{
for(const reducedMotion of ['reduce','no-preference']){
 const page=await browser.newPage({viewport:{width:1440,height:1000},reducedMotion});
 page.setDefaultTimeout(5000);
 await page.route('**/*',r=>r.fulfill({contentType:'image/svg+xml',body:'<svg xmlns="http://www.w3.org/2000/svg" width="1500" height="900"><rect width="1500" height="900" fill="#456"/></svg>'}));
 await page.setContent('<style>*,*:before,*:after{box-sizing:border-box}body{margin:0}</style><style>'+css+'</style><body class="single single-journal"><main>'+fixture.html+'</main><section data-lunara-journal-carousel id="unrelated"><button>Unrelated</button></section></body>');
 await page.addScriptTag({content:script});
 const track=page.locator('.lunara-journal-image-carousel-track'),prev=page.getByRole('button',{name:'Previous image',exact:true}),next=page.getByRole('button',{name:'Next image',exact:true});
 await page.waitForFunction(()=>document.querySelector('[data-lunara-carousel-action="next"]').disabled);
 check(await prev.isDisabled()&&await next.isDisabled(),'Fitting desktop gallery disables both controls');
 check(await track.evaluate(e=>getComputedStyle(e).scrollBehavior)===(reducedMotion==='reduce'?'auto':'smooth'),'Computed track scrolling honors motion preference');
 check(await next.evaluate(e=>Number(getComputedStyle(e).opacity)<.5),'Fitting controls have a visible disabled state');
 check(await page.locator('#unrelated button').isEnabled(),'Non-gallery marker remains untouched');
 await page.setViewportSize({width:390,height:844});
 await page.waitForFunction(()=>!document.querySelector('[data-lunara-carousel-action="next"]').disabled);
 check(await prev.isDisabled()&&await next.isEnabled(),'Phone start enables only Next');
 await track.evaluate(e=>{const native=e.scrollBy.bind(e);e.scrollBy=function(options){this.dataset.lastBehavior=options.behavior;native(options)}});
 await next.click();await page.waitForFunction(()=>document.querySelector('.lunara-journal-image-carousel-track').scrollLeft>100);await page.waitForFunction(()=>document.querySelector('[data-lunara-carousel-action="next"]').disabled);check(await prev.isEnabled(),'End enables only Previous');
 check(await track.getAttribute('data-last-behavior')===(reducedMotion==='reduce'?'auto':'smooth'),'Actual scrollBy request honors motion preference');
 await prev.click();await page.waitForFunction(()=>document.querySelector('.lunara-journal-image-carousel-track').scrollLeft<5);await page.waitForFunction(()=>document.querySelector('[data-lunara-carousel-action="prev"]').disabled);check(await next.isEnabled(),'Previous returns to start boundary');
 await track.focus();await page.keyboard.press('ArrowRight');await page.waitForFunction(()=>document.querySelector('.lunara-journal-image-carousel-track').scrollLeft>10);await page.waitForFunction(()=>!document.querySelector('[data-lunara-carousel-action="prev"]').disabled);check(true,'Native keyboard scroll refreshes boundary controls');
 await track.evaluate(e=>e.appendChild(e.lastElementChild.cloneNode(true)));
 await prev.click();await page.waitForFunction(()=>document.querySelector('.lunara-journal-image-carousel-track').scrollLeft<5);
 await next.click();await page.waitForFunction(()=>document.querySelector('.lunara-journal-image-carousel-track').scrollLeft>250);
 await page.waitForFunction(()=>!document.querySelector('[data-lunara-carousel-action="prev"]').disabled&&!document.querySelector('[data-lunara-carousel-action="next"]').disabled);check(true,'Three-slide middle snap enables both directions');
 await track.locator('figure').last().evaluate(e=>e.remove());
 const slide=await track.locator('figure').last().evaluate(e=>e.outerHTML);await track.locator('figure').last().evaluate(e=>e.remove());
 await page.waitForFunction(()=>document.querySelector('[data-lunara-carousel-action="next"]').disabled&&document.querySelector('[data-lunara-carousel-action="prev"]').disabled);check(await prev.isDisabled(),'One remaining image disables unnecessary controls');
 const onlySlide=await track.locator('figure').evaluate(e=>e.outerHTML);await track.evaluate(e=>e.replaceChildren());
 await page.waitForFunction(()=>document.querySelector('[data-lunara-carousel-action="prev"]').disabled&&document.querySelector('[data-lunara-carousel-action="next"]').disabled);check(true,'Empty track after content removal disables both controls');
 await track.evaluate((e,html)=>e.insertAdjacentHTML('beforeend',html),onlySlide);
 await track.evaluate((e,html)=>e.insertAdjacentHTML('beforeend',html),slide);await page.waitForFunction(()=>!document.querySelector('[data-lunara-carousel-action="next"]').disabled);check(true,'Adding content restores controls');
 await page.setViewportSize({width:1440,height:1000});await page.waitForFunction(()=>document.querySelector('[data-lunara-carousel-action="next"]').disabled);check(true,'Resizing back to fit disables controls');
 // Content width can change without a viewport resize or child insertion.
 await track.locator('figure').first().evaluate(e=>{e.style.setProperty('min-width','1400px','important')});await page.waitForFunction(()=>!document.querySelector('[data-lunara-carousel-action="next"]').disabled);check(true,'Content geometry changes restore useful controls');
 await track.locator('figure').first().evaluate(e=>e.style.removeProperty('min-width'));await page.waitForFunction(()=>document.querySelector('[data-lunara-carousel-action="next"]').disabled);check(true,'Restored geometry disables fitting controls');
 await page.close();
}
console.log(`Journal gallery controls: ${checks} checks passed.`);
}finally{await browser.close()}})().catch(e=>{console.error(e);process.exit(1)});
