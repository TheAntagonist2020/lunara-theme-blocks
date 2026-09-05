'use strict';

const fs = require('node:fs');
const path = require('node:path');
const zlib = require('node:zlib');
const { once } = require('node:events');
const { chromium } = require('playwright');

function parseArguments(argv) {
    const parsed = {};

    for (let index = 0; index < argv.length; index += 1) {
        const token = argv[index];
        if (!token.startsWith('--')) {
            throw new Error(`Unexpected argument: ${token}`);
        }

        const key = token.slice(2);
        const next = argv[index + 1];
        if (next !== undefined && !next.startsWith('--')) {
            parsed[key] = next;
            index += 1;
        } else {
            parsed[key] = true;
        }
    }

    return parsed;
}

function readNumber(args, key, fallback) {
    const value = args[key] === undefined ? fallback : Number(args[key]);
    if (!Number.isFinite(value)) {
        throw new Error(`--${key} must be numeric.`);
    }
    return value;
}

function requireArgument(args, key) {
    if (!args[key] || args[key] === true) {
        throw new Error(`Missing required --${key} argument.`);
    }
    return String(args[key]);
}

function normalizeUrl(value) {
    const url = new URL(value);
    url.hash = '';
    return url.toString();
}

function decodeHtmlUrl(value) {
    return value
        .replace(/&#0*38;/gi, '&')
        .replace(/&amp;/gi, '&');
}

async function probeThemeIdentity(targetUrl) {
    const headers = {
        'Cache-Control': 'no-cache',
        'User-Agent': 'LunaraPerformanceGate/1.0',
    };
    const pageResponse = await fetch(targetUrl, { headers, redirect: 'follow' });
    const html = await pageResponse.text();
    const assetMatches = Array.from(
        html.matchAll(/(?:href|src)=["']([^"']*\/wp-content\/themes\/lunara-theme-blocks[^"']*)["']/gi),
        (match) => decodeHtmlUrl(match[1]),
    );
    const linkedStyle = assetMatches.find((asset) => /\/style\.css(?:\?|$)/i.test(asset));
    const canonicalStyle = new URL(
        '/wp-content/themes/lunara-theme-blocks-20260513-2300/style.css',
        targetUrl,
    );
    canonicalStyle.searchParams.set('lunara_identity', String(Date.now()));
    let styleUrl = canonicalStyle.href;
    let styleResponse = await fetch(styleUrl, { headers, redirect: 'follow' });
    if (!styleResponse.ok && linkedStyle) {
        const fallbackStyle = new URL(linkedStyle, targetUrl);
        fallbackStyle.searchParams.set('lunara_identity', String(Date.now()));
        styleUrl = fallbackStyle.href;
        styleResponse = await fetch(styleUrl, { headers, redirect: 'follow' });
    }
    const css = await styleResponse.text();
    const versionMatch = css.match(/^\s*Version:\s*([^\r\n]+)/mi);

    return {
        targetUrl,
        pageStatus: pageResponse.status,
        styleUrl,
        styleStatus: styleResponse.status,
        version: versionMatch ? versionMatch[1].trim() : null,
    };
}

function safeName(value) {
    return String(value)
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '') || 'arm';
}

function round(value, digits = 2) {
    if (!Number.isFinite(value)) {
        return null;
    }
    const factor = 10 ** digits;
    return Math.round(value * factor) / factor;
}

function median(values) {
    const sorted = values.filter(Number.isFinite).sort((a, b) => a - b);
    if (sorted.length === 0) {
        return null;
    }
    const middle = Math.floor(sorted.length / 2);
    return sorted.length % 2 === 0
        ? (sorted[middle - 1] + sorted[middle]) / 2
        : sorted[middle];
}

function average(values) {
    const valid = values.filter(Number.isFinite);
    if (valid.length === 0) {
        return null;
    }
    return valid.reduce((total, value) => total + value, 0) / valid.length;
}

function coefficientOfVariation(values) {
    const valid = values.filter(Number.isFinite);
    const mean = average(valid);
    if (valid.length < 2 || !mean) {
        return null;
    }
    const variance = valid.reduce((total, value) => total + ((value - mean) ** 2), 0) / (valid.length - 1);
    return (Math.sqrt(variance) / mean) * 100;
}

function summarizeRuns(records) {
    const lcp = records.map((record) => record.metrics && record.metrics.lcpMs).filter(Number.isFinite);
    const cls = records.map((record) => record.metrics && record.metrics.cls).filter(Number.isFinite);
    const html = records.map((record) => record.metrics && record.metrics.htmlDecodedBytes).filter(Number.isFinite);
    const inlineStyles = records.map((record) => record.metrics && record.metrics.inlineStyleBytes).filter(Number.isFinite);
    const lcpRequestStart = records
        .map((record) => record.metrics && record.metrics.lcpResource && record.metrics.lcpResource.startTime)
        .filter(Number.isFinite);
    const lcpResponseEnd = records
        .map((record) => record.metrics && record.metrics.lcpResource && record.metrics.lcpResource.responseEnd)
        .filter(Number.isFinite);

    return {
        count: records.length,
        validLcpCount: lcp.length,
        medianLcpMs: round(median(lcp)),
        meanLcpMs: round(average(lcp)),
        minLcpMs: lcp.length ? round(Math.min(...lcp)) : null,
        maxLcpMs: lcp.length ? round(Math.max(...lcp)) : null,
        lcpCvPct: round(coefficientOfVariation(lcp)),
        medianCls: round(median(cls), 5),
        medianHtmlDecodedBytes: round(median(html), 0),
        medianInlineStyleBytes: round(median(inlineStyles), 0),
        medianLcpRequestStartMs: round(median(lcpRequestStart)),
        medianLcpResponseEndMs: round(median(lcpResponseEnd)),
        maxConsoleErrors: records.length
            ? Math.max(...records.map((record) => (record.metrics && record.metrics.consoleErrors.length) || 0))
            : null,
        maxBrokenImages: records.length
            ? Math.max(...records.map((record) => (record.metrics && record.metrics.brokenImages) || 0))
            : null,
        maxOverflowPx: records.length
            ? Math.max(...records.map((record) => (record.metrics && record.metrics.overflowPx) || 0))
            : null,
    };
}

async function beginTrace(cdp, outputFile) {
    let resolveComplete;
    let rejectComplete;
    const completion = new Promise((resolve, reject) => {
        resolveComplete = resolve;
        rejectComplete = reject;
    });

    const onComplete = (payload) => resolveComplete(payload);
    cdp.once('Tracing.tracingComplete', onComplete);

    try {
        await cdp.send('Tracing.start', {
            categories: [
                'blink.user_timing',
                'devtools.timeline',
                'disabled-by-default-devtools.timeline',
                'loading',
                'navigation',
                'v8.execute',
            ].join(','),
            options: 'sampling-frequency=10000',
            transferMode: 'ReturnAsStream',
        });
    } catch (error) {
        rejectComplete(error);
        throw error;
    }

    return async () => {
        await cdp.send('Tracing.end');
        const payload = await completion;
        if (!payload.stream) {
            throw new Error('Chrome trace completed without a stream handle.');
        }

        const output = fs.createWriteStream(outputFile);
        const gzip = zlib.createGzip({ level: 6 });
        gzip.pipe(output);
        const finished = Promise.race([
            once(output, 'close'),
            once(output, 'error').then(([error]) => Promise.reject(error)),
            once(gzip, 'error').then(([error]) => Promise.reject(error)),
        ]);

        try {
            while (true) {
                const chunk = await cdp.send('IO.read', { handle: payload.stream, size: 65536 });
                if (chunk.data) {
                    const bytes = Buffer.from(chunk.data, chunk.base64Encoded ? 'base64' : 'utf8');
                    if (!gzip.write(bytes)) {
                        await once(gzip, 'drain');
                    }
                }
                if (chunk.eof) {
                    break;
                }
            }
        } finally {
            await cdp.send('IO.close', { handle: payload.stream }).catch(() => {});
            gzip.end();
        }

        await finished;
    };
}

function installPerformanceObservers() {
    window.__lunaraPerformance = {
        cls: 0,
        lcp: null,
    };

    if (!('PerformanceObserver' in window)) {
        return;
    }

    try {
        const lcpObserver = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                const element = entry.element || null;
                window.__lunaraPerformance.lcp = {
                    startTime: entry.startTime,
                    renderTime: entry.renderTime,
                    loadTime: entry.loadTime,
                    size: entry.size,
                    url: entry.url || (element && (element.currentSrc || element.src)) || '',
                    tagName: element ? element.tagName : '',
                    id: element ? element.id : '',
                    className: element && typeof element.className === 'string' ? element.className : '',
                    loading: element ? element.getAttribute('loading') : null,
                    fetchPriority: element ? (element.fetchPriority || element.getAttribute('fetchpriority')) : null,
                };
            }
        });
        lcpObserver.observe({ type: 'largest-contentful-paint', buffered: true });
    } catch (error) {
        window.__lunaraPerformance.lcpObserverError = String(error);
    }

    try {
        const clsObserver = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (!entry.hadRecentInput) {
                    window.__lunaraPerformance.cls += entry.value;
                }
            }
        });
        clsObserver.observe({ type: 'layout-shift', buffered: true });
    } catch (error) {
        window.__lunaraPerformance.clsObserverError = String(error);
    }
}

