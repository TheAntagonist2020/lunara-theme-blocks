- [x] T1: Add Theme Studio Oscar Fact focus-picker contract
  goal: Prove the console exposes a real clickable focus picker before production code changes.
  files: `tests/theme-studio-oscar-fact-focus-picker.ps1`
  acceptance: `powershell -ExecutionPolicy Bypass -File tests\theme-studio-oscar-fact-focus-picker.ps1` fails before implementation and passes after.
  spec: `docs/staging/specs/2026-06-20-theme-studio-oscar-fact-focus-picker.md#theme-studio-oscar-fact-visual-focus-picker`

- [x] T2: Render the 3x3 focus picker
  goal: Replace the Oscar Fact focus dropdown with a scoped radio grid that writes the existing focus field.
  files: `inc/control-desk.php`
  acceptance: PHP lint and the focus-picker contract test pass.
  spec: `docs/staging/specs/2026-06-20-theme-studio-oscar-fact-focus-picker.md#theme-studio-oscar-fact-visual-focus-picker`

- [x] T3: Style the picker
  goal: Make the picker readable, keyboard-safe, and compact inside the Image Quality Console.
  files: `assets/css/lunara-control-desk.css`
  acceptance: The selected state, focus-visible state, and 3x3 grid are present in scoped CSS.
  spec: `docs/staging/specs/2026-06-20-theme-studio-oscar-fact-focus-picker.md#theme-studio-oscar-fact-visual-focus-picker`

- [x] T4: Deploy, verify, and record continuity
  goal: Deploy only changed runtime files, flush cache, verify admin/public markers, update continuity docs, and push the theme repo.
  files: `LUNARA_WORLD_CHANGELOG.md`, `09_DOCS_AND_NOTES/LUNARA_WEBSITE_HANDOFF.md`, `09_DOCS_AND_NOTES/SESSION-LOG-2026-06-20.md`
  acceptance: local/remote hashes match, public routes return `200`, no sampled public admin leakage, evidence is under `10_VISUAL_EVIDENCE`, and the theme repo is pushed.
  spec: `docs/staging/specs/2026-06-20-theme-studio-oscar-fact-focus-picker.md#theme-studio-oscar-fact-visual-focus-picker`
