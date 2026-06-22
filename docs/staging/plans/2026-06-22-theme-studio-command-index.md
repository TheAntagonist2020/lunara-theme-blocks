- [x] T1: Add command-index contract test
goal: Prove the Theme Studio command index contract before production code.
files: `tests/theme-studio-command-index.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-command-index.ps1` fails before implementation and passes after implementation.
spec: `docs/staging/specs/2026-06-22-theme-studio-command-index.md#test`

- [x] T2: Implement command-index helper and renderer
goal: Add the admin-only command index using existing Theme Studio anchors and public preview links without creating new save paths.
files: `inc/control-desk.php`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-command-index.ps1`; `php -l inc/control-desk.php`
spec: `docs/staging/specs/2026-06-22-theme-studio-command-index.md#contract`

- [x] T3: Style the command index inside Control Desk
goal: Make the index scan like a compact publication-grade cockpit, not a generic settings list.
files: `assets/css/lunara-control-desk.css`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-command-index.ps1`
spec: `docs/staging/specs/2026-06-22-theme-studio-command-index.md#interface`

- [x] T4: Verify locally
goal: Run contract tests, PHP lint, CSS/source checks, and whitespace checks before deployment.
files: `inc/control-desk.php`, `assets/css/lunara-control-desk.css`, `tests/theme-studio-command-index.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-command-index.ps1`; `php -l inc/control-desk.php`; `git diff --check`
spec: `docs/staging/specs/2026-06-22-theme-studio-command-index.md#test`

- [ ] T5: Deploy, verify, and preserve continuity
goal: Deploy only changed theme files, flush cache, smoke public routes, verify admin render, update continuity docs, and push the repo.
files: `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\LUNARA_WORLD_CHANGELOG.md`, `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\09_DOCS_AND_NOTES\LUNARA_WEBSITE_HANDOFF.md`, `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\09_DOCS_AND_NOTES\SESSION-LOG-2026-06-22.md`
acceptance: Local/remote hashes match; `/`, `/journal/`, `/reviews/`, `/review/sinners-2025/`, `/reviews/sinners-2025/`, and `/oscars/` return 200; sampled public HTML contains no command-index/admin leakage; evidence is saved under `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\10_VISUAL_EVIDENCE`.
spec: `docs/staging/specs/2026-06-22-theme-studio-command-index.md#test`
