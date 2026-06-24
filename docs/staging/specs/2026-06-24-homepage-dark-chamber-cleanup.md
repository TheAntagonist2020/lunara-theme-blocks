# Homepage Text-Led Card Chamber Cleanup

## Goal

The homepage should keep its tightened publication rhythm without forcing text-led Review or Journal cards into oversized empty visual chambers. Cards without usable media should read as intentional editorial briefs, not broken image slots or dark blank panels.

## Contract

- Keep the existing homepage architecture, route order controls, typography, public URLs, Review query, Journal query, Oscar Picks, and Oscar Facts behavior intact.
- Preserve poster-led treatment for cards that actually have verified visual media.
- Compact `has-no-visual` Latest Reviews cards on the homepage:
  - no visible empty poster chamber,
  - no forced full-height card stretch,
  - text content starts high and reads as a deliberate brief.
- Compact `has-no-visual` Journal cards on the homepage:
  - no visible empty media chamber,
  - no forced full-height card stretch,
  - lead text-only cards remain denser than visual lead cards.
- Tighten the Oscar Facts section header band so it reads as a command strip before the carousel rather than a large dark framed chamber.
- Keep all cleanup scoped to `body.home`.
- Do not introduce new settings, new database fields, raw CSS controls, third-party carousel code, or unrelated route changes.
- Do not introduce a new font family.

## Non-Goals

- No ACF Pro integration in this pass.
- No Elementor integration.
- No homepage redesign.
- No image sourcing/importing.
- No changes to post content, meta, categories, or sort behavior.

## Verification

- Focused PowerShell contract test confirms the named CSS emitter, front-page scope, no-visual card compaction, hidden empty media guards, and no typography/settings drift.
- Existing homepage mobile runway tests continue to pass.
- PHP lint changed theme PHP files.
- `git diff --check` passes.
- Live homepage visual QA at 390, 768, and 1280 confirms no horizontal overflow, one H1, no broken images, and no visible empty homepage media chambers.
