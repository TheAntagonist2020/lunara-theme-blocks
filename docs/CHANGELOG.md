# Lunara Film — Engineering Changelog & Handoff

**Scope:** all seven `theantagonist2020` repositories behind lunarafilm.com.
**Period covered:** 2026-07-01 → 2026-07-05 (the "Design Spec 2.0" era).
**Audience:** any engineer or AI session picking this project up cold.

This is a chronological record of what shipped, why, and what's still open —
written so a new hand can get productive in one read instead of re-deriving
decisions from commit messages. Version numbers and dates below are pulled
directly from each repo's `git log`, not reconstructed from memory.

---

## 2026-09-12 — Theme 3.2.73 Archive Artwork

Reviews and Journal bring Gallery and Continue reading into the shared Site
Studio inspector. Both use the existing archive provider settings, revisions,
private Preview and Apply. Opening the editor does not alter saved content.

- Gallery: choose up to twelve Media Library images, drag or keyboard-order
  them, and edit headings, alternate text, captions, links and attribution.
- Continue reading: reorder the three existing cards, choose their destinations
  and visibility, and adjust optional artwork and the supported card copy.
- Image controls match the public 16:9 cover frame and focal point. Unsupported
  fit and zoom settings are absent. Clearing a continuation image retains its
  text and destination; removing a gallery image removes that gallery row.
- Private metadata uses the same image derivative as the public archive.
  Unavailable or unreadable attachments return a warning without private
  metadata. Pending image requests and picker callbacks cannot restore stale
  values after Preview, Discard or History changes the editor state.

The canonical providers retain validation, cache invalidation and revision
ownership. Existing article artwork and story-selection rules remain intact;
this release adds archive artwork controls, not per-article image overrides.
Home and Oscars remain on the shared-editor work list for their remaining
capabilities. Verification covers both archive workflows and mobile inspectors.

## 2026-09-12 — Theme 3.2.72 Mobile Layouts

Home, Reviews, Journal and Oscars now own their mobile geometry instead of
inheriting conflicting desktop or historic Boost rules. The saved story choices,
editor transactions and artwork sources stay compatible.

- Home: phone Hero artwork uses a stable 16:10 frame above a midnight reading
  panel. Full headlines, excerpts and buttons stay readable. Arrows, Pause and
  pagination occupy separate rows with 44px targets; Journal carousel controls
  use the same separation. Tablet and desktop retain their cinematic overlays.
- Reviews: one set of 16px gutters, compact wrapping filters and usable year
  controls replace the oversized nested toolbar. Explicit root width prevents
  the old critical CSS from narrowing the archive during first paint. Long
  phone card titles can grow naturally. Retired public-command CSS is removed.
- Journal: compact filters and a single spacing rhythm bring current stories
  forward. Phone counters are hidden; full article headlines and natural card
  heights accompany stable 16:10 artwork. The critical seed matches the route.
- Oscars: ordinary words no longer hyphenate, prose cards stack, and the wider
  two-column poster wall retains portrait art. Duplicate phone statistics are
  hidden, while command links and actions use compact, readable groups.

Verification uses actual published markup and its historic CSS alongside the
candidate, real artwork visual review, browser geometry, no-JavaScript reading,
late stylesheet delivery and targeted mutation checks. Local browser previews
are candidate evidence; production acceptance follows manual deployment.

## 2026-09-12 — Theme 3.2.71 Reviews Opening

The Reviews page opens with a compact title and introduction. The public
Reviews Command statistics and its navigation buttons are removed, so the
opening no longer presents archive administration ahead of the films. The
existing saved title, description, section visibility and order still apply.
Director archives use the same compact treatment and keep their own identity.

The route stylesheet and first-paint critical CSS agree on the single-column
layout, with a 32–48px heading, readable copy and no large framed backdrop.
Long titles wrap on mobile. The lead review and archive selection remain
unchanged; a hidden introduction retains the accessible page heading.

Classic no longer exposes the eight labels belonging to the removed panel.
Their saved values and revision schema remain compatible, and ordinary editor
saves preserve those values. Home, Journal and Oscars are outside this change.

Verification covers actual rendered normal, empty, director and hidden-intro
pages, mobile and desktop geometry, no-JavaScript reading, retained editor
values, and the existing stylesheet delivery checks.

## 2026-09-12 — Theme 3.2.70 Archive Story Selection

Reviews and Journal gain a shared Stories inspector in Site Studio. Editors can
choose a published lead, search by title or ID, and drag or keyboard-order up to
24 priority stories. The remaining archive stays available. Automatic uses
publication dates; Reviews' automatic opening no longer lets an older curated
story displace the newest review. Journal's existing shared-homepage-pin mode
is explicitly identified as legacy behavior.

Manual choices survive mode changes. Unavailable priority stories stay visible
as warnings in the editor and are skipped publicly. A missing or unavailable
manual lead blocks Preview and Apply until replaced or switched to Automatic.
Existing story rules remain active until the editor explicitly adopts the new
controls; changing only Content or Layout does not change selection behavior.
Existing query filters and sort scopes remain intact.

The shared inspector uses canonical archive configuration, review pin ownership,
private Preview, Apply, Discard and revision transactions. Selection metadata
respects the same permission boundary and avoids disclosing unpublished content.
The mobile inspector uses the existing shared controls and busy-state guards.
Home and Oscars retain their existing editors; archive artwork/gallery controls
and the broader public layout pass remain separate work.

## 2026-09-12 — Theme 3.2.69 Page Workspaces

Site Studio now starts with Home, Reviews, Journal and Oscars. Contextual links
keep each page's editors together, while the complete searchable directory is
available under All editors and tools. Home is the default destination when
authorized. Its section rows link directly to the Hero, Journal, Method, Oscar
Picks and Facts editors. Existing permissions, unavailable-destination feedback,
safe URLs, keyboard access and mobile target sizing remain enforced.

The new links share busy-state freezes and unsaved-change confirmation with the
existing directory. Reviews and Journal now put all page geometry under Layout,
rather than implying that height and spacing affect only mobile. Content and
Layout labels align with the portal and homepage Oscar inspectors; History uses
one shared label. Preview and Apply retain their existing private-preview and
save transactions, and revision storage is unchanged.

This release changes editing navigation and control grouping, not saved public
presentation. It does not yet migrate archive lead/curation/gallery controls or
the remaining Oscars portal tools from their existing owners. The measured gaps
and next delivery sequence are recorded in `docs/SITE-EXPERIENCE-STANDARD.md`.

## 2026-09-11 — Theme 3.2.68 Oscar Artwork Controls

Homepage Oscar Picks and Facts now share the image controls already used by
Hero, Journal and Method: Media Library selection, source reset, full-image
fit, focal point and zoom. Each placement retains its framing independently of
the article and of its automatic, manual or existing lineup. Preview, Apply,
Discard and revision restore use the existing private workspace transactions;
restoring an older revision removes the newly introduced override setting.

Both public renderers consume the same attachment resolver as the editor.
Explicit framing uses the original image's aspect ratio instead of a previously
cropped thumbnail. Mobile Facts now use uncropped responsive sources in stable
16:10 frames, with portrait originals contained by default. Desktop placements
without an override retain their existing treatment. Fact verification and held
visual rules still apply, and a missing custom image never silently substitutes
the source. The keyboard focal controls and stale Media Library callbacks receive
browser coverage alongside the public mobile layouts. Facts remain stacked and
readable when JavaScript is unavailable; the existing carousel controllers claim
slide visibility only when initialized.

Homepage CSS indentation was compacted to keep these fixes inside the existing
60 KiB stylesheet budget; no selectors or declarations were removed for size.

## 2026-09-11 — Theme 3.2.67 Stable Site Studio Viewports

The shared Preview / Apply workspace now keeps a stable device viewport:
1440 by 900 for desktop, 768 by 1024 for tablet, and 390 by 844 for mobile.
Long pages scroll inside the preview. Previously, the parent copied the page's
scrollHeight into the iframe height. Viewport-sized heroes then grew with that
height, causing repeated resizing and distorted artwork. The live Journal
editor reached over 377,000 pixels before this correction.

The correction applies to every shared Site Studio editor, including Hero,
Journal, Oscar Picks and Facts. The scaled canvas fits the inspector column;
switching devices, opening a private preview and returning from Apply retain
the chosen dimensions. A browser regression exercises a viewport-sized image
above long content, keyboard access to the footer, narrow admin layouts and
both ResizeObserver and its resize fallback. No public image rules or article
content change in this release.

The local interactive preview helper also remaps PHP's escaped JSON URLs to
localhost, so its sample editors start with the same strict origin checks.

## 2026-09-10 — Theme 3.2.66 Site Studio Bootstrap

Site Studio now sends typed configuration with `wp_add_inline_script` and
HTML-safe JSON before its workspace and private-preview scripts. WordPress
`wp_localize_script` converts top-level scalars to strings, turning the numeric
protocol versions into text. Strict validation then disabled the editor before
Preview or Apply could run. The correction covers the shared workspaces,
including Homepage Oscar Picks and Facts, and their private preview bridge.

