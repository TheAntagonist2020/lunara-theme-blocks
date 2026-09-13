'use strict';
const { fixture } = require('./site-studio-browser-fixture');

module.exports = async function journalArticleCases(browser) {
 let checks = 0;
 const assert = (value, message) => { if (!value) throw new Error(message); checks += 1; };
 const routePath = '/journal/angel-finally-gets-a-face-that-can-fly/';
 const original = {hero:{title_size:84,image_fit:'cover',image_position_x:50,image_position_y:50},metadata:{show_byline:true,show_date:true,show_reading_time:false}};
 for (const width of [1440, 390]) {
  const page = await browser.newPage({viewport:{width,height:1000}});
  page.setDefaultTimeout(6000);
  const requests = []; let saved = structuredClone(original), history = [], restoreState = null, rejectedFraming = '';
  const html = fixture('journal-single');
  await page.route('https://example.test/**', async route => {
   const request = route.request(), url = new URL(request.url());
   if (url.pathname === '/wp-admin/admin.php') return route.fulfill({contentType:'text/html',body:html});
   if (url.pathname.startsWith('/wp-json/')) {
    const body = request.method() === 'GET' ? null : request.postDataJSON(); requests.push({path:url.pathname,body});
    if (rejectedFraming && url.pathname.endsWith('/preview')) return route.fulfill({status:422,contentType:'application/json',body:JSON.stringify({code:'site_studio_journal_single_invalid',message:'Review the highlighted controls.',fields:{[rejectedFraming]:'Correct this image framing value.'}})});
    let response;
    if (url.pathname.endsWith('/preview')) response = {url:`https://example.test${routePath}?lunara_journal_single_preview=123e4567-e89b-42d3-a456-426614174111`};
    else if (url.pathname.endsWith('/save')) { restoreState = saved; saved = structuredClone(body.state); history = [{id:'journal-revision',timestamp:'2026-09-13 12:00:00',action:'save'}]; response = {state:saved,changed_sections:['hero'],revision_id:'journal-revision',timestamp:'2026-09-13 12:00:00'}; }
    else if (url.pathname.endsWith('/restore')) { saved = restoreState; response = {state:saved,safety_revision_id:'journal-safety',timestamp:'2026-09-13 12:00:01'}; }
    else response = {revisions:history};
    return route.fulfill({contentType:'application/json',body:JSON.stringify(response)});
   }
   if (url.pathname.includes('/uploads/')) return route.fulfill({contentType:'image/svg+xml',body:'<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="900"><rect width="1600" height="900" fill="teal"/></svg>'});
   return route.fulfill({contentType:'text/html',body:'<!doctype html><main data-lunara-site-studio-section="hero">Published article preview</main>'});
  });
  await page.goto('https://example.test/wp-admin/admin.php?page=lunara-site-studio&surface=journal-single');
  await page.waitForSelector('[data-lunara-site-studio-ready="true"]');
  assert(await page.locator('[data-field-path]').count() === 7, 'Journal editor exposes exactly seven canonical presentation controls.');
  assert((await page.locator('iframe').getAttribute('src')).includes(routePath), 'Journal editor opens the current published article route.');
  assert(await page.locator('[data-journal-image-framing] img').isVisible(), 'Journal framing widget shows the preview article’s actual featured image.');
  assert(await page.getByRole('button',{name:/Choose image|Replace image/,exact:true}).count() === 0, 'Journal framing has no competing featured-image writer.');
  assert(await page.getByRole('link',{name:'Choose another Journal article to edit'}).count() === 1, 'Journal editor makes the article and gallery owner discoverable.');
  assert(await page.locator('[data-studio-editor="journal-single"]').getAttribute('aria-current') === 'page', 'Journal article layout is a first-class page-editor destination.');
  const geometry = await page.evaluate(() => ({client:document.documentElement.clientWidth,scroll:document.documentElement.scrollWidth}));
  assert(geometry.scroll <= geometry.client + 1, `Journal editor does not overflow at ${width}px.`);
  await page.evaluate(() => { window.confirm = () => true; });
  const stage = page.locator('[data-journal-image-framing] .lunara-editor-image-stage');
  await stage.focus(); await page.keyboard.press('ArrowLeft');
  assert(await page.locator('[data-field-path="hero.image_position_x"]').inputValue() === '45', 'Shared image keyboard control updates the canonical focal point.');
  await page.getByRole('combobox',{name:'Image fit',exact:true}).selectOption('full');
  assert(await page.locator('[data-journal-image-framing] img').evaluate(node => node.style.objectFit) === 'contain', 'Full-image mode visibly changes the shared framing preview.');
  await page.getByRole('combobox',{name:'Image fit',exact:true}).selectOption('cover');
  assert(await page.locator('[data-field-path="hero.image_position_x"]').inputValue() === '45', 'Switching image fit retains the remembered focal point.');
  await page.click('[data-action="discard"]');
  assert(await page.locator('[data-field-path="hero.image_position_x"]').inputValue() === '50' && requests.length === 0, 'Discard restores the saved framing locally without a write.');

  await page.fill('[data-field-path="hero.title_size"]','100');
  await page.uncheck('[data-field-path="metadata.show_byline"]');
  await page.check('[data-field-path="metadata.show_reading_time"]');
  await page.getByRole('combobox',{name:'Image fit',exact:true}).selectOption('full');
  await page.click('[data-action="preview"]');
  await page.waitForFunction(() => document.querySelector('iframe').src.includes('lunara_journal_single_preview='));
  const preview = requests.find(item => item.path.endsWith('/preview'));
  assert(preview && preview.body.state.hero.title_size === 100 && preview.body.state.hero.image_fit === 'contain' && preview.body.state.metadata.show_byline === false && preview.body.state.metadata.show_reading_time === true, 'Preview sends the complete current Journal candidate.');
  assert(JSON.stringify(saved) === JSON.stringify(original), 'Private preview leaves the saved public Journal state untouched.');
  await page.click('[data-preview-width="mobile"]');
  assert(await page.locator('iframe').evaluate(node => node.style.width) === '390px', 'Journal uses the same real-width mobile preview as the other editors.');
  await page.click('[data-action="save"]');
  await page.waitForFunction(() => document.querySelector('[data-lunara-site-studio]').dataset.workspaceState === 'live-saved');
  assert(saved.hero.image_fit === 'contain' && saved.hero.title_size === 100 && saved.metadata.show_byline === false, 'Apply persists the candidate through the shared save envelope.');
  assert(await page.locator('[data-lunara-site-studio]').getAttribute('data-dirty') === 'false', 'Successful Apply returns Journal to the saved state.');
  await page.locator('[data-revision-history]').evaluate(node => { node.open = true; });
  await page.waitForSelector('[data-action="restore"]'); await page.click('[data-action="restore"]');
  await page.waitForFunction(() => document.querySelector('[data-field-path="hero.title_size"]').value === '84');
  assert(await page.locator('[data-field-path="metadata.show_byline"]').isChecked() && await page.locator('[data-journal-image-framing] img').evaluate(node => node.style.objectFit) === 'cover', 'History restores metadata and the visible framing widget together.');
  const beforeInvalid = requests.filter(item => item.path.endsWith('/preview')).length;
  await page.fill('[data-field-path="hero.title_size"]','121');
  await page.getByRole('combobox',{name:'Image fit',exact:true}).selectOption('full');
  assert(await page.locator('[data-field-path="hero.title_size"]').inputValue() === '121', 'Changing image framing retains an invalid-only headline edit for correction.');
  await page.click('[data-action="preview"]');
  assert(requests.filter(item => item.path.endsWith('/preview')).length === beforeInvalid && await page.locator('[data-field-path="hero.title_size"]').getAttribute('aria-invalid') === 'true', 'Invalid headline size is caught before any Preview request.');
  await page.fill('[data-field-path="hero.title_size"]','100');
  for (const field of ['hero.image_fit','hero.image_position_x','hero.image_position_y']) {
   rejectedFraming = field;
   await page.click('[data-action="preview"]');
   await page.waitForFunction(key => { const node=document.querySelector('[data-field-path="'+key+'"]'); return node.getAttribute('aria-invalid')==='true' && document.activeElement===node; }, field);
   assert(await page.locator('[data-field-path="'+field+'"]').isVisible(), `Server ${field} errors reveal and focus the canonical framing control.`);
   assert(await page.getByText('Correct this image framing value.',{exact:true}).isVisible(), `Server ${field} errors remain visible to the editor.`);
  }
  await page.fill('[data-field-path="hero.image_position_y"]','72');
  assert(await page.locator('[data-journal-image-framing] img').evaluate(node => node.style.objectPosition) === '50% 72%', 'Correcting a revealed framing field updates the same image preview.');
  rejectedFraming = '';
  await page.click('[data-action="preview"]');
  await page.waitForFunction(() => document.querySelector('[data-lunara-site-studio]').dataset.workspaceState==='preview-current');
  assert(await page.locator('[data-journal-framing-fields]').isHidden() && JSON.stringify(saved)===JSON.stringify(original), 'Successful private retry clears fallback errors without a public write.');
  await page.close();
 }
 const nojs = await browser.newPage({viewport:{width:390,height:900},javaScriptEnabled:false});
 await nojs.route('**/*', route => route.fulfill({contentType:'text/html',body:'Published preview'}));
 await nojs.setContent(fixture('journal-single'));
 assert(await nojs.getByRole('link',{name:'Choose another Journal article to edit'}).count() === 1 && await nojs.locator('[data-action="save"]').isDisabled(), 'Without JavaScript, Journal shows its owner handoff and disables unsafe writes.');
 await nojs.close();
 process.stdout.write(`Journal article workspace: ${checks} assertions passed.\n`);
};
