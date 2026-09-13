# Lunara completion plan

Dalton authorized completing all seven steps, quickly without sacrificing
quality. This checklist remains open until implementation and public acceptance
are complete. A candidate, passing local test or merged PR is not a live result.
Manual WordPress.com deployment remains Dalton's action; independent work can
continue while a release awaits deployment.

September 13 update: Dalton confirms Academy 2.7.84 and Theme 3.2.76 are
verified published live. A fresh agent canary also passes for
`3.2.76+20260913-225857`: three anonymous reads agree and Journal/Oscars both
report `LIVE_COHERENT`. Detailed route acceptance below stays explicit;
deployment confirmation does not imply every remaining reader-journey check ran.

Theme 3.2.77 is now merged through PR #197 at `72a0ea1`, with all 95 required
GitHub gates green and the exact rollback hatch rebuilt. Manual deployment and
its public canary/editor acceptance are pending; last verified live theme is 3.2.76.

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
  Two anonymous GETs separately confirm current build/headings. Missing artwork,
  gallery variants and anonymous visual acceptance remain outside that sample.
- [ ] Resolve any remaining reader-journey defects exposed by that acceptance.

## 4. Academy detail pages

- [x] Academy 2.7.84 mobile dossier corrections merged: readable headings,
  44px actions, compact statistics and bounded portraits preserving saved fit
  and focal point. Captured public-template fixtures pass 859 assertions across
  four route types and four widths. This is not live candidate acceptance.
- [ ] Verify deployed ceremony/category/film/person output at phone and desktop
  widths, including long names, absent art and links between record types.
- [ ] Accept the corresponding shared presentation editor from step 2.

## 5. Shared site experience

- [x] Release Header Command navigation correction in live Theme 3.2.76: one Search action,
  meaningful current-page state, 44px controls and usable no-JavaScript links.
  Current candidate passes 64 PHP and 55 browser assertions; review fixes landed.
- [x] Implement footer/search/recovery navigation and content ownership in
  merged Theme 3.2.77; deployment acceptance remains open above.
- [ ] Replace twelve live inner main wrappers while keeping header.php's main
  landmark. Nested landmarks were confirmed on both sampled public articles;
  the next correction is isolated from the merged 3.2.77 release.
- [ ] Complete a public route matrix covering landing pages, articles, Academy
  records, search results, no results and 404; verify keyboard access, focus,
  reduced motion, readable narrow layouts and no horizontal overflow.

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
- [ ] Implement a measured stylesheet/image-loading improvement with unchanged
  visual output and safe route/cache behavior. Conservative route pruning alone
  saves only roughly 4-6KB gzip; do not overstate it as the full solution.
  Current candidate extracts 16,115 bytes of unchanged static Journal geometry
  into a synchronous route asset. Public acceptance and overall loading impact
  remain pending; 34 delivery and 412 article checks pass locally.
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

## Release acceptance for each batch

Focused tests and deliberate mutations precede the full required theme suite.
Independent review must be resolved before release. Merge tested commits, rebuild
the standing theme rollback hatch after every theme merge, deploy plugins before
the theme, and verify the actual public identity/canaries after Dalton's click.
Record results in SESSION-LOG.md and carry every unfinished item forward.