Protocol validation, authorization, saved lineups and public layouts remain
intact. The PHP harness now reproduces WordPress's actual localization behavior;
the Oscars browser fixture executes the emitted configuration script instead of
rebuilding it. This exposed the original failure before the fix was applied.
The scalar conversion is documented in
[WordPress's script localization implementation](https://developer.wordpress.org/reference/classes/wp_scripts/localize/).

## 2026-09-10 — Theme 3.2.65 Homepage Oscar Lineups

Homepage Oscar Picks and Oscar Facts now have separate Site Studio workspaces
using the shared Preview / Apply / Discard and revision history workflow.
Legacy preserves the existing selection rules; Automatic uses the newest
published eligible records; Manual retains its list when switching modes.
Search, add, remove, drag and keyboard Move controls arrange each lineup.
Picks filter by ceremony year. Private, deleted, wrong-type and unavailable
selections are skipped and flagged; empty Manual lineups hide the section.

Both workspaces control heading, label, supporting text, button text, count,
rotation interval in seconds, density and card height. The real desktop,
tablet and mobile preview consumes only the private candidate. Apply writes
canonical theme mods through the existing verified transactions; revision
restore retains missing keys as well as values. The previous Homepage Picks
curation panel links to these editors and its old writer becomes inert while
the shared editor module is available. Pick/Fact article authoring and artwork
framing remain in their existing editors; the Academy database is unchanged.
The existing 3.2.64 mobile artwork treatment remains in place.

The live renderers are the uniquely defined Picks/Facts functions in
`functions.php`; these changes do not edit the dead guarded Oscars Portal
renderer. Public output remains server-rendered. One-item rails stay static,
and the existing carousel controllers honor zero rotation and reduced motion.

### Core 0.8.11 companion fix

Live Core 0.8.10 diagnostics identified OMDb failures before TMDB was reached.
Review artwork now queries TMDB directly using exact canonical IMDb identity,
with a separate artwork cache. `_lunara_tmdb_poster_url` and backdrop metadata
retain the existing Auto / Custom / Off rules. Full Movie enrichment still
uses its existing provider contract. A nonce-protected Retry movie artwork
button queues one Review without submitting its article editor; duplicate
queued/running work is suppressed. Artwork Audit needs TMDB credentials only.
Core PR #34 is merged. Missing production sources still require a successful
retry after the new Core deployment; this code change is not live proof.

## 2026-09-10 — Core 0.8.10 Review poster source and safe film lookup

Automatic Review cards now prefer `_lunara_tmdb_poster_url` over older local,
Dossier or featured artwork. Custom and Off retain their meaning. Missing or
malformed provider URLs fall back normally, and deleted attachment pointers
no longer stop the source chain. The existing theme already consumes this
Core resolver for Review cards and homepage Review artwork.

Canonical IMDb metadata written after `save_post_review`, including REST
creation, now queues artwork hydration. Obsolete queued identities stop before
provider calls. Failed provider requests retain a fixed, redacted explanation
in Review Image Studio; raw provider errors and credentials are never stored.

The Classic Review Debrief film importer contained a nested form: its lookup
submit button could save the parent Review. Lookup now uses detached controls
and explicit click/Enter handlers. It cannot submit the Review when the editor
script is absent. Invalid lookups clear the old candidate. Core is 0.8.10 in
both plugin header and runtime; no theme version change is needed.

Validation: 24 Core regressions, 50 PHP/5 JS syntax checks, 6 CSS brace checks,
four caught mutations, and local browser checks of actual PHP-rendered markup.
Theme Review Image Studio integration and all homepage carousel contracts
also passed. Core PR #33 merged as `d22f18ef86bbe8516f40462d13fa2207171ddcbc`.
Deployment and the existing six missing-source Reviews remain separate gates.

## 2026-09-10 — Theme 3.2.64 Shared Presentation Editors and Mobile Cards

Homepage desktop/mobile order and Reviews/Journal archive order now reuse the
shared drag and keyboard controls. Order changes retain visibility and the
other Homepage layout. Method and Oscars Portal join the common Site Studio
workflow for Preview changes, Apply changes, Discard changes and revision
history; they do not introduce another save controller.

Method gains bounded published-Review search, Automatic/Manual selection with
retained manual choices, current source artwork, and the shared image chooser.
Replace image, Remove image and Use source image work with focal point, fit and
zoom. The framing guide follows the actual desktop section. Public and private
renderers consume the same values; the decorative backdrop stays hidden through
820px. Existing five-field revisions restore exactly and remove newer overrides.
Legacy public fallback survives until Apply; an applied empty or unavailable
Manual selection hides Method and warns in its editor. Private titles remain
private, late callbacks are rejected, and failed saves preserve unsaved work.

Oscars Portal keeps its existing canonical theme mods, option, revisions and
owner-bound preview tokens inside the shared workspace. Its eleven-section
rail supports drag and keyboard order, direct visibility controls and derived
states: the board is content-driven and navigator visibility follows doors.
The inspector covers existing identity copy and all seven presentation controls.
The active page-oscars.php composer receives the private candidate and section
markers. Covered old forms/writers redirect to Site Studio; supplemental Classic
controls and Academy data tools remain available.

Mobile Journal cards in the legacy homepage layout put stable artwork above
full-width text and readable headlines. Homepage Oscars cards stay within their
panel, show one full card per view, and use original portrait artwork inside a
consistent frame. Arrows and dots sit above the cards. Next reaches the final
card before wrapping, and arrow/keyboard motion respects reduced motion.
Desktop retains its existing landscape presentation. These layout repairs do
not change saved selections or adopt the new Journal carousel automatically.

Homepage Oscar Picks/Facts curation, Review authoring, Journal Desk and Academy
authoring remain later editor migrations. Guide: `docs/PRESENTATION-EDITORS.md`;
standard: `docs/EDITOR-STANDARD.md`; implementation plan:
`docs/superpowers/plans/2026-09-10-presentation-editor-migration.md`.
Release gates and deployment status are recorded in `docs/SESSION-LOG.md`.

## 2026-09-08 — Theme 3.2.63 Shared Carousel Editing

Hero Carousel and Journal Carousel now use the common Site Studio controller
for Preview changes, Apply changes, Discard changes and revision history.
The carousel adapter owns selection and fields; it no longer implements a
second save/preview workflow. Failed saves retain the draft, edits mark previews
stale, and discarded or restored candidates reject late picker/search responses.
If Preview interrupts a metadata read, the editor resumes it after the request
finishes; restored settings cannot remain stuck on cached or loading artwork.

Automatic shows the actual six newest eligible stories with artwork and dates.
Use this lineup in Manual explicitly copies that lineup and confirms replacement
of an existing list. Manual rows have thumbnails, drag ordering, Move up/down,
Remove story and expandable image/text controls. Shared image controls offer
Media Library replacement, source reset, click/keyboard focal position, fit and
zoom. The framing guide follows the private page preview's image dimensions when
available; copy previews update while typing. No changes rewrite source articles.

Editor metadata and public delivery use the same canonical artwork resolver.
Hero keeps the Review Image Studio hero backdrop, with card artwork as a fallback
only when the automatic hero slot has no image. Explicit off and empty custom
image choices are respected. Requested unavailable attachments clear stale
editor URLs; inaccessible unpublished titles remain generic. Reads and private
previews preserve saved presentation until Apply; each carousel keeps independent
settings and exact revision restore. Public playback and layout remain unchanged.

This is the first editor migration. Other Site Studio panels share the action
labels and workflow; remaining presentation/media fields and the Review, Journal
Desk and Academy authoring editors migrate in later releases. Lunara Method and
Oscars composition are unchanged. Guide: `docs/HOMEPAGE-CAROUSELS.md`; standard:
`docs/EDITOR-STANDARD.md`; implementation plan:
`docs/superpowers/plans/2026-09-08-shared-editor-controls.md`.

Validation: 92 theme contract scripts; final combined carousel gate with 40
settings/metadata, 149 editor browser, 26 delivery and 29 public browser checks;
seven mutations plus the final timing regression verified failing before its
fix; repository syntax and both task/final code reviews passed. Authenticated
WordPress acceptance and the public canary follow Dalton's manual deployment.

## 2026-09-07 — Theme 3.2.62 Independent Homepage Carousels

Theme-only candidate. Site Studio > Homepage gains separate Hero Carousel and
Journal Carousel panels. Automatic selects the six newest eligible published
articles; Manual preserves an ordered selection with image, framing and copy
overrides. Hero includes reviews and Journal; Journal includes Journal only.
Seven-second autoplay is independently configurable. Apply is the explicit
adoption boundary; legacy presentation and stored settings survive until then,
with private previews and exact revision restore through the existing Studio.

The applied Journal lane uses consistent three/two/one-card layouts. Both
carousels support keyboard, arrows, swipe, pause/play and reduced motion.
Unavailable selections are retained in the editor but skipped publicly; empty
manual sections hide and single stories remain static. Missing artwork uses a
shared placeholder. The hero preload follows the same adopted source, including
empty/single decks. Request memoization is invalidated on content/settings edits;
there is no new persistent payload cache. Legacy carousel controls redirect to
the new owner after adoption. Lunara Method and Oscars composition are unchanged.

Implementation and acceptance: `docs/superpowers/plans/2026-09-07-home-carousels.md`.
Runtime contracts cover delivery, settings, preview isolation, revision restore,
the editor and responsive browser behavior. Release identity advances to 3.2.62.

The adopted hero keeps maximum-length headlines readable on mobile and the story
button inside the hero. The existing Studio workspace test now holds mocked
requests open until its busy-state assertions finish; this removes timing races
without changing product behavior or weakening those assertions. The control
guide is `docs/HOMEPAGE-CAROUSELS.md`.

## 2026-09-07 — Theme 3.2.61 Oscars Portal Studio Presentation Controls

Theme release. No companion plugin release.

- **The Studio reaches presentation parity with the archive studios.** Reviews
  and Journal each carry seven presentation settings; the Oscars Portal Studio
  carried three. It now carries seven: the existing section gap, hero minimum
  height and card minimum height, plus a **winner portrait minimum width**
  (the 3:4 grid 3.2.59 introduced) and three rhythm choices — **grid density**
  (standard / compact / showcase), **hero prominence** (balanced / feature the
  copy / feature the image) and **poster wall rhythm** (standard / gallery /
  dense, moving the 2:3 tile floor between 150 and 230 pixels). The poster
  wall, the hero and the winner portraits were all built in 3.2.57–3.2.59 and
  could until now only be changed by editing CSS and shipping a release.
- **`card_min_height` was an inert control and had been since it shipped.** It
  was stamped onto the portal root by two separate emitters and read by
  nothing: no seed rule, no route rule, no template. Moving the slider
  validated, saved, wrote a custom property, and changed no pixel. It now
  governs the minimum height of the link, spotlight and research card grids.
  This is the same defect class as the winner map that never reached the
  portal in 3.2.53 — a value produced and never consumed — and the contract
  now pins every emitted property as also consumed, so it cannot recur
  silently.
- **Rhythm choices map to custom properties, not to class-multiplied rules.**
  Each choice resolves through a small value map in
  `inc/oscars-portal-critical.php` to exactly one declaration on
  `#primary.lunara-oscars-portal`. A ruleset per choice would have multiplied
  the route sheet, which sits under a hard 56 KB ceiling with roughly 4 KB
  free. The Oscars ledger route already used this approach; the portal now
  matches it. The **route sheet is unchanged at 53,293 bytes**; only the
  route seed grew, by 413 bytes.
- **One emitter, not two.** `inc/oscars-portal-critical.php` and
  `page-oscars.php` each carried their own copy of the property list and the
  provenance gate, so a new control had to be added twice and any divergence
  between them would have been silent. Both now call
  `lunara_oscars_portal_variable_declarations()`; the template stamps the
  string it returns and the seed wraps the same string in its selector. The
  contract pins that the template carries no second copy.
- **Fail-closed, unchanged.** Variables still emit only inside an authorized
  private preview or after an explicit Studio save, and the emitter is
  all-or-nothing: one missing, non-scalar or unmappable value emits nothing at
  all, so a site falls back to the shipped clamp geometry rather than a
  half-stamped root. An unrecognized rhythm choice is rejected by validation
  rather than stored, because a stored-but-unmappable choice would blank every
  other saved setting.
- **Contracts.** `tests/oscars-portal-fluid-contract.ps1` now pins, for all
  seven properties, that each is both emitted and consumed; that the Studio's
  offered choices and the seed's value maps are the same sets; and that
  `page-oscars.php` holds no second property list.
  `tests/oscars-portal-studio-runtime.php` gains the full presentation key
  inventory, the rhythm defaults, per-choice emission for every choice of
  every control, rejection of an unrecognized choice, and refill-from-default
  for an absent one. Eight mutations went red — a property emitted but not
  consumed (twice), `card_min_height` returned to inert, a seed map missing a
  validated choice, a Studio choice the seed cannot map, the template
  regrowing its own list, the sanitizer accepting an unknown choice, and the
  hero prominence property dropped from the emitter — and all were restored
  byte-exact.
- **Not changed.** No template markup, no route stylesheet, no plugin, no
  public copy, no default value: a site that has never saved the Studio
  renders byte-identically to 3.2.60, and a site that has saved one keeps its
  three existing numbers with the four new controls at their shipped defaults.

---

## 2026-09-07 — Theme 3.2.60 Oscars Hero Backdrop

Theme release, one fix. Follows Theme 3.2.59 within hours, because the
live site showed what the offline render could not.

- **The hero now drifts, on the page that is actually live.** 3.2.59 put
  the lighter hero gradient and the `has-backdrop` class into the hero in
  `inc/oscars-portal.php`, and the shell's `lunara-oscars-hero-drift`
  animation keys off that class. But `/oscars/` renders through
  `page-oscars.php`, whose slot composer carries its own hero markup; the
  `inc/oscars-portal.php` renderer is not the live one for this route.
  So 3.2.59 shipped with the old near-opaque 120deg gradient and no
  `has-backdrop` on the hero, and the drift never fired. 3.2.60 moves
  both into `page-oscars.php`: the 112deg gradient that lets the backdrop
  read through its middle, and the class on the `lunara-oscars-portal-slot-hero`
  section when a backdrop is set. `functions.php` still carries a
  `function_exists`-guarded dead copy of the older renderer with the old
  gradient; it is dead code and was left alone, as `CLAUDE.md` warns.
- **Contract.** `tests/oscars-portal-fluid-contract.ps1` now pins the
  template, not just the shell: the hero section must stamp
  `has-backdrop`, must carry the 112deg gradient, and must not keep the
  120deg one. The prior pin only proved the shell had a keyframe to run.
- **How it was found.** 3.2.59 went live at 17:36 UTC
  (`3.2.59+20260907-173613`); the canary returned GO on all three reads
  and both sentinels. The live HTML then showed 28 art spans on the
  board, `FRONT RUNNER` labels, backdrop and poster-backdrop classes on
  the rotation, and zero plugin hub duplicates (Academy Awards Database
  2.7.83 live), but a hero section with no `has-backdrop` and the 120deg
  gradient. A computed-style probe of the live HTML rendered offline
  confirmed `animation-name: none` on the hero and, for the record, a
  marquee card at 1,278 of 1,284 pixels, one slide per view.
- **Not changed, deliberately:** Jetpack Boost's inline critical CSS for
  the route is still the pre-3.2.58 snapshot (135,541 bytes, nine
  `1180px !important` rules) and still needs regenerating from Jetpack
  Boost → Critical CSS. Also unchanged: everything else in 3.2.59.

## 2026-09-07 — Theme 3.2.59 Oscars Portal Poster Wall

Theme release. No companion plugin release; Oscars Ledger 2.7.83 (already
on `main`) remains the plugin side of this portal.

- **The prediction board is a poster wall.** Theme 3.2.58 made the board a
  grid of text tiles. Dalton looked at it and said the page needed pictures
  and dynamism, not blocks of text and negative space. Each pick is now a
  2:3 poster tile with the art behind the copy: the status chip top-right,
  the category beneath it, the call at the foot over a legibility gradient.
  Tiles lift on hover, a won pick gets a gold ring, a lost pick desaturates.
  The tile art is resolved in `inc/oscars-portal.php` by
  `lunara_oscars_pick_visuals()` in this order: the pick's own thumbnail
  (pick photo), then the person's headshot (portrait) from the Academy
  Awards Database's local `nm…-profile` attachments, then the film's poster
  from its local `tt…-poster` attachment, then the thumbnail of a matched
  review or movie post. Nothing on the render path ever fetches remotely;
  every lookup reads local attachments and the plugin's existing caches,
  and the result is memoised in a 12-hour transient keyed to the picks'
  modified times, invalidated when a pick is saved or the plugin imports.
- **Picks can pin their IMDb ids.** The pick editor gains two fields,
  `_lunara_pick_imdb_id` (a `tt` id) and `_lunara_pick_person_id` (an `nm`
  id), sanitised by `lunara_oscar_pick_sanitize_imdb_id()`. When they are
  empty the resolver falls back to the ids in the pick's Oscar entity URL,
  then to a person-name index built from the last eight ceremonies'
  rollups and ballot ledgers, then to an exact-title match against the
  site's movie and review posts. Saving a pick schedules a one-shot warm
  30 seconds later so its art is ready before the next anonymous view.
  The status label now reads `FRONT RUNNER`, not `FRONT_RUNNER`.
- **Ceremony winners are portraits.** The 98th Academy Awards block was a
  row of tiny headshots beside text. Each winner card is now a 3:4 portrait
  with the category, name and film laid over the lower third; winners
  without a photo keep a plate at the same size so the grid never staggers.
  Six across on desktop, auto-filled at 150 pixels on tablet, two across on
  a phone. Rules are scoped to `.lunara-ceremony-winners-grid` so the
  homepage winner cards are untouched.
- **The rotation is a marquee.** "Ceremony Winners in Rotation" showed
  three tall, mostly empty cards per view. It is now one 21:9 slide per
  view: the film's TMDB backdrop across the whole slide (or its poster,
  blurred, when no backdrop is cached), the winner's portrait inset at 3:4,
  the copy over the image. The existing carousel script steps one slide at
  a time and autoplays above 900 pixels, so the block finally moves on its
  own. The rotation card in `page-oscars.php` hands the image to CSS
  through `--lunara-card-backdrop` via `esc_url`. Where the shell's
  three-up flex basis outranked the route sheet, the marquee rules now
  carry the section class so they win on specificity, not on hope.
