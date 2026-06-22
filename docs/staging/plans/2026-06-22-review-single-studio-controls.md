# Review Single Studio Controls Plan

spec: `docs/staging/specs/2026-06-22-review-single-studio-controls.md`

- [x] T1: Add contract test
goal: Verify the Review Single Studio contract before production changes.
files: `tests/theme-studio-review-single-controls.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-review-single-controls.ps1` fails for missing Review Single Studio implementation markers.
spec: `docs/staging/specs/2026-06-22-review-single-studio-controls.md#test-a-contract-test-fails-first-until-the-review-single-studio-setting-specs-save-handler-admin-render-hook-frontend-css-hook-no-raw-css-guarantee-and-fixed-follow-guardrail-are-present`

- [x] T2: Add private Theme Studio controls
goal: Render and save bounded Review Single Studio controls in `Lunara > Theme Studio`.
files: `inc/control-desk.php`, `tests/theme-studio-review-single-controls.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-review-single-controls.ps1` passes its admin-control, sanitizer, nonce, capability, and no-raw-CSS checks.
spec: `docs/staging/specs/2026-06-22-review-single-studio-controls.md#contract-add-a-private-review-single-studio-panel-inside-lunara-theme-studio-that-lets-an-administrator-tune-the-public-single-review-package-without-editing-code-raw-css-review-post-content-or-review-metadata`

- [x] T3: Emit scoped public review CSS controls
goal: Apply the saved controls to single Review pages through bounded, scoped frontend CSS.
files: `inc/frontend.php`, `tests/theme-studio-review-single-controls.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-review-single-controls.ps1` passes its frontend hook, `body.single-review` scope, bounded CSS variable, and disabled fixed-follow checks.
spec: `docs/staging/specs/2026-06-22-review-single-studio-controls.md#contract-the-public-output-is-scoped-to-bodysingle-review-and-emitted-through-the-theme-frontend-layer-as-css-variablesclasses-it-must-not-emit-private-admin-state-source-notes-nonce-values-internal-control-labels-or-setting-payloads-into-public-html`

- [x] T4: Local verification
goal: Confirm the theme remains syntactically clean and the changed files match the spec.
files: `inc/control-desk.php`, `inc/frontend.php`, `docs/staging/specs/2026-06-22-review-single-studio-controls.md`, `docs/staging/plans/2026-06-22-review-single-studio-controls.md`
acceptance: PHP lint passes for changed PHP files, `git diff --check` passes, and the contract test passes.
spec: `docs/staging/specs/2026-06-22-review-single-studio-controls.md#test-php-lint-passes-for-changed-php-files`

- [x] T5: Review implementation
goal: Confirm the change matches the spec, has no unrelated scope drift, and keeps Review content/data untouched.
files: `inc/control-desk.php`, `inc/frontend.php`, `tests/theme-studio-review-single-controls.ps1`
acceptance: Manual review finds no BLOCK items under the Praxis review checklist.
spec: `docs/staging/specs/2026-06-22-review-single-studio-controls.md#invariant-do-not-change-public-urls-cpt-registration-rewrite-rules-database-schema-review-post-content-review-metadata-keys-acf-fields-trailer-urls-spoiler-mode-metadata-debrief-data-pair-it-with-data-where-to-watch-source-data-or-oscar-ledger-matching`
