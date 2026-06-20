# Theme Studio Oscar Fact Framing Controls

contract: `Lunara > Theme Studio > Image Quality Console` exposes Oscar Fact visual framing controls for each Oscar Fact row.

contract: Oscar Fact rows can save the existing public visual metadata:
- `_lunara_fact_visual_verified`
- `_lunara_fact_visual_treatment`
- `_lunara_fact_visual_focus`

contract: Visual treatment values remain `wide` and `archival`.

contract: Visual focus values come from `lunara_oscar_fact_visual_focus_options()` and are sanitized by `lunara_sanitize_oscar_fact_visual_focus()`.

invariant: No new public route, CPT, taxonomy, database table, or URL behavior is introduced.

invariant: The public homepage Oscar Facts renderer remains the source of truth for how verified, treatment, and focus metadata render.

invariant: Empty Oscar Fact image source saves clear verified, treatment, and focus metadata so no stale public framing setting survives after an image is removed.

security: Saving remains admin-only through `admin-post.php`, `edit_theme_options`, `edit_post`, nonce verification, surface validation, post-type validation, and image-MIME validation.

test: A contract test fails until Theme Studio can render and save Oscar Fact focus metadata from the Image Quality Console.

test: PHP lint passes for changed theme PHP files.

test: Public smoke routes return `200` and sampled public HTML shows no Control Desk/admin leakage.

deferred: This pass does not add drag-based focal-point picking, live iframe previews, or bulk Oscar Fact framing actions.

## Working notes

- Parasite `31320` proved the need: the exact image needs `archival` treatment plus `center-low` focus.
- The Oscar Fact editor already owns these fields; Theme Studio should expose the same controls where image curation already happens.
