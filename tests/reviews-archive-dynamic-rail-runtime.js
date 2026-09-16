'use strict';

// Exercise the Reviews companion rail with the real theme-owned runtime. The
// fixture is deliberately small so the test covers the control contract
// without depending on a WordPress database or a live editorial lineup.
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright-core');

const root = path.resolve(__dirname, '..');
const runtimePath = path.join(root, 'assets/js/lunara-dynamic-rails.js');
const runtime = fs.readFileSync(runtimePath, 'utf8');
const executablePath = process.env.LUNARA_BROWSER_EXECUTABLE || [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    '/usr/bin/chromium',
    '/usr/bin/chromium-browser',
    '/usr/bin/google-chrome',
].find(fs.existsSync);

const html = `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${fs.readFileSync(path.join(root,'style.css'),'utf8')}\n${fs.readFileSync(path.join(root,'assets/css/lunara-review-archive.css'),'utf8')}</style><style>
    * { box-sizing: border-box; }
    body { margin: 0; padding: 16px; background: #07101b; color: #fafbfc; }
    .lunara-review-archive-page { width: min(100%, 360px); margin: 0 auto; }
    .lunara-review-archive-rail-controls { display: flex; gap: 8px; }
    .lunara-review-archive-page .lunara-review-archive-rail-track { display: flex; gap: 16px !important; width: 300px !important; max-width: 100%; overflow-x: auto; scroll-snap-type: x mandatory; }
    .lunara-review-archive-page .lunara-review-archive-rail-item { flex: 0 0 260px !important; height: 120px; scroll-snap-align: start; background: #102238; }
    .lunara-review-archive-rail-dots { display: flex; flex-wrap: wrap; }
    @media (prefers-reduced-motion: reduce) { .lunara-review-archive-rail-track { scroll-behavior: auto; } }
</style></head><body><main class="lunara-review-archive-page"><section class="lunara-review-archive-dynamic-rail" data-lunara-dynamic-rail data-lunara-dynamic-rail-autoplay="800" aria-label="Current companion review files"><div class="lunara-review-archive-rail-controls"><button type="button" class="lunara-review-archive-rail-control" data-lunara-dynamic-rail-prev aria-label="Previous companion review">‹</button><button type="button" class="lunara-review-archive-rail-control" data-lunara-dynamic-rail-next aria-label="Next companion review">›</button><button type="button" class="lunara-review-archive-rail-toggle" data-lunara-dynamic-rail-toggle aria-pressed="false">Pause</button></div><div class="lunara-review-archive-rail-track" data-lunara-dynamic-rail-track tabindex="0"><div class="lunara-review-archive-rail-item" data-lunara-dynamic-rail-item>One</div><div class="lunara-review-archive-rail-item" data-lunara-dynamic-rail-item>Two</div><div class="lunara-review-archive-rail-item" data-lunara-dynamic-rail-item>Three</div></div><div class="lunara-review-archive-rail-dots"><button type="button" class="lunara-review-archive-rail-dot is-active" data-lunara-dynamic-rail-dot data-lunara-dynamic-rail-index="0" aria-current="true" aria-label="Go to companion review 1"></button><button type="button" class="lunara-review-archive-rail-dot" data-lunara-dynamic-rail-dot data-lunara-dynamic-rail-index="1" aria-label="Go to companion review 2"></button><button type="button" class="lunara-review-archive-rail-dot" data-lunara-dynamic-rail-dot data-lunara-dynamic-rail-index="2" aria-label="Go to companion review 3"></button></div></section></main></body></html>`;

let checks = 0;
function check(value, message) {
    checks++;
    if (!value) throw new Error(message);
}

(async () => {
    const browser = await chromium.launch({ headless: true, executablePath });
    try {
        for (const reducedMotion of ['no-preference', 'reduce']) {
            const page = await browser.newPage({ viewport: { width: 390, height: 760 }, reducedMotion });
            await page.setContent(html);
            await page.addScriptTag({ content: runtime });
            const rail = page.locator('[data-lunara-dynamic-rail]');
            const track = page.locator('[data-lunara-dynamic-rail-track]');
            const toggle = page.locator('[data-lunara-dynamic-rail-toggle]');
            check(await toggle.count() === 1, `${reducedMotion}: rail must expose one pause/play control.`);
            const sizes = await rail.locator('button').evaluateAll(buttons => buttons.map(button => {
                const rect = button.getBoundingClientRect();
                return { width: rect.width, height: rect.height };
            }));
            check(sizes.every(size => size.width >= 44 && size.height >= 44), `${reducedMotion}: every rail control needs a 44px target.`);
            check((await toggle.textContent()).trim() === (reducedMotion === 'reduce' ? 'Play' : 'Pause'), `${reducedMotion}: initial playback state is wrong.`);
            if (reducedMotion === 'reduce') {
                check(await toggle.isDisabled(), 'Reduced motion must disable the rail toggle.');
                check(await toggle.getAttribute('aria-disabled') === 'true', 'Reduced motion must expose aria-disabled.');
            } else {
                await toggle.click();
                check((await toggle.textContent()).trim() === 'Play' && await toggle.getAttribute('aria-pressed') === 'true', 'Pause must expose the user-paused state.');
                await toggle.click();
                check((await toggle.textContent()).trim() === 'Pause' && await toggle.getAttribute('aria-pressed') === 'false', 'Play must restore automatic rotation.');
                await page.waitForFunction(() => document.querySelector('[data-lunara-dynamic-rail-dot].is-active').getAttribute('data-lunara-dynamic-rail-index') !== '0');
                check(await rail.locator('[data-lunara-dynamic-rail-dot].is-active').getAttribute('data-lunara-dynamic-rail-index') !== '0', 'Play must resume automatic rotation while its control retains focus.');
                await toggle.click();
            }
            await rail.locator('[data-lunara-dynamic-rail-dot]').first().click();
            await page.waitForFunction(() => document.querySelector('[data-lunara-dynamic-rail-track]').scrollLeft < 2);
            await rail.locator('[data-lunara-dynamic-rail-next]').click();
            await page.waitForFunction(() => document.querySelector('[data-lunara-dynamic-rail-dot].is-active').getAttribute('data-lunara-dynamic-rail-index') === '1');
            check(await rail.locator('[data-lunara-dynamic-rail-dot].is-active').getAttribute('data-lunara-dynamic-rail-index') === '1', `${reducedMotion}: next must update the active dot.`);
            await track.focus();
            await page.keyboard.press('ArrowLeft');
            await page.waitForFunction(() => document.querySelector('[data-lunara-dynamic-rail-track]').scrollLeft < 2);
            check(true, `${reducedMotion}: keyboard navigation returns to the first card.`);
            await page.close();
        }
        console.log(`Reviews dynamic rail runtime passed: ${checks} checks across phone and normal/reduced motion.`);
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error.stack); process.exitCode = 1; });