- **The hero drifts.** The hero backdrop gradient was so dark the image
  behind it never read. It is lighter across the middle, the section gets
  a `has-backdrop` class when an image is set, and the shell animates the
  background position over 46 seconds (`lunara-oscars-hero-drift`), off
  under reduced motion. The four ledger doors zoom their backdrop on hover.
- **A daily warm for the images that were never there.** Hero, door and
  rotation backdrops were blank on the live page because the plugin's TMDB
  cache is only filled when `$allow_remote` is true, which only the admin
  importers pass. `lunara_oscars_portal_warm_visuals()` now runs daily by
  WP-Cron, collects up to 60 title ids from the doors, the home snapshot,
  the rotation showcase and the picks, fetches each large visual package
  with remote allowed, rebuilds the person-name index, and invalidates the
  board-art and home-snapshot transients. The render path still never
  fetches; the warm fills the caches it reads.
- **Contracts.** `tests/oscars-portal-fluid-contract.ps1` now pins the
  190-pixel poster tiles, the 2:3 positioned row and the absolute art layer
  in both the route sheet and the critical seed, the stacked
  status-category-call areas in both, the portrait winners, the marquee's
  one-slide flex basis and backdrop variable, the hero drift and its
  reduced-motion stop, the daily warm hook and schedule, and that the
  render-path resolver never passes `true` for a remote fetch.
  `tests/fixtures/oscars-portal-board-harness.php` gains a seventh case,
  `board-art-src-escaped`: a stubbed visuals helper hands the renderer
  poster, portrait, `javascript:` and quote-breakout sources plus a hostile
  kind, and the harness proves the `javascript:` source is emptied, the
  quote never breaks the attribute, the kind collapses to a letters-only
  class token, and artless picks emit no art span.
  `tests/oscars-portal-board-contract.ps1` pins that every `src` the
  renderer emits routes through `esc_url`. Four mutations went red
  (marquee flex basis removed, art src unescaped, seed row back to
  side-by-side, warmer without remote) and were restored byte-exact.
- **Route sheet ceiling raised from 45 KB to 56 KB.** Three image-led
  blocks each need their own layer rules; the route sheet went from 44,120
  to 53,293 bytes, so both `tests/oscars-portal-fluid-contract.ps1` and
  `tests/oscars-portal-studio-contract.ps1` now hold it at 57,344. The
  critical seed is 5,973 of 6,144 bytes, the shell 185,822 of 204,800.
- **Verified by rendering.** The 3.2.57 live page was patched to the 3.2.59
  markup (art spans on the board, backdrop classes on the rotation, a hero
  backdrop) with images the asset manifest already held, then it was
  rendered offline in the container's Chromium at 390, 768, 1440, 1920
  and 2560 pixels with the modified stylesheets and a regenerated seed. No
  horizontal overflow at any width. The page is taller than 3.2.58 on
  purpose: 9,901 pixels at 1440 (was 8,764) because 28 poster tiles at 2:3
  take more room than 28 text tiles; 14,122 at 390 (was 13,117). The
  probes that found the shell rules capping the marquee track, the winner
  photo at 180 pixels and the category column under the chip are the
  reason those three are now fixed rather than assumed.
- **Not changed, deliberately:** Jetpack Boost's inline critical CSS for
  this route is a snapshot taken before 3.2.58 and carries nine
  `1180px !important` rules; it must be regenerated from Jetpack Boost →
  Critical CSS after this release lands, which is a derived-file
  regeneration, not a cache clear. Also unchanged: the Image CDN
  `quality=100` setting, the research shell, and the doors' copy.

## 2026-09-05 — Theme 3.2.58 Oscars Portal Fluid Rebuild

Theme release plus a companion plugin release, **Oscars Ledger 2.7.83**.

- **The portal scales to the screen.** The `/oscars/` portal was capped at
  1180 pixels by three authorities that had to move together: the route
  sheet `assets/css/lunara-oscars-portal.css`, the shell authority in
  `assets/css/lunara-shell.css`, and the inline critical seed in
  `inc/oscars-portal-critical.php`. All three now cap at 1720 pixels. On a
  2560-pixel display the content column grows from 1180 to 1720 pixels and
  the hero headline from 64 to 86 pixels; at 1440 the page reads as before.
- **A fluid type scale with floors.** Every portal size that was a fixed rem
  or a viewport clamp with a laptop ceiling is now `clamp(floor, vw-slope +
  rem, ceiling)`: hero title, hero copy, section titles and summaries, card
  headings and copy, kickers, stat labels and values, winner names, the
  navigator pills. The floor is the point: text under 12 pixels on the page
  drops from 85 elements at 2560 to 1.
- **The prediction board is a card grid.** The 27-row list that took a third
  of the desktop page and 5,700 pixels of a phone is now one tile per pick,
  auto-filling the portal width (five across at 1440, six on a monitor, two
  on a phone), category and status chip on top, call beneath. Markup is
  untouched, so `tests/oscars-portal-board-contract.ps1` still holds; the
  board went from 2,326 to 1,039 pixels tall at 1440.
- **One owner per block.** The Academy Awards plugin's landing hub, rendered
  inside the portal's research shell, restated what the portal already
  shows: its Latest Ceremony marquee duplicated the spotlights, its Latest
  Winner Circle duplicated the ceremony winners. Oscars Ledger 2.7.83 adds a
  landing section composer, `aat_landing_route_sections`, mirroring the
  plugin's existing ceremony and category composers; the theme hooks it in
  `inc/oscars-portal.php` and drops those two blocks on the portal page
  only. The hub is byte-identical everywhere else.
