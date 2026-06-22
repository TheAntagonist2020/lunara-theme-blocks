# Utility Search Studio

## Goal

Give Dalton direct Theme Studio control over Lunara utility search surfaces without changing URLs, queries, post content, or route ownership. Search and 404 should feel like intentional publication tools, not leftover WordPress utility pages.

## Scope

- Add a private `Utility Search Studio` panel inside `Lunara > Theme Studio`.
- Store new tuning values as bounded theme mods.
- Apply public CSS only to `body.search` and `body.error404`.
- Preserve existing Search template behavior: WordPress results, Oscar direct matches, recovery routes, pagination, and no public API changes.
- Preserve existing 404 template behavior and recovery links.

## Controls

Select controls:

- `lunara_utility_search_density`: `compact`, `editorial`, `showcase`
- `lunara_utility_result_treatment`: `list`, `cards`, `spotlight`
- `lunara_utility_result_media`: `guarded`, `poster-led`, `text-led`
- `lunara_utility_recovery_prominence`: `quiet`, `standard`, `strong`

Number controls:

- `lunara_utility_section_gap`: 20 to 84 px, default 42
- `lunara_utility_result_min_height`: 118 to 260 px, default 158
- `lunara_utility_card_grid_min`: 220 to 360 px, default 280

## Public Surface

Public CSS must emit scoped variables:

- `--lunara-utility-section-gap`
- `--lunara-utility-result-min-height`
- `--lunara-utility-result-grid-min`
- `--lunara-utility-result-media-fit`
- `--lunara-utility-result-copy-lines`

Selectors must stay scoped to:

- `body.search .lunara-search-page`
- `body.search .lunara-search-results-grid`
- `body.search .lunara-search-result-card`
- `body.search .lunara-search-oscar-grid`
- `body.search .lunara-search-empty-shell`
- `body.error404 .lunara-404-page`
- `body.error404 .lunara-404-panel`

## Admin Surface

- Requires `edit_theme_options`.
- Saves through `admin-post.php`.
- Uses nonce `lunara_save_utility_search_studio`.
- Does not expose raw CSS textareas.
- Includes preview links for Search desktop, Search 390px, 404 desktop, and 404 390px.

## Acceptance

- A failing contract test exists before production code.
- Theme Studio renders the new panel.
- Controls save, persist, and clamp.
- Public CSS only loads on Search and 404.
- No URL, query, schema, content, or CPT behavior changes.
- PHP lint and diff checks pass.
