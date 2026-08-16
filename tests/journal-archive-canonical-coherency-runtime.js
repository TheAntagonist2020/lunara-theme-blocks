'use strict';

/**
 * Offline fail-closed coverage for the anonymous canonical Journal
 * cache-coherency sentinel. Fixtures mirror the markup shapes actually
 * observed in production: the Blocksy `<main id="main">` wrapper, the legacy
 * 3.2.43 Journal root without `id="primary"`, and the modern candidate root
 * with the version binding. No network is used.
 */

const assert = require('node:assert/strict');
const {
	CANONICAL_JOURNAL_URL,
	EXPECTED_CARD_COUNT,
	EXPECTED_LEAD_COUNT,
	APPROVED_DEFAULT_LABEL_TOKEN,
	VERSION_ATTRIBUTE,
	STRUCTURAL_VARS_STYLE_ID,
	STRUCTURAL_SEED_STYLE_ID,
	ROUTE_STYLESHEET_LINK_ID,
	LEGACY_STUDIO_STYLE_ID,
	isCanonicalJournalUrl,
	analyzeJournalCanonicalCoherency,
} = require('./tools/lunara-journal-canonical-coherency-gate');

assert.equal(CANONICAL_JOURNAL_URL, 'https://lunarafilm.com/journal/', 'The sentinel must target the exact anonymous canonical Journal URL.');
assert.equal(EXPECTED_CARD_COUNT, 8, 'Page one must demand exactly eight cards.');
assert.equal(EXPECTED_LEAD_COUNT, 1, 'Page one must demand exactly one lead.');
assert.equal(APPROVED_DEFAULT_LABEL_TOKEN, 'tiempos-text', 'The approved default label token is tiempos-text.');

function cardMarkup({ lead = false, blankTitle = false, index = 0 } = {}) {
	const leadClass = lead ? ' is-lead' : '';
	const title = blankTitle ? ' ' : 'Journal entry ' + index;
	return '<article class="lunara-review-grid-card lunara-journal-archive-card' + leadClass + ' has-media">'
		+ '<div class="lunara-review-grid-copy">'
		+ '<p class="lunara-review-grid-kicker">Signal</p>'
		+ '<p class="lunara-dispatch-type lunara-journal-archive-card-type">News</p>'
		+ '<h3 class="lunara-review-grid-title">' + title + '</h3>'
		+ '<div class="lunara-review-grid-footer lunara-journal-archive-card-footer">'
		+ '<span class="lunara-review-grid-meta">August 16, 2026</span>'
		+ '<span class="lunara-journal-archive-card-cta">Read the entry</span>'
		+ '</div></div></article>';
}

function retentionMarkup() {
	return '<article class="lunara-journal-archive-retention-card has-media">'
		+ '<span class="lunara-journal-archive-retention-kicker">Keep going</span>'
		+ '<strong>Retention title</strong></article>';
}

function candidateHtml({
	version = '3.2.49',
	marker = true,
	preload = true,
	cards = EXPECTED_CARD_COUNT,
	leads = EXPECTED_LEAD_COUNT,
	blankTitles = 0,
	legacyRoot = false,
	modernRoot = true,
	legacyStudioStyle = false,
	vars = true,
	seed = true,
	routeLink = true,
	structuralInBody = false,
} = {}) {
	const markerClass = marker ? ' is-label-font-tiempos' : '';
	const preloadTag = preload
		? '<link rel="preload" href="https://lunarafilm.com/wp-content/uploads/lunara-fonts/v1/TiemposText-Bold.woff2" as="font" type="font/woff2" crossorigin />'
		: '';
	const varsTag = vars ? '<style id="' + STRUCTURAL_VARS_STYLE_ID + '">:root{--x:1}</style>' : '';
	const seedTag = seed ? '<style id="' + STRUCTURAL_SEED_STYLE_ID + '">.lunara-journal-archive-grid{display:grid}</style>' : '';
	const routeLinkTag = routeLink
		? "<link rel='stylesheet' id='" + ROUTE_STYLESHEET_LINK_ID + "' href='https://lunarafilm.com/wp-content/themes/lunara-theme-blocks-20260513-2300/assets/css/lunara-journal-archive.css?ver=1786874737' type='text/css' media='all' />"
		: '';
	const legacyStudioTag = legacyStudioStyle ? '<style id="' + LEGACY_STUDIO_STYLE_ID + '">:root{--legacy:1}</style>' : '';
	const structural = varsTag + seedTag + routeLinkTag;
	let cardBlocks = '';
	for (let i = 0; i < cards; i += 1) {
		cardBlocks += cardMarkup({ lead: i < leads, blankTitle: i >= cards - blankTitles, index: i });
	}
	const modernMain = modernRoot
		? '<main id="primary" class="lunara-archive-page lunara-journal-archive-page' + markerClass + '" ' + VERSION_ATTRIBUTE + '="' + version + '">'
			+ '<section class="lunara-journal-archive-grid lunara-review-grid lunara-review-archive-uniform lunara-journal-archive-slot-grid">'
			+ cardBlocks
			+ '</section>' + retentionMarkup() + '</main>'
		: '';
	const legacyMain = legacyRoot
		? '<main class="lunara-archive-page lunara-journal-archive-page"><section class="lunara-journal-archive-grid">' + cardBlocks + '</section></main>'
		: '';
	return '<!doctype html><html><head><meta charset="utf-8">'
		+ '<style id="jetpack-boost-critical-css">.lunara-journal-archive-page{opacity:1}</style>'
		+ preloadTag
		+ (structuralInBody ? '' : structural)
		+ legacyStudioTag
		+ '</head><body class="post-type-archive post-type-archive-journal">'
		+ (structuralInBody ? structural : '')
		+ '<main id="main" class="site-main hfeed" tabindex="-1">'
		+ legacyMain + modernMain
		+ '</main></body></html>';
}

