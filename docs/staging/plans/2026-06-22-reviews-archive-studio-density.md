# Reviews Archive Studio Density Controls Plan

## T1: Add Failing Contract

- [x] Create `tests/theme-studio-reviews-archive-density.ps1`.
- [x] Assert the Reviews Archive Studio controls exist in Control Desk.
- [x] Assert public CSS reads the new theme mods and emits scoped CSS variables.
- [x] Assert no raw CSS textarea is introduced.

## T2: Add Admin Controls

- [x] Add Reviews archive select and numeric specs.
- [x] Add value/clamp helpers.
- [x] Add nonce-protected save handler.
- [x] Add Theme Studio render panel with preview links.
- [x] Add saved/forbidden notices.

## T3: Add Scoped Public CSS

- [x] Read bounded Reviews archive settings in `inc/frontend.php`.
- [x] Emit CSS variables only on Reviews archive surfaces.
- [x] Apply variables to the existing hero, lead, support rail, and archive run.
- [x] Preserve mobile wrapping and dynamic rail behavior.

## T4: Verify And Ship

- [x] Run contract test.
- [x] Run PHP lint and `git diff --check`.
- [x] Deploy changed files only.
- [x] Flush cache and run public smoke checks.
- [x] Capture evidence under `10_VISUAL_EVIDENCE`.
- [ ] Update changelog, session log, handoff, commit, push, and then update the Control Desk source anchor.
