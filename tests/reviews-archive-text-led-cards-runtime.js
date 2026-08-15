'use strict';

const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');
const puppeteer = require('puppeteer');

const themeRoot = path.resolve(__dirname, '..');
const baseCss = fs.readFileSync(path.join(themeRoot, 'style.css'), 'utf8');
const componentCss = fs.readFileSync(path.join(themeRoot, 'assets/css/lunara-review-components.css'), 'utf8');
const routeCss = fs.readFileSync(path.join(themeRoot, 'assets/css/lunara-review-archive.css'), 'utf8');
const criticalRenderer = path.join(__dirname, 'reviews-archive-critical-render.php');

const criticalPayload = {
    orders: {
        hero: 1,
        utility: 2,
        grid: 2,
        pagination: 3,
        'pairing-desk': 4,
    },
    visibility: {
        hero: true,
        grid: true,
        'pairing-desk': true,
    },
};

const criticalCss = execFileSync(
    'php',
    [criticalRenderer, Buffer.from(JSON.stringify(criticalPayload)).toString('base64')],
    { encoding: 'utf8' }
);

function fail(message, metrics) {
    if (metrics) {
        process.stderr.write(`${JSON.stringify(metrics, null, 2)}\n`);
    }
    throw new Error(message);
}

function pairingMarkup() {
    const pairingCards = ['Theme Echo', 'Counter-Program', 'Career Context']
        .map((role, index) => `
            <article class="lunara-pair-card" data-pair-card="${index + 1}">
                <div class="lunara-pair-card-poster">
                    <img alt="Companion poster ${index + 1}" width="2000" height="3000" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='2000' height='3000'%3E%3Crect width='2000' height='3000' fill='%23122232'/%3E%3C/svg%3E">
                </div>
                <div class="lunara-pair-card-body">
                    <p class="lunara-pair-card-role">${role}</p>
                    <h3 class="lunara-pair-card-title"><a class="lunara-pair-card-title-link" href="#pair-${index + 1}"><span class="lunara-pair-card-title-text">Companion Film ${index + 1}</span> <span class="lunara-pair-card-year">(2020)</span></a></h3>
                    <p class="lunara-pair-card-note">A complete editorial reason connects this companion film to the reviewed work.</p>
                    <div class="lunara-pair-card-chips"><a class="lunara-pair-card-chip" href="#imdb-${index + 1}">IMDb</a></div>
                </div>
            </article>`)
        .join('');

    return `<section class="lunara-home-section lunara-pairing-desk-section">
        <div class="lunara-pairing-desk-inner">
            <div class="lunara-pair-cards">
                <div class="lunara-pair-cards-grid" data-count="3">${pairingCards}</div>
            </div>
        </div>
    </section>`;
}

function archiveMarkup() {
    return `
        <main id="primary" class="site-main lunara-archive-page lra lunara-review-archive-page lunara-review-archive-has-posts">
            <section class="lunara-review-archive-shell lunara-review-archive-slot-grid">
                <div class="lunara-review-archive-uniform lunara-review-grid">
                    <article class="lunara-review-grid-card lunara-review-archive-card is-text-led has-review-quote" data-card="text-led">
                        <a class="lunara-review-grid-link" href="#text-led">
                            <div class="lunara-review-grid-copy">
                                <p class="lunara-review-grid-kicker">Lunara Review</p>
                                <span class="lunara-score-badge lunara-score-badge-inline">★★★★</span>
                                <h3 class="lunara-review-grid-title">An Image-Less Review Must Use the Entire Card</h3>
                                <p class="lunara-review-grid-excerpt lunara-review-grid-quote">The copy is the visual lead when a poster is unavailable, so no empty media chamber may survive beside it.</p>
                            </div>
                        </a>
                    </article>
                    <article class="lunara-review-grid-card lunara-review-archive-card has-review-media has-review-quote" data-card="media-backed">
                        <a class="lunara-review-grid-link" href="#media-backed">
                            <div class="lunara-review-grid-poster-wrap">
                                <img class="lunara-review-grid-poster" alt="Media-backed review poster" width="1500" height="2000" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1500' height='2000'%3E%3Crect width='1500' height='2000' fill='%23c9a961'/%3E%3C/svg%3E">
                            </div>
                            <div class="lunara-review-grid-copy">
                                <p class="lunara-review-grid-kicker">Lunara Review</p>
                                <h3 class="lunara-review-grid-title">Media-Backed Review</h3>
                                <p class="lunara-review-grid-excerpt lunara-review-grid-quote">The existing mobile poster-and-copy composition must remain intact.</p>
                            </div>
                        </a>
                    </article>
                </div>
            </section>
            ${pairingMarkup()}
        </main>`;
}

