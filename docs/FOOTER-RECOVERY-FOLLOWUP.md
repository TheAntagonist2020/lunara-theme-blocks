# Footer and recovery: next implementation batch

Read-only inventory made September 13, 2026 against the Theme 3.2.76 candidate.
This work is **not implemented** in that release. Release and accept the current
batch before extending these owners. Source findings below are not fresh public
measurements.

## Active owners

- `inc/frontend.php`, `lunara_render_custom_footer()` (around line 1467), is
  the active footer. `lunara_use_custom_footer` defaults to **true** and disables
  Blocksy output. The nearby comment claiming Blocksy is the default is stale.
  `footer.php` is only the enclosing shell. Guarded copies in `functions.php`
  are not the editing target.
- The live footer builds three hardcoded lists: five Editorial links, four
  Oscars links, and Search/Contact/RSS plus Privacy when configured. Its separate
  legal row also uses WordPress's Privacy URL. The registered `footer-*` menu
  locations in `inc/setup.php` are not consumed by this renderer; preserve their
  saved menus rather than silently repurposing them.
- `lunara_site_studio_footer_spec()` in `inc/site-studio-adapters.php` owns six
  existing mods: logo visibility, tagline, three column headings and copyright.
  It does not own the navigation lists.
- `lunara_site_studio_utility_search_spec()` owns nine existing Search fields:
  four presentation choices, two result-focus choices and three geometry values.
- `404.php` reads ten existing `lunara_404_*` copy mods plus
  `lunara_utility_reentry_primary`. Its actual preview sections are
  `search-command` and `recovery`. The primary-route control is still in
  `inc/control-desk.php`; copy controls are in `inc/customizer.php`.

## Suggested complete slice

Give 404 recovery its own shared Site Studio editor and authenticated preview
of a fixed missing route. Migrate its ten copy fields and primary destination
through the existing mod provider. A Search-only preview cannot demonstrate
404 changes. Keep public missing pages as genuine 404 responses.

Extend the current Search editor with the remaining existing Search settings:
kicker, empty-query title and excerpt length. Correct the active recovery
destinations at the same time: `search.php` currently labels `/news/` as the
Journal, while the 404 Search action/form still use the older root `s` query.
Use the canonical Journal and Search helpers without removing legacy URLs.

Extend the existing Footer provider with one canonical links family for three
ordered columns. Use `LunaraEditorControls.orderedList` from
`assets/js/lunara-editor-controls.js` for drag ordering and keyboard movement,
with the established Preview/Apply/Discard/History lifecycle. Keep inherited
lists until explicit Apply. A bounded proposal is at most twelve links per
column, stable item keys, enabled state, plain-text label and destination.
Built-in destinations should resolve current archive/feed/Privacy URLs when
rendered; an optional custom URL must not freeze those dynamic defaults.
Preserve the logo, footer shell and legal row under their existing owners.

## Migration traps

- `lunara_search_no_query_title` is registered in the Customizer but ignored by
  the live `search.php` title reader. Making an old saved value active on read
  would alter public copy before Apply. Adopt its new behavior explicitly.
- The generic mod revision validator requires exact keys. Older six-field
  Footer revisions and preview tokens must preserve the current missing links
  family, raw values and mod absence rather than fail or erase new settings.
- Reject invalid structure, overlong labels and unsafe URLs before any writes.
  Reuse the strict HTTP(S)/root-relative URL rules demonstrated by
  `lunara_oscars_portal_studio_navigation_url()`; retain query and fragment
  semantics. Empty explicitly applied lists should stay empty.
- Retire competing Customizer/Control Desk writers only after parity, including
  stale-open-editor boundaries. Do not delete the persisted settings.
- Footer link targets currently have a 38px minimum in the late footer CSS and
  `assets/css/lunara-public-guardrails.css`; verify actual geometry and raise
  phone targets to the shared 44px minimum in the next layout pass.
- Subsequent normal-browser inspection of Editorial Policy and Contact found
  nested main landmarks. Both pages' content and the Contact email link are
  readable; fix the enclosing generic-page template landmark during this pass.
  No contact message was sent and inbox delivery was not tested.

## Acceptance

Reuse the editorial provider/workspace and private-preview gates, utility
focus/preset contracts, and actual public-route fixtures. Cover blank/query/no
result Search, genuine 404, desktop/mobile footer, empty/one/many links, long
labels, keyboard ordering, safe URLs, dynamic destinations, private isolation,
zero-write validation failures, Apply/Discard, exact rollback and old/new
History compatibility. Verify actual public output after the user's deployment.
