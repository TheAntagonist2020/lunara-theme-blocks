'use strict';

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { execFileSync } = require('child_process');
const puppeteer = require('puppeteer');

const themeRoot = path.resolve(__dirname, '..');
const baseCss = fs.readFileSync(path.join(themeRoot, 'style.css'), 'utf8');
const componentCss = fs.readFileSync(path.join(themeRoot, 'assets/css/lunara-review-components.css'), 'utf8');
const routeCss = fs.readFileSync(path.join(themeRoot, 'assets/css/lunara-review-archive.css'), 'utf8');
const boostFixturePath = path.join(themeRoot, 'tests/fixtures/reviews-production-boost-critical-3.2.39.css');
const productionBoostCritical = fs.readFileSync(boostFixturePath, 'utf8').replace(/\r?\n$/, '');
const aggregateFixturePath = process.env.LUNARA_REVIEWS_STALE_AGGREGATE_FIXTURE;
const staleAggregateCss = aggregateFixturePath
    ? fs.readFileSync(path.resolve(aggregateFixturePath), 'utf8')
    : productionBoostCritical;
const criticalRenderer = path.join(__dirname, 'reviews-archive-critical-render.php');

function renderCriticalSeed(orders) {
    const payload = {
        orders: {
            hero: orders.hero,
            utility: orders.utility,
            grid: orders.grid,
            pagination: orders.pagination,
            'pairing-desk': orders.pairing,
        },
        visibility: {
            hero: orders.showHero !== false,
            grid: true,
            'pairing-desk': orders.showPairing !== false,
        },
    };
    return execFileSync('php', [criticalRenderer, Buffer.from(JSON.stringify(payload)).toString('base64')], {
        encoding: 'utf8',
    });
}

const boostFixtureBytes = Buffer.byteLength(productionBoostCritical, 'utf8');
const boostFixtureSha256 = crypto.createHash('sha256').update(productionBoostCritical, 'utf8').digest('hex');
if (boostFixtureBytes !== 53864 || boostFixtureSha256 !== '0abce37ff14360da3914005227f713cc3f0394cc116c8ffc3ecb368629b1569e') {
    throw new Error(`Production Boost fixture drifted: ${boostFixtureBytes}B ${boostFixtureSha256}`);
}

const orderScenarios = {
    default: {
        hero: 1,
        utility: 2,
        grid: 2,
        pagination: 3,
        pairing: 4,
        sectionGap: 40,
        shellGap: 36,
        leadMin: 460,
        leadMediaMin: 270,
        leadCopyPad: 46,
        cardMin: 360,
        compactMediaWidth: 116,
        compactMediaHeight: 154,
        railGap: 18,
        excerptClamp: 3,
    },
    'pairing-first': {
        hero: 4,
        utility: 2,
        grid: 2,
        pagination: 3,
        pairing: 1,
        sectionGap: 72,
        shellGap: 46,
        leadMin: 640,
        leadMediaMin: 320,
        leadCopyPad: 58,
        cardMin: 540,
        compactMediaWidth: 150,
        compactMediaHeight: 199,
        railGap: 24,
        excerptClamp: 4,
    },
    'hero-hidden': {
        hero: 1,
        utility: 2,
        grid: 2,
        pagination: 3,
        pairing: 4,
        sectionGap: 40,
        shellGap: 36,
        leadMin: 460,
        leadMediaMin: 270,
        leadCopyPad: 46,
        cardMin: 360,
        compactMediaWidth: 116,
        compactMediaHeight: 154,
        railGap: 18,
        excerptClamp: 3,
        showHero: false,
    },
    director: {
        hero: 1,
        utility: 2,
        grid: 2,
        pagination: 3,
        pairing: 4,
        sectionGap: 40,
        shellGap: 36,
        leadMin: 460,
        leadMediaMin: 270,
        leadCopyPad: 46,
        cardMin: 360,
        compactMediaWidth: 116,
        compactMediaHeight: 154,
        railGap: 18,
        excerptClamp: 3,
        bodyClass: 'tax-lunara_director',
        showPairing: false,
        showYearFilter: false,
        showHeroActions: false,
    },
};

orderScenarios['page-template'] = {
    ...orderScenarios.default,
    bodyClass: 'page page-template-page-reviews',
};
orderScenarios['slug-page'] = {
    ...orderScenarios.default,
    bodyClass: 'page page-template-default',
};