- **Poster Highlights are posters.** Earlier compact passes in the shell had
  turned each highlight into a sideways strip: poster in a narrow left
  column, title floating bottom-right, the first two cards spanning three
  tracks. A final block in the shell makes them poster-first cards at 2:3,
  six across on desktop, four on tablet, three and then two on phones, with
  the title and year beneath.
- **Compact-pass caps removed where they were lying.** Stat, fact,
  ceremony-winner, title and door cards carried `max-width` caps of 142 and
  148 pixels from old compact passes, which is why "75 ceremonies" clipped in
  Oscar Deep Cuts and why the ceremony winner cards sat 142 pixels wide in
  428-pixel columns. The caps are gone for those cards; winner cards without
  a photo collapse to a single column so producers' lists stop stacking one
  word per line. Metric tiles lose their forced 150-pixel height.
- **Verified by rendering, not by hope.** The live page was rebuilt offline
  from its unbundled HTML and assets and rendered offline in the container's
  Chromium at 390, 768, 1440, 1920, and 2560 pixels with the modified
  stylesheets and a regenerated critical seed, scrolling through so the
  reveal-on-scroll sections fired. Page height at 1440 went from 11,962 to
  8,764 pixels, at 390 from 17,357 to 13,117. No horizontal overflow at any
  width.
- **Budgets held.** Route sheet 41,835 to 44,120 bytes (contract ceiling
  45,000), shell 181,501 to 184,028 (ceiling 204,800; the shell also lost a
  3,985-byte duplicate of the board rules), critical seed 5,163 to 5,544
  (ceiling 6,144). The release identity contract moved to
  `tests/release-identity-3-2-58.ps1`.
- **Not changed, deliberately:** the spotlight and winner images still arrive
  with `quality=100` in their CDN URLs. That parameter is not in theme or
  plugin markup; it comes from Jetpack Boost's Image CDN quality setting,
  which is a wp-admin control. Also not changed: the rotating winners
  carousel's tall empty cards, and the doors and research sections, which
  Dalton can now reorder or hide from the Oscars Portal Studio.

## 2026-09-04 — Journal Foundation 1.2.14 and Dispatch 3.2.8 Journal Voice

Plugin-only release. No theme change.

**Journal Foundation 1.2.14** (`7a77042510d95e6b8fad5f3bbbbaabc9730afcff`)

- The Control Plane prompt compiler now carries the full LUNARA Journal
  register as code-owned defaults under `editorial.voice`: `register`,
  `principles`, `structure`, `headline_rules`, `contrast_examples` (Not this /
  This pairs), `drift_catalog`, `expertise_poison_phrases`, and
  `engagement_close`. The compiled system prompt went from roughly 40 lines
  with a one-sentence voice to a full register statement with nine worked
  contrast pairs, ASCII-only, about 2,500 tokens.
- Why: the compiled prompt is the only prompt the model sees while Foundation
  is active. The rich voice prompt in Dispatch `class-prompts.php` is a
  fallback that never executes on the live stack. Every Dispatch draft since
  config 1.0.25 shares prompt hash `8b1b180c…`, and every one reads the same
  way: paragraph one restates the source, paragraph two pivots on "not just X,
  it is Y", paragraph three announces "the real story is". The voice Dalton
  wants is documented in the `lunara-journal` skill and none of it reached the
  model.
- The new keys are filled from code on every read because
  `sanitize_config()` deep-merges defaults into every stored version. A
  configuration created before 1.2.14 compiles with the full voice while
  keeping its admin-edited summary, refinement note, and banned phrases. The
  refinement note now lands after the principles and carries the "freshest
  steering, cannot override facts or formatting" framing the Dispatch fallback
  already had.
- The user directive now states the angle-first rule, fan before critic, first
  person allowed, and a landing sentence on every entry. The engagement
  question is conditional: added only when the entry has a genuine fork the
  reader could take the other side of, roughly one entry in three. Dalton's
  call after discussion: a mandatory question on automated volume becomes a
  format within a week, and a model with reasoning off produces the poll
  version the prompt forbids. The first cut of this release made it mandatory
  on every entry; that was corrected in a follow-up commit before any PR was
  opened.