async function collectPageMetrics(page) {
    return page.evaluate(() => {
        const state = window.__lunaraPerformance || { cls: 0, lcp: null };
        const navigation = performance.getEntriesByType('navigation')[0] || null;
        const resources = performance.getEntriesByType('resource');
        const paints = Object.fromEntries(
            performance.getEntriesByType('paint').map((entry) => [entry.name, entry.startTime]),
        );
        const encoder = new TextEncoder();
        const lcpUrl = state.lcp && state.lcp.url ? new URL(state.lcp.url, document.baseURI).href : '';
        const lcpResourceEntry = lcpUrl
            ? resources.find((entry) => entry.name === lcpUrl)
            : null;
        const longTasks = performance.getEntriesByType('longtask');
        const themeUrls = [
            ...Array.from(document.querySelectorAll('link[href]'), (node) => node.href),
            ...Array.from(document.querySelectorAll('script[src]'), (node) => node.src),
            ...resources.map((entry) => entry.name),
        ].filter((url) => url.includes('/wp-content/themes/lunara-theme-blocks'));
        const themeAssetCacheKeys = Array.from(new Set(themeUrls.map((url) => {
            try {
                return new URL(url, document.baseURI).searchParams.get('ver');
            } catch (error) {
                return null;
            }
        }).filter(Boolean))).sort();
        const inlineStyleBytes = Array.from(document.querySelectorAll('style')).reduce(
            (total, node) => total + encoder.encode(node.outerHTML).length,
            0,
        );
        const documentWidth = Math.max(
            document.documentElement.scrollWidth,
            document.body ? document.body.scrollWidth : 0,
        );

        return {
            url: location.href,
            title: document.title,
            lcpMs: state.lcp ? state.lcp.startTime : null,
            lcp: state.lcp,
            cls: state.cls || 0,
            fcpMs: paints['first-contentful-paint'] || null,
            domContentLoadedMs: navigation ? navigation.domContentLoadedEventEnd : null,
            loadEventMs: navigation ? navigation.loadEventEnd : null,
            ttfbMs: navigation ? navigation.responseStart : null,
            htmlTransferBytes: navigation ? navigation.transferSize : null,
            htmlEncodedBytes: navigation ? navigation.encodedBodySize : null,
            htmlDecodedBytes: navigation ? navigation.decodedBodySize : null,
            renderedHtmlBytes: encoder.encode(document.documentElement.outerHTML).length,
            inlineStyleBytes,
            requestCount: resources.length + 1,
            resourceTransferBytes: resources.reduce((total, entry) => total + (entry.transferSize || 0), 0),
            approximateTbtMs: longTasks.reduce((total, entry) => total + Math.max(0, entry.duration - 50), 0),
            lcpResource: lcpResourceEntry ? {
                name: lcpResourceEntry.name,
                initiatorType: lcpResourceEntry.initiatorType,
                startTime: lcpResourceEntry.startTime,
                responseStart: lcpResourceEntry.responseStart,
                responseEnd: lcpResourceEntry.responseEnd,
                transferSize: lcpResourceEntry.transferSize,
                encodedBodySize: lcpResourceEntry.encodedBodySize,
                decodedBodySize: lcpResourceEntry.decodedBodySize,
            } : null,
            themeAssetCacheKeys,
            h1Count: document.querySelectorAll('h1').length,
            headerPresent: Boolean(document.querySelector('header, .site-header, #masthead')),
            brokenImages: Array.from(document.images).filter(
                (image) => image.complete && image.currentSrc && image.naturalWidth === 0,
            ).length,
            overflowPx: Math.max(0, documentWidth - window.innerWidth),
        };
    });
}