function authorityCss(orders) {
    return `
        #primary.lunara-review-archive-page {
            --lunara-reviews-archive-section-gap: ${orders.sectionGap}px !important;
            --lunara-reviews-archive-shell-gap: ${orders.shellGap}px !important;
            --lunara-reviews-archive-lead-min: ${orders.leadMin}px !important;
            --lunara-reviews-archive-lead-media-min: ${orders.leadMediaMin}px !important;
            --lunara-reviews-archive-lead-copy-pad: ${orders.leadCopyPad}px !important;
            --lunara-reviews-archive-card-min: ${orders.cardMin}px !important;
            --lunara-reviews-archive-compact-media-width: ${orders.compactMediaWidth}px !important;
            --lunara-reviews-archive-compact-media-height: ${orders.compactMediaHeight}px !important;
            --lunara-reviews-archive-rail-gap: ${orders.railGap}px !important;
            --lunara-reviews-archive-excerpt-clamp: ${orders.excerptClamp} !important;
            --lunara-reviews-archive-order-hero: ${orders.hero} !important;
            --lunara-reviews-archive-order-utility: ${orders.utility} !important;
            --lunara-reviews-archive-order-grid: ${orders.grid} !important;
            --lunara-reviews-archive-order-pagination: ${orders.pagination} !important;
            --lunara-reviews-archive-order-pairing: ${orders.pairing} !important;
        }
    `;
}

// Reduced from the stale Review rules observed in the production Jetpack Boost
// critical-CSS payload after the 3.2.39 rollback. These are deliberately not
// invented "unstyled" defaults: they model the conflicting first-paint geometry
// that the route-owned seed must override before deferred CSS arrives.
const staleCriticalCss = `
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body { font: 16px/1.5 Georgia, serif; }
    .lunara-site-header { height: 132px; }
    body.post-type-archive-review .lunara-archive-page {
        --lunara-reviews-archive-section-gap: 72px !important;
        --lunara-reviews-archive-shell-gap: 28px !important;
        --lunara-reviews-archive-lead-min: 620px !important;
        --lunara-reviews-archive-lead-media-min: 320px !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 72px !important;
        margin-inline: auto !important;
        max-width: 1180px !important;
        padding: 0 40px 80px !important;
        width: min(1180px, calc(100vw - 40px)) !important;
    }
    body.post-type-archive-review .lunara-archive-hero { order: 1 !important; }
    body.post-type-archive-review .lunara-review-archive-toolbar { order: 3 !important; }
    body.post-type-archive-review .lunara-review-archive-shell { order: 4 !important; }
    body.post-type-archive-review .lunara-review-archive-slot-pagination { order: 6 !important; }
    body.post-type-archive-review .lunara-review-archive-hero {
        padding-top: 76px !important;
    }
    body.post-type-archive-review .lunara-review-archive-hero-shell {
        display: grid !important;
        gap: 28px !important;
        grid-template-columns: minmax(0, 1.3fr) minmax(280px, .7fr) !important;
        padding: 34px 36px !important;
    }
    body.post-type-archive-review .lunara-review-archive-debrief {
        display: block !important;
        padding: 22px 24px !important;
    }
    body.post-type-archive-review .lunara-review-archive-toolbar {
        display: flex !important;
        gap: 14px !important;
        padding: 10px !important;
    }
    body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-link {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) !important;
        min-height: 0 !important;
    }
    body.post-type-archive-review .lunara-pairing-desk-backdrop {
        inset: 0 !important;
        position: absolute !important;
    }
    @media (max-width: 620px) {
        body.post-type-archive-review .lunara-archive-page {
            gap: 46px !important;
            max-width: calc(100vw - 28px) !important;
            padding-inline: 0 !important;
            width: min(100%, calc(100vw - 28px)) !important;
        }
        body.post-type-archive-review .lunara-review-archive-hero-shell {
            gap: 20px !important;
            grid-template-columns: minmax(0, 1fr) !important;
            padding: 26px 20px !important;
        }
        body.post-type-archive-review .lunara-review-archive-toolbar {
            display: grid !important;
            gap: 16px !important;
        }
    }
`;

