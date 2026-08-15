# Lunara ACF Pro Migration Plan

Date: 2026-06-24
Branch: `feature/acf-pro-field-bridge-20260624`

## Purpose

This branch starts the Review / Journal / Debrief migration toward ACF Pro without removing the existing custom meta boxes or changing the frontend templates all at once.

The first phase is intentionally conservative:

1. Add ACF Local JSON load/save support.
2. Add ACF field groups for Review, Journal, and The Debrief.
3. Add a legacy meta bridge so existing code that reads `_lunara_*` values can also read new ACF values.
4. Keep the current bespoke meta boxes active until frontend verification is complete.

## Critical naming rule

Do **not** name ACF fields after the legacy keys with the leading underscore removed.

Bad example:

```text
lunara_score
```

ACF stores field-key references in underscored meta keys. A field named `lunara_score` would use:

```text
lunara_score   = value
_lunara_score  = field key reference
```

But this theme already uses `_lunara_score` as the actual legacy score value. That would create a collision.

Safe convention:

```text
acf_lunara_review_score
```

Its ACF reference key becomes `_acf_lunara_review_score`, which does not collide with existing `_lunara_*` value meta.

## Files added

```text
inc/acf.php
acf-json/field-groups/group_lunara_review_core_metadata.json
acf-json/field-groups/group_lunara_review_images.json
acf-json/field-groups/group_lunara_review_debrief.json
acf-json/field-groups/group_lunara_review_homepage.json
acf-json/field-groups/group_lunara_journal_core.json
acf-json/field-groups/group_lunara_journal_visual_file.json
docs/acf-pro-migration.md
```

`functions-loader.php` now loads `inc/acf.php` after `helpers.php` and before the existing CPT, Debrief, review-rendering, query, and frontend modules.

## ACF Local JSON

The theme now saves ACF Local JSON to:

```text
acf-json/
```

The theme also loads Local JSON from:

```text
acf-json/
acf-json/field-groups/
acf-json/post-types/
acf-json/taxonomies/
acf-json/options-pages/
```

The committed field groups live under `acf-json/field-groups/` for organization.

## Legacy bridge behavior

Existing templates and renderers currently read values such as:

```php
get_post_meta( $post_id, '_lunara_score', true );
get_post_meta( $post_id, '_lunara_review_hero_banner', true );
get_post_meta( $post_id, '_lunara_journal_kicker', true );
```

The bridge intercepts those legacy meta reads and checks whether the mapped ACF field has a saved value.

Example:

```text
_lunara_score -> acf_lunara_review_score
_lunara_review_hero_banner -> acf_lunara_review_hero_banner
_lunara_journal_kicker -> acf_lunara_journal_kicker
```

If the mapped ACF value exists, it is returned in the shape the old code expects. Image fields return URLs, gallery fields return comma-separated attachment IDs, and true/false fields return `1` or `0`.

If the ACF field does not exist or is empty, WordPress falls back to the old stored `_lunara_*` meta value.

## Field group overview

### Review — Core Metadata

Post type: `review`

Fields:

- `acf_lunara_review_score`
- `acf_lunara_review_year`
- `acf_lunara_review_imdb_title_id`
- `acf_lunara_review_director`
- `acf_lunara_review_runtime`
- `acf_lunara_review_studio`
- `acf_lunara_review_lane_label_override`
- `acf_lunara_review_archive_cta_label`
- `acf_lunara_review_archive_url_override`
- `acf_lunara_review_standfirst`
- `acf_lunara_review_pull_quote`
- `acf_lunara_review_hide_standfirst`
- `acf_lunara_review_hide_where_card`
- `acf_lunara_review_hide_details_card`

### Review — Images

Post type: `review`

Fields:

