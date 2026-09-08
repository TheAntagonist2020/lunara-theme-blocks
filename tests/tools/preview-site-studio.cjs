// Local review fixture: real PHP inspector and shipped editor assets; no WordPress writes.
'use strict';
const http = require('http'), fs = require('fs'), path = require('path'), {spawnSync} = require('child_process');
const root = path.resolve(__dirname, '../..'), port = Number(process.env.LUNARA_STUDIO_PREVIEW_PORT || 8042);
const origin = `http://127.0.0.1:${port}`, saved = {}, revisions = {}, snapshots = {};
const art = `${origin}/tests/fixtures/home-carousel-art.svg`;
const items = [
 {id:10,title:'The Dog Stars — Going Nowhere Beautifully',type:'review',available:true,date_label:'Sep 8, 2026',image_url:art,image_source:'Review artwork',excerpt:'A sample review for testing the shared editor.',kicker:'Latest Review',cta:'Read the review'},
 {id:20,title:'The Festival Dispatch: What We Are Watching This Week',type:'journal',available:true,date_label:'Sep 7, 2026',image_url:art,image_source:'Journal artwork',excerpt:'A sample Journal article for arranging your homepage.',kicker:'Journal',cta:'Read the story'},
 {id:30,title:'A Conversation About the Films That Stay With Us',type:'journal',available:true,date_label:'Sep 6, 2026',image_url:'',image_source:'No source artwork',excerpt:'This sample deliberately has no artwork.',kicker:'Conversation',cta:'Read the story'}
];
function json(response, body, status=200) { response.writeHead(status, {'Content-Type':'application/json'}); response.end(JSON.stringify(body)); }
function slide(id) { return {post_id:id,image_id:0,headline:'',excerpt:'',kicker:'',cta:'',overlay:0,focal_x:50,focal_y:30,zoom:100,fit:'cover'}; }
const server = http.createServer(async (request,response) => {
 const url = new URL(request.url,origin), kind = url.searchParams.get('surface')==='journal-carousel'||url.pathname.includes('journal-carousel')?'journal':'hero';
 if (url.pathname==='/wp-admin/admin.php') {
  const rendered=spawnSync(process.env.LUNARA_PHP_EXECUTABLE||'php',[path.join(root,'tests/home-carousel-settings-runtime.php'),'--fixture',...(kind==='journal'?['--journal']:[])],{encoding:'utf8'});
  if(rendered.status!==0){response.writeHead(500);response.end(rendered.stdout+rendered.stderr);return;}
  saved[kind] ||= {adopted:true,mode:'manual',heading:kind==='hero'?'Featured stories':'The Journal',autoplay:1,interval:7,overlay:100,slides:(kind==='hero'?[10,20,30]:[20,30]).map(slide)};
  const css=['lunara-site-studio.css','lunara-editor-controls.css','lunara-site-studio-carousels.css'].map(file=>fs.readFileSync(path.join(root,'assets/css',file),'utf8')).join('\n');
  let html=rendered.stdout.replaceAll('https://example.test',origin).replace(/(<script[^>]+id="lunara-site-studio-state"[^>]*>)[\s\S]*?(<\/script>)/,(_m,start,end)=>start+JSON.stringify(saved[kind])+end);
  html=html.replace('</head>',`<meta name="viewport" content="width=device-width, initial-scale=1"><style>body{margin:0;padding:20px;font:14px/1.5 system-ui;background:#f0f0f1}button,input,select,textarea{font:inherit}button{border:1px solid #a7b0bb;border-radius:5px;background:#f6f7f7;padding:8px 12px}.button-primary{background:#142033;color:#fff}input,select,textarea{border:1px solid #a7b0bb;border-radius:5px;padding:8px}${css}</style></head>`);
  html=html.replace('<body>','<body><p style="background:#fff1cb;padding:12px;border-radius:8px"><strong>Local interactive preview.</strong> These are sample stories. Apply changes saves only in this temporary preview. <a href="?surface=hero-carousel">Hero</a> · <a href="?surface=journal-carousel">Journal</a></p>');
  response.writeHead(200,{'Content-Type':'text/html'});response.end(html);return;
 }
 if(url.pathname==='/controller.js') {
  response.writeHead(200,{'Content-Type':'application/javascript'});response.end(['lunara-editor-controls.js','lunara-site-studio-carousels.js','lunara-site-studio.js'].map(file=>fs.readFileSync(path.join(root,'assets/js',file),'utf8')).join('\n'));return;
 }
 if(url.pathname==='/tests/fixtures/home-carousel-art.svg'){response.writeHead(200,{'Content-Type':'image/svg+xml'});response.end(fs.readFileSync(path.join(root,'tests/fixtures/home-carousel-art.svg')));return;}
 if(url.pathname.startsWith('/wp-json/lunara-site-studio/')) {
  let raw='';for await(const chunk of request){raw+=chunk;}
  let body;try{body=raw?JSON.parse(raw):{};}catch(error){return json(response,{message:'Invalid preview request'},400);}
  const action=url.pathname.split('/').pop(), eligible=items.filter(item=>kind==='hero'||item.type==='journal');
  if(action==='metadata'){return json(response,{items:Object.fromEntries(items.map(item=>[item.id,item])),images:{},automatic:eligible.map(item=>item.id)});}
  if(action==='search'){return json(response,{items:eligible.filter(item=>item.title.toLowerCase().includes((url.searchParams.get('search')||'').toLowerCase()))});}
  if(action==='preview'){return json(response,{url:`${origin}/?lunara_${kind}_carousel_preview=123e4567-e89b-42d3-a456-426614174111`});}
  if(action==='revisions'){return json(response,{revisions:revisions[kind]||[]});}
  if(action==='save') {
   const id=String(Date.now());snapshots[id]=saved[kind];revisions[kind]=[{id,timestamp:new Date().toISOString(),action:'save'},...(revisions[kind]||[])].slice(0,12);saved[kind]={...body.state,adopted:true};
   return json(response,{state:saved[kind],changed_sections:[kind==='hero'?'hero':'dispatch'],revision_id:id,timestamp:new Date().toISOString()});
  }
  if(action==='restore'&&snapshots[body.revision_id]){saved[kind]=snapshots[body.revision_id];return json(response,{state:saved[kind],safety_revision_id:String(Date.now()),timestamp:new Date().toISOString()});}
  return json(response,{message:'Preview route unavailable'},404);
 }
 response.writeHead(200,{'Content-Type':'text/html'});response.end(`<html><body style="margin:0;background:#0b1c2a;color:#dfbd6c;font:18px Georgia;min-height:1100px"><div class="lunara-cinematic-hero-bg" style="height:360px;background:url('${art}') center/cover"></div><main style="padding:32px"><h1>Homepage preview</h1><p>This local fixture exercises editor controls. The WordPress private preview supplies the complete homepage after deployment.</p></main></body></html>`);
});
server.listen(port,'127.0.0.1',()=>console.log(`Local Site Studio preview: ${origin}/wp-admin/admin.php?surface=hero-carousel`));