async function prepareStagingAccess(page, targetUrl) {
    await page.goto(targetUrl, { waitUntil: 'domcontentloaded', timeout: 90000 });
    await page.waitForFunction(() => {
        const title = document.title.trim().toLowerCase();
        const text = document.body ? document.body.innerText.toLowerCase() : '';
        return title !== 'checking your browser...'
            && !text.includes('checking your browser');
    }, { timeout: 30000 });
    await page.waitForLoadState('load', { timeout: 90000 });
    await page.goto('about:blank', { waitUntil: 'load' });
}

function shouldTrace(policy, pair, phase) {
    if (policy === 'none') {
        return false;
    }
    if (policy === 'all') {
        return true;
    }
    return policy === 'first' && pair === 1 && (phase === 'cold' || phase === 'warm');
}

async function navigateAndMeasure({
    page,
    cdp,
    arm,
    phase,
    pair,
    sequence,
    settleMs,
    tracePolicy,
    traceDirectory,
    consoleErrors,
    documentResponses,
}) {
    const traceFile = shouldTrace(tracePolicy, pair, phase)
        ? path.join(
            traceDirectory,
            `pair-${String(pair).padStart(2, '0')}-${safeName(arm.label)}-${phase}.json.gz`,
        )
        : null;
    let stopTrace = null;
    const consoleOffset = consoleErrors.length;
    const documentResponseOffset = documentResponses.length;

    if (traceFile) {
        stopTrace = await beginTrace(cdp, traceFile);
    }

    try {
        const response = phase === 'cold'
            ? await page.goto(arm.url, { waitUntil: 'load', timeout: 90000 })
            : await page.reload({ waitUntil: 'load', timeout: 90000 });

        await page.waitForTimeout(settleMs);
        const metrics = await collectPageMetrics(page);
        const measuredDocumentResponses = documentResponses.slice(documentResponseOffset);
        const finalResponse = measuredDocumentResponses.at(-1) || response;
        metrics.consoleErrors = consoleErrors.slice(consoleOffset);
        metrics.responseStatus = finalResponse ? finalResponse.status() : null;
        metrics.responseUrl = finalResponse ? finalResponse.url() : null;
        metrics.responseFromServiceWorker = finalResponse ? finalResponse.fromServiceWorker() : null;

        return {
            sequence,
            pair,
            arm: arm.key,
            label: arm.label,
            phase,
            url: arm.url,
            traceFile,
            metrics,
            error: null,
        };
    } catch (error) {
        return {
            sequence,
            pair,
            arm: arm.key,
            label: arm.label,
            phase,
            url: arm.url,
            traceFile,
            metrics: null,
            error: error && error.stack ? error.stack : String(error),
        };
    } finally {
        if (stopTrace) {
            await stopTrace();
        }
    }
}

