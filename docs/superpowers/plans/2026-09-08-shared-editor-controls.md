# Shared Site Studio controls implementation plan

> **For agentic workers:** Use superpowers:subagent-driven-development or superpowers:executing-plans. The user authorized implementation on 8 September 2026.

**Goal:** Make Hero and Journal use the same trusted Site Studio workflow, with reusable visual image and ordering controls for subsequent editor migrations.

**Architecture:** The existing Site Studio controller owns preview, save, restore, navigation protection, and responsive preview sizing for every enabled presentation surface. A carousel adapter supplies only carousel fields and selection behavior. Small shared visual controls own image selection/framing and ordered-item interactions. Existing options and content ownership stay canonical.

**Tech stack:** WordPress PHP, vanilla JavaScript, existing WordPress Media Library, existing Splide public carousels.

## Global constraints

- Preserve saved selections and overrides; reads and previews never adopt a carousel.
- Automatic uses the six newest eligible published articles; Manual retains its list when modes change.
- Existing nonce, capability, private-preview, exact-state and revision protections remain enforced.
- Public rendering remains readable without JavaScript; no new slider library.
- Presentation changes never rewrite source articles. No automatic production writes, cache clearing, or deployment.
- This release proves the shared components in Hero and Journal and the existing Site Studio shell. Review/Journal authoring and Academy forms migrate in subsequent independently verifiable releases.
- Back up existing files before edits. Work on `codex/shared-editor-3.2.63`, never main.

## Task 1: Shared workflow and accurate source metadata

Files: `inc/site-studio.php`, `assets/js/lunara-site-studio.js`,
`inc/site-studio-carousels.php`, `inc/home-carousels.php`, relevant PHP contracts.

Adapter interface supplied by Task 2:

```js
window.LunaraSiteStudioCarouselEditor = {
  validateState(state) {},
  validateDom(root) {},
  create({root, config, getState, changed, announce, isBusy}) {
    return {render() {}, setBusy(busy) {}, invalidate() {}};
  }
};
```

`getState()` returns the current editable candidate. `changed()` synchronizes
dirty state and preview freshness. `announce(message)` reports local search/media
feedback. `invalidate()` rejects outstanding picker/search callbacks after state
replacement. The shared host is the only owner of Preview/Apply/Discard/Restore.

Editor metadata in `#lunara-carousel-metadata`:

```js
{items: {"42": {id:42,title:"Story",type:"review",available:true,
  date:"2026-09-08",date_label:"Sep 8, 2026",image_url:"https://…",
  image_id:123,image_source:"Review artwork",excerpt:"…",kicker:"Review",cta:"Read the review"}},
 images: {"123": "https://…"}, automatic: [42]}
```

Search returns `{items:[same item shape]}`. A nonce-protected read-only metadata
endpoint refreshes selected IDs and attachment IDs after restoration; do not
silently show another revision's image. Support pretty and `rest_route` URLs.

- [x] Add focused resolver/metadata regression cases for review artwork fallback,
  explicit image-off behavior, manual override, unavailable selections, and automatic order.
- [x] Resolve inherited artwork through canonical Review Image Studio/Journal
  accessors; expose the same resolved image to editor and public delivery.
- [x] Extend the generic controller with the adapter interface and exact carousel
  schema/DOM validation. Normalize server save/restore response shapes to existing
  contracts. Load visual controls and adapter before the common host.
- [x] Use Preview changes / Apply changes / Discard changes consistently. Show a
  neutral loading state until initialization completes, and useful failure feedback.
- [x] Run focused PHP and generic Site Studio tests; report and commit owned files.

## Task 2: Visual carousel adapter and reusable controls

Files: `assets/js/lunara-editor-controls.js`,
`assets/js/lunara-site-studio-carousels.js`,
`assets/css/lunara-editor-controls.css`,
`assets/css/lunara-site-studio-carousels.css`, carousel editor browser contracts.

- [x] Replace the independent carousel save controller with the Task 1 adapter.
- [x] Render searchable story thumbnails, dates, selected state, accessible Move
  up/down and drag ordering, contextual item removal, and unavailable-item warnings.
- [x] Show automatic lineup read-only and offer Use this lineup in Manual. Never
  replace an existing manual list without an explicit action.
- [x] Add image thumbnails, Media Library replacement, source reset, destination
  aspect preview, click/keyboard focal position, fit and zoom, with immediate copy
  previews. Keep blank copy overrides inherited.
- [x] Make discarded/restored items reject stale media/search responses. Keep
  focus attached to moved items and retain selection on request failures.
- [x] Verify two independent modes, keyboard and pointer ordering, image/copy
  overrides, reload/restore, failed saves, stale previews, and mobile control layout.

## Task 3: Review, release gates, and delivery record

- [x] Review the integrated implementation against the editor standard.
- [x] Run the full required theme suite, focused mutations, syntax and diff checks.
- [x] Verify rendered editor controls in a browser using the local fixture; record
  any live-only acceptance that awaits Dalton's deployment.
- [x] Advance release identity to 3.2.63, update guide/changelog/session log, commit
  and push the reviewable branch. Follow existing PR authorization; Dalton deploys.
- [ ] If main is merged, rebuild and verify the exact rollback hatch. After Dalton
  deploys, probe the actual public homepage and run the versioned canary.
