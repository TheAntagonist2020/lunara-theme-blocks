'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const {chromium} = require('playwright-core');
const root = path.resolve(__dirname, '..');
const fixture = JSON.parse(execFileSync('php', [path.join(__dirname, 'header-navigation-runtime.php'), '--fixture'], {encoding:'utf8'}));
(async () => {
    const executablePath=process.env.LUNARA_BROWSER_EXECUTABLE || ['C:/Program Files/Google/Chrome/Application/chrome.exe','C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe','/usr/bin/chromium','/usr/bin/chromium-browser','/usr/bin/google-chrome'].find(fs.existsSync);
    const browser = await chromium.launch({headless:true, executablePath});
    let checks = 0;
    try {
        for (const scripts of [true, false]) for (const width of [320,390,768,1440]) {
            const context = await browser.newContext({viewport:{width,height:900}, javaScriptEnabled:scripts, reducedMotion:'reduce'});
            const page = await context.newPage();
            await page.setContent(`<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>*{box-sizing:border-box}body{margin:0;background:#09151e;color:#f4efe3}main{padding:20px}</style>${fixture.css}</head><body class="lunara-header-takeover">${fixture.html}<main><h1>A complete article headline</h1><p>Readable content below the shared header.</p></main>${scripts?'<script>'+fs.readFileSync(path.join(root,'assets/js/lunara-header.js'),'utf8')+'</script>':''}</body></html>`);
            assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth <= innerWidth),true,`No overflow ${width}, JS ${scripts}`); ++checks;
            const search = page.locator('[data-lunara-search-open]');
            const box = await search.boundingBox();
            assert.ok(box.width >=44 && box.height >=44, 'Search touch target'); ++checks;
            assert.equal(await search.getAttribute('href'), 'https://lunara.test/search/'); ++checks;
            if (!scripts || width>1020) {
                assert.equal(await page.locator('.lunara-header-nav').isVisible(), true); ++checks;
                assert.equal(await page.locator('.lunara-header-nav a[aria-current]').textContent(),'Reviews'); ++checks;
            }
            if (scripts && width<1020) {
                const opener=page.locator('[data-lunara-nav-open]');
                await opener.focus(); await page.keyboard.press('Enter');
                await page.locator('#lunara-offcanvas').waitFor({state:'visible'});
                assert.equal(await opener.getAttribute('aria-expanded'),'true'); ++checks;
                assert.equal(await page.locator('.lunara-offcanvas-nav a').count(),4); ++checks;
                const closer=page.locator('.lunara-offcanvas-close');
                for(const control of [opener,closer]) {const target=await control.boundingBox();assert.ok(target.width>=44&&target.height>=44,'Navigation touch target');++checks;}
                await closer.focus(); await page.keyboard.press('Shift+Tab');
                assert.equal(await page.locator('.lunara-offcanvas-nav a').last().evaluate(e=>e===document.activeElement),true,'Focus wraps backwards'); ++checks;
                await page.keyboard.press('Tab');
                assert.equal(await closer.evaluate(e=>e===document.activeElement),true,'Focus wraps forwards'); ++checks;
                await page.keyboard.press('Escape');
                await page.locator('#lunara-offcanvas').waitFor({state:'hidden'});
                assert.equal(await opener.evaluate(e=>e===document.activeElement),true); ++checks;
            }
            if(process.env.LUNARA_HEADER_ARTIFACTS) {
                fs.mkdirSync(process.env.LUNARA_HEADER_ARTIFACTS,{recursive:true});
                await page.screenshot({path:path.join(process.env.LUNARA_HEADER_ARTIFACTS,`header-${width}-${scripts?'js':'no-js'}.png`),fullPage:true});
            }
            await context.close();
        }
        console.log(`Header browser runtime passed: ${checks} checks.`);
    } finally {await browser.close();}
})().catch(error=>{console.error(error);process.exitCode=1;});
