# LUNARA Journal Desk — Implementation and Change Handoff

Prepared: September 7, 2026  
Scope: Work completed in this conversation, covering Journal Foundation 1.3.0 and 1.3.1.

> **Status:** Both releases were implemented, tested, and merged into the existing GitHub repository. The last independent website check showed **1.3.0 installed**. Installation of **1.3.1 was not confirmed**. This document records those observations; it does not claim a fresh website check on September 7.

## 1. Objective and approved decisions

Dalton wanted one private workspace on the existing LUNARA website to manage Journal news from his phone or computer. The scope expanded from reviewing and approving posts to including Dispatch, refining the Journal voice, and replacing article images without leaving the desk.

The approved requirements were:

- Keep the application on **lunarafilm.com**.
- Use Dalton’s existing WordPress administrator login. Dalton stated that he is the sole administrator.
- Bring the Journal draft queue, Dispatch, voice direction, editing, and publication approval together.
- Allow draft-specific feedback and deliberate changes to the standing voice.
- Keep publication under Dalton’s control.
- Add direct image upload and media-library selection inside the desk.
- Preserve the existing WordPress content, configuration, and Dispatch infrastructure.

The implementation extends the existing **LUNARA Journal Foundation** plugin. It does not introduce another website, hosting service, content database, or separate account system.

## 2. Repository and releases

| Item | Value |
| --- | --- |
| Website | https://lunarafilm.com |
| Desk address | https://lunarafilm.com/journal-desk/ |
| Repository | https://github.com/TheAntagonist2020/lunara-plugin-journal-foundation |
| Default branch | `main` |
| WordPress plugin folder | `lunara-plugin-journal-foundation` |
| Plugin entry point | `lunara-journal-foundation.php` |
| Initial implementation baseline | Foundation 1.2.14 from the repository |
| Initially observed installed Foundation | 1.2.12 |
| Observed Dispatch version | 3.2.7 |
| Last observed installed Foundation | 1.3.0 |
| Latest release implemented here | 1.3.1 |

### Release 1.3.0 — private Journal Desk

- Branch: `codex/journal-desk-app`
- Pull request: https://github.com/TheAntagonist2020/lunara-plugin-journal-foundation/pull/21
- Status: merged.
- Remote implementation commit: `954f38f0a5a87914b5a8ec7463a1c1dd67b17bf1`
- Merge commit: `aadbe9e203d1b34f58c4da619c7701afbed97ac6`
- Verified release tree: `8e0a950b505bd66d24ce5252cb353198effefa64`

### Release 1.3.1 — image controls inside the desk

- Branch: `codex/desk-image-picker`
- Pull request: https://github.com/TheAntagonist2020/lunara-plugin-journal-foundation/pull/22
- Status: merged.
- Remote implementation commit: `a8bca6444a5021a3cb01dd6200908f6a0d629260`
- Merge commit: `b11ca41f90e7a657701de21e5d81e21a05dc1c76`
- Verified release tree: `c661d3c85619077ab37b975bbd121ba5867a55d5`

The local and remote implementation commit IDs differ because the authenticated GitHub connector created equivalent commits from the verified file trees. The corresponding file trees were checked for exact equality.

The repository existed before this work and was observed to be public. The website’s private desk and draft data are protected through WordPress authentication; repository visibility is a separate concern. No repository visibility change was made.

## 3. Release 1.3.0: implemented functionality

### Private website application

- Added `/journal-desk/` inside the existing plugin.
- Added **Journal → Open Journal Desk** in WordPress administration.
- Reused the WordPress administrator session.
- Added a responsive interface with LUNARA’s dark navy and gold styling, serif headings, and mobile navigation.
- Added a web-app manifest and icon for a Home Screen launch experience.
- Rendered the application independently of the public theme’s normal page assets.
- Used direct request routing; the desk does not require a new WordPress page or a permalink flush.

This is an online web application. It has no service worker, offline draft store, or background offline synchronization. Unsaved edits remain in the open page’s memory and can be lost if the page is closed or reloaded.

### Journal draft queue

- Loads the actual Journal drafts from the existing Fast Desk functionality.
- Supports search and pagination.
- Displays draft titles, source counts, modification information, and check status.
- Filters rejected stories from the review queue.
- Provides refresh and access to Dispatch status.
- Ignores outdated queue responses when a newer request has superseded them.

### Draft editing and review

The desk provides editing for:

- Headline.
- Article content.
- Excerpt/summary.
- Deck.
- Search description.

The content editor supports limited formatting and links. Pasted content is handled as plain text, and rendered editing content is sanitized to a restricted HTML subset.

