'use strict';

/**
 * Offline fail-closed coverage for the anonymous canonical Oscars portal
 * cache-coherency sentinel. Fixtures mirror the markup page-oscars.php
 * actually renders: the `<main id="primary" class="site-main
 * lunara-oscars-portal">` root with its data-lunara-theme-version binding,
 * the six default-visible #oscars-* section anchors, the wp_head-7
 * structural seed, the route stylesheet link, and the provenance-gated
 * Studio vars block. No network is used; live exit-code 0 is reserved for
 * the explicit deployment-verification probe.
 */

const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const {
	CANONICAL_OSCARS_URL,
	LIVE_COHERENT_EXIT,
	INCOHERENT_EXIT,
	USAGE_ERROR_EXIT,
	REPLAY_COHERENT_EXIT,
	APPROVED_DEFAULT_LABEL_TOKEN,
	VERSION_ATTRIBUTE,
	STRUCTURAL_VARS_STYLE_ID,
	STRUCTURAL_SEED_STYLE_ID,
	ROUTE_STYLESHEET_LINK_ID,
	LEGACY_SHELL_STYLE_LINK_ID,
	REQUIRED_ANCHOR_IDS,
	isCanonicalOscarsUrl,
	analyzeOscarsCanonicalCoherency,
} = require('./tools/lunara-oscars-canonical-coherency-gate');

assert.equal(CANONICAL_OSCARS_URL, 'https://lunarafilm.com/oscars/', 'The sentinel must target the exact anonymous canonical Oscars portal URL.');
assert.equal(APPROVED_DEFAULT_LABEL_TOKEN, 'tiempos-text', 'The approved default label token is tiempos-text.');
assert.deepEqual(
	REQUIRED_ANCHOR_IDS,
	['oscars-doors', 'oscars-spotlights', 'oscars-titles', 'oscars-research', 'oscars-winners', 'oscars-deep-cuts'],
	'The anchor census must demand exactly the default-visible id-bearing sections — never the content-driven board, the default-hidden reviews, or the id-less rotating section.'
);

const FIXTURE_VERSION = '3.2.51';

function anchorSection(anchorId) {
	return '<section id="' + anchorId + '" class="lunara-home-section">' + anchorId + '</section>';
}

