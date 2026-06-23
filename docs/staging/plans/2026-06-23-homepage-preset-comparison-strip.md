# Homepage Preset Comparison Strip Plan

spec: `docs/staging/specs/2026-06-23-homepage-preset-comparison-strip.md`

- [x] T1: Add focused regression coverage
goal: Prove the Homepage Studio comparison strip contract before production code.
files: `tests/theme-studio-homepage-preset-comparison.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-homepage-preset-comparison.ps1` fails until the comparison helpers, render call, markers, preset labels, and no-save-handler guard exist.
spec: `docs/staging/specs/2026-06-23-homepage-preset-comparison-strip.md#homepage-preset-comparison-strip`

- [x] T2: Render the admin-only Homepage preset comparison strip
goal: Add the read-only comparison strip inside Homepage Studio using existing Theme Studio preset grammar.
files: `inc/control-desk.php`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-homepage-preset-comparison.ps1` passes and PHP lint passes for `inc/control-desk.php`.
spec: `docs/staging/specs/2026-06-23-homepage-preset-comparison-strip.md#homepage-preset-comparison-strip`

- [x] T3: Style and verify the private comparison surface
goal: Make the comparison strip scan cleanly inside the private Control Desk without public CSS or route behavior changes.
files: `assets/css/lunara-control-desk.css`
acceptance: `powershell -ExecutionPolicy Bypass -File tests/theme-studio-homepage-preset-comparison.ps1`, PHP lint, and `git diff --check` pass.
spec: `docs/staging/specs/2026-06-23-homepage-preset-comparison-strip.md#homepage-preset-comparison-strip`

- [x] T4: Deploy with zero-downtime verification and continuity
goal: Ship only changed theme files with rollback, hash verification, cache flush, public smoke tests, docs updates, and repo sync.
files: `inc/control-desk.php`, `assets/css/lunara-control-desk.css`, `LUNARA_WORLD_CHANGELOG.md`, `09_DOCS_AND_NOTES/LUNARA_WEBSITE_HANDOFF.md`, `09_DOCS_AND_NOTES/SESSION-LOG-2026-06-23.md`
acceptance: Remote hashes match local, cache is flushed, public smoke passes for `/`, `/journal/`, `/reviews/`, canonical Sinners Review, and `/oscars/`, no sampled public admin leakage, and commits are pushed.
spec: `docs/staging/specs/2026-06-23-homepage-preset-comparison-strip.md#homepage-preset-comparison-strip`
