'use strict';

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const zlib = require('zlib');
const { execFileSync } = require('child_process');
let chromium;
try {
    ({ chromium } = require('playwright'));
} catch (playwrightError) {
    ({ chromium } = require('playwright-core'));
}

const themeRoot = path.resolve(__dirname, '..');
const fixturePath = path.join(themeRoot, 'tests/fixtures/journal-production-boost-critical-3.2.43.css');
const aggregateFixturePath = path.join(themeRoot, 'tests/fixtures/journal-production-boost-aggregate-3.2.43.css.gz');
const routeCss = fs.readFileSync(path.join(themeRoot, 'assets/css/lunara-journal-archive.css'), 'utf8');
const criticalModulePath = path.join(themeRoot, 'inc/journal-archive-critical.php');
const frontendSource = fs.readFileSync(path.join(themeRoot, 'inc/frontend.php'), 'utf8');
const mediaGuardMatch = frontendSource.match(/<script id="lunara-journal-archive-media-guard-js">\s*([\s\S]*?)\s*<\/script>/);
if (!mediaGuardMatch) {
    throw new Error('Journal media guard source could not be extracted for browser behavior coverage.');
}
const mediaGuardJs = mediaGuardMatch[1];
const currentGlobalCss = [
    fs.readFileSync(path.join(themeRoot, 'style.css'), 'utf8'),
    fs.readFileSync(path.join(themeRoot, 'assets/css/lunara-shell.css'), 'utf8'),
    fs.readFileSync(path.join(themeRoot, 'assets/css/lunara-public-guardrails.css'), 'utf8'),
].join('\n');
const productionCritical = fs.readFileSync(fixturePath, 'utf8').replace(/\r?\n$/, '');
const criticalRenderer = path.join(__dirname, 'journal-archive-critical-render.php');
const routeBytes = Buffer.byteLength(routeCss, 'utf8');
const routeHash = crypto.createHash('sha256').update(routeCss, 'utf8').digest('hex');
const criticalModuleHash = crypto.createHash('sha256').update(fs.readFileSync(criticalModulePath)).digest('hex');
if (routeBytes > 40960) {
    throw new Error(`Journal route CSS exceeds 40 KiB: ${routeBytes}B`);
}

const fixtureBytes = Buffer.byteLength(productionCritical, 'utf8');
const fixtureHash = crypto.createHash('sha256').update(productionCritical, 'utf8').digest('hex');
if (fixtureBytes !== 69065 || fixtureHash !== '314c0ba9849a2f0be1dfcded682f0ca39c4a302f9f855453c2a2f41ead517192') {
    throw new Error(`Journal production critical fixture drifted: ${fixtureBytes}B ${fixtureHash}`);
}

function loadStaleAggregate() {
    const exactPath = process.env.LUNARA_JOURNAL_STALE_AGGREGATE_FIXTURE || aggregateFixturePath;
    const raw = fs.readFileSync(path.resolve(exactPath));
    const rawHash = crypto.createHash('sha256').update(raw).digest('hex');
    if (!process.env.LUNARA_JOURNAL_STALE_AGGREGATE_FIXTURE && (raw.length !== 118870 || rawHash !== '7a382dc18f658789cdc697f5d3ca6d9a1e6eb604356981e50e8f6de62794a601')) {
        throw new Error(`Compressed Journal stale aggregate drifted: ${raw.length}B ${rawHash}`);
    }
    const bytes = raw[0] === 0x1f && raw[1] === 0x8b ? zlib.gunzipSync(raw) : raw;
    const hash = crypto.createHash('sha256').update(bytes).digest('hex');
    if (bytes.length !== 847152 || hash !== '6670f9bc1f11eb7c082e7c43e3f2ec66a2472d5131f3e3a935eb691415cfaeae') {
        throw new Error(`Exact Journal stale aggregate drifted: ${bytes.length}B ${hash}`);
    }
    return { css: bytes.toString('utf8'), exact: true, compressedBytes: raw.length, compressedHash: rawHash };
}

const staleAggregate = loadStaleAggregate();

function loadLicensedLabelFontFixtures() {
    const paths = {
        regular: process.env.LUNARA_TIEMPOS_TEXT_REGULAR_WOFF2 || '',
        bold: process.env.LUNARA_TIEMPOS_TEXT_BOLD_WOFF2 || '',
    };
    if (!paths.regular && !paths.bold) return null;
    if (!paths.regular || !paths.bold) {
        throw new Error('Both LUNARA_TIEMPOS_TEXT_REGULAR_WOFF2 and LUNARA_TIEMPOS_TEXT_BOLD_WOFF2 are required.');
    }
    return Object.fromEntries(Object.entries(paths).map(([weight, fontPath]) => {
        const resolved = path.resolve(fontPath);
        const body = fs.readFileSync(resolved);
        return [weight, {
            body,
            bytes: body.length,
            sha256: crypto.createHash('sha256').update(body).digest('hex'),
        }];
    }));
}

const licensedLabelFontFixtures = loadLicensedLabelFontFixtures();

function renderHeadCss(presentation) {
    const encoded = Buffer.from(JSON.stringify(presentation || {})).toString('base64');
    return JSON.parse(execFileSync('php', [criticalRenderer, encoded], { encoding: 'utf8' }));
}

const defaultOrder = ['hero', 'deskbar', 'filters', 'toolbar', 'grid', 'retention', 'pagination'];
const scenarios = {
    default: { order: defaultOrder, gallery: false, retentionMedia: true },
    'custom-order': { order: ['hero', 'toolbar', 'grid', 'deskbar', 'retention', 'filters', 'pagination'], gallery: true, retentionMedia: true },
    'hero-hidden': { order: defaultOrder, hidden: ['hero'], gallery: true, retentionMedia: false },
    taxonomy: { order: defaultOrder, taxonomy: true, gallery: false, retentionMedia: true },
};

const imageData = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="1920" height="1080"><rect width="1920" height="1080" fill="#17283b"/><circle cx="600" cy="400" r="260" fill="#c9a961"/></svg>');

