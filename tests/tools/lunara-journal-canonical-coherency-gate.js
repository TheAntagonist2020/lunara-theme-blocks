'use strict';

/**
 * Anonymous canonical Journal cache-coherency sentinel.
 *
 * Theme 3.2.48's direct-production canary was rolled back because the exact
 * anonymous no-query `/journal/` response kept serving legacy cached archive
 * markup while query-bearing routes, taxonomies, pagination, and
 * authenticated requests had advanced to the candidate. This gate exists so
 * that split can never pass silently again: it captures the one URL that
 * failed — the anonymous canonical route, without query parameters and
 * without cache-busting — and fails closed before any functional or
 * performance continuation when the served HTML identity differs from the
 * deployed theme.
 *
 * Query-bearing root variants, authenticated requests, and cache-busted URLs
 * are diagnostic cohorts only. They cannot satisfy this proof, and the gate
 * refuses to evaluate them as evidence.
 *
 * The live probe intentionally sends no Cache-Control or Pragma request
 * headers: a cache-bypassing request would mask exactly the stale-cache
 * identity split this sentinel exists to detect.
 */

const fs = require('node:fs');

const CANONICAL_JOURNAL_URL = 'https://lunarafilm.com/journal/';
const EXPECTED_CARD_COUNT = 8;
const EXPECTED_LEAD_COUNT = 1;
const APPROVED_DEFAULT_LABEL_TOKEN = 'tiempos-text';
const JOURNAL_ROOT_CLASS = 'lunara-journal-archive-page';
const JOURNAL_CARD_CLASS = 'lunara-journal-archive-card';
const JOURNAL_LEAD_CLASS = 'is-lead';
const TIEMPOS_MARKER_CLASS = 'is-label-font-tiempos';
const TIEMPOS_BOLD_PRELOAD_FILE = 'TiemposText-Bold.woff2';
const MODERN_ROOT_ID = 'primary';
const VERSION_ATTRIBUTE = 'data-lunara-theme-version';
const STRUCTURAL_VARS_STYLE_ID = 'lunara-journal-archive-vars';
const STRUCTURAL_SEED_STYLE_ID = 'lunara-journal-archive-critical-css';
const ROUTE_STYLESHEET_LINK_ID = 'lunara-journal-archive-css';
const LEGACY_STUDIO_STYLE_ID = 'lunara-journal-archive-studio-css';

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

/**
 * The requested URL must be the exact anonymous canonical route. Anything
 * else — a query string, a fragment, another origin, another path — is
 * refused as proof rather than evaluated.
 */
function isCanonicalJournalUrl(value) {
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
		&& parsed.pathname === '/journal/'
		&& parsed.search === ''
		&& parsed.hash === ''
		// Exact-serialization backstop: a bare '?' or '#' parses to empty
		// search/hash yet still serializes to a distinct request identity, and
		// userinfo is an authenticated variant — none of them are the
		// anonymous canonical proof.
		&& parsed.href === CANONICAL_JOURNAL_URL;
}

/**
 * Analyze one captured response from the anonymous canonical `/journal/`
 * route. Every contract defaults to failure: missing markup, ambiguous
 * markup, or unparseable input all fail closed.
 */
