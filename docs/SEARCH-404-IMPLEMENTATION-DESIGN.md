# Search and 404 shared editor: implementation design

Read-only source inspection, 2026-09-13, against current 3.2.76 candidate. No source/production changes. Footer links and generic-page landmarks remain the separate Footer design owner's work.

## Recommended scope and ownership

Keep existing surface `utility-search` / owner `theme:utility-search`; add the remaining three existing Search settings. Add separate surface `utility-404` / owner `theme:utility-404`, with ten existing copy fields and the existing primary-destination choice. Reuse the shared Preview/Apply/Discard/History controller and mod transactions; no new persistence API or carousel library.

The nine existing Search presentation/focus/geometry controls still affect both Search and 404 through `lunara_output_utility_search_studio_css()` in inc/frontend.php:5254. Do not expose duplicate writers for those same mods in the 404 inspector. Link to Search's shared appearance controls and explain their cross-route effect. Give each surface truthful directory labels/descriptions; replace Search's current Classic 404 guidance with a guarded link to the new 404 editor.

## Search fields and explicit adoption

Current spec: inc/site-studio-adapters.php:957. Existing state groups `presentation` (4), `focus` (2), `geometry` (3); preserve their names/order.

Append a `content` group:

| Path | Canonical mod | Default / proposed validation |
|---|---|---|
| content.kicker | lunara_search_kicker | Search Desk; plain text, proposed 120 characters |
| content.no_query_title | lunara_search_no_query_title | Search Lunara Film; proposed 220 characters |
| content.excerpt_words | lunara_search_excerpt_words | 22; existing UI range 10–50, integer step 1 |
| content.use_empty_title | NEW lunara_search_no_query_title_enabled |false; boolean exposed as Use this heading for an empty search |

The fourth field is the necessary activation choice for one dormant existing setting, not another copy setting. There are 12 migrated existing values and one explicit adoption flag. A layout-only Apply must preserve this false flag. Display any previously saved dormant heading in its field, with clear inactive guidance. Only enabling the choice and Apply makes that heading public; Preview demonstrates it privately. Disabling it again restores the shipped empty-query heading while retaining the saved custom text.

Confirmed trap: search.php:300 currently hardcodes Search Lunara Film for an empty query and ignores lunara_search_no_query_title. Adding an unconditional get_theme_mod reader would activate old saved copy on deployment. Search kicker is already active at search.php:339; excerpt length is already active at search.php:229. Preserve them on read.

Use `value_scale: 1` / required on numeric controls to retain invalid-only local edits, maintain dirty navigation guards and block Preview/Apply without a request. Match server and UI limits. The proposed text limits are NEW save constraints: current Customizer text settings have no length cap. Do not silently replace existing overlong text with defaults when opening the editor or saving an unrelated field. Either preserve legacy raw text in read state and visibly require correction before Apply, or provide an explicit unchanged-value pass-through; do not use the generic capped reader unchanged. Legacy excerpt values outside 10–50 also need a visible correction state, not a hidden fallback.

## Search preview cases

Today both registry and pilot require /search/?q=Lunara, and exact-query validation refuses absent/empty q. That preview cannot demonstrate the newly editable empty-query heading.

Keep canonical token owner/route and existing default q=Lunara for compatibility. Add two exact server-defined preview cases: Results `{q:'Lunara'}` and Search start `{}`; same /search/ path. The controller must switch only among those validated parameter sets while preserving token+instance and updating its activePreviewUrl; use the integrated Ledger route-switching pattern, not an independent iframe setter. The resolver must accept exactly one listed case with no additional query keys. Do not allow arbitrary q or loosen duplicate/encoded-key detection. Empty q need not be accepted: omit it for Search start. Existing q=Lunara token URLs remain valid. Test a no-results query in actual renderer fixtures even if no third editor target is added.

The current private handler runs at template_redirect -1 before lunara_search_command_template_redirect at 0. Preserve that order: the Search router later synthesizes s and forces 200; authentication and exact raw-query validation must happen first. Mixed legacy lunara-utility-preset parameters must fail closed under private-token handling.

