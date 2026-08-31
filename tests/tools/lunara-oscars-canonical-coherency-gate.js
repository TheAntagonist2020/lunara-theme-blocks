'use strict';

/**
 * Anonymous canonical Oscars portal cache-coherency sentinel.
 *
 * Clone of the Journal sentinel (lunara-journal-canonical-coherency-gate.js)
 * for the portal route family: the theme 3.2.48 Journal rollback proved that
 * the exact anonymous no-query canonical response can keep serving legacy
 * cached markup while every other cache key advances. This gate captures the
 * one URL that can fail that way for the Oscars family —
 * https://lunarafilm.com/oscars/ without query parameters and without
 * cache-busting — and fails closed when the served HTML identity differs
 * from the deployed theme.
 *
 * Query-bearing root variants, authenticated requests, and cache-busted URLs
 * are diagnostic cohorts only. They cannot satisfy this proof, and the gate
 * refuses to evaluate them as evidence.
 *
 * The live probe intentionally sends no Cache-Control or Pragma request
 * headers: a cache-bypassing request would mask exactly the stale-cache
 * identity split this sentinel exists to detect.
 *
 * Portal-specific contract notes (grounded in page-oscars.php, not invented):
 * - The modern root is `<main id="primary" class="site-main
 *   lunara-oscars-portal ..." data-lunara-theme-version="...">`.
 * - The anchor census demands only the sections that are VISIBLE BY DEFAULT
 *   and carry an id: oscars-doors, oscars-spotlights, oscars-titles,
 *   oscars-research, oscars-winners, oscars-deep-cuts. The board
 *   (#oscars-board) is content-driven (an empty pick season renders
 *   nothing), #oscars-reviews ships hidden by default, and the
 *   rotating-winners section carries no id — none of those can be demanded.
 * - Structural-CSS-before-body requires only the structural seed
 *   (#lunara-oscars-portal-critical-css) and the route stylesheet link
 *   (#lunara-oscars-portal-css). The Studio variables block
 *   (#lunara-oscars-portal-vars) is PROVENANCE-GATED — it emits only after
 *   an explicit Portal Studio save — so its absence is legal; when present
 *   it must still live in the <head>.
 * - No card/pick counts are asserted: every portal chamber is data-driven.
 */

const fs = require('node:fs');

const CANONICAL_OSCARS_URL = 'https://lunarafilm.com/oscars/';
const APPROVED_DEFAULT_LABEL_TOKEN = 'tiempos-text';
const PORTAL_ROOT_CLASS = 'lunara-oscars-portal';
const TIEMPOS_MARKER_CLASS = 'is-label-font-tiempos';
const TIEMPOS_BOLD_PRELOAD_FILE = 'TiemposText-Bold.woff2';
const MODERN_ROOT_ID = 'primary';
const VERSION_ATTRIBUTE = 'data-lunara-theme-version';
const STRUCTURAL_VARS_STYLE_ID = 'lunara-oscars-portal-vars';
const STRUCTURAL_SEED_STYLE_ID = 'lunara-oscars-portal-critical-css';
const ROUTE_STYLESHEET_LINK_ID = 'lunara-oscars-portal-css';
const LEGACY_SHELL_STYLE_LINK_ID = 'lunara-oscars-shell-css';
const REQUIRED_ANCHOR_IDS = [
	'oscars-doors',
	'oscars-spotlights',
	'oscars-titles',
	'oscars-research',
	'oscars-deep-cuts',
];

// Sections whose presence is bound to ceremony data rather than to a visibility
// dial. The Winners grid renders only when the latest ceremony actually has
// recorded winners, so a nominees-announced-but-not-yet-awarded ceremony
// legitimately produces no section. Demanding it unconditionally would fail a
// correctly rendered page; these ids are still held to the no-duplicate rule,
// and navigator-link integrity below is what proves the page stays coherent.
const DATA_CONDITIONAL_ANCHOR_IDS = [
	'oscars-winners',
];

