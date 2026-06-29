# Lunara Image Guidelines — Hero & Backdrops

The rotating homepage hero now rightsizes and quality-gates every image
automatically, so a too-small or wrong-shape source can never be stretched into
a blurry full-bleed banner again. This doc records the rules the code enforces
and the spec to aim for when uploading.

## What the code does (automatic)

`lunara_rightsize_backdrop_url()` + `lunara_hero_image_qualifies()` in
`functions.php`:

1. **Uniform 16:9 crop, ~1600×900, ~220 KB.** Every hero image is downscaled
   through Site Accelerator (Jetpack Photon):
   - **TMDB backdrops** are pulled from the full-resolution `original` and
     downscaled (`https://i0.wp.com/image.tmdb.org/t/p/original/…?resize=1600,900`).
     This fixed the softness: the hero used to stretch the small `w1280` size
     across a full-width banner.
   - **Local uploads** get `?resize=1600,900&quality=86&ssl=1` on the site
     domain — the same path posters already use.
2. **Size floor (1280 px).** An image only joins the hero rotation if it is
   **≥ 1280 px wide AND landscape** (width ≥ height). Photon never *upscales*, so
   this floor is what actually keeps a 96×96 thumbnail out of the marquee.
3. **Backfill.** The builder over-queries (`$max + 6`) so any post dropped for an
   undersized image is replaced by the next-newest qualifying post — the
   rotation never comes up short.

Tunable via filters: `lunara_hero_min_width` (default `1280`) and
`lunara_hero_crop_dimensions` (default `'1600,900'`).

## Upload spec (aim for this)

| Use | Minimum | Ideal | Shape |
|-----|---------|-------|-------|
| Hero / backdrop (review & journal) | 1280 × 720 | **1920 × 1080** | 16:9 landscape |
| Poster | 1000 × 1500 | 1500 × 2000 | 2:3 portrait |

- **Reviews & journal entries that should appear in the hero need a landscape
  image ≥ 1280 px wide.** TMDB backdrops always qualify; a portrait poster or a
  small graphic will not.
- If a post is missing from the hero, its featured/backdrop image is too small
  or portrait — swap in a wider one (a TMDB backdrop is the easy win).

## Not yet built (optional follow-up)

An editor-side notice in the review/journal screen that flags when the chosen
hero image is below the floor, so you catch it before publishing rather than
noticing the post is absent from the rotation.
