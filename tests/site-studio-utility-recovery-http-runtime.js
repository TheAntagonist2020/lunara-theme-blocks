'use strict';
// Actual PHP request guard, Search router, templates and response headers; no production requests.
const assert=require('node:assert/strict');
const {spawn}=require('node:child_process');
const net=require('node:net');
const path=require('node:path');
const root=path.resolve(__dirname,'..');
const token='10000000-0000-4000-8000-000000000001';
const instance='123e4567-e89b-42d3-a456-426614174111:1';
const query=kind=>new URLSearchParams({[kind==='search'?'lunara_utility_search_preview':'lunara_404_preview']:token,lunara_site_studio_instance:instance}).toString();
let checks=0,server,serverLog='';
function check(condition,message){++checks;assert.ok(condition,message);}
async function freePort(){const socket=net.createServer();await new Promise(resolve=>socket.listen(0,'127.0.0.1',resolve));const port=socket.address().port;await new Promise(resolve=>socket.close(resolve));return port;}
(async()=>{
 const port=await freePort(),origin=`http://127.0.0.1:${port}`;
 server=spawn(process.env.PHP_BINARY||'php',['-S',`127.0.0.1:${port}`,'tests/site-studio-utility-recovery-http-fixture.php'],{cwd:root,windowsHide:true,stdio:['ignore','pipe','pipe']});
 server.stdout.on('data',chunk=>serverLog+=chunk);server.stderr.on('data',chunk=>serverLog+=chunk);
 let bootError;server.on('error',error=>{bootError=error;});
 async function request(route,scenario='public',expected=200){const response=await fetch(origin+route,{headers:{'X-Lunara-Fixture':scenario},redirect:'manual',signal:AbortSignal.timeout(15000)});const html=await response.text();check(response.status===expected,`${scenario} ${route}: expected${expected}, got${response.status}\n${html}\n${serverLog}`);return {response,html};}
 for(let attempt=0;attempt<60;attempt++){if(bootError)throw bootError;try{await fetch(origin+'/ready',{signal:AbortSignal.timeout(1000)});break;}catch(error){if(attempt===59)throw error;await new Promise(resolve=>setTimeout(resolve,50));}}
 function privateHeaders(result){check(/private.*no-store/.test(result.response.headers.get('cache-control')||''),'Private and invalid previews forbid storage');check(result.response.headers.get('x-robots-tag')==='noindex, nofollow','Private preview forbids indexing');check(result.response.headers.get('referrer-policy')==='no-referrer','Private token cannot leak by referrer');}
 function landmark(result){check((result.html.match(/<main\b/g)||[]).length===1&&(result.html.match(/<\/main>/g)||[]).length===1,'Actual header/template/footer have one main landmark');}
 const public404=await request('/ordinary-missing-page/','public',404);check(public404.html.includes('Public recovery heading'),'Ordinary404 renders public content');landmark(public404);
 const missing=await request('/definitely-not-a-real-lunara-route/?'+query('404'),'404',404);privateHeaders(missing);landmark(missing);check(missing.html.includes('Private recovery heading')&&!missing.html.includes('Public recovery heading'),'Valid404 token reaches candidate template while staying404');check(missing.html.includes('Private first line\nPrivate second line'),'Private404 preserves multiline copy');check(missing.html.includes('lunara-404-page--primary-journal'),'Private404 candidate controls recovery order');check(missing.response.headers.get('x-fixture-public-state-unchanged')==='1','Private404 does not persist candidate');
 const after404=await request('/ordinary-missing-page/','public',404);check(after404.html.includes('Public recovery heading')&&!after404.html.includes('Private recovery heading'),'Subsequent anonymous404 remains public');
 for(const scenario of ['existing-route','denied','anonymous']){privateHeaders(await request('/definitely-not-a-real-lunara-route/?'+query('404'),scenario,403));}
 privateHeaders(await request('/different-missing-route/?'+query('404'),'404',403));
 privateHeaders(await request('/definitely-not-a-real-lunara-route/?'+query('404')+'&extra=1','404',403));
 const publicStart=await request('/search/');check(publicStart.html.includes('>Public Search start</h1>'),'Public Search start uses adopted heading');landmark(publicStart);
 const start=await request('/search/?'+query('search'),'search');privateHeaders(start);landmark(start);check(start.html.includes('>Private Search start</h1>')&&start.html.includes('Private Search desk'),'Fixed Search start token renders both candidate copy controls');check(start.response.headers.get('x-fixture-public-state-unchanged')==='1','Search preview leaves settings unchanged');
 const results=await request('/search/?'+query('search')+'&q=Lunara','search');privateHeaders(results);check(results.html.includes('>Search Results</h1>')&&results.html.includes('Private Search desk'),'Same Search token renders fixed Results case');check(results.html.includes('word10')&&!results.html.includes('word11'),'Private Results use candidate excerpt count');
 const legacy=await request('/search/?'+query('search'),'legacy');privateHeaders(legacy);check(legacy.html.includes('lunara-search-page--focus-journal')&&legacy.html.includes('>Public Search start</h1>')&&!legacy.html.includes('Private Search start'),'Authenticated old9token changes old presentation without inventing copy');
 const legacyResults=await request('/search/?'+query('search')+'&q=Lunara','legacy');privateHeaders(legacyResults);check(legacyResults.html.includes('word22')&&!legacyResults.html.includes('word23'),'Old token preserves current public excerpt length');
 for(const suffix of ['&q=Other','&q=','&s=Lunara','&q=Lunara&q=Lunara','&extra=1']){privateHeaders(await request('/search/?'+query('search')+suffix,'search',403));}
 privateHeaders(await request('/search/?'+query('404'),'404',403));
 privateHeaders(await request('/search/?'+query('search').replace(token,'10000000-0000-4000-8000-000000000099'),'search',403));
 const publicResults=await request('/search/?q=Lunara');check(publicResults.html.includes('Public Search desk')&&!publicResults.html.includes('Private Search desk'),'Subsequent public results never use private copy');
 console.log(`Search/404 HTTP lifecycle passed: ${checks} checks.`);
})().catch(error=>{console.error(error);process.exitCode=1;}).finally(()=>{if(server)server.kill();});
