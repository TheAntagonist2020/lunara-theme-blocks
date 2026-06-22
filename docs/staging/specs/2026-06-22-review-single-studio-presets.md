# Review Single Studio Presets

contract: Extend `Lunara > Theme Studio > Review Single Studio` with private named presets so an administrator can apply and preview coherent single-review packages without hand-tuning every control.

contract: Presets are presentation packages only. They write the same bounded theme mods that Review Single Studio already owns:
- `lunara_review_single_density`
- `lunara_review_single_hero_scale`
- `lunara_review_single_rail_mode`
- `lunara_review_single_debrief_prominence`
- `lunara_review_single_pairing_density`
- `lunara_review_single_spoiler_treatment`
- `lunara_review_single_trailer_prominence`
- `lunara_review_single_section_gap`
- `lunara_review_single_debrief_poster_width`
- `lunara_review_related_count`

contract: The first preset set is:
- `editorial-balance`: default premium criticism package.
- `cinematic-feature`: stronger hero, Debrief, Pair It With, trailer, and related-lane treatment.
- `compact-dispatch`: tighter trade-desk read with calmer rail and faster section rhythm.
- `spoiler-shield`: full-spoiler-forward package with stronger warning treatment.

contract: Each preset card shows its name, intent, selected values, an apply button, and admin-only preview links for Sinners desktop, Sinners 390px, and Bugonia full spoiler.

contract: Admin-only preview links use a query override for the current request only. The override is ignored unless the viewer can `edit_theme_options`; it does not save theme mods and does not expose nonce values, source notes, or private Control Desk payloads in normal public HTML.

contract: The save handler accepts an optional `lunara_review_single_preset` value. Valid presets apply their mapped values; invalid preset keys are ignored and the normal manual-control save path remains intact.

invariant: No public URL, CPT, rewrite, schema, post content, Review metadata, ACF field, trailer, spoiler, Debrief, Pair It With, Where-to-Watch, or Oscar Ledger data changes.

invariant: Public Review single CSS remains scoped to `body.single-review`. Preset preview may change request-local CSS values for administrators only, not normal public visitors.

invariant: The existing manual controls remain usable. Presets accelerate setup; they do not replace granular controls.

test: Contract test requires preset specs, four preset keys, apply buttons, query-preview links, admin capability guard for preview overrides, invalid-key fallback, and no raw CSS textarea.

test: PHP lint passes for changed PHP files.

test: `git diff --check` passes.

test: Public smoke on `/`, `/reviews/`, `/reviews/sinners-2025/`, `/reviews/bugonia-the-full-spoiler/`, `/journal/`, and `/oscars/` returns `200` after deployment.

test: Responsive QA at `390`, `768`, and `1280` confirms no overflow, no broken images, preserved spoiler shield behavior, and no sampled public Control Desk/admin leakage.

deferred: User-created arbitrary preset names.

deferred: Per-review presets.

deferred: Drag-and-drop module order.

deferred: JavaScript live preview inside wp-admin.

## Working notes

The existing Review Single Studio structure has select specs, number specs, one save handler, and one frontend CSS emitter. The preset layer should reuse those boundaries instead of introducing new storage or raw CSS.

The public preview override can be implemented through the frontend CSS value readers instead of changing templates.
