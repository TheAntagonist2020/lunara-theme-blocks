# Reviews Archive Studio Density Controls

## Goal

Give Dalton direct Theme Studio control over the public Reviews archive rhythm without changing review data, URLs, queries, sort links, pagination, or the native dynamic rail behavior.

This is the next step in turning Lunara's route families into controlled visual systems: Reviews should feel like a publication desk with adjustable density, not a fixed template that requires code edits for every spacing or prominence complaint.

## Scope

- Add a `Reviews Archive Studio` panel inside `Lunara > Theme Studio`.
- Store controls as bounded theme mods.
- Render public CSS variables only on the Reviews archive/page surface.
- Preserve the existing Reviews archive shell and dynamic rail markup.
- Preserve the shared Lunara typography system.

## Controls

### Select Controls

- `lunara_reviews_archive_density`
  - `compact`
  - `editorial`
  - `showcase`
- `lunara_reviews_archive_lead_prominence`
  - `restrained`
  - `standard`
  - `feature`
- `lunara_reviews_archive_rail_density`
  - `compact`
  - `editorial`
  - `showcase`

### Numeric Controls

- `lunara_reviews_archive_section_gap`
  - default `40`
  - min `20`
  - max `90`
- `lunara_reviews_archive_lead_min_height`
  - default `460`
  - min `340`
  - max `640`
- `lunara_reviews_archive_card_min_height`
  - default `360`
  - min `260`
  - max `540`
- `lunara_reviews_archive_compact_media_width`
  - default `116`
  - min `92`
  - max `150`

## Admin Surface

- The panel lives after Homepage Studio and before Image Quality Console.
- Saving requires `edit_theme_options`.
- Saving uses `admin-post.php` and a nonce-protected action.
- Invalid select values fall back to defaults.
- Numeric values are clamped server-side.
- Reset checkboxes remove the specific theme mod without affecting other controls.
- Preview links point to `/reviews/` desktop and `/reviews/?lunara-width=390`.
- No raw CSS textarea ships in this pass.

## Public Surface

- Public CSS is scoped to:
  - `body.post-type-archive-review .lunara-review-archive-page`
  - `body.page-template-page-reviews .lunara-review-archive-page`
- CSS emits variables for:
  - section gap
  - run/shell gap
  - lead minimum height
  - lead media width
  - lead copy padding
  - card minimum height
  - compact media width
  - rail gap
  - excerpt clamp
- The archive remains readable at 390px, 768px, and 1280px with no horizontal overflow.

## Non-Goals

- No custom post type changes.
- No query, pagination, sort, or URL changes.
- No review content migration.
- No third-party carousel plugin installation.
- No raw per-page CSS editor.
- No changes to Review single pages.

## Acceptance

- `tests/theme-studio-reviews-archive-density.ps1` passes.
- PHP lint passes for changed theme files.
- `git diff --check` passes.
- Theme Studio shows Reviews Archive Studio.
- Public Reviews archive CSS reads the new theme mods and emits scoped variables.
- `/reviews/` keeps exactly one H1 and the existing sort, rail, and pagination behavior.
