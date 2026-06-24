# Homepage Oscar Picks Studio Controls Plan

Spec: `docs/staging/specs/2026-06-24-homepage-oscar-picks-studio-controls.md`

- [x] T1: Contract test
  goal: Prove Theme Studio and the public renderer expose bounded Oscar Picks controls.
  files: `G:\lunara-backups\work\lunara-theme-blocks-20260513-2300\tests\homepage-oscar-picks-studio-controls.ps1`
  acceptance: The test fails before production edits and passes after implementation.
  spec: `docs/staging/specs/2026-06-24-homepage-oscar-picks-studio-controls.md#test`

- [x] T2: Theme Studio and renderer implementation
  goal: Add bounded Oscar Picks controls to Homepage Studio and consume them in public rendering/CSS.
  files: `G:\lunara-backups\work\lunara-theme-blocks-20260513-2300\inc\control-desk.php`, `G:\lunara-backups\work\lunara-theme-blocks-20260513-2300\functions.php`, `G:\lunara-backups\work\lunara-theme-blocks-20260513-2300\inc\frontend.php`
  acceptance: Contract test, existing homepage rail tests, PHP lint, and `git diff --check` pass.
  spec: `docs/staging/specs/2026-06-24-homepage-oscar-picks-studio-controls.md#contract`

- [ ] T3: Deploy and verify
  goal: Deploy changed theme files, flush cache, smoke public routes, and capture homepage QA.
  files: `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\10_VISUAL_EVIDENCE\lunara-homepage-oscar-picks-studio-controls-20260624`, `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\09_DOCS_AND_NOTES`, `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\LUNARA_WORLD_CHANGELOG.md`
  acceptance: Local/remote hashes match, cache is flushed, public smoke returns 200, sampled homepage QA shows no overflow or private leakage, continuity docs are updated, and repo commits are pushed.
  spec: `docs/staging/specs/2026-06-24-homepage-oscar-picks-studio-controls.md#test`
