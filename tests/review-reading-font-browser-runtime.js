'use strict';
// Review and journal reading surfaces read in Tiempos Text (3.2.101).
//
// The "reader comfort" pass of 2026-05-16 pinned the review body, excerpt,
// meta line, in-copy headings, reader TOC, rail buttons and pagination to
// Georgia with !important, in style.css and again in both shell stylesheets.
// So the one surface people actually read never used the house face while
// every page still paid to download it. The contract now: on single-review
// and single-journal pages those elements resolve to the theme's own font
// tokens (Tiempos Text for copy and chrome, Tiempos Headline for headings),
// whichever shell stylesheet is in the cascade, and nothing on the reading
// surface resolves to a Georgia-first stack.
//
// The fixture loads the real stylesheets in the enqueue order setup.php uses
// (style.css, then one shell variant, then lunara-review-single.css), so a
// stray !important anywhere in that cascade fails here.
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright-core');
const root = path.resolve(__dirname, '..');

function fixture(shell, bodyClass) {
    return `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/style.css"><link rel="stylesheet" href="/assets/css/${shell}"><link rel="stylesheet" href="/assets/css/lunara-review-single.css"></head><body class="${bodyClass}"><main><article class="lunara-review-single"><header><p class="lunara-review-single-excerpt">A film about a family that keeps a secret for forty years.</p><div class="lunara-review-single-meta"><span>By Dalton</span><span>12 min read</span></div></header><nav class="lunara-reader-toc"><p class="lunara-reader-toc-kicker">In this review</p><a class="lunara-reader-toc-link" href="#act-one">Act one</a></nav><div class="lunara-review-single-content"><p>Body copy that should read in the house face.</p><h2 id="act-one">Act one</h2><p>More body copy.</p><h3>A smaller heading</h3></div><aside class="lunara-review-single-rail-actions"><a class="lunara-btn" href="#">Share</a></aside></article><nav class="lunara-archive-pagination"><a class="page-numbers" href="#">2</a></nav></main></body></html>`;
}

const surfaces = {
    '.lunara-review-single-content p': 'Tiempos Text',
    '.lunara-review-single-content': 'Tiempos Text',
    '.lunara-review-single-excerpt': 'Tiempos Text',
    '.lunara-review-single-meta': 'Tiempos Text',
    '.lunara-review-single-content h2': 'Tiempos Headline',
    '.lunara-review-single-content h3': 'Tiempos Headline',
    '.lunara-reader-toc-kicker': 'Tiempos Text',
    '.lunara-reader-toc-link': 'Tiempos Text',
    '.lunara-review-single-rail-actions .lunara-btn': 'Tiempos Text',
    '.lunara-archive-pagination .page-numbers': 'Tiempos Text',
};

if (process.argv.includes('--fixture')) { process.stdout.write(fixture('lunara-shell-non-portal.css', 'single-review')); } else {
    let checks = 0;
    const assert = (value, message) => { checks++; if (!value) throw new Error(message); };
    const executablePath = process.env.LUNARA_BROWSER_EXECUTABLE || ['C:/Program Files/Google/Chrome/Application/chrome.exe', 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', '/opt/pw-browsers/chromium', '/usr/bin/chromium', '/usr/bin/chromium-browser', '/usr/bin/google-chrome'].find(fs.existsSync);
    (async () => {
        const browser = await chromium.launch({ headless: true, executablePath });
        try {
            for (const shell of ['lunara-shell-non-portal.css', 'lunara-shell.css']) {
                for (const bodyClass of ['single-review', 'single-journal']) {
                    for (const width of [390, 1440]) {
                        const page = await browser.newPage({ viewport: { width, height: 1000 } });
                        await page.route('https://reading.test/**', route => {
                            const pathname = new URL(route.request().url()).pathname;
                            if (pathname === '/') return route.fulfill({ contentType: 'text/html', body: fixture(shell, bodyClass) });
                            const file = path.resolve(root, '.' + pathname);
                            return file.startsWith(root + path.sep) && fs.existsSync(file) ? route.fulfill({ contentType: 'text/css', body: fs.readFileSync(file) }) : route.fulfill({ status: 404, body: '' });
                        });
                        await page.goto('https://reading.test/');
                        const families = await page.evaluate((selectors) => {
                            const out = {};
                            for (const selector of selectors) {
                                const el = document.querySelector(selector);
                                out[selector] = el ? getComputedStyle(el).fontFamily : null;
                            }
                            return out;
                        }, Object.keys(surfaces));
                        const label = `${shell} / ${bodyClass} / ${width}px`;
                        for (const [selector, expected] of Object.entries(surfaces)) {
                            const family = families[selector];
                            assert(typeof family === 'string' && family.length, `${label}: ${selector} is missing from the fixture.`);
                            const first = family.split(',')[0].replace(/["']/g, '').trim();
                            assert(first === expected, `${label}: ${selector} resolves to "${first}" (want ${expected}). Full stack: ${family}`);
                            assert(!/^Georgia/i.test(family), `${label}: ${selector} resolves to a Georgia-first stack: ${family}`);
                        }
                        await page.close();
                    }
                }
            }
        } finally {
            await browser.close();
        }
        console.log(`review-reading-font-browser-runtime: ${checks} checks passed.`);
    })().catch((error) => { console.error(error.message); process.exit(1); });
}
