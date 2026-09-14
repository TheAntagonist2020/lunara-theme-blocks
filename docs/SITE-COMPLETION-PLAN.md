# Lunara completion plan

Dalton authorized completing all seven steps, quickly without sacrificing
quality. This checklist remains open until implementation and public acceptance
are complete. A candidate, passing local test or merged PR is not a live result.
Manual WordPress.com deployment remains Dalton's action; independent work can
continue while a release awaits deployment.

September 14 update: Theme 3.2.81 is a local candidate that standardizes the
reader-facing controls for the Reviews companion rail and the Oscars
rotating-winners rail. It preserves the existing editor and content state while
adding the shared Pause/Play, 44px target, hover/focus pause, touch recovery and
reduced-motion contract. Theme 3.2.80 remains the verified public build until
this candidate is merged and manually deployed.

September 13 update: Dalton confirms Academy 2.7.84 and Theme 3.2.76 are
verified published live. A fresh agent canary also passes for
`3.2.76+20260913-225857`: three anonymous reads agree and Journal/Oscars both
report `LIVE_COHERENT`. Detailed route acceptance below stays explicit;
deployment confirmation does not imply every remaining reader-journey check ran.

Theme 3.2.78 is merged through PR #198 at `a272ec1`, including the preceding
3.2.77 Footer/Search/404 editor release. All 95 required gates pass locally and
in GitHub; the exact rollback hatch is rebuilt. Academy 2.7.85 is merged through
PR #30 at `6a36be0`. Deploy the plugin before the theme. Manual deployment and
public acceptance remain pending; last verified live theme is 3.2.76.

Theme 3.2.79 is merged through PR #199 at `5e1c943`: useful Journal gallery
controls, empty Search correction, Footer writer retirement, truthful Control
Desk handoffs and Search-dialog Tab containment/focus return. Independent review,
95 initial local gates, the affected header gate and final-head CI passed. The
exact rollback hatch was rebuilt. A postmerge anonymous homepage probe still
reports `3.2.76+20260913-225857`; deploy Academy 2.7.85 before Theme 3.2.79
through the existing WordPress.com connection, then run the versioned canary.

Theme 3.2.80 is a focused Oscar carousel accessibility candidate. Oscar Picks
has a persistent Pause/Play control and 44px pagination targets; Oscar Facts has
the same control through the Splide pilot. Hover, keyboard focus and touch
interaction pause playback, and reduced-motion visitors remain paused. The
candidate is local only; it is not merged, deployed or represented as live, and
it follows the pending 3.2.79 deployment handoff.

## 1. Oscars cleanup

- [x] Theme 3.2.75 public canary: all three anonymous reads agree on
  `3.2.75+20260913-211752`; Journal and Oscars are `LIVE_COHERENT`.
- [x] Authenticated Winner sections load cleanly: seven saved fields, latest
  ceremony fallback explanation and 7.2-second rotating-winner default.
- [x] Academy 2.7.84 implements current Production Design / Sound labels and
  modern aliases while retaining historical ceremony names and canonical IDs.
  Plugin PR #29 passed CI and merged as `2fea87341f1865142c253395064913c5cccb611c`.
- [x] Dalton confirms Academy 2.7.84 deployed and verified live.
- [x] Complete the sampled public matrix for modern aliases, old links and
  historical ceremony output: seven anonymous URLs return 200; category index,
  modern/old Production Design and Sound paths, and ceremonies 84/98 agree.
  Aliases resolve directly rather than redirecting. Exact cutoff boundaries
  remain covered by source tests; this was a bounded public sample.
- [ ] Release and accept the separate Film/Person/Company category-label fix
  in Academy 2.7.85. Public film inspection exposed an older entity formatter;
  candidate labels now follow each credit's ceremony and each category's own
  latest credit. Independent review and 234 runtime checks pass; PR #30 merged
  as `6a36be0` after green CI. Manual deployment and public acceptance remain.

## 2. One editing experience

- [x] Live Home, Review/Journal archives and Oscars portal share the established
  navigation, controls and canonical transaction system through Theme 3.2.75.
- [x] Release Journal article presentation controls in live Theme 3.2.76.
  The implementation uses seven canonical mods and the shared Preview/Apply/History
  transaction; article text, featured artwork and galleries retain their
  clearly named article-editor owner.
- [x] Complete Home Latest Reviews selection and artwork controls using the
  existing carousel editor. Preserve legacy presentation until explicit Apply;
  use published review dates and canonical poster metadata after adoption.
