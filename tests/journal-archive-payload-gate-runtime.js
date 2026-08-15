'use strict';

const assert = require('node:assert/strict');
const crypto = require('node:crypto');
const {
    JOURNAL_PRODUCTION_HTML_MAX_BYTES,
    JOURNAL_STAGING_MAX_DELTA_PCT,
    measureJournalHtmlPayload,
    evaluateJournalHtmlPayload,
} = require('./tools/lunara-journal-payload-gate');

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

const productionPass = evaluateJournalHtmlPayload({
    candidate: {
        url: 'https://lunarafilm.com/journal/',
        hostname: 'lunarafilm.com',
        totalBytes: 117999,
        environmentBlocks: {},
        environmentBlockBytes: 0,
    },
});
assert.equal(productionPass.mode, 'production-absolute');
assert.equal(productionPass.passed, true);
assert.equal(productionPass.productionHardCapPassed, true);

const productionFail = evaluateJournalHtmlPayload({
    control: {
        url: 'https://lunarafilm.com/journal/?baseline=1',
        hostname: 'lunarafilm.com',
        totalBytes: 200000,
        environmentBlocks: { 'global-styles-inline-css': block(90000, 'prod-control') },
        environmentBlockBytes: 90000,
    },
    candidate: {
        url: 'https://lunarafilm.com/journal/',
        hostname: 'lunarafilm.com',
        totalBytes: 118001,
        environmentBlocks: {},
        environmentBlockBytes: 0,
    },
});
assert.equal(productionFail.passed, false, 'Environment normalization must never waive the production hard cap.');
assert.equal(productionFail.productionLimitBytes, 118000);

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
