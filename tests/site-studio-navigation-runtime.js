'use strict';

const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');
let chromium;
try { ({ chromium } = require('playwright')); } catch (error) { ({ chromium } = require('playwright-core')); }
const { controller } = require('./site-studio-browser-fixture');
const themeRoot = path.resolve(__dirname, '..');
const css = fs.readFileSync(path.join(themeRoot, 'assets/css/lunara-site-studio.css'), 'utf8');
const editorCss = fs.readFileSync(path.join(themeRoot, 'assets/css/lunara-editor-controls.css'), 'utf8');
const editorControls = fs.readFileSync(path.join(themeRoot, 'assets/js/lunara-editor-controls.js'), 'utf8');
const archiveMediaEditor = fs.readFileSync(path.join(themeRoot, 'assets/js/lunara-site-studio-archive-media.js'), 'utf8');
const archiveEditor = fs.readFileSync(path.join(themeRoot, 'assets/js/lunara-site-studio-archive-selection.js'), 'utf8');
const mainPages = { home: 'homepage-structure', reviews: 'reviews-archive', journal: 'journal-archive', oscars: 'oscars-portal' };
const contextPages = {
 home: ['homepage-structure', 'hero-carousel', 'journal-carousel', 'lunara-method', 'home-oscar-picks', 'home-oscar-facts'],
 reviews: ['reviews-archive', 'review-single'], journal: ['journal-archive'], oscars: ['oscars-portal', 'oscars-ledger']
};
const homeEditors = { hero: 'hero-carousel', dispatch: 'journal-carousel', 'pairing-desk': 'lunara-method', 'oscar-picks': 'home-oscar-picks', 'oscar-facts': 'home-oscar-facts' };
let checks = 0;
function assert(value, message, data) { if (!value) throw new Error(`${message}${data ? '\n' + JSON.stringify(data, null, 2) : ''}`); checks += 1; }
function equal(actual, expected, message) { assert(JSON.stringify(actual) === JSON.stringify(expected), message, { actual, expected }); }
function fixture(surface, options = {}) {
 const args = Object.entries({ surface, ...options }).map(([key, value]) => `--${key}=${value}`);
 const result = spawnSync('php', [path.join(__dirname, 'site-studio-navigation-fixture.php'), ...args], { encoding: 'utf8' });
 if (result.error || result.status !== 0) throw result.error || new Error(result.stderr);
 return result.stdout.replace('</head>', `<style>${css}\n${editorCss}</style><style>#wpcontent{margin-left:160px}#wpbody-content{min-width:0;padding-bottom:40px}@media(max-width:782px){#wpcontent{margin-left:0}}</style></head>`)
  .replace('<body class="wp-admin">', '<body class="wp-admin"><div id="wpcontent"><div id="wpbody-content">')
  .replace('</body>', `</div></div><script>${editorControls}</script><script>${archiveMediaEditor}</script><script>${archiveEditor}</script><script>${controller}</script></body>`);
}
function barrier() { let enter, release; const entered = new Promise(resolve => { enter = resolve; }); const held = new Promise(resolve => { release = resolve; }); return { entered, release, wait: () => { enter(); return held; } }; }
async function within(promise, label) {
 let timer;
 try { return await Promise.race([promise, new Promise((resolve, reject) => { timer = setTimeout(() => reject(new Error(`${label} did not start within 5 seconds.`)), 5000); })]); }
 finally { clearTimeout(timer); }
}
async function openPage(browser, surface, width = 1440, javascript = true, options = {}) {
 const page = await browser.newPage({ viewport: { width, height: 1100 }, javaScriptEnabled: javascript });
 page.setDefaultTimeout(5000);
 const html = fixture(surface, options), requests = [], loads = [];
 const state = { hold: null };
 await page.route('https://example.test/**', async route => {
  const request = route.request(), url = new URL(request.url());
  if (url.pathname === '/wp-admin/admin.php') return route.fulfill({ contentType: 'text/html', body: html });
  if (url.pathname.startsWith('/wp-json/')) {
   const body = request.method() === 'GET' ? null : request.postDataJSON(); requests.push({ path: url.pathname, body });
   if (url.pathname.endsWith('/preview')) {
    if (state.hold) await state.hold.wait();
    return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ url: 'https://example.test/?lunara_homepage_preview=123e4567-e89b-42d3-a456-426614174111' }) });
   }
   return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ revisions: [] }) });
  }
  loads.push(url.href); return route.fulfill({ contentType: 'text/html', body: '<!doctype html><body>Public preview</body>' });
 });
 await page.goto(`https://example.test/wp-admin/admin.php?page=lunara-site-studio&surface=${surface}`);
 return { page, requests, loads, state };
}
async function navigationSnapshot(page) {
 return page.evaluate(() => {
  function link(node) { return { id: node.dataset.studioPage || node.dataset.studioEditor, text: node.textContent.trim(), href: node.href, current: node.getAttribute('aria-current'), guarded: node.hasAttribute('data-workspace-navigation') }; }
  const directory = document.querySelector('[data-studio-tool-directory]');
  return { pages: [...document.querySelectorAll('[data-studio-page]')].map(link), context: [...document.querySelectorAll('[data-studio-editor]')].map(link), directoryOpen: directory.open, searchInside: !!directory.querySelector('[data-lunara-surface-search]'), cards: [...directory.querySelectorAll('[data-lunara-surface-card]')].map(node => node.dataset.surface), doc: [document.documentElement.clientWidth, document.documentElement.scrollWidth] };
 });
}

