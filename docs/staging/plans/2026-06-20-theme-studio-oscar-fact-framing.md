- [x] T1: Add Theme Studio Oscar Fact framing contract
  goal: Prove Theme Studio exposes Oscar Fact visual treatment and focus controls through the Image Quality Console.
  files: `tests/theme-studio-oscar-fact-framing.ps1`
  acceptance: `powershell -ExecutionPolicy Bypass -File tests\theme-studio-oscar-fact-framing.ps1` fails before production code and passes after implementation.
  spec: `docs/staging/specs/2026-06-20-theme-studio-oscar-fact-framing.md#theme-studio-oscar-fact-framing-controls`

- [x] T2: Save Oscar Fact focus from Theme Studio
  goal: Extend the existing image-source save path to persist sanitized Oscar Fact focus metadata.
  files: `inc/control-desk.php`
  acceptance: PHP lint plus `powershell -ExecutionPolicy Bypass -File tests\theme-studio-oscar-fact-framing.ps1`
  spec: `docs/staging/specs/2026-06-20-theme-studio-oscar-fact-framing.md#theme-studio-oscar-fact-framing-controls`

- [x] T3: Render compact framing controls in the console
  goal: Add the focus selector and clear visual hierarchy to Oscar Fact rows without disturbing Review or Journal image-source controls.
  files: `inc/control-desk.php`, `assets/css/lunara-control-desk.css`
  acceptance: PHP lint, JS/CSS contract checks, and admin render markers for verified/treatment/focus controls.
  spec: `docs/staging/specs/2026-06-20-theme-studio-oscar-fact-framing.md#theme-studio-oscar-fact-framing-controls`

- [x] T4: Deploy, verify, and record continuity
  goal: Deploy only changed runtime files, flush cache, verify public routes, capture evidence, and push the theme repo.
  files: `LUNARA_WORLD_CHANGELOG.md`, `09_DOCS_AND_NOTES/LUNARA_WEBSITE_HANDOFF.md`, `09_DOCS_AND_NOTES/SESSION-LOG-2026-06-20.md`
  acceptance: local/remote hashes match, public routes return `200`, no sampled public admin leakage, evidence is under `10_VISUAL_EVIDENCE`, and the theme repo is pushed.
  spec: `docs/staging/specs/2026-06-20-theme-studio-oscar-fact-framing.md#theme-studio-oscar-fact-framing-controls`