// Every in-page navigator target on the portal, whatever its visibility rule.
const IN_PAGE_ANCHOR_HREF_PATTERN = /href\s*=\s*("#(oscars-[a-z0-9-]+)"|'#(oscars-[a-z0-9-]+)')/gi;

function attributeValue(tag, name) {
	// Lookbehind instead of \b: a word boundary would let `data-id` satisfy a
	// probe for `id` and misclassify a root.
	const match = String(tag).match(
		new RegExp('(?<![\\w-])' + name + '\\s*=\\s*("([^"]*)"|\'([^\']*)\')', 'i')
	);
	if (!match) {
		return null;
	}
	return match[2] !== undefined ? match[2] : match[3];
}

function classTokens(tag) {
	const value = attributeValue(tag, 'class');
	if (value === null) {
		return [];
	}
	return value.split(/\s+/).filter(Boolean);
}

function elementIdPattern(id) {
	return new RegExp('\\bid\\s*=\\s*["\']' + id + '["\']', 'i');
}

function countElementId(html, id) {
	const matches = String(html).match(new RegExp('\\bid\\s*=\\s*["\']' + id + '["\']', 'gi'));
	return matches ? matches.length : 0;
}

/**
 * The requested URL must be the exact anonymous canonical route. Anything
 * else — a query string, a fragment, another origin, another path — is
 * refused as proof rather than evaluated.
 */
function isCanonicalOscarsUrl(value) {
	let parsed;
	try {
		parsed = new URL(String(value));
	} catch (error) {
		return false;
	}
	return parsed.protocol === 'https:'
		&& parsed.username === ''
		&& parsed.password === ''
		&& parsed.hostname.toLowerCase() === 'lunarafilm.com'
		&& parsed.port === ''
		&& parsed.pathname === '/oscars/'
		&& parsed.search === ''
		&& parsed.hash === ''
		// Exact-serialization backstop: a bare '?' or '#' parses to empty
		// search/hash yet still serializes to a distinct request identity, and
		// userinfo is an authenticated variant — none of them are the
		// anonymous canonical proof.
		&& parsed.href === CANONICAL_OSCARS_URL;
}

/**
 * Analyze one captured response from the anonymous canonical `/oscars/`
 * route. Every contract defaults to failure: missing markup, ambiguous
 * markup, or unparseable input all fail closed.
 */
