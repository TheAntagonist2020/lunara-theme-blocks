'use strict';

/**
 * Offline fail-closed coverage for the anonymous canonical Journal
 * cache-coherency sentinel. Fixtures mirror the markup shapes actually
 * observed in production: the Blocksy `<main id="main">` wrapper, the legacy
 * 3.2.43 Journal root without `id="primary"`, and the modern candidate root
 * with the version binding. No network is used.
 */

const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const {
	CANONICAL_JOURNAL_URL,
	EXPECTED_CARD_COUNT,
	EXPECTED_LEAD_COUNT,
	LIVE_COHERENT_EXIT,
	INCOHERENT_EXIT,
	USAGE_ERROR_EXIT,
	REPLAY_COHERENT_EXIT,
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
	version = '3.2.52',
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
	duplicateModernRoot = false,
	substringCardDecoys = false,
	preloadRel = 'preload',
	preloadAs = 'font',
	legacyRootDataIdSpoof = false,
} = {}) {
	const markerClass = marker ? ' is-label-font-tiempos' : '';
	const preloadTag = preload
		? '<link rel="' + preloadRel + '" href="https://lunarafilm.com/wp-content/uploads/lunara-fonts/v1/TiemposText-Bold.woff2" as="' + preloadAs + '" type="font/woff2" crossorigin />'
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
	if (substringCardDecoys) {
		// Class tokens that merely CONTAIN the card class must never count.
		cardBlocks += '<article class="pre-lunara-journal-archive-card"><h3 class="lunara-review-grid-title">Decoy</h3></article>'
			+ '<article class="lunara-journal-archive-cardx is-lead"><h3 class="lunara-review-grid-title">Decoy lead</h3></article>';
	}
	const modernMainTag = '<main id="primary" class="lunara-archive-page lunara-journal-archive-page' + markerClass + '" ' + VERSION_ATTRIBUTE + '="' + version + '">';
	const modernMain = modernRoot
		? modernMainTag
			+ '<section class="lunara-journal-archive-grid lunara-review-grid lunara-review-archive-uniform lunara-journal-archive-slot-grid">'
			+ cardBlocks
			+ '</section>' + retentionMarkup() + '</main>'
			+ (duplicateModernRoot ? modernMainTag + '</main>' : '')
		: '';
	const legacyMain = legacyRoot
		? '<main ' + (legacyRootDataIdSpoof ? 'data-id="primary" ' : '') + 'class="lunara-archive-page lunara-journal-archive-page"><section class="lunara-journal-archive-grid">' + cardBlocks + '</section></main>'
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
		expectedVersion: '3.2.52',
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
assert.notEqual('3.2.48', '3.2.52', 'A version sweep must never collapse the stale fixture onto the expected version.');
const staleVersion = analyze({}, { version: '3.2.48' });
assert.equal(staleVersion.coherent, false);
assert.equal(staleVersion.contracts['theme-version-binding'], false);
const missingBinding = analyzeJournalCanonicalCoherency({
	url: CANONICAL_JOURNAL_URL,
	finalUrl: CANONICAL_JOURNAL_URL,
	statusCode: 200,
	html: candidateHtml().replace(/ data-lunara-theme-version="3\.2\.52"/, ''),
	expectedVersion: '3.2.52',
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

// Authenticated variants and serialization tricks can never be canonical proof.
assert.equal(isCanonicalJournalUrl('https://user:pass@lunarafilm.com/journal/'), false, 'A credentials-bearing URL is an authenticated variant, never the anonymous proof.');
assert.equal(isCanonicalJournalUrl('https://admin@lunarafilm.com/journal/'), false, 'A username-bearing URL is an authenticated variant, never the anonymous proof.');
assert.equal(isCanonicalJournalUrl('https://lunarafilm.com/journal/?'), false, 'A bare-? URL serializes to a distinct request identity and must be refused.');
assert.equal(isCanonicalJournalUrl('https://lunarafilm.com/journal/#'), false, 'A bare-# URL must be refused.');
assert.equal(isCanonicalJournalUrl('https://lunarafilm.com/journal/?#'), false, 'A bare-?# URL must be refused.');
assert.equal(isCanonicalJournalUrl('https://lunarafilm.com/journal/#section'), false, 'A fragment-bearing URL must be refused.');
assert.equal(analyze({ finalUrl: 'https://user:pass@lunarafilm.com/journal/' }).coherent, false, 'An authenticated finalUrl must fail the response identity contract.');

// Root census discipline: duplicates and attribute spoofs are pinned.
const duplicated = analyze({}, { duplicateModernRoot: true });
assert.equal(duplicated.coherent, false, 'Two modern roots are ambiguous identity and must fail.');
assert.equal(duplicated.detail.modernRootCount, 2, 'The census must count both modern roots, not stop at the first.');
const spoofed = analyze({}, { modernRoot: false, legacyRoot: true, legacyRootDataIdSpoof: true });
assert.equal(spoofed.detail.legacyRootCount, 1, 'A data-id="primary" attribute must never satisfy the id="primary" modern-root probe.');
assert.equal(spoofed.detail.modernRootCount, 0, 'A data-id spoof must not be classified as the modern root.');
assert.equal(spoofed.coherent, false);

// Token-exact card census: substring class tokens never count.
const decoys = analyze({}, { substringCardDecoys: true });
assert.equal(decoys.coherent, true, 'Substring card-class decoys must not disturb the census: ' + decoys.failures.join('; '));
assert.equal(decoys.detail.cardCount, 8, 'Decoy articles whose class merely contains the card class must not be counted.');
assert.equal(decoys.detail.leadCount, 1, 'A decoy is-lead article must not inflate the lead census.');

// Preload binding is a real font preload, not any link mentioning the file.
assert.equal(analyze({}, { preloadRel: 'prefetch' }).coherent, false, 'rel=prefetch of the Bold file is not the required preload.');
assert.equal(analyze({}, { preloadAs: 'style' }).coherent, false, 'A non-font as= attribute is not the required font preload.');

// Studio-aware card expectation: legal overrides work, everything else fails closed.
const twelve = analyze({ expectedCardCount: 12 }, { cards: 12 });
assert.equal(twelve.coherent, true, 'A legal Studio items-per-page override must be honored: ' + twelve.failures.join('; '));
assert.equal(analyze({ expectedCardCount: 12 }).coherent, false, 'An override still demands the exact served count.');
assert.equal(analyze({ expectedCardCount: 30 }, { cards: 30 }).coherent, false, 'A count outside Studio legal bounds must fail closed.');
assert.equal(analyze({ expectedCardCount: 0 }).coherent, false, 'Zero expected cards must fail closed.');
assert.equal(analyze({ expectedCardCount: 8.5 }).coherent, false, 'A non-integer expectation must fail closed.');
assert.equal(analyze({ expectedCardCount: '8' }).coherent, false, 'A string expectation must fail closed.');

// Unparseable input fails closed.
assert.equal(analyzeJournalCanonicalCoherency({ url: CANONICAL_JOURNAL_URL, finalUrl: CANONICAL_JOURNAL_URL, statusCode: 200, html: '', expectedVersion: '3.2.52' }).coherent, false, 'An empty response must fail closed.');
assert.equal(analyzeJournalCanonicalCoherency({ url: CANONICAL_JOURNAL_URL, finalUrl: CANONICAL_JOURNAL_URL, statusCode: 200, html: '<html><head></head>no body tag', expectedVersion: '3.2.52' }).coherent, false, 'A response with no <body> must fail closed.');
assert.equal(analyzeJournalCanonicalCoherency({ url: CANONICAL_JOURNAL_URL, finalUrl: CANONICAL_JOURNAL_URL, statusCode: 200, html: null, expectedVersion: '3.2.52' }).coherent, false, 'A null body must fail closed.');

// Isolate the missing-body early return itself: a FULLY coherent candidate
// whose opening <body> tag is swapped for <div> carries every other passing
// signal, so only the missing-body guard can fail it. A mutation replacing
// that guard with if(false) would let this candidate pass.
const bodilessCoherent = analyzeJournalCanonicalCoherency({
	url: CANONICAL_JOURNAL_URL,
	finalUrl: CANONICAL_JOURNAL_URL,
	statusCode: 200,
	html: candidateHtml().replace('<body', '<div'),
	expectedVersion: '3.2.52',
	expectedLabelToken: APPROVED_DEFAULT_LABEL_TOKEN,
});
assert.equal(bodilessCoherent.coherent, false, 'An otherwise-coherent candidate with no <body> must fail closed.');
assert.equal(bodilessCoherent.failures.join('; ').includes('failing closed'), true, 'The missing-body guard must report its fail-closed reason.');

// CLI enforcement layer: exit-code wiring, replay provenance, and usage
// discipline are exercised end-to-end via child processes, offline.
const gatePath = path.join(__dirname, 'tools', 'lunara-journal-canonical-coherency-gate.js');
const cliDir = fs.mkdtempSync(path.join(os.tmpdir(), 'lunara-coherency-cli-'));
const coherentFixture = path.join(cliDir, 'coherent.html');
const legacyFixture = path.join(cliDir, 'legacy.html');
fs.writeFileSync(coherentFixture, candidateHtml());
fs.writeFileSync(legacyFixture, candidateHtml({ modernRoot: false, legacyRoot: true, vars: false, seed: false, routeLink: false }));

function runCli(cliArgs) {
	const result = spawnSync(process.execPath, [gatePath].concat(cliArgs), { encoding: 'utf8' });
	let parsed = null;
	try {
		parsed = JSON.parse(result.stdout);
	} catch (error) {
		parsed = null;
	}
	return { status: result.status, stdout: result.stdout, stderr: result.stderr, json: parsed };
}

assert.equal(REPLAY_COHERENT_EXIT !== LIVE_COHERENT_EXIT, true, 'Replay success must never share the live-proof exit code.');

const cliCoherentReplay = runCli(['--expected-version', '3.2.52', '--replay-html-file', coherentFixture, '--replay-status', '200', '--replay-final-url', CANONICAL_JOURNAL_URL]);
assert.equal(cliCoherentReplay.status, REPLAY_COHERENT_EXIT, 'A coherent replay must exit ' + REPLAY_COHERENT_EXIT + ' (diagnostic), got ' + cliCoherentReplay.status + ': ' + cliCoherentReplay.stderr);
assert.equal(cliCoherentReplay.json.mode, 'replay');
assert.equal(cliCoherentReplay.json.proof, false, 'A replay must never be labeled as deployment proof.');
assert.equal(cliCoherentReplay.json.coherent, true);

const cliLegacyReplay = runCli(['--expected-version', '3.2.52', '--replay-html-file', legacyFixture, '--replay-status', '200', '--replay-final-url', CANONICAL_JOURNAL_URL]);
assert.equal(cliLegacyReplay.status, INCOHERENT_EXIT, 'The legacy canary shape must exit ' + INCOHERENT_EXIT + ' from the CLI.');
assert.equal(cliLegacyReplay.json.detail.legacyRootCount, 1);
assert.equal(cliLegacyReplay.json.detail.modernRootCount, 0);

const cliAuthReplay = runCli(['--expected-version', '3.2.52', '--replay-html-file', coherentFixture, '--replay-status', '200', '--replay-final-url', 'https://admin@lunarafilm.com/journal/']);
assert.equal(cliAuthReplay.status, INCOHERENT_EXIT, 'An authenticated-variant final URL must fail the CLI even with coherent HTML.');

const cliQueryReplay = runCli(['--expected-version', '3.2.52', '--replay-html-file', coherentFixture, '--replay-status', '200', '--replay-final-url', 'https://lunarafilm.com/journal/?nocache=1']);
assert.equal(cliQueryReplay.status, INCOHERENT_EXIT, 'A cache-busted final URL must fail the CLI even with coherent HTML.');

const cliStatusReplay = runCli(['--expected-version', '3.2.52', '--replay-html-file', coherentFixture, '--replay-status', '503', '--replay-final-url', CANONICAL_JOURNAL_URL]);
assert.equal(cliStatusReplay.status, INCOHERENT_EXIT, 'A non-200 replay status must fail the CLI.');

const cliCards = runCli(['--expected-version', '3.2.52', '--expected-cards', '12', '--replay-html-file', coherentFixture, '--replay-status', '200', '--replay-final-url', CANONICAL_JOURNAL_URL]);
assert.equal(cliCards.status, INCOHERENT_EXIT, 'An --expected-cards override must reach the analyzer and fail an 8-card capture.');

assert.equal(runCli([]).status, USAGE_ERROR_EXIT, 'Missing --expected-version must be a usage error.');
assert.equal(runCli(['--expected-version', '3.2.52', '--bogus-flag', 'x']).status, USAGE_ERROR_EXIT, 'An unknown flag must be a usage error.');
assert.equal(runCli(['--expected-version', '3.2.52', '--replay-html-file', coherentFixture, '--replay-status', '200']).status, USAGE_ERROR_EXIT, 'Replay without an explicit --replay-final-url must be refused, never defaulted.');
assert.equal(runCli(['--expected-version', '3.2.52', '--replay-html-file', coherentFixture, '--replay-final-url', CANONICAL_JOURNAL_URL]).status, USAGE_ERROR_EXIT, 'Replay without an explicit --replay-status must be refused, never defaulted.');

fs.rmSync(cliDir, { recursive: true, force: true });

process.stdout.write('Journal canonical cache-coherency sentinel runtime passed.\n');
