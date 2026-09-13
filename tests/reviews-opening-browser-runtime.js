/* Actual PHP shell geometry, including a browser with JavaScript disabled. */
const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');
const { chromium } = require('playwright-core');
const root = path.resolve(__dirname, '..');
const fixtureOutput = execFileSync('php', ['-d', 'error_reporting=24575', path.join(__dirname, 'reviews-opening-runtime.php'), '--fixtures'], { encoding: 'utf8' });
const fixtures = JSON.parse(fixtureOutput.split('LUNARA_OPENING_FIXTURES\n')[1]);
// Exact anonymous opening markup and Boost critical CSS expose the competing
// body-scoped rules that a current-theme-only fixture cannot reproduce.
fixtures.published = JSON.parse(fs.readFileSync(path.join(__dirname, 'fixtures/reviews-published-opening-3.2.71.json'), 'utf8'));
const css = ['style.css', 'assets/css/lunara-review-components.css', 'assets/css/lunara-review-archive.css'].map(file => fs.readFileSync(path.join(root, file), 'utf8')).join('\n');
const seed = execFileSync('php', [path.join(__dirname, 'reviews-archive-critical-render.php')], { encoding: 'utf8' });
const report = [];
const assert = (condition, message) => { if (!condition) throw new Error(message); };

(async () => {
    const browser = await chromium.launch({ headless: true, ...(process.env.LUNARA_BROWSER_EXECUTABLE ? { executablePath: process.env.LUNARA_BROWSER_EXECUTABLE } : { channel: 'chrome' }) });
    try {
        for (const width of [320, 390, 540, 768, 1440]) {
            for (const [scenario, fixture] of Object.entries(fixtures)) {
                assert(typeof fixture.authorityCss === 'string' && fixture.authorityCss.includes('--lunara-reviews-archive-section-gap:'), `${scenario}: use the production authority CSS emitter.`);
                const page = await browser.newPage({ viewport: { width, height: 900 }, javaScriptEnabled: false });
                await page.route('**/*', route => route.fulfill({ status: 200, contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="1500" height="2000"><rect width="1500" height="2000" fill="#334856"/></svg>' }));
                // A fixed-height header stand-in isolates the actual archive
                // renderer; public header/navigation checks belong elsewhere.
                // Blocksy provides the universal border-box reset; dynamic
                // authority variables come from the real theme emitter.
                await page.setContent(`<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>*,*::before,*::after{box-sizing:border-box}</style><style>${fixture.boostCss || ''}</style><style>${css}</style><style>${fixture.authorityCss}</style><style>${seed}</style><style>body{margin:0}.fixture-header{height:112px;box-sizing:border-box;padding:32px;font:20px Georgia;background:#09151e;color:#dbb761}.lunara-reveal{opacity:1!important;transform:none!important}</style></head><body class="${fixture.bodyClass}"><header class="fixture-header">Lunara Film</header>${fixture.html}</body></html>`, { waitUntil: 'load' });
                const geometry = await page.evaluate(() => {
                    const rect = selector => { const node = document.querySelector(selector); if (!node) return null; const r = node.getBoundingClientRect(); return { top: r.top, bottom: r.bottom, height: r.height, left: r.left, right: r.right, width: r.width }; };
                    const hero = document.querySelector('.lunara-review-archive-hero');
                    const introBounds = hero && hero.getBoundingClientRect();
                    // Decorative pseudo-elements intentionally extend beyond
                    // this section. Check the actual readable text, not their
                    // contribution to scrollHeight.
                    const clippedText = hero ? [...hero.querySelectorAll('.lunara-review-archive-hero-copy-wrap > *')].flatMap(node => {
                        const range = document.createRange(); range.selectNodeContents(node);
                        return [...range.getClientRects()].filter(r => r.left < introBounds.left - 1 || r.right > introBounds.right + 1 || r.top < introBounds.top - 1 || r.bottom > introBounds.bottom + 1).map(r => ({class:node.className,left:r.left,right:r.right,top:r.top,bottom:r.bottom}));
                    }) : [];
                    return {
                        viewport: innerWidth, documentWidth: document.documentElement.scrollWidth,
                        root: rect('#primary.lra'), toolbar: rect('.lunara-review-archive-toolbar'),
                        toolbarHeadingVisible: getComputedStyle(document.querySelector('.lunara-review-archive-toolbar-head')).display !== 'none',
                        sortOverflow: getComputedStyle(document.querySelector('.lunara-review-archive-sort')).overflowX,
                        sortRows: [...document.querySelectorAll('.lunara-review-archive-sort-link')].map(node => Math.round(node.getBoundingClientRect().top)),
                        yearBounds: [...document.querySelectorAll('.lunara-review-archive-year-filter label,.lunara-review-archive-year-filter select,.lunara-review-archive-year-filter button')].map(node => {
                            const r = node.getBoundingClientRect(); return { top: r.top, bottom: r.bottom, left: r.left, right: r.right };
                        }),
                        clippedReviewTitles: [...document.querySelectorAll('.lunara-review-grid-title')].filter(node => {
                            const range = document.createRange(); range.selectNodeContents(node);
                            const bounds = node.getBoundingClientRect();
                            return [...range.getClientRects()].some(r => r.bottom > bounds.bottom + 3 || r.left < bounds.left - 1 || r.right > bounds.right + 1);
                        }).map(node => node.textContent.trim()),
                        controlBounds: [...document.querySelectorAll('.lunara-review-archive-sort-link,.lunara-review-archive-year-filter select,.lunara-review-archive-year-filter button')].map(node => {
                            const r = node.getBoundingClientRect(); return { height: r.height, left: r.left, right: r.right };
                        }),
                        hero: rect('.lunara-review-archive-hero'), title: rect('.lunara-archive-hero-title'),
                        lead: rect('.lunara-review-feature-card.is-lead'), art: rect('.lunara-review-feature-media'),
                        h1s: document.querySelectorAll('h1').length,
                        removed: document.querySelectorAll('.lunara-review-archive-debrief,.lunara-review-archive-hero-actions').length,
                        titleText: document.querySelector('h1').textContent.trim(),
                        sortLinks: Array.from(document.querySelectorAll('.lunara-review-archive-sort-link')).filter(link => link.getAttribute('href')).length,
                        introClipped: clippedText.length > 0, clippedText
                    };
                });
                const label = `${scenario}@${width}`;
                assert(geometry.h1s === 1 && geometry.removed === 0, `${label}: exactly one H1 and no statistics/actions.`);
                assert(geometry.documentWidth <= width + 1, `${label}: horizontal overflow: ${JSON.stringify(geometry)}`);
                assert(geometry.sortLinks === 3, `${label}: sorting remains usable without JavaScript.`);
                assert(geometry.controlBounds.length === (scenario === 'director' ? 3 : 5), `${label}: public year controls accompany available year terms.`);
                assert(!geometry.introClipped, `${label}: saved introduction must remain readable: ${JSON.stringify(geometry)}`);
                if (width <= 900) assert(geometry.clippedReviewTitles.length === 0, `${label}: complete review titles must remain readable: ${geometry.clippedReviewTitles.join('; ')}`);
                if (width <= 540) {
                    assert(!geometry.toolbarHeadingVisible, `${label}: the repeated toolbar headline must not crowd the mobile opening.`);
                    assert(geometry.sortRows[0] === geometry.sortRows[1] && geometry.sortRows[2] > geometry.sortRows[1], `${label}: three choices must form two rows, not a tall single column.`);
                    assert(geometry.sortOverflow === 'visible', `${label}: the wrapping sort choices must not retain a horizontal scrollbar.`);
                    assert(geometry.toolbar.height <= 260, `${label}: archive controls must remain compact in the published cascade.`);
                    if (width >= 360 && geometry.yearBounds.length) {
                        const [labelBounds, selectBounds, buttonBounds] = geometry.yearBounds;
                        assert(labelBounds.right <= selectBounds.left && selectBounds.right <= buttonBounds.left && Math.abs(selectBounds.top - buttonBounds.top) < 1, `${label}: the year label, selector and button must share a row when space permits.`);
                    }
                    assert(geometry.toolbar.left >= 15 && geometry.toolbar.right <= width - 15, `${label}: toolbar must keep mobile gutters.`);
                    if (geometry.hero) assert(geometry.hero.left >= 15 && geometry.hero.right <= width - 15, `${label}: introduction must keep mobile gutters.`);
                    if (geometry.lead) assert(geometry.lead.left >= 15 && geometry.lead.right <= width - 15, `${label}: lead card must keep mobile gutters.`);
                    assert(geometry.controlBounds.every(r => r.height >= 43.5 && r.left >= geometry.toolbar.left && r.right <= geometry.toolbar.right), `${label}: sorting and year controls must be bounded touch targets.`);
                }
                if (scenario !== 'long-copy' && geometry.hero) {
                    assert(geometry.hero.height <= (width < 700 ? 280 : 240), `${label}: intro exceeds compact height budget: ${JSON.stringify(geometry)}`);
                }
                if (scenario !== 'empty' && scenario !== 'long-copy') {
                    assert(geometry.lead && geometry.art && geometry.art.top < 760, `${label}: first film artwork should appear in the opening viewport: ${JSON.stringify(geometry)}`);
                }
                if (scenario === 'long-copy') assert(geometry.titleText.startsWith('The films that stay with us'), `${label}: saved long title must remain visible.`);
                if (process.env.LUNARA_REVIEWS_OPENING_SCREENSHOTS && ['normal', 'long-copy', 'published'].includes(scenario)) {
                    fs.mkdirSync(process.env.LUNARA_REVIEWS_OPENING_SCREENSHOTS, { recursive: true });
                    await page.screenshot({ path: path.join(process.env.LUNARA_REVIEWS_OPENING_SCREENSHOTS, `${scenario}-${width}.png`) });
                }
                report.push({ scenario, width, javascript: false, ...geometry });
                await page.close();
            }
        }
    } finally { await browser.close(); }
    process.stdout.write(JSON.stringify({ result: 'PASS', source: 'production PHP renderer', cases: report }, null, 2) + '\n');
})().catch(error => { console.error(error.stack); process.exitCode = 1; });