## Separate 404 state and preview

Use these 11 paths, each bound to its exact existing mod:

| Group | Paths / mods |
|---|---|
| hero | kicker→lunara_404_kicker; title→lunara_404_title; explanation→lunara_404_explanation |
| guidance | reset_label/reset_desc/fastest_label/fastest_desc/hubs_label/hubs_desc→matching lunara_404_* names |
| recovery | title→lunara_404_reentry_title; primary→lunara_utility_reentry_primary |

Primary is the existing enum home/reviews/journal/oscars/search, default home. It changes priority/order, not arbitrary URLs. Keep all five actual destinations and accessible labels. Do not introduce custom-link editing here.

Preserve defaults from active 404.php, not the Customizer. Concrete mismatch: lunara_404_explanation has a different default in inc/customizer.php:2498 than 404.php:106. Missing-mod public output must remain byte-equivalent until Apply. Preserve saved blank/long text and textarea newlines on read; generic mod reader currently sanitizes text and defaults long values, and does not support textarea in its read/validate branches although the generic inspector can render textarea. Use a scoped provider, or additive textarea support with regression checks for every other mod surface. Proposed new save limits: kicker/short labels 120, headings 220, descriptions 360, explanation 1000; these are proposed implementation limits to check against saved values, not existing constraints.

Suggested fixed missing preview route: /definitely-not-a-real-lunara-route/ (already used by Classic recovery previews). New queryarg lunara_404_preview; markers search-command/recovery already emitted by 404.php. Register the surface/pilot/validator/spec/host/JS marker inventory explicitly. Require exact same-origin/subdirectory-aware path, valid instance/token and actual is_404() before installing candidate filters. If that path ever becomes a real page, report preview unavailable rather than forcing that page into 404. No page/rewrite creation and no permalink flush.

Successful private 404 preview must retain HTTP 404, render actual 404.php, send existing private/no-store/noindex headers and preserve is_404. Disable canonical guessing only after successful private authorization. Invalid preview-shaped requests remain uniform 403; ordinary missing URLs remain normal public 404. Do not route the preview through /search/ or a 200 placeholder. A plain PHP include fixture cannot prove HTTP status: add a small request-lifecycle/HTTP harness.

## Compatibility: exact traps and bounded fixes

1. **Old Search revisions:** generic restore (adapters.php:1140) requires exact `mods` keys via valid_mod_snapshot(:197). Pre-expansion revisions contain only nine mods. Accept ONLY that exact known legacy list/order, validate entries strictly, then restore those nine while carrying current raw snapshots of all appended mods and adoption flag, including present:false. The new fields already had independent owners, so an old Search revision cannot claim to erase them. Capture a full current safety revision before writing. New complete revisions restore all owned fields and exact absence, including reverting first adoption.
2. **Old Search preview tokens:** get_private_preview(:1584) authenticates stored owner/route/user/hash/expiry, then resolver requires exact current validation/projection. Simply expanding the spec turns valid old nine-field token states into 403. After authentication only, recognize the exact legacy nine-field schema, validate against the frozen legacy spec and install only those nine request-local mod filters; appended fields continue using current public values/activation. Alternatively use an equally narrow compatibility projection that preserves those same semantics. Do not accept partial old-shaped POST bodies in the new save validator. Do not rewrite tokens or extend TTL.
3. **Raw preservation:** generic read-state normalization is not raw restoration. Use raw_mod_snapshot for revisions/rollback. Read/Preview/Discard must never persist defaults or strip original whitespace/newlines. Match Unicode length semantics between JavaScript and PHP; existing strlen byte-counting and browser maxlength character-counting differ.
4. **Failure rollback:** preserve existing transaction/readback/revision-failure rollback. A failed validation, write, readback or revision store must leave all mods, adoption flag and revision option exactly unchanged.
5. Coordinate any shared compatibility helper in adapters/preview with Footer implementation; do not independently broaden generic shape acceptance.

