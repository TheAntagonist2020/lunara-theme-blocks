# Utility Search Preview Presets Plan

Spec: `docs/staging/specs/2026-06-22-utility-search-preview-presets.md`

- [x] T1: Add failing preset contract test.
goal: Prove Utility Search preset specs, preview URLs, admin-only frontend preview values, and Search/404 preview reads are missing.
files: `tests/theme-studio-utility-search-preview-presets.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests\theme-studio-utility-search-preview-presets.ps1` fails for the expected missing preset contract.
spec: `docs/staging/specs/2026-06-22-utility-search-preview-presets.md#test`

- [x] T2: Add Utility Search preset specs and admin cards.
goal: Render named Utility Search packages with apply buttons and request-only preview links in Theme Studio.
files: `inc/control-desk.php`
acceptance: Focused preset contract passes for admin spec/render/save behavior.
spec: `docs/staging/specs/2026-06-22-utility-search-preview-presets.md#admin-surface`

- [x] T3: Add admin-only frontend preview reads.
goal: Let Search, 404, and scoped utility CSS read preset values only for admins with theme editing permission.
files: `inc/frontend.php`, `search.php`, `404.php`
acceptance: Focused preset contract passes for admin-only preview guards and public route scoping.
spec: `docs/staging/specs/2026-06-22-utility-search-preview-presets.md#public-surface`

- [x] T4: Verify, deploy, document, and push.
goal: Ship the preset layer with lint, hashes, cache flush, route smoke, evidence, continuity docs, and GitHub commits.
files: `LUNARA_WORLD_CHANGELOG.md`, `09_DOCS_AND_NOTES\SESSION-LOG-2026-06-22.md`, `09_DOCS_AND_NOTES\LUNARA_WEBSITE_HANDOFF.md`
acceptance: Public smoke confirms anonymous preset query does not leak private controls, Utility CSS stays Search/404-only, and evidence is saved under `10_VISUAL_EVIDENCE`.
spec: `docs/staging/specs/2026-06-22-utility-search-preview-presets.md#test`