- [x] Complete Academy dossier presentation controls for ceremony, category,
  film and person previews. Retire old writers after equivalent behavior works.
- [x] Implement footer link ownership and Search/404 recovery controls in
  merged Theme 3.2.77. Footer inherits existing navigation until explicit Apply;
  all three editors use shared private Preview, Apply and History.
- [ ] Accept the new Footer/Search/404 editors after Theme 3.2.77 deployment.
- [x] Audit current editor ownership and close six competing Footer Customizer
  controls/settings in the 3.2.79 candidate. Saved values remain unchanged;
  actual registration, stale submission and shared lifecycle cases pass.
  Footer handoffs now reach Site Studio and the Journal reading guide reaches
  article presentation. Separate useful Journal Defaults controls remain.
- [ ] Verify zero/one/many selections, unavailable stories, long labels, image
  fit/focal points, independent modes, private previews, restore and dirty-state
  protection for every newly covered surface.

## 3. Review and Journal articles

- [x] Release typography/spacing/artwork corrections in live Theme 3.2.76. Actual-template
  fixtures currently pass 412 assertions across 32 cases at 320, 390, 768 and
  1440px with JavaScript disabled; full-image Journal framing now requests the
  uncropped source rather than a hard-cropped derivative.
- [ ] Verify representative public articles after deployment: long headlines,
  full/cover artwork, missing artwork, metadata, galleries and related stories.
  Dog Stars and Angel pass the sampled 390/1440px browser checks for complete
  headings, loaded artwork, metadata, no horizontal overflow and related links.
  Two anonymous GETs separately confirm current build/headings. A second signed-in
  sample verifies natural missing-art Manhunter and the two-image David Robert
  Mitchell gallery at exact 390/1440px, including loaded images, no empty hero,
  phone buttons and keyboard movement. Swipe, missing-art Review and anonymous
  visual acceptance remain outside these samples.
- [ ] Resolve any remaining reader-journey defects exposed by that acceptance.
  Desktop gallery arrows remain enabled when both images already fit. A reviewed
  controller follow-up is prepared on `codex/site-completion-next-3.2.78`: useful
  start/end states, layout/content refresh, and JavaScript/CSS reduced motion.
  Its 36 focused checks pass; formal release work remains open. It is not part
  of merged 3.2.78.

## 4. Academy detail pages

- [x] Academy 2.7.84 mobile dossier corrections merged: readable headings,
  44px actions, compact statistics and bounded portraits preserving saved fit
  and focal point. Captured public-template fixtures pass 859 assertions across
  four route types and four widths. This is not live candidate acceptance.
- [ ] Verify deployed ceremony/category/film/person output at phone and desktop
  widths, including long names, absent art and links between record types.
  Four sampled public routes have complete headings, bounded portraits and no
  horizontal overflow at 390/1440px. Full live CSS exposed remaining 40px theme
  actions and 30/28px plugin category actions. Theme 3.2.78 and Academy 2.7.85
  correct their original owners. The offline full-cascade regression passes
  3,438 checks at 320/390/1440px; deployed acceptance remains open.
- [ ] Accept the corresponding shared presentation editor from step 2.

## 5. Shared site experience

- [x] Release Header Command navigation correction in live Theme 3.2.76: one Search action,
  meaningful current-page state, 44px controls and usable no-JavaScript links.
  Current candidate passes 64 PHP and 55 browser assertions; review fixes landed.
- [x] Implement footer/search/recovery navigation and content ownership in
  merged Theme 3.2.77; deployment acceptance remains open above.
- [x] Replace twelve live inner main wrappers while keeping header.php's main
  landmark. Nested landmarks were confirmed on both sampled public articles;
  Theme 3.2.78 contains the independently reviewed correction, passes the full
  suite/CI and is merged through PR #198. Public acceptance remains open.
- [ ] Complete a public route matrix covering landing pages, articles, Academy
  records, search results, no results and 404; verify keyboard access, focus,
  reduced motion, readable narrow layouts and no horizontal overflow.
  A signed-in live-76 journey passed Reviews year filtering and page 2, a review
  to its Academy film, Search results, no-results recovery and 404 at 390/1440px.
  Empty Search incorrectly consumed its outer page query; the 79 candidate
  fixes this with an actual-template regression. Search dialog opening/focus
  passed; the later settled check found Tab escaping and Escape losing trigger
  focus. The final 79 candidate fixes both, including dynamic results and closing
  races. Repeat that journey after deployment. This sample does not replace
  anonymous or all-route acceptance.

