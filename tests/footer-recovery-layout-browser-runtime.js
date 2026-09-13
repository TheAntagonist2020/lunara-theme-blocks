'use strict';
// Real public templates and active CSS; no frontend JavaScript or network dependency.
const fs = require('fs'), path = require('path'), { execFileSync } = require('child_process');
const { chromium } = require('playwright-core');
const root = path.resolve(__dirname, '..');
const executablePath = process.env.LUNARA_BROWSER_EXECUTABLE || ['C:/Program Files/Google/Chrome/Application/chrome.exe', 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', '/usr/bin/chromium', '/usr/bin/chromium-browser', '/usr/bin/google-chrome'].find(fs.existsSync);
const fixtures = JSON.parse(execFileSync('php', [path.join(__dirname, 'footer-recovery-layout-fixture.php')], { encoding: 'utf8' }));
// Optional scratch stylesheet supports isolated mutation proofs without changing
// the shared candidate while the full release suite is running.
const css = ['style.css', 'assets/css/lunara-shell.css', 'assets/css/lunara-review-components.css', 'assets/css/lunara-public-guardrails.css'].map(file => fs.readFileSync(file === 'style.css' && process.env.LUNARA_RECOVERY_STYLE_SOURCE ? process.env.LUNARA_RECOVERY_STYLE_SOURCE : path.join(root, file), 'utf8')).join('\n');
let checks = 0;
const failures = [], metrics = [];
function check(condition, message, evidence) { checks++; if (!condition) failures.push({ message, evidence }); }
(async () => {
    const browser = await chromium.launch({ headless: true, executablePath });
    try {
        for (const width of [320, 390, 768, 1440]) {
            const scenarios = Object.entries(fixtures);
            // A native input's intrinsic width depends on platform font metrics.
            // Exercise a larger HTML size hint, without replacing any layout CSS,
            // so the Linux CI failure is also reproducible on Windows browsers.
            if (width <= 390) for (const name of ['404', '404-long', 'search-blank', 'search-no-results']) {
                scenarios.push([`${name}-wide-native`, { ...fixtures[name], native_size: 40 }]);
            }
            for (const [name, fixture] of scenarios) {
                const page = await browser.newPage({ viewport: { width, height: 1000 }, javaScriptEnabled: false });
                await page.route('**/*', route => route.abort());
                // Blocksy supplies this reset and WordPress screen-reader utility.
                // The main opener/closer, public body
                // and footer are extracted from their actual production templates.
                let document = fixture.html.replace('</head>', `<meta name="viewport" content="width=device-width,initial-scale=1"><style>*,*::before,*::after{box-sizing:border-box}body{margin:0}.screen-reader-text{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(1px,1px,1px,1px);white-space:nowrap;border:0}</style><style>${css}</style>${fixture.authority}</head>`).replace('<body>', `<body class="${fixture.body_class}">`);
                if (fixture.native_size) document = document.replace(/<input\b(?=[^>]*\btype="search")/g, `<input size="${fixture.native_size}"`);
                await page.setContent(document, { waitUntil: 'load' });
                const data = await page.evaluate(() => {
                    const rect = node => { const r=node.getBoundingClientRect(); return {left:r.left,right:r.right,top:r.top,bottom:r.bottom,width:r.width,height:r.height}; };
                    const label = node => node.tagName.toLowerCase() + (node.className ? '.' + String(node.className).trim().replace(/\s+/g,'.') : '');
                    const visible = node => node.getBoundingClientRect().height>0 && getComputedStyle(node).visibility!=='hidden' && !node.closest('.screen-reader-text');
                    const text = [...document.querySelectorAll('h1,h2,h3,h4,p,strong,span,a')].filter(node => visible(node) && !node.children.length && node.textContent.trim());
                    const clipped = text.flatMap(node => {
                        const range=document.createRange();range.selectNodeContents(node);const box=rect(node),ink=range.getBoundingClientRect();
                        const s=getComputedStyle(node);
                        const clip = (s.overflowY==='hidden'||s.overflowY==='clip') && ink.bottom>box.bottom+2;
                        return ink.left<box.left-2||ink.right>box.right+2||clip ? [{selector:label(node),text:node.textContent,box,ink:{left:ink.left,right:ink.right,bottom:ink.bottom},overflow:s.overflow,clamp:s.webkitLineClamp}] : [];
                    });
                    const controls=[...document.querySelectorAll('a[href],button,input:not([type=hidden])')].filter(visible).map(node=>({selector:label(node),text:node.textContent.trim(),...rect(node)}));
                    const overflow=[...document.querySelectorAll('main *,footer *')].filter(visible).filter(node=>{const r=rect(node);return r.left< -1||r.right>innerWidth+1;}).map(node=>({selector:label(node),...rect(node)}));
                    return {
                        width:innerWidth,scrollWidth:document.documentElement.scrollWidth,overflow,clipped,controls,
                        main:document.querySelectorAll('main').length,h1:document.querySelectorAll('h1').length,
                        footer:document.querySelectorAll('footer.lunara-site-footer').length,
                        columns:[...document.querySelectorAll('.lunara-footer-nav-col')].map(node=>node.querySelectorAll('a').length),
                        footerLabels:[...document.querySelectorAll('.lunara-footer-nav-col a')].map(node=>node.textContent),
                        bodyText:document.body.textContent,
                        titles:[...document.querySelectorAll('.lunara-search-result-title')].map(node=>node.textContent),
                        excerpts:[...document.querySelectorAll('.lunara-search-result-copy')].map(node=>({text:node.textContent,...rect(node)})),
                        images:document.querySelectorAll('.lunara-search-result-card img').length,
                        forms:[...document.querySelectorAll('form[role=search]')].map(node=>{
                            const box=rect(node),style=getComputedStyle(node);
                            const left=box.left+parseFloat(style.borderLeftWidth)+parseFloat(style.paddingLeft),right=box.right-parseFloat(style.borderRightWidth)-parseFloat(style.paddingRight);
                            return {action:node.getAttribute('action'),method:node.method,input:node.querySelector('input')?.name,nativeSize:node.querySelector('input')?.size,box,
                                outside:[...node.querySelectorAll('input:not([type=hidden]),button')].filter(visible).map(child=>({selector:label(child),...rect(child)})).filter(child=>child.left<left-1||child.right>right+1)};
                        }),
                        footerBox:rect(document.querySelector('.lunara-site-footer')),
                    };
                });
                const tag=`${name}@${width}`;
                check(data.main===1&&data.h1===(fixture.footer_only?0:1)&&data.footer===1, `${tag}: actual templates retain landmarks`, data);
                check(data.scrollWidth<=width+1&&data.overflow.length===0, `${tag}: no horizontal overflow`, data.overflow);
                check(data.clipped.length===0, `${tag}: complete headings, labels and configured copy remain readable`, data.clipped);
                check(data.controls.length>0&&data.controls.every(item=>item.height>=43.9&&item.width>=43.9), `${tag}: every public link, input and button has a44px target`, data.controls.filter(item=>item.height<43.9||item.width<43.9));
                check(JSON.stringify(data.columns)===JSON.stringify(fixture.expected_columns), `${tag}: exact zero/one/twelve or inherited footer lists`, data.columns);
                if (fixture.footer_only) check(JSON.stringify(data.footerLabels)===JSON.stringify(fixture.footer_labels), `${tag}: complete long labels retain their saved order`, data.footerLabels);
                if (fixture.saved_copy.length) check(fixture.saved_copy.every(copy=>data.bodyText.includes(copy)), `${tag}: all customized recovery copy reaches the actual public template`);
                if (!fixture.footer_only) {
                    check(data.forms.length>0&&data.forms.every(form=>form.action==='https://example.test/search/'&&form.method==='get'&&form.input==='q'), `${tag}: recovery forms work without JavaScript`, data.forms);
                    check(data.forms.every(form=>form.outside.length===0), `${tag}: native controls stay within the form content box`, data.forms);
                    if (fixture.native_size) check(data.forms.every(form=>form.nativeSize===fixture.native_size), `${tag}: wider native input hint is active`, data.forms);
                }
                if (name==='search-query') {
                    check(JSON.stringify(data.titles)===JSON.stringify(fixture.titles), `${tag}: long source headlines remain complete`, data.titles);
                    check(data.excerpts.length===3&&data.excerpts.every(row=>row.text.includes('word50')&&!row.text.includes('word51'))&&data.images===0, `${tag}: configured excerpts and missing-art cards render without empty artwork`, data.excerpts);
                }
                // Native tab navigation remains available even with scripts disabled.
                await page.keyboard.press('Tab');
                check(await page.evaluate(()=>document.activeElement.matches('a[href],button,input')), `${tag}: keyboard reaches a native public control`);
                if(process.env.LUNARA_FOOTER_RECOVERY_ARTIFACT_DIR&&[390,1440].includes(width)) {
                    fs.mkdirSync(process.env.LUNARA_FOOTER_RECOVERY_ARTIFACT_DIR,{recursive:true});
                    await page.screenshot({path:path.join(process.env.LUNARA_FOOTER_RECOVERY_ARTIFACT_DIR,`${name}-${width}.png`),fullPage:true});
                }
                metrics.push({case:tag,footer:data.footerBox,controls:data.controls.length,columns:data.columns});
                await page.close();
            }
        }
    } finally { await browser.close(); }
    if(process.env.LUNARA_FOOTER_RECOVERY_ARTIFACT_DIR)fs.writeFileSync(path.join(process.env.LUNARA_FOOTER_RECOVERY_ARTIFACT_DIR,'layout-metrics.json'),JSON.stringify({metrics,failures,checks},null,2));
    if(failures.length) throw new Error(`${failures.length} layout assertions failed\n${JSON.stringify(failures,null,2)}`);
    process.stdout.write(`Footer/Search/404 public layout: ${metrics.length} actual-template scenarios, ${checks} assertions passed with JavaScript disabled.\n`);
})().catch(error=>{console.error(error);process.exit(1);});