function rectSnapshot() {
    const selectors = {
        root: '#primary',
        hero: '.lunara-review-archive-hero',
        heroShell: '.lunara-review-archive-hero-shell',
        heroCopy: '.lunara-review-archive-hero-copy-wrap',
        debrief: '.lunara-review-archive-debrief',
        utility: '.lunara-review-archive-utility',
        toolbar: '.lunara-review-archive-toolbar',
        archiveGrid: '.lunara-review-archive-shell',
        lead: '.lunara-review-feature-card.is-lead',
        pagination: '.lunara-review-archive-slot-pagination',
        pairing: '.lunara-pairing-desk-section',
        pairingBackdrop: '.lunara-pairing-desk-backdrop',
        pairingCards: '.lunara-pair-cards',
        pairingHead: '.lunara-pairing-desk-head',
        pairingGrid: '.lunara-pair-cards-grid',
        pairingCard: '.lunara-pair-card',
        pairingPoster: '.lunara-pair-card-poster',
        pairingBody: '.lunara-pair-card-body',
        pairingRole: '.lunara-pair-card-role',
        pairingCardTitle: '.lunara-pair-card-title',
        pairingNote: '.lunara-pair-card-note',
        pairingChips: '.lunara-pair-card-chips',
        leadKicker: '.lunara-review-feature-copy > .lunara-home-section-kicker',
        leadTitle: '.lunara-review-feature-title',
        leadExcerpt: '.lunara-review-feature-excerpt',
        leadFooter: '.lunara-review-feature-footer',
        leadLedger: '.lunara-review-feature-ledger',
        leadLedgerLink: '.lunara-review-feature-ledger .lunara-oscar-ledger',
        leadCta: '.lunara-review-feature-cta',
    };

    return Object.fromEntries(Object.entries(selectors).map(([key, selector]) => {
        const element = document.querySelector(selector);
        if (!element) {
            return [key, null];
        }
        const rect = element.getBoundingClientRect();
        return [key, { top: rect.top, left: rect.left, width: rect.width, height: rect.height }];
    }));
}

function largestDelta(before, after, viewportHeight) {
    let max = 0;
    for (const key of Object.keys(before)) {
        if (!before[key] || !after[key]) {
            continue;
        }
        if (before[key].top >= viewportHeight && after[key].top >= viewportHeight) {
            continue;
        }
        for (const metric of ['top', 'left', 'width', 'height']) {
            max = Math.max(max, Math.abs(before[key][metric] - after[key][metric]));
        }
    }
    return max;
}

