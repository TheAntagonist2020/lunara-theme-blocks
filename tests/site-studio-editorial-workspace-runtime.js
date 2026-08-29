'use strict';

const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');

let chromium;
try {
	({ chromium } = require('playwright'));
} catch (error) {
	({ chromium } = require('playwright-core'));
}

const themeRoot = path.resolve(__dirname, '..');
const css = fs.readFileSync(path.join(themeRoot, 'assets/css/lunara-site-studio.css'), 'utf8');
const controller = fs.readFileSync(path.join(themeRoot, 'assets/js/lunara-site-studio.js'), 'utf8');
const previewBridge = fs.readFileSync(path.join(themeRoot, 'assets/js/lunara-site-studio-preview.js'), 'utf8');
const token = '123e4567-e89b-42d3-a456-426614174111';

const cases = [
	{
		surface: 'reviews-archive',
		outerWidth: 1440,
		route: '/reviews/',
		query: 'lunara_reviews_preview',
		params: {},
		field: 'kicker',
		value: 'Updated Reviews',
		marker: 'hero',
		markers: ['hero', 'grid', 'pagination', 'pairing-desk'],
		handoff: 'Open full archive controls',
		archive: true
	},
	{
		surface: 'journal-archive',
		outerWidth: 1101,
		route: '/journal/',
		query: 'lunara_journal_preview',
		params: {},
		field: 'kicker',
		value: 'Updated Journal',
		marker: 'deskbar',
		markers: ['hero', 'deskbar', 'filters', 'toolbar', 'grid', 'retention', 'pagination'],
		handoff: 'Open full archive controls',
		archive: true
	},
	{
		surface: 'review-single',
		outerWidth: 782,
		route: '/reviews/sinners-2025/',
		query: 'lunara_review_single_preview',
		params: {},
		field: 'review.density',
		value: 'compact',
		marker: 'pair-it-with',
		markers: ['hero', 'criticism', 'debrief', 'pair-it-with'],
		handoff: 'Open Review Studio'
	},
	{
		surface: 'utility-search',
		outerWidth: 390,
		route: '/search/',
		query: 'lunara_utility_search_preview',
		params: { q: 'Lunara' },
		field: 'presentation.density',
		value: 'compact',
		marker: 'search-command',
		markers: ['search-command', 'direct-matches', 'result-run', 'recovery'],
		handoff: 'Open Classic controls'
	},
	{
		surface: 'site-footer',
		outerWidth: 390,
		route: '/',
		query: 'lunara_footer_preview',
		params: {},
		field: 'brand.tagline',
		value: 'A sharper closing line.',
		marker: 'footer',
		markers: ['footer'],
		handoff: 'Open Classic controls',
		booleanRemoval: 'brand.show_logo'
	}
];

function assert(condition, message, evidence) {
	if (condition) {
		return;
	}
	if (evidence) {
		process.stderr.write(`${JSON.stringify(evidence, null, 2)}\n`);
	}
	throw new Error(message);
}

function fixture(surface) {
	const result = spawnSync('php', [path.join(__dirname, 'site-studio-runtime.php'), `--fixture=${surface}`], { encoding: 'utf8' });
	if (result.error || result.status !== 0) {
		throw result.error || new Error(result.stderr);
	}
	const adminCss = '<style>#wpcontent{margin-left:160px}#wpbody-content{min-width:0;padding-bottom:40px}@media(max-width:782px){#wpcontent{margin-left:0}}</style>';
	return result.stdout
		.replace('</head>', `<style>${css}</style>${adminCss}</head>`)
		.replace('<body class="wp-admin">', '<body class="wp-admin"><div id="wpwrap"><div id="wpcontent"><div id="wpbody"><div id="wpbody-content">')
		.replace('</body>', `</div></div></div></div><script>${controller}</script></body>`);
}

