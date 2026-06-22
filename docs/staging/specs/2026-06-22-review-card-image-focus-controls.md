# Review Card Image Focus Controls

contract: Add private Theme Studio controls that let an administrator tune public Review card crop focus without editing raw CSS, Review post content, Review metadata, image files, or card markup.

contract: The first control surface lives inside the existing `Lunara > Theme Studio` Reviews control area and may render as a dedicated `Review Card Image Focus` panel or as a clearly labeled subsection shared by `Reviews Archive Studio` and `Review Single Studio`.

contract: The control set is:
- `lunara_review_archive_image_focus`: `center-center`, `center-top`, `center-bottom`, `left-center`, `right-center`
- `lunara_review_rail_image_focus`: `center-center`, `center-top`, `center-bottom`, `left-center`, `right-center`
- `lunara_review_related_image_focus`: `center-center`, `center-top`, `center-bottom`, `left-center`, `right-center`
- `lunara_review_feature_image_focus`: `center-center`, `center-top`, `center-bottom`, `left-center`, `right-center`

contract: The controls are stored as bounded theme mods. Invalid saved or submitted values fall back to `center-center`.

contract: The admin form uses `edit_theme_options`, nonce-protected `admin-post.php` saving, sanitized select/radio values, escaped output, and existing Control Desk notice behavior.

contract: The public output is scoped to Review card surfaces only:
- Reviews archive lead review media
- Reviews archive run cards
- Reviews archive companion rail cards
- homepage/latest Reviews cards where the shared review-card class is used
- single Review related-review cards
- single Review feature/hero image chamber when it uses the Review card/feature image helper

contract: Public CSS maps the bounded values to `object-position` only. It must not alter image source choice, attachment ID resolution, card quote logic, trailer badges, spoiler markers, Oscar ledger pills, Debrief output, Pair It With output, or Review query behavior.

contract: Preview links in the admin panel include desktop and 390px mobile targets for:
- `/reviews/`
- `/reviews/sinners-2025/`
- `/reviews/bugonia-the-full-spoiler/`
- `/`

invariant: Review card image quality rules remain unchanged:
- archive/home Review cards: `3:4`
- posters and Debrief poster chambers: `2:3`
- hero/trailer/backdrop surfaces: `16:9`
- no empty image chambers
- no blur-stretched media

invariant: Cards without media stay text-led. The focus controls must not reintroduce blank `.lunara-review-grid-poster-wrap` chambers or fallback background wrappers without a real URL.

invariant: The existing `lunara_get_review_card_image_data()` source priority remains the image authority path for cards. This pass tunes presentation focus, not source selection.

invariant: Do not change public URLs, CPT registration, rewrite rules, database schema, Review post content, Review metadata keys, ACF fields, trailer URLs, spoiler-mode metadata, Debrief data, Pair It With data, Where to Watch source data, or Oscar ledger matching.

architecture: Use the active theme as the implementation layer:
- `inc/control-desk.php` owns the private focus specs, value validation, admin rendering, save handler, notices, and preview links.
- `inc/frontend.php` owns bounded public CSS output and maps saved values to safe `object-position` values.
- `inc/review-rendering.php` is touched only if a stable class/hook is missing on a Review card media surface.
- `tests/theme-studio-review-card-image-focus-controls.ps1` owns the contract check before production changes.

test: A contract test fails first until the focus specs, value validation, save path, admin render path, public CSS hook, safe `object-position` mapping, no-raw-CSS guarantee, and no empty-media regression guard are present.

test: PHP lint passes for changed PHP files.

test: `git diff --check` passes.

test: Existing Theme Studio regression tests continue to pass for Review Single, Reviews Archive, Oscars Dossier, Oscars profile image controls, and Utility Search.

test: Admin verification confirms the controls render, save, persist after refresh, reject invalid values, and require `edit_theme_options`.

test: Public route smoke returns `200` for `/`, `/reviews/`, `/reviews/sinners-2025/`, `/reviews/bugonia-the-full-spoiler/`, `/journal/`, and `/oscars/`.

test: Responsive QA at `390`, `768`, and `1280` confirms Review cards keep `3:4` framing, no horizontal overflow, no broken images, no empty image wrappers, no blur-stretch, and no Control Desk/admin leakage.

deferred: Per-review/per-post crop overrides.

deferred: Drag-to-focus visual picker for Review cards.

deferred: Automatic image replacement or source-quality scoring.

deferred: New ACF fields or database schema for per-image focal points.

deferred: Bespoke theme extraction.

## Working notes

Oscars Dossier Studio already has a bounded `lunara_oscars_title_image_focus` control that safely maps a small option set to `object-position`. Review cards should use the same pattern rather than raw CSS.

`review-card-media-guards` already protects against blank poster chambers. This pass builds on that guardrail by adding focus control only when a real image surface exists.

`inc/review-rendering.php` already centralizes Review card image resolution through `lunara_get_review_card_image_data()`. The first implementation should avoid changing that source order unless the contract test proves a missing hook.
