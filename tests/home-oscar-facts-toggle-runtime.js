'use strict';

// Exercise the real Splide pilot with the Oscar Facts renderer. This catches
// the integration that the dependency-free fallback cannot see.
const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');
const { chromium } = require('playwright-core');

const root = path.resolve(__dirname, '..');
const rendered = spawnSync('php', [path.join(__dirname, 'home-oscar-mobile-art-runtime.php'), '--framing-fixture'], { encoding: 'utf8' });
if (rendered.status !== 0) throw new Error(rendered.stderr || rendered.stdout);
const styles = [
    'style.css',
    'assets/vendor/splide/splide-core.min.css',
    'assets/css/lunara-shell.css',
    'assets/css/lunara-home-modules.css',
    'assets/css/lunara-public-guardrails.css'
];
const scripts = [
    'assets/vendor/splide/splide.min.js',
    'assets/js/lunara-splide-pilot.js'
];
const html = `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">${styles.map(file => `<link rel="stylesheet" href="/${file}">`).join('')}<style>*,*::before,*::after{box-sizing:border-box}:root{--lunara-home-oscar-picks-gap:24px;--lunara-home-oscar-picks-card-min:460px;--lunara-home-oscar-picks-mobile-column:86%}body{margin:0;background:#07101b;color:#fafbfc}main.lunara-front-page{display:grid;width:calc(100% - 32px);max-width:1440px;margin:16px auto;padding:0}</style>${scripts.map(file => `<script defer src="/${file}"></script>`).join('')}</head><body class="home"><main class="lunara-front-page">${rendered.stdout}</main></body></html>`;
const executablePath = process.env.LUNARA_BROWSER_EXECUTABLE || [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    '/usr/bin/chromium',
    '/usr/bin/chromium-browser',
    '/usr/bin/google-chrome'
].find(fs.existsSync);

let checks = 0;
function check(value, message) {
    checks++;
    if (!value) throw new Error(message);
}

(async () => {
    const browser = await chromium.launch({ headless: true, executablePath });
    try {
        for (const width of [390, 1440]) for (const reducedMotion of ['no-preference', 'reduce']) {
            const page = await browser.newPage({ viewport: { width, height: 1000 }, reducedMotion });
            await page.route('https://oscar-facts-toggle.test/**', route => {
                const pathname = new URL(route.request().url()).pathname;
                if (pathname === '/') return route.fulfill({ contentType: 'text/html', body: html });
                const file = path.resolve(root, '.' + pathname);
                return file.startsWith(root + path.sep) && fs.existsSync(file)
                    ? route.fulfill({ contentType: file.endsWith('.js') ? 'text/javascript' : 'text/css', body: fs.readFileSync(file) })
                    : route.fulfill({ status: 404, body: '' });
            });
            await page.goto('https://oscar-facts-toggle.test/');
            await page.waitForFunction(() => document.querySelector('.lunara-oscar-facts-carousel')?.getAttribute('data-lunara-splide-pilot-active') === 'ready');
            const facts = page.locator('.lunara-oscar-facts-carousel');
            const toggle = facts.locator('.lunara-home-carousel-toggle');
            check(await toggle.count() === 1, `${width}/${reducedMotion}: Facts must expose one toggle.`);
            const box = await toggle.boundingBox();
            check(Boolean(box && box.width >= 44 && box.height >= 44), `${width}/${reducedMotion}: Facts toggle needs a 44px target.`);
            const initial = await toggle.evaluate(el => ({ label: el.getAttribute('aria-label') || '', play: getComputedStyle(el.querySelector('.splide__toggle__play')).display, pause: getComputedStyle(el.querySelector('.splide__toggle__pause')).display }));
            if (reducedMotion === 'reduce') {
                check(initial.play !== 'none' && initial.pause === 'none', `${width}/${reducedMotion}: reduced motion must start in the Play state.`);
                check(await toggle.isDisabled(), `${width}/${reducedMotion}: autoplay toggle must be disabled when reduced motion is requested.`);
            } else {
                check(initial.pause !== 'none' && initial.play === 'none', `${width}/${reducedMotion}: normal motion must start in the Pause state.`);
                await toggle.click();
                const paused = await toggle.evaluate(el => ({ label: el.getAttribute('aria-label') || '', play: getComputedStyle(el.querySelector('.splide__toggle__play')).display, pause: getComputedStyle(el.querySelector('.splide__toggle__pause')).display }));
                check(paused.play !== 'none' && paused.pause === 'none', `${width}/${reducedMotion}: clicking the toggle must expose Play (${JSON.stringify(paused)}).`);
                await toggle.click();
                const resumed = await toggle.evaluate(el => ({ label: el.getAttribute('aria-label') || '', play: getComputedStyle(el.querySelector('.splide__toggle__play')).display, pause: getComputedStyle(el.querySelector('.splide__toggle__pause')).display }));
                check(resumed.pause !== 'none' && resumed.play === 'none', `${width}/${reducedMotion}: clicking again must expose Pause.`);
            }
            await page.close();
        }
        console.log(`Homepage Oscar Facts toggle runtime passed: ${checks} checks across phone/desktop and normal/reduced motion.`);
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error.stack); process.exitCode = 1; });