function localTelemetryScript() {
    return `window.__lunaraLocal={cls:0,shifts:[],lcp:[],fonts:[],lead:[]};
const recordFont=e=>window.__lunaraLocal.fonts.push({event:e,time:performance.now(),status:document.fonts?document.fonts.status:'unsupported'});
const recordLead=e=>{const i=document.querySelector('.lunara-journal-archive-card.is-lead .lunara-review-grid-poster'),w=i&&i.closest('.lunara-review-grid-poster-wrap');if(!i)return;const s=getComputedStyle(i),r=i.getBoundingClientRect(),q=w&&w.getBoundingClientRect();window.__lunaraLocal.lead.push({event:e,time:performance.now(),className:i.className,opacity:s.opacity,transitionDuration:s.transitionDuration,currentSrc:i.currentSrc,complete:i.complete,naturalWidth:i.naturalWidth,rect:{x:r.x,y:r.y,width:r.width,height:r.height},wrapperRect:q?{x:q.x,y:q.y,width:q.width,height:q.height}:null});};
try{new PerformanceObserver(l=>l.getEntries().forEach(e=>{if(e.hadRecentInput)return;window.__lunaraLocal.cls+=e.value;window.__lunaraLocal.shifts.push({startTime:e.startTime,value:e.value,sources:(e.sources||[]).map(s=>({tag:s.node&&s.node.tagName||'',id:s.node&&s.node.id||'',className:s.node&&typeof s.node.className==='string'?s.node.className:'',withinPrimary:!!(s.node&&s.node.closest&&s.node.closest('#primary')),previousRect:s.previousRect?{x:s.previousRect.x,y:s.previousRect.y,width:s.previousRect.width,height:s.previousRect.height}:null,currentRect:s.currentRect?{x:s.currentRect.x,y:s.currentRect.y,width:s.currentRect.width,height:s.currentRect.height}:null}))});})).observe({type:'layout-shift',buffered:true});new PerformanceObserver(l=>l.getEntries().forEach(e=>window.__lunaraLocal.lcp.push({startTime:e.startTime,loadTime:e.loadTime||0,renderTime:e.renderTime||0,size:e.size||0,url:e.url||'',tag:e.element&&e.element.tagName||'',className:e.element&&typeof e.element.className==='string'?e.element.className:''}))).observe({type:'largest-contentful-paint',buffered:true});}catch(e){}
recordFont('document-start');if(document.fonts){document.fonts.addEventListener('loading',()=>recordFont('loading'));document.fonts.addEventListener('loadingdone',()=>recordFont('loadingdone'));document.fonts.ready.then(()=>recordFont('ready'));}
document.addEventListener('DOMContentLoaded',()=>{recordLead('domcontentloaded');requestAnimationFrame(()=>recordLead('first-animation-frame'));setTimeout(()=>recordLead('after-transition-window'),650);setTimeout(()=>recordLead('after-runtime-fallback'),1850);},{once:true});`;
}

function laneMarkup(slug, scenario) {
    if (slug === 'hero') {
        return '<header class="lunara-archive-hero lunara-journal-archive-hero lunara-journal-archive-slot-hero"><p class="lunara-archive-hero-kicker">Journal</p><h1 class="lunara-archive-hero-title">Lunara Journal</h1><p class="lunara-archive-hero-copy">Trade reporting, criticism, and cinematic context moving through one live editorial desk.</p></header>';
    }
    if (slug === 'deskbar') {
        return '<div class="lunara-journal-archive-deskbar lunara-journal-archive-slot-deskbar"><span><strong>On the desk:</strong> 302 files</span><span><strong>Latest file:</strong> August 15, 2026</span><span><strong>Desk mix:</strong> 18 lanes</span></div>';
    }
    if (slug === 'filters') {
        const pills = ['All', 'Industry', 'Trailers', 'Awards', 'Interviews', 'Festivals'].map((label, index) => `<a class="lunara-journal-filter-pill${index === 0 ? ' is-active' : ''}" href="#">${label} <span class="lunara-journal-filter-count">(${12 + index})</span></a>`).join('');
        return `<div class="lunara-journal-filter-groups lunara-journal-archive-slot-filters"><nav class="lunara-journal-archive-filters"><span class="lunara-journal-filter-label">Sections</span>${pills}</nav><nav class="lunara-journal-archive-filters"><span class="lunara-journal-filter-label">Topics</span>${pills}</nav></div>`;
    }
    if (slug === 'toolbar') {
        return '<div class="lunara-editorial-archive-toolbar lunara-journal-archive-toolbar lunara-journal-archive-slot-toolbar"><div class="lunara-home-section-head lunara-editorial-archive-toolbar-head"><div><p class="lunara-home-section-kicker">Desk Order</p><h2 class="lunara-section-title">Latest files from the desk</h2><p class="lunara-home-section-summary">Follow the Journal in the order that suits the reporting day.</p></div></div><div class="lunara-archive-sort"><a class="lunara-archive-sort-link is-active">Newest Filed</a><a class="lunara-archive-sort-link">Oldest Filed</a><a class="lunara-archive-sort-link">Recently Updated</a></div></div>';
    }
    if (slug === 'grid') {
        const card = (index, media) => {
            const visualLead = index === 0 && !scenario.paged && !scenario.taxonomy;
            const hasMedia = media && !(index === 0 && scenario.undersizedLead);
            return `<article class="lunara-review-grid-card lunara-journal-archive-card${visualLead ? ' is-lead' : ''}${hasMedia ? ' has-media' : ' is-text-brief'}"><a class="lunara-review-grid-link" href="#">${hasMedia ? `<div class="lunara-review-grid-poster-wrap"><img class="lunara-review-grid-poster" src="${imageData}" width="1920" height="1080" loading="${visualLead ? 'eager' : 'lazy'}" fetchpriority="${visualLead ? 'high' : 'auto'}" alt="Wide editorial still"></div>` : ''}<div class="lunara-review-grid-copy"><p class="lunara-review-grid-kicker">${visualLead ? 'Lead file' : 'From the desk'}</p><p class="lunara-dispatch-type lunara-journal-archive-card-type">Industry Dispatch</p><div class="lunara-journal-card-provenance"><span class="lunara-journal-card-provenance-pill">Original reporting</span></div><h3 class="lunara-review-grid-title">Lanterns Is Doing Something Stranger Than DC Usually Gets Away With</h3><p class="lunara-review-grid-excerpt">A specific reported argument gives the archive card enough editorial density to exercise its real line rhythm.</p><div class="lunara-review-grid-footer lunara-journal-archive-card-footer"><span class="lunara-review-grid-meta">August 15, 2026</span><span class="lunara-review-grid-updated">Updated today</span><span class="lunara-journal-archive-card-cta">Read file</span></div></div></a></article>`;
        };
        return `<section class="lunara-journal-archive-grid lunara-review-grid lunara-review-archive-uniform lunara-journal-archive-slot-grid">${card(0, true)}${card(1, true)}${card(2, false)}${card(3, true)}${card(4, true)}${card(5, false)}${card(6, true)}${card(7, true)}</section>`;
    }
    if (slug === 'retention') {
        const media = scenario.retentionMedia ? `<span class="lunara-journal-archive-retention-media" style="--lunara-retention-focus-x:31%;--lunara-retention-focus-y:67%"><img src="${imageData}" width="1920" height="1080" alt="Retention still"></span>` : '';
        const retentionCard = `<article class="lunara-journal-archive-retention-card${scenario.retentionMedia ? ' has-media' : ''}"><a class="lunara-journal-archive-retention-card-link" href="#">${media}<span class="lunara-journal-archive-retention-kicker">Latest File</span><strong>Open the newest desk entry</strong><span>Stay with the freshest reported movement before it settles into the wider conversation.</span></a>${scenario.retentionMedia ? '<small class="lunara-journal-archive-retention-provenance"><span>Studio credit</span> · <a href="#">Studio source</a></small>' : ''}</article>`;
        const cards = scenario.retentionCards === false ? '' : `<div class="lunara-journal-archive-retention-head"><p class="lunara-home-section-kicker">Desk Channels</p><h2 class="lunara-section-title">Keep the file moving</h2></div><div class="lunara-journal-archive-retention-grid">${retentionCard}${retentionCard}${retentionCard}</div>`;
        const gallery = scenario.gallery && !scenario.taxonomy ? `<section class="lunara-journal-archive-gallery"><header class="lunara-journal-archive-gallery-head"><p class="lunara-home-section-kicker">Visual File</p><h3 class="lunara-section-title">From the Journal desk</h3><p class="lunara-journal-archive-gallery-copy">Three frames selected for the archive.</p></header><div class="lunara-journal-archive-gallery-grid"><figure class="lunara-journal-archive-gallery-item"><div class="lunara-journal-archive-gallery-media" style="--lunara-gallery-focus-x:31%;--lunara-gallery-focus-y:67%"><img class="lunara-journal-archive-gallery-image" src="${imageData}" width="1920" height="1080" alt="Archive gallery still"></div><figcaption><p>Archive caption</p><small>Studio credit · <a class="lunara-journal-archive-gallery-source" href="#">Studio source</a></small></figcaption></figure></div></section>` : '';
        return `<section class="lunara-journal-archive-retention lunara-journal-archive-slot-retention">${cards}${gallery}</section>`;
    }
    return '<nav class="lunara-archive-pagination lunara-journal-archive-slot-pagination"><div class="nav-links"><a class="page-numbers">1</a><a class="page-numbers">2</a><a class="next page-numbers">Older →</a></div></nav>';
}