async function runArm({ browser, arm, pair, sequenceStart, config, traceDirectory }) {
    const context = await browser.newContext({
        viewport: { width: config.viewportWidth, height: config.viewportHeight },
        screen: { width: config.viewportWidth, height: config.viewportHeight },
        deviceScaleFactor: 3,
        isMobile: true,
        hasTouch: true,
        locale: 'en-US',
        timezoneId: 'America/Chicago',
        serviceWorkers: 'block',
        userAgent: 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36',
    });
    await context.addInitScript(installPerformanceObservers);

    const page = await context.newPage();
    const consoleErrors = [];
    const documentResponses = [];
    page.on('console', (message) => {
        if (message.type() === 'error') {
            consoleErrors.push(message.text());
        }
    });
    page.on('pageerror', (error) => consoleErrors.push(String(error)));
    page.on('response', (response) => {
        if (response.request().resourceType() === 'document') {
            documentResponses.push(response);
        }
    });

    const cdp = await context.newCDPSession(page);
    await cdp.send('Network.enable');
    await prepareStagingAccess(page, arm.url);
    consoleErrors.length = 0;
    documentResponses.length = 0;
    await cdp.send('Network.setCacheDisabled', { cacheDisabled: false });
    await cdp.send('Network.clearBrowserCache');
    await cdp.send('Network.emulateNetworkConditions', {
        offline: false,
        latency: config.networkLatencyMs,
        downloadThroughput: config.downloadBytesPerSecond,
        uploadThroughput: config.uploadBytesPerSecond,
        connectionType: 'cellular4g',
    });
    await cdp.send('Emulation.setCPUThrottlingRate', { rate: config.cpuSlowdown });

    const records = [];
    records.push(await navigateAndMeasure({
        page,
        cdp,
        arm,
        phase: 'cold',
        pair,
        sequence: sequenceStart,
        settleMs: config.settleMs,
        tracePolicy: config.tracePolicy,
        traceDirectory,
        consoleErrors,
        documentResponses,
    }));

    if (config.includeWarm && !records[0].error) {
        records.push(await navigateAndMeasure({
            page,
            cdp,
            arm,
            phase: 'warm',
            pair,
            sequence: sequenceStart + 1,
            settleMs: config.settleMs,
            tracePolicy: config.tracePolicy,
            traceDirectory,
            consoleErrors,
            documentResponses,
        }));
    }

    await context.close();
    return records;
}

