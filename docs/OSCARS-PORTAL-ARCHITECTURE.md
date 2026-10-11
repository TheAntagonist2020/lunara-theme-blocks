# Oscars Portal — module map

How `/oscars/` is put together after Theme 3.2.90, which parts own what, and
the coupling that is still left. Read this before changing the portal. The
code-level history lives in `CHANGELOG.md`; this file answers *where does
it live and what depends on it*.

## Layers, top to bottom

| Layer | File(s) | Owns |
| --- | --- | --- |
| Presentation | `page-oscars.php` | The page's markup and slot order. Reads the Studio config, then renders eleven slots (`hero`, `navigator`, `board`, `doors`, `spotlights`, `titles`, `research`, `linked-reviews`, `winners`, `deep-cuts`, `rotating-winners`) through `lunara_oscars_portal_render_sections()`. |
| Portal services | `inc/oscars-portal.php` | The Prediction Board renderer, board artwork resolution and its daily warmer, the door-backdrop map, route body classes, and the landing-section filter. |
| Portal editor | `inc/oscars-portal-studio.php`, `inc/oscars-portal-critical.php` | Site Studio state (identity copy, visibility, order, geometry, winners), the private preview, and the route's first-paint CSS variables. |
| Oscars data | `inc/oscars-data.php` (new in 3.2.90) | Everything read from the Academy database for the portal *and* the homepage: the ceremony snapshot and winner map, winner cards and media links, the rotating ceremony showcase, the database spotlight, ledger story cards, deep cuts, and the import-time cache flush. |
| Prediction data | `inc/oscar-picks.php` (new in 3.2.90) | The `lunara_oscar_pick` post type, its category taxonomy, the ceremony-year helpers, the editor meta box and `lunara_get_oscar_picks()`, which feeds both the board and the homepage rail. |
| Plugin boundary | `inc/oscars-family.php` | `lunara_oscars_reader()`, the one sanctioned door into the Oscars Ledger plugin, plus the route-family detectors. |
| Route assets | `assets/css/lunara-oscars-portal.css` | The portal's cacheable route sheet (56 KB budget). Enqueued by `inc/frontend.php` only on the portal route. |

### What changed in 3.2.90

- **The board's data source left the monolith.** The Oscar Picks domain was
  the only live portal code still defined in `functions.php`. It now lives in
  `inc/oscar-picks.php`. `functions.php` `require`s it at the exact line the
  code used to occupy, so its `init` registrations keep their historical
  order relative to the monolith's other `init` hooks. The saved rewrite
  rules reflect that order (see `inc/oscar-taxonomy-rewrites.php`).
- **The data layer left the homepage module.** `inc/home-sections.php` was
  about 90% Oscars data builders under a homepage name. Those functions moved
  unchanged to `inc/oscars-data.php`. The loader requires it immediately before
  `home-sections.php`, which now keeps only the card where-to-watch hook and
  the Latest Reviews renderer.
- **Both moves are byte-identical.** Every function name and body is unchanged,
  and each hook still registers at the same point in the load sequence. Tests
  that extracted or required the old files now read the new ones.
- **The template stopped doing work it throws away.** "Reviews Inside the
  Ledger" is hidden by default, but its query ran on every request. It now
  runs only when the section will render. The door-backdrop IMDb IDs now
  come from `lunara_oscars_portal_door_backdrop_map()`, the same map the daily
  visual warmer uses, instead of an inline copy that could drift from it.
- **Tablet research layout.** Between 541 and 820px, the shared guardrail
  sized Academy containers to the viewport, and the bordered research shell
  clipped them. The portal sheet now scopes them to the shell.

## Caches

| Key | Lifetime | Cleared by |
| --- | --- | --- |
| `lunara_home_oscars_snapshot_v7` | 15 min | `lunara_flush_oscars_home_transients()` on `aat_after_data_import` (also clears v6) |
| `lunara_home_database_spotlight_v1` | 15 min | same flush |
| `lunara_home_deep_cuts_v1` | 1 day | same flush |
| `lunara_oscars_rotating_showcase_v4_{day}_{limit}` | 1 day | same flush (today and tomorrow, limits 4–16, plus v3) |
| `lunara_oscars_board_art_{md5}` | 12 h | keyed on pick modified times plus the `lunara_oscars_board_visuals_stamp` option |

A data import purges the homepage page cache only, not `/oscars/`. The edge
cache's own TTL covers the portal. Adding a key to any of these payloads
means bumping its version (the 3.2.48 / 3.2.53 rule in `AGENTS.md`).

## Coupling that remains (logged, not fixed)

- **Raw database reads in the data layer.** The database spotlight and deep
  cuts query the Academy table directly rather than through the reader. The
  read-path ratchet (`tests/oscars-read-path-ratchet.ps1`) pins the total
  count, so it can only go down. Retiring them needs plugin read accessors
  first (plugins ship before the theme).
- **The plugin calls back into the theme.** The plugin's table and hub
  templates call theme helpers (the winner photo map, winner labels, and
  `lunara_enrich_oscars_entry_links`). Renaming any of them breaks the
  research section.
- **Plugin stylesheet on the portal.** The plugin detects the portal page and
  enqueues its table styles plus the theme's legacy `assets/css/oscars.css`.
  The research section embeds the plugin's database block, so some of those
  rules are live. Auditing which ones is its own task.
- **Four "is this the portal?" checks.** `lunara_is_oscars_portal_route()`,
  `lunara_is_oscars_portal_page()`, an open-coded `is_page()` in
  `inc/frontend.php`, and the dossier-surface check. They agree today. Merge
  them into one when the route family next changes.
- **Template logic.** The first ~390 lines of `page-oscars.php` still derive
  the view data inline. Several contracts pin exact slices of that source, so
  moving it into a view-model function needs those contracts rewritten in
  the same change.
- **Prediction Board crops.** Board tiles crop 16:9 stills into 2:3 frames.
  That is a design decision, not a bug, but it cuts people at the frame
  edges in wide photographs.

## Tests that cover the portal

Runtime (PHP): `oscars-portal-studio-runtime`, `oscars-winner-map-runtime`,
`site-studio-oscars-runtime`, `oscar-taxonomy-rewrites-runtime`,
`site-studio-home-oscars-runtime`.
Contracts (PowerShell): `oscars-portal-studio-contract`,
`oscars-portal-board-contract`, `oscars-portal-fluid-contract`,
`oscars-canonical-coherency`, `oscars-read-path-ratchet`,
`public-route-stabilization`, `homepage-oscar-picks-seasonal-forecast`.
Several PowerShell contracts pin release version 3.2.81 and stop at that
assertion on later releases. See the 3.2.90 session-log entry for how they
were run anyway.