## Competing writers to retire after parity

- Remove controls AND setting registrations for exactly three Search mods and ten 404 mods in inc/customizer.php:2474–2546, leaving persisted theme mods intact. Do not remove lunara_header_search_placeholder: it shares the Search Customizer section but remains Header Command's owner.
- inc/control-desk.php:3527 admin_post_lunara_save_utility_search_studio currently writes all nine Search mods plus 404 primary; missing posted selects are replaced with defaults. Its preset branch at 3544 also writes these fields. Replace old panel at 9818 with truthful Search/404 handoffs and reject stale old form submissions before writes. Hiding the panel alone is insufficient.
- Legacy preset specs/apply helpers at 3277/3417 include shared Search and 404 values. Preserve saved compatibility marker lunara_utility_search_preset and read-only definitions until references are checked, but stop old write paths. Do not give one new editor an implicit Apply affecting the other surface. If preset packages are retained later, scope an explicit cross-surface transaction or project each surface's exact owned fields; not required for this minimal slice.
- Retain harmless old preset-preview links only if still intended; their capability-gated request overlay at frontend.php:5215 must not override authenticated Site Studio candidate state. Existing exact-query rejection already blocks mixing them; retain tests.

## Canonical route corrections

- search.php:461 Journal fallback currently points to /news/. Use get_post_type_archive_link('journal') with /journal/ fallback, matching current Header Command ownership. Do NOT use lunara_home_dispatch_archive_url() here: it can return a separately customized homepage CTA or legacy category, so it is not a canonical Journal resolver.
-404.php Search action currently uses /?s=; form targetsroot with name s. Use lunara_search_command_url() and name q when available, retaining the existing root/s fallback only if the helper is absent.
- Prefer existing Review archive helper for review links; use home_url for all relative destinations/subdirectory installs. Keep legacy /?s= and /news/ compatibility routes; fix outgoing UI links without deleting/redirecting unrelated URLs.

## Meaningful verification and wiring

Extend required tests/site-studio-editorial-contract.ps1 and site-studio-private-preview-contract.ps1, reusing editorial/runtime/shared-workspace/private-preview/bridge fixtures. Update the four theme-studio-utility-search*.ps1 source contracts intentionally: several currently insist Classic forms/admin-post/preset buttons exist. Replace obsolete writer-presence assertions with preserved enum/public behavior and stale-writer rejection, not blanket removal of checks.

Add actual search.php/404.php rendering matrix at 320/390/768/1440: blank/query/no-results/mixed Review+Journal+Oscar matches, long Unicode headlines/copy, missing artwork, no JavaScript, complete headings/body text, 44px targets, form route/query behavior and no overflow. Screenshot both compact and long-copy cases. Verify real 404 status and unchanged Search 200 behavior through request lifecycle.

Provider/workspace tests: all 12 existing Search settings plus adoption flag; all 11 404 fields; raw missing/blank/long/multiline defaults; dormant saved title unchanged on boot/layout-only Apply; Preview private activation; explicit Apply/reload; Discard/History; exact old 9-key revision and token compatibility; malformed near-legacy shape rejected; zero-write failures; cross-surface token/owner mismatch, missing route now real, expiry/anonymous/wrong-user/foreign host/subdirectory/duplicate query/mixed preset failures; allowed Results↔Search start preview keeps binding. Verify each exact dotted server error reveals/focuses its real control, and invalid-only numeric text remains dirty through unrelated actions.

Useful mutations: unconditional dormant-title read, incorrect 404 default, root/s form reintroduced, /news/ reintroduced, 404 status changed 200, token accepted on another route, old revision drops new mods, partial-state validator acceptance, stale admin-post writes, Preview field filter omitted. Each must fail a meaningful assertion.

Suggested next action: after 3.2.76 is accepted, implement Search/404 as one bounded batch beside the separate Footer work, agree the narrow shared legacy-compatibility hook first, then deliver one reviewed release with actual post-deployment route/status acceptance.