function mutateFixtureState(html, mutate) {
	let replaced = false;
	const result = html.replace(/(<script[^>]+id="lunara-site-studio-state"[^>]*>)([\s\S]*?)(<\/script>)/, (match, open, json, close) => {
		const state = JSON.parse(json);
		mutate(state);
		replaced = true;
		return `${open}${JSON.stringify(state)}${close}`;
	});
	assert(replaced, 'The workspace fixture must expose one parseable state payload.');
	return result;
}

function canonicalUrl(testCase, includeToken) {
	const url = new URL(`https://example.test${testCase.route}`);
	Object.keys(testCase.params).forEach(key => url.searchParams.append(key, testCase.params[key]));
	if (includeToken) {
		url.searchParams.append(testCase.query, token);
	}
	return url.href;
}

function getPath(value, fieldPath) {
	return fieldPath.split('.').reduce((current, key) => current[key], value);
}

function lastRequest(requests, suffix) {
	for (let index = requests.length - 1; index >= 0; index -= 1) {
		if (requests[index].path.endsWith(suffix)) {
			return requests[index];
		}
	}
	return null;
}

async function waitForFrame(page, expectedUrl) {
	await page.waitForFunction(url => {
		const frame = document.querySelector('iframe');
		try {
			return frame && frame.getAttribute('src') === url && frame.contentWindow.location.href === url;
		} catch (error) {
			return false;
		}
	}, expectedUrl);
}