- `acf_lunara_review_card_image`
- `acf_lunara_review_hero_banner`
- `acf_lunara_review_hero_banner_caption`
- `acf_lunara_review_context_shot`
- `acf_lunara_review_context_shot_caption`
- `acf_lunara_review_visual_evidence`
- `acf_lunara_review_visual_evidence_caption`
- `acf_lunara_review_thematic_echo`
- `acf_lunara_review_thematic_echo_caption`

### Review — The Debrief

Post type: `review`

Live bridge fields:

- `acf_lunara_review_where`
- `acf_lunara_debrief_theme_echo`
- `acf_lunara_debrief_counter_program`
- `acf_lunara_debrief_career_context`

Structured future fields are included for the next renderer refactor:

- `acf_lunara_debrief_theme_echo_structured`
- `acf_lunara_debrief_counter_structured`
- `acf_lunara_debrief_career_structured`

The legacy-compatible text lines still drive the current `[lunara_debrief]` renderer. The structured fields are staged but should not be treated as the source of truth until the Debrief renderer is refactored.

### Review — Homepage Placement

Post type: `review`

Fields:

- `acf_lunara_review_home_hero_featured`
- `acf_lunara_review_home_hero_priority`
- `acf_lunara_review_home_featured_shelf`
- `acf_lunara_review_home_featured_priority`

### Journal — Core Editorial

Post type: `journal`

Fields:

- `acf_lunara_journal_kicker`
- `acf_lunara_journal_label_override`
- `acf_lunara_journal_signal_note`
- `acf_lunara_journal_featured`
- `acf_lunara_journal_standfirst`
- `acf_lunara_journal_archive_cta_label`
- `acf_lunara_journal_archive_url_override`
- `acf_lunara_journal_hide_standfirst`
- `acf_lunara_journal_hide_details_card`
- `acf_lunara_journal_hide_signal_card`
- `acf_lunara_journal_hide_related`

### Journal — Visual File

Post type: `journal`

Fields:

- `acf_lunara_journal_hero_image`
- `acf_lunara_journal_hero_secondary_image`
- `acf_lunara_journal_hero_media_layout`
- `acf_lunara_journal_hide_hero_media`
- `acf_lunara_journal_featured_image_credit`
- `acf_lunara_journal_featured_image_source_name`
- `acf_lunara_journal_featured_image_source_url`
- `acf_lunara_journal_gallery`

## Admin verification

After deploying this branch to a staging or safe environment with ACF Pro active:

1. Open **ACF → Field Groups**.
2. Confirm the six Lunara field groups appear.
3. If ACF shows them as available for sync, sync them.
4. Open an existing Review.
5. Fill only one low-risk ACF field, such as `Card Pull Quote`.
6. Save.
7. Verify the review card renders the ACF value while the old meta box remains untouched.
8. Repeat with a Journal `Signal Note`.
9. Repeat with a Review `Hero Banner` image.

## Frontend verification

Check at least:

```text
/reviews/{review-slug}/
/journal/{journal-slug}/
/reviews/
/
```

Specific checks:

- Review score still renders.
- Review hero still renders.
- Review card image still renders.
- Debrief still renders.
- Pair It With rows still render.
- Journal kicker still renders.
- Journal signal note still renders.
- Journal carousel still renders when gallery images are selected.

## What this branch does not do yet

This branch does **not** remove:

- `lunara_debrief_meta_callback()`
- `lunara_save_debrief_meta()`
- `lunara_review_editorial_meta_callback()`
- `lunara_save_review_editorial_meta()`
- `lunara_journal_meta_box_render()`
- `lunara_journal_meta_box_save()`

Those should remain until ACF fields have been verified on real posts.

## Recommended next phase

After this bridge is verified:

1. Refactor `[lunara_debrief]` so structured ACF Debrief fields become the primary source.
2. Keep legacy Pair It With text parsing as fallback.
3. Add a WP-CLI migration command to copy legacy `_lunara_*` values into `acf_lunara_*` values.
4. Disable old meta boxes behind a feature flag.
5. Remove old meta boxes only after at least one full deploy cycle with no regressions.