function formatCell(value, suffix = '') {
    return Number.isFinite(value) ? `${value}${suffix}` : 'n/a';
}

function buildMarkdown(summary, records) {
    const controlCold = summary.arms.control.cold;
    const candidateCold = summary.arms.candidate.cold;
    const lines = [
        '# Lunara Performance Measurement Gate',
        '',
        `Generated: ${summary.generatedAt}`,
        '',
        `Decision: **${summary.decision}**`,
        '',
        '## Fixed Conditions',
        '',
        `- Viewport: ${summary.config.viewportWidth} x ${summary.config.viewportHeight}, mobile, DPR 3.`,
        `- Network: Fast 4G profile (${summary.config.downloadMbps} Mbps down, ${summary.config.uploadMbps} Mbps up, ${summary.config.networkLatencyMs} ms latency).`,
        `- CPU: ${summary.config.cpuSlowdown}x slowdown.`,
        `- Cold definition: fresh browser context, completed anonymous staging access challenge, then cleared browser cache; WordPress.com CDN/server caches are unchanged.`,
        `- Pair order: AB on odd pairs, BA on even pairs.`,
        `- Runs: ${summary.config.runs} cold pairs${summary.config.includeWarm ? ', each followed by an unthrottled-cache warm reload under the same network/CPU profile' : ''}.`,
        '',
        '## Arm Summary',
        '',
        '| Arm | Phase | Valid | Median LCP | LCP CV | Median CLS | HTML decoded | Inline styles | LCP request start |',
        '|---|---:|---:|---:|---:|---:|---:|---:|---:|',
    ];

    for (const armKey of ['control', 'candidate']) {
        for (const phase of ['cold', 'warm']) {
            const item = summary.arms[armKey][phase];
            if (!item) {
                continue;
            }
            lines.push(
                `| ${summary.labels[armKey]} | ${phase} | ${item.validLcpCount}/${summary.config.runs} | ${formatCell(item.medianLcpMs, ' ms')} | ${formatCell(item.lcpCvPct, '%')} | ${formatCell(item.medianCls)} | ${formatCell(item.medianHtmlDecodedBytes, ' B')} | ${formatCell(item.medianInlineStyleBytes, ' B')} | ${formatCell(item.medianLcpRequestStartMs, ' ms')} |`,
            );
        }
    }

    lines.push(
        '',
        '## Gate',
        '',
        `- Measurement stable: ${summary.gate.measurementStable ? 'yes' : 'no'}.`,
        `- Maximum allowed per-arm cold LCP CV: ${summary.config.maxCvPct}%.`,
        `- Median absolute paired A/A delta: ${formatCell(summary.gate.medianAbsolutePairedDeltaPct, '%')} (limit ${summary.config.maxPairedNoisePct}%).`,
        `- Candidate median cold LCP delta: ${formatCell(summary.gate.candidateLcpDeltaPct, '%')} (regression limit 10%).`,
        `- Candidate median CLS limit: ${formatCell(summary.gate.candidateClsLimit)}; observed ${formatCell(candidateCold.medianCls)}.`,
        `- Home HTML delta: ${formatCell(summary.gate.htmlDeltaBytes, ' B')}.`,
        `- Inline-style delta: ${formatCell(summary.gate.inlineStyleDeltaBytes, ' B')}.`,
        '',
        '## Run Detail',
        '',
        '| Seq | Pair | Arm | Phase | LCP | CLS | HTML | Inline CSS | Status | Trace |',
        '|---:|---:|---|---|---:|---:|---:|---:|---:|---|',
    );

    for (const record of records) {
        const metrics = record.metrics || {};
        lines.push(
            `| ${record.sequence} | ${record.pair} | ${record.label} | ${record.phase} | ${formatCell(metrics.lcpMs, ' ms')} | ${formatCell(metrics.cls)} | ${formatCell(metrics.htmlDecodedBytes, ' B')} | ${formatCell(metrics.inlineStyleBytes, ' B')} | ${metrics.responseStatus || (record.error ? 'ERROR' : 'n/a')} | ${record.traceFile ? path.basename(record.traceFile) : ''} |`,
        );
    }

    const errors = records.filter((record) => record.error);
    if (errors.length) {
        lines.push('', '## Errors', '');
        for (const record of errors) {
            lines.push(`- Pair ${record.pair}, ${record.label}, ${record.phase}: ${record.error.split('\n')[0]}`);
        }
    }

    lines.push(
        '',
        '## Interpretation',
        '',
        summary.notes.join('\n'),
        '',
        `Control cold range: ${formatCell(controlCold.minLcpMs, ' ms')} to ${formatCell(controlCold.maxLcpMs, ' ms')}. Candidate cold range: ${formatCell(candidateCold.minLcpMs, ' ms')} to ${formatCell(candidateCold.maxLcpMs, ' ms')}.`,
        '',
    );
    return lines.join('\n');
}