function candidateHtml({
	version = FIXTURE_VERSION,
	marker = true,
	preload = true,
	modernRoot = true,
	legacyRoot = false,
	seed = true,
	routeLink = true,
	vars = 'absent', // 'absent' | 'head' | 'body' — provenance-gated Studio geometry
	legacyShellCss = false,
	omitAnchor = null,
	duplicateAnchor = null,
	duplicateModernRoot = false,
	structuralInBody = false,
	preloadRel = 'preload',
	preloadAs = 'font',
	legacyRootDataIdSpoof = false,
	tokenDecoyRoots = false,
} = {}) {
	const markerClass = marker ? ' is-label-font-tiempos' : '';
	const preloadTag = preload
		? '<link rel="' + preloadRel + '" href="https://lunarafilm.com/wp-content/uploads/lunara-fonts/v1/TiemposText-Bold.woff2" as="' + preloadAs + '" type="font/woff2" crossorigin />'
		: '';
	const seedTag = seed ? '<style id="' + STRUCTURAL_SEED_STYLE_ID + '">#primary.lunara-oscars-portal{display:grid}</style>' : '';
	const routeLinkTag = routeLink
		? "<link rel='stylesheet' id='" + ROUTE_STYLESHEET_LINK_ID + "' href='https://lunarafilm.com/wp-content/themes/lunara-theme-blocks/assets/css/lunara-oscars-portal.css?ver=1786874737' type='text/css' media='all' />"
		: '';
	const varsTag = '<style id="' + STRUCTURAL_VARS_STYLE_ID + '">#primary.lunara-oscars-portal{--lunara-oscars-portal-section-gap:52px}</style>';
	const legacyShellTag = legacyShellCss
		? "<link rel='stylesheet' id='" + LEGACY_SHELL_STYLE_LINK_ID + "' href='https://lunarafilm.com/wp-content/themes/lunara-theme-blocks/assets/css/oscars.css' type='text/css' media='all' />"
		: '';
	const structural = seedTag + routeLinkTag;

	let sections = '';
	for (const anchorId of REQUIRED_ANCHOR_IDS) {
		if (anchorId === omitAnchor) {
			continue;
		}
		sections += anchorSection(anchorId);
		if (anchorId === duplicateAnchor) {
			sections += anchorSection(anchorId);
		}
	}
	// Content-driven and default-hidden chambers legitimately absent: no
	// #oscars-board (off-season), no #oscars-reviews (ships hidden), and the
	// rotating-winners section carries no id at all.
	sections += '<section class="lunara-home-section lunara-oscars-rotating-winners-section" aria-label="Oscars Deep Dive">rotating</section>';

	const modernMainTag = '<main id="primary" class="site-main lunara-oscars-portal' + markerClass + '" ' + VERSION_ATTRIBUTE + '="' + version + '">';
	const modernMain = modernRoot
		? modernMainTag + sections + '</main>' + (duplicateModernRoot ? modernMainTag + '</main>' : '')
		: '';
	const legacyMain = legacyRoot
		? '<main ' + (legacyRootDataIdSpoof ? 'data-id="primary" ' : '') + 'class="site-main lunara-oscars-portal">' + sections + '</main>'
		: '';
	const decoys = tokenDecoyRoots
		? '<main id="primary-decoy" class="pre-lunara-oscars-portal">decoy</main><main class="lunara-oscars-portalx site-main">decoy</main>'
		: '';

	return '<!doctype html><html><head><meta charset="utf-8">'
		+ '<style id="jetpack-boost-critical-css">.lunara-oscars-portal{opacity:1}</style>'
		+ preloadTag
		+ (structuralInBody ? '' : structural)
		+ (vars === 'head' ? varsTag : '')
		+ legacyShellTag
		+ '</head><body class="page lunara-oscars-portal-page lunara-oscars-family">'
		+ (structuralInBody ? structural : '')
		+ (vars === 'body' ? varsTag : '')
		+ '<main id="main" class="site-main hfeed" tabindex="-1">'
		+ legacyMain + modernMain + decoys
		+ '</main></body></html>';
}

function analyze(overrides = {}, htmlOptions = {}) {
	return analyzeOscarsCanonicalCoherency({
		url: CANONICAL_OSCARS_URL,
		finalUrl: CANONICAL_OSCARS_URL,
		statusCode: 200,
		html: candidateHtml(htmlOptions),
		expectedVersion: FIXTURE_VERSION,
		expectedLabelToken: APPROVED_DEFAULT_LABEL_TOKEN,
		...overrides,
	});
}

// Canonical URL discipline: only the exact anonymous no-query route is proof.
assert.equal(isCanonicalOscarsUrl('https://lunarafilm.com/oscars/'), true);
assert.equal(isCanonicalOscarsUrl('https://lunarafilm.com/oscars/?view=table'), false, 'Query-bearing variants are diagnostic only.');
assert.equal(isCanonicalOscarsUrl('https://lunarafilm.com/oscars/?nocache=1'), false, 'Cache-busted URLs can never satisfy the canonical proof.');
assert.equal(isCanonicalOscarsUrl('https://lunarafilm.com/oscars/ceremony/98/'), false, 'Ledger routes are a different family, never portal proof.');
assert.equal(isCanonicalOscarsUrl('https://www.lunarafilm.com/oscars/'), false, 'Only the exact canonical host is proof.');
assert.equal(isCanonicalOscarsUrl('http://lunarafilm.com/oscars/'), false);
assert.equal(isCanonicalOscarsUrl('https://lunarafilm.com/journal/'), false, 'The Journal sentinel owns its own canonical URL.');

