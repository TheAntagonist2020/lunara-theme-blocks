# Review Pair It With Source Authority Plan

spec: `docs/staging/specs/2026-06-22-review-pair-it-with-source-authority.md`

- [x] T1: Add Pair It With source-authority contract
goal: Prove the private Image Quality pairing lane is absent before production code changes.
files: `tests/theme-studio-review-pair-it-with-source-authority.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests\theme-studio-review-pair-it-with-source-authority.ps1` fails for missing pairing surface, row builder, filter link, row details, and read-only contract markers.
spec: `docs/staging/specs/2026-06-22-review-pair-it-with-source-authority.md#review-pair-it-with-source-authority`

- [x] T2: Add read-only pairing source rows
goal: Build private Image Quality rows from Review Pair It With fields using the existing parser and warnings.
files: `inc/control-desk.php`, `tests/theme-studio-review-pair-it-with-source-authority.ps1`
acceptance: Focused contract confirms review-pairing surface support, row builder, parser reuse, state mapping, expected/resolved title metadata, poster preview handling, and no image-source mutation form for pairing rows.
spec: `docs/staging/specs/2026-06-22-review-pair-it-with-source-authority.md#review-pair-it-with-source-authority`

- [x] T3: Integrate lane into Image Quality Console
goal: Add Pair It With sources to filters, priority lanes, grouped output, and summary without changing public routes.
files: `inc/control-desk.php`, `assets/css/lunara-control-desk.css`, `tests/theme-studio-review-pair-it-with-source-authority.ps1`
acceptance: Focused contract confirms filter URLs, priority lane label/count, row rendering classes, metadata labels, and private-only/read-only controls.
spec: `docs/staging/specs/2026-06-22-review-pair-it-with-source-authority.md#review-pair-it-with-source-authority`

- [x] T4: Verify local green state
goal: Run focused and regression checks before deployment.
files: `inc/control-desk.php`, `inc/debrief.php`, `assets/css/lunara-control-desk.css`, `tests/theme-studio-review-pair-it-with-source-authority.ps1`
acceptance: Focused contract, Pair It With controls regression, Review Card Image Focus regression, PHP lint, and `git diff --check` pass.
spec: `docs/staging/specs/2026-06-22-review-pair-it-with-source-authority.md#review-pair-it-with-source-authority`

- [x] T5: Deploy, verify, document, and push
goal: Ship the private pairing source-authority lane with continuity and evidence.
files: `inc/control-desk.php`, `inc/debrief.php`, `assets/css/lunara-control-desk.css`, `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\LUNARA_WORLD_CHANGELOG.md`, `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\09_DOCS_AND_NOTES\SESSION-LOG-2026-06-22.md`, `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\09_DOCS_AND_NOTES\LUNARA_WEBSITE_HANDOFF.md`
acceptance: Remote PHP lint, local/remote SHA256 match, cache flush, admin render markers for the lane, public route smoke, no public admin/source leakage, continuity updates, and pushed theme commit.
spec: `docs/staging/specs/2026-06-22-review-pair-it-with-source-authority.md#review-pair-it-with-source-authority`
