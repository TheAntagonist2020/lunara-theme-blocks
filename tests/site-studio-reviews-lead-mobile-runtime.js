'use strict';

const fs = require('fs');
const path = require('path');
const puppeteer = require('puppeteer');

const themeRoot = path.resolve(__dirname, '..');
const adminCss = fs.readFileSync(path.join(themeRoot, 'assets/css/lunara-control-desk.css'), 'utf8');
const controlDesk = fs.readFileSync(path.join(themeRoot, 'inc/control-desk.php'), 'utf8');

function fail(message, metrics) {
    if (metrics) {
        process.stderr.write(`${JSON.stringify(metrics, null, 2)}\n`);
    }
    throw new Error(message);
}

(async () => {
    const browser = await puppeteer.launch({ headless: true });
    try {
        const viewportMetrics = [];

        for (const viewportWidth of [390, 375]) {
            const page = await browser.newPage();
            await page.setViewport({ width: viewportWidth, height: 844, deviceScaleFactor: 1 });
            await page.setContent(`<!doctype html>
            <html>
                <head>
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <style>
                        * { box-sizing: content-box; }
                        html, body { margin: 0; min-height: 100%; padding: 0; }
                        body { font-family: Arial, sans-serif; }
                        select { font-size: 14px; max-width: 400px; width: 400px; }
                        .lunara-site-studio { margin: 0 18px; width: calc(100% - 36px); }
                        .lunara-site-studio-workspace { width: 100%; }
                    </style>
                    <style>${adminCss}</style>
                </head>
                <body class="wp-admin">
                    <div class="wrap lunara-control-desk lunara-site-studio">
                        <div class="lunara-site-studio-workspace">
                            <section id="lunara-theme-studio-reviews-archive-studio" class="lunara-control-desk-homepage-studio">
                                <form class="lunara-control-desk-homepage-form">
                                    <div class="lunara-control-desk-homepage-grid">
                                        <div class="lunara-control-desk-homepage-card">
                                            <label class="lunara-control-desk-homepage-field lunara-control-desk-reviews-lead-field">
                                                <strong>Featured Review</strong>
                                                <select name="lunara_reviews_archive_lead_id">
                                                    <option>Automatic — newest release</option>
                                                    <option>The Longest Possible Review Title — August 15, 2026</option>
                                                </select>
                                            </label>
                                        </div>
                                    </div>
                                </form>
                            </section>
                        </div>
                    </div>
                </body>
            </html>`);

            const metrics = await page.evaluate(() => {
                const workspace = document.querySelector('.lunara-site-studio-workspace');
                const card = document.querySelector('.lunara-control-desk-homepage-card');
                const label = document.querySelector('.lunara-control-desk-reviews-lead-field');
                const select = document.querySelector('select[name="lunara_reviews_archive_lead_id"]');
                const cardStyle = getComputedStyle(card);
                const labelStyle = getComputedStyle(label);
                const selectStyle = getComputedStyle(select);
                const cardContentWidth = card.getBoundingClientRect().width
                    - parseFloat(cardStyle.paddingLeft)
                    - parseFloat(cardStyle.paddingRight)
                    - parseFloat(cardStyle.borderLeftWidth)
                    - parseFloat(cardStyle.borderRightWidth);

                return {
                    documentClientWidth: document.documentElement.clientWidth,
                    documentScrollWidth: document.documentElement.scrollWidth,
                    workspaceClientWidth: workspace.clientWidth,
                    workspaceScrollWidth: workspace.scrollWidth,
                    cardContentWidth,
                    labelWidth: label.getBoundingClientRect().width,
                    selectWidth: select.getBoundingClientRect().width,
                    labelBoxSizing: labelStyle.boxSizing,
                    labelMinWidth: labelStyle.minWidth,
                    labelOverflowX: labelStyle.overflowX,
                    selectBoxSizing: selectStyle.boxSizing,
                    selectMinWidth: selectStyle.minWidth,
                    selectMaxWidth: selectStyle.maxWidth,
                    selectOverflowX: selectStyle.overflowX,
                };
            });

            if (metrics.documentScrollWidth > metrics.documentClientWidth) {
                fail(`The ${viewportWidth}px Site Studio document still scrolls horizontally.`, metrics);
            }
            if (metrics.workspaceScrollWidth > metrics.workspaceClientWidth) {
                fail(`The Reviews Archive workspace still overflows at ${viewportWidth}px.`, metrics);
            }
            if (metrics.selectWidth > metrics.cardContentWidth + 0.5) {
                fail(`The Reviews lead selector is wider than its card at ${viewportWidth}px.`, metrics);
            }
            if (metrics.labelWidth > metrics.cardContentWidth + 0.5) {
                fail(`The Reviews lead label is wider than its card at ${viewportWidth}px.`, metrics);
            }
            if ('border-box' !== metrics.selectBoxSizing || '0px' !== metrics.selectMinWidth || '100%' !== metrics.selectMaxWidth) {
                fail('The Reviews lead selector is missing its shrink-safe box contract.', metrics);
            }
            if ('border-box' !== metrics.labelBoxSizing || '0px' !== metrics.labelMinWidth) {
                fail('The Reviews lead label is missing its shrink-safe box contract.', metrics);
            }
            if ('hidden' === metrics.labelOverflowX || 'hidden' === metrics.selectOverflowX) {
                fail('The mobile repair must not hide overflow.', metrics);
            }

            viewportMetrics.push({ viewportWidth, ...metrics });
            await page.close();
        }

        if (!/class="lunara-control-desk-homepage-field lunara-control-desk-reviews-lead-field"[\s\S]{0,300}<select name="lunara_reviews_archive_lead_id">/.test(controlDesk)) {
            fail('The production Reviews lead label is missing its narrow scoped class.', viewportMetrics);
        }

        process.stdout.write(`Site Studio Reviews lead mobile runtime passed: ${JSON.stringify(viewportMetrics)}\n`);
    } finally {
        await browser.close();
    }
})().catch((error) => {
    process.stderr.write(`${error.stack || error.message}\n`);
    process.exit(1);
});