function homepageMarkup() {
    return `<main id="primary" class="site-main lunara-home-main">${pairingMarkup()}</main>`;
}

async function measure(page) {
    return page.evaluate(() => {
        const numeric = (value) => Number.parseFloat(value) || 0;
        const textRects = (element) => {
            const range = document.createRange();
            range.selectNodeContents(element);
            return Array.from(range.getClientRects()).map((rect) => ({
                top: rect.top,
                right: rect.right,
                bottom: rect.bottom,
                left: rect.left,
            }));
        };
        const intersectionArea = (a, b) => {
            const width = Math.max(0, Math.min(a.right, b.right) - Math.max(a.left, b.left));
            const height = Math.max(0, Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top));
            return width * height;
        };
        const cardMetrics = (name) => {
            const card = document.querySelector(`[data-card="${name}"]`);
            if (!card) {
                return null;
            }
            const link = card.querySelector('.lunara-review-grid-link');
            const copy = card.querySelector('.lunara-review-grid-copy');
            const media = card.querySelector('.lunara-review-grid-poster-wrap');
            const linkRect = link.getBoundingClientRect();
            const copyRect = copy.getBoundingClientRect();
            const mediaRect = media ? media.getBoundingClientRect() : null;
            const columns = getComputedStyle(link).gridTemplateColumns
                .trim()
                .split(/\s+/)
                .filter(Boolean);
            const copyStyle = getComputedStyle(copy);

            return {
                childElementCount: link.childElementCount,
                columns,
                link: { left: linkRect.left, width: linkRect.width, height: linkRect.height },
                copy: { left: copyRect.left, width: copyRect.width, height: copyRect.height },
                copyGrid: {
                    columnStart: copyStyle.gridColumnStart,
                    columnEnd: copyStyle.gridColumnEnd,
                },
                media: mediaRect ? { left: mediaRect.left, width: mediaRect.width, height: mediaRect.height } : null,
            };
        };

        const pairingMetrics = Array.from(document.querySelectorAll('[data-pair-card]')).map((card, index) => {
            const role = card.querySelector('.lunara-pair-card-role');
            const title = card.querySelector('.lunara-pair-card-title-link');
            const pseudo = getComputedStyle(card, '::after');
            const cardRect = card.getBoundingClientRect();
            const counterVisible = pseudo.display !== 'none' && pseudo.content !== 'none';
            // Chromium exposes the unresolved counter() expression through
            // getComputedStyle(). Measure the visibly rendered 01/02/03 label.
            const content = String(index + 1).padStart(2, '0');
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            context.font = `${pseudo.fontStyle} ${pseudo.fontWeight} ${pseudo.fontSize} ${pseudo.fontFamily}`;
            const measuredWidth = context.measureText(content).width + Math.max(0, content.length - 1) * numeric(pseudo.letterSpacing);
            const counterWidth = numeric(pseudo.width) || measuredWidth;
            const counterHeight = numeric(pseudo.height) || numeric(pseudo.lineHeight) || numeric(pseudo.fontSize);
            const counter = counterVisible ? {
                top: cardRect.top + numeric(pseudo.top),
                right: cardRect.right - numeric(pseudo.right),
                bottom: cardRect.top + numeric(pseudo.top) + counterHeight,
                left: cardRect.right - numeric(pseudo.right) - counterWidth,
            } : null;
            const roleRects = textRects(role);
            const titleRects = textRects(title);

            return {
                counter,
                counterDisplay: pseudo.display,
                counterFontSize: pseudo.fontSize,
                counterLineHeight: pseudo.lineHeight,
                counterRight: pseudo.right,
                counterTop: pseudo.top,
                rolePaddingRight: getComputedStyle(role).paddingRight,
                titlePaddingRight: getComputedStyle(card.querySelector('.lunara-pair-card-title')).paddingRight,
                roleIntersection: counter ? Math.max(0, ...roleRects.map((rect) => intersectionArea(counter, rect))) : 0,
                titleIntersection: counter ? Math.max(0, ...titleRects.map((rect) => intersectionArea(counter, rect))) : 0,
            };
        });

        return {
            viewport: document.documentElement.clientWidth,
            scrollWidth: document.documentElement.scrollWidth,
            textLed: cardMetrics('text-led'),
            mediaBacked: cardMetrics('media-backed'),
            pairing: pairingMetrics,
        };
    });
}