function styleSnapshot() {
    const read = (selector, properties) => {
        const element = document.querySelector(selector);
        if (!element) {
            return null;
        }
        const style = getComputedStyle(element);
        return Object.fromEntries(properties.map((property) => [property, style.getPropertyValue(property)]));
    };

    return {
        root: read('#primary', ['display', 'gap', 'width', 'max-width', 'padding', 'margin']),
        hero: read('.lunara-review-archive-hero', ['display', 'gap', 'padding', 'margin']),
        heroShell: read('.lunara-review-archive-hero-shell', ['display', 'gap', 'grid-template-columns', 'padding', 'margin']),
        heroCopy: read('.lunara-review-archive-hero-copy-wrap', ['display', 'gap', 'align-content', 'padding', 'margin']),
        heroKicker: read('.lunara-archive-hero-kicker', ['height', 'font-family', 'font-size', 'line-height', 'letter-spacing', 'margin']),
        heroTitle: read('.lunara-archive-hero-title', ['height', 'font-family', 'font-size', 'line-height', 'letter-spacing', 'margin']),
        heroDeck: read('.lunara-archive-hero-copy', ['height', 'font-family', 'font-size', 'line-height', 'margin']),
        debrief: read('.lunara-review-archive-debrief', ['display', 'gap', 'align-content', 'align-self', 'padding', 'margin']),
        debriefKicker: read('.lunara-review-archive-debrief-kicker', ['height', 'font-family', 'font-size', 'line-height', 'letter-spacing', 'margin']),
        debriefList: read('.lunara-review-archive-debrief-list', ['display', 'gap', 'grid-template-columns', 'padding', 'margin']),
        debriefItem: read('.lunara-review-archive-debrief-list li', ['display', 'gap', 'grid-template-columns', 'align-items', 'padding', 'margin']),
        debriefStrong: read('.lunara-review-archive-debrief-list strong', ['height', 'font-family', 'font-size', 'line-height', 'letter-spacing', 'margin']),
        debriefValue: read('.lunara-review-archive-debrief-list span', ['height', 'font-family', 'font-size', 'line-height', 'margin']),
        heroActions: read('.lunara-review-archive-hero-actions', ['display', 'gap', 'flex-wrap', 'padding', 'margin']),
        heroAction: read('.lunara-review-archive-hero-actions a', ['display', 'height', 'min-height', 'font-family', 'font-size', 'line-height', 'padding', 'margin']),
        toolbar: read('.lunara-review-archive-toolbar', ['height', 'gap', 'padding', 'grid-template-columns']),
        toolbarHead: read('.lunara-review-archive-toolbar-head', ['display', 'height', 'gap', 'padding', 'margin']),
        toolbarKicker: read('.lunara-review-archive-toolbar-head .lunara-home-section-kicker', ['height', 'font-family', 'font-size', 'line-height', 'letter-spacing', 'margin']),
        toolbarTitle: read('.lunara-review-archive-toolbar-head .lunara-section-title', ['height', 'font-family', 'font-size', 'line-height', 'letter-spacing', 'margin']),
        sort: read('.lunara-review-archive-sort', ['height', 'gap', 'padding', 'justify-content']),
        sortLabel: read('.lunara-review-archive-sort-label', ['display', 'height', 'font-family', 'font-size', 'line-height', 'letter-spacing', 'margin']),
        sortLink: read('.lunara-review-archive-sort-link', ['display', 'height', 'min-height', 'font-family', 'font-size', 'line-height', 'letter-spacing', 'padding', 'margin']),
        yearFilter: read('.lunara-review-archive-year-filter', ['display', 'height', 'gap', 'align-items', 'flex-wrap', 'padding', 'margin']),
        yearLabel: read('.lunara-review-archive-year-filter label', ['display', 'height', 'width', 'min-width', 'max-width', 'flex', 'font-family', 'font-size', 'font-weight', 'line-height', 'letter-spacing', 'margin']),
        yearSelect: read('.lunara-review-archive-year-filter select', ['display', 'height', 'width', 'min-width', 'max-width', 'flex', 'min-height', 'font-family', 'font-size', 'line-height', 'padding', 'margin']),
        yearButton: read('.lunara-review-archive-year-filter button', ['display', 'height', 'width', 'min-width', 'max-width', 'flex', 'min-height', 'font-family', 'font-size', 'font-weight', 'line-height', 'padding', 'margin']),
        utility: read('.lunara-review-archive-utility', ['height', 'padding', 'margin']),
        archiveGrid: read('.lunara-review-archive-shell', ['height', 'gap', 'padding', 'margin']),
        pagination: read('.lunara-review-archive-slot-pagination', ['height', 'padding', 'margin']),
        pairing: read('.lunara-review-archive-slot-pairing-desk', ['height', 'padding', 'margin']),
        lead: read('.lunara-review-feature-link', ['height', 'min-height', 'max-height', 'grid-template-columns', 'grid-template-rows']),
        media: read('.lunara-review-feature-media', ['width', 'height', 'aspect-ratio']),
        copy: read('.lunara-review-feature-copy', ['height', 'gap', 'padding']),
        excerpt: read('.lunara-review-feature-excerpt', ['height', 'display', '-webkit-line-clamp', 'line-height', 'margin']),
        backdrop: read('.lunara-pairing-desk-backdrop', ['display', 'position', 'inset']),
        pairingGrid: read('.lunara-pair-cards-grid', ['display', 'gap', 'grid-template-columns']),
        pairingHead: read('.lunara-pairing-desk-head', ['height', 'gap', 'grid-template-columns', 'margin-bottom']),
        pairingClaim: read('.lunara-pairing-desk-claim', ['height', 'font-size', 'line-height', 'margin', 'padding']),
        pairingTitle: read('.lunara-pairing-desk-head .lunara-home-section-title', ['height', 'font-size', 'line-height', 'margin']),
        pairingCopy: read('.lunara-pairing-desk-copy', ['height', 'font-size', 'line-height', 'margin']),
        pairingCard: read('.lunara-pair-card', ['display', 'height', 'grid-template-columns']),
        pairingPoster: read('.lunara-pair-card-poster', ['width', 'height', 'aspect-ratio']),
        pairingBody: read('.lunara-pair-card-body', ['height', 'gap', 'padding']),
        pairingRole: read('.lunara-pair-card-role', ['height', 'font-family', 'font-size', 'line-height', 'letter-spacing', 'margin']),
        pairingCardTitle: read('.lunara-pair-card-title', ['height', 'font-family', 'font-size', 'line-height', 'margin']),
        pairingTitleLink: read('.lunara-pair-card-title-link', ['height', 'display', 'padding', 'margin']),
        pairingNote: read('.lunara-pair-card-note', ['height', 'font-family', 'font-size', 'line-height', 'margin', 'text-wrap']),
        pairingChips: read('.lunara-pair-card-chips', ['height', 'margin', 'padding']),
        leadKicker: read('.lunara-review-feature-copy > .lunara-home-section-kicker', ['height', 'font-size', 'line-height', 'margin']),
        leadTitle: read('.lunara-review-feature-title', ['height', 'width', 'font-family', 'font-size', 'font-weight', 'line-height', 'letter-spacing', 'margin', 'overflow-wrap', 'text-wrap', 'word-break']),
        leadTitleLink: read('.lunara-review-feature-title-link', ['display', 'height', 'width', 'font-family', 'font-size', 'font-weight', 'line-height', 'letter-spacing', 'padding', 'margin', 'overflow-wrap', 'text-wrap', 'word-break']),
        leadFooter: read('.lunara-review-feature-footer', ['height', 'display', 'gap', 'margin', 'padding']),
        leadLedger: read('.lunara-review-feature-ledger', ['width', 'height', 'display', 'align-items']),
        leadLedgerLink: read('.lunara-review-feature-ledger .lunara-oscar-ledger', ['width', 'height', 'display', 'gap', 'margin', 'flex-wrap']),
        leadLedgerPill: read('.lunara-review-feature-ledger .lunara-oscar-ledger-pill', ['width', 'height', 'font-family', 'font-size', 'font-weight', 'letter-spacing', 'padding', 'margin']),
        leadLedgerCounts: read('.lunara-review-feature-ledger .lunara-oscar-ledger-counts', ['width', 'height', 'font-family', 'font-size', 'font-weight', 'letter-spacing']),
        leadCta: read('.lunara-review-feature-cta', ['width', 'height', 'display', 'font-family', 'font-size', 'font-weight', 'letter-spacing', 'padding']),
    };
}

