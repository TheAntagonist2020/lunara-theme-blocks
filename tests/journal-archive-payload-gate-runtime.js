'use strict';

const assert = require('node:assert/strict');
const crypto = require('node:crypto');
const {
    JOURNAL_PRODUCTION_TOTAL_MAX_BYTES,
    JOURNAL_PRODUCTION_HTML_MAX_BYTES,
    JOURNAL_STAGING_MAX_DELTA_PCT,
    measureJournalHtmlPayload,
    evaluateJournalHtmlPayload,
} = require('./tools/lunara-journal-payload-gate');

assert.equal(JOURNAL_PRODUCTION_TOTAL_MAX_BYTES, 190000, 'The decoded production response must remain at or below 190,000 bytes in total.');
assert.equal(JOURNAL_PRODUCTION_HTML_MAX_BYTES, 118000, 'The production-host hard cap must remain exactly 118,000 bytes.');
assert.equal(JOURNAL_STAGING_MAX_DELTA_PCT, 5, 'Matched staging may regress by no more than five percent.');

const measured = measureJournalHtmlPayload({
    url: 'https://staging-example.wpcomstaging.com/journal/',
    html: '<!doctype html><html><head><style id="global-styles-inline-css">body{color:white}</style><style id="gutenkit-frontend-common-inline-css">.g{display:block}</style><style id="lunara-journal-archive-vars">:root{--x:1}</style></head><body><main>Journal</main></body></html>',
});
assert.equal(measured.hostname, 'staging-example.wpcomstaging.com');
assert.ok(measured.totalBytes > measured.environmentBlockBytes);
assert.deepEqual(Object.keys(measured.environmentBlocks).sort(), [
    'global-styles-inline-css',
    'gutenkit-frontend-common-inline-css',
]);
assert.match(measured.environmentBlocks['global-styles-inline-css'].sha256, /^[a-f0-9]{64}$/);
assert.equal(measured.environmentBlocks['global-styles-inline-css'].bytes > 0, true);
assert.equal(measured.themeBlocks['lunara-journal-archive-vars'] > 0, true, 'Theme blocks must be reported separately, never normalized away as plugin environment.');

const duplicated = measureJournalHtmlPayload({
    url: 'https://staging-example.wpcomstaging.com/journal/',
    html: '<style id="global-styles-inline-css">a{color:red}</style><style id="global-styles-inline-css">a{color:red}</style>',
});
assert.deepEqual(duplicated.duplicateEnvironmentBlockIds, ['global-styles-inline-css'], 'Duplicate environment IDs must be surfaced instead of overwritten.');

const redirectedOffOrigin = measureJournalHtmlPayload({
    url: 'https://staging-example.wpcomstaging.com/journal/',
    finalUrl: 'https://attacker.example/journal/',
    html: '<main>redirected</main>',
});
assert.equal(redirectedOffOrigin.redirectValid, false, 'A fetch redirected off origin must be marked invalid from the final response URL.');

const redirectedOffRoute = measureJournalHtmlPayload({
    url: 'https://staging-example.wpcomstaging.com/journal/',
    finalUrl: 'https://staging-example.wpcomstaging.com/journal/page/2/',
    html: '<main>redirected</main>',
});
assert.equal(redirectedOffRoute.redirectValid, false, 'A fetch redirected off /journal/ must be marked invalid from the final response URL.');

const block = (bytes, seed) => ({
    bytes,
    sha256: crypto.createHash('sha256').update(`${seed}:${bytes}`).digest('hex'),
});

const productionBaseline = evaluateJournalHtmlPayload({
    candidate: {
        url: 'https://lunarafilm.com/journal/',
        hostname: 'lunarafilm.com',
        totalBytes: 180069,
        environmentBlocks: {
            'jetpack-boost-critical-css': block(69065, 'production-3.2.43-critical'),
        },
        environmentBlockBytes: 69065,
    },
});
assert.equal(productionBaseline.mode, 'production-absolute');
assert.equal(productionBaseline.passed, true, 'The locked 180,069B production response must pass after subtracting its one exact 69,065B Boost critical block.');
assert.equal(productionBaseline.productionTotalBytes, 180069);
assert.equal(productionBaseline.productionBoostCriticalBytes, 69065);
assert.equal(productionBaseline.productionNormalizedBytes, 111004);
assert.equal(productionBaseline.productionTotalCapPassed, true);
assert.equal(productionBaseline.productionHardCapPassed, true);

const productionWithoutBoost = evaluateJournalHtmlPayload({
    candidate: {
        url: 'https://lunarafilm.com/journal/',
        totalBytes: 117999,
        environmentBlocks: {},
        environmentBlockBytes: 0,
    },
});
assert.equal(productionWithoutBoost.passed, true, 'A production response with no Boost block must subtract zero and pass only on its raw bytes.');
assert.equal(productionWithoutBoost.productionBoostCriticalBytes, 0);
assert.equal(productionWithoutBoost.productionNormalizedBytes, 117999);