The review view includes source links and stored source excerpts where available, featured-image information, technical publishing checks, and separate voice-review prompts.

**Technical validation is not editorial approval.** Passing checks does not establish that claims are accurate, the voice is right, or the article should be published.

### Draft-specific AI revision proposals

- Added **Propose a revision** with free-text editorial feedback.
- Uses the active Journal voice and the configured Dispatch provider/model.
- Generates a candidate headline, article, excerpt, and search description.
- Shows the proposed version for comparison.
- Requires an explicit application of the candidate to the editor.
- Requires a separate draft save to persist that applied revision.
- Supports restoring the previous editor version through the available undo action.
- Rejects application of a candidate when the relevant draft has changed since the proposal was requested.

Requesting a rewrite does not save or publish it.

### Shared Journal voice controls

Added a dedicated **Voice** view with:

- **How the Journal should sound**: the shared voice summary.
- **Your standing instructions**: ongoing editorial direction.
- **Phrases to avoid**: an editable list.

These settings are saved through the existing versioned configuration repository and guide both Dispatch and desk revision proposals.

Draft feedback stays temporary unless Dalton explicitly chooses **Keep feedback as a standing instruction**. That action appends the feedback to the standing refinement and saves a new active configuration version. Feedback is not silently promoted into a permanent rule, and the system does not automatically retrain a model from edits.

The intended editorial direction remains a conversation about cinema: fan first, critic second; a specific reason to care; knowledgeable without impersonal trade-paper phrasing. The controls allow Dalton to refine that direction rather than treating it as permanently finished.

### Dispatch inside the same workspace

- Added a dedicated **Dispatch** view.
- Added manual Dispatch triggering through the existing asynchronous worker.
- Displays queued/running state and available last-run information.
- Polls approximately every 30 seconds while a run is queued or running and the desk is open.
- Allows source-feed management and story-selection adjustments.
- Supports adding/removing sources and editing source labels, URLs, enablement, per-feed limits, and priorities.
- Supports preferred/maximum entries and skip rules.
- Preserves the existing scheduler and drafting machinery.

Schedule configuration and provider credentials still use the existing protected Journal Control Plane. They were not rebuilt as new native desk forms. Some advanced settings and version-history actions therefore still open that existing administration page.

### Saving, rejecting, and publishing

- Saves through the existing Fast Desk save-and-validate action.
- Requires a current draft revision token before a write.
- Tracks unsaved changes and blocks publication while changes are unsaved.
- Reloads the saved workspace before treating the draft as ready for publication.
- Blocks publication when a save succeeded but the follow-up read failed.
- Offers **Reload saved draft** for recovery, with an unsaved-change warning where applicable.
- Requires explicit publication confirmation.
- Retains the existing publication configuration, capabilities, locks, and validation gates.
- Rejecting a story removes it from the review workflow while retaining the underlying WordPress draft/private post.
- Successful publication or rejection removes the item from the current local queue and preserves the success message if a subsequent queue refresh fails.

The existing `chatgpt.may_publish` setting remains relevant even when the human administrator uses this desk because the implementation reuses the established publishing route. This work did not silently enable that setting or expand bridge scopes.

## 4. Release 1.3.1: image editing inside the desk

### Why the follow-up was needed

The 1.3.0 featured-image card displayed the current image but linked to WordPress for replacement. Dalton asked to complete that operation directly inside the standalone workspace.

### New interface

Added **Change image** beside the featured-image preview, with:

- **Upload from your device**.
- **Choose from your media library**.
- A searchable, paginated image library with thumbnail previews and dimensions.
- Editable **Image credit**.
- Editable **Alt text**.

The image picker is part of the existing editor; it does not navigate away from the article.

### Selection and save behavior

- Selecting a different image updates the local preview immediately.
- Unsaved article text is preserved.
- The article’s featured image changes only when **Save draft** succeeds.
- A different image clears the previous image’s credit to avoid carrying incorrect attribution forward.
- The selected image’s existing alt text is used as the starting description.
- The selected attachment URL becomes the image-source URL for that replacement.
- Selecting the same image preserves the current staged image fields.
- Image changes participate in dirty-state checks and block publishing until saved and verified.
- Changing image selection clears outstanding rewrite/undo state so an old editor snapshot cannot accidentally restore the previous image.

### Important media behavior

**Uploads and article saves are distinct operations.** Uploading stores the file in WordPress’s media library immediately, even if the article is not subsequently saved. Uploaded file URLs are public. The desk’s privacy does not make WordPress media files private.

