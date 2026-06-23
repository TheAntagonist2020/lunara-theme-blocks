# Homepage Preset Comparison Strip

date: 2026-06-23
status: proposed

contract: Add an admin-only comparison strip inside `Lunara > Theme Studio > Homepage Studio` that shows the major homepage front-door packages side by side.

decision: The strip uses existing Homepage Studio concepts instead of adding another public layout system.

decision: Presets describe combinations of existing homepage controls:
- front-door density
- route-card prominence
- first-section rhythm
- Latest Reviews density
- Journal lane density
- Oscar Facts density
- desktop/mobile section-order preset

decision: Presets are advisory/comparison packages first. The first pass renders a read-only strip and active/custom state; applying a preset is deferred unless the saved-control contract is explicitly approved later.

decision: The comparison strip follows the existing Review Single, Utility Search, and Oscars Dossier comparison-strip grammar already present in `inc/control-desk.php`.

interface: New admin helper functions may be added in `inc/control-desk.php`:
- `lunara_control_desk_homepage_comparison_specs()`
- `lunara_control_desk_homepage_preset_specs()`
- `lunara_control_desk_homepage_active_preset_key()`
- `lunara_control_desk_render_homepage_preset_comparison_strip()`

interface: Styling belongs only in `assets/css/lunara-control-desk.css`.

invariant: No public URL, database schema, Gutenberg block content, public CSS, homepage render output, or saved theme-mod value changes in the read-only pass.

invariant: The strip is visible only inside the private Control Desk to users who can access Theme Studio.

invariant: Zero public downtime is mandatory. If this proceeds to implementation, live deployment must have rollback backups, local lint, remote lint where PHP changed, intended-file upload only, hash verification, cache flush, and immediate public smoke tests.

test: A focused PowerShell regression confirms the Homepage comparison helpers and render markers exist, all preset names render, the strip appears in Homepage Studio, and no new save handler is introduced.

test: PHP lint passes for changed PHP files.

test: `git diff --check` passes.

test: Public smoke for `/`, `/journal/`, `/reviews/`, canonical Sinners Review, and `/oscars/` returns `200` with no sampled Control Desk/admin leakage.

deferred: One-click preset application.

deferred: Public preview-query overrides for homepage packages.

deferred: Carousel behavior controls for Oscar Facts and other homepage lanes.

## Working Notes

Current Homepage Studio already controls density, section order, visibility shortcuts, section gap, card heights, and Oscar Facts signature density. The next non-duplicative step is usability: make those controls readable as publication packages so Dalton can steer the front door faster without asking for code changes.