const productionTotalFail = evaluateJournalHtmlPayload({
    candidate: {
        url: 'https://lunarafilm.com/journal/',
        totalBytes: 190001,
        environmentBlocks: {
            'jetpack-boost-critical-css': block(80000, 'total-cap-critical'),
        },
        environmentBlockBytes: 80000,
    },
});
assert.equal(productionTotalFail.productionNormalizedBytes, 110001);
assert.equal(productionTotalFail.productionHardCapPassed, true);
assert.equal(productionTotalFail.productionTotalCapPassed, false);
assert.equal(productionTotalFail.passed, false, 'Passing the no-Boost cap must never waive the 190,000-byte decoded-total cap.');

const productionNormalizedFail = evaluateJournalHtmlPayload({
    candidate: {
        url: 'https://lunarafilm.com/journal/',
        totalBytes: 180000,
        environmentBlocks: {
            'jetpack-boost-critical-css': block(60000, 'normalized-cap-critical'),
        },
        environmentBlockBytes: 60000,
    },
});
assert.equal(productionNormalizedFail.productionTotalCapPassed, true);
assert.equal(productionNormalizedFail.productionNormalizedBytes, 120000);
assert.equal(productionNormalizedFail.productionHardCapPassed, false);
assert.equal(productionNormalizedFail.passed, false, 'Production HTML minus only Boost critical CSS must remain within 118,000 bytes.');

const productionOtherEnvironmentFail = evaluateJournalHtmlPayload({
    candidate: {
        url: 'https://lunarafilm.com/journal/',
        totalBytes: 118001,
        environmentBlocks: {
            'global-styles-inline-css': block(50000, 'must-not-subtract'),
        },
        environmentBlockBytes: 50000,
    },
});
assert.equal(productionOtherEnvironmentFail.productionBoostCriticalBytes, 0);
assert.equal(productionOtherEnvironmentFail.productionNormalizedBytes, 118001);
assert.equal(productionOtherEnvironmentFail.passed, false, 'Production normalization must never subtract global styles or any environment block other than Boost critical CSS.');

const productionDuplicateBoostFail = evaluateJournalHtmlPayload({
    candidate: {
        url: 'https://lunarafilm.com/journal/',
        totalBytes: 180069,
        environmentBlocks: {
            'jetpack-boost-critical-css': block(69065, 'production-3.2.43-critical'),
        },
        environmentBlockBytes: 69065,
        duplicateEnvironmentBlockIds: ['jetpack-boost-critical-css'],
    },
});
assert.equal(productionDuplicateBoostFail.productionEvidenceValid, false);
assert.equal(productionDuplicateBoostFail.passed, false, 'Duplicate Boost critical IDs must fail closed instead of permitting an ambiguous subtraction.');

const productionMalformedBoostFail = evaluateJournalHtmlPayload({
    candidate: {
        url: 'https://lunarafilm.com/journal/',
        totalBytes: 180069,
        environmentBlocks: {
            'jetpack-boost-critical-css': { bytes: 69065, sha256: 'not-a-hash' },
        },
        environmentBlockBytes: 69065,
    },
});
assert.equal(productionMalformedBoostFail.productionEvidenceValid, false);
assert.equal(productionMalformedBoostFail.passed, false, 'A present Boost block must carry its exact measured byte count and SHA-256 before subtraction.');

const productionNullBoostFail = evaluateJournalHtmlPayload({
    candidate: {
        url: 'https://lunarafilm.com/journal/',
        totalBytes: 117000,
        environmentBlocks: {
            'jetpack-boost-critical-css': null,
        },
        environmentBlockBytes: 0,
    },
});
assert.equal(productionNullBoostFail.productionEvidenceValid, false);
assert.equal(productionNullBoostFail.comparable, false);
assert.equal(productionNullBoostFail.passed, false, 'A present null or wrong-shaped Boost descriptor must fail closed, never masquerade as the explicit absent-Boost path.');

const stagingBaseline = {
    url: 'https://staging-example.wpcomstaging.com/journal/?version=3.2.43',
    hostname: 'staging-example.wpcomstaging.com',
    totalBytes: 148282,
    environmentBlocks: {
        'global-styles-inline-css': block(25905, 'global'),
        'gutenkit-frontend-common-inline-css': block(835, 'gutenkit'),
    },
    environmentBlockBytes: 26740,
};
const stagingCandidate = {
    url: 'https://staging-example.wpcomstaging.com/journal/?version=3.2.45',
    hostname: 'staging-example.wpcomstaging.com',
    totalBytes: 145889,
    environmentBlocks: {
        'global-styles-inline-css': block(25905, 'global'),
        'gutenkit-frontend-common-inline-css': block(835, 'gutenkit'),
    },
    environmentBlockBytes: 26740,
};
const stagingPass = evaluateJournalHtmlPayload({ control: stagingBaseline, candidate: stagingCandidate });
assert.equal(stagingPass.mode, 'matched-staging');
assert.equal(stagingPass.comparable, true);
assert.equal(stagingPass.passed, true);
assert.ok(stagingPass.rawDeltaPct < 0 && Math.abs(stagingPass.rawDeltaPct - (-1.6138)) < 0.01);
assert.ok(stagingPass.normalizedDeltaPct < 0, 'Matched staging must report the theme-owned normalized delta separately.');
assert.equal(stagingPass.productionHardCapPassed, null, 'A staging pass must never be represented as a production hard-cap pass.');

