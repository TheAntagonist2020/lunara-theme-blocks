# Oscars Profile Image Controls

## Goal

Extend Oscars Dossier Studio so Dalton can tune title, person, and company profile image chambers directly from Theme Studio.

## Controls

- `lunara_oscars_profile_media_treatment`
  - Default: `poster-frame`
  - Allowed: `poster-frame`, `cinematic-crop`, `archival-fit`
  - Affects whether profile media reads as a poster/portrait chamber, a stronger cropped feature image, or an archival full-image chamber.
- `lunara_oscars_profile_media_width`
  - Default: `340`
  - Range: `220` to `520`
  - Unit: `px`
  - Affects the maximum width of the profile image chamber.
- `lunara_oscars_profile_media_height`
  - Default: `500`
  - Range: `320` to `700`
  - Unit: `px`
  - Affects the maximum height of the profile image chamber.

## Public Contract

- No public URL, post type, rewrite, query, or Oscar data changes.
- No plugin database/schema changes.
- Theme Studio remains admin-only and uses bounded theme mods.
- Public CSS remains scoped to Oscars profile surfaces.
- Existing image-focus control continues to drive `object-position`.
- Images must never stretch; changes must use `object-fit`, `aspect-ratio`, width, height, max-width, and max-height.

## Visual Contract

- `poster-frame` keeps the default premium title/person file image rhythm.
- `cinematic-crop` gives profile media more visual authority when a strong still or portrait can carry the chamber.
- `archival-fit` protects older or unusual source images from being cut off.
- Width and height controls must be bounded enough to avoid mobile overflow.
