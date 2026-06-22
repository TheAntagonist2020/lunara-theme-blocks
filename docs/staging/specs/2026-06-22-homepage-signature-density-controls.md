# Homepage Signature Density Controls

## Goal

Homepage Studio should give Dalton direct, bounded control over the homepage signature lanes so the first public surface can stay dynamic without requiring code edits for every rhythm adjustment.

## Contract

- Add Homepage Studio select controls for:
  - Latest Reviews density.
  - Journal lane density.
  - Oscar Facts density.
- Each density control must use the safe `compact`, `editorial`, and `showcase` options.
- Add bounded numeric controls for:
  - Latest Reviews card minimum height.
  - Journal card minimum height.
  - Oscar Facts card minimum height.
- Store all controls as theme mods through the existing Homepage Studio save path.
- Render public CSS only on the homepage.
- Public CSS must emit bounded custom properties for the three lane card heights.
- Public CSS must tune card gap, line clamp, and lane rhythm from the selected density values.
- Keep the current Gutenberg homepage architecture, section order controls, Splide pilot, typography, public URLs, and image-quality guardrails intact.

## Non-Goals

- No custom-theme replacement.
- No database schema changes.
- No route or query changes.
- No raw CSS textarea.
- No changes to post content, Review cards, Journal entries, or Oscar Fact data.

## Verification

- Focused PowerShell contract test confirms the controls, save path, and public CSS hooks exist.
- PHP lint changed theme PHP files.
- `git diff --check` passes.
- Live homepage returns 200 and keeps no horizontal overflow at 390, 768, and 1280.
- Public routes contain no Control Desk/admin leakage.