function analyze(overrides = {}, htmlOptions = {}) {
	return analyzeJournalCanonicalCoherency({
		url: CANONICAL_JOURNAL_URL,
		finalUrl: CANONICAL_JOURNAL_URL,
		statusCode: 200,
		html: candidateHtml(htmlOptions),
		expectedVersion: '3.2.49',
		expectedLabelToken: APPROVED_DEFAULT_LABEL_TOKEN,
		...overrides,
	});
}

// Canonical URL discipline: only the exact anonymous no-query route is proof.
assert.equal(isCanonicalJournalUrl('https://lunarafilm.com/journal/'), true);
assert.equal(isCanonicalJournalUrl('https://lunarafilm.com/journal/?sort=date_asc'), false, 'Query-bearing variants are diagnostic only.');
assert.equal(isCanonicalJournalUrl('https://lunarafilm.com/journal/?nocache=1'), false, 'Cache-busted URLs can never satisfy the canonical proof.');
assert.equal(isCanonicalJournalUrl('https://lunarafilm.com/journal/page/2/'), false);
assert.equal(isCanonicalJournalUrl('https://www.lunarafilm.com/journal/'), false, 'Only the exact canonical host is proof.');
assert.equal(isCanonicalJournalUrl('http://lunarafilm.com/journal/'), false);

// The coherent candidate response passes every contract.
const coherent = analyze();
assert.equal(coherent.coherent, true, 'A coherent candidate response must pass: ' + coherent.failures.join('; '));
assert.equal(coherent.detail.modernRootCount, 1);
assert.equal(coherent.detail.legacyRootCount, 0);
assert.equal(coherent.detail.cardCount, 8);
assert.equal(coherent.detail.leadCount, 1);

// The valid custom Lora bypass passes only without Tiempos marker and preload.
const lora = analyze({ expectedLabelToken: 'lora' }, { marker: false, preload: false });
assert.equal(lora.coherent, true, 'A valid custom Lora response must pass: ' + lora.failures.join('; '));
const loraLeakedMarker = analyze({ expectedLabelToken: 'lora' }, { marker: true, preload: false });
assert.equal(loraLeakedMarker.coherent, false, 'The Tiempos marker must never leak onto a custom Lora route.');
const loraLeakedPreload = analyze({ expectedLabelToken: 'lora' }, { marker: false, preload: true });
assert.equal(loraLeakedPreload.coherent, false, 'The Tiempos preload must never leak onto a custom Lora route.');

// Default token requires both marker and preload — a half-applied identity fails.
assert.equal(analyze({}, { marker: false, preload: true }).coherent, false, 'Default token without the marker is incoherent.');
assert.equal(analyze({}, { marker: true, preload: false }).coherent, false, 'Default token without the Bold preload is incoherent.');

// The exact 3.2.48 canary failure shape: legacy cached root, no modern root.
const canaryShape = analyze({}, { modernRoot: false, legacyRoot: true, vars: false, seed: false, routeLink: false });
assert.equal(canaryShape.coherent, false);
assert.equal(canaryShape.detail.legacyRootCount, 1);
assert.equal(canaryShape.detail.modernRootCount, 0);
assert.equal(canaryShape.contracts['journal-modern-root-identity'], false);
assert.equal(canaryShape.contracts['theme-version-binding'], false, 'Legacy cached HTML must fail the version binding, not slip past it.');

