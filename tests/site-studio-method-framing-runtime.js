'use strict';
const fs=require('fs'),path=require('path'),{spawnSync}=require('child_process'),{chromium}=require('playwright-core');
const root=path.resolve(__dirname,'..');
const read=file=>fs.readFileSync(path.join(root,file),'utf8');
function php(file,arg){const result=spawnSync('php',[path.join(__dirname,file),arg],{encoding:'utf8'});if(result.status!==0)throw new Error(result.stderr||result.stdout);return result.stdout;}
const editorShell=php('site-studio-runtime.php','--fixture=lunara-method');
const publicStyles=['style.css','assets/css/lunara-shell.css','assets/css/lunara-review-components.css','assets/css/lunara-home-modules.css','assets/css/lunara-public-guardrails.css'].map(file=>`<style>${read(file)}</style>`).join('');
const editorStyles=['lunara-site-studio.css','lunara-editor-controls.css'].map(file=>read('assets/css/'+file)).join('\n');
const editorScripts=['lunara-editor-controls.js','lunara-site-studio-method.js','lunara-site-studio.js'].map(file=>read('assets/js/'+file)).join('\n');
const executablePath=process.env.LUNARA_BROWSER_EXECUTABLE||['C:/Program Files/Google/Chrome/Application/chrome.exe','C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe','/usr/bin/chromium','/usr/bin/chromium-browser','/usr/bin/google-chrome'].find(fs.existsSync);
let checks=0;function check(value,message){checks++;if(!value)throw new Error(message);}
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath});
 try{
  for(const fit of ['cover','full','hidden']) for(const art of [[1600,900],[600,900]]){
   const fixture=JSON.parse(php('site-studio-method-runtime.php','--fixture-framing='+fit));
   const editor=editorShell.replace(/(<script[^>]+id="lunara-site-studio-state"[^>]*>)[\s\S]*?(<\/script>)/,(_match,open,close)=>open+JSON.stringify(fixture.state)+close).replace('</head>',`<style>body{margin:0;font:14px/1.5 system-ui}${editorStyles}</style></head>`).replace('</body>',`<script>${editorScripts}</script></body>`);
   const publicPage=`<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1">${publicStyles}<style>body{margin:0}.lunara-front-page{width:calc(100% - 32px);margin:16px auto}</style></head><body class="home"><main class="lunara-front-page">${fixture.html}</main></body></html>`;
   const page=await browser.newPage({viewport:{width:1440,height:1000},reducedMotion:'reduce'});
   await page.route('https://example.test/**',route=>{
    const url=new URL(route.request().url());
    if(url.pathname==='/wp-admin/admin.php')return route.fulfill({contentType:'text/html',body:editor});
    if(url.pathname.endsWith('/metadata'))return route.fulfill({json:{item:{id:201,available:true,title:'Published Review',date_label:'Sep 10, 2026',image_url:'https://example.test/current-hero-1.jpg',image_source:'Review hero artwork'},image_url:''}});
    if(url.pathname.endsWith('.jpg'))return route.fulfill({contentType:'image/svg+xml',body:`<svg xmlns="http://www.w3.org/2000/svg" width="${art[0]}" height="${art[1]}"><rect width="100%" height="100%" fill="#29485a"/></svg>`});
    if(url.pathname.startsWith('/wp-json/'))return route.fulfill({json:{revisions:[]}});
    if(url.pathname!=='/')return route.fulfill({status:404,body:''});
    return route.fulfill({contentType:'text/html',body:publicPage});
   });
   await page.goto('https://example.test/wp-admin/admin.php?page=lunara-site-studio&surface=lunara-method');
   await page.waitForFunction(()=>document.querySelector('[data-method-selected]').textContent.includes('Published Review'));
   await page.locator('details[data-section="fine-tune"]').evaluate(node=>{node.open=true;});
   await page.waitForFunction(()=>{const frame=document.querySelector('iframe');return frame.contentDocument.querySelector('.lunara-pairing-desk-section')&&frame.contentDocument.readyState==='complete';});
   if(fit!=='hidden')await page.waitForFunction(()=>{const image=document.querySelector('.lunara-editor-image-stage img');return image.complete&&image.naturalWidth>0;});
   const result=await page.evaluate(({fit,art})=>{
    const stage=document.querySelector('.lunara-editor-image-stage'),image=stage.querySelector('img'),frame=document.querySelector('iframe'),section=frame.contentDocument.querySelector('.lunara-pairing-desk-section'),backdrop=section.querySelector('.lunara-pairing-desk-backdrop');
    if(fit==='hidden')return{hidden:image.hidden&&!backdrop};
    function painted(node,container,background){
     const css=node.ownerDocument.defaultView.getComputedStyle(node),rect=node.getBoundingClientRect(),outer=container.getBoundingClientRect();
     const size=background?css.backgroundSize:css.objectFit,position=(background?css.backgroundPosition:css.objectPosition).split(' ').map(parseFloat);
     const scale=(size==='cover'?Math.max:Math.min)(rect.width/art[0],rect.height/art[1]);
     const width=art[0]*scale,height=art[1]*scale;
     return{size,position,zoom:css.transform==='none'?1:new DOMMatrix(css.transform).a,bounds:[(rect.left+(rect.width-width)*position[0]/100-outer.left-container.clientLeft)/container.clientWidth,(rect.top+(rect.height-height)*position[1]/100-outer.top-container.clientTop)/container.clientHeight,width/container.clientWidth,height/container.clientHeight]};
    }
    return{public:painted(backdrop,section,true),guide:painted(image,stage,false),sectionRatio:section.getBoundingClientRect().width/section.getBoundingClientRect().height,guideRatio:parseFloat(stage.style.aspectRatio.split('/')[0])/parseFloat(stage.style.aspectRatio.split('/')[1])};
   },{fit,art});
   if(fit==='hidden'){check(result.hidden,'Remove image hides both actual public artwork and the editor guide.');}
   else{
    check(Math.abs(result.sectionRatio-result.guideRatio)<0.001,'The guide follows the rendered section dimensions, not a fixed test height.');
    check(result.public.size===(fit==='cover'?'cover':'contain')&&result.guide.size===(fit==='cover'?'cover':'contain'),'Both destinations use the requested cover/full fit.');
    check(result.public.position.join(',')==='17,81'&&result.guide.position.join(',')==='17,81','Both destinations preserve the actual requested focal point.');
    check(Math.abs(result.public.zoom-(fit==='cover'?1.09:1))<0.001&&Math.abs(result.guide.zoom-result.public.zoom)<0.001,'Full fit removes zoom; cover applies the requested zoom in both destinations.');
    // Compare the visible source-image coordinates. The guide's 1px border and
    // small display size create subpixel rounding, especially for tall art.
    const crop=bounds=>[-bounds[0]/bounds[2],-bounds[1]/bounds[3],1/bounds[2],1/bounds[3]];
    const publicComposition=fit==='cover'?crop(result.public.bounds):result.public.bounds,guideComposition=fit==='cover'?crop(result.guide.bounds):result.guide.bounds;
    check(publicComposition.every((value,index)=>Math.abs(value-guideComposition[index])<0.01),'Rendered artwork and the guide have equivalent composition: '+JSON.stringify(result));
   }
   await page.click('[data-preview-width="mobile"]');
   await page.waitForFunction(()=>document.querySelector('iframe').contentWindow.innerWidth===390);
   check(await page.evaluate(()=>{const frame=document.querySelector('iframe'),backdrop=frame.contentDocument.querySelector('.lunara-pairing-desk-backdrop');return !backdrop||frame.contentWindow.getComputedStyle(backdrop).display==='none';}),'The actual public decorative backdrop stays hidden in the mobile preview.');
   await page.close();
  }
  console.log(`Method real-renderer framing parity passed: ${checks} checks for cover/full/hidden with landscape and portrait artwork.`);
 }finally{await browser.close();}
})().catch(error=>{console.error(error.stack);process.exitCode=1;});
