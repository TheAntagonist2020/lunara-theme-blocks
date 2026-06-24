# Homepage Oscar Picks Curation Console Plan

Spec: `docs/staging/specs/2026-06-24-homepage-oscar-picks-curation-console.md`

- [x] T1: Contract test
  goal: Prove the curation console, ordered theme mod, sanitization, and public fallback contract.
  files: `G:\lunara-backups\work\lunara-theme-blocks-20260513-2300\tests\homepage-oscar-picks-curation-console.ps1`
  acceptance: The test fails before runtime edits and passes after implementation.
  spec: `docs/staging/specs/2026-06-24-homepage-oscar-picks-curation-console.md#test`

- [x] T2: Admin curation console and public order consumption
  goal: Add the private Homepage Studio curation UI and make the public Oscar Picks rail honor the saved ordered set.
  files: `G:\lunara-backups\work\lunara-theme-blocks-20260513-2300\inc\control-desk.php`, `G:\lunara-backups\work\lunara-theme-blocks-20260513-2300\functions.php`, `G:\lunara-backups\work\lunara-theme-blocks-20260513-2300\assets\css\lunara-control-desk.css`
  acceptance: Focused contract, Oscar Picks controls regression, Oscar Picks rail regression, PHP lint, and `git diff --check` pass.
  spec: `docs/staging/specs/2026-06-24-homepage-oscar-picks-curation-console.md#contract`

- [ ] T3: Deploy and verify
  goal: Deploy changed runtime files, flush cache, smoke public routes, confirm admin markers, capture homepage QA, update continuity, and push the repo.
  files: `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\10_VISUAL_EVIDENCE\lunara-homepage-oscar-picks-curation-console-20260624`, `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\09_DOCS_AND_NOTES`, `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\LUNARA_WORLD_CHANGELOG.md`
  acceptance: Local/remote hashes match, public smoke returns 200, sampled public HTML contains no private leakage, and responsive QA shows no overflow or broken images.
  spec: `docs/staging/specs/2026-06-24-homepage-oscar-picks-curation-console.md#test`
