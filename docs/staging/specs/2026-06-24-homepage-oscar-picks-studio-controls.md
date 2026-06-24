# Homepage Oscar Picks Studio Controls

contract: Theme Studio exposes admin-only, bounded Homepage Studio controls for the homepage Oscar Picks rail.

contract: Public Oscar Picks rendering reads theme mods for count, density, card minimum height, and autoplay interval when block args do not explicitly override them.

contract: The control surface does not change public URLs, post types, Gutenberg block content, carousel JavaScript contracts, or Oscar Pick post data.

data:
- `lunara_home_oscar_picks_density`: select, `compact|editorial|showcase`, default `editorial`.
- `lunara_home_oscar_picks_count`: integer, default `12`, range `4..16`.
- `lunara_home_oscar_picks_card_min_height`: integer pixels, default `520`, range `380..720`.
- `lunara_home_oscar_picks_autoplay_interval`: integer milliseconds, default `6500`, range `0..12000`.

invariant: Autoplay remains disabled when fewer than two cards render.

invariant: Reduced-motion behavior remains controlled by the shared carousel controller; Theme Studio does not force motion.

test: `tests/homepage-oscar-picks-studio-controls.ps1` verifies the admin control keys, renderer consumption, clamping, public density hook, and CSS variable output.

test: Existing homepage Oscar Picks rail, homepage Splide pilot, and Oscar Facts signature tests must still pass.

deferred: Drag/drop manual Oscar Pick ordering is not part of this pass; section-order presets already control route-family placement.

## Working notes

Continue the direct-control pattern already used by Brand Console, Image Quality Console, and Homepage Studio. Keep this as a narrow control pass so the previous rail work remains stable.