## 6. Measured performance

- [x] Inspect current Boost settings: JPEG quality is already 82; PNG/WebP 80.
  Do not repeat the obsolete quality-100 change.
- [x] Capture anonymous HTML/CSS inventory for six representative pages.
  Combined CSS is approximately 789-963KB decoded; canonical style.css is
  521,438 bytes. This inventory is not a rendering-speed or Core Web Vitals test.
- [ ] Resolve Critical CSS targets that redirect or 404 under old
  `/oscar-picks/category/` and `/oscar-facts/category/` routes; identify their
  actual owner before changing taxonomy behavior or suppressing failures.
  Registrations are in functions.php. The current public failures were reproduced
  locally using WordPress's real rewrite generator: attachment rules precede and
  shadow the category rules. Theme 3.2.77 corrects registration order and narrowly
  promotes affected already-saved rules without a rewrite flush or option writes.
  Public acceptance of all affected category/feed/pagination routes remains open;
  missing saved rules are intentionally not synthesized by the compatibility fix.
- [ ] Regenerate valid Critical CSS after the final structural release through
  the authorized owner workflow, then verify coverage and failure state.
- [x] Verify Journal stylesheet extraction on live 3.2.76: two anonymous articles
  deliver the same synchronous 16,191-byte route asset with a long cache lifetime;
  canonical source hashes match after line-ending normalization. Per-page inline
  variables occupy 167 bytes. The saved 3.2.75 inline baseline was 14,860 bytes;
  16,115 was the expanded candidate static body before extraction, not the old
  public baseline. This proves reusable CSS delivery, not faster LCP or CLS.
- [x] Observe browser cache reuse across three Angel reloads and one Beyond Fest
  navigation: zero transferred bytes for the same 16,191-byte decoded stylesheet.
  Signed-in throttled timings with concurrent local tests are exploratory only;
  they do not establish anonymous visitor speed or controlled improvement.
- [ ] Establish loading impact under controlled browser conditions. Do not add
  source minification or speculative duplicate removal: existing Boost bundles
  already compact the CSS, leaving approximately 100 and 39-46 gzip bytes to
  gain respectively from those approaches in sampled public bundles.
  One official public PageSpeed API request returned HTTP 429 with daily quota
  zero. No Lighthouse metrics were obtained and no retry was attempted. An
  available approved anonymous browser or measurement service is still needed.
- [ ] Compare loading and layout shift on identical representative pages and
  conditions, including image and font requests. Stored Boost scores are not a
  controlled before/after benchmark.

## 7. Editorial readiness and opening lineups

- [x] Read-only publication inventory: 193 review records dated June 15 through
  September 13, 2026; approximate rendered bodies exceed 300 words. Publication
  dates cluster in July (184), with seven September and two June records in the
  window. This does not establish association eligibility or screening access.
- [ ] Prepare and review concrete Home hero / Journal / review lineups using
  real published work and actual artwork, then apply through the agreed owner.
  Proposed published-story lineups are recorded in EDITORIAL-READINESS.md;
  they have not been applied.
- [ ] Verify About, editorial policy, contact and archive discovery as a reader.
- [ ] Resolve the discrepancy between the association's currently retrievable
  requirements and older bylaws before presenting membership criteria as final.
- [ ] Identify any fresh coverage needed with Dalton. Do not invent reviews,
  screening attendance, access or publication history; new writing is separate
  editorial work.
  Manhunter's published final sentence is incomplete in stored content; restore
  the intended editorial copy through its owner, rather than treating it as CSS.
  Canonical raw-body inspection confirms the truncation. MCP revision calls did
  not support this Journal CPT. Subsequent read-only UI inspection covered seven
  of 31 versions, including the earliest and its successor; both already contain
  the fragment. No complete ending was found. Recovery has stopped; intended
  source wording or a reviewed replacement is needed.

## Release acceptance for each batch

Focused tests and deliberate mutations precede the full required theme suite.
Independent review must be resolved before release. Merge tested commits, rebuild
the standing theme rollback hatch after every theme merge, deploy plugins before
the theme, and verify the actual public identity/canaries after Dalton's click.
Record results in SESSION-LOG.md and carry every unfinished item forward.
