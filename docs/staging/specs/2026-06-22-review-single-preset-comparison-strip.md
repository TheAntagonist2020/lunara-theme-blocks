# Review Single Preset Comparison Strip

contract: `Lunara > Theme Studio > Review Single Studio` renders a compact read-only comparison strip for the existing Review Single presets.

interface: Each comparison item shows the preset label, active/custom state, and a concise normalized summary of the controls that materially change single-review authority pages: package density, hero scale, rail mode, Debrief prominence, Pair It With density, spoiler treatment, trailer prominence, section rhythm, Debrief poster width, and related review count.

contract: Review Single Studio preset preview links use the current singular review smoke-test path for Sinners (`/review/sinners-2025/`) while preserving the existing Bugonia full-spoiler preview sample. This does not alter rewrites, public URLs, permalink settings, redirects, or Review content.

invariant: The strip is admin-only inside Control Desk and does not add or change public query strings, saved theme mods, Review post content, Review metadata, spoiler reveal behavior, trailer rendering, Debrief data, Pair It With data, Where-to-Watch data, Oscar Ledger matching, schema, public CSS, or raw CSS textareas.

invariant: The comparison strip uses the existing `lunara_control_desk_review_single_preset_specs()` source of truth. Preset values must not be duplicated in a second array.

invariant: Values are escaped on output and labels come from the existing select/number spec labels where possible, so the comparison remains consistent with the individual controls below it.

failure: If a preset omits a known setting, the strip renders `Default` for that setting rather than breaking the admin panel.

test: `tests/theme-studio-review-single-preset-comparison.ps1` must fail before implementation and pass after implementation by proving that the comparison renderer exists, is called inside Review Single Studio, iterates preset specs, includes all four preset names, includes the comparison strip class, uses `/review/sinners-2025/` for Sinners preview samples, and does not introduce a new public preview query variable.

deferred: Permalink/rewrite repair, arbitrary user-created presets, per-review presets, drag ordering, and visual thumbnails of live Review pages inside wp-admin.

## Working notes

This mirrors the shipped Utility Search comparison strip and keeps the control language consistent across Theme Studio.
