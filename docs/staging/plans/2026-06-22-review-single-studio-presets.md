# Review Single Studio Presets Plan

spec: `docs/staging/specs/2026-06-22-review-single-studio-presets.md`

- [x] T1: Add preset contract coverage
goal: Require named presets, preset apply handling, admin-only preview links, and preview override guardrails before production changes.
files: `tests/theme-studio-review-single-controls.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-review-single-controls.ps1` fails for missing preset-layer markers.
spec: `docs/staging/specs/2026-06-22-review-single-studio-presets.md#test-contract-test-requires-preset-specs-four-preset-keys-apply-buttons-query-preview-links-admin-capability-guard-for-preview-overrides-invalid-key-fallback-and-no-raw-css-textarea`

- [x] T2: Add private preset cards and apply path
goal: Render named preset cards in Review Single Studio and let valid preset apply buttons save bounded theme mods through the existing admin-post handler.
files: `inc/control-desk.php`, `tests/theme-studio-review-single-controls.ps1`
acceptance: Contract test passes its admin preset specs, apply button, nonce, capability, and invalid-key fallback checks.
spec: `docs/staging/specs/2026-06-22-review-single-studio-presets.md#contract-the-save-handler-accepts-an-optional-lunara_review_single_preset-value-valid-presets-apply-their-mapped-values-invalid-preset-keys-are-ignored-and-the-normal-manual-control-save-path-remains-intact`

- [x] T3: Add admin-only public preview override
goal: Let admins preview a preset on Review single pages with a request-only query override while normal public visitors keep saved theme-mod values.
files: `inc/frontend.php`, `tests/theme-studio-review-single-controls.ps1`
acceptance: Contract test passes its frontend preview override, capability guard, query key, and `body.single-review` scope checks.
spec: `docs/staging/specs/2026-06-22-review-single-studio-presets.md#contract-admin-only-preview-links-use-a-query-override-for-the-current-request-only-the-override-is-ignored-unless-the-viewer-can-edit_theme_options-it-does-not-save-theme-mods-and-does-not-expose-nonce-values-source-notes-or-private-control-desk-payloads-in-normal-public-html`

- [x] T4: Local verification and review
goal: Confirm the preset layer is syntactically clean, scoped, documented, and free of unrelated Review data changes.
files: `inc/control-desk.php`, `inc/frontend.php`, `tests/theme-studio-review-single-controls.ps1`, `docs/staging/specs/2026-06-22-review-single-studio-presets.md`, `docs/staging/plans/2026-06-22-review-single-studio-presets.md`
acceptance: PHP lint passes for changed PHP files, `git diff --check` passes, contract test passes, and manual Praxis review finds no BLOCK items.
spec: `docs/staging/specs/2026-06-22-review-single-studio-presets.md#invariant-no-public-url-cpt-rewrite-schema-post-content-review-metadata-acf-field-trailer-spoiler-debrief-pair-it-with-where-to-watch-or-oscar-ledger-data-changes`