- The validator reports `expertise_poison_phrases` as warnings ("House tell
  found, cut on sight"), never as errors, so a draft is flagged for a human
  without being pushed into `validation_failed`.
- Contracts added and wired into `lint.yml`:
  `tests/prompt-compiler-voice-runtime.php` (sections present and ordered,
  ASCII-only prompt, half-formed contrast pairs dropped, pre-1.2.14 stored
  config still compiles the voice, directive ends at the news-data boundary,
  ChatGPT editor instructions inherit it) and
  `tests/validator-house-tells-runtime.php` (warnings not errors; banned
  phrases still fail).
- Version pins, OpenAPI release versions, README, and the Site Studio runtime
  stub moved to 1.2.14. Protocol and schema versions stay at 1.2.2: no key
  was renamed or removed and Dispatch reads the compiled strings, not the keys.

**Dispatch 3.2.8** (`254a7605823053e91be5f51069ffa6a0928fd2d2`)

- Fallback system prompt and user directive aligned with the compiled voice:
  first person, opinion in paragraph one, performed-expertise phrases and
  "the real story is" / "the takeaway is simple" banned, template headlines
  ("X Turns Y Into Z") banned, landing sentence on every entry with an
  engagement question only when there is a real fork. The old "Do not force
  a question" phrasing is gone; the new rule says when a question earns its
  place instead of treating it as a formula or a foul.
- OpenAI Responses requests send `text.verbosity: medium` instead of `low`.
  Low is an explicit terseness instruction, which is the wire-service register
  the Journal exists to reject. `reasoning.effort` stays `none`: reasoning
  tokens count against the 2,200 `max_output_tokens` cap and would truncate a
  two-entry run. Output cost is bounded by the same cap either way; input
  grows by about 2,500 cacheable tokens per run, which at the gpt-5.4-mini
  cached rate is a fraction of a cent. `openai-cost-guard-runtime.php` pins
  the new pair and explains the choice inline.
- `Lunara_Dispatch_Post_Builder::normalize_typographic_punctuation()` folds
  curly quotes, em and en dashes, ellipses, no-break and thin spaces, and their
  HTML entities to ASCII at the top of `split_into_individual_posts()`, before
  titles are derived. Three of the four drafts inspected this session carried
  Foundation's "non-ASCII" warning purely from the model's curly apostrophes.
  Accented characters in names are left alone so that warning still means
  something.
- Contract added and wired into `lint.yml`: `tests/journal-voice-runtime.php`
  (fallback prompt agreement, normalizer output byte-for-byte, normalizer runs
  before extraction).
- Version pins and the three release-aligned user agents moved to 3.2.8.

**Not changed, deliberately**

- The Dispatch quality gate's `has_originality_signal()` list rewards "not
  just", "the signal", "the pattern", "reads like": the exact tics the new
  prompt tells the model to drop. It is a skip filter, not a generator, and
  its list is broad enough ("studio", "filmmaker") that clean copy still
  passes. Logged in the session log for a separate pass.
- The deck-equals-first-paragraph duplication carried from 2026-09-02.
- Provider and model. Dispatch stays on `gpt-5.4-mini`. Switching provider or
  raising the output cap to allow reasoning is a cost decision for Dalton.

## 2026-09-02 — Theme 3.2.57 Journal Single Lede Parity

- Removed the journal half of the enlarged opening-paragraph rule in
  `style.css`. Reviews keep their lede; journal entries no longer single out
  `p:first-of-type`, so every journal paragraph now takes the same body clamp
  from the single-journal inline guardrail in `inc/frontend.php`.
- Why: on a journal entry the first body paragraph is the hero deck repeated,
  because the Foundation ingest derives the deck from the excerpt and the
  excerpt from the opening of the content. The lede rule then lifted that
  repeated sentence to 20.5px while paragraph two sat at the 16px clamp floor
  at 1280 to 1440px viewports, a 28% step in the same Tiempos Text face. On a
  three-paragraph dispatch it read as the article changing typeface. Both
  rules arrived together in Theme 3.2.18 "Journal reading focus"; the journal
  body clamp was evidently meant to cover all paragraphs and could not, because
  the `> p:first-of-type` selector outranks it on specificity.
- Verified the cascade against the live 3.2.56 CSS bundles and the inline
  guardrail block on a published journal entry before changing anything. The
  stored post content is three bare paragraphs with no markup, so the
  difference was entirely CSS.
- The remaining non-important generic `p:first-of-type` rule at 1.24rem still
  loses to the `!important` journal clamp, so this is a single-selector change
  with no second edit and no cache flush.
- Added `tests/journal-single-lede-parity.ps1` to hold the boundary: reviews
  keep exactly one lede rule, no shipped CSS singles out the first journal
  paragraph, and the shared guardrail clamp stays in place and stays
  `!important`.
- Not changed: the deck-equals-first-paragraph duplication itself. That is
  Dispatch and Journal Foundation ingest behavior, logged in the session log as
  a separate call for Dalton.

## 2026-08-29 — Theme 3.2.56 Site Studio Editorial and Utility Workspaces

- Migrated Reviews Archive, Journal Archive, Review Single, Utility Search &
  Recovery, and Site Footer into the common Site Studio workspace. Each adapter
  reads, validates, previews, saves, snapshots, and restores its existing
  canonical theme or provider state; no second settings database was added.
- Derived archive section order and visibility from the existing canonical
  Reviews and Journal registries. The recognizable left rail now edits the same
  public order, while advanced handoffs retain the full archive tools and Core
  Review Studio remains the owner of individual Review records.
- Extended private preview to the exact Reviews, Journal, Review, Search, and
  Footer routes. Utility Search retains the fixed `q=Lunara` preview query, all
  candidate tokens remain user/owner/route bound, and stable public section
  markers drive the strict same-origin section bridge without wrapping or
  restructuring live page content.
- Added strict state specifications for Review Single, Utility Search, and Site
  Footer plus hardened provider projections for the two archives. Each migrated
  surface retains twelve private revisions, and restore still creates a safety
  snapshot before replacing canonical state.
- Kept the 404-only Primary Return Route in Utility Search's Classic controls;
  the fixed `/search/?q=Lunara` iframe cannot truthfully preview that separate
  route. Search-result and no-result presentation remain previewable in Studio.
- Hardened archive saves and previews to merge only the inspector-owned fields
  into a fresh provider read, so concurrent advanced-tool changes cannot be
  overwritten by a stale browser candidate. Dotted validation errors now focus
  their exact plain-language controls, and invalid legacy theme mods normalize
  to safe defaults before the workspace opens.
- Archive private-preview providers now require a successful transient write
  and exact readback before returning a token. Failed or mismatched records are
  deleted and surface a bounded human error instead of sending the iframe to a
  token that cannot be authorized.
- Added familiar, plain-language controls for density, prominence, spacing,
  mobile geometry, footer language, archive order, and visibility. The same
  workspace now proves real 1440, 768, and 390 preview widths; normal Save Live
  remains confirmation-free while removals and restores require confirmation.
- Prepared plugin-first compatibility releases for Core 0.8.9, Journal
  Foundation 1.2.13, and Dispatch 3.2.7. They contribute inert guided
  destinations and redacted status, make Foundation the authoritative workflow
  owner, and replace Journal's raw source JSON with labeled source rows. Deploy
  all plugins before the theme, with Foundation before Dispatch and Theme.
- Allowed normalized same-origin admin anchors to retain their precise guided
  handoff destination while external, credentialed, control-character, and
  out-of-admin URLs continue to fail closed.
- Removed the legacy version-change whole-domain Rocket purge plus Header and
  Hero Command administration purges and the remaining affirmative purge
  instruction. Public correctness and ordinary administration no longer depend
  on a cache-clearing side effect.
- Added focused PHP/static contracts and a real-Chrome editorial workspace gate
  covering local dirty state, responsive layouts, archive reordering, cancelled
  removals, fixed preview URLs, region-to-inspector focus, ordinary saves, and
  the five new surfaces at representative breakpoints.
- This entry records a local release candidate only. No integration,
  deployment, cache operation, production write, or live canary occurred.

## 2026-08-29 — Theme 3.2.55 Site Studio Foundation and Visual Site Map Pilot

- Added a normalized Site Studio registry and authenticated, nonce-checked,
  capability-aware REST API for registry, state, preview, save, revisions, and
  confirmed restore operations. Contributed surfaces remain redacted,
  dependency-aware, recursion-bounded, and unavailable rather than fatal when
  their owner is missing or invalid.
- Piloted Global Design, Homepage Structure, and Lunara Method through their
  canonical theme storage rather than adding a second settings system. Global
  Design owns the shared palette and font roles; Homepage Structure owns its
  existing order, visibility, preset, and block-composition state; Lunara
  Method owns only its established copy, Review, and backdrop controls.
- Made Homepage saves atomic across managed theme mods and exact front-page
  `post_content`. Validation completes before mutation, failed writes and
  readbacks restore exact prior presence/value/content, unknown blocks remain
  in place, and registry-mode pages are never silently converted to block
  composition.
- Added owner-, route-, surface-, user-, and expiry-bound private preview plus
  a same-origin section bridge. Private responses establish no-store/noindex
  protections before lookup, preview substitutions stay request-local, and
  parent/child messages require the active frame, exact origin, instance, and
  allowlisted surface/section without exposing the preview token.
- Authorized private previews suppress WordPress admin chrome before Core's
  priority-zero initializer can install its toolbar bump, so the 390, 768, and
  1440 preview canvases measure the public page rather than an admin-shifted
  document. Normal and denied requests leave the admin bar untouched.
- Replaced the Control Desk renderer path with a dedicated responsive Site
  Studio workspace: searchable Visual Site Map, candidate-ordered section
  rail, one selected same-origin iframe, contextual inspector, progressive
  disclosures, accessible status/focus/error handling, and true 1440, 768,
  and 390 internal preview widths.
- Kept twelve-entry revision histories with durable readback and a verified
  pre-restore safety snapshot. Homepage revisions preserve the combined mod
  and content transaction, and every restore revalidates current capability,
  dependency, schema, and front-page identity before replacement.
- Revision History now refreshes in place after successful Save and Restore
  adoption. The independent authenticated read is sequence-guarded against
  stale overlap, strictly validates its bounded response, preserves disclosure
  and focus behavior, and leaves the accepted canonical result untouched when
  the refresh itself fails.
- Save and Restore now require complete canonical mutation envelopes at both
  the REST and browser boundaries. Missing or malformed revision identifiers,
  timestamps, changed-section lists, or safety-snapshot metadata fail closed
  without adopting state, navigating the preview, clearing unsaved work, or
  starting a revision refresh.
- This entry records a local release candidate only. No integration,
  deployment, cache operation, production write, or live canary occurred.

## 2026-08-28 — Theme 3.2.54 Public Route Stabilization

- Restored one valid document landmark on Home, Reviews, Journal, and Oscars:
  `header.php` remains the sole canonical `<main>`, while each route retains
  its existing `#primary`, classes, Studio ordering hooks, and theme-version
  marker on a neutral inner wrapper.
- Replaced mobile viewport-derived route/grid widths with parent-relative,
  border-box containment. Removed page, shell, and section overflow masking
  that concealed layout defects while preserving explicit Review rails,
  Journal filter/sort scrollers, Oscars carousels, media crops, and line clamps.
- Both Oscars winner lanes now share a conditional media-link renderer. Visual
  anchors receive an accessible name from canonical winner title/context;
  posterless cards emit no empty media anchor and keep their named text link.
- Design Tokens no longer calls `rocket_clean_domain()`. All visible Control
  Desk cache guidance and save notices now follow the standing rule: never
  clear caches as a fix.
- Added `tests/public-route-stabilization.ps1` plus its portable Playwright
  runtime: four canonical route fixtures at 390, 430, 768, 782, and 1440,
  asserting one main, no document overflow, no action-masking ancestor, local
  scroller containment, and the Oscars anchor contract. The existing Oscars
  winner runtime now executes poster, plugin-markup, and posterless branches.
- Read-only 390px A/A probes confirmed the public site still serves Theme
  3.2.53; no deployment occurred. All four one-pair probes were deliberately
  recorded as `BASELINE_NOISY`, so they are measurements rather than release
  comparisons. Home and Journal used image LCPs (35,374 B and 40,716 B
  transferred); Reviews and Oscars used text LCPs. No live bottleneck change
  was justified from this noisy cohort.

## 2026-08-19 — Theme 3.2.53 Oscars Latest Ceremony Winners Transport

- **The Latest Ceremony Winners section on `/oscars/` had never rendered, on
  any deploy, since it was written.** `lunara_get_home_oscars_snapshot()`
  built a `$winner_map` from the ceremony rollup and then omitted it from the
  array it returned. `page-oscars.php` read `$snapshot['winner_map']`, got
  nothing, passed an empty map to `lunara_build_oscars_ceremony_winner_cards()`,
  which returns early on empty input — so the portal produced zero winner
  cards and the section's render condition was never satisfied. One missing
  array key stood between 12,138 rows of ledger data and the section built to
  display them.
- The defect survived because the *other* consumer looked healthy. The
  homepage rotating showcase rebuilds the map from the rollup itself rather
  than reading the snapshot, so the winner cards visibly worked on `/` while
  the portal's copy was dead. A shared surface with two implementations only
  needs one of them to be right to look right.
- Fixes:
  - Extracted `lunara_build_oscars_winner_map()` — the one reducer both
    surfaces (and now the portal) call, so tie resolution cannot diverge.
    First row per canonical category wins; blank and non-array rows are
    skipped rather than keyed.
  - The snapshot now returns `winner_map`, and its cache version moves
    `v6 → v7`. Adding a key to a cached payload without moving the version
    would have served shape-old snapshots to shape-new readers for the life
    of the TTL — the 3.2.48 failure class, in miniature.
  - `lunara_flush_oscars_home_transients()` clears both `v7` and the retired
    `v6`, so an upgraded site does not leave a dead row to expire on its own.
  - The portal rebuilds the map from `$snapshot['rollup']` when the key is
    absent, so a pre-v7 payload surviving in an object cache across a deploy
    degrades to a rebuild instead of a dark section.
  - The winners lane now reads `_visual` defensively, matching the hardened
    read the rotating lane already used. That path had never executed.
- New contract `tests/oscars-winner-map-runtime.php` pins the transport, not
  the presentation: the reducer's tie and skip rules, the presence and
  contents of `winner_map` on both a cold build and a cache hit, the `v7`
  cache identity, the flush covering both keys, and the portal's pre-v7
  fallback. Verified by mutation — seven separate breakages, each confirmed
  to fail the suite before the fix was restored.
- No CSS shipped: every class in the section is already covered by
  `lunara-oscars-portal.css`, `lunara-shell.css`, and the portal critical CSS.
  The section was fully built and fully styled the whole time; it was starved
  of data, not of design.

---

## 2026-08-19 — Theme 3.2.52 Oscars Navigator Link Integrity

- Fixed a dead in-page anchor on `/oscars/`: the portal navigator emitted a
  "Winners" link gated only on the `lunara_oscars_show_latest_winners`
  visibility dial, while the section itself additionally required ceremony
  winner data, which was resolving empty. The defect was **pre-existing, not
  a 3.2.51 regression**: the same asymmetry is present in the 3.2.43 tree at
  the corresponding lines. It went unseen until the new Oscars coherency
  sentinel looked at the route for the first time.
- **Correction (3.2.53).** This entry originally explained the empty data as
  "a ceremony with nominees recorded but no winners yet." That was wrong.
  Ceremony 97 carries 518 winner rows and always did; the data never reached
  the template. See 3.2.53 for the actual cause. The 3.2.52 fix itself stands
  — an in-page link must not outlive the section it points at, whatever the
  reason that section is absent — but it hardened the symptom, not the cause.
- The winner-card data is now resolved in the template's data-prep block
  rather than at the point of render, because the navigator is emitted well
  above the section and must know whether that section will exist before it
  links to it. Both the link and the section now share one `$has_latest_winners`
  gate, matching the pattern `#oscars-board` already used correctly.
- The Oscars sentinel's anchor census no longer demands `oscars-winners`
  unconditionally — a data-conditional section that correctly declines to
  render is not an incoherent page, and the old expectation would have
  failed a healthy portal. Data-conditional anchors are still held to the
  no-duplicate rule, and a new `portal-navigator-link-integrity` contract
  replaces the lost coverage with a stronger, general invariant: every
  in-page `#oscars-*` link on the route must resolve to a section that
  exists. That catches this entire class on any slot, not just this one.

---

## 2026-08-17 — Theme 3.2.51 Oscars Route-Family Design Pass

- Gave the Oscars route family its first route-owned system, mirroring the
  Reviews 3.2.40 and Journal 3.2.44 pattern. A new family boundary
  (`inc/oscars-family.php`) provides route detectors and a single reader
  around the plugin's 2.7.82 read-path API; the theme's three direct-SQL
  Oscars call sites now consume plugin accessors and degrade to hidden or
  empty — never back to SQL — with a ratchet contract pinning remaining
  direct table references at 22, decrease only.
- The `/oscars/` portal renders through an eleven-slot Oscars Portal Studio
  composer (visibility backed by the existing `lunara_oscars_show_*` theme
  mods as canonical owners, order and geometry in a validated option with
  twelve-snapshot history, restore, and admin-only preview tokens), proven
  byte-identical to the previous template across five visibility states,
  with every `#oscars-*` anchor preserved and the root stamped with
  `data-lunara-theme-version`.
- Site Studio gains an Oscars group: the Portal Studio and the existing
  Oscars Dossier Studio (now context-aware with bounded returns), with
  surface groups derived from the registry.
- Portal CSS consolidated from five owners into one route asset inside a
  45KB budget — the inline priority-1001 emitter is gone, the portal no
  longer loads the 42KB cross-route `oscars.css`, and the head follows the
  proven order: resolver-gated licensed Tiempos Bold preload at 4,
  provenance-gated Studio variables at 6, structural seed with slot order
  neutralization at 7, unaggregated synchronous stylesheet at 8, with full
  Boost/Rocket protections.
- The plugin's ceremony, category, title, and person routes gain a
  conservative theme overlay through the 2.7.82 composer seam: Dossier
  variables at head 6 (value-identical to the preserved legacy emitter), a
  structural seed reserving the plugin's own geometry, and licensed Tiempos
  label/control faces behind a fail-closed Design-Tokens marker — no plugin
  CSS or template changes, zero session state on the anonymous-cacheable
  routes.
- Coverage: a 147-assertion Portal Studio runtime, the Prediction Board's
  first executed contract, an eleven-case ledger overlay contract with
  emitter value-identity proof, the read-path ratchet, and an `/oscars/`
  canonical cache-coherency sentinel cloned from the Journal gate family
  (exact-URL discipline, version binding, live/replay exit separation).

## 2026-08-16 — Theme 3.2.50 Reviews Archive Studio Parity

- Brought the Reviews Archive Studio to full capability parity with the
  Journal Archive Studio via a dedicated `inc/reviews-archive-studio.php`
  module: archive identity, lead curator, automatic-or-curated archive runs
  (up to 24 reviews), a four-lane section composer, public-language label
  ownership with byte-identical defaults, retention cards with optional
  media showcase, an archive gallery (up to 12 images), editorial rhythm and
  geometry controls, twelve-snapshot configuration history with restore and
  audit, and capability-gated private preview tokens.
- Existing WordPress owners stay canonical: identity/order/visibility/
  presentation theme mods are read and written in place, and the archive
  lead still lives only in `_lunara_review_pinned` written through
  `lunara_set_pinned_review_id()`. Validation follows the Journal's raw-token
  discipline — no broad sanitization before allowlist checks; invalid input
  repairs fail-closed to defaults and never replaces the public file.
- Curated runs compose into the query as a stable SQL CASE at priority 19 so
  the existing pinned-lead filter (priority 20) stays untouched: pin first,
  curated order second, native order third, with exact page-unique results.
- Added the Reviews route label-face resolver mirroring the Journal's exact
  raw-token rule, the licensed Tiempos Text Bold preload at head priority 4
  for the default token only, and label/control-scoped route CSS inside the
  45KB budget; the retention slot owns its flex order in both the critical
  seed and the route stylesheet, and the Reviews root now stamps
  `data-lunara-theme-version` for cache-identity verification.
- Director taxonomy archives remain contractually exempt from every Studio
  surface — they read pure defaults unconditionally, and the existing
  composition contract asserts are unchanged.
- Coverage: a 209-assertion Studio runtime harness and a dedicated suite
  contract lock the module roster, validator reachability, revision and
  preview flows, SQL composition, typography discipline, byte budgets, and
  the director exemption.

## 2026-08-16 — Theme 3.2.49 Journal Canonical Cache-Coherency Reissue

- Reissued the independently approved Theme 3.2.48 licensed Journal typography
  product tree after its direct-production canary was automatically rolled
  back: the anonymous no-query `/journal/` response kept serving legacy cached
  archive markup while query-bearing Journal routes, pagination, taxonomies,
  and authenticated requests had advanced to the candidate — a mixed public
  identity and therefore a mandatory hard stop.
- Advanced only the public theme version and matching test version locks so
  this reissue can never be confused with the publicly served, rolled-back
  3.2.48 canary identity; the licensed Tiempos label/control typography, the
  custom Studio bypass (Lora), and the priority 6/7/8 first-paint ordering are
  unchanged from the reviewed candidate tree.
- Stamped the modern Journal archive root with a `data-lunara-theme-version`
  attribute so the served archive HTML carries a cache-borne identity that
  deployment verification can compare against the deployed stylesheet version.
- Added a fail-closed anonymous canonical cache-coherency sentinel
  (`tests/tools/lunara-journal-canonical-coherency-gate.js`, with offline
  runtime and contract coverage) that must pass on the exact no-query public
  `/journal/` URL before any functional or performance continuation: expected
  version binding, exactly one modern archive root and zero legacy roots,
  exactly one lead and eight nonblank cards, structural route CSS before the
  body, no legacy route assets, and Tiempos marker/preload ownership that
  matches the resolved Studio label token. Query-bearing, cache-busted, or
  authenticated variants remain diagnostic only and cannot satisfy the proof.

## 2026-08-16 — Theme 3.2.48 Licensed Journal Typography Recovery

- Restored the Journal label and control voice to Dalton's licensed Tiempos
  Text files only when the resolved Studio label token remains the approved
  `tiempos-text` default; valid custom choices such as Lora remain authoritative.
- Kept the route-owned typography boundary to Journal kickers, filters, sort,
  provenance, calls to action, and pagination while preserving titles, excerpts,
  prose, display typography, media, queries, and editorial state.
- Added route-aware licensed-font delivery with Georgia and Times New Roman as
  stable zero-network fallbacks, plus native, delayed, and forced-missing-font
  lifecycle coverage at 390, 768, and 1440 pixels.
- Preserved exact first-paint ordering at variables priority 6, critical seed
  priority 7, and linked route stylesheet priority 8; retained the bounded
  Journal CTA border correction after settled and dynamic parity remained green.

## 2026-08-15 — Theme 3.2.47 Exact Journal Recovery Reissue

- Reissued the independently approved Theme 3.2.46 Journal product tree after
  its automatic production rollback, with no runtime, layout, query, media,
  Studio, or editorial behavior changes.
- Changed only the public theme version, this changelog entry, and matching test
  version locks so future deployment and rollback evidence cannot confuse the
  recovery candidate with the prior 3.2.46 canary identity.
- Bound release validation to the corrected, immutable Theme 3.2.43 production
  script baseline while keeping responsive media, alt text, root-lead health,
  and route-root integrity as absolute candidate gates rather than parity
  allowances for known legacy defects.

## 2026-08-15 — Theme 3.2.46 Journal Lead First-Paint Recovery

- Preserved the complete Journal Archive Studio and responsive-media system
  while correcting the production-canary interaction that made the page-one
  lead image wait on the deferred shared image-reveal runtime.
- The unpaged root Journal lead now paints synchronously at full opacity with
  no fade in both the critical seed and canonical route stylesheet. Ordinary
  cards, page 2+, taxonomy archives, image selection, `srcset`, and editorial
  ownership remain unchanged.
- Expanded exact-aggregate browser coverage to prove the lead is visible
  without the runtime-loaded class, and instrumented the production gate with
  layout-shift source rectangles plus lead resource/class timing.
- Theme 3.2.45 failed its direct production canary and was automatically rolled
  back to exact Theme 3.2.43. This 3.2.46 recovery remains local until its full
  suite, independent review, CI, and prepared rollback gates are complete.

## 2026-08-15 — Theme 3.2.45 Journal Responsive Media Repair

- Preserved existing native Journal archive card markup byte-for-byte whenever
  the registered route image already supplies a valid responsive source set.
  Attachments missing that set now recover through an uncropped native source
  first, then a bounded HTTPS WordPress.com Image CDN width set when eligible.
- Enforced honest local attachment provenance, intrinsic dimensions, strictly
  ascending candidates, a 768-pixel source-width floor, and fail-closed
  text-led cards for deleted, remote, undersized, non-image, or invalid media.
  The settled 16:10 archive-card chamber and object-fit treatment are unchanged.
- Limited visual lead treatment, lead language, eager loading, and high fetch
  priority to the first card on the unpaged root Journal archive. Paged and
  taxonomy archives retain their existing query order with uniform lazy cards.
- Added an environment-signed payload comparator for matched non-production
  evidence while retaining two absolute production `/journal/` limits:
  190,000 decoded bytes total and 118,000 decoded bytes after subtracting
  exactly one measured Boost critical block. Redirected origins/routes,
  duplicate blocks, or byte/hash drift fail closed and cannot normalize either
  production limit.
- Added isolated PHP, JavaScript, static, and exact-aggregate browser coverage.
  This local theme-only candidate does not alter plugins, content, WordPress
  options, query ordering, cache/CDN settings, global CSS, or route geometry.

## 2026-08-15 — Theme 3.2.44 Journal Archive Studio

- Added a focused Journal Archive Studio that aggregates the archive's existing
  WordPress owners into one bounded editorial surface while preserving those
  owners as canonical storage. Editors can control identity, public labels,
  filter caps, item count, seven-section visibility/order, lead and curated
  files, retention cards, and a page-one archive gallery without changing
  global reading settings.
- Made lead behavior explicit and deterministic across every root Journal sort:
  automatic selects the newest eligible file, shared uses the homepage lead and
  falls back to newest, and manual validates one published Journal file. Curated
  and lead priorities remain unique across pagination, with stable ID
  tie-breakers; taxonomy archives remain query-native.
- Added strict last-valid promotion, bounded revision/audit history, restore,
  and private preview-on-demand with nonce, owner, expiry, 403, and no-store
  protections. Invalid input cannot replace public state, and the Studio does
  not publish, schedule, rewrite, or mutate Journal posts.
- Added three editable retention cards and a bounded, ordered archive-only media
  gallery with attachment validation, responsive intrinsic image markup,
  editorial alt/caption/credit/source/link ownership, visible provenance, and
  local broken-media fallback. Empty or failed media emits no blank chamber.
- Moved Journal archive structure into one route-owned, server-rendered visual
  system while retaining the shared Lunara font tokens. Route CSS is delivered
  synchronously and remains under 40 KiB; generated variables plus the critical
  seed remain under 8 KiB.
- Locked a portable browser regression to the exact Theme 3.2.43 production
  Boost critical CSS and 847,152-byte aggregate. Root Journal, alternate order,
  hidden-Hero, populated/empty media, and taxonomy scenarios at 390, 768, and
  1440 pixels all hold delivery, critical-withdrawal, and seed-removal geometry
  within the one-pixel release gate.
- This is a local theme-only candidate. It does not alter plugins, content,
  WordPress options, Jetpack Boost, cache/CDN state, staging, or production.

## 2026-08-15 — Theme 3.2.43 Reviews Mobile Card Legibility

- Repaired image-less Review support cards at 390 and 782 pixels so their
  single copy child occupies the full archive card instead of being trapped in
  the media-backed 104-pixel poster column.
- Scoped the image-less-card override to the Reviews archive's renderer-owned
  wrapper and uniform grid; media-backed mobile cards retain their poster/copy
  columns and desktop cards retain the existing composition.
- Hid the purely decorative Pairing Desk card numerals below 680 pixels in the
  canonical component asset, eliminating arbitrary role/title collisions on
  both Home and Reviews without reflowing copy or changing tablet/desktop
  treatment.
- Preserved the independently approved 3.2.42 first-paint structural seed
  byte-for-byte, kept the route asset within its 45 KB budget, and added a real
  browser regression covering Home and Reviews at 375, 390, 782, and 1440
  pixels, including text-versus-numeral intersection checks through the
  Reviews first-paint phases.
- This is a local theme-only candidate. It does not alter plugins, content,
  WordPress options, cache, CDN, staging, or production.

## 2026-08-15 — Theme 3.2.42 Reviews First-Paint Stability

- Preserved the reviewed Reviews Archive Studio and composition work from
  3.2.40/3.2.41 while repairing the production-only interaction with stale
  Jetpack Boost critical and aggregate CSS.
- Added an 11,387-byte universal structural guard in `wp_head`, bounded by a
  12,288-byte contract; the full 37,271-byte visual system remains external and
  cacheable instead of returning to the former 43 KB inline cascade.
- Kept the `lunara-review-archive` stylesheet direct, unaggregated, and
  render-blocking through handle-specific Jetpack Boost filters while leaving
  every unrelated stylesheet's optimizer state untouched.
- Moved Archive Studio geometry/order variables to head phase with ID-level
  authority, preserving non-default saved lane order and density before stale
  optimizer CSS is withdrawn.
- Locked every rendered lane, Release Year controls, responsive lead geometry,
  and the complete Pairing Desk containing/card structure without hiding page
  overflow or changing public data/storage.
- Moved the settled route geometry under the renderer-owned `#primary.lra`
  wrapper, so the CPT archive, director
  taxonomy, explicit page template, and slug-selected default page share the
  same first-paint and settled layout without relying on body-class aliases.
- Added an actual-asset browser regression at 1440, 782, and 390 pixels for the
  default, Pairing-first, Hero-hidden, director, explicit-template, and
  slug-selected page compositions. It replays the
  archived 53,864-byte production Boost critical payload and the exact 821 KB
  stale aggregate, then proves deferred dependency delivery, critical removal,
  and the persistent seed each move geometry by no more than one pixel.
- Production approval now explicitly requires a real-iPhone Safari smoke on
  staging because the compact route-owned first-paint guard uses native CSS
  nesting; desktop emulation alone is not sufficient for this release.
- This is a theme-only staging candidate. No plugin, post, production, cache,
  CDN, or WordPress-option changes are part of the release.

## 2026-08-15 — Theme 3.2.41 Reviews Studio Mobile Containment

- Made the Reviews Archive lead label and selector shrink to their Site Studio
  card at 390 px and 375 px without hiding overflow.
- Scoped the repair to the private Site Studio Reviews panel; public rendering,
  lead storage, archive composition, and WordPress options are unchanged.
- Added a portable production-markup/CSS contract plus an actual-CSS browser
  regression that verifies document, workspace, label, and selector widths.

---

## 2026-08-15 — Theme 3.2.40 Reviews Archive Composition

- Added a focused Reviews Archive Studio for canonical copy, bounded lead
  curation, four-lane visibility/order, and existing density geometry.
- Repaired featured-card anchor semantics and made every saved lane position
  truthful, including Review Grid's utility toolbar and Pairing Desk.
- Composed the pinned lead into SQL ordering, added a no-JS Review Year filter,
  preserved pagination/query state, and repaired the one-item last-page pager.
- Preserved native attachment candidates, bounded canonical TMDB delivery to
  `w342`/`w500`/`w780`, and kept below-fold archive media lazy.
- Extracted approximately 42 KB of static Reviews CSS into a route-scoped,
  cacheable asset while retaining under 2 KB of dynamic variables inline.
- No plugin, post, production, cache, CDN, or WordPress option writes are part
  of this release.

---

## 1. Repository map

| Repo | Purpose | Version at end of period |
|---|---|---|
| `lunara-theme-blocks` | The WordPress child theme. Nearly all front-end logic, all Control Desk admin UI, homepage composition, entity dossier templates. | **3.1.53** |
| `lunara-plugin-oscars-ledger` | The Academy Awards database: master table, reporting/derivation tables, admin UI, entity graph builder. | **2.7.73** |
| `lunara-plugin-core` | Shared content models: Reviews CPT, the entity graph (`movie`/`person`/`ledger_entry`), Debrief/Trinity ACF fields, Essay Builder fields. | **0.4.1** |
| `lunara-plugin-imdb-guard` | IMDb/TMDB validation, poster/backdrop sync, local batch poster-import toolkit. | **0.4.0** |
| `lunara-plugin-dispatch` | Journal/dispatch content tooling. | 3.0.15 (untouched this period) |
| `lunara-plugin-ai-assistant-classic` | AI assistant provider integration. | 0.6.0 (untouched this period) |

**Live site:** lunarafilm.com is WordPress.com Atomic with GitHub Deployments.
Dalton Johnson (site owner) deploys each repo's `main` branch manually after
merge — a merged PR is not automatically live. Always confirm deploy status
before assuming a fix is visible to readers.

**Branch convention:** all work in this period happened on
`claude/tmdb-key-rotation-9hsb3b` in every repo, merged to `main` via PR,
generally same-day.

---

## 2. Timeline

### Era 0 — Pre-existing state (before 2026-07-01)
Theme was on an older 3.1.x line; Oscars plugin had the master
`wp_academy_awards` table (Dalton's own uploaded data, ground truth) but no
reporting/derivation layer beyond basic display. No entity graph existed.
Homepage was Customizer/registry-rendered only. No live search, no Essay
Builder, no Hero Command.

### Era 1 — Oscars portal + hero stabilization (2026-07-01 → 07-02)
*Theme 3.1.17 → 3.1.21 · Oscars 2.7.61 → 2.7.66 · IMDb Guard 0.3.0*

- Oscars portal landing page redesigned: carousels, review schema, social
  cards, newsletter capture (theme 3.1.17).
- Portal cinematic layer moved to a critical-path shell block for LCP
  (3.1.18); Reviews page refit with a pinnable lead review (3.1.19);
  Pairing Desk homepage showcase introduced and hardened through several
  passes (3.1.21–3.1.23).
- **Atmosphere V1** shipped (3.1.24): grain, dolly, reduced-motion handling —
  the first deliberate motion-design layer. Still Gallery shot reel (3.1.25),
  reveal-system reconciliation (3.1.26), Pairing Desk copy made editable from
  Control Desk (3.1.27).
- Hero carousel instability surfaced and was chased across three releases
  (3.1.28–3.1.30: deck preload, Atmosphere v2, fail-open mount) before the
  **root cause** was found in 3.1.32: the hero's fade CSS was written for
  Splide v3 semantics but the site runs Splide v4, which fades via
  `translateX` stacking, not `opacity`/`display`. Slides were rendering but
  invisible. Fixed at the CSS layer, not by working around symptoms.
- Oscars side: guide-text repair, local-first imagery so poster/backdrop
  delivery doesn't depend on live TMDB calls, dossier hero posters
  (2.7.61); ceremony and category dossier premium passes (2.7.62–2.7.64);
  poster importer + Portrait Queue unstuck (2.7.65); legacy migration UI
  retired and non-public blocks hidden from the inserter (2.7.66).
- IMDb Guard 0.3.0: one-click "Fill Missing Posters" batch backfill.

### Era 2 — Design Spec 2.0, sprints 1–2 (2026-07-03 → 07-04 early)
*Theme 3.1.31 → 3.1.38*

Dalton supplied `LUNARA_FILM_DESIGN_SPEC 2.0` (19 numbered sections) as the
governing design document for the rest of this period. Work from here
forward is tracked against that spec.

- 3.1.31: block palette cleanup across theme + Oscars + Dispatch (unused
  Gutenberg blocks hidden from the inserter).
- 3.1.32–3.1.34: the hero fade fix (above) plus hero recomposition, Pairing
  Desk archive styling, and an "exclusivity claim" line making Pairing
  Desk's uniqueness legible to first-time visitors.
- **Sprint 1** (3.1.35, §5/§10): star-rating cascade animation, magnetic
  cursor-pull interactions on priority surfaces (fine-pointer only,
  reduced-motion off).
- **Sprint 2** (3.1.36, §2/§7): the Monolith letterbox effect (scroll-driven
  cinematic bars using native CSS scroll-timelines, not GSAP — survives
  script failure) and the "Method" footer curtain reveal.
- GSAP layer proper (3.1.37, §5): character-rise heading animation via
  SplitText, Trinity card fan-out — the two effects that actually needed
  GSAP over native CSS.
- 3.1.38: GSAP asset paths fixed for WP.com's deployer (which excludes
  `vendor/` directories — GSAP moved to `assets/lib/gsap/`), Latest Reviews
  homepage section restored after being silently dropped.

### Era 3 — The entity graph is born (2026-07-04)
*Theme 3.1.39–3.1.43 · Oscars 2.7.67–2.7.72 · Core 0.2.0*

This is the highest-leverage work of the period: turning the Oscars master
table into a real, queryable knowledge graph instead of a flat spreadsheet.

- **Core 0.2.0**: registers `movie`, `person`, `ledger_entry` as first-class
  post types with full ACF schema (release year, studio taxonomy, relational
  directors/cast, runtime, TMDB backdrop, Where-to-Watch repeater per
  spec §4A). Models only — nothing populated yet.
- **Oscars 2.7.67**: the Entity Graph Builder — a resumable, batched
  admin-driven process that derives `movie`/`person`/`ledger_entry` content
  from the master table. Handles tens of thousands of rows via AJAX
  self-looping + chained cron fallback.
- **2.7.68**: accuracy pass. Two real bugs fixed: (a) naive substring
  category matching had misclassified some ART DIRECTION credits as
  DIRECTING — replaced with AMPAS `award_class` + word-boundary regex; (b)
  release years were off by one because the code subtracted 1 from a field
  that already held the correct year.
- **2.7.69 "The Living Graph"**: integrity audit with one-click heal,
  auto-resync on data import, and a daily heartbeat cron that self-heals
  drift between master/reporting/graph without manual intervention.
- **2.7.70–2.7.71**: Winner Flag Backfill — the bundled dataset
  (`data/oscars.csv`) is winner-complete even where the live master table
  isn't; a format-immune matching key (ceremony + normalized category +
  sorted person/film token set) finds and flags the missing winners
  regardless of how differently the two datasets format the same row.
