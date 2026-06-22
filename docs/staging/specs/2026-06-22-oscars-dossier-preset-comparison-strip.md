# Oscars Dossier Preset Comparison Strip

contract: `Lunara > Theme Studio > Oscars Dossier Studio` renders a compact read-only comparison strip for the existing Oscars Dossier presets.

interface: Each comparison item shows the preset label, active/custom state, and a concise normalized summary of the controls that materially change Oscars route-family pages: dossier density, ceremony rhythm, major-race prominence, profile scale, write-up prominence, related reviews shown, related-review treatment, profile image chamber, title/person image focus, section gap, card minimum width, profile image width, and profile image height.

invariant: The strip is admin-only inside Control Desk and does not add or change public query strings, saved theme mods, Oscars plugin schema/data, ceremony write-up data, public URLs, rewrites, public CSS, post content, or raw CSS textareas.

invariant: The comparison strip uses the existing `lunara_control_desk_oscars_dossier_preset_specs()` source of truth. Preset values must not be duplicated in a second preset array.

invariant: Values are escaped on output and labels come from existing select/number spec labels where possible, so the comparison stays aligned with the individual controls below it.

failure: If a preset omits a known setting, the strip renders `Default` for that setting rather than breaking the admin panel.

test: `tests/theme-studio-oscars-dossier-preset-comparison.ps1` must fail before implementation and pass after implementation by proving that the comparison renderer exists, is called inside Oscars Dossier Studio, iterates preset specs, includes all four preset names, includes the comparison strip class, includes the normalized comparison labels, and does not introduce a new public comparison query variable.

deferred: Arbitrary user-created Oscars presets, drag ordering, embedded live route screenshots in wp-admin, Oscars plugin schema/data edits, and public route visual redesign.

## Working notes

This mirrors the shipped Utility Search and Review Single comparison strips so Theme Studio develops one consistent package-comparison grammar.
