'use strict';
const fs = require('fs');
const { fixture } = require('./site-studio-browser-fixture');
let chromium;
try { ({ chromium } = require('playwright')); } catch (_) { ({ chromium } = require('playwright-core')); }
const assert = (condition, message, evidence) => {
 if (!condition) throw new Error(`${message}\n${JSON.stringify(evidence || {}, null, 2)}`);
};

// A viewport-sized hero followed by a long page reproduces the live feedback
// loop: using scrollHeight as iframe height enlarges both on every resize.
const content = `<!doctype html><meta name="viewport" content="width=device-width">
<style>body{margin:0}header{height:100vh;background:#142033}main{height:2400px}
img{display:block;width:100%;height:100%;object-fit:cover}footer{height:100px}</style>
<header><img alt="Preview artwork" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1600' height='900'%3E%3Crect width='100%25' height='100%25' fill='%239b741d'/%3E%3C/svg%3E"></header>
<main>Long editorial page</main><footer><a href="#">End of preview</a></footer>`;

(async () => {
 const executablePath = process.env.LUNARA_BROWSER_EXECUTABLE;
 assert(executablePath && fs.existsSync(executablePath), 'A real Chromium executable is required.');
 const browser = await chromium.launch({ headless: true, executablePath });
 const evidence = [];
 try {
  for (const observer of [true, false]) {
   const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
   const html = fixture('global-design');
   const requests = [];
   let baseline;
   if (!observer) await page.addInitScript(() => { window.ResizeObserver = undefined; });
   await page.route('https://example.test/**', async route => {
    const request = route.request(), url = new URL(request.url());
    if (url.pathname === '/wp-admin/admin.php') return route.fulfill({ contentType: 'text/html', body: html });
    if (url.pathname.startsWith('/wp-json/')) {
     requests.push(url.pathname);
     let payload = {};
     if (url.pathname.endsWith('/preview')) payload = { url: 'https://example.test/?lunara_global_design_preview=123e4567-e89b-42d3-a456-426614174111' };
     if (url.pathname.endsWith('/save')) payload = { state: baseline, changed_sections: [], revision_id: 'saved-viewport', timestamp: '2026-09-11 12:00:00' };
     if (url.pathname.endsWith('/revisions')) payload = { revisions: [] };
     return route.fulfill({ contentType: 'application/json', body: JSON.stringify(payload) });
    }
    return route.fulfill({ contentType: 'text/html', body: content });
   });
   await page.goto('https://example.test/wp-admin/admin.php?page=lunara-site-studio&surface=global-design');
   await page.waitForSelector('[data-lunara-site-studio-ready="true"]');
   baseline = await page.locator('#lunara-site-studio-state').evaluate(e => JSON.parse(e.textContent));

   async function measure(name, width, height) {
    const samples = await page.evaluate(async () => {
     const frame = document.querySelector('iframe'), samples = [];
     for (let i = 0; i < 8; i++) {
      await new Promise(resolve => requestAnimationFrame(resolve));
      const flow = document.querySelector('.lunara-site-studio-preview-flow');
      const box = frame.getBoundingClientRect();
      samples.push({ width: frame.contentWindow.innerWidth, height: frame.contentWindow.innerHeight,
       attr: Number(frame.getAttribute('height')), documentHeight: frame.contentDocument.documentElement.scrollHeight,
       heroHeight: frame.contentDocument.querySelector('header').clientHeight,
       imageHeight: frame.contentDocument.querySelector('img').clientHeight,
       flowHeight: flow.getBoundingClientRect().height, frameHeight: box.height,
       overflow: document.documentElement.scrollWidth > innerWidth + 1 });
     }
     return samples;
    });
    assert(samples.every(s => s.width === width && s.height === height && s.attr === height), `${name}: preview viewport must stay at the selected device dimensions.`, samples);
    assert(samples.every(s => s.documentHeight > height && s.heroHeight === height && s.imageHeight === height), `${name}: tall content must scroll inside a stable viewport; artwork must retain its viewport sizing.`, samples);
    assert(samples.every(s => Math.abs(s.flowHeight - s.frameHeight) < 1 && !s.overflow), `${name}: scaled preview must fit the editor without extra blank canvas.`, samples);
    evidence.push({ observer, name, width, height, stableSamples: samples.length });
   }

   for (const [name, width, height] of [['desktop',1440,900],['tablet',768,1024],['mobile',390,844],['desktop',1440,900]]) {
    await page.click(`[data-preview-width="${name}"]`);
    await measure(name, width, height);
   }
   assert(requests.length === 0, 'Changing preview devices must not save settings or issue REST requests.');
   await page.click('[data-preview-width="mobile"]');
   await page.fill('[data-field-path="colors.gold"]', '#abcdef');
   await page.click('[data-action="preview"]');
   await page.waitForFunction(() => document.querySelector('iframe').contentDocument.querySelector('footer') && document.querySelector('[data-lunara-site-studio]').dataset.workspaceState === 'preview-current');
   await measure('private mobile preview', 390, 844);
   await page.frameLocator('iframe').getByRole('link', { name: 'End of preview' }).focus();
   assert(await page.locator('iframe').evaluate(e => e.contentWindow.scrollY > 500), 'Keyboard users must be able to reach content below the preview viewport.');
   await page.setViewportSize({ width: 390, height: 844 });
   await page.waitForTimeout(120); // The no-ResizeObserver path intentionally debounces resize by 80ms.
   await measure('narrow editor', 390, 844);
   await page.click('[data-action="save"]');
   await page.waitForFunction(() => document.querySelector('[data-lunara-site-studio]').dataset.workspaceState === 'live-saved' && !new URL(document.querySelector('iframe').src).search);
   await measure('applied preview', 390, 844);
   await page.close();
  }
  console.log(JSON.stringify({ checks: evidence.length, evidence }));
 } finally { await browser.close(); }
})().catch(error => { console.error(error.stack || error); process.exitCode = 1; });
