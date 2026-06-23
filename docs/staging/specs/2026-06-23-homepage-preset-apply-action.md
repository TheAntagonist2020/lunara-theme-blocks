# Homepage Preset Apply Action

date: 2026-06-23
status: approved

contract: Add an admin-only `Apply Package` control to the existing Homepage Studio preset comparison strip.

decision: Applying a package reuses the existing `lunara_save_homepage_studio` admin-post action and nonce instead of adding a new handler.

decision: The submitted package key comes from a hidden `lunara_homepage_apply_preset` field on the clicked button.

decision: The save handler validates the key against `lunara_control_desk_homepage_preset_specs()`.

decision: When the key is valid, the handler applies only the preset values already defined for the existing Homepage Studio controls:
- `lunara_home_front_door_density`
- `lunara_home_route_card_prominence`
- `lunara_home_first_section_rhythm`
- `lunara_home_latest_reviews_density`
- `lunara_home_journal_lane_density`
- `lunara_home_oscar_facts_density`
- `lunara_home_section_order_preset`

decision: Number controls and visibility shortcuts remain manual values in this pass.

decision: Applying a package redirects back to Homepage Studio with a specific success notice.

failure: Invalid or missing package keys are ignored and fall back to the posted/manual Homepage Studio values.

failure: Users without `edit_theme_options` are redirected with the existing forbidden notice.

invariant: No public URL, database schema, Gutenberg block content, Review/Oscars/Journal controls, raw CSS textarea, public query variable, or public apply endpoint is added.

invariant: All saved values pass through the existing option allowlists/order-preset allowlist before being stored as theme mods.

interface: Existing functions in `inc/control-desk.php` may be extended:
- `lunara_control_desk_save_homepage_studio()`
- `lunara_control_desk_render_homepage_preset_comparison_item()`
- `lunara_control_desk_get_notice_map()`

interface: Admin-only button styling belongs in `assets/css/lunara-control-desk.css`.

test: A focused PowerShell regression proves that:
- no new `admin_post_lunara_save_homepage_preset` handler exists
- the existing Homepage Studio form contains `lunara_homepage_apply_preset`
- preset buttons use `value` keys for all four packages
- the save handler reads and sanitizes `lunara_homepage_apply_preset`
- valid presets override the relevant posted select/order values
- invalid presets are ignored
- a dedicated `homepage_preset_applied` notice exists
- output remains escaped

test: Existing Homepage Studio comparison/order/signature-density/responsive-order tests continue to pass.

test: PHP lint passes for `inc/control-desk.php`.

test: `git diff --check` passes.

test: Public smoke for `/`, `/journal/`, `/reviews/`, canonical Sinners Review, and `/oscars/` returns `200` with no sampled Control Desk/admin leakage.
