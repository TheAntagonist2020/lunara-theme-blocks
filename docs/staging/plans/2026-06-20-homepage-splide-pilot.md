- [x] T1: Add scoped Splide contract test
  goal: Prove the theme only vendors and enqueues Splide for the homepage pilot.
  files: `tests/homepage-splide-pilot.ps1`
  acceptance: `powershell -ExecutionPolicy Bypass -File tests\homepage-splide-pilot.ps1` fails before implementation and passes after implementation.
  spec: `docs/staging/specs/2026-06-20-homepage-splide-pilot.md#contract-homepage-oscar-facts-may-opt-into-a-theme-owned-splide-pilot-through-scoped-front-page-assets-only`

- [x] T2: Vendor Splide and add Lunara initializer
  goal: Add Splide 4.1.3 production files and a Lunara-owned wrapper initializer without importing plugin archive assets.
  files: `assets/vendor/splide/`, `assets/js/lunara-splide-pilot.js`
  acceptance: `powershell -ExecutionPolicy Bypass -File tests\homepage-splide-pilot.ps1`
  spec: `docs/staging/specs/2026-06-20-homepage-splide-pilot.md#contract-splide-production-files-live-under-assetsvendorsplide-with-the-mit-license-retained-no-third-party-carousel-plugin-assets-are-installed-or-enqueued`

- [x] T3: Scope homepage enqueue and presentation
  goal: Enqueue the Splide pilot only on the homepage and keep the existing carousel as fallback.
  files: `inc/frontend.php`
  acceptance: PHP lint changed theme files and `powershell -ExecutionPolicy Bypass -File tests\homepage-splide-pilot.ps1`
  spec: `docs/staging/specs/2026-06-20-homepage-splide-pilot.md#invariant-the-homepage-remains-usable-if-splide-fails-to-load-the-existing-lunara-carousel-markup-remains-in-place-as-fallback`

- [x] T4: Verify, deploy, and record continuity
  goal: Verify homepage behavior locally and live, then update continuity docs and push the theme repo.
  files: `LUNARA_WORLD_CHANGELOG.md`, `09_DOCS_AND_NOTES/LUNARA_WEBSITE_HANDOFF.md`, `09_DOCS_AND_NOTES/SESSION-LOG-2026-06-20.md`
  acceptance: Public smoke routes return 200, cache is flushed, screenshots/evidence live under `10_VISUAL_EVIDENCE`, and the theme repo is pushed.
  spec: `docs/staging/specs/2026-06-20-homepage-splide-pilot.md#test-homepage-visual-qa-at-390-768-and-1280-confirms-no-horizontal-overflow-visible-oscar-facts-slides-controlsdots-present-and-no-admincontrol-desk-leakage`

Notes:
- The pilot also added per-fact visual framing controls for Oscar Facts: public visual treatment (`wide` or `archival`) and crop focus.
- Oscar Fact `31320` (`Parasite`) is configured as `archival` with `center-low` focus so the full still reads as an intentional framed archival image instead of a cropped backdrop.
- The Splide wrapper now syncs track/list height to the active slide and clears the old homepage carousel `75vh` root height after initialization.
- Mobile controls use a two-row footer with dots above the arrows.