**Alt text is shared attachment metadata.** Saving an alt-text edit updates the selected media-library attachment as well as the Journal field. Other places using the same attachment can therefore receive that alt text. The interface explicitly explains this behavior.

Image credit remains a Journal article field; it is not automatically inferred or copied from an attachment caption.

### Upload handling

- Accepts JPEG, PNG, WebP, GIF, and AVIF.
- Checks the actual uploaded file’s image signature, not only the filename extension.
- Limits uploads to 20 MB or the site’s lower upload limit.
- Uses WordPress’s core media endpoint for upload processing and its additional validation.
- Core WordPress may reject formats unsupported by the specific server.
- Does not accept arbitrary post/status parameters through the upload wrapper.
- Returns a selectable image without publishing or attaching it to the Journal article automatically.

No crop editor, image generation, automatic image search, or external-URL import interface was added in this release.

### Image-write safeguards

- Requires administrator session, nonce, and media capabilities.
- Validates the selected positive integer attachment ID and supported image type.
- Requires permission to edit the selected attachment.
- Checks the draft revision before image or article writes.
- Verifies the stored featured-image ID and attachment alt text after writing.
- Includes shared attachment alt text in the draft revision calculation.
- Clears the image-inspection cache when image metadata changes.
- Reports partial saves explicitly if image changes succeed but subsequent article saving cannot be confirmed.
- Requires reloading after an unverified partial save before another save or publication.

These operations are not one atomic database transaction. A partial failure can leave image changes saved while article changes remain unconfirmed; the UI is designed to expose that state rather than report complete success.

## 5. Authentication, privacy, and concurrency

### Application access

- The app page requires a logged-in WordPress administrator with `manage_options`.
- Anonymous page access is redirected to authentication.
- A hidden URL alone is not the access-control mechanism.
- The page sends no-store/no-cache and search-indexing exclusion directives.
- A restrictive Content Security Policy limits scripts and connections to the site; image loading allows the sources needed for media previews.
- The manifest contains generic app metadata, not draft content.

### Private API access

New app routes require:

1. A genuine WordPress login-cookie session matching the current user.
2. An explicit valid `X-WP-Nonce` for the REST API.
3. Administrator capability.
4. Additional post/media/publication capabilities for the requested operation.

Bridge tokens and application-password credentials do not grant access to these private app operations. Separate capability checks remain in place for editing posts, publishing, and managing media.

### Concurrent edits

- Revision hashes cover article content, status, featured media, and relevant editorial metadata.
- Shared attachment alt text was added to those hashes in 1.3.1.
- Read-time validation bookkeeping is excluded so opening/checking a draft does not invalidate an otherwise unchanged edit.
- Short locks serialize writes from app tabs.
- Legacy bridge and normal WordPress editor writes do not participate in those same locks.

This protects against stale app submissions, but it is not a global transaction or lock across every WordPress editing path.

## 6. API inventory

Base URL: `https://lunarafilm.com/wp-json/lunara/v1/`

| Method | Relative route | Purpose |
| --- | --- | --- |
| GET | `journal/app/settings` | Read the desk’s permitted voice/source/selection configuration and publication information |
| POST | `journal/app/settings` | Save explicitly allowed settings against `expected_version_id` |
| GET | `journal/app/drafts/{id}` | Open the draft workspace and revision token |
| POST | `journal/app/drafts/{id}/save` | Save against `expected_revision`, then validate; 1.3.1 adds `featured_media` |
| POST | `journal/app/drafts/{id}/revise` | Return an unsaved rewrite proposal |
| POST | `journal/app/drafts/{id}/reject` | Remove a draft from the review workflow without trashing it |
| POST | `journal/app/drafts/{id}/publish` | Publish through existing gates with `confirm_publish_now: true` |
| GET | `journal/app/media` | 1.3.1: image-library search and paging through `search` and `page` |
| POST | `journal/app/media` | 1.3.1: multipart upload containing one `file` |

Existing routes reused by the interface:

- `GET journal/desk` for queue/search/pagination.
- `POST journal/desk/run-dispatch` for asynchronous Dispatch execution.

The new media wrapper uses `/wp/v2/media` internally. It returns only the image-picker fields rather than exposing attachment descriptions or arbitrary core response metadata.

## 7. Rewrite-provider implementation

- Reuses the configured Dispatch provider, model, and server-side credentials.
- Supports the implemented OpenAI Responses, Claude, Gemini, and Grok paths.
- Retains the existing approved OpenAI mini/nano model-family restriction.
- Normalizes the stored configuration through the canonical configuration schema before prompt compilation.
- Reuses the inherited voice/compiler correction already present in the 1.2.14 repository baseline.
- Uses bounded input/output, a 55-second provider timeout, a 2,200-token output limit, and no automatic retries.
- Builds source context from the draft’s stored source metadata.
- Restricts generated source links to the known source URL set.
- Sanitizes returned HTML and redacts sensitive provider errors.
- Does not persist the provider response as a draft automatically.

