- [x] T1: Add failing comparison-strip contract test.
goal: Prove the Utility Search preset comparison renderer and admin placement are missing.
files: `tests/theme-studio-utility-search-preset-comparison.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests\theme-studio-utility-search-preset-comparison.ps1` fails for the expected missing renderer/markup.
spec: `docs/staging/specs/2026-06-22-utility-search-preset-comparison-strip.md#utility-search-preset-comparison-strip`

- [x] T2: Render the admin-only comparison strip.
goal: Add a read-only, normalized preset comparison strip sourced from the existing Utility Search preset specs.
files: `inc/control-desk.php`, `assets/css/lunara-control-desk.css`
acceptance: Focused comparison-strip contract passes, plus `php -l inc\control-desk.php`.
spec: `docs/staging/specs/2026-06-22-utility-search-preset-comparison-strip.md#utility-search-preset-comparison-strip`

- [x] T3: Verify, deploy, document, and push.
goal: Ship the comparison strip with lint, cache flush, admin/public smoke, evidence, continuity docs, and GitHub commits.
files: `LUNARA_WORLD_CHANGELOG.md`, `09_DOCS_AND_NOTES\SESSION-LOG-2026-06-22.md`, `09_DOCS_AND_NOTES\LUNARA_WEBSITE_HANDOFF.md`
acceptance: Public route smoke confirms no new public query behavior or private/admin leakage; Theme Studio evidence is saved under `10_VISUAL_EVIDENCE`.
spec: `docs/staging/specs/2026-06-22-utility-search-preset-comparison-strip.md#utility-search-preset-comparison-strip`