function analyzeOscarsCanonicalCoherency({
	url,
	finalUrl = url,
	statusCode,
	html,
	expectedVersion,
	expectedLabelToken = APPROVED_DEFAULT_LABEL_TOKEN,
}) {
	const contracts = {
		'canonical-request-identity': false,
		'canonical-response-identity': false,
		'theme-version-binding': false,
		'portal-modern-root-identity': false,
		'portal-zero-legacy-roots': false,
		'portal-structural-css-before-body': false,
		'portal-no-legacy-route-assets': false,
		'portal-anchor-census': false,
		'portal-navigator-link-integrity': false,
		'tiempos-label-ownership': false,
	};
	const failures = [];
	const detail = {
		modernRootCount: 0,
		legacyRootCount: 0,
		portalRootCount: 0,
		rootVersion: null,
		expectedVersion: typeof expectedVersion === 'string' ? expectedVersion : null,
		expectedLabelToken,
		anchorCounts: {},
		missingAnchors: [],
		duplicateAnchors: [],
		danglingAnchorLinks: [],
		varsPresent: false,
		varsInHead: false,
		tiemposMarker: false,
		tiemposBoldPreload: false,
	};

	const fail = (contract, message) => {
		failures.push(contract + ': ' + message);
	};

	contracts['canonical-request-identity'] = isCanonicalOscarsUrl(url);
	if (!contracts['canonical-request-identity']) {
		fail('canonical-request-identity', 'the gate only accepts the exact anonymous no-query ' + CANONICAL_OSCARS_URL + ' URL; got ' + String(url));
	}

	contracts['canonical-response-identity'] = statusCode === 200 && isCanonicalOscarsUrl(finalUrl);
	if (statusCode !== 200) {
		fail('canonical-response-identity', 'expected HTTP 200, got ' + String(statusCode));
	} else if (!isCanonicalOscarsUrl(finalUrl)) {
		fail('canonical-response-identity', 'the response settled off the canonical route: ' + String(finalUrl));
	}

	const document = typeof html === 'string' ? html : '';
	const bodyIndex = document.search(/<body\b/i);
	if (document === '' || bodyIndex === -1) {
		fail('portal-structural-css-before-body', 'response HTML is empty or has no <body>; failing closed');
		return finalizeVerdict(contracts, failures, detail);
	}
	const headHtml = document.slice(0, bodyIndex);

	if (typeof expectedVersion !== 'string' || expectedVersion.trim() === '') {
		fail('theme-version-binding', 'an expected deployed theme version is required; failing closed');
		return finalizeVerdict(contracts, failures, detail);
	}

	// 3.2.56 moved the canonical route root from <main id="primary"> to a
	// <div id="primary">. Scanning only <main> tags then found zero roots on a
	// perfectly healthy page, reported INCOHERENT, and told an operator to roll
	// back a good release. The root is identified by its id and route class —
	// that is the contract; the element type never was.
	//
	// Widened, not lowered: exactly one root, the version binding, the tiempos
	// marker, and zero legacy roots are all still required. A legacy root is
	// still "carries the route class, lacks the modern id", which is precisely
	// the mixed identity that forced the 3.2.48 rollback.
	const candidateTags = document.match(/<[a-zA-Z][\w-]*\b[^>]*>/g) || [];
	let modernRoot = null;
	for (const tag of candidateTags) {
		const tokens = classTokens(tag);
		if (!tokens.includes(PORTAL_ROOT_CLASS)) {
			continue;
		}
		detail.portalRootCount += 1;
		if (attributeValue(tag, 'id') === MODERN_ROOT_ID) {
			detail.modernRootCount += 1;
			modernRoot = tag;
		} else {
			detail.legacyRootCount += 1;
		}
	}

	contracts['portal-modern-root-identity'] = detail.modernRootCount === 1;
	if (detail.modernRootCount !== 1) {
		fail('portal-modern-root-identity', 'expected exactly one modern #' + MODERN_ROOT_ID + ' portal root, found ' + detail.modernRootCount);
	}
	contracts['portal-zero-legacy-roots'] = detail.legacyRootCount === 0 && detail.portalRootCount === detail.modernRootCount;
	if (detail.legacyRootCount !== 0) {
		fail('portal-zero-legacy-roots', 'found ' + detail.legacyRootCount + ' legacy portal root(s) — the mixed identity the Journal 3.2.48 rollback proved possible');
	}

	if (modernRoot !== null && detail.modernRootCount === 1) {
		detail.rootVersion = attributeValue(modernRoot, VERSION_ATTRIBUTE);
		contracts['theme-version-binding'] = detail.rootVersion === expectedVersion;
		if (detail.rootVersion === null) {
			fail('theme-version-binding', 'the modern root carries no ' + VERSION_ATTRIBUTE + ' attribute; the served HTML predates the deployed theme');
		} else if (detail.rootVersion !== expectedVersion) {
			fail('theme-version-binding', 'served HTML identity ' + detail.rootVersion + ' does not match deployed theme ' + expectedVersion);
		}
	} else {
		fail('theme-version-binding', 'no unambiguous modern root to read the version binding from');
	}

	// Structural CSS before <body>: the seed and the route stylesheet link are
	// required; the Studio vars block is provenance-gated (emitted only after
	// an explicit Portal Studio save), so it is optional — but if the page
	// carries it at all, it must be in the <head>.
	const hasSeed = elementIdPattern(STRUCTURAL_SEED_STYLE_ID).test(headHtml);
	const hasRouteLink = elementIdPattern(ROUTE_STYLESHEET_LINK_ID).test(headHtml);
	detail.varsPresent = elementIdPattern(STRUCTURAL_VARS_STYLE_ID).test(document);
	detail.varsInHead = elementIdPattern(STRUCTURAL_VARS_STYLE_ID).test(headHtml);
	contracts['portal-structural-css-before-body'] = hasSeed && hasRouteLink && (!detail.varsPresent || detail.varsInHead);
	if (!hasSeed) {
		fail('portal-structural-css-before-body', 'missing #' + STRUCTURAL_SEED_STYLE_ID + ' in <head>');
	}
	if (!hasRouteLink) {
		fail('portal-structural-css-before-body', 'missing #' + ROUTE_STYLESHEET_LINK_ID + ' route stylesheet link in <head>');
	}
	if (detail.varsPresent && !detail.varsInHead) {
		fail('portal-structural-css-before-body', '#' + STRUCTURAL_VARS_STYLE_ID + ' present but after <body>; saved Studio geometry must stamp in the <head>');
	}

	contracts['portal-no-legacy-route-assets'] = !elementIdPattern(LEGACY_SHELL_STYLE_LINK_ID).test(document);
	if (!contracts['portal-no-legacy-route-assets']) {
		fail('portal-no-legacy-route-assets', 'legacy #' + LEGACY_SHELL_STYLE_LINK_ID + ' cross-route stylesheet present — the unsplit fallback bundle is serving the portal');
	}

	// Anchor census: each default-visible section id appears exactly once, and
	// each data-conditional section id appears at most once.
	let anchorsCoherent = true;
	for (const anchorId of REQUIRED_ANCHOR_IDS) {
		const count = countElementId(document, anchorId);
		detail.anchorCounts[anchorId] = count;
		if (count === 0) {
			detail.missingAnchors.push(anchorId);
			anchorsCoherent = false;
		} else if (count > 1) {
			detail.duplicateAnchors.push(anchorId);
			anchorsCoherent = false;
		}
	}
	for (const anchorId of DATA_CONDITIONAL_ANCHOR_IDS) {
		const count = countElementId(document, anchorId);
		detail.anchorCounts[anchorId] = count;
		if (count > 1) {
			detail.duplicateAnchors.push(anchorId);
			anchorsCoherent = false;
		}
	}
	contracts['portal-anchor-census'] = anchorsCoherent;
	if (detail.missingAnchors.length > 0) {
		fail('portal-anchor-census', 'missing default-visible section anchor(s): ' + detail.missingAnchors.join(', '));
	}
	if (detail.duplicateAnchors.length > 0) {
		fail('portal-anchor-census', 'duplicated section anchor(s): ' + detail.duplicateAnchors.join(', '));
	}

	// Navigator link integrity: every in-page #oscars-* target the page links to
	// must resolve to a section that actually exists. A conditional section that
	// declines to render has to take its navigator link with it — the dead
	// "Winners" link that survived a winner-less ceremony is exactly this class.
	const linkedAnchorIds = new Set();
	IN_PAGE_ANCHOR_HREF_PATTERN.lastIndex = 0;
	let anchorHrefMatch = IN_PAGE_ANCHOR_HREF_PATTERN.exec(document);
	while (anchorHrefMatch !== null) {
		linkedAnchorIds.add(anchorHrefMatch[2] || anchorHrefMatch[3]);
		anchorHrefMatch = IN_PAGE_ANCHOR_HREF_PATTERN.exec(document);
	}
	for (const anchorId of Array.from(linkedAnchorIds).sort()) {
		if (countElementId(document, anchorId) === 0) {
			detail.danglingAnchorLinks.push(anchorId);
		}
	}
	contracts['portal-navigator-link-integrity'] = detail.danglingAnchorLinks.length === 0;
	if (!contracts['portal-navigator-link-integrity']) {
		fail(
			'portal-navigator-link-integrity',
			'in-page link(s) point at section(s) that do not exist on the page: '
				+ detail.danglingAnchorLinks.map((id) => '#' + id).join(', ')
		);
	}

	detail.tiemposMarker = modernRoot !== null && classTokens(modernRoot).includes(TIEMPOS_MARKER_CLASS);
	const headLinks = headHtml.match(/<link\b[^>]*>/gi) || [];
	detail.tiemposBoldPreload = headLinks.some((tag) => {
		const rel = attributeValue(tag, 'rel');
		const href = attributeValue(tag, 'href');
		const asAttr = attributeValue(tag, 'as');
		return rel !== null && rel.toLowerCase() === 'preload'
			&& asAttr !== null && asAttr.toLowerCase() === 'font'
			&& href !== null && href.includes(TIEMPOS_BOLD_PRELOAD_FILE);
	});
	if (expectedLabelToken === APPROVED_DEFAULT_LABEL_TOKEN) {
		contracts['tiempos-label-ownership'] = detail.tiemposMarker && detail.tiemposBoldPreload;
		if (!detail.tiemposMarker) {
			fail('tiempos-label-ownership', 'default ' + APPROVED_DEFAULT_LABEL_TOKEN + ' token requires the ' + TIEMPOS_MARKER_CLASS + ' marker on the modern root');
		}
		if (!detail.tiemposBoldPreload) {
			fail('tiempos-label-ownership', 'default ' + APPROVED_DEFAULT_LABEL_TOKEN + ' token requires the ' + TIEMPOS_BOLD_PRELOAD_FILE + ' preload in <head>');
		}
	} else {
		contracts['tiempos-label-ownership'] = !detail.tiemposMarker && !detail.tiemposBoldPreload;
		if (detail.tiemposMarker) {
			fail('tiempos-label-ownership', 'custom Studio token ' + expectedLabelToken + ' must not receive the Tiempos marker');
		}
		if (detail.tiemposBoldPreload) {
			fail('tiempos-label-ownership', 'custom Studio token ' + expectedLabelToken + ' must not receive the Tiempos preload');
		}
	}

	return finalizeVerdict(contracts, failures, detail);
}