// Authenticated variants and serialization tricks can never be canonical proof.
assert.equal(isCanonicalOscarsUrl('https://user:pass@lunarafilm.com/oscars/'), false, 'A credentials-bearing URL is an authenticated variant, never the anonymous proof.');
assert.equal(isCanonicalOscarsUrl('https://admin@lunarafilm.com/oscars/'), false, 'A username-bearing URL is an authenticated variant, never the anonymous proof.');
assert.equal(isCanonicalOscarsUrl('https://lunarafilm.com/oscars/?'), false, 'A bare-? URL serializes to a distinct request identity and must be refused.');
assert.equal(isCanonicalOscarsUrl('https://lunarafilm.com/oscars/#'), false, 'A bare-# URL must be refused.');
assert.equal(isCanonicalOscarsUrl('https://lunarafilm.com/oscars/?#'), false, 'A bare-?# URL must be refused.');
assert.equal(isCanonicalOscarsUrl('https://lunarafilm.com/oscars/#oscars-research'), false, 'A fragment-bearing URL must be refused.');

// The coherent candidate response passes every contract.
const coherent = analyze();
assert.equal(coherent.coherent, true, 'A coherent candidate response must pass: ' + coherent.failures.join('; '));
assert.equal(coherent.detail.modernRootCount, 1);
assert.equal(coherent.detail.legacyRootCount, 0);
assert.deepEqual(coherent.detail.missingAnchors, []);
assert.equal(coherent.detail.varsPresent, false, 'An unsaved-Studio portal legitimately carries no vars block.');

// Saved-Studio provenance: a vars block in the head passes; after <body> fails.
const varsInHead = analyze({}, { vars: 'head' });
assert.equal(varsInHead.coherent, true, 'A saved-Studio portal with the vars block in <head> must pass: ' + varsInHead.failures.join('; '));
assert.equal(varsInHead.detail.varsPresent, true);
const varsInBody = analyze({}, { vars: 'body' });
assert.equal(varsInBody.coherent, false, 'A vars block after <body> must fail the structural before-body contract.');
assert.equal(varsInBody.contracts['portal-structural-css-before-body'], false);

// The valid custom Lora bypass passes only without Tiempos marker and preload.
const lora = analyze({ expectedLabelToken: 'lora' }, { marker: false, preload: false });
assert.equal(lora.coherent, true, 'A valid custom Lora response must pass: ' + lora.failures.join('; '));
assert.equal(analyze({ expectedLabelToken: 'lora' }, { marker: true, preload: false }).coherent, false, 'The Tiempos marker must never leak onto a custom-token route.');
assert.equal(analyze({ expectedLabelToken: 'lora' }, { marker: false, preload: true }).coherent, false, 'The Tiempos preload must never leak onto a custom-token route.');

// Default token requires both marker and preload — a half-applied identity fails.
assert.equal(analyze({}, { marker: false, preload: true }).coherent, false, 'Default token without the marker is incoherent.');
assert.equal(analyze({}, { marker: true, preload: false }).coherent, false, 'Default token without the Bold preload is incoherent.');

// Preload binding is a real font preload, not any link mentioning the file.
assert.equal(analyze({}, { preloadRel: 'prefetch' }).coherent, false, 'rel=prefetch of the Bold file is not the required preload.');
assert.equal(analyze({}, { preloadAs: 'style' }).coherent, false, 'A non-font as= attribute is not the required font preload.');

