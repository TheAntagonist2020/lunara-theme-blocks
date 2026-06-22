- [x] T1: Add responsive order contract
  goal: Lock the desktop/mobile order contract before production changes.
  files: `docs/staging/specs/2026-06-22-homepage-studio-responsive-order.md`, `tests/theme-studio-homepage-responsive-order.ps1`
  acceptance: Responsive order test fails before implementation and passes after separate desktop/mobile order support exists.
  spec: `docs/staging/specs/2026-06-22-homepage-studio-responsive-order.md#contract-homepage-studio-presets-control-desktop-and-mobile-homepage-section-order-separately`

- [x] T2: Add mobile order helper and CSS output
  goal: Preserve desktop order while applying a separate mobile order map at the homepage mobile breakpoint.
  files: `functions.php`, `header.php`, `inc/helpers.php`
  acceptance: `functions.php` reads `lunara_home_section_mobile_order`; `header.php` emits desktop and mobile `.lunara-home-slot-*` order rules from the sanitized maps.
  spec: `docs/staging/specs/2026-06-22-homepage-studio-responsive-order.md#contract-desktop-order-continues-to-write-lunara_home_section_order-mobile-order-writes-the-new-sanitized-lunara_home_section_mobile_order`

- [x] T3: Wire Homepage Studio presets to both orders
  goal: Make the existing preset cards save both desktop and mobile order strings and preview the actual responsive flow.
  files: `inc/control-desk.php`, `assets/css/lunara-control-desk.css`
  acceptance: Saving Homepage Studio writes `lunara_home_section_order` and `lunara_home_section_mobile_order`; UI copy no longer says per-breakpoint ordering is deferred.
  spec: `docs/staging/specs/2026-06-22-homepage-studio-responsive-order.md#contract-presets-keep-the-same-three-public-choices-editorial-default-journal-first-and-oscars-forward`

- [x] T4: Verify locally
  goal: Prove the change is syntactically safe and covered by focused regression checks.
  files: `functions.php`, `header.php`, `inc/helpers.php`, `inc/control-desk.php`, `assets/css/lunara-control-desk.css`
  acceptance: PHP lint passes, Homepage Studio tests pass, and `git diff --check` is clean.
  spec: `docs/staging/specs/2026-06-22-homepage-studio-responsive-order.md#test-powershell-executionpolicy-bypass-file-teststheme-studio-homepage-responsive-orderps1`

- [x] T5: Deploy, QA, and record continuity
  goal: Deploy only changed theme files, flush cache, verify public routes/responsive homepage behavior, update continuity docs, and push the theme repo.
  files: `LUNARA_WORLD_CHANGELOG.md`, `09_DOCS_AND_NOTES/LUNARA_WEBSITE_HANDOFF.md`, active session log
  acceptance: Local/remote hashes match, public smoke routes return 200, responsive evidence lives under `10_VISUAL_EVIDENCE`, and the theme repo is pushed.
  spec: `docs/staging/specs/2026-06-22-homepage-studio-responsive-order.md#test-journal-reviews-reviewssinners-2025-and-oscars-return-200-after-deploy-with-no-control-deskadmin-leakage`
