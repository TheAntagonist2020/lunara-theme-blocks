'use strict';
const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');
const { chromium } = require('playwright-core');
const root = path.resolve(__dirname, '..');
const rendered = spawnSync('php', [path.join(__dirname, 'home-carousels-runtime.php'), '--fixture'], { encoding: 'utf8' });
if (rendered.status !== 0) throw new Error(rendered.stderr || rendered.stdout);
const executablePath = process.env.LUNARA_BROWSER_EXECUTABLE || ['C:/Program Files/Google/Chrome/Application/chrome.exe', 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', '/usr/bin/chromium', '/usr/bin/chromium-browser', '/usr/bin/google-chrome'].find(fs.existsSync);
let checks = 0;
function assert(value, message) { checks++; if (!value) throw new Error(message); }
async function fixture(browser, options = {}, html = rendered.stdout) {
    const page = await browser.newPage(options);
    await page.route('https://carousel.test/**', route => {
        const url = new URL(route.request().url());
        if (url.pathname === '/') return route.fulfill({ contentType: 'text/html', body: html });
        const file = path.resolve(root, '.' + url.pathname);
        if (!file.startsWith(root + path.sep) || !fs.existsSync(file)) return route.fulfill({ status: 404, body: '' });
        const type = { '.css': 'text/css', '.js': 'text/javascript', '.svg': 'image/svg+xml' }[path.extname(file)] || 'text/plain';
        return route.fulfill({ contentType: type, body: fs.readFileSync(file) });
    });
    await page.goto('https://carousel.test/');
    return page;
}
(async () => {
    const browser = await chromium.launch({ headless: true, executablePath });
    try {
        for (const [width, columns] of [[1440,3], [820,2], [390,1]]) {
            const page = await fixture(browser, { viewport: { width, height: 1000 } });
            await page.waitForSelector('.lunara-home-journal-carousel.is-initialized');
            await page.waitForFunction(expected => document.querySelectorAll('.lunara-home-journal-carousel .is-visible').length === expected, columns);
            const metrics = await page.evaluate(() => ({
                visible: document.querySelectorAll('.lunara-home-journal-carousel .is-visible').length,
                width: document.documentElement.clientWidth, scroll: document.documentElement.scrollWidth,
                cards: Array.from(document.querySelectorAll('.lunara-home-news-card')).map(el => ({ height: el.getBoundingClientRect().height, width: el.getBoundingClientRect().width })),
                titles: Array.from(document.querySelectorAll('.lunara-home-news-copy h3')).map(el => el.scrollWidth <= el.clientWidth + 1)
            }));
            assert(metrics.visible === columns, `${width}px should display ${columns} news cards: ${JSON.stringify(metrics)}`);
            assert(metrics.scroll <= metrics.width + 1 && metrics.titles.every(Boolean), `${width}px must not overflow or clip long headlines.`);
            assert(metrics.cards.every(card => Math.abs(card.height - metrics.cards[0].height) < 2), `${width}px news cards must have consistent heights.`);
            const arrowDirection = await page.evaluate(() => ['.lunara-hero-arrow-prev svg','.lunara-hero-arrow-next svg'].map(selector => {
                const transform = getComputedStyle(document.querySelector(selector)).transform;
                const matrix = transform === 'none' ? new DOMMatrix() : new DOMMatrix(transform);
                return { x:matrix.a,y:matrix.d };
            }));
            assert(arrowDirection[0].x<-.99 && arrowDirection[0].y<-.99 && arrowDirection[1].x>.99 && arrowDirection[1].y>.99, `${width}px Hero Previous must point left and Next right: ${JSON.stringify(arrowDirection)}`);
            const news = page.locator('.lunara-home-journal-carousel');
            await news.getByRole('button', { name: 'Pause autoplay' }).click();
            assert(await news.getByRole('button', { name: 'Start autoplay' }).isVisible(), 'Pause button must expose a usable Play state.');
            assert(await page.locator('.lunara-home-curated-hero').getByRole('button', { name: 'Pause autoplay' }).count() === 1, 'Pausing Journal must not pause the hero.');
            await news.getByRole('button', { name: 'Next slide', exact: true }).click();
            await page.waitForFunction(() => document.querySelector('.lunara-home-journal-carousel .splide__slide:nth-child(2)').classList.contains('is-active'));
            assert(await news.locator('.splide__slide:nth-child(2)').getAttribute('aria-label') === '2 of 6', 'Arrow navigation must advance one card.');
            await news.getByRole('button', { name: 'Next slide', exact: true }).focus();
            await page.keyboard.press('ArrowRight');
            await page.waitForFunction(() => document.querySelector('.lunara-home-journal-carousel .splide__slide:nth-child(3)').classList.contains('is-active'));
            assert(await news.locator('.splide__slide:nth-child(3)').isVisible(), 'Focused keyboard navigation must work.');
            await page.close();
        }
        // Include the real native-fallback wrapper and header offset: both supplied
        // inherited mobile minimum heights that the original bare fixture missed.
        const mobileHome = rendered.stdout.replace('<main>', '<header style="height:135px">Header</header><main><div class="lunara-home-cinematic-front-door is-native-fallback">').replace('</main>', '</div></main>');
        for (const width of [320,390,768]) {
            const page = await fixture(browser, { viewport: { width, height: 844 } }, mobileHome);
            await page.waitForSelector('.lunara-home-curated-hero.is-initialized');
            const rows = await page.evaluate(() => ['.lunara-home-curated-hero','.lunara-home-journal-carousel'].map(selector => {
                const root = document.querySelector(selector), track = root.querySelector('.splide__track').getBoundingClientRect();
                const arrows = [...root.querySelectorAll('.splide__arrow')].map(el => el.getBoundingClientRect());
                const pause = root.querySelector('.splide__toggle').getBoundingClientRect();
                const pages = [...root.querySelectorAll('.splide__pagination button')].map(el => el.getBoundingClientRect());
                return { selector, trackBottom: track.bottom, arrowBottom: Math.max(...arrows.map(r => r.bottom)), pauseBottom: pause.bottom,
                    controls: [...arrows,pause].map(r => ({ top:r.top, width:r.width, height:r.height })),
                    pages: pages.map(r => ({ top:r.top,width:r.width,height:r.height })) };
            }));
            assert(rows.every(row => row.controls.every(r => r.top >= row.trackBottom && r.width >= 44 && r.height >= 44) && row.pages.every(r => r.top >= Math.max(row.arrowBottom,row.pauseBottom) && r.width >= 44 && r.height >= 44)), `${width}px Hero and Journal controls must occupy separate rows clear of story copy with 44px targets: ${JSON.stringify(rows)}`);
            assert(rows[0].pauseBottom <= 844, `${width}px representative Hero Pause control must fit beneath the header in the first viewport.`);
            const composition = await page.evaluate(() => {
                const slide = document.querySelector('.lunara-home-curated-hero .splide__slide.is-active');
                const art = slide.querySelector('.lunara-cinematic-hero-bg').getBoundingClientRect();
                const panel = slide.querySelector('.lunara-cinematic-hero-shell');
                const img = slide.querySelector('img');
                img.style.setProperty('--lunara-hero-focal-x','17%'); img.style.setProperty('--lunara-hero-focal-y','63%');
                return { ratio:art.width/art.height,artBottom:art.bottom,panelTop:panel.getBoundingClientRect().top,
                    background:getComputedStyle(panel).backgroundColor, focal:getComputedStyle(img).objectPosition,
                    overlay:getComputedStyle(slide.querySelector('.lunara-cinematic-hero-overlay')).display };
            });
            if (width <= 540) {
                assert(Math.abs(composition.ratio-1.6)<.01 && composition.panelTop>=composition.artBottom-1 && composition.background==='rgb(7, 17, 27)' && composition.overlay==='none' && composition.focal==='17% 63%', `${width}px phone artwork must retain its focal point in a 16:10 frame above a solid midnight reading panel: ${JSON.stringify(composition)}`);
            } else { assert(composition.overlay!=='none','Tablet must retain the cinematic overlay composition.'); }
            if (width === 390) {
                const hero = page.locator('.lunara-home-curated-hero');
                const fullFrame = await hero.locator('.splide__slide.is-active img').evaluate(img => { img.classList.add('is-full-frame'); return getComputedStyle(img).objectFit; });
                assert(fullFrame==='contain','Saved full-frame image fit must remain intact on the phone composition.');
                await hero.getByRole('button', { name:'Pause autoplay' }).click();
                assert(await hero.getByRole('button', { name:'Start autoplay' }).isVisible(), 'Moved Hero pause control must retain Play state.');
                await hero.getByRole('button', { name:'Next slide',exact:true }).click();
                await page.waitForFunction(() => document.querySelector('.lunara-home-curated-hero .splide__slide:nth-child(2)').classList.contains('is-active'));
                await hero.getByRole('button', { name:'Next slide',exact:true }).focus();
                await page.keyboard.press('ArrowRight');
                await page.waitForFunction(() => document.querySelector('.lunara-home-curated-hero .splide__slide:nth-child(3)').classList.contains('is-active'));
                assert(await hero.locator('.splide__slide:nth-child(3)').isVisible(), 'Moved Hero arrows must preserve click and keyboard navigation.');
            }
            await page.close();
        }
        const reduced = await fixture(browser, { viewport: { width: 390, height: 1000 }, reducedMotion: 'reduce' });
        assert(await reduced.locator('.splide__toggle:visible').count() === 0, 'Reduced motion must disable automatic playback controls.');
        assert(await reduced.locator('.lunara-home-curated-hero .is-active').count() >= 1, 'Reduced motion must leave readable hero content.');
        await reduced.close();
        const nojs = await fixture(browser, { viewport: { width: 390, height: 1000 }, javaScriptEnabled: false });
        assert(await nojs.locator('.lunara-home-news-card:visible').count() === 6, 'No JavaScript must expose every Journal story.');
        assert(await nojs.locator('.lunara-cinematic-hero-slide:visible').count() === 1, 'No JavaScript must show the first hero without stacked slides.');
        const nojsPhone = await nojs.evaluate(() => { const hero=document.querySelector('.lunara-home-curated-hero'),art=hero.querySelector('.lunara-cinematic-hero-bg').getBoundingClientRect(),panel=hero.querySelector('.lunara-cinematic-hero-shell'); return { ratio:art.width/art.height,panelTop:panel.getBoundingClientRect().top,artBottom:art.bottom,background:getComputedStyle(panel).backgroundColor }; });
        assert(Math.abs(nojsPhone.ratio-1.6)<.01 && nojsPhone.panelTop>=nojsPhone.artBottom-1 && nojsPhone.background==='rgb(7, 17, 27)' && await nojs.locator('.lunara-home-curated-hero button:visible').count()===0,'No-JavaScript phone Hero must retain artwork and reading panel without inert controls.');
        await nojs.close();
        const singleHeroHtml = rendered.stdout.replace(/<section\b[^>]*lunara-home-curated-hero[\s\S]*?<\/section>/, section => {
            let slides=0; return section.replace(/<li\b[\s\S]*?<\/li>/g, slide => slides++===0 ? slide : '').replace(/<button class="splide__toggle[\s\S]*?<\/button>/,'');
        });
        const single = await fixture(browser,{viewport:{width:390,height:844}},singleHeroHtml);
        assert(await single.locator('.lunara-home-curated-hero.is-hero-static').count()===1 && await single.locator('.lunara-home-curated-hero button:visible').count()===0 && await single.locator('.lunara-home-curated-hero .lunara-cinematic-hero-title').isVisible(),'Single-story phone Hero must show its text with no empty navigation controls.');
        await single.close();
        const off = await fixture(browser, {}, rendered.stdout.replaceAll('data-lunara-autoplay-enabled="1"', 'data-lunara-autoplay-enabled="0"'));
        assert(await off.getByRole('button', { name: 'Start autoplay' }).count() === 2, 'Both autoplay settings must start paused independently.');
        await off.close();
        const failed = await fixture(browser, {}, rendered.stdout.replace('/assets/vendor/splide/splide.min.js', '/missing-slider.js'));
        assert(await failed.locator('.lunara-home-news-card:visible').count() === 6, 'Failed slider download must preserve the card grid.');
        assert(await failed.locator('.lunara-cinematic-hero-slide:visible').count() === 1, 'Failed slider download must preserve the hero.');
        await failed.close();
        const longCopy = rendered.stdout.replaceAll('A long headline about the current film scene that should stay readable on a small screen', 'Current film coverage with a longer headline. '.repeat(6).slice(0,240)).replaceAll('An original article excerpt with enough detail to identify the source.', 'An editorial description of the film and its place in the current scene. '.repeat(10).slice(0,600));
        const longPage = await fixture(browser, { viewport: { width: 390, height: 1000 }, reducedMotion: 'reduce' }, longCopy);
        const copyFits = await longPage.evaluate(() => {
            const hero = document.querySelector('.lunara-home-curated-hero').getBoundingClientRect();
            const title = document.querySelector('.lunara-cinematic-hero-title');
            const cta = document.querySelector('.lunara-cinematic-hero-cta').getBoundingClientRect();
            return { height: title.clientHeight, scroll: title.scrollHeight, ctaBottom: cta.bottom, heroBottom: hero.bottom, ctaRight: cta.right, width: innerWidth };
        });
        assert(copyFits.scroll <= copyFits.height + 2 && copyFits.ctaBottom <= copyFits.heroBottom && copyFits.ctaRight <= copyFits.width, 'Maximum-length hero copy must stay readable with its CTA: ' + JSON.stringify(copyFits));
        await longPage.close();
        console.log(`Homepage carousel browser: ${checks} checks passed.`);
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