function pairingCollisionSnapshot() {
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

    const cards = Array.from(document.querySelectorAll('.lunara-pairing-desk-section .lunara-pair-card')).map((card, index) => {
        const pseudo = getComputedStyle(card, '::after');
        if (pseudo.display === 'none' || !pseudo.content || pseudo.content === 'none' || pseudo.content === 'normal') {
            return { active: false, counterDisplay: pseudo.display, roleIntersection: 0, titleIntersection: 0 };
        }
        const cardRect = card.getBoundingClientRect();
        // Chromium exposes the unresolved counter() expression through
        // getComputedStyle(). Measure the visibly rendered 01/02/03 label.
        const content = String(index + 1).padStart(2, '0');
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        context.font = `${pseudo.fontStyle} ${pseudo.fontWeight} ${pseudo.fontSize} ${pseudo.fontFamily}`;
        const measuredWidth = context.measureText(content).width + Math.max(0, content.length - 1) * numeric(pseudo.letterSpacing);
        const counterWidth = numeric(pseudo.width) || measuredWidth;
        const counterHeight = numeric(pseudo.height) || numeric(pseudo.lineHeight) || numeric(pseudo.fontSize);
        const counter = {
            top: cardRect.top + numeric(pseudo.top),
            right: cardRect.right - numeric(pseudo.right),
            bottom: cardRect.top + numeric(pseudo.top) + counterHeight,
            left: cardRect.right - numeric(pseudo.right) - counterWidth,
        };
        const role = card.querySelector('.lunara-pair-card-role');
        const title = card.querySelector('.lunara-pair-card-title-link');

        return {
            active: true,
            counterDisplay: pseudo.display,
            counterFontSize: pseudo.fontSize,
            counterLineHeight: pseudo.lineHeight,
            counterRight: pseudo.right,
            counterTop: pseudo.top,
            rolePaddingRight: getComputedStyle(role).paddingRight,
            titlePaddingRight: getComputedStyle(card.querySelector('.lunara-pair-card-title')).paddingRight,
            roleIntersection: Math.max(0, ...textRects(role).map((rect) => intersectionArea(counter, rect))),
            titleIntersection: Math.max(0, ...textRects(title).map((rect) => intersectionArea(counter, rect))),
        };
    });

    return { cards };
}

function assertPairingCollisionFree(snapshot, label) {
    for (const [index, card] of snapshot.cards.entries()) {
        if (card.active || card.counterDisplay !== 'none') {
            throw new Error(`${label}: decorative Pairing numeral ${index + 1} must be hidden on phones.`);
        }
        if (card.roleIntersection > 0.5 || card.titleIntersection > 0.5) {
            throw new Error(`${label}: Pairing numeral ${index + 1} overlaps its role/title (${card.roleIntersection}/${card.titleIntersection}).`);
        }
    }
}