// Stale version: candidate HTML carrying the prior identity must fail.
// The stale fixture uses the permanently rolled-back 3.2.48 identity (the
// Journal runtime's convention) so mechanical version sweeps can never
// collapse it onto the expected version.
assert.notEqual('3.2.48', FIXTURE_VERSION, 'A version sweep must never collapse the stale fixture onto the expected version.');
const staleVersion = analyze({}, { version: '3.2.48' });
assert.equal(staleVersion.coherent, false);
assert.equal(staleVersion.contracts['theme-version-binding'], false);
const missingBinding = analyzeOscarsCanonicalCoherency({
	url: CANONICAL_OSCARS_URL,
	finalUrl: CANONICAL_OSCARS_URL,
	statusCode: 200,
	html: candidateHtml().replace(' ' + VERSION_ATTRIBUTE + '="' + FIXTURE_VERSION + '"', ''),
	expectedVersion: FIXTURE_VERSION,
});
assert.equal(missingBinding.coherent, false, 'HTML without the version attribute predates the deployed theme and must fail.');

// The expected version is mandatory — the gate cannot run open.
assert.equal(analyze({ expectedVersion: '' }).coherent, false, 'A blank expected version must fail closed.');
assert.equal(analyze({ expectedVersion: undefined }).coherent, false, 'A missing expected version must fail closed.');

// Missing root: the stale-cache shape that motivated the sentinel.
const missingRoot = analyze({}, { modernRoot: false, legacyRoot: true, seed: false, routeLink: false });
assert.equal(missingRoot.coherent, false);
assert.equal(missingRoot.detail.modernRootCount, 0);
assert.equal(missingRoot.detail.legacyRootCount, 1);
assert.equal(missingRoot.contracts['portal-modern-root-identity'], false);
assert.equal(missingRoot.contracts['theme-version-binding'], false, 'Legacy cached HTML must fail the version binding, not slip past it.');

// Mixed identity: both roots present at once must fail.
const mixed = analyze({}, { legacyRoot: true });
assert.equal(mixed.coherent, false, 'A response carrying both roots is the mixed identity hard stop.');
assert.equal(mixed.contracts['portal-zero-legacy-roots'], false);

// Duplicate modern roots are ambiguous identity.
const duplicated = analyze({}, { duplicateModernRoot: true });
assert.equal(duplicated.coherent, false, 'Two modern roots are ambiguous identity and must fail.');
assert.equal(duplicated.detail.modernRootCount, 2, 'The census must count both modern roots, not stop at the first.');

// Attribute spoofs and token decoys never satisfy the root probes.
const spoofed = analyze({}, { modernRoot: false, legacyRoot: true, legacyRootDataIdSpoof: true });
assert.equal(spoofed.detail.legacyRootCount, 1, 'A data-id="primary" attribute must never satisfy the id="primary" modern-root probe.');
assert.equal(spoofed.detail.modernRootCount, 0);
assert.equal(spoofed.coherent, false);
const decoys = analyze({}, { tokenDecoyRoots: true });
assert.equal(decoys.coherent, true, 'Class tokens merely containing the portal root class must not disturb the census: ' + decoys.failures.join('; '));
assert.equal(decoys.detail.portalRootCount, 1, 'Decoy mains whose class merely contains the root class must not be counted.');

// Legacy route assets: the unsplit fallback's cross-route oscars.css fails.
assert.equal(analyze({}, { legacyShellCss: true }).coherent, false, 'The legacy cross-route stylesheet must fail the no-legacy-assets contract.');

// Structural CSS must be complete and must precede <body>.
assert.equal(analyze({}, { seed: false }).coherent, false, 'Missing structural seed fails the structural contract.');
assert.equal(analyze({}, { routeLink: false }).coherent, false, 'Missing route stylesheet link fails the structural contract.');
const lateStructural = analyze({}, { structuralInBody: true });
assert.equal(lateStructural.coherent, false, 'Structural CSS after <body> fails the before-body contract.');
assert.equal(lateStructural.contracts['portal-structural-css-before-body'], false);

// Anchor census: every default-visible section id exactly once.
for (const anchorId of REQUIRED_ANCHOR_IDS) {
	const missing = analyze({}, { omitAnchor: anchorId });
	assert.equal(missing.coherent, false, 'A portal without #' + anchorId + ' must fail the anchor census.');
	assert.deepEqual(missing.detail.missingAnchors, [anchorId]);
}
const doubled = analyze({}, { duplicateAnchor: 'oscars-research' });
assert.equal(doubled.coherent, false, 'A duplicated section anchor must fail the census.');
assert.deepEqual(doubled.detail.duplicateAnchors, ['oscars-research']);