async function main() {
    const args = parseArguments(process.argv.slice(2));
    const controlUrl = normalizeUrl(requireArgument(args, 'controlUrl'));
    const candidateUrl = normalizeUrl(args.candidateUrl && args.candidateUrl !== true ? args.candidateUrl : controlUrl);
    const outputDirectory = path.resolve(requireArgument(args, 'outputDirectory'));
    const tracePolicy = String(args.tracePolicy || 'first');
    if (!['none', 'first', 'all'].includes(tracePolicy)) {
        throw new Error('--tracePolicy must be none, first, or all.');
    }

    const config = {
        runs: Math.max(1, Math.trunc(readNumber(args, 'runs', 5))),
        viewportWidth: Math.trunc(readNumber(args, 'viewportWidth', 390)),
        viewportHeight: Math.trunc(readNumber(args, 'viewportHeight', 844)),
        cpuSlowdown: readNumber(args, 'cpuSlowdown', 4),
        networkLatencyMs: readNumber(args, 'networkLatencyMs', 20),
        downloadMbps: readNumber(args, 'downloadMbps', 4),
        uploadMbps: readNumber(args, 'uploadMbps', 3),
        settleMs: Math.trunc(readNumber(args, 'settleMs', 4000)),
        maxCvPct: readNumber(args, 'maxCvPct', 10),
        maxPairedNoisePct: readNumber(args, 'maxPairedNoisePct', 10),
        includeWarm: args.skipWarm !== true,
        tracePolicy,
    };
    config.downloadBytesPerSecond = config.downloadMbps * 1024 * 1024 / 8;
    config.uploadBytesPerSecond = config.uploadMbps * 1024 * 1024 / 8;

    const arms = {
        control: {
            key: 'control',
            label: String(args.controlLabel || 'control'),
            url: controlUrl,
            expectedVersion: args.expectedControlVersion && args.expectedControlVersion !== true
                ? String(args.expectedControlVersion)
                : null,
        },
        candidate: {
            key: 'candidate',
            label: String(args.candidateLabel || 'candidate'),
            url: candidateUrl,
            expectedVersion: args.expectedCandidateVersion && args.expectedCandidateVersion !== true
                ? String(args.expectedCandidateVersion)
                : null,
        },
    };

    const identityByUrl = new Map();
    for (const armKey of ['control', 'candidate']) {
        const arm = arms[armKey];
        if (!identityByUrl.has(arm.url)) {
            identityByUrl.set(arm.url, await probeThemeIdentity(arm.url));
        }
        arm.identity = identityByUrl.get(arm.url);
        if (arm.expectedVersion && arm.identity.version !== arm.expectedVersion) {
            throw new Error(
                `${arm.label} expected theme ${arm.expectedVersion}, observed ${arm.identity.version || 'no Version header'} at ${arm.identity.styleUrl}.`,
            );
        }
    }

    fs.mkdirSync(outputDirectory, { recursive: true });
    const traceDirectory = path.join(outputDirectory, 'traces');
    fs.mkdirSync(traceDirectory, { recursive: true });

    const browser = await chromium.launch({ headless: true });
    const records = [];
    let sequence = 1;
    try {
        for (let pair = 1; pair <= config.runs; pair += 1) {
            const order = pair % 2 === 1
                ? ['control', 'candidate']
                : ['candidate', 'control'];
            for (const armKey of order) {
                const arm = arms[armKey];
                process.stdout.write(`Pair ${pair}/${config.runs}: ${arm.label} cold${config.includeWarm ? ' + warm' : ''}\n`);
                const armRecords = await runArm({
                    browser,
                    arm,
                    pair,
                    sequenceStart: sequence,
                    config,
                    traceDirectory,
                });
                records.push(...armRecords);
                sequence += armRecords.length;
            }
        }
    } finally {
        await browser.close();
    }

    const select = (arm, phase) => records.filter(
        (record) => record.arm === arm && record.phase === phase && !record.error && record.metrics,
    );
    const armSummary = {
        control: {
            cold: summarizeRuns(select('control', 'cold')),
            warm: config.includeWarm ? summarizeRuns(select('control', 'warm')) : null,
        },
        candidate: {
            cold: summarizeRuns(select('candidate', 'cold')),
            warm: config.includeWarm ? summarizeRuns(select('candidate', 'warm')) : null,
        },
    };

    const pairedDeltas = [];
    for (let pair = 1; pair <= config.runs; pair += 1) {
        const control = select('control', 'cold').find((record) => record.pair === pair);
        const candidate = select('candidate', 'cold').find((record) => record.pair === pair);
        if (control && candidate && control.metrics.lcpMs > 0) {
            pairedDeltas.push(((candidate.metrics.lcpMs - control.metrics.lcpMs) / control.metrics.lcpMs) * 100);
        }
    }

    const controlCold = armSummary.control.cold;
    const candidateCold = armSummary.candidate.cold;
    const sameTarget = controlUrl === candidateUrl;
    const sameOrigin = new URL(controlUrl).origin === new URL(candidateUrl).origin;
    const enoughRuns = controlCold.validLcpCount === config.runs
        && candidateCold.validLcpCount === config.runs;
    const cvStable = enoughRuns
        && Number.isFinite(controlCold.lcpCvPct)
        && Number.isFinite(candidateCold.lcpCvPct)
        && controlCold.lcpCvPct <= config.maxCvPct
        && candidateCold.lcpCvPct <= config.maxCvPct;
    const medianAbsolutePairedDeltaPct = median(pairedDeltas.map(Math.abs));
    const pairedNoiseStable = !sameTarget
        || (Number.isFinite(medianAbsolutePairedDeltaPct)
            && medianAbsolutePairedDeltaPct <= config.maxPairedNoisePct);
    const measurementStable = cvStable && pairedNoiseStable;
    const candidateLcpDeltaPct = Number.isFinite(controlCold.medianLcpMs)
        && controlCold.medianLcpMs > 0
        && Number.isFinite(candidateCold.medianLcpMs)
        ? ((candidateCold.medianLcpMs - controlCold.medianLcpMs) / controlCold.medianLcpMs) * 100
        : null;
    const candidateClsLimit = Number.isFinite(controlCold.medianCls)
        ? Math.max(0.005, controlCold.medianCls * 1.1)
        : null;
    const lcpGatePassed = Number.isFinite(candidateLcpDeltaPct) && candidateLcpDeltaPct <= 10;
    const clsGatePassed = Number.isFinite(candidateCold.medianCls)
        && Number.isFinite(candidateClsLimit)
        && candidateCold.medianCls <= candidateClsLimit;
    const candidateGatePassed = measurementStable && lcpGatePassed && clsGatePassed;
    const htmlDeltaBytes = Number.isFinite(controlCold.medianHtmlDecodedBytes)
        && Number.isFinite(candidateCold.medianHtmlDecodedBytes)
        ? candidateCold.medianHtmlDecodedBytes - controlCold.medianHtmlDecodedBytes
        : null;
    const inlineStyleDeltaBytes = Number.isFinite(controlCold.medianInlineStyleBytes)
        && Number.isFinite(candidateCold.medianInlineStyleBytes)
        ? candidateCold.medianInlineStyleBytes - controlCold.medianInlineStyleBytes
        : null;

    let decision;
    const notes = [];
    if (sameTarget) {
        decision = measurementStable ? 'BASELINE_STABLE' : 'BASELINE_NOISY';
        notes.push('- This is an A/A baseline run against one unchanged target. It validates measurement noise; it does not evaluate a code candidate.');
    } else if (!sameOrigin) {
        decision = 'INCONCLUSIVE_DIFFERENT_ORIGIN';
        notes.push('- The two arms use different origins. Do not treat this as a release gate unless the environments are proven infrastructure-identical.');
    } else if (!measurementStable) {
        decision = 'MEASUREMENT_NOISY';
        notes.push('- The cold-run variance exceeds the protocol limit. Repeat the measurement before judging code.');
    } else {
        decision = candidateGatePassed ? 'CANDIDATE_PASS' : 'CANDIDATE_FAIL';
        notes.push(`- Candidate gate requires median cold LCP regression <= 10% and median CLS <= ${round(candidateClsLimit, 5)}.`);
    }
    notes.push('- Traces are diagnostic samples; the release decision is based on five paired measurements and their variance, not the best single run.');

    const summary = {
        generatedAt: new Date().toISOString(),
        decision,
        labels: {
            control: arms.control.label,
            candidate: arms.candidate.label,
        },
        targets: {
            control: controlUrl,
            candidate: candidateUrl,
            sameTarget,
            sameOrigin,
        },
        identities: {
            control: arms.control.identity,
            candidate: arms.candidate.identity,
        },
        config,
        arms: armSummary,
        pairedColdLcpDeltasPct: pairedDeltas.map((value) => round(value)),
        gate: {
            enoughRuns,
            cvStable,
            pairedNoiseStable,
            measurementStable,
            medianAbsolutePairedDeltaPct: round(medianAbsolutePairedDeltaPct),
            candidateLcpDeltaPct: round(candidateLcpDeltaPct),
            candidateClsLimit: round(candidateClsLimit, 5),
            lcpGatePassed,
            clsGatePassed,
            candidateGatePassed,
            htmlDeltaBytes: round(htmlDeltaBytes, 0),
            inlineStyleDeltaBytes: round(inlineStyleDeltaBytes, 0),
        },
        notes,
    };

    fs.writeFileSync(path.join(outputDirectory, 'runs.json'), `${JSON.stringify(records, null, 2)}\n`);
    fs.writeFileSync(path.join(outputDirectory, 'summary.json'), `${JSON.stringify(summary, null, 2)}\n`);
    fs.writeFileSync(path.join(outputDirectory, 'REPORT.md'), buildMarkdown(summary, records));

    process.stdout.write(`Decision: ${decision}\n`);
    process.stdout.write(`Report: ${path.join(outputDirectory, 'REPORT.md')}\n`);

    if (decision === 'BASELINE_NOISY' || decision === 'MEASUREMENT_NOISY') {
        process.exitCode = 2;
    } else if (decision === 'CANDIDATE_FAIL') {
        process.exitCode = 3;
    } else if (decision === 'INCONCLUSIVE_DIFFERENT_ORIGIN') {
        process.exitCode = 4;
    }
}

main().catch((error) => {
    process.stderr.write(`${error && error.stack ? error.stack : error}\n`);
    process.exitCode = 1;
});
