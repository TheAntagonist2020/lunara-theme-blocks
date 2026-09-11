'use strict';
const fs=require('fs'),path=require('path'),{spawnSync}=require('child_process');
let chromium;try{({chromium}=require('playwright'));}catch(error){({chromium}=require('playwright-core'));}
let checks=0;function check(ok,message){checks++;if(!ok){throw new Error(message);}}
async function waitHeld(read){const deadline=Date.now()+8000;while(!read()){if(Date.now()>deadline){throw new Error('The expected held request did not arrive.');}await new Promise(resolve=>setTimeout(resolve,10));}}
(async()=>{
 const executablePath=process.env.LUNARA_BROWSER_EXECUTABLE||['C:/Program Files/Google/Chrome/Application/chrome.exe','C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe','/usr/bin/chromium','/usr/bin/google-chrome'].find(fs.existsSync);
 const browser=await chromium.launch({headless:true,executablePath});
 try{
 for(const kind of ['picks','facts']){
  const surface='home-oscar-'+kind,root=path.join(__dirname,'..');
  const rendered=spawnSync('php',[path.join(__dirname,'site-studio-home-oscars-fixture.php'),surface],{encoding:'utf8'});check(rendered.status===0,'Real PHP inspector renders: '+kind);
  const scripts=['lunara-editor-controls.js','lunara-site-studio-home-oscars.js','lunara-site-studio.js'].map(f=>fs.readFileSync(path.join(root,'assets/js',f),'utf8')).join('\n');
  const css=['lunara-site-studio.css','lunara-editor-controls.css'].map(f=>fs.readFileSync(path.join(root,'assets/css',f),'utf8')).join('\n');
  const html=rendered.stdout.replace('</head>','<style>'+css+'</style></head>').replace('</body>','<script>'+scripts+'</script></body>');
  const page=await browser.newPage({viewport:{width:1440,height:1000}});const errors=[];page.on('pageerror',e=>errors.push(e.message));page.setDefaultTimeout(8000);
  let saved=null,previewed=null,posts=0,malformed='',missingImage=false,holdItems=false,releaseItems=null,holdPreview=false,releasePreview=null;
  const requests=[];
  await page.addInitScript(({facts})=>{
   if(facts){window.AbortController=undefined;}
   window.testMediaFrames=[];
   window.wp={media:()=>{const frame={selected:null,callback:null,on(name,callback){if(name==='select'){this.callback=callback;}return this;},open(){window.testMediaFrames.push(this);},state(){return {get:()=>({first:()=>({toJSON:()=>this.selected})})};}};return frame;}};
  },{facts:kind==='facts'});
  await page.route('https://example.test/**',async route=>{
   const req=route.request(),url=new URL(req.url()),reply=body=>route.fulfill({status:200,contentType:'application/json',body:JSON.stringify(body)});
   if(url.pathname.includes('admin.php')){return route.fulfill({contentType:'text/html',body:html});}
   if(url.pathname.endsWith('/items')){
    requests.push(url);const selected=(url.searchParams.get('ids')||'').split(',').filter(Boolean).map(Number),imageIds=(url.searchParams.get('image_ids')||'').split(',').filter(Boolean),images={};
    imageIds.forEach(id=>{images[id]=missingImage?'':'https://example.test/uploads/custom-'+id+'.svg';});
    const payload={items:[1,2,3,99].filter(id=>id!==99||selected.includes(id)).map(id=>({id,title:id===99?'Unavailable selection #99':'Published '+kind+' '+id,available:id!==99,image_url:'https://example.test/uploads/source-'+id+'.svg',image_source:'Source artwork',image_id:0,image_allowed:id!==99&&!(kind==='facts'&&id===3),fit:'cover',focal_x:50,focal_y:30,zoom:100})),results:[1,2,3],lineup:(url.searchParams.get('mode')==='manual'?selected.filter(id=>id!==99):url.searchParams.get('mode')==='legacy'?[1,2,3]:[3,2,1]).slice(0,Number(url.searchParams.get('count'))),images};
    if(malformed==='boolean'){payload.items[0].image_allowed='yes';}else if(malformed==='url'){payload.items[0].image_url='javascript:alert(1)';}else if(malformed==='duplicate'){payload.lineup=[1,1];}else if(malformed==='image'){payload.images={};}
    if(holdItems){holdItems=false;await new Promise(resolve=>{releaseItems=resolve;});}
    return reply(payload);
   }
   if(url.pathname.startsWith('/uploads/')){return route.fulfill({contentType:'image/svg+xml',body:'<svg xmlns="http://www.w3.org/2000/svg" width="200" height="600"><rect width="200" height="600" fill="#153e5c"/></svg>'});}
   if(url.pathname.endsWith('/preview')){previewed=req.postDataJSON().state;if(holdPreview){holdPreview=false;await new Promise(resolve=>{releasePreview=resolve;});}return reply({url:'https://example.test/?lunara_home_oscar_'+kind+'_preview=22222222-2222-4222-8222-222222222222'});}
   if(url.pathname.endsWith('/save')){posts++;saved=req.postDataJSON().state;return reply({state:saved,changed_sections:['oscar-'+kind],revision_id:'saved-1',timestamp:'2026-09-10 20:00:00'});}
   if(url.pathname.endsWith('/revisions')){return reply({revisions:[]});}
   return route.fulfill({contentType:'text/html',body:'<!doctype html><p>Private homepage fixture</p>'});
  });
  await page.goto('https://example.test/wp-admin/admin.php?page=lunara-site-studio&surface='+surface);
  await page.waitForSelector('[data-lunara-site-studio-ready="true"]');check(errors.length===0,'Workspace boots without script errors.');
  const row=id=>page.locator('[data-oscar-artwork-id="'+id+'"]');
  const ready=async()=>page.waitForFunction(()=>!document.querySelector('[data-oscars-status]').textContent.includes('Loading')&&!document.querySelector('[data-oscars-status]').textContent.includes('Could not load'));
  const open=async id=>{await row(id).waitFor();const details=row(id).locator('details');if(!await details.evaluate(el=>el.open)){await details.locator('summary').click();}return row(id);};
  const frame=async(id,imageId)=>{const item=await open(id);await item.getByRole('button',{name:/^(Replace|Choose) image$/}).click();await page.evaluate(imageId=>{const frame=window.testMediaFrames.at(-1);frame.selected={id:imageId,url:'https://example.test/uploads/custom-'+imageId+'.svg'};frame.callback();},imageId);};
  await ready();check(await page.locator('[data-oscars-lineup] > li').count()===3,'Legacy mode displays current lineup artwork.');
  check(await row(1).locator('button[data-editor-move]').count()===0,'Legacy lineup cannot accidentally be reordered.');
  if(kind==='facts'){await open(3);check(await row(3).getByRole('combobox',{name:'Image fit',exact:true}).isDisabled()&&(await row(3).innerText()).includes('held or unverified'),'Unverified Fact artwork has disabled controls with an explanation.');}
  await open(1);await row(1).getByRole('button',{name:/^Image framing:/}).press('ArrowRight');
  check(await row(1).getByRole('slider',{name:'Horizontal focal point',exact:true}).inputValue()==='55','Shared focal target supports keyboard arrows.');
  const zoom=row(1).getByRole('slider',{name:'Zoom',exact:true});await zoom.focus();await zoom.press('ArrowRight');await zoom.press('ArrowRight');
  check(await zoom.evaluate(el=>document.activeElement===el)&&await zoom.inputValue()==='102','Adjusting zoom preserves keyboard focus.');
  await row(1).getByRole('combobox',{name:'Image fit',exact:true}).selectOption('full');
  check(await zoom.isDisabled()&&await row(1).locator('.lunara-editor-image-stage img').evaluate(el=>el.style.objectFit)==='contain','Full fit disables cropping and shows the complete image.');
  check(await row(1).locator('.lunara-editor-image-stage img').evaluate(el=>el.style.objectPosition)==='55% 30%','Full fit retains positioning like the public renderer.');
  await row(1).getByRole('combobox',{name:'Image fit',exact:true}).selectOption('cover');
  check(await row(1).getByRole('slider',{name:'Horizontal focal point',exact:true}).inputValue()==='55','Returning to Fill frame restores the stored focal point.');
  await frame(1,701);check((await row(1).locator('.lunara-editor-image-stage img').getAttribute('src')).includes('custom-701'),'Media picker changes the placement image.');
  await row(1).getByRole('button',{name:'Use source image',exact:true}).click();
  check((await row(1).locator('.lunara-editor-image-stage img').getAttribute('src')).includes('source-1')&&await zoom.inputValue()==='102','Use source image retains framing.');
  await frame(1,701);
  await page.locator('[data-field-path="selection.mode"]').selectOption('manual');
  await page.getByRole('button',{name:'Add: Published '+kind+' 1',exact:true}).click();await page.getByRole('button',{name:'Add: Published '+kind+' 2',exact:true}).click();
  await page.locator('[data-oscars-lineup] li').nth(1).getByRole('button',{name:'Move earlier',exact:true}).click();
  check((await page.locator('[data-oscars-lineup] li').first().innerText()).includes('Published '+kind+' 2'),'Keyboard-accessible Move changes lineup order.');
  await page.locator('[data-field-path="selection.mode"]').selectOption('automatic');await page.locator('[data-field-path="selection.mode"]').selectOption('manual');
  await ready();check(await page.locator('[data-oscars-lineup] li').count()===2,'Mode switching retains the list.');
  await open(1);check((await row(1).locator('.lunara-editor-image-stage img').getAttribute('src')).includes('custom-701'),'Mode switching retains custom artwork.');
  await page.getByRole('button',{name:'Tablet',exact:true}).click();check(await row(1).locator('.lunara-editor-image-stage').evaluate(el=>el.style.aspectRatio)==='1.6 / 1','Tablet framing guide matches the public 820px breakpoint.');
  await page.getByRole('button',{name:'Mobile',exact:true}).click();check(await page.locator('iframe').getAttribute('width')==='390','Mobile preview uses the real 390px width.');
  check(await row(1).locator('.lunara-editor-image-stage').evaluate(el=>el.style.aspectRatio)==='1.6 / 1','Mobile framing guide uses the 16:10 image frame.');
  await row(1).getByRole('button',{name:'Replace image',exact:true}).click();
  holdPreview=true;
  await page.getByRole('button',{name:'Preview changes',exact:true}).click();
  await page.waitForFunction(()=>document.querySelector('[data-site-studio-inspector],.lunara-site-studio-inspector').getAttribute('aria-busy')==='true');
  check(await row(1).getByRole('button',{name:'Move earlier',exact:true}).isDisabled()&&await row(1).getByRole('button',{name:'Remove',exact:true}).isDisabled()&&await zoom.isDisabled(),'Preview freezes ordering and image controls.');
  await waitHeld(()=>releasePreview);releasePreview();releasePreview=null;
  await page.waitForFunction(()=>document.querySelector('[data-lunara-site-studio]').getAttribute('data-workspace-state')==='preview-current');await ready();
  await page.evaluate(()=>{const frame=window.testMediaFrames.at(-1);frame.selected={id:702,url:'https://example.test/uploads/custom-702.svg'};frame.callback();});
  check((await row(1).locator('.lunara-editor-image-stage img').getAttribute('src')).includes('custom-701'),'A media selection opened before Preview cannot change the candidate afterward.');
  check(posts===0&&previewed.selection.ids==='2,1','Preview uses the exact draft without saving public settings.');
  const artwork=JSON.parse(previewed.artwork.overrides);check(artwork[1].image_id===701&&artwork[1].focal_x===55&&artwork[1].zoom===102,'Private Preview includes exact artwork and focal settings.');
  check((await page.locator('iframe').getAttribute('src')).includes('lunara_site_studio_instance='),'Private preview binds the iframe instance.');
  await page.getByRole('button',{name:'Apply changes',exact:true}).click();await page.waitForFunction(()=>document.querySelector('[data-lunara-site-studio]').getAttribute('data-workspace-state')==='live-saved');
  check(posts===1&&saved.selection.ids==='2,1','Apply submits only the chosen lineup.');
  await ready();check(JSON.parse(saved.artwork.overrides)[1].image_id===701,'Apply persists placement artwork separately from source metadata.');
  await page.locator('[data-oscars-lineup] li').first().getByRole('button',{name:'Remove',exact:true}).click();await page.locator('[data-oscars-lineup] li').first().getByRole('button',{name:'Remove',exact:true}).click();
  check((await page.locator('[data-oscars-status]').innerText()).includes('will be hidden'),'Empty manual selection clearly warns about hiding the section.');
  page.on('dialog',dialog=>dialog.accept());await page.getByRole('button',{name:'Discard changes',exact:true}).click();
  check(await page.locator('[data-oscars-lineup] li').count()===2,'Discard restores the last applied lineup.');
  await ready();await open(1);check((await row(1).locator('.lunara-editor-image-stage img').getAttribute('src')).includes('custom-701'),'Discard restores the last applied image configuration.');
  const invalidStates=await page.evaluate(state=>{
   const validate=window.LunaraSiteStudioHomeOscarsEditor.validateState,copy=()=>JSON.parse(JSON.stringify(state)),bad=[];
   let test=copy();test.artwork.overrides=' []';bad.push(test);
   test=copy();test.artwork.overrides=JSON.stringify({1:{image_id:701,fit:'cover',focal_x:101,focal_y:30,zoom:100}});bad.push(test);
   test=copy();test.artwork.overrides=JSON.stringify({1:{image_id:701,fit:'cover',focal_x:50,focal_y:30,zoom:113}});bad.push(test);
   test=copy();test.artwork.overrides=JSON.stringify({1:{image_id:701,fit:'cover',focal_x:50,focal_y:30,zoom:100,extra:true}});bad.push(test);
   test=copy();test.artwork.overrides=JSON.stringify({1:{fit:'cover',image_id:701,focal_x:50,focal_y:30,zoom:100}});bad.push(test);
   test=copy();test.artwork.overrides='{} ';bad.push(test);
   test=copy();test.artwork.overrides='{"9000000000":{"image_id":0,"fit":"cover","focal_x":50,"focal_y":50,"zoom":100},"8000000000":{"image_id":0,"fit":"cover","focal_x":50,"focal_y":50,"zoom":100}}';bad.push(test);
   return validate(state)&&bad.every(candidate=>!validate(candidate));
  },saved);check(invalidStates,'Client rejects malformed, noncanonical, unknown-key and out-of-range image settings.');
  for(const value of ['boolean','url','duplicate','image']){
   malformed=value;await page.getByRole('button',{name:'Search',exact:true}).click();await page.getByRole('button',{name:'Refresh lineup details',exact:true}).waitFor();
   check(await row(1).getByRole('combobox',{name:'Image fit',exact:true}).isDisabled(),'Invalid '+value+' metadata disables image editing without dropping settings.');
   malformed='';await page.getByRole('button',{name:'Refresh lineup details',exact:true}).click();await ready();
   check((await row(1).locator('.lunara-editor-image-stage img').getAttribute('src')).includes('custom-701'),'Retry recovers authoritative '+value+' metadata.');
  }
  holdItems=true;await page.getByRole('button',{name:'Search',exact:true}).click();await waitHeld(()=>releaseItems);
  await row(1).getByRole('button',{name:'Reset image framing',exact:true}).click();await ready();releaseItems();releaseItems=null;
  await open(1);check(await row(1).getByRole('slider',{name:'Horizontal focal point',exact:true}).inputValue()==='50'&&await row(1).getByRole('slider',{name:'Zoom',exact:true}).inputValue()==='100','Reset during a pending metadata read resumes editing at source framing.');
  await frame(1,701);
  await row(1).getByRole('button',{name:'Replace image',exact:true}).click();
  await page.locator('[data-field-path="selection.mode"]').selectOption('automatic');await ready();
  await page.evaluate(()=>{const frame=window.testMediaFrames.at(-1);frame.selected={id:702,url:'https://example.test/uploads/custom-702.svg'};frame.callback();});
  await open(1);check((await row(1).locator('.lunara-editor-image-stage img').getAttribute('src')).includes('custom-701'),'Mode changes reject a previous picker callback.');
  await page.locator('[data-field-path="selection.mode"]').selectOption('manual');await ready();
  missingImage=true;await page.getByRole('button',{name:'Search',exact:true}).click();await ready();await open(1);
  check((await row(1).innerText()).includes('Display image unavailable')&&!await row(1).getByRole('combobox',{name:'Image fit',exact:true}).isDisabled(),'A missing custom attachment is explicit and framing remains editable.');
  missingImage=false;await page.getByRole('button',{name:'Search',exact:true}).click();await ready();
  holdItems=true;await page.getByRole('button',{name:'Search',exact:true}).click();await waitHeld(()=>releaseItems);
  await page.locator('[data-field-path="selection.mode"]').selectOption('automatic');await ready();
  const lateResponse=kind==='facts'?page.waitForResponse(response=>new URL(response.url()).pathname.endsWith('/items')&&new URL(response.url()).searchParams.get('mode')==='manual'):null;
  releaseItems();releaseItems=null;if(lateResponse){await (await lateResponse).finished();}await page.evaluate(()=>new Promise(resolve=>requestAnimationFrame(()=>requestAnimationFrame(resolve))));
  check(await page.locator('[data-oscars-lineup] > li').first().getAttribute('data-oscar-artwork-id')==='3','A previous manual metadata response cannot overwrite the current automatic lineup.');
  await page.locator('[data-field-path="selection.mode"]').selectOption('manual');await ready();
  await row(1).getByRole('button',{name:'Remove',exact:true}).click();await ready();
  check(await page.getByRole('button',{name:'Reset unused image framing',exact:true}).isVisible(),'Removed story artwork remains available for explicit cleanup.');
  await page.getByRole('button',{name:'Reset unused image framing',exact:true}).click();await ready();
  await page.getByRole('button',{name:'Add: Published '+kind+' 1',exact:true}).click();await ready();await open(1);
  check((await row(1).locator('.lunara-editor-image-stage img').getAttribute('src')).includes('source-1'),'Reset unused image framing removes only the retained orphan override.');
  const artifacts=process.env.LUNARA_ARTWORK_SCREENSHOTS;
  if(artifacts){fs.mkdirSync(artifacts,{recursive:true});await page.getByRole('button',{name:'Desktop',exact:true}).click();await row(1).scrollIntoViewIfNeeded();await page.screenshot({path:path.join(artifacts,kind+'-editor-desktop.png')});await page.setViewportSize({width:680,height:1000});await row(1).scrollIntoViewIfNeeded();await page.screenshot({path:path.join(artifacts,kind+'-editor-narrow.png')});check(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth),'Narrow inspector avoids horizontal page overflow.');}
  check(errors.length===0,'Editor operations produce no browser errors.');await page.close();
 }
 }finally{await browser.close();}
 console.log('Homepage Oscars browser: '+checks+' checks passed.');
})().catch(error=>{console.error(error);process.exit(1);});
