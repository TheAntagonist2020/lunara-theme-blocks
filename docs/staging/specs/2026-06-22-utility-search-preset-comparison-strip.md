# Utility Search Preset Comparison Strip

contract: `Lunara > Theme Studio > Utility Search Studio` renders a compact read-only comparison strip for the five existing Utility Search presets.

interface: Each preset comparison item shows the preset label, active/custom state, and a concise normalized summary of the values that materially change public Search/404 rhythm: density, treatment, media mode, lead focus, spotlight type, 404 re-entry, section gap, result height, and grid minimum.

invariant: The strip is admin-only inside Control Desk and does not add or change public query strings, saved theme mods, Search result behavior, 404 behavior, database schema, raw CSS textareas, or public CSS.

invariant: The comparison strip uses the existing `lunara_control_desk_utility_search_preset_specs()` source of truth. Preset values must not be duplicated in a second array.

invariant: Values are escaped on output and labels come from the existing select/number spec labels where possible, so the comparison remains consistent with the individual controls below it.

failure: If a preset omits a known setting, the strip renders `Default` for that setting rather than breaking the admin panel.

test: `tests/theme-studio-utility-search-preset-comparison.ps1` must fail before implementation and pass after implementation by proving that the comparison renderer exists, is called inside Utility Search Studio, iterates preset specs, includes all five preset names, includes the comparison strip class, and does not introduce a new public preview query variable.

deferred: Side-by-side image screenshots inside wp-admin, drag ordering, and visual thumbnails of live pages.

## Working notes

This is the next safe step toward Dalton's granular-control goal: make preset choice understandable without requiring repeated save/preview loops.