(async () => {
	const executablePath = process.env.LUNARA_BROWSER_EXECUTABLE;
	assert(executablePath && fs.existsSync(executablePath), 'LUNARA_BROWSER_EXECUTABLE must name a real Chromium executable.');
	const browser = await chromium.launch({ headless: true, executablePath });
	const results = [];

	try {
		{
			const page = await browser.newPage({ viewport: { width: 782, height: 900 } });
			const unsafeFixture = mutateFixtureState(fixture('utility-search'), state => {
				state.presentation.density = 'not-an-option';
				state.geometry.section_gap = 999;
			});
			await page.route('https://example.test/**', route => route.fulfill({ status: 200, contentType: 'text/html', body: unsafeFixture }));
			await page.goto('https://example.test/wp-admin/admin.php?page=lunara-site-studio&surface=utility-search', { waitUntil: 'domcontentloaded' });
			const failedClosed = await page.evaluate(() => {
				const root = document.querySelector('[data-lunara-site-studio]');
				const actions = Array.from(root.querySelectorAll('[data-action]'));
				return { ready: root.hasAttribute('data-lunara-site-studio-ready'), state: root.dataset.workspaceState, disabled: actions.every(action => action.disabled && action.getAttribute('aria-disabled') === 'true') };
			});
			assert(!failedClosed.ready && failedClosed.state === 'recovery' && failedClosed.disabled, 'Invalid legacy-shaped baseline values must fail closed before editing.', failedClosed);
			await page.close();
		}

		for (const testCase of cases) {
			process.stdout.write(`checking ${testCase.surface}...\n`);
			const page = await browser.newPage({ viewport: { width: testCase.outerWidth, height: 1050 } });
			const adminFixture = fixture(testCase.surface);
			const requests = [];
			let frontendLoads = 0;
			let saveFailure = null;

			await page.route('https://example.test/**', async route => {
				const request = route.request();
				const url = new URL(request.url());
				if (url.pathname === '/wp-admin/admin.php') {
					return route.fulfill({ status: 200, contentType: 'text/html', body: adminFixture });
				}
				if (url.pathname.startsWith('/wp-json/')) {
					const method = request.method();
					const body = method === 'GET' ? null : request.postDataJSON();
					requests.push({ path: url.pathname, method, body });
					if (url.pathname.endsWith('/preview')) {
						return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ url: canonicalUrl(testCase, true) }) });
					}
					if (url.pathname.endsWith('/save')) {
						if (saveFailure) {
							return route.fulfill({ status: 422, contentType: 'application/json', body: JSON.stringify({ code: 'site_studio_invalid', message: 'Review the highlighted controls.', fields: saveFailure }) });
						}
						return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ state: body.state, changed_sections: [], revision_id: 'editorial-save', timestamp: '2026-08-29 12:00:00' }) });
					}
					if (url.pathname.endsWith('/revisions')) {
						return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ revisions: [{ id: 'editorial-save', timestamp: '2026-08-29 12:00:00', action: 'save' }] }) });
					}
					return route.fulfill({ status: 200, contentType: 'application/json', body: '{}' });
				}

				frontendLoads += 1;
				const instance = url.searchParams.get('lunara_site_studio_instance');
				const markerHtml = testCase.markers.map(marker => `<button type="button" data-lunara-site-studio-section="${marker}">${marker}</button>`).join('');
				const childConfig = instance ? `<script>window.LunaraSiteStudioPreviewConfig=${JSON.stringify({ protocol: 'lunara-site-studio/v1', version: 1, type: 'select-section', surface: testCase.surface, instance, markers: testCase.markers })};</script><script>${previewBridge}</script>` : '';
				return route.fulfill({
					status: 200,
					contentType: 'text/html',
					body: '<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"></head><body><main>' + markerHtml + '</main>' + childConfig + '</body></html>'
				});
			});

			await page.goto(`https://example.test/wp-admin/admin.php?page=lunara-site-studio&surface=${testCase.surface}`, { waitUntil: 'domcontentloaded' });
			try {
				await page.waitForSelector('[data-lunara-site-studio-ready="true"]', { timeout: 5000 });
			} catch (error) {
				const diagnostic = await page.evaluate(() => {
					const root = document.querySelector('[data-lunara-site-studio]');
					const ids = root ? Array.from(root.querySelectorAll('[id]')).map(node => node.id) : [];
					const described = root ? Array.from(root.querySelectorAll('[aria-describedby]')).map(node => ({ path: node.dataset.fieldPath || node.id, ids: node.getAttribute('aria-describedby').split(/\s+/), missing: node.getAttribute('aria-describedby').split(/\s+/).filter(id => id && !document.getElementById(id)) })) : [];
					return {
						ready: root && root.getAttribute('data-lunara-site-studio-ready'), state: root && root.dataset.workspaceState, status: root && root.querySelector('[data-workspace-status]').textContent, config: window.LunaraSiteStudioWorkspaceConfig,
						fields: root ? Array.from(root.querySelectorAll('[data-field-path]')).map(node => node.dataset.fieldPath) : [],
						actions: root ? Array.from(root.querySelectorAll('[data-action]')).map(node => node.dataset.action) : [],
						duplicateIds: ids.filter((id, index) => ids.indexOf(id) !== index),
						brokenDescriptions: described.filter(item => item.missing.length),
						navigation: root ? root.querySelectorAll('[data-workspace-navigation]').length : 0
					};
				});
				process.stderr.write(`${JSON.stringify({ surface: testCase.surface, diagnostic }, null, 2)}\n`);
				throw error;
			}
			await waitForFrame(page, canonicalUrl(testCase, false));

			const initial = await page.evaluate(() => {
				const root = document.querySelector('[data-lunara-site-studio]');
				const details = Array.from(root.querySelectorAll('details'));
				const workspace = root.querySelector('.lunara-site-studio-workspace');
				const inspector = root.querySelector('.lunara-site-studio-inspector');
				const fields = Array.from(inspector.querySelectorAll('[data-field-path]'));
				return {
					cards: root.querySelectorAll('[data-lunara-surface-card]').length,
					iframes: root.querySelectorAll('iframe').length,
					open: details.filter(node => node.open).map(node => node.dataset.section),
					doc: [document.documentElement.clientWidth, document.documentElement.scrollWidth],
					columns: getComputedStyle(workspace).gridTemplateColumns.split(' ').length,
					technicalText: /theme_mod|option_name|lunara_[a-z0-9_]+/i.test(inspector.textContent),
					labels: fields.every(field => !!field.id && !!inspector.querySelector(`label[for="${field.id}"]`)),
					handoffText: inspector.textContent
				};
			});
			const expectedColumns = testCase.outerWidth > 1280 ? 3 : testCase.outerWidth > 782 ? 2 : 1;
			assert(initial.cards === 10 && initial.iframes === 1, `${testCase.surface} must render the complete map and exactly one preview.`, initial);
			assert(JSON.stringify(initial.open) === '["essentials"]', `${testCase.surface} must open only Essentials.`, initial);
			assert(initial.doc[1] <= initial.doc[0] + 1 && initial.columns === expectedColumns, `${testCase.surface} responsive shell failed at ${testCase.outerWidth}px.`, initial);
			assert(!initial.technicalText && initial.labels && initial.handoffText.includes(testCase.handoff), `${testCase.surface} controls must remain plain-language, labeled, and provide the canonical handoff.`, initial);

			const field = `[data-field-path="${testCase.field}"]`;
			const initialRequestCount = requests.length;
			const initialLoadCount = frontendLoads;
			if (await page.locator(field).evaluate(node => node.tagName === 'SELECT')) {
				await page.selectOption(field, testCase.value);
			} else {
				await page.fill(field, testCase.value);
			}
			assert(requests.length === initialRequestCount && frontendLoads === initialLoadCount, `${testCase.surface} edits must remain local before Preview or Save.`);
			assert(await page.locator('[data-lunara-site-studio]').getAttribute('data-dirty') === 'true', `${testCase.surface} must visibly mark unsaved changes.`);

			if (testCase.archive) {
				const movable = page.locator('[data-section-row]').nth(1);
				const movedSlug = await movable.getAttribute('data-slug');
				await movable.locator('[data-section-move="earlier"]').click();
				assert(await page.locator('[data-section-row]').first().getAttribute('data-slug') === movedSlug, `${testCase.surface} must update the recognizable section order locally.`);

				const visibility = page.locator('[data-section-visible]').first();
				await page.evaluate(() => { window.__removeConfirms = 0; window.confirm = () => { window.__removeConfirms += 1; return false; }; });
				await visibility.click();
				assert(await visibility.isChecked() && await page.evaluate(() => window.__removeConfirms) === 1, `${testCase.surface} must preserve a section when removal is cancelled.`);
				await page.evaluate(() => { window.confirm = () => true; });
				await visibility.click();
				assert(!(await visibility.isChecked()), `${testCase.surface} must apply an explicitly confirmed section removal locally.`);
			}

			if (testCase.booleanRemoval) {
				const booleanControl = page.locator(`[data-field-path="${testCase.booleanRemoval}"]`);
				await page.evaluate(() => { window.__removeConfirms = 0; window.confirm = () => { window.__removeConfirms += 1; return false; }; });
				await booleanControl.click();
				assert(await booleanControl.isChecked() && await page.evaluate(() => window.__removeConfirms) === 1, 'Footer logo removal must be atomic when cancelled.');
				await page.evaluate(() => { window.confirm = () => true; });
				await booleanControl.click();
			}

			await page.click('[data-preview-width="mobile"]');
			const mobile = await page.evaluate(() => ({
				width: document.querySelector('iframe').getAttribute('width'),
				style: document.querySelector('iframe').style.width,
				pressed: document.querySelector('[data-preview-width="mobile"]').getAttribute('aria-pressed')
			}));
			assert(mobile.width === '390' && mobile.style === '390px' && mobile.pressed === 'true', `${testCase.surface} must expose a real 390px preview.`, mobile);

			await page.click('[data-action="preview"]');
			await page.waitForFunction(() => document.querySelector('[data-lunara-site-studio]').dataset.workspaceState === 'preview-current');
			const privateUrl = `${canonicalUrl(testCase, true)}&lunara_site_studio_instance=123e4567-e89b-42d3-a456-000000000000%3A1`;
			await waitForFrame(page, privateUrl);
			const previewRequest = lastRequest(requests, '/preview');
			assert(previewRequest && getPath(previewRequest.body.state, testCase.field) === testCase.value, `${testCase.surface} Preview must submit the complete current candidate.`);

			await page.frameLocator('iframe').locator(`[data-lunara-site-studio-section="${testCase.marker}"]`).click();
			await page.waitForFunction(marker => {
				const rail = document.querySelector(`[data-section-control="${marker}"]`);
				return !!rail && rail.hasAttribute('data-preview-selected');
			}, testCase.marker);
			const selection = await page.evaluate(marker => {
				const rail = document.querySelector(`[data-section-control="${marker}"]`);
				const selected = Array.from(document.querySelectorAll('[data-preview-selected]'));
				return { rail: !!rail && rail.hasAttribute('data-preview-selected'), selected: selected.length, focused: selected.some(node => node === document.activeElement || node.contains(document.activeElement)) };
			}, testCase.marker);
			assert(selection.rail && selection.selected >= 1 && selection.focused, `${testCase.surface} preview-region messages must select and focus the matching recognizable controls.`, selection);

			if (testCase.surface === 'reviews-archive' || testCase.surface === 'review-single') {
				const errorKey = testCase.surface === 'reviews-archive' ? 'section_visibility' : 'review.density';
				saveFailure = { [errorKey]: `Correct ${errorKey}.` };
				await page.click('[data-action="save"]');
				await page.waitForFunction(() => document.querySelector('[data-lunara-site-studio]').dataset.workspaceState === 'validation-error');
				const validation = await page.evaluate(({ errorKey, field, value }) => {
					const target = document.querySelector(`[data-error-key="${errorKey}"]`);
					const described = (target.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
					const error = document.getElementById(described[described.length - 1]);
					const control = document.querySelector(`[data-field-path="${field}"]`);
					return { invalid: target.getAttribute('aria-invalid'), focused: document.activeElement === target, visible: !!error && !error.hidden && !!error.textContent, retained: control.tagName === 'SELECT' ? control.value === value : control.value === value, dirty: document.querySelector('[data-lunara-site-studio]').dataset.dirty };
				}, { errorKey, field: testCase.field, value: testCase.value });
				assert(validation.invalid === 'true' && validation.focused && validation.visible && validation.retained && validation.dirty === 'true', `${testCase.surface} must retain the candidate and focus its exact field-specific error after a 422.`, validation);
				saveFailure = null;
			}

			await page.evaluate(() => { window.__saveConfirms = 0; window.confirm = () => { window.__saveConfirms += 1; return true; }; });
			await page.click('[data-action="save"]');
			await page.waitForFunction(() => document.querySelector('[data-lunara-site-studio]').dataset.workspaceState === 'live-saved');
			await waitForFrame(page, canonicalUrl(testCase, false));
			const saveRequest = lastRequest(requests, '/save');
			assert(saveRequest && getPath(saveRequest.body.state, testCase.field) === testCase.value, `${testCase.surface} Save Live must persist the visible candidate.`);
			assert(await page.evaluate(() => window.__saveConfirms) === 0, `${testCase.surface} ordinary Save Live must not ask for confirmation.`);
			assert(await page.locator('[data-lunara-site-studio]').getAttribute('data-dirty') === 'false', `${testCase.surface} must visibly return to live state after save.`);

			results.push({ surface: testCase.surface, width: testCase.outerWidth, preview: canonicalUrl(testCase, true), marker: testCase.marker });
			await page.close();
		}
	} finally {
		await browser.close();
	}

	process.stdout.write(`site-studio editorial workspace: ${results.length} surfaces passed.\n`);
})().catch(error => {
	process.stderr.write(`${error.stack || error.message}\n`);
	process.exit(1);
});
