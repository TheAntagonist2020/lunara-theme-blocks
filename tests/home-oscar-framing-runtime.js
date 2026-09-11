'use strict';
// Actual PHP renderers + resolver + the full public stylesheet cascade. No
// handcrafted replacement cards: image precedence and CSS must agree in public.
const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');
const { chromium } = require('playwright-core');
const root = path.resolve(__dirname, '..');
const styles = ['style.css', 'assets/css/lunara-shell.css', 'assets/css/lunara-home-modules.css', 'assets/css/lunara-public-guardrails.css'];
function fixture(overrides) {
    const result = spawnSync('php', [path.join(__dirname, 'home-oscar-mobile-art-runtime.php'), '--framing-fixture', ...(overrides ? ['--overrides'] : [])], { encoding: 'utf8' });
    if (result.status !== 0) throw new Error(result.stderr || result.stdout);
    return `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">${styles.map(file => `<link rel="stylesheet" href="/${file}">`).join('')}<style>*,*::before,*::after{box-sizing:border-box}:root{--lunara-home-oscar-picks-gap:24px;--lunara-home-oscar-picks-card-min:460px;--lunara-home-oscar-picks-mobile-column:86%}body{margin:0;background:#07101b;color:#fafbfc}main.lunara-front-page{display:grid;width:calc(100% - 32px);max-width:1440px;margin:16px auto;padding:0}</style></head><body class="home"><main class="lunara-front-page">${result.stdout}</main></body></html>`;
}
const fixtures = [fixture(false), fixture(true)];
async function routeFixture(page, overrides, scripts = false) {
    await page.route('https://oscar-framing.test/**', route => {
        const pathname = new URL(route.request().url()).pathname;
        if (pathname === '/') {
            const html = fixtures[Number(overrides)].replace('</head>', `${scripts ? '<script defer src="/assets/js/lunara-scroll-carousel.js"></script><script defer src="/assets/js/lunara-carousel.js"></script>' : ''}</head>`);
            return route.fulfill({ contentType: 'text/html', body: html });
        }
        const file = path.resolve(root, '.' + pathname);
        return file.startsWith(root + path.sep) && fs.existsSync(file) ? route.fulfill({ contentType: file.endsWith('.js') ? 'text/javascript' : 'text/css', body: fs.readFileSync(file) }) : route.fulfill({ status: 404, body: '' });
    });
}
const executablePath = process.env.LUNARA_BROWSER_EXECUTABLE || ['C:/Program Files/Google/Chrome/Application/chrome.exe', 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', '/usr/bin/chromium', '/usr/bin/chromium-browser', '/usr/bin/google-chrome'].find(fs.existsSync);
let checks = 0;
const failures = [];
function check(value, message) { checks++; if (!value) failures.push(message); }
function near(actual, expected, message, tolerance = 1.1) { check(Math.abs(actual - expected) <= tolerance, `${message}: expected ${expected}, got ${actual}`); }
(async () => {
    const browser = await chromium.launch({ headless: true, executablePath });
    try {
        for (const width of [320, 390, 768, 820, 1440]) for (const overrides of [false, true]) {
            // A script-disabled visitor must receive the same images and all
            // readable stories, including text-only/unapproved/held Facts.
            const page = await browser.newPage({ viewport: { width, height: 1000 }, javaScriptEnabled: false, reducedMotion: 'reduce' });
            const label = `${width}px / ${overrides ? 'overrides' : 'unchanged'} / no JavaScript`;
            await routeFixture(page, overrides, true);
            await page.goto('https://oscar-framing.test/');
            const cards = page.locator('[data-lunara-oscar-item-id]');
            check(await cards.count() === 11, `${label}: all four Picks and seven Facts must render.`);
            for (let index = 0; index < await cards.count(); index++) {
                const card = cards.nth(index);
                await card.scrollIntoViewIfNeeded();
                if (await card.locator('img').count()) await card.locator('img').evaluate(img => img.decode());
                const measurements = await card.evaluate(el => {
                    const rect = node => { const r = node.getBoundingClientRect(); return { x: r.x, y: r.y, width: r.width, height: r.height, right: r.right, bottom: r.bottom }; };
                    const title = el.querySelector('h3');
                    const media = el.querySelector('.lunara-oscar-pick-card-media,.lunara-oscar-fact-card-poster');
                    const copy = el.querySelector('.lunara-oscar-pick-card-copy,.lunara-oscar-fact-card-text');
                    const img = el.querySelector('img');
                    const style = getComputedStyle(el);
                    const imageStyle = img ? getComputedStyle(img) : null;
                    return {
                        id: Number(el.dataset.lunaraOscarItemId), card: rect(el), link:rect(el.querySelector('a')), copy: rect(copy), media: media ? rect(media) : null,
                        readable: style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity) > 0 && el.getBoundingClientRect().height > 0,
                        visibility: {display:style.display,visibility:style.visibility,opacity:style.opacity,position:style.position},
                        titleReadable: getComputedStyle(title).display !== 'none' && title.scrollWidth <= title.clientWidth + 1 && (title.scrollHeight <= title.clientHeight + 1 || (getComputedStyle(title).overflowY === 'visible' && getComputedStyle(title).webkitLineClamp === 'none')),
                        title: {width:title.clientWidth,scrollWidth:title.scrollWidth,height:title.clientHeight,scrollHeight:title.scrollHeight,lineClamp:getComputedStyle(title).webkitLineClamp,rect:rect(title)},
                        image: img ? { loaded: img.complete && img.naturalWidth > 0, width: img.naturalWidth, height: img.naturalHeight, src: img.currentSrc, fit: imageStyle.objectFit, position: imageStyle.objectPosition, transform: imageStyle.transform } : null,
                        pageWidth: document.documentElement.scrollWidth, viewport: document.documentElement.clientWidth,
                        track: rect(el.parentElement), trackInnerWidth:el.parentElement.clientWidth - parseFloat(getComputedStyle(el.parentElement).paddingLeft) - parseFloat(getComputedStyle(el.parentElement).paddingRight), section: rect(el.closest('section')),
                    };
                });
                const m = measurements;
                const item = `${label}: item ${m.id}`;
                check(m.readable, `${item} must be readable without JavaScript. ${JSON.stringify(m.visibility)}`);
                check(m.card.width >= 170, `${item}: a card must retain a usable width. ${JSON.stringify({card:m.card,link:m.link,copy:m.copy,track:m.track})}`);
                if (m.id >= 100 || width <= 820) near(m.card.width, m.trackInnerWidth, `${item}: show a complete card across its track's content area`, 3);
                check(m.titleReadable, `${item} long headline must not clip horizontally or vertically. ${JSON.stringify(m.title)}`);
                check(m.title.rect.bottom <= m.copy.bottom + 1 && m.copy.bottom <= m.card.bottom + 1, `${item}: the complete headline and copy must remain inside the card. ${JSON.stringify({card:m.card,copy:m.copy,title:m.title})}`);
                check(m.pageWidth <= m.viewport + 1, `${item} must not cause document overflow.`);
                const missing = [4, 104, 105, 106].includes(m.id);
                check(Boolean(m.image) !== missing, `${item}: missing, held, and unverified visuals must remain text-only.`);
                if (!m.image) continue;
                check(m.image.loaded, `${item}: selected artwork must load.`);
                if (width <= 820) {
                    near(m.media.width / m.media.height, 1.6, `${item}: image frame must stay 16:10`, .025);
                    check(m.copy.y >= m.media.bottom - 1, `${item}: copy must sit below artwork on narrow screens.`);
                    check(m.copy.width >= m.media.width - 3, `${item}: copy must retain a full-width readable column. ${JSON.stringify({card:m.card,copy:m.copy,media:m.media})}`);
                }
                if (overrides) {
                    const full = [1, 3, 101, 103].includes(m.id);
                    check(m.image.fit === (full ? 'contain' : 'cover'), `${item}: public fit must match the saved full/cover choice.`);
                    const focus = [1, 101].includes(m.id) ? '29% 67%' : [3, 103].includes(m.id) ? '50% 50%' : '23% 71%';
                    check(m.image.position === focus, `${item}: public focal point must match the saved setting.`);
                    const scale = m.image.transform === 'none' ? 1 : Number(m.image.transform.match(/^matrix\(([^,]+)/)[1]);
                    near(scale, full ? 1 : 1.12, `${item}: full-image mode prevents crop; cover honors zoom`, .001);
                    check(m.image.width === (full ? 800 : 600) && m.image.height === (full ? 600 : 900), `${item}: the selected full source must survive all breakpoints.`);
                    if (![3, 103].includes(m.id)) check(decodeURIComponent(m.image.src).includes('#70413a'), `${item}: selected replacement artwork must win over the source thumbnail.`);
                } else if ([1, 101, 107].includes(m.id)) {
                    if (width <= 820) {
                        check(m.image.width === 600 && m.image.height === 900, `${item}: mobile must load the portrait original, not a landscape crop.`);
                        check(m.image.fit === 'contain', `${item}: the complete original portrait must stay visible on mobile.`);
                    } else if (m.id !== 107) {
                        check(m.image.width === 800 && m.image.height === 600, `${item}: untouched desktop artwork must preserve its existing landscape thumbnail.`);
                        check(m.image.fit === 'cover', `${item}: untouched desktop crop must remain unchanged.`);
                    }
                }
            }
            if (process.env.LUNARA_ARTIFACT_DIR && overrides && [390, 1440].includes(width)) {
                fs.mkdirSync(process.env.LUNARA_ARTIFACT_DIR, { recursive: true });
                await page.locator('[data-lunara-oscar-item-id="101"]').scrollIntoViewIfNeeded();
                const firstFact = page.locator('[data-lunara-oscar-item-id="101"]');
                if ((await firstFact.boundingBox()).width >= 170) await firstFact.screenshot({ path: path.join(process.env.LUNARA_ARTIFACT_DIR, `facts-overridden-${width}.png`) });
                if (width === 1440) await page.locator('.lunara-oscar-picks-section').screenshot({ path: path.join(process.env.LUNARA_ARTIFACT_DIR, 'picks-overridden-1440.png') });
            }
            await page.close();
        }
        // Mount the real generic fallback (Splide ownership has its own gate).
        // Progressive enhancement must hide only the inactive Facts once ready,
        // retaining working dot/keyboard navigation and Pick arrow navigation.
        for (const width of [390, 1440]) for (const overrides of [false, true]) {
            const page = await browser.newPage({ viewport: { width, height: 1000 }, reducedMotion: 'reduce' });
            page.setDefaultTimeout(8000);
            const label = `${width}px / ${overrides ? 'overrides' : 'unchanged'} / generic controller`;
            await routeFixture(page, overrides, true);
            await page.goto('https://oscar-framing.test/');
            const facts = page.locator('.lunara-oscar-facts-carousel');
            await page.waitForFunction(() => document.querySelector('.lunara-oscar-facts-carousel').hasAttribute('data-lunara-carousel-ready'));
            check(await facts.locator('.lunara-oscar-fact-card.active').count() === 1, `${label}: exactly one Fact is active after initialization.`);
            check(await facts.locator('[data-lunara-oscar-item-id="102"]').evaluate(el => getComputedStyle(el).visibility === 'hidden'), `${label}: inactive Facts must be hidden after the controller is ready.`);
            try { await facts.locator('.lunara-carousel-dot').nth(1).click(); }
            catch (error) {
                const geometry = await facts.evaluate(el => [el,el.querySelector('.lunara-oscar-facts-track'),el.querySelector('.active.lunara-oscar-fact-card'),el.querySelector('.active .lunara-oscar-fact-card-link'),el.querySelector('.active .lunara-oscar-fact-card-text'),el.querySelector('.active .lunara-oscar-fact-card-foot'),el.querySelector('.lunara-oscar-facts-dots')].map(node => ({class:node.className,rect:node.getBoundingClientRect().toJSON(),position:getComputedStyle(node).position,height:getComputedStyle(node).height,grid:getComputedStyle(node).gridTemplateRows})));
                throw new Error(`${label}: Fact dots must remain clickable. ${JSON.stringify(geometry)}\n${error.message}`);
            }
            check(await facts.locator('[data-lunara-oscar-item-id="102"]').evaluate(el => el.classList.contains('active') && getComputedStyle(el).visibility === 'visible'), `${label}: a dot must reveal the selected Fact.`);
            await facts.focus();
            await page.keyboard.press('ArrowRight');
            check(await facts.locator('[data-lunara-oscar-item-id="103"]').evaluate(el => el.classList.contains('active') && getComputedStyle(el).visibility === 'visible'), `${label}: keyboard navigation must reveal the next Fact.`);
            await page.keyboard.press('ArrowLeft');
            check(await facts.locator('[data-lunara-oscar-item-id="102"]').evaluate(el => el.classList.contains('active')), `${label}: keyboard navigation must return to the prior Fact.`);
            await page.locator('[data-lunara-carousel-next]').click();
            await page.waitForFunction(() => document.querySelector('[data-lunara-carousel-track]').scrollLeft > 0);
            check(true, `${label}: Pick Next arrow must still advance.`);
            await page.close();
        }
        if (failures.length) throw new Error(`${failures.length} of ${checks} public framing checks failed:\n${failures.join('\n')}`);
        console.log(`Homepage Oscar framing runtime passed: ${checks} checks across five viewports, original and overridden artwork, no JavaScript and real generic carousel navigation.`);
    } finally { await browser.close(); }
})().catch(error => { console.error(error.stack); if (failures.length && !error.message.includes('public framing checks failed')) console.error(failures.join('\n')); process.exitCode = 1; });