No live paid provider invocation was used to establish end-to-end behavior during this implementation. Provider tests used controlled responses, so live credential validity, quota, and provider output quality remained installation checks.

## 8. Additional fixes included

- Corrected configuration-list replacement for `editorial.voice.banned_phrases` and `editorial.selection.skip_rules`, including intentional empty lists. The previous deep merge could restore defaults instead of honoring replacement.
- Corrected source-card field mapping to actual stored source headline/publication fields.
- Aligned source/selection controls with supported limits: feed maximum 50, priority maximum 10, and entry maximum 3.
- Kept failure/schedule information accessible in the mobile settings presentation.
- Prevented settings forms from displaying stale editable values while loading.
- Added stale queue-response protection.
- Preserved consumed save revisions when a follow-up workspace read fails.
- Preserved successful publication/rejection notices through queue refresh failures.
- Updated plugin, runtime, README, OpenAPI release identities, and release checks for the releases.

## 9. Files added or substantially changed

Paths below are relative to the repository root.

| File | Responsibility |
| --- | --- |
| `lunara-journal-foundation.php` | Plugin bootstrap/includes and release identity |
| `includes/class-lunara-journal-desk-app.php` | Private app route, admin-menu entry, response headers, manifest, bootstrap data, upload-limit configuration |
| `includes/class-lunara-journal-desk-api.php` | Session permissions, versioned settings, draft revision/locking wrappers, image/media operations, save verification |
| `includes/class-lunara-journal-desk-rewriter.php` | Provider-backed revision proposals and source/output restrictions |
| `includes/class-lunara-journal-config-schema.php` | Editable-list replacement correction |
| `assets/desk/desk.js` | Queue, editor, voice/Dispatch views, revision workflow, media picker, upload and save interactions |
| `assets/desk/desk-state.js` | Draft normalization, dirty checks, publication guards, candidate checks, image selection, save payloads |
| `assets/desk/desk.css` | Responsive layout and image-picker styling |
| `assets/desk/icon.svg` | App icon |
| `docs/journal-desk-app.md` | Repository installation, behavior, and verification notes |
| `tests/desk-api-runtime.php` | Session/configuration/draft/image/media boundary tests |
| `tests/desk-rewriter-runtime.php` | Rewrite permissions, providers, source constraints, and no-persistence tests |
| `tests/desk-state.test.cjs` | Frontend state and image-selection behavior |
| `tests/desk-image-ui.test.cjs` | DOM-based upload, picker, save, and failure interaction tests |
| `tests/release-contract.php` | Existing release checks updated for the new version |
| `.github/workflows/lint.yml` | PHP matrix and frontend test execution |
| `package.json`, `package-lock.json` | Reproducible test dependencies; jsdom 26.1.0 is development-only |
| `README.md` | Release identity and desk entry-point documentation |
| `openapi/lunara-journal-bridge.openapi.json` | Existing schema release identity updated |
| `openapi/lunara-journal-fast-desk.openapi.json` | Existing schema release identity updated |
| `openapi/lunara-journal-fast-desk.staging.openapi.json` | Existing schema release identity updated |

The OpenAPI version updates should not be mistaken for a new external GPT Action specification for all private cookie-only app endpoints. The desk calls its private routes directly.

## 10. Testing completed

### Local automated verification

All 12 PHP test scripts passed under the available PHP 8.3 runtime:

- `automation-attention-runtime.php`
- `automation-contract.php`
- `automation-source-bridge-runtime.php`
- `control-plane-sources-runtime.php`
- `desk-api-runtime.php`
- `desk-rewriter-runtime.php`
- `hub-telemetry-runtime.php`
- `prompt-compiler-voice-runtime.php`
- `release-contract.php`
- `site-studio-workflow-runtime.php`
- `validator-house-tells-runtime.php`
- `wp-behavior-contract.php`

JavaScript state tests and the DOM interaction test passed. Coverage included:

- Unsaved changes and unverified saves block publication.
- Rewrite proposals cannot be applied to another or subsequently changed draft.
- Image replacement preserves unsaved writing.
- A replacement clears stale credit and produces the expected save payload.
- Device upload uses multipart data and the REST nonce.
- Library search and paging use the expected requests.
- Failed partial saves retain editor text and disable further saving/publication until reload.
- Invalid images, stale revisions, missing media permission, non-administrators, and missing nonces are rejected.
- Upload size/signature checks and restricted parameter forwarding are enforced.

