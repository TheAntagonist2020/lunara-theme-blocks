'use strict';

const fs = require('fs');
const path = require('path');
let chromium;
try {
    ({ chromium } = require('playwright'));
} catch (error) {
    ({ chromium } = require('playwright-core'));
}

const themeRoot = path.resolve(__dirname, '..');
const viewportWidths = [390, 430, 768, 782, 1440];
const failures = [];
const read = (relativePath) => fs.readFileSync(path.join(themeRoot, relativePath), 'utf8');
const css = [
    'style.css',
    'assets/css/lunara-shell.css',
    'assets/css/lunara-public-guardrails.css',
    'assets/css/lunara-home-modules.css',
    'assets/css/lunara-review-archive.css',
    'assets/css/lunara-journal-archive.css',
    'assets/css/lunara-oscars-portal.css',
].map(read).join('\n');

function routeRootTag(relativePath, marker = '') {
    const source = read(relativePath);
    const scopedSource = marker && source.includes(marker) ? source.slice(source.indexOf(marker)) : source;
    const match = scopedSource.match(/<(main|div)\s+id="primary"/i);
    if (!match) {
        throw new Error(`${relativePath}: canonical #primary route root was not found.`);
    }
    return match[1].toLowerCase();
}

const routes = {
    home: {
        source: 'front-page.php',
        bodyClass: 'home',
        rootClass: 'site-main lunara-front-page',
        content: `
            <section class="lunara-home-section lunara-dispatches-section">
                <div class="lunara-journal-home-grid">
                    <article class="lunara-journal-home-card has-no-visual"><span class="lunara-journal-home-card-copy">Journal card</span></article>
                </div>
                <a class="test-important-action" href="#home-action">Journal destination</a>
            </section>`,
    },
    reviews: {
        source: 'inc/review-rendering.php',
        marker: 'function lunara_render_review_archive_shell',
        bodyClass: 'post-type-archive-review',
        rootClass: 'site-main lunara-archive-page lra lunara-review-archive-page',
        content: `
            <section class="lunara-home-section lunara-review-archive-shell lunara-review-archive-slot-grid">
                <div class="lunara-review-archive-support-suite">
                    <a class="test-important-action" href="#reviews-action">Open review</a>
                    <div class="lunara-review-archive-rail"><div class="lunara-review-archive-rail-track" tabindex="0">
                        <div class="lunara-review-archive-rail-item">One</div><div class="lunara-review-archive-rail-item">Two</div><div class="lunara-review-archive-rail-item">Three</div>
                    </div></div>
                </div>
            </section>`,
        scroller: { selector: '.lunara-review-archive-rail-track', widths: [390, 430, 768, 782] },
    },
    journal: {
        source: 'archive-journal.php',
        bodyClass: 'post-type-archive-journal',
        rootClass: 'lunara-archive-page lunara-journal-archive-page',
        content: `
            <div class="lunara-journal-filter-groups lunara-journal-archive-slot-filters">
                <nav class="lunara-journal-archive-filters"><span class="lunara-journal-filter-label">Filter</span>${'<a class="lunara-journal-filter-pill" href="#journal-filter">Long Journal Section</a>'.repeat(7)}</nav>
            </div>
            <section class="lunara-journal-archive-grid lunara-journal-archive-slot-grid"><a class="test-important-action" href="#journal-action">Open dispatch</a></section>`,
        scroller: { selector: '.lunara-journal-archive-filters', widths: [390, 430] },
    },
    oscars: {
        source: 'page-oscars.php',
        bodyClass: 'lunara-oscars-portal-page lunara-oscars-family',
        rootClass: 'site-main lunara-oscars-portal',
        content: `
            <section class="lunara-home-section lunara-oscars-portal-winners lunara-ceremony-winners-section"><a class="test-important-action" href="#oscars-action">Open ceremony</a></section>
            <section class="lunara-home-section lunara-oscars-rotating-winners-section"><div class="lunara-ledger-carousel-wrap"><div class="lunara-ledger-carousel-track lunara-oscars-winner-carousel-track">${'<article class="lunara-ceremony-winner-card">Winner</article>'.repeat(6)}</div></div></section>`,
        scroller: { selector: '.lunara-oscars-winner-carousel-track', widths: viewportWidths },
    },
};

