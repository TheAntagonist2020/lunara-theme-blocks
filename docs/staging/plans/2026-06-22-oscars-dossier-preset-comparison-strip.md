- [x] T1: Add failing Oscars comparison-strip contract test.
goal: Prove the Oscars Dossier preset comparison renderer is missing.
files: `tests/theme-studio-oscars-dossier-preset-comparison.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests\theme-studio-oscars-dossier-preset-comparison.ps1` fails for the expected missing renderer/markup.
spec: `docs/staging/specs/2026-06-22-oscars-dossier-preset-comparison-strip.md#oscars-dossier-preset-comparison-strip`

- [x] T2: Render the admin-only Oscars comparison strip.
goal: Add a read-only, normalized Oscars Dossier preset comparison strip sourced from the existing Oscars preset specs.
files: `inc/control-desk.php`, `assets/css/lunara-control-desk.css`
acceptance: Focused comparison-strip contract passes, plus `php -l inc\control-desk.php`.
spec: `docs/staging/specs/2026-06-22-oscars-dossier-preset-comparison-strip.md#oscars-dossier-preset-comparison-strip`

- [ ] T3: Verify, deploy, document, and push.
goal: Ship the Oscars Dossier comparison strip with lint, cache flush, admin/public smoke, evidence, continuity docs, and GitHub commits.
files: `LUNARA_WORLD_CHANGELOG.md`, `09_DOCS_AND_NOTES\SESSION-LOG-2026-06-22.md`, `09_DOCS_AND_NOTES\LUNARA_WEBSITE_HANDOFF.md`
acceptance: Public route smoke confirms no public route behavior or private/admin leakage; Theme Studio evidence is saved under `10_VISUAL_EVIDENCE`.
spec: `docs/staging/specs/2026-06-22-oscars-dossier-preset-comparison-strip.md#oscars-dossier-preset-comparison-strip`