- **2.7.72 Name Repair**: U+FFFD mojibake in imported names (e.g.
  "Chloé Zhao" corrupted to "Chlo?-Zhao") healed by resolving the correct
  name via TMDB's `/find` endpoint against the IMDb bridge ID, then
  byte-exact `REPLACE()` across graph posts, `aat_entities`, and the master
  table.
- **Theme 3.1.39 "Phase 2B"**: the graph gets front-end surfaces — `/film/`
  and `/talent/` dossier templates, archive index pages, JSON-LD (§11/§15).
  3.1.40–3.1.42: archive page-size enforcement (the first attempt used
  `pre_get_posts` priority 99, which ordered correctly but didn't override
  page size — fixed with `PHP_INT_MAX` + explicit `posts_per_archive_page`),
  WP Rocket self-purge on every theme version bump (a real problem: Rocket's
  Remove Unused CSS was serving stale used-CSS after deploys, explaining
  several "it looks different for me" reports).
- **3.1.43**: Oscar award-history ordering flipped to nominations-first,
  win as the finale, per Dalton's direct request.

### Era 4 — Relational Trinity, Hero Command, Phase 4 (2026-07-05 morning)
*Theme 3.1.44–3.1.46 · Core 0.3.0*

- **Core 0.3.0 + Theme 3.1.44**: the Debrief "Pair It With" trinity (Theme
  Echo / Counter-Program / Career Context, spec §13) upgraded from free-text
  fields to real ACF relationship pickers pointing at `movie` entities. The
  renderer tries the relational field first and falls back to the legacy
  text-parsing path when unset — so existing reviews keep rendering
  identically until an editor links a film.