function documentHtml(scenario, headCss) {
    const hidden = new Set(scenario.hidden || []);
    const lanes = scenario.order.filter((slug) => !hidden.has(slug)).map((slug) => laneMarkup(slug, scenario)).join('');
    const fallbackH1 = hidden.has('hero') ? '<h1 class="screen-reader-text">Lunara Journal</h1>' : '';
    const bodyClass = scenario.taxonomy
        ? 'archive post-type-archive-journal tax-journal_topic lunara-journal-taxonomy-archive'
        : 'archive post-type-archive-journal';
    const telemetry = scenario.captureCls ? `<script>${localTelemetryScript()}</script>` : '';
    const base = scenario.fontBase ? '<base href="https://lunara.test/">' : '';
    const globalCss = scenario.includeGlobalCss ? `<style id="canonical-global">${currentGlobalCss}</style>` : '';
    const labelFontClass = scenario.customLabelFont ? '' : ' is-label-font-tiempos';
    const customLabelCss = scenario.customLabelFont ? '<style id="custom-label-token">:root{--lunara-font-label:Lora,serif}</style>' : '';
    return `<!doctype html><html><head><meta charset="utf-8">${base}<meta name="viewport" content="width=device-width,initial-scale=1">${telemetry}${globalCss}${customLabelCss}<style id="production-critical">${productionCritical}</style><style id="blocking-route">${routeCss}</style><style id="journal-vars">${headCss.variables}</style><style id="current-structural-seed">${headCss.seed}</style></head><body class="${bodyClass}"><div class="site-main"><main id="primary" class="lunara-archive-page lunara-journal-archive-page${labelFontClass}">${fallbackH1}${lanes}</main></div><script>${mediaGuardJs}</script></body></html>`;
}

function rectSnapshot() {
    const selectors = {
        root: '#primary',
        hero: '.lunara-journal-archive-slot-hero',
        heroKicker: '.lunara-archive-hero-kicker',
        heroTitle: '.lunara-archive-hero-title',
        heroCopy: '.lunara-archive-hero-copy',
        deskbar: '.lunara-journal-archive-slot-deskbar',
        deskSpan: '.lunara-journal-archive-slot-deskbar span',
        deskStrong: '.lunara-journal-archive-slot-deskbar strong',
        filters: '.lunara-journal-archive-slot-filters',
        filterRow: '.lunara-journal-archive-filters',
        filterPill: '.lunara-journal-filter-pill',
        toolbar: '.lunara-journal-archive-slot-toolbar',
        toolbarHead: '.lunara-editorial-archive-toolbar-head',
        toolbarKicker: '.lunara-editorial-archive-toolbar-head .lunara-home-section-kicker',
        toolbarTitle: '.lunara-editorial-archive-toolbar-head .lunara-section-title',
        toolbarSummary: '.lunara-home-section-summary',
        sort: '.lunara-archive-sort',
        sortLink: '.lunara-archive-sort-link',
        grid: '.lunara-journal-archive-slot-grid',
        lead: '.lunara-journal-archive-card.is-lead',
        card: '.lunara-journal-archive-card:not(.is-lead)',
        cardLink: '.lunara-journal-archive-card:not(.is-lead) .lunara-review-grid-link',
        cardMedia: '.lunara-journal-archive-card:not(.is-lead) .lunara-review-grid-poster-wrap',
        cardCopy: '.lunara-journal-archive-card:not(.is-lead) .lunara-review-grid-copy',
        cardKicker: '.lunara-journal-archive-card:not(.is-lead) .lunara-review-grid-kicker',
        cardType: '.lunara-journal-archive-card:not(.is-lead) .lunara-dispatch-type',
        cardTitle: '.lunara-journal-archive-card:not(.is-lead) .lunara-review-grid-title',
        cardExcerpt: '.lunara-journal-archive-card:not(.is-lead) .lunara-review-grid-excerpt',
        cardFooter: '.lunara-journal-archive-card:not(.is-lead) .lunara-review-grid-footer',
        retention: '.lunara-journal-archive-slot-retention',
        retentionHead: '.lunara-journal-archive-retention-head',
        retentionGrid: '.lunara-journal-archive-retention-grid',
        retentionCard: '.lunara-journal-archive-retention-card',
        retentionLink: '.lunara-journal-archive-retention-card-link',
        retentionKicker: '.lunara-journal-archive-retention-kicker',
        retentionTitle: '.lunara-journal-archive-retention-card-link > strong',
        retentionCopy: '.lunara-journal-archive-retention-card-link > span:not(.lunara-journal-archive-retention-kicker):not(.lunara-journal-archive-retention-media)',
        retentionProvenance: '.lunara-journal-archive-retention-provenance',
        retentionCredit: '.lunara-journal-archive-retention-provenance > span',
        retentionSource: '.lunara-journal-archive-retention-provenance > a',
        retentionMedia: '.lunara-journal-archive-retention-media',
        retentionImage: '.lunara-journal-archive-retention-media img',
        gallery: '.lunara-journal-archive-gallery',
        galleryMedia: '.lunara-journal-archive-gallery-media',
        galleryImage: '.lunara-journal-archive-gallery-media img',
        pagination: '.lunara-journal-archive-slot-pagination',
        paginationLink: '.lunara-journal-archive-slot-pagination .page-numbers',
    };
    return Object.fromEntries(Object.entries(selectors).map(([key, selector]) => {
        const element = document.querySelector(selector);
        if (!element) return [key, null];
        const rect = element.getBoundingClientRect();
        return [key, { top: rect.top, left: rect.left, width: rect.width, height: rect.height }];
    }));
}