(async () => {
    const browser = await puppeteer.launch({ headless: true });
    const results = [];

    try {
        const scopeProbe = await browser.newPage();
        const probeSeed = renderCriticalSeed(orderScenarios.default);
        await scopeProbe.setContent(`<!doctype html><style>${probeSeed}</style><main id="primary" class="lunara-review-archive-page"><div class="lunara-review-archive-slot-pagination">Probe</div></main>`);
        const probeResult = await scopeProbe.evaluate(() => {
            const root = document.getElementById('primary');
            const computed = getComputedStyle(root);
            return {
                display: computed.display,
                fontToken: computed.getPropertyValue('--b').trim(),
            };
        });
        await scopeProbe.close();
        if (probeResult.display === 'flex' || probeResult.fontToken !== '') {
            throw new Error('Reviews structural seed must be inert when the renderer-owned lra class is absent.');
        }

        for (const [orderScenario, orders] of Object.entries(orderScenarios)) {
            const criticalSeed = renderCriticalSeed(orders);
            if (!criticalSeed || Buffer.byteLength(criticalSeed, 'utf8') > 12288) {
                throw new Error(`${orderScenario} emitted an invalid critical seed (${Buffer.byteLength(criticalSeed || '', 'utf8')}B).`);
            }
            for (const viewport of [{ width: 1440, height: 1000 }, { width: 782, height: 1000 }, { width: 390, height: 844 }]) {
                const page = await browser.newPage();
                await page.setViewport({ ...viewport, deviceScaleFactor: 1 });
                const heroActionsMarkup = orders.showHeroActions === false
                    ? ''
                    : '<div class="lunara-review-archive-hero-actions"><a href="#run">Browse The Run</a><a href="#ledger">Oscar Ledger</a><a href="#journal">Journal Desk</a></div>';
                const heroMarkup = orders.showHero === false
                    ? '<h1 class="screen-reader-text">Lunara Reviews</h1>'
                    : `<section class="lunara-home-section lunara-archive-hero lunara-review-archive-hero lunara-review-archive-slot-hero" style="order:${orders.hero}!important">
                        <div class="lunara-review-archive-hero-shell">
                            <div class="lunara-review-archive-hero-copy-wrap"><p class="lunara-archive-hero-kicker">Criticism Desk</p><h1 class="lunara-archive-hero-title">Lunara Reviews</h1><p class="lunara-archive-hero-copy">Spoiler-free criticism, full-spoiler companion files, festival finds, and the films that deserve a longer argument after the credits roll.</p></div>
                            <aside class="lunara-review-archive-debrief"><p class="lunara-review-archive-debrief-kicker">Reviews Command</p><ul class="lunara-review-archive-debrief-list"><li><strong>Archive Depth</strong><span>100</span></li><li><strong>Visible File</strong><span>9</span></li><li><strong>Latest Update</strong><span>Aug 15, 2026</span></li><li><strong>Current Order</strong><span>Release Timeline</span></li></ul>${heroActionsMarkup}</aside>
                        </div>
                    </section>`;
                const bodyClass = orders.bodyClass || 'post-type-archive-review';
                const yearFilterMarkup = orders.showYearFilter === false
                    ? ''
                    : `<form class="lunara-review-archive-year-filter"><label for="review-year">Release year</label><select id="review-year" name="review_year"><option>All years</option><option>2026</option><option>2025</option><option>2024</option></select><input type="hidden" name="sort" value="modified_desc"><button type="submit">Filter</button></form>`;
                const pairingMarkup = orders.showPairing === false
                    ? ''
                    : `<div class="lunara-review-archive-slot-pairing-desk" style="order:${orders.pairing}!important">
                        <section id="pairing-desk" class="lunara-home-section lunara-pairing-desk-section"><div class="lunara-pairing-desk-backdrop"></div><div class="lunara-pairing-desk-overlay"></div><div class="lunara-pairing-desk-inner"><div class="lunara-pairing-desk-head"><div><p class="lunara-pairing-desk-claim">The Lunara Method</p><h2 class="lunara-home-section-title">Every review ends with three more films.</h2></div><div class="lunara-pairing-desk-intro"><p class="lunara-pairing-desk-copy">Theme, counter-program, and career context keep the argument moving.</p></div></div><div class="lunara-pair-cards"><div class="lunara-pair-cards-grid" data-count="3">${['Theme Echo','Counter-Program','Career Context'].map((role, index) => `<article class="lunara-pair-card"><div class="lunara-pair-card-poster"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 2000 3000'%3E%3Crect width='2000' height='3000' fill='%230b1b2a'/%3E%3C/svg%3E" alt="Companion poster ${index + 1}"></div><div class="lunara-pair-card-body"><p class="lunara-pair-card-role">${role}</p><h3 class="lunara-pair-card-title"><a class="lunara-pair-card-title-link">Companion Film ${index + 1} <span class="lunara-pair-card-year">(2020)</span></a></h3><p class="lunara-pair-card-note">A complete editorial reason connects this companion film to the reviewed work and keeps the argument moving after the credits.</p><div class="lunara-pair-card-chips"><a class="lunara-pair-card-chip">IMDb</a></div></div></article>`).join('')}</div></div></div></section>
                    </div>`;
                await page.setContent(`<!doctype html>
                <html><head><meta name="viewport" content="width=device-width,initial-scale=1">
                <style id="stale-boost-critical">${productionBoostCritical}</style><style>${authorityCss(orders)}</style><style id="blocking-review-archive">${routeCss}</style><style id="current-structural-seed">${criticalSeed}</style></head>
                <body class="${bodyClass}"><header class="lunara-site-header" style="height:132px"></header>
                <main id="primary" class="lunara-archive-page lunara-review-archive-page lra">
                    ${heroMarkup}
                    <section class="lunara-home-section lunara-review-archive-utility lunara-review-archive-slot-utility" style="order:${orders.utility}!important">
                        <div class="lunara-review-archive-toolbar"><div class="lunara-home-section-head lunara-review-archive-toolbar-head"><div><p class="lunara-home-section-kicker">Review Order</p><h2 class="lunara-section-title">Release timeline or real editing activity</h2></div></div><nav class="lunara-review-archive-sort"><span class="lunara-review-archive-sort-label">Sort by</span><a class="lunara-review-archive-sort-link">Release Timeline</a><a class="lunara-review-archive-sort-link">Recently Updated</a><a class="lunara-review-archive-sort-link">Score</a></nav>${yearFilterMarkup}</div>
                    </section>
                    <section class="lunara-home-section lunara-review-archive-shell lunara-review-archive-slot-grid" style="order:${orders.grid}!important">
                        <article class="lunara-review-feature-card is-lead has-review-media has-review-quote"><div class="lunara-review-feature-layout lunara-review-feature-link"><a class="lunara-review-feature-media-link" href="#review"><div class="lunara-review-feature-media"><img class="lunara-review-feature-image" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1500 2000'%3E%3Crect width='1500' height='2000' fill='%23122232'/%3E%3C/svg%3E" width="1500" height="2000" loading="lazy" sizes="(max-width:900px) 88vw,520px" alt="Review poster"><span class="lunara-score-badge"><span class="lunara-stars">★★★★½</span></span></div></a><div class="lunara-review-feature-copy"><p class="lunara-home-section-kicker">Lunara Review</p><h2 class="lunara-review-feature-title"><a class="lunara-review-feature-title-link" href="#review">A Long, Exacting Lead Review Headline That Exercises the Real Archive Geometry</a></h2><p class="lunara-review-feature-excerpt lunara-review-feature-quote">A critic follows the argument through performance, form, history, and consequence, allowing enough real copy to expose any minimum-height-only first-paint mistake before the full archive stylesheet arrives.</p><div class="lunara-review-feature-footer"><div class="lunara-review-feature-ledger"><a class="lunara-oscar-ledger" href="#ledger"><span class="lunara-oscar-ledger-pill">Oscar Ledger</span><span class="lunara-oscar-ledger-counts">5 nominations · 2 wins</span></a></div><a class="lunara-section-link lunara-review-feature-cta" href="#review">Read Review</a></div></div></div></article>
                    </section>
                    <nav class="lunara-review-archive-slot-pagination" style="order:${orders.pagination}!important">Previous · Next</nav>
                    ${pairingMarkup}
                </main></body></html>`);

                const before = await page.evaluate(rectSnapshot);
                const beforeStyles = await page.evaluate(styleSnapshot);
                const aggregateStyle = await page.addStyleTag({ content: staleAggregateCss });
                await aggregateStyle.evaluate((element) => { element.id = 'stale-boost-aggregate'; });
                await page.addStyleTag({ content: `${baseCss}\n${componentCss}` });
                const after = await page.evaluate(rectSnapshot);
                const afterStyles = await page.evaluate(styleSnapshot);
                const afterPairingCollisions = await page.evaluate(pairingCollisionSnapshot);
                await page.evaluate(() => document.getElementById('stale-boost-critical').remove());
                const settled = await page.evaluate(rectSnapshot);
                const settledStyles = await page.evaluate(styleSnapshot);
                const settledPairingCollisions = await page.evaluate(pairingCollisionSnapshot);
                const deliveryDelta = largestDelta(before, after, viewport.height);
                const withdrawalDelta = largestDelta(after, settled, viewport.height);
                await page.evaluate(() => document.getElementById('stale-boost-aggregate').remove());
                const currentWithSeed = await page.evaluate(rectSnapshot);
                const currentWithSeedStyles = await page.evaluate(styleSnapshot);
                const currentPairingCollisions = await page.evaluate(pairingCollisionSnapshot);
                const aggregateWithdrawalDelta = largestDelta(settled, currentWithSeed, viewport.height);
                await page.evaluate(() => document.getElementById('current-structural-seed').remove());
                const canonical = await page.evaluate(rectSnapshot);
                const canonicalStyles = await page.evaluate(styleSnapshot);
                const canonicalPairingCollisions = await page.evaluate(pairingCollisionSnapshot);
                const seedPersistenceDelta = largestDelta(currentWithSeed, canonical, viewport.height);
                // Aggregate replacement happens on a later request, not as a
                // live-page media flip. Record it for diagnosis, but gate the
                // observable delivery/critical withdrawal and the settled
                // current-CSS design with-versus-without the persistent seed.
                const delta = Math.max(deliveryDelta, withdrawalDelta, seedPersistenceDelta);
                if (orders.showPairing !== false && viewport.width <= 820 && (beforeStyles.backdrop.display !== 'none' || afterStyles.backdrop.display !== 'none')) {
                    throw new Error(`Pairing backdrop must stay hidden through mobile first paint; before=${beforeStyles.backdrop.display} after=${afterStyles.backdrop.display}`);
                }
                if (orders.showPairing !== false && viewport.width <= 680) {
                    assertPairingCollisionFree(afterPairingCollisions, `${orderScenario}/${viewport.width}px deferred delivery`);
                    assertPairingCollisionFree(settledPairingCollisions, `${orderScenario}/${viewport.width}px critical withdrawal`);
                    assertPairingCollisionFree(currentPairingCollisions, `${orderScenario}/${viewport.width}px aggregate withdrawal`);
                    assertPairingCollisionFree(canonicalPairingCollisions, `${orderScenario}/${viewport.width}px settled current CSS`);
                }
                if (viewport.width > 820 && beforeStyles.copy.padding === '0px') {
                    throw new Error('Desktop lead copy padding must resolve from the saved authority variable before route CSS arrives.');
                }
                results.push({ orderScenario, viewport, delta, deliveryDelta, withdrawalDelta, aggregateWithdrawalDelta, seedPersistenceDelta, before, after, settled, currentWithSeed, canonical, beforeStyles, afterStyles, settledStyles, currentWithSeedStyles, canonicalStyles, afterPairingCollisions, settledPairingCollisions, currentPairingCollisions, canonicalPairingCollisions });
                await page.close();
            }
        }
    } finally {
        await browser.close();
    }

    const failed = results.find((result) => result.delta > 1 || result.seedPersistenceDelta > 1);
    if (failed) {
        const diagnostics = results.filter((result) => result.delta > 1).map((result) => {
            const phases = [
                { name: 'delivery', delta: result.deliveryDelta, from: result.before, to: result.after },
                { name: 'critical-withdrawal', delta: result.withdrawalDelta, from: result.after, to: result.settled },
                { name: 'seed-persistence', delta: result.seedPersistenceDelta, from: result.currentWithSeed, to: result.canonical },
            ].sort((left, right) => right.delta - left.delta);
            const dominant = phases[0];
            const changed = {};
            for (const key of Object.keys(dominant.from)) {
                if (!dominant.from[key] || !dominant.to[key]) {
                    continue;
                }
                const metrics = {};
                for (const metric of ['top', 'left', 'width', 'height']) {
                    const amount = dominant.to[key][metric] - dominant.from[key][metric];
                    if (Math.abs(amount) > 0.5) {
                        metrics[metric] = amount;
                    }
                }
                if (Object.keys(metrics).length) {
                    changed[key] = metrics;
                }
            }
            const persistenceStyleChanges = {};
            for (const key of Object.keys(result.currentWithSeedStyles)) {
                const seeded = result.currentWithSeedStyles[key];
                const canonical = result.canonicalStyles[key];
                if (!seeded || !canonical) {
                    continue;
                }
                const properties = {};
                for (const property of Object.keys(seeded)) {
                    if (seeded[property] !== canonical[property]) {
                        properties[property] = { seeded: seeded[property], canonical: canonical[property] };
                    }
                }
                if (Object.keys(properties).length) {
                    persistenceStyleChanges[key] = properties;
                }
            }
            const withdrawalStyleChanges = {};
            for (const key of Object.keys(result.afterStyles)) {
                const stale = result.afterStyles[key];
                const withdrawn = result.settledStyles[key];
                if (!stale || !withdrawn) {
                    continue;
                }
                const properties = {};
                for (const property of Object.keys(stale)) {
                    if (stale[property] !== withdrawn[property]) {
                        properties[property] = { stale: stale[property], withdrawn: withdrawn[property] };
                    }
                }
                if (Object.keys(properties).length) {
                    withdrawalStyleChanges[key] = properties;
                }
            }
            return {
                orderScenario: result.orderScenario,
                viewport: result.viewport,
                delta: result.delta,
                deliveryDelta: result.deliveryDelta,
                withdrawalDelta: result.withdrawalDelta,
                aggregateWithdrawalDelta: result.aggregateWithdrawalDelta,
                seedPersistenceDelta: result.seedPersistenceDelta,
                dominantPhase: dominant.name,
                changed,
                withdrawalStyleChanges,
                persistenceStyleChanges,
            };
        });
        process.stderr.write(`${JSON.stringify(diagnostics, null, 2)}\n`);
        throw new Error(`Reviews archive geometry moved ${failed.delta.toFixed(2)}px during delivery, optimizer withdrawal, or seed-persistence checks.`);
    }

    process.stdout.write(`Reviews archive first-paint geometry stable: ${JSON.stringify(results.map(({ orderScenario, viewport, delta, seedPersistenceDelta }) => ({ orderScenario, viewport, delta, seedPersistenceDelta })))}\n`);
})().catch((error) => {
    process.stderr.write(`${error.stack || error.message}\n`);
    process.exit(1);
});