function finalizeVerdict(contracts, failures, detail) {
	const coherent = Object.values(contracts).every(Boolean) && failures.length === 0;
	return { coherent, contracts, failures, detail };
}

async function fetchCanonicalOscars() {
	const response = await fetch(CANONICAL_OSCARS_URL, {
		redirect: 'follow',
		headers: {
			'User-Agent': 'Mozilla/5.0 (compatible; LunaraCanonicalCoherencyGate/1.0)',
			Accept: 'text/html,application/xhtml+xml',
		},
	});
	const html = await response.text();
	return {
		url: CANONICAL_OSCARS_URL,
		finalUrl: response.url,
		statusCode: response.status,
		html,
	};
}

/**
 * Exit-code discipline: only a LIVE coherent probe exits 0. A replay that
 * passes every contract exits REPLAY_COHERENT_EXIT so no wrapper can mistake
 * a diagnostic replay for the anonymous canonical deployment proof.
 */
const LIVE_COHERENT_EXIT = 0;
const INCOHERENT_EXIT = 1;
const USAGE_ERROR_EXIT = 2;
const REPLAY_COHERENT_EXIT = 3;

function parseArgs(argv) {
	const args = { expectedLabelToken: APPROVED_DEFAULT_LABEL_TOKEN };
	for (let i = 0; i < argv.length; i += 1) {
		const flag = argv[i];
		if (flag === '--expected-version') {
			args.expectedVersion = argv[++i];
		} else if (flag === '--expected-label-token') {
			args.expectedLabelToken = argv[++i];
		} else if (flag === '--replay-html-file') {
			args.replayHtmlFile = argv[++i];
		} else if (flag === '--replay-final-url') {
			args.replayFinalUrl = argv[++i];
		} else if (flag === '--replay-status') {
			args.replayStatus = Number(argv[++i]);
		} else {
			throw new Error('Unknown argument: ' + flag);
		}
	}
	if (typeof args.expectedVersion !== 'string' || args.expectedVersion.trim() === '') {
		throw new Error('--expected-version is required: pass the deployed theme version this HTML must match.');
	}
	if (args.replayHtmlFile) {
		// Replay provenance must be stated, never fabricated: a capture's real
		// final URL and status are part of the evidence, so silent defaults
		// are refused.
		if (typeof args.replayFinalUrl !== 'string' || args.replayFinalUrl.trim() === '') {
			throw new Error('--replay-final-url is required with --replay-html-file: state the capture\'s real final URL.');
		}
		if (!Number.isFinite(args.replayStatus)) {
			throw new Error('--replay-status is required with --replay-html-file: state the capture\'s real HTTP status.');
		}
	}
	return args;
}