function computedStyleSnapshot() {
    const selectors = {
        root: '#primary',
        heroKicker: '.lunara-archive-hero-kicker',
        heroTitle: '.lunara-archive-hero-title',
        heroCopy: '.lunara-archive-hero-copy',
        deskSpan: '.lunara-journal-archive-slot-deskbar span',
        deskStrong: '.lunara-journal-archive-slot-deskbar strong',
        filterPill: '.lunara-journal-filter-pill',
        toolbarHead: '.lunara-editorial-archive-toolbar-head',
        toolbarKicker: '.lunara-editorial-archive-toolbar-head .lunara-home-section-kicker',
        toolbarTitle: '.lunara-editorial-archive-toolbar-head .lunara-section-title',
        toolbarSummary: '.lunara-home-section-summary',
        sort: '.lunara-archive-sort',
        sortLink: '.lunara-archive-sort-link',
        grid: '.lunara-journal-archive-slot-grid',
        card: '.lunara-journal-archive-card:not(.is-lead)',
        cardLink: '.lunara-journal-archive-card:not(.is-lead) .lunara-review-grid-link',
        cardCopy: '.lunara-journal-archive-card:not(.is-lead) .lunara-review-grid-copy',
        cardKicker: '.lunara-journal-archive-card:not(.is-lead) .lunara-review-grid-kicker',
        cardType: '.lunara-journal-archive-card:not(.is-lead) .lunara-dispatch-type',
        cardTitle: '.lunara-journal-archive-card:not(.is-lead) .lunara-review-grid-title',
        cardExcerpt: '.lunara-journal-archive-card:not(.is-lead) .lunara-review-grid-excerpt',
        cardFooter: '.lunara-journal-archive-card:not(.is-lead) .lunara-review-grid-footer',
        retentionLink: '.lunara-journal-archive-retention-card-link',
        retentionKicker: '.lunara-journal-archive-retention-kicker',
        retentionTitle: '.lunara-journal-archive-retention-card-link > strong',
        retentionCopy: '.lunara-journal-archive-retention-card-link > span:not(.lunara-journal-archive-retention-kicker):not(.lunara-journal-archive-retention-media)',
        retentionProvenance: '.lunara-journal-archive-retention-provenance',
        retentionCredit: '.lunara-journal-archive-retention-provenance > span',
        retentionSource: '.lunara-journal-archive-retention-provenance > a',
        pagination: '.lunara-journal-archive-slot-pagination',
        paginationNav: '.lunara-journal-archive-slot-pagination .nav-links',
        paginationLink: '.lunara-journal-archive-slot-pagination .page-numbers',
    };
    const properties = [
        'display', 'boxSizing', 'width', 'maxWidth', 'minWidth', 'height', 'minHeight', 'maxHeight',
        'marginTop', 'marginRight', 'marginBottom', 'marginLeft',
        'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
        'gap', 'rowGap', 'columnGap', 'gridTemplateColumns', 'gridTemplateRows',
        'alignItems', 'alignContent', 'justifyContent', 'flexWrap',
        'fontFamily', 'fontSize', 'fontWeight', 'lineHeight', 'letterSpacing', 'textTransform',
        'overflow', 'overflowX', 'whiteSpace', 'webkitLineClamp', 'webkitBoxOrient',
        'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth', 'borderRadius',
    ];
    return Object.fromEntries(Object.entries(selectors).map(([key, selector]) => {
        const element = document.querySelector(selector);
        if (!element) return [key, null];
        const style = getComputedStyle(element);
        return [key, Object.fromEntries(properties.map((property) => [property, style[property]]))];
    }));
}

function computedStyleDelta(before, after) {
    const changed = {};
    for (const key of Object.keys(before)) {
        if (!before[key] || !after[key]) continue;
        const properties = {};
        for (const property of Object.keys(before[key])) {
            if (before[key][property] !== after[key][property]) {
                properties[property] = [before[key][property], after[key][property]];
            }
        }
        if (Object.keys(properties).length) changed[key] = properties;
    }
    return changed;
}

function compactComputedStyles(styles) {
    const properties = ['display', 'width', 'maxWidth', 'minHeight', 'maxHeight', 'marginTop', 'marginBottom', 'paddingTop', 'paddingBottom', 'gap', 'alignItems', 'justifyContent', 'flexWrap', 'fontFamily', 'fontSize', 'fontWeight', 'lineHeight', 'letterSpacing', 'textTransform', 'webkitLineClamp'];
    return Object.fromEntries(Object.entries(styles).map(([key, values]) => [
        key,
        values ? Object.fromEntries(properties.map((property) => [property, values[property]])) : null,
    ]));
}

function largestDelta(before, after) {
    let max = 0;
    let source = '';
    for (const key of Object.keys(before)) {
        if (!before[key] || !after[key]) continue;
        for (const metric of ['top', 'left', 'width', 'height']) {
            const delta = Math.abs(before[key][metric] - after[key][metric]);
            if (delta > max) {
                max = delta;
                source = `${key}.${metric}`;
            }
        }
    }
    return { max, source };
}

function deltaDetails(before, after) {
    const details = [];
    for (const key of Object.keys(before)) {
        if (!before[key] || !after[key]) continue;
        for (const metric of ['top', 'left', 'width', 'height']) {
            const delta = Math.abs(before[key][metric] - after[key][metric]);
            if (delta > 0.5) details.push({ key, metric, delta, before: before[key][metric], after: after[key][metric] });
        }
    }
    return details.sort((a, b) => b.delta - a.delta).slice(0, 20);
}

async function snapshot(page) {
    return page.evaluate(rectSnapshot);
}

async function styleSnapshot(page) {
    return page.evaluate(computedStyleSnapshot);
}

async function addLateStyle(page, id, css) {
    await page.evaluate(({ id, css }) => {
        const style = document.createElement('style');
        style.id = id;
        style.textContent = css;
        document.head.appendChild(style);
    }, { id, css });
    await page.evaluate(async () => {
        if (document.fonts && document.fonts.ready) await document.fonts.ready;
        await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
    });
}

async function removeStyle(page, id) {
    await page.evaluate((styleId) => {
        const style = document.getElementById(styleId);
        if (style) style.remove();
    }, id);
    await page.evaluate(() => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve))));
}

