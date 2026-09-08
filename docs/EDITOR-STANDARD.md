# Lunara editor standard

Direction agreed with Dalton on 8 September 2026. This is the target experience
and an initial source inventory; implementation is still pending.

Every Lunara editor should feel like the same product. Someone who learns to
choose an image, reorder stories, preview, and save in one place should be able
to do those things everywhere else without learning another interface. This
applies to editorial content, presentation, media, and editorial workflow settings.

## Common experience

| Task | Shared behavior |
| --- | --- |
| Find the editor | Site Studio is the common entry point. An authenticated **Edit this section** link opens the exact controls for a public section. Show the current page, section, or article prominently. |
| Work in the editor | Use the same navigation, field styling, status area, and action placement. Organize controls around content, images, and presentation. Adapt the workspace to smaller screens without hiding essential actions. |
| Choose and order content | Search by readable title, see thumbnails, add or remove items, and drag them into order. Provide keyboard move controls. Automatic and Manual have the same meaning everywhere; switching modes retains manual work. |
| Change images | Use one chooser with a visible thumbnail, Replace, Remove, and Reset to inherited image where applicable. Show the source of inherited art. Preview crop and focal point against the actual destination shape. |
| Change words | Use consistent headline, excerpt, label, and button fields wherever relevant. Clearly show whether a change affects this placement or the source article. |
| Preview | Show current unsaved changes in a private preview, with desktop and mobile views for public content. Mark stale previews. Loading, ready, empty, and failure states must be distinct. |
| Save and recover | Keep actions in the same place, show unsaved changes, prevent accidental loss, confirm the saved state, and provide history/restore. Surface-specific publishing checks remain enforced. |

Uniform actions must have uniform consequences. Use **Preview changes**,
**Apply changes**, and **Discard changes** for presentation. Articles use
**Save draft**, **Publish**, or **Update published article** according to their
state. Workflow settings use **Save settings**. A draft save must never publish.
A presentation override must never rewrite an article.

Content-specific fields belong inside this common experience: a Review needs
film details, a score, and its Debrief; an Oscar record needs category and ceremony
data. The shared controls should behave identically around those fields.

## Initial source inventory

Inspected local checkouts on 8 September 2026. These are migration families and
entry points, not a claim that every current production form has been exercised.

| Family | Existing implementation to consolidate |
| --- | --- |
| Site presentation | Theme `inc/site-studio-registry.php`, `inc/site-studio.php`, and `assets/js/lunara-site-studio.js`: homepage structure, Method, archives, Review Single, global design, search, footer, Oscars portal and ledger presentation. |
| Homepage carousels | Theme `inc/site-studio-carousels.php` and `assets/js/lunara-site-studio-carousels.js`: Hero and Journal share infrastructure but use a separate editor controller and Apply Carousel action. |
| Reviews and editorial imagery | Core `includes/class-lunara-core-site-studio-bridge.php`, `class-lunara-review-image-studio.php`, and `class-lunara-debrief-studio.php`; theme `inc/editorial-meta.php`. Review Studio routes to the canonical content editor; Debrief currently previews saved fields. |
| Journal authoring and workflow | Journal Foundation `includes/class-lunara-journal-desk-app.php`, `class-lunara-journal-site-studio.php`, and `assets/desk/desk.js`: its own workspace, image controls, draft/publish actions, and workflow settings. |
| Academy editorial tools | Oscars Ledger `academy-awards-table.php`: Awards Tracker, Poster Library, Person Portrait Queue, and Ceremony Write-Ups. Theme Control Desk also contains Oscar Picks/Facts presentation controls. |
| Reusable media and blocks | Theme `inc/carousel.php`, `inc/curated-media.php`, and `inc/blocks.php`: additional media/slide editing paths, including a separate Save Order action. |

## Delivery order

1. Establish shared editor components and state handling through the Hero and
   Journal carousels. Prove search, ordering, image replacement/framing,
   private preview, Apply, and reload through the actual editor. Investigate
   the current automatic Hero's missing artwork as part of image resolution.
2. Reuse those components across the remaining Site Studio presentation editors
   and media controls, including the Method and Oscars sections.
3. Bring Review Studio, Journal Desk, and Academy editorial forms into the same
   interaction standard, retaining their content models and publishing rules.

Each migration must identify the authoritative saved fields and every editor
that can write them. Redirect or retire competing controls as the replacement
becomes usable. Preserve existing selections and overrides until explicitly
applied. Shared behavior should be implemented once and reused through the
existing theme/plugin boundaries; copying the same CSS into separate editors
does not satisfy the standard. Keep WordPress and Blocksy for this work.

## Acceptance

An editor is ready when Dalton can open the relevant section, select and reorder
items, replace an image, change its framing and copy, preview privately, apply,
reload, and see the intended result on the public page. Exercise keyboard use,
mobile layouts, missing art, empty lists, invalid/deleted selections, stale
previews, and failed saves. Article editing also needs a demonstrated draft,
preview, and publish/update cycle without altering unrelated content.

Run the relevant regression checks and the required release gates. After
Dalton's deployment, verify the real public result and canary. Rebuild the
rollback hatch after every main merge, as required by `AGENTS.md`.