- **Theme 3.1.45 "Hero Command"**: not in the original spec — added because
  Dalton explicitly asked for "total granular control" of the homepage hero
  after finding the overlay/truncation behavior too rigid. An admin-curated,
  ordered slide deck (any post type, any order, any count) with a global +
  per-slide overlay-intensity dial, replacing the old single-checkbox
  "feature this post" workflow as the primary curation surface.
- **Theme 3.1.46 "Phase 4 cutover"**: the entity graph stops being an
  island. Reviews grow a "Film Dossier" rail card linking to the matching
  `/film/` page (matched via the review's IMDb ID against the movie
  entity's IMDb ID — bidirectional bridge); footer gains Film Index /
  Talent Index links, gated on the entity post types actually being
  registered.

### Era 5 — Derivation integrity, the last two winners (2026-07-05)
*Oscars 2.7.73*

- Two documentary winners (*The True Glory*, ceremony 18; *First Steps*,
  ceremony 20) were flagged `winner=1` in master but produced no row in the
  derived facts table — found via the integrity audit's `lost_winner_rows`
  query. **Root cause:** slug-style local-name entity IDs
  (`lnm-the-governments-of-great-britain`, 36 chars) exceeded the reporting
  tables' `varchar(32)` entity-ID columns; strict-mode MySQL silently
  rejected the whole insert.
- **Fix, three layers deep** so this class of bug can't recur silently:
  (1) a versioned schema migration widens all six entity-ID columns to
  `varchar(64)`; (2) every derivation insert is now wrapped in a guard that
  clamps to column width, retries once with invalid UTF-8 stripped, and
  logs (table + row + SQL error) anything that still fails; (3) the
  integrity audit gained a **full derivation census** — every master row
  must produce a facts row, winner or not, not just a winner-only check
  (which had been hiding non-winner losses of the same kind).
- Result after this shipped and the builder re-ran: facts winners
  3,515/3,515, zero rows lost in derivation.

### Era 6 — Design Spec closeout: search, essays, blocks, GEO (2026-07-05 daytime)
*Theme 3.1.47–3.1.51 · Core 0.4.0*

- **3.1.47**: Essay Builder front-end renderer (prose, pull-quote, inset
  frame, video spread, cinematic banner — spec §12) paired with **Core
  0.4.0**'s ACF Flexible Content field registration (capped at 20 modules
  per §19A). Also formalized the spec's §18 fluid-clamp type tokens.
