# Review Pair It With Controls Plan

spec: `docs/staging/specs/2026-06-22-review-pair-it-with-controls.md`

- [x] T1: Add Pair It With control contract test
goal: Prove the new Review Single Studio Pair It With precision layer is absent before production code changes.
files: `tests/theme-studio-review-pair-it-with-controls.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests\theme-studio-review-pair-it-with-controls.ps1` fails for missing Pair It With setting specs/render/CSS hooks.
spec: `docs/staging/specs/2026-06-22-review-pair-it-with-controls.md#review-pair-it-with-controls`

- [x] T2: Add bounded Theme Studio controls
goal: Extend the existing Review Single Studio form with Pair It With layout, column, thumb, text-depth, mobile-stack, and image-focus controls.
files: `inc/control-desk.php`, `tests/theme-studio-review-pair-it-with-controls.ps1`
acceptance: Focused contract test confirms setting specs, preset integration, admin render markers, nonce/capability save path, clamped numbers, and no raw CSS textarea.
spec: `docs/staging/specs/2026-06-22-review-pair-it-with-controls.md#review-pair-it-with-controls`

- [x] T3: Add scoped frontend Pair It With CSS
goal: Emit sanitized Pair It With geometry variables/classes on Review single pages without changing Review/Debrief data.
files: `inc/frontend.php`, `tests/theme-studio-review-pair-it-with-controls.ps1`
acceptance: Focused contract test confirms public CSS variables, scoped selectors, preview reuse, mobile stack behavior hooks, image focus, and no non-Review CSS leakage markers.
spec: `docs/staging/specs/2026-06-22-review-pair-it-with-controls.md#review-pair-it-with-controls`

- [x] T4: Verify local green state
goal: Run focused and regression checks before deployment.
files: `inc/control-desk.php`, `inc/frontend.php`, `tests/theme-studio-review-pair-it-with-controls.ps1`
acceptance: Focused contract, Review Single controls regression, Review Single preset-comparison regression, Review Card Image Focus regression, PHP lint, and `git diff --check` pass.
spec: `docs/staging/specs/2026-06-22-review-pair-it-with-controls.md#review-pair-it-with-controls`

- [x] T5: Deploy, verify, document, and push
goal: Ship the Pair It With precision controls live with evidence and continuity.
files: `inc/control-desk.php`, `inc/frontend.php`, `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\LUNARA_WORLD_CHANGELOG.md`, `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\09_DOCS_AND_NOTES\SESSION-LOG-2026-06-22.md`, `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\09_DOCS_AND_NOTES\LUNARA_WEBSITE_HANDOFF.md`
acceptance: Remote PHP lint, local/remote SHA256 match, cache flush, public route smoke, responsive Sinners/Bugonia evidence at `390`, `768`, and `1280`, no admin leakage, no overflow, continuity updates, and pushed theme commit.
spec: `docs/staging/specs/2026-06-22-review-pair-it-with-controls.md#review-pair-it-with-controls`
