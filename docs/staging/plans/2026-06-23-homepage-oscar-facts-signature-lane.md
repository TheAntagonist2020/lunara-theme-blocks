# Homepage Oscar Facts Signature Lane Plan

Spec: `docs/staging/specs/2026-06-23-homepage-oscar-facts-signature-lane.md`

- [x] T1: Add the signature-lane contract test
goal: Prove the Oscar Facts console, progress, state, and fallback contracts before runtime changes.
files: `tests/homepage-oscar-facts-signature-lane.ps1`
acceptance: `powershell -ExecutionPolicy Bypass -File tests\homepage-oscar-facts-signature-lane.ps1` fails before implementation and passes after implementation.
spec: `docs/staging/specs/2026-06-23-homepage-oscar-facts-signature-lane.md#homepage-oscar-facts-signature-lane`

- [x] T2: Add ledger-console markup and Splide state updates
goal: Render a compact public counter/progress console and keep it synchronized through the existing Splide pilot.
files: `functions.php`, `assets/js/lunara-splide-pilot.js`
acceptance: Focused contract plus `tests\homepage-splide-pilot.ps1` pass.
spec: `docs/staging/specs/2026-06-23-homepage-oscar-facts-signature-lane.md#homepage-oscar-facts-signature-lane`

- [x] T3: Polish the public Oscar Facts signature frame
goal: Make the existing homepage Oscar Facts lane feel denser, more premium, and mobile-safe without changing data or routes.
files: `inc/frontend.php`
acceptance: Focused contract passes and responsive homepage browser QA shows no overflow, broken images, empty media chambers, or private leakage.
spec: `docs/staging/specs/2026-06-23-homepage-oscar-facts-signature-lane.md#homepage-oscar-facts-signature-lane`

- [x] T4: Verify, deploy, and record continuity
goal: Deploy only changed theme files, verify live behavior, flush cache, capture evidence, update continuity, and push the theme repo.
files: `LUNARA_WORLD_CHANGELOG.md`, `09_DOCS_AND_NOTES/LUNARA_WEBSITE_HANDOFF.md`, `09_DOCS_AND_NOTES/SESSION-LOG-2026-06-23.md`
acceptance: Local/remote hashes match, public smoke passes, evidence is under `10_VISUAL_EVIDENCE`, and the theme repo is pushed.
spec: `docs/staging/specs/2026-06-23-homepage-oscar-facts-signature-lane.md#homepage-oscar-facts-signature-lane`