// Request/response identity discipline.
assert.equal(analyze({ url: 'https://lunarafilm.com/oscars/?view=table' }).coherent, false, 'A query-bearing request can never be accepted as canonical proof.');
assert.equal(analyze({ finalUrl: 'https://lunarafilm.com/oscars/ceremony/98/' }).coherent, false, 'A redirect onto a ledger route must fail.');
assert.equal(analyze({ finalUrl: 'https://attacker.example/oscars/' }).coherent, false, 'A redirect off origin must fail.');
assert.equal(analyze({ finalUrl: 'https://user:pass@lunarafilm.com/oscars/' }).coherent, false, 'An authenticated finalUrl must fail the response identity contract.');
assert.equal(analyze({ statusCode: 503 }).coherent, false, 'A non-200 response must fail.');

// Unparseable input fails closed.
assert.equal(analyzeOscarsCanonicalCoherency({ url: CANONICAL_OSCARS_URL, finalUrl: CANONICAL_OSCARS_URL, statusCode: 200, html: '', expectedVersion: FIXTURE_VERSION }).coherent, false, 'An empty response must fail closed.');
assert.equal(analyzeOscarsCanonicalCoherency({ url: CANONICAL_OSCARS_URL, finalUrl: CANONICAL_OSCARS_URL, statusCode: 200, html: '<html><head></head>no body tag', expectedVersion: FIXTURE_VERSION }).coherent, false, 'A response with no <body> must fail closed.');
assert.equal(analyzeOscarsCanonicalCoherency({ url: CANONICAL_OSCARS_URL, finalUrl: CANONICAL_OSCARS_URL, statusCode: 200, html: null, expectedVersion: FIXTURE_VERSION }).coherent, false, 'A null body must fail closed.');

// Isolate the missing-body early return itself: a FULLY coherent candidate
// whose opening <body> tag is swapped for <div> carries every other passing
// signal, so only the missing-body guard can fail it. A mutation replacing
// that guard with if(false) would let this candidate pass.
const bodilessCoherent = analyzeOscarsCanonicalCoherency({
	url: CANONICAL_OSCARS_URL,
	finalUrl: CANONICAL_OSCARS_URL,
	statusCode: 200,
	html: candidateHtml().replace('<body', '<div'),
	expectedVersion: FIXTURE_VERSION,
	expectedLabelToken: APPROVED_DEFAULT_LABEL_TOKEN,
});
assert.equal(bodilessCoherent.coherent, false, 'An otherwise-coherent candidate with no <body> must fail closed.');
assert.equal(bodilessCoherent.failures.join('; ').includes('failing closed'), true, 'The missing-body guard must report its fail-closed reason.');

// CLI enforcement layer: exit-code wiring, replay provenance, and usage
// discipline are exercised end-to-end via child processes, offline. Exit 0
// (live coherent) requires a live network probe by construction and is
// asserted here only as the reserved, distinct code.
const gatePath = path.join(__dirname, 'tools', 'lunara-oscars-canonical-coherency-gate.js');
const cliDir = fs.mkdtempSync(path.join(os.tmpdir(), 'lunara-oscars-coherency-cli-'));
const coherentFixture = path.join(cliDir, 'coherent.html');
const legacyFixture = path.join(cliDir, 'legacy.html');
fs.writeFileSync(coherentFixture, candidateHtml());
fs.writeFileSync(legacyFixture, candidateHtml({ modernRoot: false, legacyRoot: true, seed: false, routeLink: false }));

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

assert.equal(LIVE_COHERENT_EXIT, 0, 'Exit 0 is reserved for the live coherent deployment proof.');
assert.equal(REPLAY_COHERENT_EXIT !== LIVE_COHERENT_EXIT, true, 'Replay success must never share the live-proof exit code.');

