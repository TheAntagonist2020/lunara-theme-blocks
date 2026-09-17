'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const test = require('node:test');
const postcss = require('postcss');
const { buildShell, checkArtifact, onlyPortalSelectors, sourcePath, outputPath } = require('./tools/build-shell-css.cjs');

test('only positive body anchors in every selector branch are removable', () => {
    for (const selector of [
        'body.lunara-oscars-portal-page .card',
        'body.other.lunara-oscars-portal-page:hover .card, body.lunara-oscars-portal-page :is(.a,.b)',
        'body/**/.lunara-oscars-portal-page [data-label="a,b"]',
    ]) assert.equal(onlyPortalSelectors(selector), true, selector);
    for (const selector of [
        'body.lunara-oscars-portal-page .card, .shared',
        'body.lunara-oscars-portal-page .card, body.home .card',
        'body:not(.lunara-oscars-portal-page) .card',
        'body:is(.lunara-oscars-portal-page, .home) .card',
        'body:has(.lunara-oscars-portal-page) .card',
        'body .lunara-oscars-portal-page .card',
        'body[data-page=".lunara-oscars-portal-page"] .card',
        '.lunara-oscars-portal-page .card',
        'svg|body.lunara-oscars-portal-page .card',
    ]) assert.equal(onlyPortalSelectors(selector), false, selector);
});

test('splicing preserves CSS and order while cleaning only empty-line whitespace', () => {
    const remove = 'body.lunara-oscars-portal-page :is(.one,.two) { color: red; }';
    const source = '/* before */\r\n.shared { content: "},x{"; }\r\n' +
        '@media (max-width: 900px) {\r\n  /* keep */ ' + remove + '\r\n' +
        '  body.lunara-oscars-portal-page .a, body.home .a { color: blue; }\r\n}\r\n' +
        '@supports (display: grid) {\n\t  ' + remove + '\n}\n' +
        '@keyframes turn { from { opacity: 0; } to { opacity: 1; } }\n' +
        '.shared { color: gold; }\n\n   \n\t\n';
    const built = buildShell(source);
    assert.equal(built.ranges.length, 2);
    const expected = '/* before */\r\n.shared { content: "},x{"; }\r\n' +
        '@media (max-width: 900px) {\r\n  /* keep */ \r\n' +
        '  body.lunara-oscars-portal-page .a, body.home .a { color: blue; }\r\n}\r\n' +
        '@supports (display: grid) {\n\n}\n' +
        '@keyframes turn { from { opacity: 0; } to { opacity: 1; } }\n' +
        '.shared { color: gold; }\n';
    assert.equal(built.retained, expected);
    assert.doesNotThrow(() => postcss.parse(built.css));
});

test('unfamiliar at-rules and CSS nesting remain untouched', () => {
    const source = '@unknown { body.lunara-oscars-portal-page .a { color: red; } }\n' +
        '.shared { body.lunara-oscars-portal-page & { color: red; } }';
    assert.equal(buildShell(source).retained, source);
});

test('stale output and a source-only portal change both fail the artifact check', () => {
    const source = '.shared{color:gold}body.lunara-oscars-portal-page{color:red}';
    const output = buildShell(source).css;
    assert.doesNotThrow(() => checkArtifact(source, output));
    assert.throws(() => checkArtifact(source.replace('red', 'blue'), output), /Stale/);
    assert.throws(() => checkArtifact(source, output + '\n'), /Stale/);
});

test('committed artifact matches canonical shell and saves more than 100KB', () => {
    const source = fs.readFileSync(sourcePath, 'utf8');
    const output = fs.readFileSync(outputPath, 'utf8');
    checkArtifact(source, output);
    assert.ok(Buffer.byteLength(source) - Buffer.byteLength(output) > 100000);
    assert.doesNotThrow(() => postcss.parse(output));
});
