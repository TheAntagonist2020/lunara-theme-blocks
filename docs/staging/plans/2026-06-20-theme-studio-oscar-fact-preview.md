- [x] T1: Add Theme Studio Oscar Fact preview contract
  goal: Prove Oscar Fact rows expose a compact wide/archive preview before production code changes.
  files: `tests/theme-studio-oscar-fact-preview.ps1`
  acceptance: `powershell -ExecutionPolicy Bypass -File tests\theme-studio-oscar-fact-preview.ps1` fails before implementation and passes after.
  spec: `docs/staging/specs/2026-06-20-theme-studio-oscar-fact-preview.md#theme-studio-oscar-fact-preview-swatches`

- [x] T2: Render the Oscar Fact preview pair
  goal: Add an admin-only helper that renders `Wide crop` and `Archival fit` previews only when an Oscar Fact row has an attachment.
  files: `inc/control-desk.php`
  acceptance: PHP lint and the preview contract test pass.
  spec: `docs/staging/specs/2026-06-20-theme-studio-oscar-fact-preview.md#theme-studio-oscar-fact-preview-swatches`

- [x] T3: Style the preview swatch
  goal: Give the preview pair restrained publication-grade styling, responsive fallback, and faithful crop/contain behavior.
  files: `assets/css/lunara-control-desk.css`
  acceptance: The wide preview uses `object-fit: cover`, the archival preview uses `object-fit: contain`, and the layout collapses cleanly on narrow admin widths.
  spec: `docs/staging/specs/2026-06-20-theme-studio-oscar-fact-preview.md#theme-studio-oscar-fact-preview-swatches`

- [x] T4: Deploy, verify, and record continuity
  goal: Deploy only changed runtime files, flush cache, verify admin/public markers, update continuity docs, and push the theme repo.
  files: `LUNARA_WORLD_CHANGELOG.md`, `09_DOCS_AND_NOTES/LUNARA_WEBSITE_HANDOFF.md`, `09_DOCS_AND_NOTES/SESSION-LOG-2026-06-20.md`
  acceptance: local/remote hashes match, public routes return `200`, no sampled public admin leakage, evidence is under `10_VISUAL_EVIDENCE`, and the theme repo is pushed.
  spec: `docs/staging/specs/2026-06-20-theme-studio-oscar-fact-preview.md#theme-studio-oscar-fact-preview-swatches`