async function assertStructure(page, scenario) {
    const result = await page.evaluate(() => {
        const root = document.getElementById('primary');
        const lanes = Array.from(root.children).filter((node) => /lunara-journal-archive-slot-/.test(node.className)).map((node) => {
            const match = String(node.className).match(/lunara-journal-archive-slot-([a-z]+)/);
            return match ? match[1] : '';
        });
        const retentionMedia = document.querySelector('.lunara-journal-archive-retention-media');
        const retentionImage = retentionMedia ? retentionMedia.querySelector('img') : null;
        const galleryMedia = document.querySelector('.lunara-journal-archive-gallery-media');
        const galleryImage = galleryMedia ? galleryMedia.querySelector('img') : null;
        const leadImage = document.querySelector('.lunara-journal-archive-card.is-lead .lunara-review-grid-poster');
        const leadImageStyle = leadImage ? getComputedStyle(leadImage) : null;
        const ratio = (element) => {
            if (!element) return null;
            const rect = element.getBoundingClientRect();
            return rect.height ? rect.width / rect.height : null;
        };
        return {
            lanes,
            h1: root.querySelectorAll('h1').length,
            galleryCount: root.querySelectorAll('.lunara-journal-archive-gallery').length,
            overflow: document.documentElement.scrollWidth - window.innerWidth,
            retentionRatio: ratio(retentionMedia),
            retentionFill: retentionMedia && retentionImage ? Math.max(Math.abs(retentionMedia.getBoundingClientRect().width - retentionImage.getBoundingClientRect().width), Math.abs(retentionMedia.getBoundingClientRect().height - retentionImage.getBoundingClientRect().height)) : null,
            retentionObjectPosition: retentionImage ? getComputedStyle(retentionImage).objectPosition : null,
            retentionIntrinsic: retentionImage ? !!(retentionImage.getAttribute('width') && retentionImage.getAttribute('height')) : null,
            galleryRatio: ratio(galleryMedia),
            galleryFill: galleryMedia && galleryImage ? Math.max(Math.abs(galleryMedia.getBoundingClientRect().width - galleryImage.getBoundingClientRect().width), Math.abs(galleryMedia.getBoundingClientRect().height - galleryImage.getBoundingClientRect().height)) : null,
            galleryObjectPosition: galleryImage ? getComputedStyle(galleryImage).objectPosition : null,
            galleryIntrinsic: galleryImage ? !!(galleryImage.getAttribute('width') && galleryImage.getAttribute('height')) : null,
            leadImageOpacity: leadImageStyle ? leadImageStyle.opacity : null,
            leadImageTransitionDuration: leadImageStyle ? leadImageStyle.transitionDuration : null,
            leadImageLoadedClass: leadImage ? leadImage.classList.contains('lunara-img-loaded') : null,
            overflowers: Array.from(root.querySelectorAll('*')).map((element) => {
                const rect = element.getBoundingClientRect();
                const rootRect = root.getBoundingClientRect();
                return { selector: element.className || element.tagName, left: rect.left - rootRect.left, right: rect.right - rootRect.right, scroll: element.scrollWidth - element.clientWidth };
            }).filter((entry) => entry.left < -1 || entry.right > 1).sort((a, b) => Math.max(Math.abs(b.left), Math.abs(b.right)) - Math.max(Math.abs(a.left), Math.abs(a.right))).slice(0, 8),
        };
    });
    const expectedLanes = scenario.order.filter((slug) => !(scenario.hidden || []).includes(slug));
    if (JSON.stringify(result.lanes) !== JSON.stringify(expectedLanes)) throw new Error(`DOM order mismatch: ${JSON.stringify(result.lanes)} vs ${JSON.stringify(expectedLanes)}`);
    if (result.h1 !== 1) throw new Error(`Expected exactly one H1, found ${result.h1}`);
    const expectedGallery = scenario.gallery && !scenario.taxonomy ? 1 : 0;
    if (result.galleryCount !== expectedGallery) throw new Error(`Gallery scope mismatch: ${result.galleryCount} vs ${expectedGallery}`);
    if (result.overflow > 1) throw new Error(`Horizontal overflow ${result.overflow}px: ${JSON.stringify(result.overflowers)}`);
    if (scenario.retentionMedia) {
        if (Math.abs(result.retentionRatio - 16 / 9) > 0.02 || result.retentionFill > 1 || result.retentionObjectPosition !== '31% 67%' || !result.retentionIntrinsic) {
            throw new Error(`Retention media geometry failed: ${JSON.stringify(result)}`);
        }
    } else if (result.retentionRatio !== null) {
        throw new Error('Empty retention media emitted a reserved chamber.');
    }
    if (expectedGallery && (Math.abs(result.galleryRatio - 16 / 9) > 0.02 || result.galleryFill > 1 || result.galleryObjectPosition !== '31% 67%' || !result.galleryIntrinsic)) {
        throw new Error(`Gallery media geometry failed: ${JSON.stringify(result)}`);
    }
    const expectsVisualLead = !scenario.paged && !scenario.taxonomy && !(scenario.hidden || []).includes('grid');
    if (expectsVisualLead && (result.leadImageOpacity !== '1' || result.leadImageTransitionDuration !== '0s' || result.leadImageLoadedClass !== false)) {
        throw new Error(`The page-one Journal lead must paint without waiting for the shared image-reveal runtime: ${JSON.stringify(result)}`);
    }
    if (!expectsVisualLead && result.leadImageOpacity !== null) {
        throw new Error('Paged and taxonomy archives must not expose a visual-lead image override.');
    }
}

async function openTestPage(browser, width, height = 900) {
    const context = await browser.newContext({ viewport: { width, height }, deviceScaleFactor: 1 });
    const page = await context.newPage();
    return { context, page };
}

function largestRectArrayDelta(before, after) {
    let max = 0;
    let source = '';
    for (let index = 0; index < Math.min(before.length, after.length); index += 1) {
        for (const metric of ['x', 'y', 'width', 'height']) {
            const delta = Math.abs(before[index][metric] - after[index][metric]);
            if (delta > max) {
                max = delta;
                source = `${index}.${metric}`;
            }
        }
    }
    return { max, source };
}

async function labelFontContractSnapshot(page) {
    return page.evaluate(() => {
        const rect = (element) => {
            const value = element.getBoundingClientRect();
            return { x: value.x, y: value.y, width: value.width, height: value.height };
        };
        const cards = Array.from(document.querySelectorAll('.lunara-journal-archive-card'));
        const shellSelectors = [
            '#primary',
            '.lunara-journal-archive-slot-hero',
            '.lunara-journal-archive-slot-deskbar',
            '.lunara-journal-archive-slot-filters',
            '.lunara-journal-archive-slot-toolbar',
            '.lunara-journal-archive-slot-grid',
            '.lunara-journal-archive-slot-retention',
            '.lunara-journal-archive-retention-grid',
            '.lunara-journal-archive-slot-pagination',
            '.lunara-journal-archive-filters',
        ];
        const parentElements = shellSelectors.map((selector) => document.querySelector(selector)).concat(
            cards.flatMap((card) => [
                card,
                card.querySelector('.lunara-review-grid-link'),
                card.querySelector('.lunara-review-grid-copy'),
                card.querySelector('.lunara-review-grid-footer'),
            ])
        );
        const childElements = cards.flatMap((card) => [
            card.querySelector('.lunara-review-grid-meta'),
            card.querySelector('.lunara-review-grid-updated'),
            card.querySelector('.lunara-journal-archive-card-cta'),
        ]);
        const visibilitySelectors = [
            '.lunara-archive-hero-kicker',
            '.lunara-journal-filter-label',
            '.lunara-journal-filter-pill',
            '.lunara-archive-sort-link',
            '.lunara-journal-card-provenance-pill',
            '.lunara-journal-archive-card-cta',
            '.lunara-journal-archive-retention-kicker',
            '.page-numbers',
        ];
        const visible = visibilitySelectors.every((selector) => {
            const element = document.querySelector(selector);
            if (!element) return false;
            const style = getComputedStyle(element);
            const bounds = element.getBoundingClientRect();
            return style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0' && bounds.width > 0 && bounds.height > 0;
        });
        return {
            cardCount: cards.length,
            nonblank: cards.every((card) => ['.lunara-review-grid-title', '.lunara-review-grid-excerpt', '.lunara-review-grid-meta', '.lunara-review-grid-updated', '.lunara-journal-archive-card-cta'].every((selector) => (card.querySelector(selector)?.textContent || '').trim() !== '')),
            parentRects: parentElements.filter(Boolean).map(rect),
            childRects: childElements.filter(Boolean).map(rect),
            visible,
            marker: document.querySelector('#primary')?.classList.contains('is-label-font-tiempos') || false,
            ctaFontFamily: getComputedStyle(document.querySelector('.lunara-journal-archive-card-cta')).fontFamily,
            font400: document.fonts ? document.fonts.check('400 16px "Lunara Journal Tiempos Text"') : false,
            font700: document.fonts ? document.fonts.check('700 16px "Lunara Journal Tiempos Text"') : false,
        };
    });
}

