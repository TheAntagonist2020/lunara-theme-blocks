'use strict';
// Exercise the complete public cascade: the former viewport-wide track and
// fourth Journal card override are invisible to isolated component CSS tests.
const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');
const { chromium } = require('playwright-core');
const root = path.resolve(__dirname, '..');
const renderedArt = spawnSync('php', [path.join(__dirname, 'home-oscar-mobile-art-runtime.php'), '--fixture'], { encoding: 'utf8' });
if (renderedArt.status !== 0) throw new Error(renderedArt.stderr || renderedArt.stdout);
const portraitMedia = renderedArt.stdout.match(/<div class="lunara-oscar-pick-card-media is-portrait">[\s\S]*?<\/div>/);
if (!portraitMedia) throw new Error('The public Oscar renderer must expose its portrait artwork.');
const art = 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="1000"><rect width="1600" height="1000" fill="#34485c"/><circle cx="800" cy="500" r="290" fill="#bba470"/></svg>');
const titles = ['It Took Forty Years to Become the Star of This Story', 'A Film Festival Dispatch With a Long but Entirely Readable Headline', 'A Journal Story Without Artwork', 'The Fourth Card Must Keep a Landscape Image Even When Its Headline Wraps Across Several Lines'];
function panelFixture(count = 15) {
    const journal = titles.map((title, i) => `<article class="lunara-journal-home-card ${i === 0 ? 'is-lead' : ''} ${i === 2 ? 'has-no-visual' : 'has-visual'}"><a class="lunara-journal-home-card-link" href="#journal-${i}">${i === 2 ? '' : `<div class="lunara-journal-home-card-media"><img class="lunara-journal-home-card-image" src="${art}" width="1600" height="1000" alt="Landscape artwork"></div>`}<div class="lunara-journal-home-card-copy"><p class="lunara-journal-home-card-kicker">From the desk</p><p class="lunara-dispatch-type">Journal</p><h3 class="lunara-journal-home-card-title">${title}</h3><p class="lunara-journal-home-card-excerpt">Film coverage should be readable on a phone. The artwork and the headline both need enough room to make sense.</p><div class="lunara-journal-home-card-meta"><span>Sep 10, 2026</span><span class="lunara-journal-home-card-cta">Read file</span></div></div></a></article>`).join('');
    const oscars = Array.from({ length: count }, (_, i) => `<article class="lunara-oscar-pick-card is-status-contender ${i === 2 ? 'has-no-visual' : 'has-visual'}" role="listitem"><a class="lunara-oscar-pick-card-link" href="#oscars-${i}">${i === 2 ? '' : `<div class="lunara-oscar-pick-card-media"><img class="lunara-oscar-pick-card-image" src="${art}" width="1600" height="1000" alt="Film artwork"><span class="lunara-oscar-pick-card-status">CONTENDER</span></div>`}<div class="lunara-oscar-pick-card-copy"><p class="lunara-oscar-pick-card-kicker">${i % 2 ? 'Best Cinematography' : 'Best Picture'}</p><h3 class="lunara-oscar-pick-card-title">${i % 2 ? 'A Cinematographer With a Long Name — An Ambitious Film With an Equally Long Title' : 'The Odyssey'}</h3><p class="lunara-oscar-pick-card-meta">99th Academy Awards · 2027 ceremony</p><p class="lunara-oscar-pick-card-rationale">A forecast with enough detail to explain the choice. Longer titles must remain inside the card without changing the shape of its artwork.</p><p class="lunara-oscar-pick-card-ledger">Open in Ledger</p></div></a></article>`).join('');
    const dots = Array.from({ length: count }, (_, i) => `<button class="lunara-carousel-dot ${i === 0 ? 'active' : ''}" type="button" aria-label="Show Oscar Pick ${i + 1}"></button>`).join('');
    const oscarCards = oscars.replace(/<div class="lunara-oscar-pick-card-media">[\s\S]*?<\/div>/, () => portraitMedia[0]);
    return `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">${['style.css','assets/css/lunara-shell.css','assets/css/lunara-home-modules.css','assets/css/lunara-public-guardrails.css'].map(file => `<link rel="stylesheet" href="/${file}">`).join('')}<style>*,*::before,*::after{box-sizing:border-box}:root{--lunara-home-journal-card-min:420px;--lunara-home-journal-excerpt-clamp:3;--lunara-home-oscar-picks-gap:24px;--lunara-home-oscar-picks-mobile-column:86%;--lunara-home-oscar-picks-card-min:460px}body{margin:0;background:#07101b;color:#fafbfc}main.lunara-front-page{display:grid;width:calc(100% - 32px);max-width:1440px;margin:16px auto;padding:0}</style></head><body class="home"><main class="lunara-front-page"><section class="lunara-home-section lunara-home-slot-dispatch lunara-dispatches-section" aria-label="Journal"><div class="lunara-home-section-head"><div><p class="lunara-home-section-kicker">Journal</p><h2 class="lunara-home-section-title">Fresh movement from the Lunara Journal</h2></div></div><div class="lunara-journal-home-grid">${journal}</div></section><section class="lunara-home-section lunara-home-slot-oscar-picks lunara-oscar-picks-section" aria-label="Lunara Oscar Forecast"><div class="lunara-home-section-head"><div><p class="lunara-home-section-kicker">Lunara Sweep Watch</p><h2 class="lunara-home-section-title">The Odyssey across the Academy Awards</h2></div></div>${count > 1 ? `<div class="lunara-oscar-picks-controls"><button class="lunara-carousel-control" aria-label="Previous Oscar Pick">&lt;</button><div class="lunara-oscar-picks-dots lunara-carousel-dots">${dots}</div><button class="lunara-carousel-control" aria-label="Next Oscar Pick">&gt;</button></div>` : ''}<div class="lunara-oscar-picks-track" role="list" tabindex="0">${oscarCards}</div></section></main></body></html>`;
}
if (process.argv.includes('--fixture')) { process.stdout.write(panelFixture()); } else {
    let checks = 0;
    const assert = (value, message) => { checks++; if (!value) throw new Error(message); };
    const executablePath = process.env.LUNARA_BROWSER_EXECUTABLE || ['C:/Program Files/Google/Chrome/Application/chrome.exe', 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', '/usr/bin/chromium', '/usr/bin/chromium-browser', '/usr/bin/google-chrome'].find(fs.existsSync);
    (async () => {
        const browser = await chromium.launch({ headless: true, executablePath });
        try {
            for (const width of [320, 390, 430, 768, 820, 1440]) {
                const page = await browser.newPage({ viewport: { width, height: 1000 }, hasTouch: width <= 820, reducedMotion: 'reduce' });
                await page.route('https://panels.test/**', route => {
                    const pathname = new URL(route.request().url()).pathname;
                    if (pathname === '/') return route.fulfill({ contentType: 'text/html', body: panelFixture() });
                    const file = path.resolve(root, '.' + pathname);
                    return file.startsWith(root + path.sep) && fs.existsSync(file) ? route.fulfill({ contentType: 'text/css', body: fs.readFileSync(file) }) : route.fulfill({ status: 404, body: '' });
                });
                await page.goto('https://panels.test/');
                await page.locator('.lunara-oscar-pick-card-media.is-portrait img').scrollIntoViewIfNeeded();
                await page.waitForFunction(() => { const img=document.querySelector('.lunara-oscar-pick-card-media.is-portrait img'); return img.complete && img.naturalWidth > 0; });
                const geometry = await page.evaluate(() => {
                    const rect = el => { const r = el.getBoundingClientRect(); return { x:r.x, y:r.y, width:r.width, height:r.height, right:r.right, bottom:r.bottom }; };
                    const track = document.querySelector('.lunara-oscar-picks-track');
                    const section = track.closest('section');
                    const sectionStyle = getComputedStyle(section);
                    return {
                        viewport:document.documentElement.clientWidth, documentWidth:document.documentElement.scrollWidth,
                        journal:[...document.querySelectorAll('.lunara-journal-home-card')].map(el => ({ card:rect(el), copy:rect(el.querySelector('.lunara-journal-home-card-copy')), media:el.querySelector('.lunara-journal-home-card-media') ? rect(el.querySelector('.lunara-journal-home-card-media')) : null })),
                        track:rect(track), innerWidth:section.clientWidth - parseFloat(sectionStyle.paddingLeft) - parseFloat(sectionStyle.paddingRight),
                        portrait:(() => { const img=track.querySelector('.is-portrait img'); return {fit:getComputedStyle(img).objectFit,width:img.naturalWidth,height:img.naturalHeight}; })(),
                        oscars:[...track.children].map(el => ({ card:rect(el), link:rect(el.firstElementChild), media:el.querySelector('.lunara-oscar-pick-card-media') ? rect(el.querySelector('.lunara-oscar-pick-card-media')) : null })),
                        titleOverflow:[...document.querySelectorAll('h3')].some(el => el.scrollWidth > el.clientWidth + 1 || (innerWidth <= 820 && el.scrollHeight > el.clientHeight + 1)),
                        controls:rect(document.querySelector('.lunara-oscar-picks-controls')),
                        buttons:[...document.querySelectorAll('.lunara-oscar-picks-controls button')].map(rect)
                    };
                });
                assert(geometry.documentWidth <= geometry.viewport + 1, `${width}px: page must not overflow horizontally.`);
                assert(!geometry.titleOverflow, `${width}px: long headlines must remain readable.`);
                if (width <= 820) {
                    assert(geometry.portrait.fit === 'contain' && geometry.portrait.width < geometry.portrait.height, `${width}px: load the original portrait and show it completely inside the landscape frame.`);
                    for (const item of geometry.journal) {
                        assert(item.copy.width >= item.card.width - 3, `${width}px: Journal copy needs a full-width column: ${JSON.stringify(item)}`);
                        if (item.media) {
                            assert(Math.abs(item.media.width / item.media.height - 1.6) < .02, `${width}px: every Journal image, including the fourth, must retain 16:10 framing.`);
                            assert(item.copy.y >= item.media.bottom - 1, `${width}px: Journal copy must sit below artwork.`);
                        }
                    }
                    assert(geometry.track.width <= geometry.innerWidth + 1, `${width}px: Oscars track must fit the panel, not the viewport.`);
                    for (const item of geometry.oscars) {
                        assert(Math.abs(item.card.width - geometry.track.width) < 3, `${width}px: show one complete Oscar card.`);
                        assert(Math.abs(item.card.height - geometry.oscars[0].card.height) < 2, `${width}px: Oscars cards must keep a stable height.`);
                        if (item.media) assert(Math.abs(item.media.width / item.media.height - 1.6) < .02, `${width}px: Oscars landscape art must keep its ratio.`);
                    }
                    assert(geometry.buttons.every(button => button.x >= geometry.controls.x - 1 && button.right <= geometry.controls.right + 1), `${width}px: all fifteen slide controls must stay inside their panel.`);
                } else {
                    assert(geometry.portrait.fit === 'cover' && geometry.portrait.width > geometry.portrait.height, 'Desktop must retain the existing landscape thumbnail and crop.');
                    assert(geometry.journal[3].copy.x >= geometry.journal[3].media.right - 1, 'Desktop must retain the existing side-by-side fourth Journal card.');
                    assert(geometry.oscars[0].card.width < geometry.track.width * .5, 'Desktop must retain multiple Oscar cards.');
                }
                await page.close();
            }
            console.log(`Homepage mobile panels runtime passed (${checks} checks).`);
        } finally { await browser.close(); }
    })().catch(error => { console.error(error.stack); process.exitCode = 1; });
}
