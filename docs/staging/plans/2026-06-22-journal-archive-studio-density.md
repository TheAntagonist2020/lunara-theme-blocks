- [x] T1: Add Journal Archive Studio contract test
goal: Prove the admin controls and public CSS contract before production code.
files: `tests/theme-studio-journal-archive-density.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-journal-archive-density.ps1` fails before implementation and passes after implementation.
spec: `docs/staging/specs/2026-06-22-journal-archive-studio-density.md#controls`

- [x] T2: Implement admin controls
goal: Add the Theme Studio Journal Archive Studio panel, save handler, sanitized select helpers, clamped numeric helpers, preview links, and notices.
files: `inc/control-desk.php`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-journal-archive-density.ps1`
spec: `docs/staging/specs/2026-06-22-journal-archive-studio-density.md#contract`

- [x] T3: Implement scoped public CSS
goal: Apply Journal archive density and geometry through bounded CSS variables without touching archive queries or content.
files: `inc/frontend.php`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-journal-archive-density.ps1`; `php -l inc/frontend.php`
spec: `docs/staging/specs/2026-06-22-journal-archive-studio-density.md#invariant`

- [x] T4: Verify and ship locally
goal: Run contract tests, PHP lint, and whitespace checks before deployment.
files: `inc/control-desk.php`, `inc/frontend.php`, `tests/theme-studio-journal-archive-density.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-journal-archive-density.ps1`; `php -l inc/control-desk.php`; `php -l inc/frontend.php`; `git diff --check`
spec: `docs/staging/specs/2026-06-22-journal-archive-studio-density.md#test`

- [x] T5: Add media failure guard
goal: Prevent failed Journal archive images from leaving visible blank chambers on public cards.
files: `inc/frontend.php`, `tests/theme-studio-journal-archive-density.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-journal-archive-density.ps1`; browser verification reports zero visible blank media slots.
spec: `docs/staging/specs/2026-06-22-journal-archive-studio-density.md#invariant`

- [x] T6: Deploy and verify public Journal archive
goal: Deploy only changed theme files, flush cache, smoke public routes, capture responsive evidence, update continuity, and push the repo.
files: `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\LUNARA_WORLD_CHANGELOG.md`, `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\09_DOCS_AND_NOTES\LUNARA_WEBSITE_HANDOFF.md`, `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\09_DOCS_AND_NOTES\SESSION-LOG-2026-06-22.md`
acceptance: Local/remote hashes match; `/`, `/journal/`, `/reviews/`, `/review/sinners-2025/`, `/reviews/sinners-2025/`, and `/oscars/` return 200; 390/768/1280 evidence is saved under `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\10_VISUAL_EVIDENCE`.
spec: `docs/staging/specs/2026-06-22-journal-archive-studio-density.md#test`