const stagingRegression = evaluateJournalHtmlPayload({
    control: stagingBaseline,
    candidate: { ...stagingCandidate, totalBytes: Math.ceil(stagingBaseline.totalBytes * 1.051) },
});
assert.equal(stagingRegression.passed, false, 'A matched-staging regression above five percent must fail.');

const normalizedRegression = evaluateJournalHtmlPayload({
    control: stagingBaseline,
    candidate: { ...stagingCandidate, totalBytes: 154440 },
});
assert.ok(normalizedRegression.rawDeltaPct < 5 && normalizedRegression.normalizedDeltaPct > 5);
assert.equal(normalizedRegression.passed, false, 'Environment bytes must not dilute a greater-than-five-percent normalized regression into a pass.');

const crossOrigin = evaluateJournalHtmlPayload({
    control: stagingBaseline,
    candidate: { ...stagingCandidate, url: 'https://other.wpcomstaging.com/journal/', hostname: 'other.wpcomstaging.com' },
});
assert.equal(crossOrigin.comparable, false, 'Different staging origins must not be normalized together.');
assert.equal(crossOrigin.passed, false);

const pluginMismatch = evaluateJournalHtmlPayload({
    control: stagingBaseline,
    candidate: {
        ...stagingCandidate,
        environmentBlocks: { 'global-styles-inline-css': block(25905, 'global') },
        environmentBlockBytes: 25905,
    },
});
assert.equal(pluginMismatch.environmentComparable, false, 'Plugin-owned inline block presence must match before staging payloads are comparable.');
assert.equal(pluginMismatch.passed, false);

const pluginByteDrift = evaluateJournalHtmlPayload({
    control: stagingBaseline,
    candidate: {
        ...stagingCandidate,
        environmentBlocks: {
            ...stagingCandidate.environmentBlocks,
            'global-styles-inline-css': block(25906, 'global'),
        },
        environmentBlockBytes: 26741,
    },
});
assert.equal(pluginByteDrift.environmentComparable, false, 'Even one byte of environment-owned block drift must make a staging comparison inconclusive.');
assert.equal(pluginByteDrift.passed, false);

const pluginHashDrift = evaluateJournalHtmlPayload({
    control: stagingBaseline,
    candidate: {
        ...stagingCandidate,
        environmentBlocks: {
            ...stagingCandidate.environmentBlocks,
            'global-styles-inline-css': block(25905, 'different-content'),
        },
    },
});
assert.equal(pluginHashDrift.environmentComparable, false, 'Equal byte counts with different environment content hashes must remain inconclusive.');
assert.equal(pluginHashDrift.passed, false);

const spoofedHostname = evaluateJournalHtmlPayload({
    control: stagingBaseline,
    candidate: {
        ...stagingCandidate,
        url: 'https://attacker.example/journal/',
        hostname: stagingBaseline.hostname,
    },
});
assert.equal(spoofedHostname.comparable, false, 'The gate must derive origin from URL instead of trusting a supplied hostname field.');

const wrongRoute = evaluateJournalHtmlPayload({
    control: stagingBaseline,
    candidate: { ...stagingCandidate, url: 'https://staging-example.wpcomstaging.com/journal/page/2/' },
});
assert.equal(wrongRoute.comparable, false, 'Matched staging payload evidence must compare the canonical /journal/ route only.');
assert.equal(wrongRoute.passed, false);

const duplicateEnvironment = evaluateJournalHtmlPayload({
    control: { ...stagingBaseline, duplicateEnvironmentBlockIds: ['global-styles-inline-css'] },
    candidate: stagingCandidate,
});
assert.equal(duplicateEnvironment.environmentComparable, false);
assert.equal(duplicateEnvironment.passed, false, 'Duplicate environment blocks must invalidate the comparison.');

const redirectedEvidence = evaluateJournalHtmlPayload({
    control: stagingBaseline,
    candidate: { ...stagingCandidate, redirectValid: false },
});
assert.equal(redirectedEvidence.comparable, false);
assert.equal(redirectedEvidence.passed, false, 'Redirected payload evidence must never pass by retaining its requested canonical URL.' );

const insecureProduction = evaluateJournalHtmlPayload({
    candidate: {
        url: 'http://lunarafilm.com/journal/',
        totalBytes: 100000,
        environmentBlocks: {},
        environmentBlockBytes: 0,
    },
});
assert.equal(insecureProduction.passed, false, 'Only the exact HTTPS production origin may exercise the production hard gate.');

const wrongProductionRoute = evaluateJournalHtmlPayload({
    candidate: {
        url: 'https://lunarafilm.com/journal/page/2/',
        totalBytes: 100000,
        environmentBlocks: {},
        environmentBlockBytes: 0,
    },
});
assert.equal(wrongProductionRoute.passed, false, 'The 118 KB production gate applies to canonical /journal/ only, never another route masquerading as evidence.');

process.stdout.write('Journal archive payload gate runtime passed.\n');
