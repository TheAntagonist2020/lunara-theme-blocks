# Lunara Site Studio v1

## Purpose

Lunara Site Studio gives the editor one focused administrative surface for the parts of the site that were previously buried inside the large Control Desk. It is an editor workflow release; it does not replace public templates, change saved content on page load, or add work to anonymous requests.

## Surfaces

The `Lunara > Site Studio` submenu exposes four allowlisted views:

- **Lunara Method** — the first/default view, using the existing Pairing Desk fields, validation, theme-mod storage, and save handler.
- **Homepage Structure** — the exact six canonical homepage lanes, their current presence, and responsive ordering controls. Advanced tuning remains in Control Desk through a direct link.
- **Reviews Archive** — the existing Reviews archive presentation controls.
- **Journal Archive** — the existing Journal archive presentation controls.

Only the selected view renders. Every embedded form posts to its existing handler and uses a bounded return-context value; no submitted return URL is accepted.

## Homepage editor behavior

The six canonical homepage blocks keep their attributes, order, and public PHP render callbacks. In the editor, their former full-page server previews are replaced with compact cards and direct links to the responsible control surface and public section. Hero and Latest Reviews retain their Inspector controls, including the Hero fallback-image chooser and multiline excerpt.

When cinematic front-door mode owns the public Hero, the Hero card states that ownership and links to Hero Command. The editor does not imply that removing the stored Hero block will remove the public front-door Hero.

## Data and compatibility

- Lunara Method continues to use the five existing `lunara_home_pairing_desk_*` theme mods.
- Blank Method text fields continue to mean “use the public fallback”; the focused screen shows the effective current wording without writing it merely by viewing.
- Homepage Structure derives its six lanes from `lunara_home_section_block_map()` and `lunara_get_home_section_registry()`.
- Structure saves preserve the first canonical block's attributes, all noncanonical top-level blocks, and deterministic placement. Extra duplicate canonical sections are removed.
- Existing Control Desk and Customizer paths remain available.
- Theme 3.2.36 behavior remains the fallback if 3.2.37 is rolled back; no data migration is required.

## Performance and security boundaries

- Site Studio loads only in WordPress admin for users with `edit_theme_options`.
- Its editor configuration and stylesheet load only in the block editor.
- Compact cards make no server-render request and load no public section imagery.
- Public templates, render callback ownership, fonts, and route-family styling remain unchanged.
- Every save retains the existing capability, nonce, post-status, and attachment validation.

## Acceptance

- Opening the Home editor does not render the six public sections in the canvas.
- Home content remains byte-for-byte unchanged until an explicit save.
- The six lane cards remain bounded without horizontal overflow.
- Lunara Method copy, review, and backdrop can be edited from the first Site Studio screen.
- Reviews and Journal archive forms save and return to their focused Site Studio view.
- Anonymous requests enqueue no Site Studio assets and execute no Site Studio queries.