// Mixed identity: both roots present at once must fail.
const mixed = analyze({}, { legacyRoot: true });
assert.equal(mixed.coherent, false, 'A response carrying both roots is the mixed identity hard stop.');
assert.equal(mixed.contracts['journal-zero-legacy-roots'], false);

// Mixed route assets: a legacy Studio style block alongside the candidate fails.
assert.equal(analyze({}, { legacyStudioStyle: true }).coherent, false, 'Legacy route assets must fail the no-mixed-assets contract.');

// Version binding: stale candidate HTML with the prior version must fail.
const staleVersion = analyze({}, { version: '3.2.48' });
assert.equal(staleVersion.coherent, false);
assert.equal(staleVersion.contracts['theme-version-binding'], false);
const missingBinding = analyzeJournalCanonicalCoherency({
	url: CANONICAL_JOURNAL_URL,
	finalUrl: CANONICAL_JOURNAL_URL,
	statusCode: 200,
	html: candidateHtml().replace(/ data-lunara-theme-version="3\.2\.49"/, ''),
	expectedVersion: '3.2.49',
});
assert.equal(missingBinding.coherent, false, 'HTML without the version attribute predates the deployed theme and must fail.');

// The expected version is mandatory — the gate cannot run open.
assert.equal(analyze({ expectedVersion: '' }).coherent, false, 'A blank expected version must fail closed.');
assert.equal(analyze({ expectedVersion: undefined }).coherent, false, 'A missing expected version must fail closed.');

// Request/response identity discipline.
assert.equal(analyze({ url: 'https://lunarafilm.com/journal/?sort=date_asc' }).coherent, false, 'A query-bearing request can never be accepted as canonical proof.');
assert.equal(analyze({ finalUrl: 'https://lunarafilm.com/journal/page/2/' }).coherent, false, 'A redirect off the canonical route must fail.');
assert.equal(analyze({ finalUrl: 'https://attacker.example/journal/' }).coherent, false, 'A redirect off origin must fail.');
assert.equal(analyze({ statusCode: 503 }).coherent, false, 'A non-200 response must fail.');

// Structural CSS must be complete and must precede <body>.
assert.equal(analyze({}, { vars: false }).coherent, false, 'Missing variables block fails the structural contract.');
assert.equal(analyze({}, { seed: false }).coherent, false, 'Missing critical seed fails the structural contract.');
assert.equal(analyze({}, { routeLink: false }).coherent, false, 'Missing route stylesheet link fails the structural contract.');
const lateStructural = analyze({}, { structuralInBody: true });
assert.equal(lateStructural.coherent, false, 'Structural CSS after <body> fails the before-body contract.');
assert.equal(lateStructural.contracts['journal-structural-css-before-body'], false);

// Card and lead census: exact counts, token-exact matching, nonblank titles.
assert.equal(analyze({}, { cards: 7 }).coherent, false, 'Seven cards must fail the exact eight-card contract.');
assert.equal(analyze({}, { cards: 9 }).coherent, false, 'Nine cards must fail the exact eight-card contract.');
assert.equal(analyze({}, { leads: 0 }).coherent, false, 'Zero leads must fail the one-lead contract.');
assert.equal(analyze({}, { leads: 2 }).coherent, false, 'Two leads must fail the one-lead contract.');
assert.equal(analyze({}, { blankTitles: 1 }).coherent, false, 'A blank card title must fail the nonblank contract.');
const censusDetail = analyze();
assert.equal(censusDetail.detail.cardCount, 8, 'Retention cards and -card-type/-card-cta/-card-footer substrings must never inflate the card census.');

// Unparseable input fails closed.
assert.equal(analyzeJournalCanonicalCoherency({ url: CANONICAL_JOURNAL_URL, finalUrl: CANONICAL_JOURNAL_URL, statusCode: 200, html: '', expectedVersion: '3.2.49' }).coherent, false, 'An empty response must fail closed.');
assert.equal(analyzeJournalCanonicalCoherency({ url: CANONICAL_JOURNAL_URL, finalUrl: CANONICAL_JOURNAL_URL, statusCode: 200, html: '<html><head></head>no body tag', expectedVersion: '3.2.49' }).coherent, false, 'A response with no <body> must fail closed.');
assert.equal(analyzeJournalCanonicalCoherency({ url: CANONICAL_JOURNAL_URL, finalUrl: CANONICAL_JOURNAL_URL, statusCode: 200, html: null, expectedVersion: '3.2.49' }).coherent, false, 'A null body must fail closed.');

process.stdout.write('Journal canonical cache-coherency sentinel runtime passed.\n');