- **3.1.48**: Live Global Search — one public REST endpoint
  (`lunara/v1/search`) sweeping reviews, journal, films, and talent in a
  single query, surfaced through a ⌘K/`/`-triggered command-palette
  overlay (spec §6/§9). JS-off degrades to the normal `/?s=` results page.
- **3.1.49**: GSAP motion extended to the surfaces it hadn't reached yet —
  entity dossier headings, index grids (batched `ScrollTrigger.batch` so
  long grids don't all animate at once), award-record rows, and Essay
  Builder modules (with a slow scale-settle on the cinematic banner).
- **3.1.50 "Hybrid homepage composition"**: resolved Dalton's "I want
  granular control and this doesn't feel like it" complaint about the
  homepage editor showing shortcode-like content instead of real blocks.
  The Home page's Gutenberg blocks now ARE the homepage — reorder by
  dragging, hide/show by delete/insert, every block server-side-rendered
  live in the editor. `front-page.php` forks on whether the front page
  contains any Lunara section block: if yes, blocks render; if no, the old
  Customizer-registry path runs untouched (the built-in rollback). Homepage
  Studio's publication-package presets still work — applying one now
  rewrites the block list to match instead of writing theme_mods only.
  The hero block's render callback was also corrected to call the
  Hero-Command-aware carousel renderer instead of the old static hero.
- **3.1.51**: closed three spec items in one release — JSON-LD `@graph`
  consolidation with stable `@id`s so a Review's `itemReviewed` and its
  film dossier are the same graph node (§11/§15); `/llms.txt` plus
  per-nomination machine-readable award strings for AI-search retrieval
  (§16 GEO); progressive `@view-transition` cross-fades on same-origin
  navigation (§17). A pre-ship unit test caught a real ordinal bug
  ("93th" instead of "93rd") before it shipped.
- **Core 0.4.1**: pure label change. The `movie` CPT surfaced in wp-admin
  as "Movies," which read as duplicate/competing content next to the
  Reviews menu — renamed to "Film Dossiers" (and `person` → "Talent") to
  make the distinction between *your writing* (Reviews) and *the reference
  graph* (Film Dossiers/Talent) legible in the admin UI. No functional
  change — CPT keys, slugs (`/film/`, `/talent/`), and ACF locations are
  untouched.
- **IMDb Guard 0.4.0**: the local TMDB poster-batch PowerShell toolkit
  (previously living only on Dalton's machine, with a hardcoded API key)
  moved into version control with the key removed from every file
  (resolved from an env var, a git-ignored local file, or an interactive
  prompt — never committed) and a one-click manifest exporter added to the
  plugin's admin screen.

### Era 7 — The masthead flash (2026-07-05 evening)
*Theme 3.1.52–3.1.53*

Reader-reported: the homepage's LUNARA FILM wordmark rendered giant and
centered for roughly half a second on load, then snapped down into its
real two-column layout. Diagnosed in two passes, both confirmed against a
screen recording and (for the second) a live browser probe measuring the
logo element's bounding box every animation frame:

- **3.1.52**: gave the logo `<img>` its settled width/height as an inline
  style attribute (via CSS custom properties so responsive breakpoints
  still override it) so no caching/optimization layer could defer it, and
  excluded the front-door style block from WP Rocket's Remove Unused CSS
  pipeline. This fixed the "giant" half of the symptom but not the "snap."
- **3.1.53**: found the real cause — the masthead's two-column
  `grid-template-columns` rule lived in a function literally named
  `..._first_viewport_polish_css` but hooked to `wp_footer` at priority
  135. The browser painted the entire first viewport single-column and
  only reflowed once the parser reached the bottom of a ~500KB document.
  Moved the hook to `wp_head` (verified the CSS has no rendered-content
  dependency that required footer timing) and added it to the same Rocket
  exclusion list. Live probe after 3.1.52 measured a real 475ms single→
  two-column reflow; re-verification of 3.1.53 once deployed is the open
  item (see §4 below).

---

## 3. Current state (as of last commit in this period)

- **Design Spec 2.0 completion: 14 of 19 sections fully shipped, 5 in
  documented polish** (clamp-token migration across legacy call sites,
  mobile HUD formal audit, homepage rhythm coherence pass, and two backlog
  items — Atmosphere v3, content-ops tooling — that were never numbered
  spec sections to begin with).
- The Oscars data is canonically correct: master table winner-complete at
  3,515 rows, facts table matching exactly, 5,249 `movie` posts / 8,427
  `person` posts / 12,118 `ledger_entry` posts, daily self-healing
  integrity heartbeat active.
- All shortcode-stored page content was migrated to native block markup
  (`academy_awards` → `academy-awards/database`, `lunara_awards_tracker_v2`
  → `academy-awards/tracker-v2`, `lunara_reviews` → `lunara/reviews`) via
  direct content edits — no code change required, since the target blocks
  already existed and simply weren't being used by those pages.
- Homepage composition is hybrid: blocks own the page, Studio packages
  write through to the same blocks.

## 4. Open items / next up

1. **Verify 3.1.53 live.** A live browser probe (Playwright against the
   real homepage, sampling the masthead logo's bounding box per animation
   frame) was built and run once against 3.1.52's fix — it caught the
   footer-hook bug. The same probe needs to run again once 3.1.53 is
   deployed; expected result is a single unchanging geometry sample.
2. **Clamp-token migration.** §8/§18's fluid-type tokens
   (`--lunara-display-text`, `--lunara-header-text`, `--lunara-body-text`)
   exist and are used by new surfaces (Essay Builder); legacy `font-size`
   declarations elsewhere in `style.css` haven't been migrated onto them
   yet. Do this in 2–3 bounded passes with before/after screenshots at
   375/768/1440px — not one big sweep — so any regression is attributable.
3. **Mobile HUD formal audit** (§9/§14): a scripted 390px sweep across all
   template types checking `touch-action`/`overscroll-behavior` on every
   horizontal rail and tap-target sizing. Not started.
4. **Homepage coherence pass**: one deliberate read of the fully-composed
   front door at three widths for rhythm/spacing/duplicate-signal issues.
   Not started.
5. **Atmosphere v3** (not a numbered spec item, a standing backlog entry):
   Tracking Shot, Dust Motes, Hero Breath — the remaining motion-design
   pieces from the original Atmosphere roadmap.
6. **Content-ops tooling** (recommended, not requested): a Trinity backfill
   screen to bulk-convert legacy Pair-It-With text into the new relational
   picker fields, and an auto-grow-on-publish hook so a review with an
   unrecognized IMDb ID spawns its own `movie` entity instead of waiting
   for the next full graph rebuild.
7. **Reliability tooling + the Blocksy exit** (recommended, discussed with
   Dalton, not started): a deploy-truth panel in Control Desk showing
   GitHub `main` version vs. live version per repo; CI lint/smoke gates;
   and — as a larger, explicitly staged project — building a Lunara-owned
   header/nav to replace the last real Blocksy dependency, ending on a
   one-line `Template:` removal from `style.css` (safe because theme_mods
   are keyed to the stylesheet directory name, not the parent).

## 5. Standing operational notes

- **Security**: the TMDB API key that was hardcoded in Dalton's local
  `.bat` launchers (now fixed in `imdb-guard` 0.4.0 — key never committed)
  should be rotated at themoviedb.org and re-entered in IMDb Guard's
  settings screen. Unconfirmed whether this has been done.
- **Deploys are manual.** A merged PR is not live until Dalton deploys that
  repo's `main` via WP.com's GitHub Deployments. Do not assume a fix is
  visible to readers from a merge notification alone — check the live
  version (`style.css` header for the theme; there is no external version
  probe for the plugins) or ask.
- **Data provenance**: `wp_academy_awards` (the master table) is Dalton's
  own manually uploaded data and is ground truth. He is the sole uploader.
  Never treat the bundled `data/oscars.csv` dataset in the Oscars repo as
  more authoritative than the live master table — it's a supplementary
  winner-completeness reference used only for format-immune backfill
  matching, not a replacement source.
- **Rollback pattern used throughout this period**: every additive
  feature (Hero Command, hybrid homepage composition, the Trinity
  relational fields) was built to fall back to its pre-existing behavior
  when unconfigured/unpopulated, rather than requiring a flag flip or code
  revert to undo. Preserve this pattern in future work on this codebase.
