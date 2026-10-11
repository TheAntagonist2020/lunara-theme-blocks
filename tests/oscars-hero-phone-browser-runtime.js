'use strict';
// Oscars portal hero on phones (3.2.100) and without the film loop (3.2.103).
//
// Below 820px the hero grid stacks into a tall portrait slab. Until 3.2.100 the
// 16:9 Best Picture backdrop was cover-cropped into it, the 112deg wash sat on
// the wrong axis, and the Best Picture poster card landed in the middle. The
// film loop that played behind the hero on wide screens was removed in
// 3.2.103 (Dalton's call: it also played on phones held sideways and on
// tablets, and it was never the point of the page). The contract now:
//   - the hero has no video: no reel element, no reel script, no mp4 in the
//     theme, nothing requested at any width;
//   - on phones the backdrop is a single band behind the headline that fades
//     to navy before the poster card, not a cover-cropped slab;
//   - on desktop the backdrop covers the hero.
// The fixture lifts the hero's inline style straight from page-oscars.php so a
// template change that breaks the hook fails here, and loads style.css so the
// Key Light shafts (the section's own ::before/::after) are in the cascade.
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright-core');
const root = path.resolve(__dirname, '..');

const template = fs.readFileSync(path.join(root, 'page-oscars.php'), 'utf8');
const styleLine = template.match(/\$hero_style = "(.*)";\s*$/m);
if (!styleLine) throw new Error('page-oscars.php must build $hero_style on one line so this fixture can lift it.');
const art = 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="900"><rect width="1600" height="900" fill="#6b4a2a"/><circle cx="800" cy="450" r="300" fill="#d9b86a"/></svg>');
const heroStyle = styleLine[1].replace(`" . esc_url( $hero_backdrop_url ) . "`, art).replace(/\\'/g, "'");
if (!heroStyle.includes(art)) throw new Error('Could not substitute the backdrop URL into $hero_style: ' + styleLine[1]);

function fixture() {
    return `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/style.css"><link rel="stylesheet" href="/assets/css/lunara-oscars-portal.css"><style>*,*::before,*::after{box-sizing:border-box}body{margin:0;background:#07101b;color:#fafbfc;font-family:Georgia,serif}main{display:grid;width:calc(100% - 32px);max-width:1440px;margin:16px auto}.lunara-oscars-portal-hero{background-color:#0a1520;border-radius:24px;overflow:hidden}.lunara-oscars-portal-hero-grid{display:grid;grid-template-columns:1.25fr 360px;gap:24px;padding:28px}.lunara-home-hero-title{font-size:48px;line-height:1.05;margin:12px 0}.lunara-oscars-portal-actions{display:flex;gap:10px}.lunara-oscars-portal-actions a{padding:12px 18px;border:1px solid #c9a961;border-radius:999px;color:#c9a961;text-decoration:none}.lunara-oscars-portal-stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:18px}.lunara-oscars-portal-stat{background:rgba(0,0,0,.4);padding:12px;border-radius:12px}.lunara-oscars-portal-feature-card{display:block;background:#0a1520;border-radius:18px;padding:16px;color:inherit;text-decoration:none}.lunara-oscars-portal-feature-poster img{width:100%;display:block;border-radius:10px}</style></head><body class="lunara-oscars-portal-page"><main class="lunara-oscars-portal"><section class="lunara-home-section lunara-oscars-portal-hero lunara-oscars-portal-slot-hero has-backdrop" style="${heroStyle}"><div class="lunara-oscars-hero-band" aria-hidden="true"></div><div class="lunara-oscars-portal-hero-grid"><div class="lunara-oscars-portal-copy"><p class="lunara-home-section-kicker">The Lunara Oscar Ledger</p><h1 class="lunara-home-hero-title">Academy Awards history, treated like a living editorial system.</h1><div class="lunara-oscars-portal-actions"><a href="#">Latest Ceremony</a><a href="#">Open Full Ledger</a><a class="lunara-button-ghost" href="#">Browse Categories</a></div><div class="lunara-oscars-portal-stat-grid">${['Ceremony','Year','Rows','Categories'].map(l => `<div class="lunara-oscars-portal-stat"><span class="lunara-oscars-portal-stat-label">${l}</span><strong class="lunara-oscars-portal-stat-value">98th</strong></div>`).join('')}</div></div><a class="lunara-oscars-portal-feature-card" href="#"><div class="lunara-oscars-portal-feature-poster has-poster-bg"><img src="${art}" width="683" height="1024" alt="Poster"></div><div class="lunara-oscars-portal-feature-copy"><p class="lunara-oscars-portal-feature-kicker">Latest Best Picture</p><h2>One Battle after Another</h2><p class="lunara-oscars-portal-feature-meta">98th Academy Awards / 2025</p></div></a></div></section></main></body></html>`;
}

if (process.argv.includes('--fixture')) { process.stdout.write(fixture()); } else {
    let checks = 0;
    const assert = (value, message) => { checks++; if (!value) throw new Error(message); };
    // No video anywhere in the hero's supply chain.
    assert(!/<video\b|hero-reel|hero_reel/.test(template), 'page-oscars.php renders no video and no reel.');
    assert(!/hero-reel|hero_reel|oscars-hero-loop/.test(fs.readFileSync(path.join(root, 'inc/frontend.php'), 'utf8')), 'inc/frontend.php enqueues no reel script and resolves no loop.');
    assert(!fs.existsSync(path.join(root, 'assets/js/lunara-oscars-hero-reel.js')) && !fs.existsSync(path.join(root, 'assets/video/oscars-hero-loop.mp4')), 'The reel script and the 1.2 MB mp4 are not in the theme.');
    assert(!/hero-reel/.test(fs.readFileSync(path.join(root, 'assets/css/lunara-oscars-portal.css'), 'utf8')), 'lunara-oscars-portal.css styles no reel.');
    const executablePath = process.env.LUNARA_BROWSER_EXECUTABLE || ['C:/Program Files/Google/Chrome/Application/chrome.exe', 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', '/opt/pw-browsers/chromium', '/usr/bin/chromium', '/usr/bin/chromium-browser', '/usr/bin/google-chrome'].find(fs.existsSync);
    (async () => {
        const browser = await chromium.launch({ headless: true, executablePath });
        try {
            for (const width of [320, 390, 430, 820, 821, 1440]) {
                const page = await browser.newPage({ viewport: { width, height: 1000 }, hasTouch: width <= 820 });
                const requested = [];
                await page.route('https://hero.test/**', route => {
                    const pathname = new URL(route.request().url()).pathname;
                    requested.push(pathname);
                    if (pathname === '/') return route.fulfill({ contentType: 'text/html', body: fixture() });
                    if (pathname.endsWith('.mp4')) return route.fulfill({ status: 200, contentType: 'video/mp4', body: '' });
                    const file = path.resolve(root, '.' + pathname);
                    const type = pathname.endsWith('.js') ? 'text/javascript' : 'text/css';
                    return file.startsWith(root + path.sep) && fs.existsSync(file) ? route.fulfill({ contentType: type, body: fs.readFileSync(file) }) : route.fulfill({ status: 404, body: '' });
                });
                await page.goto('https://hero.test/');
                await page.waitForTimeout(300);
                const state = await page.evaluate(() => {
                    const rect = el => { const r = el.getBoundingClientRect(); return { x: r.x, y: r.y, width: r.width, height: r.height, bottom: r.bottom }; };
                    const hero = document.querySelector('.lunara-oscars-portal-hero');
                    const cs = getComputedStyle(hero);
                    const bandEl = document.querySelector('.lunara-oscars-hero-band');
                    const before = getComputedStyle(bandEl);
                    return {
                        viewport: document.documentElement.clientWidth, documentWidth: document.documentElement.scrollWidth,
                        videos: document.querySelectorAll('video').length,
                        backgroundImage: cs.backgroundImage, backgroundSize: cs.backgroundSize,
                        band: { display: before.display, backgroundImage: before.backgroundImage, backgroundSize: before.backgroundSize, backgroundRepeat: before.backgroundRepeat, height: bandEl.getBoundingClientRect().height, width: bandEl.getBoundingClientRect().width, position: before.position },
                        hero: rect(hero), poster: rect(document.querySelector('.lunara-oscars-portal-feature-poster')), title: rect(document.querySelector('.lunara-home-hero-title')),
                    };
                });
                assert(state.documentWidth <= state.viewport + 1, `${width}px: page must not overflow horizontally.`);
                assert(state.videos === 0 && !requested.some(p => p.endsWith('.mp4')), `${width}px: no video element, no mp4 request.`);
                if (width <= 820) {
                    assert(state.backgroundImage === 'none', `${width}px: the section itself no longer paints the cover slab (background-image: ${state.backgroundImage.slice(0, 60)}).`);
                    assert(state.band.display === 'block' && state.band.position === 'absolute' && state.band.backgroundImage.includes('data:image/svg+xml'), `${width}px: the backdrop is painted by the band element (display ${state.band.display}).`);
                    assert(Math.abs(state.band.width - state.hero.width) <= 4, `${width}px: the band spans the hero (band ${Math.round(state.band.width)}px, hero ${Math.round(state.hero.width)}px).`);
                    // Two layers (wash, still): each reports its own value.
                    assert(state.band.backgroundSize.split(',').every(s => s.trim() === 'cover') && state.band.backgroundRepeat.split(',').every(r => r.trim() === 'no-repeat'), `${width}px: the band covers its own box and never tiles (size ${state.band.backgroundSize}, repeat ${state.band.backgroundRepeat}).`);
                    const expected = Math.min(0.58 * width, 300);
                    assert(Math.abs(state.band.height - expected) <= 1, `${width}px: the band is min(58vw, 300px) tall (got ${state.band.height}, expected ${expected}).`);
                    // The poster card starts below the band so it never sits on the still; the headline sits on it.
                    const bandBottom = state.hero.y + state.band.height;
                    assert(state.poster.y >= bandBottom - 1, `${width}px: the poster card (top ${Math.round(state.poster.y)}) must sit below the backdrop band (bottom ${Math.round(bandBottom)}).`);
                    assert(state.title.y < bandBottom, `${width}px: the headline sits on the band.`);
                } else {
                    assert(state.backgroundImage.includes('data:image/svg+xml') && state.backgroundSize.split(',').every(s => s.trim() === 'cover'), `${width}px: desktop keeps the cover backdrop on the section (size ${state.backgroundSize}).`);
                    assert(state.band.display === 'none', `${width}px: the band element is hidden on desktop (display: ${state.band.display}).`);
                }
                await page.close();
            }
        } finally {
            await browser.close();
        }
        console.log(`Oscars hero phone browser runtime passed: ${checks} checks.`);
    })().catch(error => { console.error(error.message); process.exit(1); });
}