function assertTextLedFullWidth(metrics, phase, requireExplicitSpan = false) {
    const textLed = metrics.textLed;
    if (textLed.childElementCount !== 1) {
        fail(`${phase}: the text-led card fixture must contain only its copy child.`, metrics);
    }
    if (textLed.columns.length !== 1) {
        fail(`${phase}: the text-led card retained an empty mobile media column.`, metrics);
    }
    if (Math.abs(textLed.copy.left - textLed.link.left) > 1 || Math.abs(textLed.copy.width - textLed.link.width) > 1) {
        fail(`${phase}: the text-led copy does not fill the link grid.`, metrics);
    }
    if (requireExplicitSpan && (textLed.copyGrid.columnStart !== '1' || textLed.copyGrid.columnEnd !== '-1')) {
        fail(`${phase}: the text-led copy must explicitly span the complete mobile grid.`, metrics);
    }
}

function assertMediaBackedPreserved(metrics, phase, mobile) {
    const mediaBacked = metrics.mediaBacked;
    if (mediaBacked.childElementCount !== 2 || !mediaBacked.media || mediaBacked.media.width <= 0) {
        fail(`${phase}: the media-backed card lost its poster/copy structure.`, metrics);
    }
    if (mobile) {
        if (mediaBacked.columns.length !== 2) {
            fail(`${phase}: the media-backed mobile card must retain two columns.`, metrics);
        }
        if (Math.abs(mediaBacked.media.width - 104) > 1) {
            fail(`${phase}: the media-backed mobile poster width drifted from 104px.`, metrics);
        }
        if (mediaBacked.copy.left < mediaBacked.media.left + mediaBacked.media.width - 1) {
            fail(`${phase}: the media-backed copy no longer follows its poster.`, metrics);
        }
    }
}

function assertPairingNumerals(metrics, viewportWidth, phase) {
    for (const [index, card] of metrics.pairing.entries()) {
        if (viewportWidth <= 680) {
            if (card.roleIntersection > 0.5 || card.titleIntersection > 0.5) {
                fail(`${phase}: Pairing numeral ${index + 1} overlaps its role or title.`, metrics);
            }
            if (card.counterDisplay !== 'none' || card.counter !== null) {
                fail(`${phase}: decorative Pairing numeral ${index + 1} must be hidden on phones.`, metrics);
            }
            if (Number.parseFloat(card.rolePaddingRight) > 0.5 || Number.parseFloat(card.titlePaddingRight) > 0.5) {
                fail(`${phase}: Pairing copy ${index + 1} must not reflow for the absolute numeral.`, metrics);
            }
        } else if (Math.abs(Number.parseFloat(card.counterFontSize) - 54.4) > 0.5) {
            fail(`${phase}: the desktop/tablet Pairing numeral geometry regressed.`, metrics);
        }
    }
}

