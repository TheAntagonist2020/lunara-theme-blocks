# Utility Search Focus Controls

## Goal

Give Dalton direct control over Search and recovery route priority after the first Utility Search Studio pass, without changing the WordPress search query, public URLs, schema, post content, or underlying recovery data.

## Contract

- `Lunara > Theme Studio > Utility Search Studio` adds a second, bounded control group for utility route priority.
- Search result focus changes presentation order only.
- 404 and empty-search route focus changes button/card order only.
- Existing Search query behavior, Oscar match generation, recovery matching, pagination, and live-search suggestions remain unchanged.

## Controls

Select controls:

- `lunara_utility_search_lead_focus`: `balanced`, `ledger`, `reviews`, `journal`
- `lunara_utility_search_spotlight_type`: `automatic`, `review`, `journal`, `page`
- `lunara_utility_reentry_primary`: `home`, `reviews`, `journal`, `oscars`, `search`

## Public Surface

- `search.php` may reorder the already-returned `$result_posts` display array based on the focus controls.
- `search.php` may render Oscar matches before or after editorial results based on `lunara_utility_search_lead_focus`.
- `search.php` must add stable body-internal classes/data hooks for the selected focus state.
- `404.php` may reorder existing re-entry actions based on `lunara_utility_reentry_primary`.
- Public CSS may style the selected Search focus and recovery priority, scoped to Search/404 only.

## Admin Surface

- Controls remain inside the existing Utility Search Studio form.
- Requires `edit_theme_options`.
- Saves through the existing `lunara_save_utility_search_studio` admin-post action and nonce.
- Does not expose raw CSS textareas.
- Includes preview links for Search and 404.

## Invariants

- No URL, rewrite, schema, CPT, query, post-content, or live-search AJAX behavior changes.
- Search remains query-driven and Oscar-aware.
- Empty or missing result groups do not create blank chambers.
- Utility controls remain bounded and reversible through theme mods.

## Acceptance

- A failing contract test exists before production code.
- Theme Studio renders the three new focus controls.
- Invalid values clamp to defaults.
- `search.php` reads the focus values and uses them to order display layers/results.
- `404.php` reads the primary re-entry value and uses it to order recovery actions.
- Public CSS remains scoped to Search and 404.
- PHP lint and `git diff --check` pass.
