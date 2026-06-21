# Theme Studio Oscar Fact Preview Swatches

contract: `Lunara > Theme Studio > Image Quality Console` renders a compact Oscar Fact visual preview when an Oscar Fact row has a selected attachment.

contract: Each preview shows the same selected image in two public-facing interpretations:
- `Wide crop`
- `Archival fit`

contract: `Wide crop` uses the selected Oscar Fact crop focus from `lunara_oscar_fact_visual_focus_css()`.

contract: `Archival fit` keeps the full source image visible inside a cinematic backing plate.

invariant: Rows without an attachment do not render an empty preview chamber.

invariant: This pass does not alter public routes, public carousel behavior, post types, database schema, save semantics, or homepage rendering.

invariant: The existing public Oscar Facts renderer remains the source of truth for live output; this preview is an admin decision aid only.

security: Preview rendering remains admin-only inside the existing Theme Studio capability checks.

test: A contract test fails until the preview helper, labels, focus CSS custom property, and scoped admin CSS exist.

test: PHP lint passes for changed theme PHP files.

test: Public smoke routes return `200` and sampled public HTML shows no Control Desk/admin leakage.

deferred: This pass does not add live iframe rendering, drag-based focal-point controls, or bulk visual verification.

## Working notes

- The immediate problem is judging whether a fact should be public `wide` or `archival` without hopping between admin and the homepage.
- The preview should be small enough to keep the Image Quality Console dense, but clear enough to catch bad crops before they ship.
