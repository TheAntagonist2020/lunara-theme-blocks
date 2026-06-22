# Review Single Studio Controls

contract: Add a private `Review Single Studio` panel inside `Lunara > Theme Studio` that lets an administrator tune the public single-review package without editing code, raw CSS, Review post content, or Review metadata.

contract: The panel controls only presentation rhythm for `body.single-review` surfaces:
- overall review package density
- cinematic hero prominence
- right-rail behavior
- Debrief poster prominence
- Pair It With density
- spoiler shield treatment
- trailer module prominence
- related-review count and lane density
- section gap rhythm

contract: New settings are stored as bounded theme mods. Existing theme mods remain the source of truth where they already exist, including `lunara_review_related_count`, `lunara_review_show_where_card_default`, `lunara_review_show_details_card_default`, `lunara_review_where_kicker`, and related review labels.

contract: The first setting set is:
- `lunara_review_single_density`: `compact`, `editorial`, `feature`
- `lunara_review_single_hero_scale`: `standard`, `poster-forward`, `wide-forward`
- `lunara_review_single_rail_mode`: `balanced`, `minimal`, `metadata-forward`
- `lunara_review_single_debrief_prominence`: `standard`, `poster-forward`, `signature-forward`
- `lunara_review_single_pairing_density`: `compact`, `editorial`, `showcase`
- `lunara_review_single_spoiler_treatment`: `standard`, `shield-forward`, `high-contrast`
- `lunara_review_single_trailer_prominence`: `standard`, `centered`, `feature`
- `lunara_review_single_section_gap`: integer `24` through `96`
- `lunara_review_single_debrief_poster_width`: integer `220` through `420`
- `lunara_review_related_count`: integer `2` through `6`

contract: The admin form uses `edit_theme_options`, a nonce-protected `admin-post.php` save action, sanitized select values, clamped integer values, and existing Control Desk notice behavior.

contract: The public output is scoped to `body.single-review` and emitted through the theme frontend layer as CSS variables/classes. It must not emit private admin state, source notes, nonce values, internal control labels, or setting payloads into public HTML.

contract: Preview links in the panel include desktop and 390px mobile links for:
- `/reviews/sinners-2025/`
- `/reviews/bugonia-the-full-spoiler/`
- `/reviews/`

invariant: Do not change public URLs, CPT registration, rewrite rules, database schema, Review post content, Review metadata keys, ACF fields, trailer URLs, spoiler-mode metadata, Debrief data, Pair It With data, Where to Watch source data, or Oscar ledger matching.

invariant: Full-spoiler reviews continue to wrap the body, Debrief, and related modules with the existing protected-content attributes and only reveal through the existing spoiler mechanism.

invariant: Trailer modules continue to follow the existing placement contract from `lunara_get_trailer_placement()`, shortcodes, and `lunara_insert_trailer_into_content_html()`. The Studio controls may change presentation prominence, not editorial placement rules.

invariant: Debrief and Pair It With remain rendered from `[lunara_debrief]` and `lunara_split_review_debrief_block()`. The Studio controls may change spacing, width, and card rhythm, not the content source.

invariant: The right rail must remain in normal sticky/static document flow. Do not re-enable the disabled fixed-follow sidebar JavaScript in `inc/frontend.php`, because it is documented as capable of overlapping the Debrief section.

invariant: Review images keep Lunara image-quality rules:
- review/related cards: `3:4`
- posters and Debrief poster chambers: `2:3`
- hero and trailer/backdrop surfaces: `16:9`
- no empty image chambers
- no blur-stretched media

architecture: Use the active theme as the implementation layer:
- `inc/control-desk.php` owns the private Theme Studio panel, setting specs, save handler, notices, and preview links.
- `inc/frontend.php` owns bounded public CSS output for single Review pages.
- `single-review.php` is touched only if a stable wrapper class or data hook is missing.
- `tests/theme-studio-review-single-controls.ps1` owns contract checks before production code changes.

test: A contract test fails first until the Review Single Studio setting specs, save handler, admin render hook, frontend CSS hook, no-raw-CSS guarantee, and fixed-follow guardrail are present.

test: PHP lint passes for changed PHP files.

test: `git diff --check` passes.

test: Admin verification confirms `Lunara > Theme Studio` renders `Review Single Studio`, saves valid controls, persists values after refresh, clamps invalid numeric values, rejects invalid select values, and requires `edit_theme_options`.

test: Public route smoke returns `200` for `/`, `/reviews/`, `/reviews/sinners-2025/`, `/reviews/bugonia-the-full-spoiler/`, `/journal/`, and `/oscars/`.

test: Responsive QA at `390`, `768`, and `1280` on a standard review and a full-spoiler review confirms no horizontal overflow, no overlapped right rail, no broken images, no empty image wrappers, preserved spoiler gate, visible Debrief, visible Pair It With when present, and no Control Desk/admin leakage.

deferred: Per-review override controls.

deferred: Drag-and-drop Pair It With editing.

deferred: New Review/Debrief CPT or ACF migration.

deferred: Custom theme extraction.

deferred: Public animation/carousel changes on single reviews beyond presentation rhythm.

## Working notes

Current `single-review.php` already renders the main surfaces this control panel should tune: hero, spoiler warning, trailer module, share strip, right rail, Where to Watch, Review Details, Oscar ledger pill, Debrief, Pair It With, and related reviews.

Current `inc/frontend.php` includes review-specific CSS around Debrief poster handling and includes a disabled `lunara_output_sidebar_scroll_follow_js()` path with a comment warning that JS fixed-follow can overlap Debrief. The new controls should respect that warning.

The first implementation should favor safe classes/CSS variables over markup churn. The page has enough hooks for most work; `single-review.php` should remain a last resort.