function analyzeJournalCanonicalCoherency({
	url,
	finalUrl = url,
	statusCode,
	html,
	expectedVersion,
	expectedLabelToken = APPROVED_DEFAULT_LABEL_TOKEN,
	expectedCardCount = EXPECTED_CARD_COUNT,
}) {
	const contracts = {
		'canonical-request-identity': false,
		'canonical-response-identity': false,
		'theme-version-binding': false,
		'journal-modern-root-identity': false,
		'journal-zero-legacy-roots': false,
		'journal-structural-css-before-body': false,
		'journal-no-legacy-route-assets': false,
		'one-page-one-lead': false,
		'eight-nonblank-cards': false,
		'tiempos-label-ownership': false,
	};
	const failures = [];
	const detail = {
		modernRootCount: 0,
		legacyRootCount: 0,
		journalRootCount: 0,
		cardCount: 0,
		nonblankCardCount: 0,
		leadCount: 0,
		rootVersion: null,
		expectedVersion: typeof expectedVersion === 'string' ? expectedVersion : null,
		expectedLabelToken,
		tiemposMarker: false,
		tiemposBoldPreload: false,
	};

	const fail = (contract, message) => {
		failures.push(contract + ': ' + message);
	};

	contracts['canonical-request-identity'] = isCanonicalJournalUrl(url);
	if (!contracts['canonical-request-identity']) {
		fail('canonical-request-identity', 'the gate only accepts the exact anonymous no-query ' + CANONICAL_JOURNAL_URL + ' URL; got ' + String(url));
	}

	contracts['canonical-response-identity'] = statusCode === 200 && isCanonicalJournalUrl(finalUrl);
	if (statusCode !== 200) {
		fail('canonical-response-identity', 'expected HTTP 200, got ' + String(statusCode));
	} else if (!isCanonicalJournalUrl(finalUrl)) {
		fail('canonical-response-identity', 'the response settled off the canonical route: ' + String(finalUrl));
	}

	// The card expectation may track a legitimate Journal Archive Studio
	// items-per-page change, but only inside the Studio's own legal bounds —
	// anything else fails closed instead of running open.
	if (!Number.isInteger(expectedCardCount) || expectedCardCount < 1 || expectedCardCount > 24) {
		fail('eight-nonblank-cards', 'expectedCardCount must be an integer between 1 and 24; failing closed');
		return finalizeVerdict(contracts, failures, detail);
	}
	detail.expectedCardCount = expectedCardCount;

	const document = typeof html === 'string' ? html : '';
	const bodyIndex = document.search(/<body\b/i);
	if (document === '' || bodyIndex === -1) {
		fail('journal-structural-css-before-body', 'response HTML is empty or has no <body>; failing closed');
		return finalizeVerdict(contracts, failures, detail);
	}
	const headHtml = document.slice(0, bodyIndex);

	if (typeof expectedVersion !== 'string' || expectedVersion.trim() === '') {
		fail('theme-version-binding', 'an expected deployed theme version is required; failing closed');
		return finalizeVerdict(contracts, failures, detail);
	}

	const mainTags = document.match(/<main\b[^>]*>/gi) || [];
	let modernRoot = null;
	for (const tag of mainTags) {
		const tokens = classTokens(tag);
		if (!tokens.includes(JOURNAL_ROOT_CLASS)) {
			continue;
		}
		detail.journalRootCount += 1;
		if (attributeValue(tag, 'id') === MODERN_ROOT_ID) {
			detail.modernRootCount += 1;
			modernRoot = tag;
		} else {
			detail.legacyRootCount += 1;
		}
	}

	contracts['journal-modern-root-identity'] = detail.modernRootCount === 1;
	if (detail.modernRootCount !== 1) {
		fail('journal-modern-root-identity', 'expected exactly one modern #' + MODERN_ROOT_ID + ' Journal root, found ' + detail.modernRootCount);
	}
	contracts['journal-zero-legacy-roots'] = detail.legacyRootCount === 0 && detail.journalRootCount === detail.modernRootCount;
	if (detail.legacyRootCount !== 0) {
		fail('journal-zero-legacy-roots', 'found ' + detail.legacyRootCount + ' legacy Journal root(s) — the mixed identity that forced the 3.2.48 rollback');
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

	const hasVars = elementIdPattern(STRUCTURAL_VARS_STYLE_ID).test(headHtml);
	const hasSeed = elementIdPattern(STRUCTURAL_SEED_STYLE_ID).test(headHtml);
	const hasRouteLink = elementIdPattern(ROUTE_STYLESHEET_LINK_ID).test(headHtml);
	contracts['journal-structural-css-before-body'] = hasVars && hasSeed && hasRouteLink;
	if (!hasVars) {
		fail('journal-structural-css-before-body', 'missing #' + STRUCTURAL_VARS_STYLE_ID + ' in <head>');
	}
	if (!hasSeed) {
		fail('journal-structural-css-before-body', 'missing #' + STRUCTURAL_SEED_STYLE_ID + ' in <head>');
	}
	if (!hasRouteLink) {
		fail('journal-structural-css-before-body', 'missing #' + ROUTE_STYLESHEET_LINK_ID + ' route stylesheet link in <head>');
	}

	contracts['journal-no-legacy-route-assets'] = !elementIdPattern(LEGACY_STUDIO_STYLE_ID).test(document);
	if (!contracts['journal-no-legacy-route-assets']) {
		fail('journal-no-legacy-route-assets', 'legacy #' + LEGACY_STUDIO_STYLE_ID + ' block present — mixed old/new route assets');
	}

	const articles = document.match(/<article\b[^>]*>[\s\S]*?<\/article>/gi) || [];
	for (const article of articles) {
		const openTag = (article.match(/<article\b[^>]*>/i) || [''])[0];
		const tokens = classTokens(openTag);
		if (!tokens.includes(JOURNAL_CARD_CLASS)) {
			continue;
		}
		detail.cardCount += 1;
		if (tokens.includes(JOURNAL_LEAD_CLASS)) {
			detail.leadCount += 1;
		}
		const titleMatch = article.match(/<h3\b[^>]*class\s*=\s*["'][^"']*\blunara-review-grid-title\b[^"']*["'][^>]*>([\s\S]*?)<\/h3>/i);
		const titleText = titleMatch ? titleMatch[1].replace(/<[^>]*>/g, '').replace(/&[a-z#0-9]+;/gi, ' ').trim() : '';
		if (titleText !== '') {
			detail.nonblankCardCount += 1;
		}
	}

	contracts['one-page-one-lead'] = detail.leadCount === EXPECTED_LEAD_COUNT;
	if (detail.leadCount !== EXPECTED_LEAD_COUNT) {
		fail('one-page-one-lead', 'expected exactly ' + EXPECTED_LEAD_COUNT + ' lead on page one, found ' + detail.leadCount);
	}
	contracts['eight-nonblank-cards'] = detail.cardCount === expectedCardCount
		&& detail.nonblankCardCount === expectedCardCount;
	if (detail.cardCount !== expectedCardCount) {
		fail('eight-nonblank-cards', 'expected exactly ' + expectedCardCount + ' Journal cards, found ' + detail.cardCount);
	} else if (detail.nonblankCardCount !== expectedCardCount) {
		fail('eight-nonblank-cards', (detail.cardCount - detail.nonblankCardCount) + ' card(s) have a blank title');
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

async function fetchCanonicalJournal() {
	const response = await fetch(CANONICAL_JOURNAL_URL, {
		redirect: 'follow',
		headers: {
			'User-Agent': 'Mozilla/5.0 (compatible; LunaraCanonicalCoherencyGate/1.0)',
			Accept: 'text/html,application/xhtml+xml',
		},
	});
	const html = await response.text();
	return {
		url: CANONICAL_JOURNAL_URL,
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
	const args = { expectedLabelToken: APPROVED_DEFAULT_LABEL_TOKEN, expectedCardCount: EXPECTED_CARD_COUNT };
	for (let i = 0; i < argv.length; i += 1) {
		const flag = argv[i];
		if (flag === '--expected-version') {
			args.expectedVersion = argv[++i];
		} else if (flag === '--expected-label-token') {
			args.expectedLabelToken = argv[++i];
		} else if (flag === '--expected-cards') {
			args.expectedCardCount = Number(argv[++i]);
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
		process.stderr.write('Usage: node lunara-journal-canonical-coherency-gate.js --expected-version <version> [--expected-label-token <token>] [--expected-cards <1-24>] [--replay-html-file <path> --replay-status <code> --replay-final-url <url>]\n');
		process.stderr.write('Exit codes: 0 live coherent (the only deployment proof); 1 incoherent; 2 usage error; 3 replay coherent (diagnostic only, never proof).\n');
		process.exitCode = USAGE_ERROR_EXIT;
		return;
	}

	let capture;
	let mode;
	if (args.replayHtmlFile) {
		mode = 'replay';
		capture = {
			url: CANONICAL_JOURNAL_URL,
			finalUrl: args.replayFinalUrl,
			statusCode: args.replayStatus,
			html: fs.readFileSync(args.replayHtmlFile, 'utf8'),
		};
	} else {
		mode = 'live';
		try {
			capture = await fetchCanonicalJournal();
		} catch (error) {
			process.stdout.write(JSON.stringify({ mode, coherent: false, proof: false, failures: ['canonical-response-identity: fetch failed — ' + String(error.message)] }, null, 2) + '\n');
			process.exitCode = INCOHERENT_EXIT;
			return;
		}
	}

	const verdict = analyzeJournalCanonicalCoherency({
		url: capture.url,
		finalUrl: capture.finalUrl,
		statusCode: capture.statusCode,
		html: capture.html,
		expectedVersion: args.expectedVersion,
		expectedLabelToken: args.expectedLabelToken,
		expectedCardCount: args.expectedCardCount,
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
	CANONICAL_JOURNAL_URL,
	EXPECTED_CARD_COUNT,
	EXPECTED_LEAD_COUNT,
	LIVE_COHERENT_EXIT,
	INCOHERENT_EXIT,
	USAGE_ERROR_EXIT,
	REPLAY_COHERENT_EXIT,
	APPROVED_DEFAULT_LABEL_TOKEN,
	TIEMPOS_MARKER_CLASS,
	TIEMPOS_BOLD_PRELOAD_FILE,
	VERSION_ATTRIBUTE,
	STRUCTURAL_VARS_STYLE_ID,
	STRUCTURAL_SEED_STYLE_ID,
	ROUTE_STYLESHEET_LINK_ID,
	LEGACY_STUDIO_STYLE_ID,
	isCanonicalJournalUrl,
	analyzeJournalCanonicalCoherency,
};