async function assertLicensedLabelFontCohorts(browser, headCss) {
    if (!licensedLabelFontFixtures) {
        return { executed: false, reason: 'Licensed local WOFF2 fixtures were not provided.' };
    }
    const cases = [
        { name: 'licensed-immediate', delayMs: 0, missing: false, expectLoaded: true },
        { name: 'licensed-delayed-optional', delayMs: 750, missing: false, expectLoaded: null },
        { name: 'forced-missing-tiempos', delayMs: 0, missing: true, expectLoaded: false },
    ];
    const runs = [];
    for (const fontCase of cases) {
        for (const width of [390, 768, 1440]) {
            const testPage = await openTestPage(browser, width, width === 390 ? 844 : 900);
            const { context, page } = testPage;
            const requests = [];
            page.on('request', (request) => requests.push(request.url()));
            await page.route('https://lunara.test/wp-content/uploads/lunara-fonts/v1/**', async (route) => {
                const name = path.basename(new URL(route.request().url()).pathname).toLowerCase();
                if (fontCase.missing) {
                    await route.abort('failed');
                    return;
                }
                const fixture = name === 'tiempostext-regular.woff2'
                    ? licensedLabelFontFixtures.regular
                    : (name === 'tiempostext-bold.woff2' ? licensedLabelFontFixtures.bold : null);
                if (!fixture) {
                    await route.abort('blockedbyclient');
                    return;
                }
                if (fontCase.delayMs) await new Promise((resolve) => setTimeout(resolve, fontCase.delayMs));
                try {
                    await route.fulfill({ status: 200, contentType: 'font/woff2', body: fixture.body });
                } catch (error) {
                    if (!/cancel|closed|handled|intercept/i.test(String(error))) throw error;
                }
            });
            const scenario = { order: defaultOrder, gallery: false, retentionMedia: true, captureCls: true, fontBase: true, includeGlobalCss: true };
            await page.setContent(documentHtml(scenario, headCss), { waitUntil: 'load' });
            await page.evaluate(() => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve))));
            const early = await labelFontContractSnapshot(page);
            await Promise.race([
                page.evaluate(() => document.fonts && document.fonts.ready),
                page.waitForTimeout(2600),
            ]);
            await page.waitForTimeout(fontCase.delayMs ? 350 : 100);
            const settled = await labelFontContractSnapshot(page);
            await addLateStyle(page, 'stale-aggregate', staleAggregate.css);
            const delivered = await labelFontContractSnapshot(page);
            const deliveryTelemetry = await page.evaluate(() => window.__lunaraLocal || { cls: 0, shifts: [] });
            await removeStyle(page, 'production-critical');
            const criticalWithdrawn = await labelFontContractSnapshot(page);
            await removeStyle(page, 'stale-aggregate');
            const canonicalWithSeed = await labelFontContractSnapshot(page);
            await removeStyle(page, 'current-structural-seed');
            const canonicalWithoutSeed = await labelFontContractSnapshot(page);
            const stages = [early, settled, delivered, criticalWithdrawn, canonicalWithSeed, canonicalWithoutSeed];
            const parentDeltas = stages.slice(1).map((stage, index) => largestRectArrayDelta(stages[index].parentRects, stage.parentRects));
            const childDeltas = stages.slice(1).map((stage, index) => largestRectArrayDelta(stages[index].childRects, stage.childRects));
            const hardParentDeltas = [parentDeltas[0], parentDeltas[1], parentDeltas[2], parentDeltas[4]];
            const hardChildDeltas = [childDeltas[0], childDeltas[1], childDeltas[2], childDeltas[4]];
            const fontRequests = requests.filter((url) => url.startsWith('https://lunara.test/wp-content/uploads/lunara-fonts/v1/'));
            const externalRequests = requests.filter((url) => !url.startsWith('https://lunara.test/wp-content/uploads/lunara-fonts/v1/'));
            const themeOwnedShiftEntries = (deliveryTelemetry.shifts || []).filter((shift) => shift.value > 0.02 && shift.sources.some((source) => source.withinPrimary));
            const clsLimit = width === 390 ? 0.015 : 0.03;
            const contractStable = stages.every((stage) => stage.cardCount === 8 && stage.nonblank && stage.parentRects.length === 42 && stage.childRects.length === 24 && stage.visible);
            const loaded = settled.font400 && settled.font700;
            if (!contractStable || hardParentDeltas.some((delta) => delta.max > 1) || hardChildDeltas.some((delta) => delta.max > 1) || deliveryTelemetry.cls > clsLimit || themeOwnedShiftEntries.length || externalRequests.length || !fontRequests.length || (fontCase.expectLoaded !== null && loaded !== fontCase.expectLoaded)) {
                throw new Error(`Journal licensed label-font cohort failed: ${JSON.stringify({ case: fontCase.name, width, contractStable, parentDeltas, childDeltas, hardParentDeltas, hardChildDeltas, deliveryCls: deliveryTelemetry.cls, clsLimit, themeOwnedShiftEntries, fontRequests, externalRequests, loaded, expectLoaded: fontCase.expectLoaded, stages })}`);
            }
            runs.push({ case: fontCase.name, width, hardParentDeltas, hardChildDeltas, aggregateWithdrawalParentDiagnostic: parentDeltas[3], aggregateWithdrawalChildDiagnostic: childDeltas[3], deliveryCls: deliveryTelemetry.cls, clsLimit, themeOwnedShiftEntries: themeOwnedShiftEntries.length, fontRequestCount: fontRequests.length, externalRequestCount: externalRequests.length, loaded });
            await context.close();
        }
    }
    const customBypassRuns = [];
    for (const width of [390, 768, 1440]) {
        const testPage = await openTestPage(browser, width, width === 390 ? 844 : 900);
        const { context, page } = testPage;
        const requests = [];
        page.on('request', (request) => requests.push(request.url()));
        await page.route('https://lunara.test/wp-content/uploads/lunara-fonts/v1/**', (route) => route.abort('blockedbyclient'));
        await page.setContent(documentHtml({ order: defaultOrder, gallery: false, retentionMedia: true, fontBase: true, includeGlobalCss: true, customLabelFont: true }, headCss), { waitUntil: 'load' });
        await page.evaluate(() => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve))));
        const snapshot = await labelFontContractSnapshot(page);
        const externalRequests = requests.filter((url) => !url.startsWith('https://lunara.test/wp-content/uploads/lunara-fonts/v1/'));
        if (snapshot.marker || !/^Lora\b/.test(snapshot.ctaFontFamily) || externalRequests.length) {
            throw new Error(`Journal custom Studio label bypass failed: ${JSON.stringify({ width, snapshot, requests, externalRequests })}`);
        }
        customBypassRuns.push({ width, marker: snapshot.marker, ctaFontFamily: snapshot.ctaFontFamily, externalRequestCount: externalRequests.length });
        await context.close();
    }
    return {
        executed: true,
        fixture: {
            regular: { bytes: licensedLabelFontFixtures.regular.bytes, sha256: licensedLabelFontFixtures.regular.sha256 },
            bold: { bytes: licensedLabelFontFixtures.bold.bytes, sha256: licensedLabelFontFixtures.bold.sha256 },
        },
        runs,
        customBypassRuns,
    };
}

