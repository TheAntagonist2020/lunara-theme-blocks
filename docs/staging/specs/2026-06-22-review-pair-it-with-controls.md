# Review Pair It With Controls

contract: Extend the existing `Review Single Studio` panel with a focused `Pair It With` precision layer that lets an administrator tune the public Pair It With module without editing code, raw CSS, Review post content, Debrief content, or Pair It With data.

contract: The controls refine only the already-rendered `body.single-review .lunara-review-single-debrief--pairings` surface:
- module width behavior
- desktop column count
- poster/thumb width
- text depth
- mobile stacking rhythm
- optional image focus for pairing thumbs

contract: New settings are stored as bounded theme mods and read through the existing Review Single Studio save/preview infrastructure where practical.

contract: The first setting set is:
- `lunara_review_pair_with_layout`: `contained`, `wide`, `feature`
- `lunara_review_pair_with_columns`: integer `1` through `3`
- `lunara_review_pair_with_thumb_width`: integer `64` through `140`
- `lunara_review_pair_with_text_depth`: `tight`, `balanced`, `full`
- `lunara_review_pair_with_mobile_stack`: `compact`, `editorial`, `poster-led`
- `lunara_review_pair_with_image_focus`: `center-center`, `center-top`, `center-bottom`, `left-center`, `right-center`

contract: The admin UI renders this as a subsection inside `Review Single Studio`, not a separate top-level tab.

contract: The admin form uses `edit_theme_options`, the existing nonce-protected Review Single Studio save path or a new nonce-protected `admin-post.php` path if separation is cleaner, sanitized select values, clamped integer values, and existing Control Desk notices.

contract: The public output is scoped to `body.single-review` and specifically to the Pair It With/Debrief pairing selectors. It must not emit private admin state, source notes, nonce values, internal control labels, or setting payloads into public HTML.

contract: Preset behavior remains additive. Existing Review Single presets may set the new Pair It With controls only if doing so preserves the current package meaning:
- `Editorial Balance`: balanced Pair It With.
- `Cinematic Feature`: wider/showcase Pair It With.
- `Compact Dispatch`: compact Pair It With.
- `Spoiler Shield`: balanced Pair It With with no spoiler-gate behavior changes.

invariant: Do not change public URLs, CPT registration, rewrite rules, database schema, Review post content, Review metadata keys, ACF fields, trailer URLs, spoiler-mode metadata, Debrief data, Pair It With data, Where to Watch source data, or Oscar ledger matching.

invariant: Pair It With remains rendered from the existing Debrief split/enhancement path: `[lunara_debrief]`, `lunara_split_review_debrief_block()`, and `lunara_enhance_review_debrief_html()`.

invariant: This pass may change Pair It With presentation, width, stacking, image fit/focus, and text depth. It must not reorder Pair It With entries or invent new titles/notes.

invariant: Pair It With poster/thumbs keep the poster/image discipline already established:
- poster-style thumbs remain `2:3`
- no empty image chambers
- no blur-stretched media
- text-led entries remain intentional when no media exists

invariant: Full-spoiler reviews continue to use the existing spoiler shield. Pair It With controls must not bypass, weaken, or duplicate the protected-content wrapper.

architecture: Use the active theme as the implementation layer:
- `inc/control-desk.php` owns the private Theme Studio controls, setting specs, save handling, preset integration, and preview links.
- `inc/frontend.php` owns bounded public CSS output for Pair It With module geometry.
- `single-review.php` is touched only if a stable wrapper class or data hook is missing.
- `tests/theme-studio-review-pair-it-with-controls.ps1` owns contract checks before production code changes.

test: A contract test fails first until the new Pair It With setting specs, admin render markers, save handling, frontend CSS variables, scoped selectors, preset integration, and no-raw-CSS guarantee are present.

test: PHP lint passes for changed PHP files.

test: `git diff --check` passes.

test: Admin verification confirms `Lunara > Theme Studio > Review Single Studio` renders the Pair It With precision layer, saves valid controls, persists values after refresh, clamps invalid numeric values, rejects invalid select values, and requires `edit_theme_options`.

test: Public route smoke returns `200` for `/`, `/reviews/`, `/reviews/sinners-2025/`, `/reviews/bugonia-the-full-spoiler/`, `/journal/`, and `/oscars/`.

test: Responsive QA at `390`, `768`, and `1280` on Sinners and Bugonia full spoiler confirms no horizontal overflow, no broken images, no empty image wrappers, preserved spoiler gate, visible Debrief, visible Pair It With when present, and no Control Desk/admin leakage.

deferred: Drag-and-drop Pair It With ordering.

deferred: Per-review Pair It With layout overrides.

deferred: Editing Pair It With titles, notes, links, or Oscar matches from Theme Studio.

deferred: New ACF schema for Pair It With entries.

deferred: Bespoke theme extraction.

## Working notes

The current Review Single Studio already has `lunara_review_single_pairing_density`, but that setting mostly tunes spacing/prominence. The next control need is geometric: how much width the Pair It With module receives, how many columns it uses on desktop, how strong poster thumbs are, and how mobile stacks the text/image relationship.

Recent visual QA on the canonical Sinners Review showed the Pair It With module remains cramped on desktop for the amount of text it carries. This should be treated as a retention-module refinement, not a Review data-model change.

Keep this change narrow. If the Pair It With module needs more semantic markup later, that belongs in a later Review/Debrief data-model pass, not this control-layer pass.
