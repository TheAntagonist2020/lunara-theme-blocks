'use strict';

const fs = require('node:fs');
const crypto = require('node:crypto');

const JOURNAL_PRODUCTION_HTML_MAX_BYTES = 118000;
const JOURNAL_STAGING_MAX_DELTA_PCT = 5;
const ENVIRONMENT_STYLE_IDS = new Set([
    'global-styles-inline-css',
    'wp-block-library-inline-css',
    'wp-block-library-inline-css-extra',
    'gutenkit-frontend-common-inline-css',
    'jetpack_likes-inline-css',
    'jetpack-global-styles-frontend-style-inline-css',
    'ai_summarization-inline-css',
    'wp-img-auto-sizes-contain-inline-css',
    'jetpack-boost-critical-css',
]);

function canonicalHostname(value) {
    return new URL(value).hostname.toLowerCase().replace(/^www\./, '');
}

function styleBlocks(html) {
    const blocks = {};
    const duplicates = [];
    for (const match of String(html).matchAll(/<style\b([^>]*)>[\s\S]*?<\/style>/gi)) {
        const idMatch = match[1].match(/\bid=["']([^"']+)["']/i);
        if (!idMatch) continue;
		const id = idMatch[1];
		if (Object.prototype.hasOwnProperty.call(blocks, id)) {
			duplicates.push(id);
			continue;
		}
		blocks[id] = {
			bytes: Buffer.byteLength(match[0], 'utf8'),
			sha256: crypto.createHash('sha256').update(match[0], 'utf8').digest('hex'),
		};
    }
    return { blocks, duplicates: Array.from(new Set(duplicates)).sort() };
}

function measureJournalHtmlPayload({ url, finalUrl = url, html }) {
	const requested = new URL(url);
	const finalResponse = new URL(finalUrl);
    const normalizedUrl = finalResponse.toString();
	const redirectValid = requested.protocol === 'https:'
		&& requested.port === ''
		&& requested.pathname === '/journal/'
		&& finalResponse.protocol === 'https:'
		&& finalResponse.port === ''
		&& finalResponse.pathname === '/journal/'
		&& requested.origin === finalResponse.origin;
    const inventory = styleBlocks(html);
    const blocks = inventory.blocks;
    const environmentBlocks = {};
    const themeBlocks = {};
    for (const [id, descriptor] of Object.entries(blocks)) {
        if (ENVIRONMENT_STYLE_IDS.has(id)) {
            environmentBlocks[id] = descriptor;
        } else if (id.startsWith('lunara-')) {
            themeBlocks[id] = descriptor.bytes;
        }
    }
    const environmentBlockBytes = Object.values(environmentBlocks).reduce((sum, value) => sum + value.bytes, 0);
    return {
        url: normalizedUrl,
		requestedUrl: requested.toString(),
		finalUrl: normalizedUrl,
		redirectValid,
        hostname: canonicalHostname(normalizedUrl),
        totalBytes: Buffer.byteLength(String(html), 'utf8'),
        environmentBlocks,
        environmentBlockBytes,
        duplicateEnvironmentBlockIds: inventory.duplicates.filter((id) => ENVIRONMENT_STYLE_IDS.has(id)),
        normalizedBytes: Buffer.byteLength(String(html), 'utf8') - environmentBlockBytes,
        themeBlocks,
    };
}

function blockSignature(record) {
	const blocks = record && record.environmentBlocks ? record.environmentBlocks : {};
    return Object.keys(blocks).sort().map((id) => {
		const descriptor = blocks[id];
		return `${id}:${descriptor && descriptor.bytes}:${descriptor && descriptor.sha256}`;
	}).join('|');
}