async function assertMediaFailureBehavior(browser, headCss) {
    const galleryOnly = await openTestPage(browser, 390);
    const galleryOnlyPage = galleryOnly.page;
    await galleryOnlyPage.setContent(documentHtml({ order: defaultOrder, gallery: true, retentionMedia: false, retentionCards: false }, headCss), { waitUntil: 'load' });
    const galleryOnlyResult = await galleryOnlyPage.evaluate(() => {
        document.querySelector('.lunara-journal-archive-gallery-media img').dispatchEvent(new Event('error'));
        return {
            gallery: document.querySelectorAll('.lunara-journal-archive-gallery').length,
            retention: document.querySelectorAll('.lunara-journal-archive-slot-retention').length,
        };
    });
    await galleryOnly.context.close();
    if (galleryOnlyResult.gallery !== 0 || galleryOnlyResult.retention !== 0) {
        throw new Error(`Failed final gallery media left an empty outer retention chamber: ${JSON.stringify(galleryOnlyResult)}`);
    }

    const cardsAndGallery = await openTestPage(browser, 390);
    const cardsAndGalleryPage = cardsAndGallery.page;
    await cardsAndGalleryPage.setContent(documentHtml({ order: defaultOrder, gallery: true, retentionMedia: true }, headCss), { waitUntil: 'load' });
    const cardsAndGalleryResult = await cardsAndGalleryPage.evaluate(() => {
        document.querySelector('.lunara-journal-archive-gallery-media img').dispatchEvent(new Event('error'));
        return {
            gallery: document.querySelectorAll('.lunara-journal-archive-gallery').length,
            retention: document.querySelectorAll('.lunara-journal-archive-slot-retention').length,
            cards: document.querySelectorAll('.lunara-journal-archive-retention-card').length,
        };
    });
    await cardsAndGallery.context.close();
    if (cardsAndGalleryResult.gallery !== 0 || cardsAndGalleryResult.retention !== 1 || cardsAndGalleryResult.cards !== 3) {
        throw new Error(`Failed gallery media must preserve existing retention cards: ${JSON.stringify(cardsAndGalleryResult)}`);
    }

    const featuredMedia = await openTestPage(browser, 390);
    const featuredMediaPage = featuredMedia.page;
    await featuredMediaPage.setContent(documentHtml({ order: defaultOrder, gallery: false, retentionMedia: true }, headCss), { waitUntil: 'load' });
    const featuredMediaResult = await featuredMediaPage.evaluate(() => {
        const card = document.querySelector('.lunara-journal-archive-card.is-lead');
        card.querySelector('.lunara-review-grid-poster-wrap img').dispatchEvent(new Event('error'));
        return {
            card: !!card,
            copy: card.querySelectorAll('.lunara-review-grid-copy').length,
            wrapper: card.querySelectorAll('.lunara-review-grid-poster-wrap').length,
            hasMedia: card.classList.contains('has-media'),
            failed: card.classList.contains('is-media-failed'),
        };
    });
    await featuredMedia.context.close();
    if (!featuredMediaResult.card || featuredMediaResult.copy !== 1 || featuredMediaResult.wrapper !== 0 || featuredMediaResult.hasMedia || !featuredMediaResult.failed) {
        throw new Error(`Failed featured image must become a text card with no reserved media chamber: ${JSON.stringify(featuredMediaResult)}`);
    }
}

async function assertCardScopeBehavior(browser, headCss) {
    for (const scenario of [
        { name: 'paged', paged: true, order: defaultOrder, gallery: false, retentionMedia: false },
        { name: 'taxonomy', taxonomy: true, order: defaultOrder, gallery: false, retentionMedia: false },
    ]) {
        const testPage = await openTestPage(browser, 390, 844);
        await testPage.page.setContent(documentHtml(scenario, headCss), { waitUntil: 'load' });
        const state = await testPage.page.evaluate(() => ({
            leads: document.querySelectorAll('.lunara-journal-archive-card.is-lead').length,
            leadKickers: Array.from(document.querySelectorAll('.lunara-review-grid-kicker')).filter((node) => node.textContent.trim() === 'Lead file').length,
            eager: document.querySelectorAll('.lunara-journal-archive-card img[loading="eager"]').length,
            high: document.querySelectorAll('.lunara-journal-archive-card img[fetchpriority="high"]').length,
        }));
        await testPage.context.close();
        if (state.leads || state.leadKickers || state.eager || state.high) {
            throw new Error(`${scenario.name} Journal cards must remain uniform/lazy/non-high: ${JSON.stringify(state)}`);
        }
    }

    const undersized = await openTestPage(browser, 390);
    await undersized.page.setContent(documentHtml({ order: defaultOrder, gallery: false, retentionMedia: false, undersizedLead: true }, headCss), { waitUntil: 'load' });
    const undersizedState = await undersized.page.evaluate(() => {
        const card = document.querySelector('.lunara-journal-archive-card');
        return {
            textLed: card.classList.contains('is-text-brief'),
            hasMedia: card.classList.contains('has-media'),
            wrappers: card.querySelectorAll('.lunara-review-grid-poster-wrap').length,
            images: card.querySelectorAll('img').length,
        };
    });
    await undersized.context.close();
    if (!undersizedState.textLed || undersizedState.hasMedia || undersizedState.wrappers || undersizedState.images) {
        throw new Error(`An undersized source must leave no browser media chamber: ${JSON.stringify(undersizedState)}`);
    }
}

async function assertLocalClsCohort(browser, headCss) {
    const runs = [];
    const recordLead = async (page, event) => page.evaluate((label) => {
        const image = document.querySelector('.lunara-journal-archive-card.is-lead .lunara-review-grid-poster');
        const wrapper = image && image.closest('.lunara-review-grid-poster-wrap');
        if (!image || !window.__lunaraLocal) return;
        const style = getComputedStyle(image);
        const rect = image.getBoundingClientRect();
        const wrapperRect = wrapper && wrapper.getBoundingClientRect();
        window.__lunaraLocal.lead.push({
            event: label,
            time: performance.now(),
            className: image.className,
            opacity: style.opacity,
            transitionDuration: style.transitionDuration,
            currentSrc: image.currentSrc,
            complete: image.complete,
            naturalWidth: image.naturalWidth,
            rect: { x: rect.x, y: rect.y, width: rect.width, height: rect.height },
            wrapperRect: wrapperRect ? { x: wrapperRect.x, y: wrapperRect.y, width: wrapperRect.width, height: wrapperRect.height } : null,
        });
    }, event);
    for (let index = 0; index < 7; index += 1) {
        const testPage = await openTestPage(browser, 390, 844);
        const page = testPage.page;
        await page.setContent(documentHtml({ order: defaultOrder, gallery: false, retentionMedia: true, captureCls: true }, headCss), { waitUntil: 'load' });
        await recordLead(page, 'before-aggregate');
        await addLateStyle(page, 'stale-aggregate', staleAggregate.css + '\n' + currentGlobalCss);
        await recordLead(page, 'after-aggregate');
        await page.waitForTimeout(1900);
        await recordLead(page, 'settled');
        const evidence = await page.evaluate(() => {
            const image = document.querySelector('.lunara-journal-archive-card.is-lead .lunara-review-grid-poster');
            const resources = image ? performance.getEntriesByType('resource').filter((entry) => entry.name === image.currentSrc).map((entry) => ({
                name: entry.name,
                startTime: entry.startTime,
                responseStart: entry.responseStart,
                responseEnd: entry.responseEnd,
                duration: entry.duration,
                transferSize: entry.transferSize || 0,
                encodedBodySize: entry.encodedBodySize || 0,
            })) : [];
            return { ...window.__lunaraLocal, resources, viewport: { width: innerWidth, height: innerHeight, deviceScaleFactor: devicePixelRatio } };
        });
        await testPage.context.close();
        if (evidence.viewport.width !== 390 || evidence.viewport.height !== 844 || evidence.viewport.deviceScaleFactor !== 1) {
            throw new Error(`Journal local CLS viewport drifted: ${JSON.stringify(evidence.viewport)}`);
        }
        const rects = evidence.lead.map((entry) => entry.wrapperRect).filter(Boolean);
        const first = rects[0];
        const chamberDelta = first ? rects.reduce((max, rect) => Math.max(max, Math.abs(rect.x - first.x), Math.abs(rect.y - first.y), Math.abs(rect.width - first.width), Math.abs(rect.height - first.height)), 0) : Number.POSITIVE_INFINITY;
        const required = ['domcontentloaded', 'first-animation-frame', 'after-transition-window', 'after-runtime-fallback', 'before-aggregate', 'after-aggregate', 'settled'];
        const completeTimeline = required.every((event) => evidence.lead.some((entry) => entry.event === event));
        const runtimeIndependent = completeTimeline && evidence.lead.every((entry) => entry.opacity === '1' && entry.transitionDuration === '0s' && !String(entry.className).split(/\s+/).includes('lunara-img-loaded'));
        const themeOwnedShiftEntries = evidence.shifts.filter((shift) => shift.value > 0.02 && shift.sources.some((source) => source.withinPrimary));
        runs.push({ run: index + 1, viewport: evidence.viewport, cls: evidence.cls, shifts: evidence.shifts, themeOwnedShiftEntries, lcp: evidence.lcp.at(-1) || null, fonts: evidence.fonts, lead: evidence.lead, resources: evidence.resources, chamberDelta, completeTimeline, runtimeIndependent });
    }
    const values = runs.map((run) => run.cls).sort((a, b) => a - b);
    const medianCls = values[Math.floor(values.length / 2)];
    const over005 = values.filter((value) => value > 0.05).length;
    const over010 = values.filter((value) => value > 0.10).length;
    if (medianCls > 0.02 || over005 > 1 || over010 > 0 || runs.some((run) => run.chamberDelta > 1 || !run.runtimeIndependent || run.themeOwnedShiftEntries.length > 0)) {
        throw new Error(`Journal local 390px CLS gate failed: ${JSON.stringify({ medianCls, over005, over010, runs })}`);
    }
    return { viewport: { width: 390, height: 844, deviceScaleFactor: 1 }, cacheMode: 'fresh-context', runtimePresent: false, evidenceMode: 'candidate-only', samples: runs.length, medianCls, over005, over010, runs };
}

