# Theme Studio Oscar Fact Visual Focus Picker

contract: `Lunara > Theme Studio > Image Quality Console` renders a clickable 3x3 visual focus picker for Oscar Fact rows.

contract: The picker writes the existing `lunara_image_source_visual_focus` field and stores the existing `_lunara_fact_visual_focus` metadata.

contract: The picker uses the existing focus keys from `lunara_oscar_fact_visual_focus_options()`.

contract: The picker offers nine positions:
- `left-high`
- `center-high`
- `right-high`
- `left`
- `center`
- `right`
- `left-low`
- `center-low`
- `right-low`

invariant: The save path, sanitizer, public renderer, public carousel, URLs, schemas, CPTs, and taxonomies remain unchanged.

invariant: The existing public crop focus behavior remains the source of truth; this is an admin usability layer over the same values.

security: Rendering and saving remain inside the existing Theme Studio capability, post-edit, nonce, surface, post-type, and MIME guardrails.

test: A contract test fails until the focus picker renders as a scoped radio grid with the existing focus values and CSS states.

test: PHP lint passes for changed theme PHP files.

test: Public smoke routes return `200` and sampled public HTML shows no Control Desk/admin leakage.

deferred: This pass does not add drag-to-focus coordinates, per-image crop storage beyond the existing enum, live iframe previews, or bulk focus updates.

## Working notes

- This is the safe v1 of the direct-control goal: clickable positions instead of asking Dalton to translate crop logic through a dropdown.
- Keep the dropdown out of the UI once the radio grid exists; duplicate same-name fields would be brittle.
