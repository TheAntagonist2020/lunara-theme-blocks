# Shared presentation editors implementation plan

> **For agentic workers:** Use superpowers:subagent-driven-development or superpowers:executing-plans. Dalton authorized continuing the editor-standard migration on 10 September 2026. Execute without another design approval.

**Goal:** Extend the shared editing experience to Homepage/archive section ordering, the Lunara Method, and the Oscars Portal.

**Architecture:** Keep the existing Site Studio host as the only owner of Preview changes, Apply changes, Discard changes, revision history, and navigation protection. Reuse `LunaraEditorControls` for ordering and imagery. Extend existing canonical adapters and private previews rather than introducing competing storage or a second save controller.

**Tech stack:** WordPress PHP, vanilla JavaScript, WordPress Media Library, existing browser/PHP contract harnesses. Theme 3.2.64 candidate starts from main `40e2f1c6d2a963ef33f6982cbddcccd7a4bf1638`.

## Global constraints

- Preserve current saved presentation on upgrade. Reads, searches, and previews never write public settings or rewrite articles.
- Automatic selection uses publication dates; switching modes retains manual selections.
- Reuse the existing shared controls; do not copy a second drag or image editor implementation.
- Preview changes, Apply changes, Discard changes, and history belong to the common host. Reject stale asynchronous callbacks after candidate replacement and during operations.
- Preserve nonce/capability checks, strict schemas, same-origin private previews, field errors, failed-save recovery, and exact revision restoration.
- Existing options/theme mods remain authoritative. Normalize historical snapshots explicitly when adding fields.
- Drag operations have keyboard alternatives and focus retention. Mobile layouts keep every essential action reachable.
- Keep WordPress and Blocksy. No new slider library, production writes, deployment, cache clearing, or source-article curation in this work.
- Back up touched files with Copy-Item before editing. Commit only task-owned files on `codex/presentation-editors-3.2.64`.

## Task 1: Shared section ordering

Files: `assets/js/lunara-editor-controls.js`, `assets/js/lunara-site-studio.js`, `assets/css/lunara-editor-controls.css`, `inc/site-studio.php`, and focused Site Studio browser fixtures/contracts.

The existing `LunaraEditorControls.orderedList(options)` is the drag implementation. Its options are `parent`, `items()`, `key(item)`, `row(item,index)`, `enabled()`, and `move(from,to)`; its result is `render(focus)` and `cancelDrag()`. Reuse this contract for Homepage desktop/mobile order and Reviews/Journal archive section order. Existing row nodes and visibility controls can be retained by the row callback.

```js
function reordered(items, from, to) {
  var next = items.slice();
  if (from < 0 || to < 0 || from >= next.length || to >= next.length) { return next; }
  next.splice(to, 0, next.splice(from, 1)[0]);
  return next;
}
// Homepage: update only the active desktop_order/mobile_order; preset becomes ''.
// Archives: update section_order; retain section_visibility and every other field.
// Every mutation calls the host's renderState()/syncDirty(), never fetch/save.
```

- [x] Add meaningful browser regressions for drag order matching the private-preview/save payload, independent Homepage desktop/mobile orders, keyboard focus after moving, retained visibility, and canceled drag after discard or busy transition.
- [x] Load shared controls before the generic host on these surfaces. Preserve fail-closed startup if required controls are absent; update fixture asset loading accordingly.
- [x] Replace the separate section-move mutation paths with one common path per ordered state. Keyboard buttons and pointer drag use that same path. Keep existing selectors needed by validation, preview selection and accessibility.
- [x] Keep end-of-list move buttons correctly disabled; preserve focused moved items. A drag started before switching widths or restoring settings must not mutate a replacement list.
- [x] Run `pwsh -NoProfile -File tests/site-studio-workspace-contract.ps1`, `pwsh -NoProfile -File tests/site-studio-editorial-contract.ps1`, and `pwsh -NoProfile -File tests/home-carousels.ps1`. Report actual results and commit the owned files.

## Task 2: Method visual selection and backdrop

Files: a focused `inc/site-studio-method.php` and `assets/js/lunara-site-studio-method.js`; integrations in `functions-loader.php`, `inc/site-studio.php`, `inc/site-studio-adapters.php`, `inc/site-studio-preview.php`, shared visual controls, and the active Method renderer/CSS. Focused tests must cover server persistence, preview, public rendering, and browser behavior.

The Method currently stores `kicker`, `title`, `copy`, `review_id`, and `backdrop_id` via the existing `lunara_home_pairing_desk_*` theme mods. The active `lunara_get_pairing_desk_review_id()` and `lunara_render_home_pairing_desk()` definitions are in `functions.php` unless a later loaded provider overrides them; verify before editing. Keep existing copy and ID storage keys. Add an explicit `review_mode` (`automatic`/`manual`) and a `backdrop` object with `hidden`, `focal_x`, `focal_y`, `fit`, and `zoom`. Existing ID settings determine initial mode when no explicit mode is stored. Automatic retains `review_id` but ignores it for public selection. Backdrop defaults preserve the existing center/26% cover composition. The decorative backdrop remains hidden at widths up to 820px, as the current public stylesheet requires; explain that in its controls instead of claiming a mobile backdrop preview.

```js
// Additive candidate fields; existing five fields stay intact.
{
  review_mode: 'automatic',
  backdrop: {hidden:false, focal_x:50, focal_y:26, fit:'cover', zoom:100}
}
// focal_x/focal_y: integer 0..100; zoom: integer 100..112;
// fit: cover/full; hidden: boolean. Exact enum/type validation is mandatory.
```