(async () => {
    const browser = await chromium.launch({ headless: true, executablePath: process.env.LUNARA_BROWSER_EXECUTABLE || undefined, args: ['--no-sandbox', '--disable-setuid-sandbox'] });
    const browserVersion = browser.version();
    if (!staleAggregate.exact) throw new Error('Journal first-paint gate requires the exact production aggregate.');
    const headCss = renderHeadCss({});
    const headBytes = Buffer.byteLength(headCss.variables + headCss.seed, 'utf8');
    if (headBytes > 8192) throw new Error(`Journal head CSS exceeds 8 KiB: ${headBytes}B`);
    const results = [];
    let localClsCohort;
    let labelFontCohort;
    try {
        for (const [scenarioName, scenario] of Object.entries(scenarios)) {
            for (const width of [390, 768, 1440]) {
                const testPage = await openTestPage(browser, width);
                const page = testPage.page;
                await page.setContent(documentHtml(scenario, headCss), { waitUntil: 'load' });
                await page.evaluate(() => document.fonts && document.fonts.ready);
                await assertStructure(page, scenario);
                const before = await snapshot(page);
                const beforeStyles = await styleSnapshot(page);

                await addLateStyle(page, 'stale-aggregate', staleAggregate.css + '\n' + currentGlobalCss);
                const afterDelivery = await snapshot(page);
                const afterDeliveryStyles = await styleSnapshot(page);
                await assertStructure(page, scenario);

                await removeStyle(page, 'production-critical');
                const afterCriticalWithdrawal = await snapshot(page);

                await removeStyle(page, 'stale-aggregate');
                const canonicalWithSeed = await snapshot(page);

                await removeStyle(page, 'current-structural-seed');
                const canonicalWithoutSeed = await snapshot(page);
                await assertStructure(page, scenario);

                const delivery = largestDelta(before, afterDelivery);
                const criticalWithdrawal = largestDelta(afterDelivery, afterCriticalWithdrawal);
                const aggregateWithdrawal = largestDelta(afterCriticalWithdrawal, canonicalWithSeed);
                const seedPersistence = largestDelta(canonicalWithSeed, canonicalWithoutSeed);
                const result = { scenario: scenarioName, width, exactAggregate: staleAggregate.exact, deliveryDelta: delivery, criticalWithdrawalDelta: criticalWithdrawal, aggregateWithdrawalDiagnostic: aggregateWithdrawal, seedPersistenceDelta: seedPersistence };
                if (delivery.max > 1 || criticalWithdrawal.max > 1 || seedPersistence.max > 1) {
                    throw new Error(`Journal first-paint gate failed: ${JSON.stringify({ ...result, deliveryDetails: deltaDetails(before, afterDelivery), deliveryStyleDetails: computedStyleDelta(beforeStyles, afterDeliveryStyles), deliveredComputedStyles: compactComputedStyles(afterDeliveryStyles) })}`);
                }
                results.push(result);
                await testPage.context.close();
            }
        }
        await assertMediaFailureBehavior(browser, headCss);
        await assertCardScopeBehavior(browser, headCss);
        localClsCohort = await assertLocalClsCohort(browser, headCss);
        labelFontCohort = await assertLicensedLabelFontCohorts(browser, headCss);
    } finally {
        await browser.close();
    }
    const candidateBase = execFileSync('git', ['rev-parse', 'HEAD'], { cwd: themeRoot, encoding: 'utf8' }).trim();
    const candidateIndexTree = execFileSync('git', ['write-tree'], { cwd: themeRoot, encoding: 'utf8' }).trim();
    const candidateDiff = execFileSync('git', ['-c', 'core.autocrlf=false', '-c', 'core.safecrlf=false', 'diff', '--binary', 'HEAD'], { cwd: themeRoot });
    const report = { capturedAt: new Date().toISOString(), browserVersion, candidateBase, candidateIndexTree, candidateDiffHash: crypto.createHash('sha256').update(candidateDiff).digest('hex'), fixtureBytes, fixtureHash, aggregateBytes: 847152, aggregateHash: '6670f9bc1f11eb7c082e7c43e3f2ec66a2472d5131f3e3a935eb691415cfaeae', compressedAggregateBytes: staleAggregate.compressedBytes, compressedAggregateHash: staleAggregate.compressedHash, routeBytes, routeHash, criticalModuleHash, variablesHash: crypto.createHash('sha256').update(headCss.variables, 'utf8').digest('hex'), seedHash: crypto.createHash('sha256').update(headCss.seed, 'utf8').digest('hex'), headBytes, exactAggregate: staleAggregate.exact, localClsCohort, labelFontCohort, results };
    const serialized = JSON.stringify(report, null, 2) + '\n';
    if (process.env.LUNARA_JOURNAL_EVIDENCE_OUT) fs.writeFileSync(path.resolve(process.env.LUNARA_JOURNAL_EVIDENCE_OUT), serialized);
    process.stdout.write(serialized);
})().catch((error) => {
    console.error(error.stack || error.message || String(error));
    process.exit(1);
});
