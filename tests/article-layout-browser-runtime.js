'use strict';
/* Real Review/Journal template geometry; JavaScript is disabled throughout. */
const fs = require('fs'), path = require('path'), { execFileSync } = require('child_process');
const { chromium } = require('playwright-core');
const executablePath = process.env.LUNARA_BROWSER_EXECUTABLE || ['C:/Program Files/Google/Chrome/Application/chrome.exe', 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', '/usr/bin/chromium', '/usr/bin/chromium-browser', '/usr/bin/google-chrome'].find(fs.existsSync);
const root = path.resolve(__dirname, '..');
const fixtures = JSON.parse(execFileSync('php', [path.join(__dirname, 'article-layout-runtime.php')], { encoding: 'utf8' }));
const sharedCss = ['style.css', 'assets/css/lunara-shell.css', 'assets/css/lunara-review-components.css', 'assets/css/lunara-public-guardrails.css'].map(file => fs.readFileSync(path.join(root, file), 'utf8')).join('\n');
const journalCss = fs.readFileSync(path.join(root, 'assets/css/lunara-journal-single.css'), 'utf8');
const reviewCss = fs.readFileSync(path.join(root, 'assets/css/lunara-review-single.css'), 'utf8');
let assertions = 0;
const assert = (condition, message, value) => { assertions++; if (!condition) throw new Error(message + (value ? '\n' + JSON.stringify(value, null, 2) : '')); };
const metrics = [];
(async () => {
    const browser = await chromium.launch({ headless: true, executablePath });
    try {
        for (const width of [320, 390, 768, 1440]) {
            for (const [name, fixture] of Object.entries(fixtures)) {
                const page = await browser.newPage({ viewport: { width, height: 1000 }, javaScriptEnabled: false });
                let journalStyleRequests = 0;
                await page.route('**/*', route => {
                    if (route.request().url().includes('/assets/css/lunara-journal-single.css?ver=')) { journalStyleRequests++; return route.fulfill({ contentType: 'text/css', body: journalCss }); }
                    const portrait = route.request().url().endsWith('/poster.svg');
                    const hardCropped = route.request().url().endsWith('/hard-cropped.svg');
                    const w = portrait ? 1000 : 1920, h = portrait ? 1500 : 1080;
                    return route.fulfill({ contentType: 'image/svg+xml', body: `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}"><defs><linearGradient id="sky"><stop stop-color="${hardCropped ? '#9f3355' : '#8ca8bb'}"/><stop offset="1" stop-color="#173342"/></linearGradient></defs><rect width="${w}" height="${h}" fill="url(#sky)"/><rect x="${w * .06}" y="${h * .1}" width="${w * .22}" height="${h * .72}" fill="#dac49a"/><rect x="${w * .64}" y="${h * .26}" width="${w * .26}" height="${h * .7}" fill="#091824"/><path d="M0 ${h * .83}L${w} ${h * .83}" stroke="#c9a961" stroke-width="12"/></svg>` });
                });
                // Blocksy supplies border-box and the outer main shell. Header
                // behavior has its own fixture; this neutral stand-in is68px.
                await page.setContent(`<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>*,*::before,*::after{box-sizing:border-box}body{margin:0}.fixture-header{height:68px;padding:20px 16px;background:#09151e;color:#dbb761;font:20px Georgia}</style><style>${sharedCss}</style>${fixture.type === 'review' ? '<style>' + reviewCss + '</style>' : ''}${fixture.authority}</head><body class="single single-${fixture.type}"><div id="main-container"><header class="fixture-header">Lunara Film</header><main id="main">${fixture.html}</main></div></body></html>`, { waitUntil: 'load' });
                const data = await page.evaluate(() => {
                    const rect = node => { if (!node) return null; const r = node.getBoundingClientRect(); return { left: r.left, right: r.right, top: r.top, bottom: r.bottom, width: r.width, height: r.height }; };
                    const title = document.querySelector('h1'), content = document.querySelector('.lunara-review-single-content');
                    const hero = document.querySelector('.lunara-journal-cinematic-hero-frame,.lunara-review-single-cinematic-hero');
                    const img = hero && hero.querySelector('img');
                    const reading = [...content.querySelectorAll('h2,p')];
                    const sections=[...document.querySelector('article.lunara-review-single').children].filter(node=>node.getBoundingClientRect().height>0).map(rect);
                    const sectionGaps=sections.slice(1).map((box,index)=>box.top-sections[index].bottom);
                    const clipping = [title, ...reading].flatMap(node => {
                        const bounds = rect(node); const range = document.createRange(); range.selectNodeContents(node);
                        return [...range.getClientRects()].filter(r => r.left < bounds.left - 1 || r.right > bounds.right + 1 || (['hidden','clip'].includes(getComputedStyle(node).overflowY) && r.bottom > bounds.bottom + 1)).map(r => ({ text: node.textContent.slice(0, 80), range: { left: r.left, right: r.right, bottom: r.bottom }, bounds }));
                    });
                    return { document: document.documentElement.scrollWidth, viewport: innerWidth, h1Count: document.querySelectorAll('h1').length, title: rect(title), titleText: title.textContent, titleSize: parseFloat(getComputedStyle(title).fontSize), metadata: document.querySelector('.lunara-review-single-meta').textContent, duplicateMetaDots: [...document.querySelectorAll('.lunara-review-single-meta > span')].some(node=>!['none','normal'].includes(getComputedStyle(node,'::after').content)), markers: [...document.querySelectorAll('[data-lunara-site-studio-section]')].map(node=>node.dataset.lunaraSiteStudioSection), galleryControls: [...document.querySelectorAll('.lunara-journal-carousel-btn')].map(rect), content: rect(content), firstParagraph: rect(content.querySelector('p')), paragraphSize: parseFloat(getComputedStyle(content.querySelector('p')).fontSize), hero: rect(hero), image: rect(img), imageFit: img ? getComputedStyle(img).objectFit : '', imagePosition: img ? getComputedStyle(img).objectPosition : '', imageSource: img ? {size:img.dataset.fixtureImageSize,src:img.currentSrc,srcset:img.getAttribute('srcset'),sizes:img.getAttribute('sizes'),loading:img.loading,priority:img.getAttribute('fetchpriority')} : null, natural: img ? { width: img.naturalWidth, height: img.naturalHeight } : null, clipping, sectionGaps, bodyText: content.textContent, linkCount: content.querySelectorAll('a[href]').length };
                });
                const label = `${name}@${width}`;
                assert(journalStyleRequests === (fixture.type === 'journal' ? 1 : 0), `${label}: route loads exactly one versioned Journal stylesheet before layout checks`, journalStyleRequests);
                if (fixture.type === 'journal') assert(fixture.authority.length < 850 && !fixture.authority.includes('!important') && fixture.authority.indexOf('<link ') < fixture.authority.indexOf('<style '), `${label}: HTML carries only the synchronous link and private presentation variables`, fixture.authority);
                assert(data.document <= width + 1, `${label}: no document overflow`, data);
                assert(data.h1Count === 1 && data.titleText === fixture.title, `${label}: one complete server-rendered article heading`, data);
                assert(data.clipping.length === 0, `${label}: full headlines, paragraphs and long links remain readable`, data.clipping);
                assert(data.bodyText.includes('The ending rewards another look.') && data.linkCount > 0, `${label}: complete article and navigation survive without JavaScript`);
                assert(data.paragraphSize >= 16, `${label}: phone body text stays at least16px`, data);
                assert(!data.duplicateMetaDots, `${label}: metadata uses one separator between items`, data);
                if (width <= 640) {
                    assert(data.content.width >= width - 48 && data.content.left >= 12 && data.content.right <= width - 12, `${label}: consistent useful reading width and safe phone gutters`, data);
                    assert(data.title.width >= width - 48, `${label}: heading uses available phone width`, data);
                    assert(data.sectionGaps.every(gap=>gap>=-1&&gap<=40), `${label}: phone sections have one spacing rhythm without stacked margins`, data);
                }
                if (fixture.art === 'missing') {
                    assert(!data.hero && !data.image, `${label}: missing artwork leaves no empty media frame`, data);
                } else {
                    assert(data.natural.width > 0 && data.image.width > 0 && data.image.left >= -1 && data.image.right <= width + 1, `${label}: artwork loads within the viewport`, data);
                    if (fixture.type === 'journal') {
                        assert(data.imageSource.size === 'full' && !data.imageSource.src.includes('hard-cropped') && !!data.imageSource.srcset && data.imageSource.sizes.includes('1080px') && data.imageSource.loading === 'eager' && data.imageSource.priority === 'high', `${label}: public framing uses the complete responsive source and keeps priority loading`, data.imageSource);
                        if (fixture.art === 'poster') assert(Math.abs(data.natural.width / data.natural.height - 2 / 3) < .01, `${label}: full-fit and focal controls never receive the hard-cropped derivative`, data);
                        assert(Math.abs(data.hero.width / data.hero.height - 16 / 9) < .015 && data.imagePosition === `${fixture.mods.lunara_journal_single_image_position_x ?? 50}% ${fixture.mods.lunara_journal_single_image_position_y ?? 50}%` && data.imageFit === (fixture.mods.lunara_journal_single_image_fit || 'cover'), `${label}: landscape hero keeps its frame and neutral focal point`, data);
                    } else {
                        assert(Math.abs(data.image.width / data.image.height - data.natural.width / data.natural.height) < .02, `${label}: Review artwork keeps its complete native proportion`, data);
                    }
                    if (fixture.scenario === 'normal' && width <= 640) assert(data.hero.top + Math.min(data.hero.height, 160) <= 844, `${label}: the first phone viewport includes useful artwork below the opening`, data);
                }
                if(fixture.type==='journal') {
                    assert(data.markers.includes('hero')&&data.markers.includes('article'), `${label}: real article sections expose shared preview markers`, data);
                    if(data.galleryControls.length) assert(data.markers.includes('gallery')&&data.galleryControls.every(box=>box.width>=44&&box.height>=44), `${label}: gallery controls have reachable44px targets and a real preview marker`, data);
                    if(fixture.private) assert(fixture.public_unchanged&&data.metadata.includes('6 min read')&&!data.metadata.includes('September 13')&&data.metadata.includes('Dalton Johnson')===fixture.mods.lunara_journal_show_byline, `${label}: private provider filters reach actual metadata without public writes`, data);
                }
                if (process.env.LUNARA_ARTICLE_ARTIFACT_DIR && ((fixture.scenario === 'normal' && [390, 1440].includes(width)) || (width === 390 && fixture.scenario !== 'normal'))) {
                    fs.mkdirSync(process.env.LUNARA_ARTICLE_ARTIFACT_DIR, { recursive: true });
                    await page.screenshot({ path: path.join(process.env.LUNARA_ARTICLE_ARTIFACT_DIR, label.replace('@', '-') + '.png'), fullPage: true });
                }
                metrics.push({ case: label, contentWidth: data.content.width, titleHeight: data.title.height, titleSize: data.titleSize, hero: data.hero, paragraphSize: data.paragraphSize });
                await page.close();
            }
        }
        for(const width of [320,390,768,1440]) {
            const normal=metrics.find(row=>row.case===`journal-normal@${width}`),small=metrics.find(row=>row.case===`journal-framed-small@${width}`),large=metrics.find(row=>row.case===`journal-framed-large@${width}`);
            assert(small.titleSize<=normal.titleSize&&normal.titleSize<large.titleSize, `Journal headline size affects actual private rendering at${width}px while preserving a readable phone floor`, {small,normal,large});
        }
        if (process.env.LUNARA_ARTICLE_ARTIFACT_DIR) fs.writeFileSync(path.join(process.env.LUNARA_ARTICLE_ARTIFACT_DIR, 'article-layout-metrics.json'), JSON.stringify(metrics, null, 2));
        process.stdout.write(`Article layout browser: ${metrics.length} actual-template scenarios, ${assertions} assertions passed.\n`);
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exit(1); });