function htmlFor(route) {
    const tag = routeRootTag(route.source, route.marker || '');
    return `<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>
        html,body{margin:0;max-width:none}body{background:#07101b;color:#fff}
        #canonical-main{box-sizing:border-box;display:block;width:100%;margin:0;padding:0;min-width:0}
        .test-important-action{box-sizing:border-box;display:inline-flex;min-width:0;max-width:100%;padding:8px}
        ${css}
        body.lunara-public-stabilization-fixture .lunara-review-archive-rail-item{flex:0 0 280px!important}
        body.lunara-public-stabilization-fixture.lunara-oscars-portal-page .lunara-oscars-winner-carousel-track{display:grid!important;grid-template-columns:none!important;grid-auto-flow:column!important;grid-auto-columns:260px!important}
    </style></head><body class="lunara-public-stabilization-fixture ${route.bodyClass}"><div class="site"><main id="canonical-main" class="site-main"><i class="test-box-sizing-sentinel" aria-hidden="true"></i><${tag} id="primary" class="${route.rootClass}" data-lunara-theme-version="3.2.58">${route.content}</${tag}></main></div></body></html>`;
}

async function inspect(page, routeName, route, width) {
    await page.setViewportSize({ width, height: 1000 });
    await page.setContent(htmlFor(route), { waitUntil: 'load' });
    const snapshot = await page.evaluate(({ routeName, scroller }) => {
        const mainCount = document.querySelectorAll('main').length;
        const documentOverflow = Math.max(document.documentElement.scrollWidth, document.body.scrollWidth) - window.innerWidth;
        const sentinelBoxSizing = getComputedStyle(document.querySelector('.test-box-sizing-sentinel')).boxSizing;
        const action = document.querySelector('.test-important-action');
        const masking = [];
        for (let node = action; node && node !== document.documentElement; node = node.parentElement) {
            const style = getComputedStyle(node);
            if (['hidden', 'clip'].includes(style.overflowX)) {
                masking.push(node === document.body ? 'body' : node === action ? '.test-important-action' : (node.id ? `#${node.id}` : `.${String(node.className).trim().split(/\s+/).join('.')}`));
            }
        }
        const rail = scroller && scroller.widths.includes(window.innerWidth) ? document.querySelector(scroller.selector) : null;
        const railStyle = rail ? getComputedStyle(rail) : null;
        return {
            routeName,
            width: window.innerWidth,
            mainCount,
            documentOverflow,
            sentinelBoxSizing,
            masking,
            rail: rail ? {
                selector: scroller.selector,
                overflowX: railStyle.overflowX,
                clientWidth: rail.clientWidth,
                scrollWidth: rail.scrollWidth,
            } : null,
        };
    }, { routeName, scroller: route.scroller || null });

    if (snapshot.mainCount !== 1) failures.push(`${routeName}@${width}: expected one main landmark, measured ${snapshot.mainCount}.`);
    if (snapshot.sentinelBoxSizing !== 'content-box') failures.push(`${routeName}@${width}: fixture globally rewrites box sizing to ${snapshot.sentinelBoxSizing}.`);
    if (snapshot.documentOverflow > 1) failures.push(`${routeName}@${width}: document overflows by ${snapshot.documentOverflow}px.`);
    if (snapshot.masking.length) failures.push(`${routeName}@${width}: important action is masked by ${snapshot.masking.join(', ')}.`);
    if (snapshot.rail && (!['auto', 'scroll'].includes(snapshot.rail.overflowX) || snapshot.rail.scrollWidth <= snapshot.rail.clientWidth)) {
        failures.push(`${routeName}@${width}: intentional scroller ${snapshot.rail.selector} is not locally scrollable (${snapshot.rail.overflowX}, ${snapshot.rail.clientWidth}/${snapshot.rail.scrollWidth}).`);
    }
}

(async () => {
    const browser = await chromium.launch({
        headless: true,
        executablePath: process.env.LUNARA_BROWSER_EXECUTABLE || undefined,
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });
    try {
        const page = await browser.newPage();
        for (const [routeName, route] of Object.entries(routes)) {
            for (const width of viewportWidths) {
                await inspect(page, routeName, route, width);
            }
        }
    } finally {
        await browser.close();
    }

    if (failures.length) {
        process.stderr.write(`public-route-stabilization-runtime: ${failures.length} assertion(s) failed\n${failures.map((failure) => ` - ${failure}`).join('\n')}\n`);
        process.exit(1);
    }
    process.stdout.write(`public-route-stabilization-runtime: 4 routes x ${viewportWidths.length} widths passed.\n`);
})().catch((error) => {
    process.stderr.write(`${error.stack || error.message}\n`);
    process.exit(1);
});