async function main() {
	let args;
	try {
		args = parseArgs(process.argv.slice(2));
	} catch (error) {
		process.stderr.write(String(error.message) + '\n');
		process.stderr.write('Usage: node lunara-oscars-canonical-coherency-gate.js --expected-version <version> [--expected-label-token <token>] [--replay-html-file <path> --replay-status <code> --replay-final-url <url>]\n');
		process.stderr.write('Exit codes: 0 live coherent (the only deployment proof); 1 incoherent; 2 usage error; 3 replay coherent (diagnostic only, never proof).\n');
		process.exitCode = USAGE_ERROR_EXIT;
		return;
	}

	let capture;
	let mode;
	if (args.replayHtmlFile) {
		mode = 'replay';
		capture = {
			url: CANONICAL_OSCARS_URL,
			finalUrl: args.replayFinalUrl,
			statusCode: args.replayStatus,
			html: fs.readFileSync(args.replayHtmlFile, 'utf8'),
		};
	} else {
		mode = 'live';
		try {
			capture = await fetchCanonicalOscars();
		} catch (error) {
			process.stdout.write(JSON.stringify({ mode, coherent: false, proof: false, failures: ['canonical-response-identity: fetch failed — ' + String(error.message)] }, null, 2) + '\n');
			process.exitCode = INCOHERENT_EXIT;
			return;
		}
	}

	const verdict = analyzeOscarsCanonicalCoherency({
		url: capture.url,
		finalUrl: capture.finalUrl,
		statusCode: capture.statusCode,
		html: capture.html,
		expectedVersion: args.expectedVersion,
		expectedLabelToken: args.expectedLabelToken,
	});
	const proof = mode === 'live' && verdict.coherent;
	process.stdout.write(JSON.stringify({ mode, proof, ...verdict }, null, 2) + '\n');
	if (!verdict.coherent) {
		process.exitCode = INCOHERENT_EXIT;
	} else {
		process.exitCode = mode === 'live' ? LIVE_COHERENT_EXIT : REPLAY_COHERENT_EXIT;
	}
}

if (require.main === module) {
	main();
}

module.exports = {
	CANONICAL_OSCARS_URL,
	LIVE_COHERENT_EXIT,
	INCOHERENT_EXIT,
	USAGE_ERROR_EXIT,
	REPLAY_COHERENT_EXIT,
	APPROVED_DEFAULT_LABEL_TOKEN,
	PORTAL_ROOT_CLASS,
	TIEMPOS_MARKER_CLASS,
	TIEMPOS_BOLD_PRELOAD_FILE,
	VERSION_ATTRIBUTE,
	STRUCTURAL_VARS_STYLE_ID,
	STRUCTURAL_SEED_STYLE_ID,
	ROUTE_STYLESHEET_LINK_ID,
	LEGACY_SHELL_STYLE_LINK_ID,
	REQUIRED_ANCHOR_IDS,
	DATA_CONDITIONAL_ANCHOR_IDS,
	isCanonicalOscarsUrl,
	analyzeOscarsCanonicalCoherency,
};
