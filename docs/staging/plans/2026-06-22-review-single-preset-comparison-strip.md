- [x] T1: Add failing Review comparison-strip contract test.
goal: Prove the Review Single preset comparison renderer and canonical preview-link cleanup are missing.
files: `tests/theme-studio-review-single-preset-comparison.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests\theme-studio-review-single-preset-comparison.ps1` fails for the expected missing renderer/markup.
spec: `docs/staging/specs/2026-06-22-review-single-preset-comparison-strip.md#review-single-preset-comparison-strip`

- [x] T2: Render the admin-only Review comparison strip.
goal: Add a read-only, normalized Review Single preset comparison strip sourced from the existing Review Single preset specs.
files: `inc/control-desk.php`, `assets/css/lunara-control-desk.css`
acceptance: Focused comparison-strip contract passes, plus `php -l inc\control-desk.php`.
spec: `docs/staging/specs/2026-06-22-review-single-preset-comparison-strip.md#review-single-preset-comparison-strip`

- [ ] T3: Verify, deploy, document, and push.
goal: Ship the Review Single comparison strip with lint, cache flush, admin/public smoke, evidence, continuity docs, and GitHub commits.
files: `LUNARA_WORLD_CHANGELOG.md`, `09_DOCS_AND_NOTES\SESSION-LOG-2026-06-22.md`, `09_DOCS_AND_NOTES\LUNARA_WEBSITE_HANDOFF.md`
acceptance: Public route smoke confirms no new public query behavior or private/admin leakage; Theme Studio evidence is saved under `10_VISUAL_EVIDENCE`.
spec: `docs/staging/specs/2026-06-22-review-single-preset-comparison-strip.md#review-single-preset-comparison-strip`
