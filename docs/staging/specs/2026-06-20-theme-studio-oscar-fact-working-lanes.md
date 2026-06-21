# Theme Studio Oscar Fact Working Lanes

## Goal

Turn the Image Quality Console into a practical Oscar Facts cleanup lane so verified facts, unverified facts, and facts needing images can be inspected without mixing them into the broader Review and Journal image queue.

## Scope

- Add a focused Oscar Fact state filter to `Lunara > Theme Studio > Image Quality Console`.
- Keep the existing image quality rows, visual preview, treatment picker, and focus picker intact.
- Keep the public carousel behavior unchanged.
- Do not create new tables, public routes, post types, or frontend markup.

## Admin Contract

The console must expose a private, admin-only filter dimension with these states:

- `All`
- `Verified`
- `Unverified`
- `Needs image`

When the state is not `All`, only Oscar Fact rows should be visible:

- `Verified` means the row has a featured image and `_lunara_fact_visual_verified` is true.
- `Unverified` means the row has a featured image but `_lunara_fact_visual_verified` is false.
- `Needs image` means the row has no featured image.

The priority lane should add direct one-click links for:

- `Verified facts`
- `Unverified facts`
- `Needs image`

## Safety

- Public URLs and public carousel output remain unchanged.
- Filter query args must be sanitized and bounded.
- The filter must not affect Review or Journal queues unless an Oscar Fact state is explicitly selected.
- Existing `surface`, `post_status`, and readiness filters must continue to work.

## Acceptance

- A contract test proves the new request key, allowed values, row matcher, query args, and labels exist.
- PHP lint passes for changed theme PHP.
- The Image Quality Console renders with the new Oscar Fact state group.
- Filter links preserve the `theme-studio` tab and anchor to `#lunara-theme-studio-image-quality`.
