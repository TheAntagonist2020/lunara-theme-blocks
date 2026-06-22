# Oscars Related Controls

## Goal

Extend Oscars Dossier Studio so Dalton can tune the public Oscars related-review lane and profile image framing without asking for code edits.

## Controls

- `lunara_oscars_related_reviews_treatment`
  - Default: `standard-grid`
  - Allowed: `standard-grid`, `compact-rail`, `feature-strip`
  - Affects the visual presentation of Oscars related-review cards on ceremony, category, title, and person/profile routes.
- `lunara_oscars_title_image_focus`
  - Default: `center-center`
  - Allowed: `center-center`, `center-top`, `center-bottom`, `left-center`, `right-center`
  - Affects title/profile/poster and related-review image object positioning where the theme controls presentation.
- `lunara_oscars_related_reviews_count`
  - Default: `6`
  - Range: `2` to `8`
  - Affects the number of related-review cards rendered by the Oscars plugin.

## Public Contract

- No URL, post type, query, rewrite, or Oscar result data changes.
- Theme Studio remains admin-only and uses bounded theme mods.
- Public CSS stays scoped to Oscars surfaces.
- The Oscars plugin must tolerate missing theme mods and fall back to the current behavior.
- Related-review cards must keep the existing media guard: no empty media wrappers or label-only placeholder chambers.

## Visual Contract

- `standard-grid` keeps the current publication-card rhythm.
- `compact-rail` tightens related reviews into a denser retention row.
- `feature-strip` gives the first related review more editorial weight without breaking the grid.
- Image focus applies with `object-position`, not image distortion.