PHP/JavaScript syntax and Git whitespace checks were also run. New image behavior was first exercised with failing tests before the implementation was added.

### GitHub checks

Both PR #21 and PR #22 passed all three reported matrix checks before merge:

- `lint (7.4)`
- `lint (8.2)`
- `lint (8.3)`

The pipeline includes PHP runtime suites, JavaScript checks, and OpenAPI JSON parsing. The 1.3.1 pipeline additionally installs the locked development dependencies and runs the DOM interaction test.

### What these tests do not establish

- A full browser session against the installed WordPress site.
- Actual device upload handling on the production host.
- Production thumbnail rendering and shared alt-text behavior across the theme.
- Live provider credentials, quotas, rewrite quality, or Dispatch completion.
- A successful real article publication from the new interface.

**No actual Journal article was published as a test.**

## 11. Installation history and remaining deployment work

### Observed sequence

1. The website initially reported Foundation 1.2.12.
2. Release 1.3.0 was merged and an installer was prepared.
3. The connected WordPress updater reported success but independent readback still showed 1.2.12.
4. During the later image-control work, a fresh plugin listing showed 1.3.0 installed. This conversation did not establish which action performed that installation.
5. Release 1.3.1 was merged and an updated installer was prepared.
6. The connected updater again reported success, but independent readback still showed 1.3.0.
7. An unauthenticated request to the new `journal/app/media` route returned `404 rest_no_route`, consistent with the 1.3.1 route not yet being installed.

A “Plugin updated” response alone is therefore not reliable evidence that the requested release was installed. Verify the installed version independently.

### Installer

Filename: `lunara-journal-foundation-1.3.1.zip`

- ZIP root folder: `lunara-plugin-journal-foundation/`.
- 31 runtime files.
- Verified archive size when packaged: 141,221 bytes.
- Excludes repository development/test/documentation files and Node test dependencies.
- Contains the complete runtime release, including both the private desk and its image refinements.

At the time of this handoff, the local prepared file is:

`/workspace/scratch/38ce1244272c/lunara-journal-foundation-1.3.1.zip`

That workspace path is a session artifact, not a deployment path on WordPress. The installer was also made available as a downloadable file in the conversation.

### Next deployment steps

1. Check the current installed version before doing anything else; it may have changed since the last observation.
2. If needed, upload the 1.3.1 ZIP through **Plugins → Add New Plugin → Upload Plugin**, replacing the existing Foundation installation while preserving its folder and database.
3. Confirm the plugin remains active and reports 1.3.1.
4. Open `/journal-desk/` using the administrator login and reload the page so the new versioned assets load.
5. Confirm anonymous requests to the private media route are denied, rather than returning private media results.
6. Open one draft; stage text edits and select an existing image; confirm the text remains intact.
7. Upload a suitable image from the actual phone/desktop workflow and check credit/alt behavior.
8. Save and verify the reloaded article, featured image, and validation results.
9. Exercise a provider rewrite and a Dispatch run with the existing configured services.
10. Test publication only with a real article Dalton explicitly chooses to publish.

## 12. Codex continuation notes

Dalton asked to have this work in Codex. The source was saved and merged into GitHub, but this conversation was **not transferred into a separate Codex interface/project**. Do not claim that such a transfer occurred.

For continuation, use the existing repository and current `main`. Do not rebuild this as another standalone app or create a second content system.

The local working checkout used for this work was:

`/workspace/lunara-journal-foundation`

Its final image-feature commit was `1879295` on `codex/desk-image-picker`; the corresponding merged remote commits are recorded above. A future session should fetch current remote state rather than assume the local checkout remains current.

### Instructions for the next development session

- Preserve administrator-only access and existing publication gates.
- Keep temporary draft feedback distinct from deliberately saved standing voice instructions.
- Preserve unsaved writing during image and revision interactions.
- Treat uploads as media-library writes and image selection as an unsaved article change until Save draft.
- Keep the shared attachment-alt behavior visible to the administrator.
- Do not treat local tests or a generic updater success message as proof of production installation.
- Continue from the merged implementation; verify live status before proposing duplicate installation work.
- Obtain Dalton’s actual voice refinements rather than inventing permanent editorial rules.

## 13. Suggested immediate next step

Verify installation of 1.3.1, then use one existing draft to complete the entire workflow: refine the voice, replace its image, set the credit and alt text, save, and review the reloaded result. That will establish whether the implemented desk works as intended on Dalton’s real website before further expansion.
