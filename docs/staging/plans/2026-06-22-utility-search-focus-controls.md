# Utility Search Focus Controls Plan

## Tasks

- [x] T1: Add failing focus-control contract test.
goal: Prove the new bounded controls, Search display ordering hooks, 404 re-entry ordering, and scoped CSS are missing before implementation.
files: `tests/theme-studio-utility-search-focus-controls.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests\theme-studio-utility-search-focus-controls.ps1` fails for the expected missing contract.
spec: `docs/staging/specs/2026-06-22-utility-search-focus-controls.md#acceptance`

- [x] T2: Add Utility Search focus controls to Theme Studio.
goal: Extend the existing Utility Search Studio with bounded focus controls and save/default behavior.
files: `inc/control-desk.php`
acceptance: Focus contract passes for control definitions, nonce-protected save coverage, and no raw CSS textarea.
spec: `docs/staging/specs/2026-06-22-utility-search-focus-controls.md#controls`

- [x] T3: Apply focus controls to Search and 404 rendering.
goal: Reorder display-only Search groups/results and 404 actions from safe theme-mod values without changing queries or URLs.
files: `search.php`, `404.php`
acceptance: Focus contract passes for Search and 404 render hooks and PHP lint passes.
spec: `docs/staging/specs/2026-06-22-utility-search-focus-controls.md#public-surface`

- [x] T4: Add scoped public CSS for selected focus states.
goal: Make the selected Search/recovery focus visually legible while keeping CSS restricted to Search and 404.
files: `inc/frontend.php`
acceptance: Focus contract passes for scoped CSS variables/classes and `git diff --check` passes.
spec: `docs/staging/specs/2026-06-22-utility-search-focus-controls.md#public-surface`

- [x] T5: Verify, deploy, document, and push.
goal: Ship the focus controls with lint, local/remote hashes, cache flush, route smoke, visual evidence, continuity docs, and GitHub commits.
files: `LUNARA_WORLD_CHANGELOG.md`, `09_DOCS_AND_NOTES\SESSION-LOG-2026-06-22.md`, `09_DOCS_AND_NOTES\LUNARA_WEBSITE_HANDOFF.md`
acceptance: Public smoke and evidence confirm no overflow, one H1, no private leakage, and Utility CSS only on Search/404.
spec: `docs/staging/specs/2026-06-22-utility-search-focus-controls.md#acceptance`