(async () => {
 assert(process.env.LUNARA_BROWSER_EXECUTABLE && fs.existsSync(process.env.LUNARA_BROWSER_EXECUTABLE), 'A real Chromium executable is required.');
 const browser = await chromium.launch({ headless: true, executablePath: process.env.LUNARA_BROWSER_EXECUTABLE });
 try {
  if (!process.env.LUNARA_NAV_GUARDS_ONLY) {
  for (const width of [1440, 390]) {
   for (const [family, surface] of Object.entries(mainPages)) {
    const { page, requests, loads } = await openPage(browser, surface, width);
    await page.waitForSelector('[data-lunara-site-studio-ready="true"]');
    const snapshot = await navigationSnapshot(page);
    equal(snapshot.pages.map(link => link.id), Object.keys(mainPages), `${surface}/${width}: four stable page entries`);
    equal(snapshot.pages.filter(link => link.current).map(link => link.id), [family], `${surface}/${width}: correct current parent`);
    equal(snapshot.context.map(link => link.id), contextPages[family], `${surface}/${width}: correct contextual editors`);
    equal(snapshot.context.filter(link => link.current).map(link => link.id), [surface], `${surface}/${width}: one current editor`);
    if (family === 'oscars') equal(snapshot.context.find(link => link.id === 'oscars-ledger').text, 'Ledger layouts', 'The ledger destination must describe its presentation controls without promising database editing.');
    for (const link of [...snapshot.pages, ...snapshot.context]) {
     const expected = mainPages[link.id] || link.id;
     assert(new URL(link.href).searchParams.get('surface') === expected && link.guarded, `${surface}: canonical guarded destination ${link.id}`, link);
    }
    assert(!snapshot.directoryOpen && snapshot.searchInside && new Set(snapshot.cards).size === snapshot.cards.length && snapshot.cards.length === 14, `${surface}: secondary directory starts closed and retains all 14 registered destinations`, snapshot);
    assert(snapshot.doc[1] <= snapshot.doc[0] + 1, `${surface}/${width}: document must not overflow`, snapshot);
    const targets = page.locator('[data-studio-page], [data-studio-editor]');
    for (let index = 0; index < await targets.count(); index += 1) {
     const geometry = await targets.nth(index).evaluate(node => { const box = node.getBoundingClientRect(); return { width: box.width, height: box.height, left: box.left, right: box.right, viewport: innerWidth }; });
     assert(geometry.height >= 44 && geometry.left >= -1 && geometry.right <= geometry.viewport + 1, `${surface}/${width}: visible 44px navigation target`, geometry);
    }
    if (surface === 'reviews-archive' || surface === 'journal-archive') {
     const groups = await page.locator('.lunara-site-studio-inspector > details').evaluateAll(nodes => nodes.map(node => ({ id: node.dataset.section, label: node.querySelector(':scope > summary').textContent.trim(), open: node.open, fields: [...node.querySelectorAll('[data-field-path]')].map(field => field.dataset.fieldPath) })));
     equal(groups.map(group => group.id), ['essentials', 'stories', 'gallery', 'retention', 'fine-tune', 'advanced', 'revision-history'], `${surface}: shared inspector groups without false Mobile category`);
     equal(groups.filter(group => group.open).map(group => group.id), ['essentials'], `${surface}: Content opens first`);
     equal(groups.map(group => group.label), ['Content', 'Stories', 'Gallery', 'Continue reading', 'Layout', 'Advanced', 'History'], `${surface}: shared plain-language inspector labels`);
     equal(groups[0].fields, ['kicker', 'title', 'deck', 'supporting_copy'], `${surface}: editorial copy is in Content`);
     const layout = groups.find(group => group.id === 'fine-tune');
     assert(layout.fields.length >= 7 && layout.fields.every(field => field === 'item_count' || field.startsWith('presentation.')), `${surface}: geometry and presentation stay together under Layout`, groups);
    }
    const requestCount = requests.length, loadCount = loads.length;
    await page.locator('[data-studio-tool-directory] > summary').click();
    await page.fill('[data-lunara-surface-search]', 'GLOBAL DESIGN');
    equal(await page.locator('[data-lunara-surface-card]:visible').evaluateAll(nodes => nodes.map(node => node.dataset.surface)), ['global-design'], `${surface}: secondary tools search is case-insensitive`);
    assert(await page.locator('[data-studio-page]:visible').count() === 4 && await page.locator('[data-studio-editor]:visible').count() === contextPages[family].length, 'Tool filtering must not hide primary or contextual navigation.');
    assert(requests.length === requestCount && loads.length === loadCount, 'Tool search must stay local without REST calls or preview reload.');
    if (process.env.LUNARA_NAV_SCREENSHOTS && (surface === 'homepage-structure' || surface === 'reviews-archive' && width === 1440)) {
     await page.locator('[data-studio-tool-directory] > summary').click();
     fs.mkdirSync(process.env.LUNARA_NAV_SCREENSHOTS, { recursive: true });
     await page.screenshot({ path: path.join(process.env.LUNARA_NAV_SCREENSHOTS, `site-studio-${surface}-${width}.png`), fullPage: true });
    }
    await page.close();
   }
  }

  // Navigation is server rendered and keyboard operable even when enhancement fails.
  for (const width of [1440, 390]) {
   const { page } = await openPage(browser, 'homepage-structure', width, false);
   const snapshot = await navigationSnapshot(page);
   equal(snapshot.context.map(link => link.id), contextPages.home, `No JS/${width}: all homepage editors remain linked`);
   const rows = await page.locator('[data-home-section-editor]').evaluateAll(nodes => nodes.map(node => ({ section: node.dataset.homeSectionEditor, surface: new URL(node.href).searchParams.get('surface'), row: node.closest('[data-home-row]').dataset.slug, guarded: node.hasAttribute('data-workspace-navigation') })));
   equal(rows.map(row => row.section).sort(), Object.keys(homeEditors).sort(), `No JS/${width}: five real section editors, no invented Reviews editor`);
   for (const row of rows) assert(row.surface === homeEditors[row.section] && row.row === row.section && row.guarded, 'Section edit link must belong to the matching live row', row);
   await page.locator('[data-studio-tool-directory] > summary').focus();
   await page.keyboard.press('Enter');
   assert(await page.locator('[data-studio-tool-directory]').getAttribute('open') !== null && await page.locator('[data-lunara-surface-card]:visible').count() === 14, `No JS/${width}: native disclosure exposes the full directory`);
   await page.locator('[data-studio-page="reviews"]').focus();
   const focus = await page.locator('[data-studio-page="reviews"]').evaluate(node => ({ focused: document.activeElement === node, outline: getComputedStyle(node).outlineStyle }));
   assert(focus.focused && focus.outline !== 'none', 'Page links must retain visible keyboard focus.');
   await Promise.all([page.waitForURL(/surface=reviews-archive/), page.keyboard.press('Enter')]);
   assert(new URL(page.url()).searchParams.get('surface') === 'reviews-archive', 'No JS navigation must follow its canonical link.');
   await page.close();
  }

  for (const surface of ['hero-carousel', 'journal-carousel', 'lunara-method', 'home-oscar-picks', 'home-oscar-facts', 'review-single', 'oscars-ledger', 'global-design']) {
   const { page } = await openPage(browser, surface, 1440, false);
   const snapshot = await navigationSnapshot(page);
   const family = Object.keys(contextPages).find(key => contextPages[key].includes(surface));
   equal(snapshot.pages.filter(link => link.current).map(link => link.id), family ? [family] : [], `${surface}: secondary editor has the correct primary parent`);
   equal(snapshot.context.filter(link => link.current).map(link => link.id), family ? [surface] : [], `${surface}: current contextual editor is accurate`);
   await page.close();
  }

  for (const [option, id] of [['deny', 'reviews-archive'], ['deny', 'hero-carousel'], ['unavailable', 'journal-archive'], ['unsafe', 'hero-carousel']]) {
   const { page } = await openPage(browser, 'homepage-structure', 390, false, { [option]: id });
   if (option === 'deny') {
    assert(await page.locator(`[data-studio-editor="${id}"]`).count() === 0 && await page.locator(`[data-home-section-editor][href*="surface=${id}"]`).count() === 0, 'Unauthorized or unsafe destinations must not leak through shortcuts.');
    if (id === 'reviews-archive') assert(await page.locator('[data-studio-page="reviews"]').count() === 0, 'A capability-denied primary editor must be omitted.');
   } else if (option === 'unsafe') {
    const href = await page.locator('[data-studio-editor="hero-carousel"]').getAttribute('href');
    assert(href === 'https://example.test/wp-admin/admin.php?page=lunara-site-studio&surface=hero-carousel', 'An unsafe registry handoff must never override the canonical local editor URL.');
   } else {
    assert((await page.locator('[data-studio-page="journal"]').textContent()).includes('Unavailable'), 'Unavailable registered editors must retain their truthful status.');
   }
   await page.close();
  }
  }

  const destinations = ['[data-studio-page="reviews"]', '[data-studio-editor="journal-carousel"]', '[data-home-section-editor="hero"]', '[data-lunara-surface-card][data-surface="global-design"]'];
  for (const selector of destinations) {
   const { page, requests, state } = await openPage(browser, 'homepage-structure', 1440);
   await page.waitForSelector('[data-lunara-site-studio-ready="true"]');
   if (selector.includes('surface-card')) await page.locator('[data-studio-tool-directory] > summary').click();
   await page.locator('[data-home-row] [data-move="later"]').first().click();
   assert(await page.locator('[data-lunara-site-studio]').getAttribute('data-dirty') === 'true', 'The navigation test must begin with a real edited candidate.');
   await page.evaluate(() => { window.name = '0'; window.confirm = () => { window.name = String(Number(window.name) + 1); return false; }; });
   const before = page.url();
   await page.locator(selector).click();
   assert(page.url() === before && await page.evaluate(() => window.name) === '1' && await page.locator('[data-lunara-site-studio]').getAttribute('data-dirty') === 'true', `${selector}: cancelling navigation retains the candidate and prompts once`);
   state.hold = barrier();
   await page.locator('[data-action="preview"]').click();
   await within(state.hold.entered, 'Homepage private Preview request');
   const blocked = await page.locator(selector).evaluate(node => ({ disabled: node.getAttribute('aria-disabled'), click: !node.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, button: 0 })), aux: !node.dispatchEvent(new MouseEvent('auxclick', { bubbles: true, cancelable: true, button: 1 })), key: !node.dispatchEvent(new KeyboardEvent('keydown', { bubbles: true, cancelable: true, key: 'Enter' })) }));
   assert(blocked.disabled === 'true' && blocked.click && blocked.aux && blocked.key && page.url() === before && await page.evaluate(() => window.name) === '1', `${selector}: pending Preview suppresses primary, auxiliary and keyboard navigation without prompting`, blocked);
   state.hold.release();
   await page.waitForFunction(() => document.querySelector('[data-lunara-site-studio]').dataset.workspaceState === 'preview-current');
   assert(await page.locator(selector).getAttribute('aria-disabled') === null && requests.length === 1, 'Completed Preview must restore navigation without extra requests.');
   await page.evaluate(() => { window.confirm = () => { window.name = String(Number(window.name) + 1); return true; }; });
   let unexpectedUnload = false;
   page.on('dialog', async dialog => { unexpectedUnload = true; await dialog.dismiss(); });
   const target = await page.locator(selector).getAttribute('href');
   await Promise.all([page.waitForURL(target), page.locator(selector).click()]);
   assert(page.url() === target && await page.evaluate(() => window.name) === '2' && !unexpectedUnload, `${selector}: accepted navigation prompts once and bypasses duplicate beforeunload`);
   await page.close();
  }
  process.stdout.write(`site-studio-navigation runtime: ${checks} assertions passed.\n`);
 } finally { await browser.close(); }
})().catch(error => { process.stderr.write(`${error.stack}\n`); process.exitCode = 1; });
