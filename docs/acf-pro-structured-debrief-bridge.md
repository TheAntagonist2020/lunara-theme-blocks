# Structured Debrief Bridge Phase

Date: 2026-06-24
Branch: `feature/acf-pro-field-bridge-20260624`

## Purpose

This phase extends the first ACF Pro bridge so structured Debrief fields can feed the existing frontend before the `[lunara_debrief]` renderer is fully rewritten.

The current frontend still reads legacy meta keys such as:

```php
get_post_meta( $post_id, '_lunara_where', true );
get_post_meta( $post_id, '_lunara_theme_echo', true );
get_post_meta( $post_id, '_lunara_counter_program', true );
get_post_meta( $post_id, '_lunara_career_context', true );
```

Instead of forcing a risky template rewrite, `inc/acf.php` now lets those legacy reads resolve from structured ACF fields when the legacy-compatible text fields are empty.

## New structured field group

Added:

```text
acf-json/field-groups/group_lunara_review_where_structured.json
```

Field group title:

```text
Lunara Review — Where to Watch Structured
```

Primary field:

```text
acf_lunara_review_where_structured
```

This is an ACF Pro Repeater with rows for:

- `provider_label`
- `provider_type`
- `provider_url`
- `affiliate_disclosure`
- `availability_note`

## Structured Debrief fallbacks

The bridge now supports structured fallbacks for these legacy keys:

```text
_lunara_where
_lunara_theme_echo
_lunara_counter_program
_lunara_career_context
_lunara_craft_mirror
```

### Where to Watch

Priority order:

1. `acf_lunara_review_where` legacy-compatible textarea.
2. `acf_lunara_review_where_structured` repeater serialized into legacy text.
3. Existing `_lunara_where` value.

Serialized example:

```text
Max | https://play.max.com/... | affiliate — Streaming window begins July 1.
```

### Pair It With

Priority order:

1. Legacy-compatible ACF text field:
   - `acf_lunara_debrief_theme_echo`
   - `acf_lunara_debrief_counter_program`
   - `acf_lunara_debrief_career_context`
2. Structured group serialized into the current legacy line format:
   - `acf_lunara_debrief_theme_echo_structured`
   - `acf_lunara_debrief_counter_structured`
   - `acf_lunara_debrief_career_structured`
3. Existing legacy `_lunara_*` meta value.

Serialized example:

```text
There Will Be Blood (2007) — shared thematic pressure | tt0469494
```

The current Pair It With parser already understands this line format, so this bridge allows structured ACF editing without changing the frontend output path yet.

## Verification steps

On a staging or safe environment with ACF Pro active:

1. Sync the new `Lunara Review — Where to Watch Structured` field group.
2. Open a Review that does **not** have `acf_lunara_review_where` filled.
3. Add one structured availability row.
4. Save the Review.
5. Confirm the single Review page still renders the Where to Watch card.
6. Leave `acf_lunara_debrief_theme_echo` empty.
7. Fill `Theme Echo — Structured` title, year, IMDb ID, and note.
8. Confirm the Debrief renders the Theme Echo row through the existing Pair It With layout.
9. Repeat for Counter-Program and Career Context.

## Important limitation

This does not yet replace the current Debrief renderer. It makes structured ACF fields usable through the current renderer. The later renderer refactor should read structured ACF fields directly and use legacy lines only as fallback.