function evaluateJournalHtmlPayload({ control = null, candidate }) {
    if (!candidate || !candidate.url || !Number.isFinite(candidate.totalBytes)) {
        throw new Error('A measured candidate Journal payload is required.');
    }
	let candidateUrl;
	try {
		candidateUrl = new URL(candidate.url);
	} catch (error) {
		return { mode: 'invalid-url', comparable: false, environmentComparable: false, passed: false, productionLimitBytes: JOURNAL_PRODUCTION_HTML_MAX_BYTES, productionHardCapPassed: null };
	}
    const candidateHost = canonicalHostname(candidateUrl.href);
	const canonicalRoute = candidateUrl.pathname === '/journal/';
	const secureOrigin = candidateUrl.protocol === 'https:' && candidateUrl.port === '';
	const finalCandidateValid = candidate.redirectValid !== false;
	const productionHost = finalCandidateValid && secureOrigin && canonicalRoute && candidateHost === 'lunarafilm.com';
	const stagingHost = finalCandidateValid && secureOrigin && canonicalRoute && candidateHost.endsWith('.wpcomstaging.com');

    if (productionHost) {
        const passed = candidate.totalBytes <= JOURNAL_PRODUCTION_HTML_MAX_BYTES;
        return {
            mode: 'production-absolute',
            comparable: true,
            environmentComparable: true,
            passed,
            candidateBytes: candidate.totalBytes,
            productionLimitBytes: JOURNAL_PRODUCTION_HTML_MAX_BYTES,
            productionHardCapPassed: passed,
            rawDeltaPct: null,
            normalizedDeltaPct: null,
        };
    }

    if (!stagingHost || !control || !control.url || !Number.isFinite(control.totalBytes)) {
        return {
            mode: 'unsupported-or-unmatched',
            comparable: false,
            environmentComparable: false,
            passed: false,
            productionLimitBytes: JOURNAL_PRODUCTION_HTML_MAX_BYTES,
            productionHardCapPassed: null,
        };
    }

	let controlUrl;
	try {
		controlUrl = new URL(control.url);
	} catch (error) {
		return { mode: 'matched-staging', comparable: false, environmentComparable: false, passed: false, productionLimitBytes: JOURNAL_PRODUCTION_HTML_MAX_BYTES, productionHardCapPassed: null };
	}
    const controlHost = canonicalHostname(controlUrl.href);
    const sameOrigin = controlUrl.origin === candidateUrl.origin;
	const sameRoute = controlUrl.pathname === '/journal/' && candidateUrl.pathname === '/journal/';
	const noDuplicateBlocks = !(control.duplicateEnvironmentBlockIds || []).length && !(candidate.duplicateEnvironmentBlockIds || []).length;
	const redirectsValid = control.redirectValid !== false && candidate.redirectValid !== false;
    const environmentComparable = noDuplicateBlocks && blockSignature(control) === blockSignature(candidate);
    const comparable = redirectsValid && sameOrigin && sameRoute && controlUrl.protocol === 'https:' && controlHost.endsWith('.wpcomstaging.com') && environmentComparable;
    const rawDeltaPct = control.totalBytes > 0
        ? ((candidate.totalBytes - control.totalBytes) / control.totalBytes) * 100
        : null;
    const controlNormalized = Number.isFinite(control.normalizedBytes)
        ? control.normalizedBytes
        : control.totalBytes - (control.environmentBlockBytes || 0);
    const candidateNormalized = Number.isFinite(candidate.normalizedBytes)
        ? candidate.normalizedBytes
        : candidate.totalBytes - (candidate.environmentBlockBytes || 0);
    const normalizedDeltaPct = controlNormalized > 0
        ? ((candidateNormalized - controlNormalized) / controlNormalized) * 100
        : null;

    return {
        mode: 'matched-staging',
        comparable,
        sameOrigin,
        environmentComparable,
        environmentBlockDeltaBytes: (candidate.environmentBlockBytes || 0) - (control.environmentBlockBytes || 0),
        rawDeltaPct,
        normalizedDeltaPct,
        passed: comparable && Number.isFinite(normalizedDeltaPct) && normalizedDeltaPct <= JOURNAL_STAGING_MAX_DELTA_PCT,
        stagingMaxDeltaPct: JOURNAL_STAGING_MAX_DELTA_PCT,
        productionLimitBytes: JOURNAL_PRODUCTION_HTML_MAX_BYTES,
        productionHardCapPassed: null,
    };
}

function parseArguments(argv) {
    const result = {};
    for (let index = 0; index < argv.length; index += 1) {
        const key = argv[index];
        if (!key.startsWith('--')) throw new Error(`Unexpected argument: ${key}`);
        const value = argv[index + 1];
        if (!value || value.startsWith('--')) throw new Error(`Missing value for ${key}`);
        result[key.slice(2)] = value;
        index += 1;
    }
    return result;
}

async function readMeasurement(args, prefix) {
    const url = args[`${prefix}Url`];
    const file = args[`${prefix}File`];
    if (!url) return null;
    let html;
    if (file) {
        html = fs.readFileSync(file, 'utf8');
    } else {
        const requestUrl = new URL(url);
        requestUrl.searchParams.set('lunara_payload_gate', String(Date.now()));
        const response = await fetch(requestUrl, {
            headers: { 'Cache-Control': 'no-cache', 'User-Agent': 'LunaraJournalPayloadGate/1.0' },
            redirect: 'follow',
        });
        if (!response.ok) throw new Error(`${prefix} returned HTTP ${response.status}.`);
		const finalUrl = response.url;
		html = await response.text();
		return measureJournalHtmlPayload({ url, finalUrl, html });
    }
    return measureJournalHtmlPayload({ url, html });
}

async function main() {
    const args = parseArguments(process.argv.slice(2));
    const candidate = await readMeasurement(args, 'candidate');
    const control = await readMeasurement(args, 'control');
    const result = evaluateJournalHtmlPayload({ control, candidate });
    process.stdout.write(`${JSON.stringify({ control, candidate, result }, null, 2)}\n`);
    if (!result.passed) process.exitCode = 1;
}

module.exports = {
    JOURNAL_PRODUCTION_HTML_MAX_BYTES,
    JOURNAL_STAGING_MAX_DELTA_PCT,
    measureJournalHtmlPayload,
    evaluateJournalHtmlPayload,
};

if (require.main === module) {
    main().catch((error) => {
        process.stderr.write(`${error && error.stack ? error.stack : error}\n`);
        process.exitCode = 1;
    });
}