(async () => {
    const browser = await puppeteer.launch({ headless: true });
    const results = [];

    try {
        for (const viewportWidth of [375, 390, 782, 1440]) {
            const page = await browser.newPage();
            await page.setViewport({ width: viewportWidth, height: 1000, deviceScaleFactor: 1 });
            await page.setContent(`<!doctype html>
                <html>
                    <head>
                        <meta name="viewport" content="width=device-width, initial-scale=1">
                        <style>*{box-sizing:border-box}html,body{margin:0;min-height:100%;padding:0}body{background:#07101b;color:#f4efe3}</style>
                        <style>${baseCss}</style>
                        <style>${componentCss}</style>
                        <style id="lunara-review-archive-critical-css">${criticalCss}</style>
                    </head>
                    <body class="post-type-archive-review">${archiveMarkup()}</body>
                </html>`);

            const before = await measure(page);
            assertTextLedFullWidth(before, `${viewportWidth}px before route CSS`);
            assertPairingNumerals(before, viewportWidth, `Reviews ${viewportWidth}px before route CSS`);

            await page.addStyleTag({ content: routeCss });
            await page.evaluate(() => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve))));

            const after = await measure(page);
            assertTextLedFullWidth(after, `${viewportWidth}px after route CSS`, viewportWidth <= 820);
            assertMediaBackedPreserved(after, `${viewportWidth}px after route CSS`, viewportWidth <= 820);
            assertPairingNumerals(after, viewportWidth, `${viewportWidth}px after route CSS`);

            if (after.scrollWidth > after.viewport) {
                fail(`${viewportWidth}px: the Reviews archive overflows horizontally.`, { before, after });
            }

            const homepage = await browser.newPage();
            await homepage.setViewport({ width: viewportWidth, height: 1000, deviceScaleFactor: 1 });
            await homepage.setContent(`<!doctype html>
                <html>
                    <head>
                        <meta name="viewport" content="width=device-width, initial-scale=1">
                        <style>*{box-sizing:border-box}html,body{margin:0;min-height:100%;padding:0}body{background:#07101b;color:#f4efe3}</style>
                        <style>${baseCss}</style>
                        <style>${componentCss}</style>
                    </head>
                    <body class="home">${homepageMarkup()}</body>
                </html>`);
            const homepageMetrics = await measure(homepage);
            assertPairingNumerals(homepageMetrics, viewportWidth, `Homepage ${viewportWidth}px shared component CSS`);
            if (homepageMetrics.scrollWidth > homepageMetrics.viewport) {
                fail(`Homepage ${viewportWidth}px: the Pairing module overflows horizontally.`, homepageMetrics);
            }

            const maxPairingIntersection = (metrics) => Math.max(
                0,
                ...metrics.pairing.map((card) => Math.max(card.roleIntersection, card.titleIntersection))
            );
            results.push({
                viewportWidth,
                textLedColumns: after.textLed.columns,
                textLedCopyWidth: after.textLed.copy.width,
                textLedLinkWidth: after.textLed.link.width,
                textLedCopyGrid: after.textLed.copyGrid,
                mediaColumns: after.mediaBacked.columns,
                mediaWidth: after.mediaBacked.media.width,
                reviewsPairingIntersection: maxPairingIntersection(after),
                homepagePairingIntersection: maxPairingIntersection(homepageMetrics),
                counterFontSize: after.pairing[0].counterFontSize,
                counterDisplay: after.pairing[0].counterDisplay,
                counterRight: after.pairing[0].counterRight,
                rolePaddingRight: after.pairing[0].rolePaddingRight,
            });
            await homepage.close();
            await page.close();
        }

        process.stdout.write(`Reviews archive text-led card runtime passed: ${JSON.stringify(results)}\n`);
    } finally {
        await browser.close();
    }
})().catch((error) => {
    process.stderr.write(`${error.stack || error.message}\n`);
    process.exit(1);
});
