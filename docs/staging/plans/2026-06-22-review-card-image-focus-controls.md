# Review Card Image Focus Controls Plan

spec: `docs/staging/specs/2026-06-22-review-card-image-focus-controls.md`

- [x] T1: Add contract test
goal: Verify the Review card image-focus contract before production changes.
files: `tests/theme-studio-review-card-image-focus-controls.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-review-card-image-focus-controls.ps1` fails for missing focus specs, save/render paths, frontend CSS mapping, and no-empty-media guard markers.
spec: `docs/staging/specs/2026-06-22-review-card-image-focus-controls.md#test-a-contract-test-fails-first-until-the-focus-specs-value-validation-save-path-admin-render-path-public-css-hook-safe-object-position-mapping-no-raw-css-guarantee-and-no-empty-media-regression-guard-are-present`

- [x] T2: Add private Theme Studio focus controls
goal: Render and save bounded Review card image-focus controls in `Lunara > Theme Studio`.
files: `inc/control-desk.php`, `tests/theme-studio-review-card-image-focus-controls.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-review-card-image-focus-controls.ps1` passes its admin-control, sanitizer, nonce, capability, invalid-value fallback, preview-link, and no-raw-CSS checks.
spec: `docs/staging/specs/2026-06-22-review-card-image-focus-controls.md#contract-add-private-theme-studio-controls-that-let-an-administrator-tune-public-review-card-crop-focus-without-editing-raw-css-review-post-content-review-metadata-image-files-or-card-markup`

- [x] T3: Emit scoped public focus CSS
goal: Apply saved focus controls to Review card image surfaces through bounded `object-position` CSS.
files: `inc/frontend.php`, `inc/review-rendering.php`, `tests/theme-studio-review-card-image-focus-controls.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-review-card-image-focus-controls.ps1` passes its public CSS scope, safe value map, `3:4` preservation, and no-empty-media checks.
spec: `docs/staging/specs/2026-06-22-review-card-image-focus-controls.md#contract-the-public-output-is-scoped-to-review-card-surfaces-only`

- [x] T4: Local verification and regression sweep
goal: Confirm the theme remains syntactically clean and existing Theme Studio control surfaces still satisfy their contracts.
files: `inc/control-desk.php`, `inc/frontend.php`, `inc/review-rendering.php`, `tests/theme-studio-review-card-image-focus-controls.ps1`
acceptance: PHP lint passes for changed PHP files, `git diff --check` passes, and the existing Theme Studio regression tests for Review Single, Reviews Archive, Oscars Dossier, Oscars profile image controls, and Utility Search pass.
spec: `docs/staging/specs/2026-06-22-review-card-image-focus-controls.md#test-existing-theme-studio-regression-tests-continue-to-pass-for-review-single-reviews-archive-oscars-dossier-oscars-profile-image-controls-and-utility-search`

- [x] T5: Review implementation
goal: Confirm the change matches the spec, has no unrelated scope drift, and keeps Review data/source behavior untouched.
files: `inc/control-desk.php`, `inc/frontend.php`, `inc/review-rendering.php`, `tests/theme-studio-review-card-image-focus-controls.ps1`
acceptance: Manual review finds no BLOCK items under the Praxis review checklist.
spec: `docs/staging/specs/2026-06-22-review-card-image-focus-controls.md#invariant-do-not-change-public-urls-cpt-registration-rewrite-rules-database-schema-review-post-content-review-metadata-keys-acf-fields-trailer-urls-spoiler-mode-metadata-debrief-data-pair-it-with-data-where-to-watch-source-data-or-oscar-ledger-matching`
