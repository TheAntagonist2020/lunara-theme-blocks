# Homepage Preset Apply Action Plan

spec: `docs/staging/specs/2026-06-23-homepage-preset-apply-action.md`

- [x] T1: Add focused regression coverage
goal: Prove the safe admin-only apply contract before production code.
files: `tests/theme-studio-homepage-preset-apply-action.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-homepage-preset-apply-action.ps1` fails until the apply field, button values, save-handler override, invalid-key guard, and notice exist.
spec: `docs/staging/specs/2026-06-23-homepage-preset-apply-action.md#homepage-preset-apply-action`

- [x] T2: Implement Homepage package apply behavior
goal: Make each comparison package set the existing Homepage Studio select/order controls through the existing save handler.
files: `inc/control-desk.php`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-homepage-preset-apply-action.ps1` and `php -l inc/control-desk.php` pass.
spec: `docs/staging/specs/2026-06-23-homepage-preset-apply-action.md#homepage-preset-apply-action`

- [x] T3: Style and verify the private action surface
goal: Make Apply Package buttons feel clear inside the private comparison strip without changing public CSS.
files: `assets/css/lunara-control-desk.css`
acceptance: Focused apply test, Homepage comparison/order/signature-density/responsive-order regressions, PHP lint, and `git diff --check` pass.
spec: `docs/staging/specs/2026-06-23-homepage-preset-apply-action.md#homepage-preset-apply-action`

- [ ] T4: Deploy with zero-downtime verification and continuity
goal: Ship only changed theme files with rollback, hashes, cache flush, public smoke, docs updates, and repo sync.
files: `inc/control-desk.php`, `assets/css/lunara-control-desk.css`, `LUNARA_WORLD_CHANGELOG.md`, `09_DOCS_AND_NOTES/LUNARA_WEBSITE_HANDOFF.md`, `09_DOCS_AND_NOTES/SESSION-LOG-2026-06-23.md`
acceptance: Remote hashes match local, cache is flushed, public smoke passes for `/`, `/journal/`, `/reviews/`, canonical Sinners Review, and `/oscars/`, no sampled public admin leakage, and commits are pushed.
spec: `docs/staging/specs/2026-06-23-homepage-preset-apply-action.md#homepage-preset-apply-action`