const cliCoherentReplay = runCli(['--expected-version', FIXTURE_VERSION, '--replay-html-file', coherentFixture, '--replay-status', '200', '--replay-final-url', CANONICAL_OSCARS_URL]);
assert.equal(cliCoherentReplay.status, REPLAY_COHERENT_EXIT, 'A coherent replay must exit ' + REPLAY_COHERENT_EXIT + ' (diagnostic), got ' + cliCoherentReplay.status + ': ' + cliCoherentReplay.stderr);
assert.equal(cliCoherentReplay.json.mode, 'replay');
assert.equal(cliCoherentReplay.json.proof, false, 'A replay must never be labeled as deployment proof.');
assert.equal(cliCoherentReplay.json.coherent, true);

const cliLegacyReplay = runCli(['--expected-version', FIXTURE_VERSION, '--replay-html-file', legacyFixture, '--replay-status', '200', '--replay-final-url', CANONICAL_OSCARS_URL]);
assert.equal(cliLegacyReplay.status, INCOHERENT_EXIT, 'The legacy stale-cache shape must exit ' + INCOHERENT_EXIT + ' from the CLI.');
assert.equal(cliLegacyReplay.json.detail.legacyRootCount, 1);
assert.equal(cliLegacyReplay.json.detail.modernRootCount, 0);

const cliAuthReplay = runCli(['--expected-version', FIXTURE_VERSION, '--replay-html-file', coherentFixture, '--replay-status', '200', '--replay-final-url', 'https://admin@lunarafilm.com/oscars/']);
assert.equal(cliAuthReplay.status, INCOHERENT_EXIT, 'An authenticated-variant final URL must fail the CLI even with coherent HTML.');

const cliQueryReplay = runCli(['--expected-version', FIXTURE_VERSION, '--replay-html-file', coherentFixture, '--replay-status', '200', '--replay-final-url', 'https://lunarafilm.com/oscars/?nocache=1']);
assert.equal(cliQueryReplay.status, INCOHERENT_EXIT, 'A cache-busted final URL must fail the CLI even with coherent HTML.');

const cliStatusReplay = runCli(['--expected-version', FIXTURE_VERSION, '--replay-html-file', coherentFixture, '--replay-status', '503', '--replay-final-url', CANONICAL_OSCARS_URL]);
assert.equal(cliStatusReplay.status, INCOHERENT_EXIT, 'A non-200 replay status must fail the CLI.');

const cliCustomToken = runCli(['--expected-version', FIXTURE_VERSION, '--expected-label-token', 'lora', '--replay-html-file', coherentFixture, '--replay-status', '200', '--replay-final-url', CANONICAL_OSCARS_URL]);
assert.equal(cliCustomToken.status, INCOHERENT_EXIT, 'An --expected-label-token override must reach the analyzer and fail a default-token capture.');

assert.equal(runCli([]).status, USAGE_ERROR_EXIT, 'Missing --expected-version must be a usage error.');
assert.equal(runCli(['--expected-version', FIXTURE_VERSION, '--bogus-flag', 'x']).status, USAGE_ERROR_EXIT, 'An unknown flag must be a usage error.');
assert.equal(runCli(['--expected-version', FIXTURE_VERSION, '--replay-html-file', coherentFixture, '--replay-status', '200']).status, USAGE_ERROR_EXIT, 'Replay without an explicit --replay-final-url must be refused, never defaulted.');
assert.equal(runCli(['--expected-version', FIXTURE_VERSION, '--replay-html-file', coherentFixture, '--replay-final-url', CANONICAL_OSCARS_URL]).status, USAGE_ERROR_EXIT, 'Replay without an explicit --replay-status must be refused, never defaulted.');

fs.rmSync(cliDir, { recursive: true, force: true });

process.stdout.write('Oscars canonical cache-coherency sentinel runtime passed.\n');
