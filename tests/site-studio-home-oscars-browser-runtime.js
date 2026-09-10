'use strict';
const fs=require('fs'),path=require('path'),{spawnSync}=require('child_process');
let chromium;try{({chromium}=require('playwright'));}catch(error){({chromium}=require('playwright-core'));}
let checks=0;function check(ok,message){checks++;if(!ok){throw new Error(message);}}
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
  let saved=null,previewed=null,posts=0;
  await page.route('https://example.test/**',async route=>{
   const req=route.request(),url=new URL(req.url()),reply=body=>route.fulfill({status:200,contentType:'application/json',body:JSON.stringify(body)});
   if(url.pathname.includes('admin.php')){return route.fulfill({contentType:'text/html',body:html});}
   if(url.pathname.endsWith('/items')){const selected=(url.searchParams.get('ids')||'').split(',').filter(Boolean).map(Number);return reply({items:[1,2,3,99].filter((id)=>id!==99||selected.includes(id)).map(id=>({id,title:id===99?'Unavailable selection #99':'Published '+kind+' '+id,available:id!==99})),results:[1,2,3]});}
   if(url.pathname.endsWith('/preview')){previewed=req.postDataJSON().state;return reply({url:'https://example.test/?lunara_home_oscar_'+kind+'_preview=22222222-2222-4222-8222-222222222222'});}
   if(url.pathname.endsWith('/save')){posts++;saved=req.postDataJSON().state;return reply({state:saved,changed_sections:['oscar-'+kind],revision_id:'saved-1',timestamp:'2026-09-10 20:00:00'});}
   if(url.pathname.endsWith('/revisions')){return reply({revisions:[]});}
   return route.fulfill({contentType:'text/html',body:'<!doctype html><p>Private homepage fixture</p>'});
  });
  await page.goto('https://example.test/wp-admin/admin.php?page=lunara-site-studio&surface='+surface);
  await page.waitForSelector('[data-lunara-site-studio-ready="true"]');check(errors.length===0,'Workspace boots without script errors.');
  await page.locator('[data-field-path="selection.mode"]').selectOption('manual');
  await page.getByRole('button',{name:'Add: Published '+kind+' 1',exact:true}).click();await page.getByRole('button',{name:'Add: Published '+kind+' 2',exact:true}).click();
  await page.locator('[data-oscars-lineup] li').nth(1).getByRole('button',{name:'Move earlier',exact:true}).click();
  check((await page.locator('[data-oscars-lineup] li').first().innerText()).includes('Published '+kind+' 2'),'Keyboard-accessible Move changes lineup order.');
  await page.locator('[data-field-path="selection.mode"]').selectOption('automatic');await page.locator('[data-field-path="selection.mode"]').selectOption('manual');
  check(await page.locator('[data-oscars-lineup] li').count()===2,'Mode switching retains the list.');
  await page.getByRole('button',{name:'Mobile',exact:true}).click();check(await page.locator('iframe').getAttribute('width')==='390','Mobile preview uses the real 390px width.');
  await page.getByRole('button',{name:'Preview changes',exact:true}).click();await page.waitForFunction(()=>document.querySelector('[data-lunara-site-studio]').getAttribute('data-workspace-state')==='preview-current');
  check(posts===0&&previewed.selection.ids==='2,1','Preview uses the exact draft without saving public settings.');
  check((await page.locator('iframe').getAttribute('src')).includes('lunara_site_studio_instance='),'Private preview binds the iframe instance.');
  await page.getByRole('button',{name:'Apply changes',exact:true}).click();await page.waitForFunction(()=>document.querySelector('[data-lunara-site-studio]').getAttribute('data-workspace-state')==='live-saved');
  check(posts===1&&saved.selection.ids==='2,1','Apply submits only the chosen lineup.');
  await page.locator('[data-oscars-lineup] li').first().getByRole('button',{name:'Remove',exact:true}).click();await page.locator('[data-oscars-lineup] li').first().getByRole('button',{name:'Remove',exact:true}).click();
  check((await page.locator('[data-oscars-status]').innerText()).includes('will be hidden'),'Empty manual selection clearly warns about hiding the section.');
  page.on('dialog',dialog=>dialog.accept());await page.getByRole('button',{name:'Discard changes',exact:true}).click();
  check(await page.locator('[data-oscars-lineup] li').count()===2,'Discard restores the last applied lineup.');
  check(errors.length===0,'Editor operations produce no browser errors.');await page.close();
 }
 }finally{await browser.close();}
 console.log('Homepage Oscars browser: '+checks+' checks passed.');
})().catch(error=>{console.error(error);process.exit(1);});
