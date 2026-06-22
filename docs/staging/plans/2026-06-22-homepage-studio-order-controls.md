- [x] T1: Add Homepage Studio order-control contract
  goal: Lock the safe control contract before production changes.
  files: `tests/theme-studio-homepage-order-controls.ps1`
  acceptance: Test fails before implementation and passes after the Theme Studio order preset UI/save path exists.
  spec: `docs/staging/specs/2026-06-22-homepage-studio-order-controls.md#contract-homepage-studio-exposes-bounded-section-order-presets-instead-of-requiring-raw-customizer-slug-editing`

- [x] T2: Add bounded preset helpers and save path
  goal: Add a small preset API that maps user-friendly choices to sanitized homepage section order strings.
  files: `inc/control-desk.php`
  acceptance: `lunara_home_section_order_preset` is saved and `lunara_home_section_order` receives the derived sanitized order.
  spec: `docs/staging/specs/2026-06-22-homepage-studio-order-controls.md#contract-the-preset-source-of-truth-is-stored-in-lunara_home_section_order_preset-and-saving-a-preset-writes-a-sanitized-derived-lunara_home_section_order-string`

- [x] T3: Render order presets in Homepage Studio
  goal: Replace the read-only order preview with selectable preset cards plus current desktop/mobile previews.
  files: `inc/control-desk.php`, `assets/css/lunara-control-desk.css`
  acceptance: Theme Studio shows Editorial default, Journal first, and Oscars forward with no raw CSS or freeform slug textarea.
  spec: `docs/staging/specs/2026-06-22-homepage-studio-order-controls.md#contract-presets-include-editorial-default-journal-first-and-oscars-forward-with-clear-desk-facing-copy-explaining-what-each-affects`

- [x] T4: Verify locally and prepare deploy
  goal: Prove the change is syntactically safe and does not disturb public routing.
  files: `inc/control-desk.php`, `assets/css/lunara-control-desk.css`
  acceptance: PHP lint passes, contract test passes, and `git diff --check` is clean.
  spec: `docs/staging/specs/2026-06-22-homepage-studio-order-controls.md#test-powershell-executionpolicy-bypass-file-teststheme-studio-homepage-order-controlssps1`

- [ ] T5: Deploy, smoke test, and record continuity
  goal: Deploy only changed theme files, flush cache, verify public routes, update continuity docs, and push the theme repo.
  files: `LUNARA_WORLD_CHANGELOG.md`, `09_DOCS_AND_NOTES/LUNARA_WEBSITE_HANDOFF.md`, active session log
  acceptance: Local/remote hashes match, public smoke routes return 200, evidence lives under `10_VISUAL_EVIDENCE`, and the theme repo is pushed.
  spec: `docs/staging/specs/2026-06-22-homepage-studio-order-controls.md#test-journal-reviews-reviewssinners-2025-and-oscars-return-200-after-deploy-with-no-control-deskadmin-leakage`