- [x] Add regressions for historical settings/snapshots, retained manual ID across mode changes, unavailable/private selection warnings, current source artwork, missing attachments, framing parity in preview/public output, and rejected late metadata/media callbacks.
- [x] Implement nonce-protected, read-only, bounded Review search and selected-item/image metadata with published-title privacy rules. Reuse canonical art accessors. Display thumbnail, title, date, inherited image provenance, and unavailable/empty states; avoid an unbounded dropdown of all Reviews.
- [x] Connect the Method adapter to the host lifecycle `create({root,config,getState,changed,announce,isBusy}) -> {render,setBusy,invalidate}`. Generalize the adapter selection minimally so the carousel behavior remains unchanged.
- [x] Reuse `LunaraEditorControls.image` with Replace image, Remove image, and Use source image. Shared control options may be extended compatibly for explicit hidden artwork. Display focal/fit/zoom against the destination frame, and make public/private rendering consume those same values.
- [x] Extend Method state/read/validation/save/restore/preview mappings. Historical revision restore removes newly added overrides and returns default framing/mode derived from its original ID. All writes remain inside Apply/Restore transactions with rollback on failure.
- [x] Redirect the competing Method presentation controls to the shared editor once usable. Leave unrelated controls and article content intact. Do not edit dead duplicate renderer copies.
- [x] Preserve the old public fallback when the explicit selection-mode mod is absent. After Apply, an empty/unavailable Manual choice hides the Method and warns in the editor; Automatic may retain an unavailable manual ID without using or revealing it. Automatic keeps the existing bounded eligibility rule (newest published Review with any pairing filled), with explicit date ordering. Retiring old input fields must also stop their legacy save paths from clearing or overwriting the Method when unrelated Homepage settings are saved.
- [x] Run focused new checks plus Site Studio pilot/private-preview/workspace and carousel regressions, report results, and commit task-owned files.

Task 1 and Task 2 passed independent review. Method PHP regressions were written first; initial browser coverage followed implementation, a recorded process deviation. A later real-renderer framing test and public-CSS mutation address the framing-coverage follow-up.

## Task 3: Oscars Portal in the shared workspace

Files: `inc/site-studio.php`, `inc/site-studio-preview.php`, `assets/js/lunara-site-studio.js`, a focused Oscars inspector module if needed, `inc/oscars-portal-studio.php`, `page-oscars.php`, and focused PHP/browser tests. Read the bounded source map in the release artifact directory before implementation.

Use the existing `lunara_site_studio_oscars_portal_adapter()` and schema. The Portal already owns copy/visibility in theme mods, order/presentation in its existing option, and its existing revision list. This task must not invent another store. Homepage Oscar Picks/Facts and Academy authoring are separate subsequent migrations.

```php
// Add to existing preview pilots using the existing provider token consumer.
'oscars-portal' => array(
  'owner' => 'theme:oscars-portal', 'query' => 'lunara_oscars_preview',
  'route' => '/oscars/', 'params' => array(), 'storage' => 'provider',
  'preview_callback' => 'lunara_oscars_portal_studio_get_preview_config',
  'markers' => array('board','hero','navigator','doors','spotlights','titles','research','linked-reviews','winners','deep-cuts','rotating-winners'),
)
```

- [x] Add browser/server regressions for opening this surface, all existing copy/presentation fields, eleven-section drag and keyboard ordering, visibility, preview, apply, discard, reload/restore, failed saves, and unavailable dependencies.
- [x] Enable the Portal in the shared host and add a purpose-built inspector for all existing canonical fields with the same action labels, history, field feedback, private desktop/mobile preview, and shared section ordering as Task 1.
- [x] Keep derived visibility clear: the board remains content-driven; navigator visibility follows doors. Changing doors must update the candidate and its displayed derived state immediately, so the preview/save payload and inspector agree.
- [x] Integrate provider previews and preview-section markers with the active `page-oscars.php` renderer. Verify request-local preview values reach the active renderer and that anonymous/cross-user requests never receive private candidates.
- [x] Redirect the old Portal form entry point to the shared workspace; preserve access to other Oscar presentation/data tools. Retain exact provider validation, existing public composition, revisions and safe historical fallback.
- [x] Run focused new checks, Oscars Portal contracts, shared Studio private-preview/editorial/browser checks, and commit the implementation.

## Task 4: Integration and release

- [x] Prioritize Dalton's added mobile issue: inspect Journal and Oscars image panels on the live site at phone widths, identify the affected routes/control surfaces, and fix measured cropping, card sizing, spacing or horizontal overflow. Cover the actual active renderers and confirm the fix at desktop as well. Capture before/after evidence; do not infer the cause from a screenshot alone.
- [x] Complete task reviews and a whole-branch review; resolve material findings.
- [x] Run all required theme contracts, PHP/JS/CSS checks, and focused mutations of order isolation, Method metadata invalidation/framing persistence, and Oscars preview/persistence. Record observed failures and exact restoration.
- [x] Inspect desktop/mobile fixtures visually and verify that keyboard controls, long copy, missing art, and failed saves remain usable. Distinguish local fixtures from authenticated WordPress acceptance.
- [x] Update release identity to 3.2.64, editor guide/standard and changelog; append a truthful session log including the observed 3.2.63 live canary GO. Commit and push a reviewable candidate using the authorized release workflow.
- [x] Rebuild and verify the tree-exact rollback hatch after each main merge. The release merge was verified; repeat after the documentation merge.
- [ ] Dalton performs deployment; verify the public 3.2.64 build and canary only after that occurs, then complete authenticated editor acceptance.
