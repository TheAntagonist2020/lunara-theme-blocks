# Lunara Film — Session Log

**Purpose:** a durable, append-only record of *what happened in each working
session* — what shipped, what was verified, what is live, what is still open,
and what was decided. It exists so that no session's progress is ever lost to a
closed tab, an expired container, or a summarized conversation.

**How this differs from the other two documents:**

| Document | Answers |
| --- | --- |
| `docs/CHANGELOG.md` | *What changed in the code, and why.* Per release. |
| `docs/GO-LIVE-RUNBOOK.md` | *How a change gets deployed and proven.* Timeless. |
| `docs/SESSION-LOG.md` (this) | *Where the project stands right now, and whose move it is.* Per session. |

Newest entry first. Never edit a past entry to make it agree with the present —
if a past entry turns out to be wrong, add a correction line inside it saying so
and pointing at the entry that supersedes it. The value of this file is that it
is honest about what was believed at the time.

**Every session that changes code, ships a release, or changes what is live
must append an entry here before it ends.** See `AGENTS.md` at the repo root
for the required shape and the standing workflow rules. (`CLAUDE.md` points
there; `AGENTS.md` is the single canonical copy.)

---

## 2026-09-12 — Theme 3.2.70 acceptance and 3.2.71 Reviews opening

### Headline

Theme 3.2.70 is verified live. Dalton identified the public Reviews Command
panel as inappropriate for the reader-facing opening. Theme 3.2.71 removes
that panel and condenses the introduction while retaining the saved page
identity and story selection.

### Verified live state

| Probe | Observed result |
| --- | --- |
| Explicit-version 3.2.70 canary | Three anonymous reads agree on `3.2.70+20260912-174826`; Journal and Oscars both `LIVE_COHERENT`, exit 0 / GO. |
| Public Reviews route in browser | Theme 3.2.70; the large introduction and Reviews Command panel precede the toolbar and first film. This is rendered page content, not an editor-only overlay. |

### What shipped and why

See `docs/CHANGELOG.md` for the compact Reviews opening candidate. The obsolete
Classic label inputs are retired without deleting saved labels or revisions.
No agent deployment or manual cache purge occurred. Theme 3.2.71 is not
deployed; its live canary is pending.

### Commit ledger

| Repo | Commit or release reference | Meaning |
| --- | --- | --- |
| Theme | `59cffc42c2f7797e7034c0544873b92034294589` / PR #190 | 3.2.70 merged on main and verified live. |
| Theme | `codex/reviews-opening-3.2.71` | Compact Reviews opening candidate based on that main commit. |

### Gate ledger

- Versioned production canary for 3.2.70: exit 0 / GO.
- Full required theme contract suite: 95/95 passed.
- Syntax/structure checks: 129 PHP files, 65 JavaScript files and 26 CSS files;
  zero failures. Release identity was rechecked after the record update.
- Actual Reviews renderer runtime: 117 assertions passed, plus the existing
  provider checks. Ten desktop/mobile browser cases passed without JavaScript;
  saved long text remains readable and the removed panel never appears.
- Mutation test: restoring the public panel fails its intended assertion;
  restoring the fixed renderer matches its SHA-256 and passes again.
- Additional legacy Reviews first-paint runtime: FAILED at 390px on both the
  untouched 3.2.70 baseline and this candidate. See the bounded finding below.
  This extra failure is not included in, or represented as, the 95 passing gates.
- Independent renderer/CSS review found no further issue in the opening change.
  Final syntax, CI, merge identity and exact rollback proof are retained in the
  release receipt alongside the local gate logs.
- Production canary for 3.2.71 is not run until Dalton deploys it.

### Corrections

None. The previous session's 3.2.70 deployment-pending state is superseded by
the live probes above.

### Logged, not fixed

The extra stylesheet-delivery simulation found a pre-existing mobile root-width
conflict. Historic Boost CSS starts the 390px archive at 362px; current base CSS
later changes it to 390px. The route asset and critical seed are already present
but do not own that root width. The exact 3.2.70 baseline has the same conflict.
The candidate reduces default simulated delivery movement from 100.875px to
52.21875px; seed withdrawal itself causes zero movement. Three mobile archive
scenarios fail, while tablet, desktop and the other route classes pass. No
mobile width change was added to this release. This simulation is not a live
measurement; explicit mobile width ownership and live delivery verification
remain a separate follow-up. Logs and isolated baseline assets are in the
`reviews-opening-3.2.71` artifact folder.

The rest of the site, archive card presentation, large sorting toolbar and
broader stylesheet diet remain outside this bounded opening correction.

### Punch-list carried forward

- Complete and merge 3.2.71, then rebuild the exact rollback hatch: agent.
- Manual WordPress.com deployment and reader review of the opening: Dalton.
- Continue the shared editor and premium public-layout work after acceptance.

### Whose move it is next

The agent finishes verification and the release handoff. Dalton then uses the
manual deployment in WordPress.com for `lunara-theme-blocks` from `main`; the
agent verifies the versioned public canary after the deployment is reported.

## 2026-09-12 — Theme 3.2.69 acceptance and 3.2.70 archive selection

### Headline

Theme 3.2.69 is verified live after Dalton's deployment. Home, Reviews, Journal
and Oscars have their page workspaces. The next bounded delivery, 3.2.70,
migrates archive lead and priority-story selection into that shared editor.

### Verified live state

| Probe | Observed result |
| --- | --- |
| Explicit-version 3.2.69 canary | Three anonymous reads agree on `3.2.69+20260912-151630`; Journal and Oscars both `LIVE_COHERENT`, exit 0 / GO. |
| Signed-in Site Studio | Default Home workspace reports Live settings loaded; four primary page links and contextual Home editors are present. |
| Reviews workspace navigation | Reviews becomes the active page; Content, Layout, Advanced and History groups load. No public settings were saved. |

### What shipped and why

See `docs/CHANGELOG.md` for the archive story selection candidate. Existing
presentation remains in place until its new selection controls are adopted and
applied. No agent deployment or manual cache purge occurred. Theme 3.2.70 is not
deployed; its live canary is pending.

### Commit ledger

| Repo | Commit or release reference | Meaning |
| --- | --- | --- |
| Theme | `9869110a460273a651fc77bdf265fe9e7218b8c5` / PR #189 | 3.2.69 merged on main and now verified live. |
| Theme | `codex/archive-selection-3.2.70` | Archive selection candidate based on that verified main commit. |

### Gate ledger

- Versioned production canary for 3.2.69: exit 0 / GO.
- Archive selection browser runtime: 123 checks passed across Reviews and
  Journal at 1440px and 390px. Coverage includes independent modes, retained
  choices, drag/keyboard order, missing artwork, empty/maximal lists, validation
  focus, delayed responses, Preview/Apply/Discard, reload and legacy history.
- Real provider runtime additions: 59 Reviews and 50 Journal assertions passed,
  including canonical pin ownership, private Manual versus Automatic query/SQL
  ordering, old revisions, unavailable Classic rows and metadata permissions.
  The unchanged public shell consumes the query's first result; these additions
  exercise the actual query/SQL chain, not a newly rendered full public page.
- Existing editorial workspaces and page navigation checks passed. Screenshots
  use actual PHP inspector markup and fixture artwork; they verify editor layout,
  not candidate deployment or final public-page art.
- Mutation: removing metadata refresh after Preview unfreezes the editor failed
  the interrupted-read regression. The working file was restored by copy with
  an identical SHA-256. A subsequent narrow focus fix also passes the browser
  suite: refreshed rows retain the focused Remove action.
- Independent review found and closed stale unactivated selection overwrite,
  Classic unavailable-ID loss/title disclosure, and refresh-related keyboard
  focus loss. No other actionable findings remained.
- Two initial static archive checks expected row markup inside each provider.
  They now verify the shared row owner and its executable hidden-field behavior;
  both complete archive gates pass on focused reruns. Initial logs are retained.
- Full theme contract suite: 95/95 passed after the two focused archive reruns
  above (initial pass 93/95). The final workspace gate includes the focus fix.
- Syntax: 128 PHP, 64 JavaScript and 26 CSS files, zero failures.
- No plugin code changed. No public settings or article content changed.

### Corrections

The previous entry records the earlier 3.2.67 observations honestly. The new
canary above supersedes its deployment-pending state without changing that
historical evidence.

### Logged, not fixed

- Archive artwork/gallery and retention controls still need shared migration.
- Oscars portal controls and Academy record authoring remain distinct work.
- Public opening compositions, shared headers, mobile typography, Boost Critical
  CSS, CDN quality and the base stylesheet diet remain open.

### Punch-list carried forward

- Candidate verification complete. Agent: merge after GitHub checks and rebuild
  the exact rollback hatch.
- Dalton: manual WordPress.com deployment after the verified main handoff.
- Agent after deployment: explicit-version canary and real archive editor acceptance.
- Next: remaining Oscars controls and the public layout pass under the recorded
  site experience standard.

### Whose move it is next

Agent: merge the verified archive selection candidate after GitHub checks and
rebuild the hatch. Dalton retains the manual WordPress.com deployment action.
The broader site work remains open.

## 2026-09-12 — Theme 3.2.69 page workspaces and site standard

### Headline

Dalton wants one intuitive editing suite and a premium, recognizable experience
across Home, Reviews, Journal and Oscars. The audit found shared transactions
already in place but incomplete control migration and fragmented navigation.
Theme 3.2.69 groups editors by page; the broader standard and measured backlog
are in `docs/SITE-EXPERIENCE-STANDARD.md`.

### Verified live state

| Probe | Observed result |
| --- | --- |
| Explicit-version 3.2.68 canary | All three anonymous reads still served `3.2.67+20260911-195706`. Journal and Oscars failed the expected-version binding; exit 1, not GO. |
| Signed-in public browser | Home, Reviews, Journal and Oscars also served 3.2.67. Reported deployment has not been verified. |
| Four-page visual inspection | Reviews opens with a large statistics/identity panel; Journal places identity, counters and filters above story images; header text navigation differs across routes. |
| Oscars at 390px | Document width remains 390px; main heading hyphenates ordinary words across lines. Public typography fix remains open. |

### What shipped and why

See `docs/CHANGELOG.md` for the candidate's navigation and inspector changes.
No agent deployment or manual cache purge occurred. Theme 3.2.69 is not
deployed; its live canary is pending.

### Commit ledger

| Repo | Commit or release reference | Meaning |
| --- | --- | --- |
| Theme | `6efe56141cb4ef5cbb6bdec62583b97f2d031d9b` / PR #188 | 3.2.68 merged on main; reported deployment is not yet confirmed by public probes. |
| Theme | `codex/site-studio-pages-3.2.69` | Page navigation, inspector grouping and site experience standard, based on current main. |

### Gate ledger

- Full theme contract suite: 95/95 passed, including the new navigation browser
  regression in the existing workspace gate.
- Targeted shared PHP runtime, four-width workspace browser run and five-surface
  editorial browser run passed. Initial test expectations were corrected for
  the intentionally collapsed directory and archive Layout's item-count field.
- New navigation coverage checks page/context links, permission filtering,
  unavailable destinations, local search, no-JavaScript keyboard navigation,
  unsaved-change cancellation/acceptance, and pending-preview navigation locks.
- Mutation: removing the new links from the dirty-navigation guard failed the
  first primary-page cancellation assertion. The fixed JS was restored by copy
  and its SHA-256 matched the saved file exactly.
- Desktop/mobile Home and desktop Reviews fixture screenshots were inspected.
  They use real PHP editor markup with stubbed preview content; they are not
  proof of a candidate deployed to WordPress.
- Independent review found one misleading ledger destination label, corrected
  to Ledger layouts. No other actionable review findings remained.
- Syntax: 125 PHP, 62 JavaScript and 26 CSS files, zero failures.
- No plugin code changed. No public settings or article content changed.

### Corrections

None to prior measured evidence. The 3.2.68 deployment report is recorded
separately from the observed 3.2.67 responses; it is not treated as a passing
canary or attributed to caching without further evidence.

### Logged, not fixed

- Archive lead/manual ordering, gallery and retention controls still need
  shared-editor migration; existing canonical provider fields are identified
  in the site experience standard.
- Oscars portal controls and Academy record authoring remain distinct work.
- The public design findings above, Boost Critical CSS, CDN quality and the
  stylesheet diet remain open.

### Punch-list carried forward

- Candidate verification is complete. Agent: merge and rebuild exact rollback hatch.
- Dalton: manual WordPress.com deployment of the verified main handoff.
- Agent after deployment: explicit-version canary and real editor verification.
- Next implementation: Reviews/Journal lead and ordered selection in Site
  Studio, followed by the remaining Oscars controls and public layout pass.

### Whose move it is next

Agent: merge the verified candidate. Dalton: manual WordPress.com deployment
after the main commit is supplied. The broader site work continues from the
recorded standard, not from an assumption that navigation alone completes it.

## 2026-09-11 — Theme 3.2.67 acceptance and 3.2.68 Oscar artwork

### Headline

Theme 3.2.67 is verified live after Dalton's deployment. The shared editor's
device viewport is stable. Theme 3.2.68 adds the missing Oscar Picks and Facts
artwork controls to the same Site Studio workflow; see `docs/CHANGELOG.md`.

### Verified live state

| Probe | Observed result |
| --- | --- |
| Versioned 3.2.67 canary | Three reads agree on `3.2.67+20260911-195706`; Journal and Oscars both `LIVE_COHERENT`, exit 0 / GO. |
| Signed-in Oscar Picks editor | Live settings loaded; desktop iframe 1440 by 900, tablet 768 by 1024 and mobile 390 by 844; return to desktop remains stable. |
| Public mobile Oscar Picks | One complete card fits inside the panel; visible artwork loads in a roughly 327 by 205 pixel frame. |

The initial checks still returned 3.2.66 while deployment and cached responses
caught up. A later complete canary passed. No cache purge was used to obtain it.

### What shipped and why

The 3.2.68 candidate connects shared artwork controls to both homepage Oscar
renderers. It preserves existing saved lineups, source articles and Fact visual
approval, while making per-placement framing reviewable through private Preview.
No agent deployment or manual cache purge occurred. Theme 3.2.68 is not
deployed; its live canary is pending.

### Commit ledger

| Repo | Commit or release reference | Meaning |
| --- | --- | --- |
| Theme | `3d3fd866ad4f09906ef90ed72b7af22d58cc0b48` / PR #187 | 3.2.67 main merge, now publicly verified. |
| Theme | `codex/oscars-framing-3.2.68` | Oscar artwork implementation and acceptance record, based on current main. |

### Gate ledger

- Versioned production canary for 3.2.67: exit 0 / GO.
- Oscar editor browser regression: 87 checks passed. A screenshot run adds two
  narrow-inspector overflow checks; its sample images are test fixtures.
- Artwork PHP contract: 66 cumulative checks, including the 20 existing Oscar
  transaction assertions. Private preview: 18 checks with no public writes.
- Actual public renderers and complete stylesheet cascade: 1,227 browser checks
  across 320, 390, 768, 820 and 1440 pixels, with JavaScript disabled and with
  the generic carousel controller enabled.
- Reset-race mutation: removing request invalidation/restart reproduces an
  eight-second readiness timeout. Restored fixed JS was hash-verified exactly.
- Full release run: 94/95 passed initially; the only failure was homepage CSS
  size (64,929 bytes against the unchanged 60 KiB limit). Removing indentation
  reduced it to 61,315 bytes without changing non-whitespace content. All 11
  contracts referencing that stylesheet were then rerun and passed. Effective
  final contract result: 95/95 passed; the original failed log is retained.
- Syntax: 123 PHP, 61 JavaScript and 26 CSS files, zero failures.
- No plugin changed; plugin tests are not repeated.

### Corrections

None to prior recorded evidence. Initial stale public responses were retained
as failed attempts, not counted as a successful deployment.

Public fixtures exposed pre-existing Facts rules that hid inactive stories
without JavaScript, clipped long headlines and positioned generic navigation
over the footer. Corrected these along with the inherited column flow and fixed
hero height that prevented a readable static layout.

### Logged, not fixed

- The Oscars portal's presentation and the Academy database authoring screens
  remain separate work from homepage Picks and Facts.
- Fresh coverage and the current Odyssey lineup remain editorial decisions.

### Punch-list carried forward

- Complete local checks, merge the candidate and rebuild the exact rollback
  hatch after the merge.
- Dalton performs the manual WordPress.com deployment; the agent then runs
  the explicit-version public canary and checks the real editors.
- Follow up with Oscars portal layout and editor consistency.

### Whose move it is next

Agent: finish candidate verification and release preparation. Dalton: manual
WordPress.com deployment after the verified main-branch handoff.

## 2026-09-11 — Journal carousel live; Theme 3.2.67 preview correction

### Headline

Theme 3.2.66 is now verified live and the Journal Carousel editor loads normally.
Applied Dalton's requested Journal carousel: six newest published Journal
stories, seven-second rotation, and the existing "Fresh movement from the
Lunara Journal" heading. The old uneven grid and its July lead are gone from
the anonymous homepage. A separate shared preview sizing defect was reproduced
and corrected in the Theme 3.2.67 candidate; see `docs/CHANGELOG.md`.

### Verified live state

| Probe | Observed result |
| --- | --- |
| Theme canary with explicit 3.2.66 argument | All three reads report `3.2.66+20260911-104401`; Journal and Oscars both `LIVE_COHERENT`, exit 0 / GO. |
| Journal editor | Live settings loaded; private Preview became current; Apply returned "Changes applied." |
| Anonymous canonical homepage after Apply | HTTP 200; six new Journal cards; old grid absent; old Tom Cruise lead absent from the Journal section. |
| Desktop 1536px / tablet 768px / mobile 390px | Three / two / one visible cards, respectively; no horizontal document overflow. Equal card and image dimensions within each viewport. |
| Six Journal images on mobile | All loaded; each frame measured approximately 327 by 205 pixels. |
| Public carousel controls | Drag advanced the slide; Enter on Next advanced it again; pause/play was available. |
| Reduced motion / JavaScript disabled | Reduced motion kept slide 1 stationary with a polite live region. Without JavaScript all six stories remained in a readable single-column mobile grid. |
| Shared editor preview before correction | Its mobile iframe grew beyond 377,000 pixels. This is an editor canvas defect, separate from the corrected public card layout. |

### What shipped and why

Journal activation is a user-requested presentation change on existing Theme
3.2.66, through Site Studio's private Preview and Apply workflow. Hero selection,
Method, Oscars lineups and article content were not edited. The automatic
Journal lineup ignores old featured ordering and retains any manual list.

No agent deployment or manual cache purge occurred. Theme 3.2.67 is not
deployed; its live canary is pending. The existing runtime dependencies are
working after the Codex restart; no additional installation was needed.

### Commit ledger

| Repo | Commit or release reference | Meaning |
| --- | --- | --- |
| Theme | `1aea52ca07764e048019481852a9b1d5400384bb` / PR #186 | 3.2.66 main merge, now publicly verified. |
| Theme | `codex/site-studio-viewport-3.2.67` | Preview correction and this acceptance record, based on current main. |

The standing rollback PR #159 was rebuilt after PR #186. Rebuild and prove
tree-exactness again after the next merge, including a documentation merge.

### Gate ledger

- New regression first failed against the shipped controller: iframe height
  grew from 18,400 to 35,900 pixels over eight animation frames.
- Corrected controller: 14 browser scenarios passed, including both observer
  paths, device changes, private Preview, Apply and keyboard access below the fold.
- Reintroducing the original scroll-height controller was caught by the new
  regression; the corrected file was restored before the full release run.
- The local interactive helper initially rejected its unmapped escaped JSON
  URLs. Corrected that development-only remapping and verified editor startup
  in the browser; its CJS entry point also passes syntax checking.
- Full release suite: 95/95 contracts passed. Syntax: 121 PHP, 60 JavaScript
  and 26 CSS files, zero failures; the local CJS helper was checked separately.
- The first full run was stopped after stale escaped version expectations were
  found; those expectations were advanced before restarting. This was not a
  passing release run.
- Core tests were not repeated because no plugin changed.

### Corrections

A successful deployment did not itself activate the new Journal presentation.
The explicit Apply boundary preserved the old grid until this session. The
3.2.66 bootstrap fix made that operation possible. Live verification also found
that earlier small-height preview fixtures did not exercise viewport-sized
heroes followed by long pages; the new regression covers that feedback loop.

### Logged, not fixed

Oscar Pick/Fact artwork framing, Academy record authoring, the broader mobile
layout review and the remaining base stylesheet diet stay on the backlog.

### Punch-list carried forward

- 3.2.67 release gates are complete. Merge and rebuild the exact rollback hatch.
- Dalton performs the manual WordPress.com theme deployment from main.
- Then verify the actual Site Studio preview dimensions and 3.2.67 canary.
- Next editorial/design pass: Oscar Pick/Fact artwork framing using the same
  predictable control vocabulary. Fresh coverage remains editorial work.

### Whose move it is next

Agent: finish the prepared release and report its actual state. Dalton: manual
WordPress.com deployment of the merged theme. No additional carousel activation
is required for the public Journal layout fixed here.

## 2026-09-10 — Theme 3.2.66 editor bootstrap candidate

### Headline

Dalton deployed Core 0.8.11, then Theme 3.2.65. Both versions are verified live.
All six missing Review poster/backdrop pairs were recovered through the dedicated
Retry movie artwork button. Live editor acceptance exposed a separate shared
bootstrap failure; Theme 3.2.66 corrects it and is the next release candidate.

### Verified live state

| Probe | Observed result |
| --- | --- |
| WordPress.com active plugin inventory | Core 0.8.11 active. |
| Theme anonymous canonical canary | Build `3.2.65+20260910-210003` in all three reads; Journal and Oscars both `LIVE_COHERENT`, verifier exit 0 / GO. |
| Reviews 102632, 102631, 102630, 102628, 102627, 102626 | Each now has saved `_lunara_tmdb_poster_url` and `_lunara_tmdb_backdrop_url`, hydration `ready`, and an empty provider issue. |
| Before/after inspection of those six Reviews | Body, title, excerpt, published status and card/hero/Debrief choices unchanged. Review 102632's Markdown metadata also retained. |
| Comparison Review 102629, The Invite | Existing artwork retained; no retry or article save performed. Its older OMDb diagnostic remains. |
| Public Review pages and archive | All six canonical pages return 200 and reference their recovered poster/backdrop filenames. All six responsive poster files return 200 / image/jpeg; the archive uses those filenames at responsive sizes. |
| Live Homepage Oscar Picks, Facts and Hero editors | All three initially entered recovery state with Preview / Apply disabled. The public canary does not cover authenticated editor startup. |

### What shipped and why

Core 0.8.11's direct TMDB path works in production. Six targeted retries were
content-maintenance actions; no bulk backfill or article-form submission ran.
Theme 3.2.66 changes the shared configuration transport and private preview
bridge; see `docs/CHANGELOG.md` for implementation detail.

No agent deployment or cache operation occurred. Theme 3.2.66 is not
deployed; its live canary is pending.

### Commit ledger

| Repo | Commit or release reference | Meaning |
| --- | --- | --- |
| Core | `37670e307934c6d4a95af35074a185e4eae87b43` / PR #34 | 0.8.11 merge, now deployed and artwork recovery verified. |
| Theme | `dce54d909eaebbe45152044592f1062fb8f5935f` / PR #185 | 3.2.65 merge, now publicly verified. |
| Theme | `codex/site-studio-bootstrap-3.2.66` | New topic branch from current main; this record travels with the correction. |

The standing rollback PR #159 was rebuilt after PR #185 and remains
tree-exact to `c55bf394594149db2888295c5d51f85f47b2b520`. Rebuild it after
the next theme merge as required.

### Gate ledger

- The first canary attempts hit local CRLF and WSL PATH problems; they did not
  prove a pass. Running the LF-normalized script with Git Bash completed the
  actual live protocol and returned GO. No repository script was changed.
- Reproduced the editor failure by making the PHP localization stub match
  WordPress's scalar conversion. The protocol assertion failed, and the real
  PHP-rendered browser workspace could not become ready.
- After correction, the workspace and private-preview PHP suites passed;
  the Oscars browser suite passed 22 checks, including ordering, mode retention,
  mobile preview, private Preview, Apply and Discard.
- Full theme release suite: 95/95 contracts passed. Syntax: 121 PHP, 59 JS
  and 26 CSS files, zero failures. Reverting the workspace transport or the
  preview transport was caught by the corresponding regression test; both
  mutations were restored. The unchanged Core suite was not repeated.

### Corrections

The earlier local harness preserved scalar types that WordPress localization
changes. Its passing editor tests therefore did not prove live startup. The
production scripts matched the deployed repository files; this was a transport
bug, not a stale-file problem. Public canary GO remains valid for its scope.

### Logged, not fixed

Full Movie enrichment still has the separate OMDb connection issue. Review
artwork now bypasses it successfully. Pick/Fact artwork framing, Academy record
authoring and the next mobile layout pass remain open.

### Punch-list carried forward

- Finish and merge 3.2.66, then rebuild the exact rollback hatch — agent.
- Dalton performs the manual theme deployment through WordPress.com; verify
  both live Oscars editors and their private previews afterward.
- Continue with uniform artwork controls and mobile framing for Oscar Picks
  and Facts, then Academy record editing. Editorial lineup curation remains separate.

### Whose move it is next

Agent: finish the correction's release checks and merge/hatch preparation.
Dalton: manual WordPress.com theme deployment after that handoff.

## 2026-09-10 — Theme 3.2.65 homepage Oscars candidate

### Headline

Dalton confirmed his deployment. Read-only plugin inventory verified Core
0.8.10 active, and its new Review diagnostic identified OMDb failures before
artwork lookup reached TMDB. Core 0.8.11 now removes that artwork dependency;
PR #34 is merged. The next focused theme batch adds shared Homepage Oscar
Picks and Facts editors, without expanding into Academy record authoring.
See `docs/CHANGELOG.md` for the implementation detail.

### Verified live state

| Probe | Observed result |
| --- | --- |
| WordPress.com active plugin inventory | `lunara-core/lunara-core` is 0.8.10. |
| Review 102631 One Night Only and 102629 The Invite | Redacted provider issue names OMDB lookup failure; hydration status is identity_only. The Invite retains its existing artwork URLs. |
| Seven recent Review metadata records | Six still lack saved poster/backdrop URLs. They have not been represented as repaired. |

The preceding entry records Theme 3.2.64 build and GO. Those earlier theme
probes were not repeated for this code-only batch.

### What shipped and why

Core 0.8.11 is merged for manual deployment. Theme 3.2.65 is the local release
candidate accompanying this record. Its two independent editors reuse the
existing shared selection/order controls and private preview/save system.
Legacy preserves saved lineups until a selection mode is explicitly changed;
the new controls do not rewrite articles. The old Homepage curation entry
points to Site Studio and cannot compete with it.

### Commit ledger

| Repo | Commit or release reference | Meaning |
| --- | --- | --- |
| Core | `ba3572263de862618a7142746cbb5006e4feb63a` | 0.8.11 implementation, 24 suites and three caught mutations. |
| Core | `37670e307934c6d4a95af35074a185e4eae87b43` | PR #34 merge, 19:50:12 UTC; CI run 34522380321 passed. |
| Theme | `774e426e278817861c4281378d413cf1efe8e3ca` | Main base for `codex/homepage-oscars-studio-3.2.65`; candidate and this record travel in the same functional PR. |

### Gate ledger

- Core: 24/24 regression suites; syntax on 50 PHP and 5 JS files; 6 CSS
  files balanced. Identity mismatch, OMDb recoupling and unauthorized-retry
  mutations were each caught and restored.
- Theme focused tests: 20 adapter/query checks, 12 request-local preview
  checks, and 22 real-browser editor checks passed.
- Full theme release contracts: 95/95 passed. The older mobile renderer
  fixture initially lacked the new selection helper; it now loads the real
  helper and passes, including 305 mobile layout checks and 38 navigation /
  reduced-motion checks. Syntax passed on 121 PHP and 59 JS files; 26 CSS
  files balanced. Empty-manual fallback, manual-order and preview-write
  mutations were all caught and restored. Final changed-file checks passed.
- No deployment, cache operation, production write, or verification of a live 3.2.65 release occurred.

### Corrections and open work

The preceding entry's unknown server-side provider cause is now narrowed to
OMDb by the deployed redacted diagnostic. It does not establish whether the
TMDB credential is valid; only a successful deployed retry can prove that.
Missing sources remain IDs 102632, 102631, 102630, 102628, 102627, 102626.
Layout/image framing beyond the existing mobile fixes, reusable media tools,
Review authoring, Journal Desk and Academy authoring remain subsequent work.
The pre-existing Critical CSS regeneration, CDN quality and stylesheet diet
items remain open. No work on those settings occurred in this batch.

### Next move

The theme gates are complete. Merge the functional PR and rebuild exact
rollback PR #159 against the resulting main. Dalton then performs the manual deployment
in WordPress.com Settings → Repositories: Core first, then theme. Verify the
versioned theme canary and actual public homepage after his click, then retry
the six affected Reviews and check their saved URLs and rendered images.

## 2026-09-10 — Core 0.8.10 poster retrieval prepared; six live sources missing

### Headline

Dalton asked to verify Review images through `_lunara_tmdb_poster_url`.
The live audit found six recent Reviews without saved poster/backdrop URLs,
and Automatic source ordering could prefer older artwork over a saved TMDB
poster. Core 0.8.10 fixes source priority and late-metadata queueing, and fixes
a film-lookup control that unexpectedly submitted the parent Review during
this investigation. Core PR #33 is merged; no agent deployment was triggered.
The six missing sources have not been filled or represented as repaired live.

### Verified live state

| Evidence | Observed result |
| --- | --- |
| Theme identity and canary, 18:47 UTC | Theme `3.2.64+20260910-184351`; exit 0 / GO; Journal and Oscars `LIVE_COHERENT`, detailed below. |
| Active Core inventory during artwork audit | `lunara-core/lunara-core`, version **0.8.8**. Core 0.8.9 on main was not evidence of a deployed plugin. |
| Review Artwork Audit census | 268 Reviews; 268 canonical IMDb identities; 6 missing poster, 6 missing banner, 116 protected custom selections. Historical completed pass covered 261, excluding seven newer Reviews. |
| Ten inspected Review records | Six recent records lack both TMDB URLs. The Invite and three older sampled Reviews have saved TMDB posters. Four sampled saved poster URLs returned HTTP 200 and JPEG content. |
| Public `/reviews/` DOM | Six missing-source Reviews are text-led. The Invite uses an uploaded image despite a saved TMDB poster; older sampled Reviews use local or TMDB images. Offscreen lazy images were not classified as broken. |
| Native WordPress.com Repositories, inspected after the audit | All seven connections use `main` with Auto Deploy Off. Core targets `/wp-content/plugins/lunara-core`; theme targets `/wp-content/themes/lunara-theme-blocks-20260513-2300`. No connection settings changed. |

Missing-source IDs: **102632** The Dog Stars (`tt21285562`), **102631** One Night
Only (`tt37853455`), **102630** Spider-Man: Brand New Day (`tt22084616`),
**102628** Tony (`tt33095251`), **102627** Teenage Sex and Death at Camp Miasma
(`tt35298123`), **102626** The End of Oak Street (`tt27165187`). Local OMDb reads
returned matching identities and poster availability for these six; this is
not proof that the server's TMDB connection works. No OMDb URLs were written
into TMDB metadata. The server-side failure reason remains unverified.

### What shipped and why

See **Core 0.8.10 Review poster source and safe film lookup** in
`docs/CHANGELOG.md`. The Core change is on main and ready for Dalton's existing
WordPress.com repository deployment. This theme record is documentation only.
The new plugin does not automatically retry the library; retrieval of existing
missing artwork still needs a deliberate action after deployment.

### Commit ledger

| Repository | Commit | Meaning |
| --- | --- | --- |
| `lunara-plugin-core` | `d55ec660a08796169700d6ffdc30a5148e6c665f` | Reviewed and tested 0.8.10 code; PR #33 head. |
| `lunara-plugin-core` | `d22f18ef86bbe8516f40462d13fa2207171ddcbc` | PR #33 merged September 10, 19:12:25 UTC. |
| `lunara-theme-blocks` | `54d9d1bc7a9aebaf04b8437e50f991db58e3d582` | PR #183 corrected the deployment route; rollback hatch verified against this main. |
| `lunara-theme-blocks` | `codex/review-poster-record-0.8.10` | This audit, incident and deployment handoff record. |

### Gate ledger

- Core: **24/24** regression scripts; **50 PHP**, **5 JS**, **6 CSS** checks.
- Four isolated mutations caught: late IMDb hook, preferred poster source,
  canonical poster write key and redacted provider diagnostic.
- Browser fixture uses real importer markup inside a parent Review form and
  mocked lookup responses. Before fix: click caused 0 lookups / 1 Review save.
  After fix: click and Enter each look up without saving; invalid input clears
  the old candidate; no editor script causes no lookup/save; normal Update
  Review still submits its parent form. No provider/production calls in tests.
- Theme Review Image Studio integration passed. Homepage suite passed:
  40 settings/preview, 149 editor, 26 delivery and 29 browser contracts.
- Core PR CI `34518933444`: lint SUCCESS on the merged head.
- Live Core 0.8.10, successful server retrieval, six filled source URLs and
  public Review/homepage image acceptance **not yet verified**. No cache purge.

### Corrections and unintended production save

At **18:58:46 UTC**, the existing Classic Review dialog's **Look up film**
button submitted Review **102632**, despite lookup being intended as read-only.
Browser form parsing drops the nested form, leaving its submit button owned
by the Review form. This is reproduced locally and fixed in Core 0.8.10.

The save retained the title, published status and September 7 publication
date. It wrote default image modes, queued hydration, and linked draft Film
Dossier **102720**, whose body/excerpt are empty. Hydration finished
`identity_only`; no poster or backdrop was retrieved. No draft-import
confirmation button was clicked. The saved body (5,280 characters), excerpt
(143 characters), title and Debrief fields match available revision **102719**.
That revision was created during the save, so it is not an independent
pre-save body snapshot. Do not claim a complete before/after proof.

The revision retained `_wpcom_is_markdown=1`, which the save cleared. A bounded
MCP update restored only this formatting flag. A fresh inspect confirmed that
the flag was the only changed meta value during that restoration; body,
excerpt, title, publication date and published status were identical. The
linked draft and image-processing metadata are recorded, not silently deleted.
Further production form actions were stopped. No browser backup was restored.

### Logged, not fixed

- The server provider failure behind identity-only hydration needs the new
  redacted status or a supported read-only diagnostic after deployment.
- Native WordPress.com repository destinations and Auto Deploy Off are now
  directly verified, superseding the earlier unverified state in this day's
  deployment-route entry. No Deployer for Git setup is needed for this route.
- Full authenticated Preview/Apply/history acceptance and remaining editor
  migrations, Boost Critical CSS, CDN quality and base stylesheet diet remain.

### Whose move it is next

Dalton deploys **lunara-plugin-core main**, using the existing Core connection
and active destination, then reports completion. Agent verifies Core **0.8.10**,
retries exact-IMDb artwork through a safe supported path, inspects the six
canonical URL values, and checks actual Review cards/homepage art. A failed
provider request must be diagnosed, not replaced by a title-only image guess.
Rebuild and verify theme rollback branch / PR #159 after merging this record.

## 2026-09-10 — Theme 3.2.64 live; WordPress.com deployment route corrected

### Headline

Dalton deployed the merged Theme 3.2.64 through WordPress.com's native GitHub
deployment system. The public homepage and the versioned Journal/Oscars canary
confirm the release is serving. The prior Control Desk / Deployer for Git
instructions were wrong and are corrected in the working agreement, runbook,
operator guide and canary's rollback message.

### Verified live state

| Evidence | Observed result |
| --- | --- |
| Dalton's WordPress.com Production → Deployments screenshot | `lunara-theme-blocks`, branch `main`, commit `959d253`, PR #182, **Deployed**, marked Latest Deployment. |
| Anonymous canonical homepage, 18:47:08 UTC | HTTP 200; build `3.2.64+20260910-184351`. |
| Versioned canary, completed 18:47:38 UTC | `bash tests/tools/lunara-canary-verify.sh 3.2.64`; exit 0 / GO. Three anonymous canonical Journal reads returned the same 3.2.64 build; Journal and Oscars both `LIVE_COHERENT`. |
| Rollback hatch after PR #182 | Remote tree equals `c55bf394594149db2888295c5d51f85f47b2b520`; first parent is deployed main `959d253ae9742263801f189eef7a616dd44ee767`. The previous session also verified its simulated merge. |

The screenshot identifies the deployment mechanism, repository, branch and
commit. It does not display the destination, mode or automatic-deployment
toggle. The standing manual-deployment policy remains; those configuration
values must be checked on the connection before making claims about them.

### What shipped and why

The deployed code is the already reviewed Theme 3.2.64 from PR #181, with the
release record in PR #182. See `docs/CHANGELOG.md`, **Theme 3.2.64 Shared
Presentation Editors and Mobile Cards**, and `docs/PRESENTATION-EDITORS.md`.
This follow-up changes only repository instructions and the verifier's
operator-facing rollback text, all excluded by `.deployignore`; it does not
change the public theme version or production settings.

### Commit ledger

| Repository | Commit | Meaning |
| --- | --- | --- |
| `lunara-theme-blocks` | `e2554ae74a89ea29ef939ecab78dd473d4c8d876` | PR #181 merged the 3.2.64 presentation editors and mobile repairs. |
| `lunara-theme-blocks` | `959d253ae9742263801f189eef7a616dd44ee767` | PR #182 merge; the commit shown deployed in Dalton's native dashboard. |
| `lunara-theme-blocks` | This record's topic branch, `codex/deployment-route-and-live-record-3.2.64` | Correct deployment guidance and record production verification. |
| `lunara-theme-blocks` | `claude/rollback-exact-theme-3.2.43` / PR #159 | Rebuild and verify against current main after any merge of this record. |

### Gate ledger

- The actual 3.2.64 public canary passed as recorded above, without cache
  clearing or agent-triggered deployment.
- Code-release contracts, syntax checks, mutation coverage and independent
  review are recorded in the preceding candidate and merged entries; no new
  application code is introduced here. The existing release-identity gate,
  shell syntax and whitespace checks passed for this instruction correction.
  Normal GitHub CI is required before merging.
- Authenticated production editor Preview/Apply/Discard/history acceptance
  remains outstanding. Canary coherence does not prove that editing workflow.

### Corrections

The installed Deployer for Git Pro plugin was incorrectly treated as evidence
of the active theme deployment mechanism. Dalton's direct explanation and
screenshot establish the native WordPress.com connection. Control Desk's
`lunara_control_desk_get_deploy_truth_cards()` reads theme version, timestamps
and file fingerprints; its status panel does not perform deployment.

Affected historical entries retain their original text and now carry a
correction pointer to this entry. Current instructions name WordPress.com and
avoid claiming that the auto-deploy toggle or destination was verified.
The official WordPress.com deployment guide confirms `.deployignore` support:
https://wordpress.com/support/github-deployments/.

### Logged, not fixed

Deployer for Git Pro remains installed and active; its configuration/use was
not established and nothing was enabled, disabled or removed. The native
repository destination, deployment mode and automation setting were not shown
in the supplied screenshot. The homepage Journal still uses the saved legacy
selection, including its older lead; deploying code does not Apply a new
carousel selection. The fresh Automatic lineup remains an explicit editor step.

### Punch-list carried forward and whose move is next

- Agent: finish the repository-only record, CI and rollback-hatch maintenance.
- Editor acceptance: exercise the shared controls in signed-in Site Studio and
  check the homepage at phone widths. Apply Automatic to Journal Carousel or
  curate a Manual lineup when Dalton is ready to change its public selection.
- Continue Homepage Oscar Picks/Facts, reusable media, Review authoring,
  Journal Desk and Academy authoring through the common editor workflow.
- Boost Critical CSS, global CDN quality verification, stylesheet reduction,
  dead guarded renderers and fresh editorial coverage remain separate work.

## 2026-09-10 — Theme 3.2.64 merged; manual deployment next

> Deployment-route correction (2026-09-10): theme deployment references in this
> entry misidentified the tool or menu. Dalton uses WordPress.com native
> Deployments / Settings → Repositories. See **Theme 3.2.64 live; WordPress.com
> deployment route corrected** above. Original observations and gate results
> remain as recorded; plugin activation alone does not identify a deploy path.

### Headline

Theme 3.2.64 is merged to main through [PR #181](https://github.com/TheAntagonist2020/lunara-theme-blocks/pull/181), including the shared
presentation editors and public mobile Journal/Oscars repairs. Local checks,
independent review and GitHub CI passed. This entry records repository delivery;
the measured public state below determines what is live.

### Verified live state

| Probe | Observed result |
| --- | --- |
| Canonical public homepage, 18:30:21 UTC | HTTP 200; build `3.2.63+20260909-015214`. |
| Versioned canary | Expected 3.2.63; exit 0 / GO, Journal and Oscars `LIVE_COHERENT`, three consistent anonymous reads. |

No agent deployment, cache operation, or production settings/article write
occurred. Authenticated WordPress editing acceptance remains outstanding.

### What shipped and why

See `docs/CHANGELOG.md`, **Theme 3.2.64 Shared Presentation Editors and Mobile
Cards**, and `docs/PRESENTATION-EDITORS.md`. The approved work now gives Method
and Oscars Portal the common editing workflow, shared section ordering, and
usable mobile public artwork. Saved selections remain unchanged until Apply.

### Commit ledger

| Repository | Commit | Meaning |
| --- | --- | --- |
| `lunara-theme-blocks` | `e2554ae74a89ea29ef939ecab78dd473d4c8d876` | [PR #181](https://github.com/TheAntagonist2020/lunara-theme-blocks/pull/181) merged Theme 3.2.64 to main. |
| `lunara-theme-blocks` | `40390f2280b360694be92798b3161cd495224660` | Release candidate, guide, identity and integrated validation record. |
| `lunara-theme-blocks` | `e61da2ace4b3fc71c971321a6c17146e3243c33a` | Final-review correction: preserve native Portal validation anchors through the actual REST save response. |
| `lunara-theme-blocks` | `claude/rollback-exact-theme-3.2.43` / PR #159 | Remote tree and simulated merge verified against `c55bf394594149db2888295c5d51f85f47b2b520`; first parent matched release main. Rebuild again after this documentation merge. |

### Gate ledger

- All 94 required local contracts passed after correcting the measured CSS
  budget failure and rerunning the affected budget/mobile gates. Initial run
  and corrected results are preserved in the candidate record.
- 117 PHP, 57 JavaScript and 26 CSS syntax/balance checks passed; the two PHP files in the final REST fix passed syntax again.
- All three task reviews and the whole-branch follow-up review approved. The
  duplicated fixture builder and unused helpers identified in Task 3 were
  removed; Method framing coverage was strengthened with actual renderer checks.
- The initial whole-branch review required the Portal field-error correction.
  Thirteen exact canonical paths were added to the REST allowlist. Real save
  endpoint tests cover identity and presentation errors, unknown/private-field
  exclusion and unchanged settings/revisions after failure. The narrow fix
  passed its covering gates and independent re-review.
- GitHub CI: [Lint run 34514173476](https://github.com/TheAntagonist2020/lunara-theme-blocks/actions/runs/34514173476) passed on the exact reviewed head `e61da2ace4b3fc71c971321a6c17146e3243c33a`.
- No live 3.2.64 acceptance is claimed unless the versioned canary row above
  explicitly records it. Merging and a replayed canary are not deployment proof.

### Corrections and remaining work

The earlier candidate entry remains an accurate pre-merge snapshot. Its
remaining PR/merge step is superseded by this entry. No historical live-state
claim was rewritten.

Homepage Oscar Picks/Facts, reusable media, Review authoring, Journal Desk and
Academy authoring remain later shared-editor migrations. Curating the opening
lineups and publishing fresh coverage remain editorial work. Boost Critical
CSS, global CDN quality verification, the wider stylesheet diet and dead
guarded renderer cleanup remain open.

### Whose move is next

- Agent: merge this documentation record and rebuild/verify the exact rollback
  hatch against the resulting main, following the standing runbook.
- Dalton: use **Lunara Control Desk → Deployer for Git** for the manual theme
  deployment. Auto-deploy stays off. Then verify the actual public build and
  `bash tests/tools/lunara-canary-verify.sh 3.2.64` and exercise the authenticated
  editor controls at desktop and phone widths.
- Follow-up: in Journal Carousel, choose Automatic and Apply to adopt the
  latest eligible stories, or curate Manual. Then continue Homepage Oscar
  Picks/Facts and the remaining authoring editors.

## 2026-09-10 — Theme 3.2.64 presentation editors candidate

> Deployment-route correction (2026-09-10): theme deployment references in this
> entry misidentified the tool or menu. Dalton uses WordPress.com native
> Deployments / Settings → Repositories. See **Theme 3.2.64 live; WordPress.com
> deployment route corrected** above. Original observations and gate results
> remain as recorded; plugin activation alone does not identify a deploy path.

### Headline

The local Theme 3.2.64 candidate extends the shared presentation editor to
Homepage/archive ordering, Method selection/artwork, and Oscars Portal. It also
repairs the public homepage Journal/Oscars cards on mobile. The live baseline
was verified as 3.2.63; this candidate record does not claim deployment.

### Verified live state

Read-only probes against canonical production URLs on 2026-09-10 UTC:

| Probe | Observed result |
| --- | --- |
| Homepage `/`, 16:46:29 UTC | HTTP 200; build `3.2.63+20260909-015214`. |
| `lunara-canary-verify.sh 3.2.63` | Exit 0, GO; Journal and Oscars `LIVE_COHERENT`, including three coherent anonymous Journal reads. |
| Phone-width public DOM | Legacy Journal grid is still active; its non-lead cards squeeze copy beside tall images. Homepage Oscar Picks track exceeds its panel width. |

No deployment, cache operation, production write, or
verification of a live 3.2.64 release occurred.
Authenticated WordPress acceptance was not performed.
Local browser fixtures use real theme markup and code with mocked WordPress
services; they are not production editing acceptance.

### What changed and why

See `docs/CHANGELOG.md`, **Theme 3.2.64 Shared Presentation Editors and Mobile
Cards**, for behavior and scope. `docs/PRESENTATION-EDITORS.md` maps the current
controls. The migration reuses the common workflow and canonical stores while
retiring covered competing writers. Mobile repairs apply to the currently
active legacy Journal presentation without silently adopting another lineup.

### Commit ledger

| Repository | Commit | Meaning |
| --- | --- | --- |
| `lunara-theme-blocks` | `40e2f1c6d2a963ef33f6982cbddcccd7a4bf1638` | Main baseline for this candidate. |
| `lunara-theme-blocks` | `1640abe` / `9db9d43` | Shared section ordering and real-pointer regression. |
| `lunara-theme-blocks` | `d1c9cc7` / `b0b7fb1` | Method migration and exact framing persistence. |
| `lunara-theme-blocks` | `3677ddc` / `6fbfee7` / `2b372d6` / `a3791a3` | Mobile card geometry, original portrait sources, control placement, and final-card navigation. |
| `lunara-theme-blocks` | `1871aea` / `d5e888c` / `75b927f` | Portal shared host, mobile ordering layout, and common test fixture. |
| `lunara-theme-blocks` | This candidate's release commit | Real-renderer Method framing coverage, CSS budget cleanup, 3.2.64 identity and release documentation. |

### Gate ledger

| Gate | Result |
| --- | --- |
| Full theme contracts | Initial 93/94; the sole failure was the 60 KB homepage CSS budget. Budget and full mobile gates passed after repair, satisfying all 94 required contracts. Original failure evidence was retained. |
| Repository syntax | 117 PHP, 57 JavaScript and 26 CSS files checked; zero syntax/brace failures. |
| Mobile public rendering | Actual PHP artwork renderer plus 305 layout and 38 navigation checks; phone/tablet/desktop, long headlines, missing art, portrait sources, arrows/dots/keyboard and reduced motion. |
| Shared ordering | Workspace/editorial contracts; real-pointer checks at 1440, 1101, 782 and 390; independent task review approved. |
| Method | 34 PHP, 53 editor browser and 28 real-renderer framing checks; task review approved. |
| Portal | Real-provider/private-preview, shared-host, save/reload/restore and browser gates passed. All 56 ordered-row geometry checks passed across Portal/Homepage/Reviews/Journal at phone and narrow desktop widths. Independent task review approved after fixture cleanup. |
| Mutations | Shared drag, Journal columns, Oscars width/source/portrait-fit, last-card wrapping, Method framing persistence/picker invalidation and actual public focal CSS mutations were caught. Portal token-owner, preview non-persistence and active-renderer candidate mutations were caught. File mutations restored byte-exactly. |
| Visual inspection | Local Journal phone comparison, final Oscar renderer at 390/1440, Method and Portal inspectors at 390/1440, and corrected mobile section-order controls. |
| Whole-branch review | Pending against the complete candidate commit; all three task reviews approved. |
| Not performed | Production settings/article writes, cache operations, deployment, authenticated Apply/reload/restore, or a live 3.2.64 canary. |

### Corrections

The prior session's observation that production was still 3.2.62 remains an
accurate account of that earlier check. This session now verifies 3.2.63 live.
The operator guide's stale standalone-theme claim has been corrected to match
the active `Template: blocksy` header and the standing operating agreement.

### Logged, not fixed

Homepage Oscar Picks/Facts curation, reusable media, Review Studio, Journal Desk
and Academy authoring still need subsequent shared-control migrations. Source
artwork gaps and the opening lineups remain editorial follow-ups. Boost Critical
CSS regeneration, global Image CDN quality verification, the base stylesheet
diet and dead guarded renderer cleanup remain separate work.

Method's browser regressions were partly written after implementation. The
report records that process deviation without claiming complete test-first
sequencing. Its framing-coverage follow-up was addressed with actual renderer
composition checks and a public-CSS mutation.

The initial combined run caught homepage CSS at 61,654 bytes against its
61,440-byte budget. Removing redundant mobile declarations and compacting the
track rule reduced it to 61,407 bytes. The unchanged size limit and the full
305-layout/38-navigation gate passed after the repair. Two static no-art checks
were updated to reflect properties inherited from the shared card rules.

Portal review found a duplicated browser fixture builder and two unused test
helpers. The builder now has one owner; both consumers use it. Eight fixture
variants stayed byte-identical, executable assertions were preserved, and
focused Portal/workspace reruns plus independent re-review passed.

### Punch-list and whose move is next

- Agent: finish integrated checks/review, push the candidate through the
  authorized PR/merge workflow, then rebuild and verify rollback PR #159 against
  current main. No merge happened when this candidate record was prepared.
- Dalton: after the release is merged, perform the manual **Deployer for Git**
  action from Lunara Control Desk. Auto-deploy stays off.
- After deployment: verify the actual public build and run
  `bash tests/tools/lunara-canary-verify.sh 3.2.64`; accept the authenticated
  controls at desktop and phone widths. Only a live exit 0 is GO.
- Follow-up: apply/curate the Journal and Hero lineups, then migrate Homepage
  Oscar Picks/Facts and the remaining authoring editors to the shared controls.


## 2026-09-08 — Theme 3.2.63 merged; manual deployment next

> Deployment-route correction (2026-09-10): theme deployment references in this
> entry misidentified the tool or menu. Dalton uses WordPress.com native
> Deployments / Settings → Repositories. See **Theme 3.2.64 live; WordPress.com
> deployment route corrected** above. Original observations and gate results
> remain as recorded; plugin activation alone does not identify a deploy path.

### Headline

PR #179 merged at 2026-09-09 01:41:37 UTC after Dalton authorized the release
handoff. Main now contains Theme 3.2.63 with the exact reviewed source tree.
The standing rollback PR #159 was rebuilt against that merge and verified.
The public site still reports 3.2.62; merging did not deploy the theme.

### Verified live state

Read-only, canonical URLs without query strings were checked on 2026-09-09 UTC:

| Route | HTTP | Public build | Checked UTC |
| --- | --- | --- | --- |
| `/` | 200 | `3.2.62+20260907-213435` | 01:42:20 |
| `/journal/` | 200 | `3.2.62+20260907-213435` | 01:42:22 |
| `/oscars/` | 200 | `3.2.62+20260907-213435` | 01:42:24 |

These are availability/build probes, not a 3.2.63 live canary or authenticated
editor acceptance. No deployment, production write, or cache operation occurred.

### What shipped and why

The shared Hero/Journal editor candidate is now merged into `main` and ready
for Dalton's manual Deployer for Git release from Lunara Control Desk.
See `docs/CHANGELOG.md`, **Theme 3.2.63 Shared Carousel Editing**, for behavior
and scope. This session adds a release record without changing deployed files.

### Commit ledger

| Repository | Commit | Meaning |
| --- | --- | --- |
| `lunara-theme-blocks` | `9d702f93c0c8a332a9986c3859055e8765a831fe` | Reviewed PR #179 head; GitHub lint passed before merge. |
| `lunara-theme-blocks` | `09705df1b9152847a3c4154f14ab4f30ed2716aa` | PR #179 merge; tree equals the reviewed head. |
| `lunara-theme-blocks` | PR #159 / `claude/rollback-exact-theme-3.2.43` | Rebuilt after PR #179 with current main as first parent and previous hatch as second parent; normal fast-forward push. |

### Gate ledger

| Gate | Result |
| --- | --- |
| Published PR head | GitHub lint run `34289477407` passed on `9d702f9`. |
| Merge integrity | Main and reviewed head both have tree `c6ea74c2a52a684a8f19c9153e60ceb98d69cba4`. |
| Rollback after PR #179 | Remote tree and simulated merge both equal `c55bf394594149db2888295c5d51f85f47b2b520`; first parent is the PR #179 merge; PR #159 is open and mergeable. |
| Release checks reused | Candidate's 92 theme contracts, 40 settings/metadata, 149 editor browser, 26 delivery, 29 public browser checks; 113 PHP, 50 JS and 26 CSS syntax/balance checks; seven mutations and final timing regression. See preceding candidate record. |
| Record-only checks | Release identity contract and `git diff --check` passed before committing this entry. |
| Not run in this merge session | Full local suite and mutations were not repeated for the identical source tree; live 3.2.63 canary and authenticated editor acceptance await manual deployment. |

### Corrections

None. The earlier candidate record remains an accurate account of its own
session and has not been rewritten to claim deployment or merging.

### Logged, not fixed

Remaining authoring/media editors still need the shared-control migration.
Missing source artwork and opening-lineup curation remain editorial follow-ups;
Boost Critical CSS, Image CDN quality and the base stylesheet diet remain open.

### Punch-list and whose move is next

- Agent: merge this docs-only record and rebuild/verify PR #159 again, as required
  after every merge. Always resolve the hatch against current main.
- Dalton: use Lunara Control Desk to deploy the theme from `main` with Deployer
  for Git. Auto-deploy remains off.
- After deployment: verify the public build, run
  `bash tests/tools/lunara-canary-verify.sh 3.2.63`, and accept Hero/Journal editing
  in the authenticated Studio. Only a live exit 0 is GO.
- Follow-up: curate the two opening lineups, then migrate the remaining editors
  to the shared controls in the agreed sequence.

## 2026-09-08 — Theme 3.2.63 shared editor candidate

> Deployment-route correction (2026-09-10): theme deployment references in this
> entry misidentified the tool or menu. Dalton uses WordPress.com native
> Deployments / Settings → Repositories. See **Theme 3.2.64 live; WordPress.com
> deployment route corrected** above. Original observations and gate results
> remain as recorded; plugin activation alone does not identify a deploy path.

### Headline

Dalton authorized the first shared editor implementation: Hero and Journal now
use the common Site Studio workflow and reusable image/ordering controls.
Release behavior and scope are recorded in `docs/CHANGELOG.md`. The wider editor
standard remains the migration target; this release does not replace the Review,
Journal Desk or Academy authoring forms.

### Verification and delivery

The candidate passed both component reviews and the final integration review.
Focused browser checks exercised the actual PHP inspector and shipped JavaScript
with mocked REST persistence, Media Library selections and revisions. Desktop/mobile
screenshots use sample content and local base styles. The interactive browser
extension blocked the local review URL; no live admin UI acceptance is claimed.
The temporary local preview server was stopped after verification.

### Verified live state

Read-only production content inspection found a separate artwork gap: review
102632 has no stored poster/backdrop or featured image. Review 102629 has saved
TMDB art with automatic image slots. The shared resolver cannot supply artwork
that has never been saved. No deployment, cache operation, production write, or
verification of a live 3.2.63 release occurred. Dalton remains responsible for
the manual Deployer for Git button after the reviewed candidate reaches main.

### Commit ledger

| Repository | SHA | Meaning |
| --- | --- | --- |
| Theme | `a6ce114` | Shared Site Studio host, source metadata and canonical artwork. |
| Theme | `da16048` | Reusable visual controls and carousel field adapter. |
| Theme | `ccc29fb` | Positive automatic hero-to-card artwork fallback regression. |
| Theme | `f614b06` | Clear stale metadata across restored/discarded candidates. |
| Theme | `68fd9ff` | Theme 3.2.63 identity, guide and initial release record. |
| Theme | `ad92ebb` | Resume interrupted metadata after Preview; deterministic browser regression. |

These commits and this closing record are on `codex/shared-editor-3.2.63`.

### Gate ledger

| Gate | Result |
| --- | --- |
| Full theme contract suite | 92 unique PowerShell scripts passed; zero failures. |
| Final combined carousel gate after review fixes | 40 settings/metadata, 149 shared-editor browser, 26 delivery and 29 public browser checks passed. |
| Mutation checks | Seven deliberate faults caught at their intended assertions; clean scratch restored byte-exact. Working checkout was never mutated. |
| Final Preview/metadata timing regression | Failed with the old code at the held-metadata artwork wait; passed after the read resumes on unfreeze. |
| Syntax and whitespace | 113 PHP, 50 JavaScript and 26 CSS files passed; changed adapter rechecked after the final fix; diff check passed. |
| Code review | Both task reviews and final whole-branch review approved, including focused fixes. |
| Live 3.2.63 acceptance | Not run: requires Dalton's deployment, authenticated editor inspection, actual public homepage probe and versioned canary. |

### Corrections and logged issues

Historical comments in two Oscars contracts now describe the earlier poster-wall
release without incorrectly attributing that CSS budget change to 3.2.63. No
earlier production claim was revised. Missing source artwork on review 102632 is
logged for curation; the editor cannot infer an image that was never stored.

### Punch-list and next move

- Review and gates are complete. Merge the published candidate, then Dalton can
  deploy Theme 3.2.63 with the manual Control Desk button.
- After any main merge, rebuild `claude/rollback-exact-theme-3.2.43` / PR #159
  and verify the exact rollback tree.
- After Dalton deploys, inspect Hero and Journal in Site Studio, verify the public
  homepage and run `bash tests/tools/lunara-canary-verify.sh 3.2.63`.
- Curate missing artwork and the opening lineups as a separate editorial action.
- Reuse the controls in remaining presentation/media editors, then Review Studio,
  Journal Desk and Academy editorial tools. Boost/CSS work remains a later task.

## 2026-09-08 — Uniform editing across Lunara agreed; initial standard recorded

### Headline

Dalton clarified that consistent controls must extend across all Lunara editors,
including content and workflow editors. `docs/EDITOR-STANDARD.md` records that
direction, a source inventory, shared interaction rules, and a proposed delivery
order. This is a docs-only addition; implementation of the shared editor is pending.

### Verification and delivery

The inventory was checked against the theme, Core, Journal Foundation, and Oscars
Ledger local source entry points. No production probes or changes were made in
this continuation; the preceding live verification is recorded in the next entry.
The release-identity contract and diff check cover this documentation update.
The full implementation suite and production canary were not repeated because
no executable code changed. No release or main merge occurred.

### Commit ledger

| Repository | Branch | Meaning |
| --- | --- | --- |
| lunara-theme-blocks | `codex/carousel-live-handoff-20260908` | Editor standard and inventory, following the live carousel handoff. |

### Open work and next move

The next implementation slice is shared controls proven in Hero and Journal,
then reused across Site Studio, media, Review Studio, Journal Desk, and Academy
editorial tools. Existing content ownership and publishing rules remain part of
the design. The Hero artwork gaps and unadopted Journal carousel from the prior
entry remain open. No corrections to that entry are needed.

The agent can use the standard for the next implementation task. This docs branch
remains available for a future requested PR; rebuild the rollback hatch after
any eventual main merge. The standard is not a claim of completed UI work.

## 2026-09-08 — Live 3.2.62 verified; single-story hero replaced with automatic selection

### Headline

PR #178 is merged and Dalton deployed Theme 3.2.62. The public hero still used
the legacy manual deck containing only the "X Leaked Spider-Man" Journal story.
The live Hero Carousel editor was switched to Automatic, privately previewed,
and explicitly applied. Its saved state survives reloading, and the anonymous
homepage now contains six recent reviews instead of that forced one-story deck.

### Verified live state

| Surface | Observed result |
| --- | --- |
| Theme build | `3.2.62+20260907-213435`; three anonymous build reads agreed. |
| Journal and Oscars canonical routes | Canary exit 0, GO; both sentinels reported LIVE_COHERENT. This run preceded the Hero settings Apply. |
| Hero editor | Automatic mode saved, private preview succeeded, Apply acknowledged, and the setting persisted after reload. |
| Public homepage after Apply | Adopted hero markup; six reviews beginning with The Dog Stars. The old "X Leaked Spider-Man" article is absent from the hero. |
| Rollback hatch | PR #159 is open and mergeable, parented on the current main tip; its tree remains `c55bf394594149db2888295c5d51f85f47b2b520`. |

### What changed and why

Only the Hero carousel's saved presentation was applied. Its prior manual list
is retained, as designed; no source article was edited. Journal presentation
was not adopted during this operation. An initial anonymous read still returned
the old hero after the save; subsequent canonical reads and browser inspection
confirmed the new lineup. No cache clear or deployment was triggered by the agent.
Feature details remain in `docs/CHANGELOG.md` and `docs/HOMEPAGE-CAROUSELS.md`.

### Commit ledger

| Repository | Commit or branch | Meaning |
| --- | --- | --- |
| lunara-theme-blocks | `778282e6b783a579c774d2f09fe15a6e958ebf1f` | Carousel implementation from PR #178. |
| lunara-theme-blocks | `fbff3da7702fa57237d5ee6c523a570f1efcce9e` | Verified merged main tip. |
| lunara-theme-blocks | `claude/rollback-exact-theme-3.2.43`, PR #159 | Rebuilt against that main tip; tree and parent checked before push, remote head verified afterward. |
| lunara-theme-blocks | `codex/carousel-live-handoff-20260908` | This docs-only operational record, based on current main. |

### Gate ledger and corrections

Production canary passed. Real authenticated Hero preview, Apply and reload
passed; the final anonymous public lineup and browser rendering were checked.
No implementation files changed, so the full local suite was not repeated.
The release-identity contract and diff check are the checks for this docs update.

The first editor snapshot was taken before its controller finished loading and
showed the generic failure placeholder. The agent initially called this a startup
defect, then retracted that diagnosis after the ready state and enabled controls
appeared. No persistent startup defect was established. The loaded mode was
Manual with one story, not Automatic as the uninitialized select had appeared.

### Logged, not fixed

Five of the six current automatic hero slides render the missing-art placeholder;
The Invite has an image. The remaining image sources need investigation or curation.
The initial loading message is misleading, and carousel controls across the site
remain split among several custom implementations. No full-site rebuild or parent
theme change was performed or approved as a concrete implementation scope.

### Punch-list and next move

The user wants consistent visual carousel editing across the site: select stories,
drag thumbnails into order, replace imagery and copy, and reliably see saved changes.
The recommended next scope is one shared carousel editor with direct edit links
and migration of the existing custom carousels, preserving the site's styling.
Start with a demonstrably usable manual Hero and Journal workflow, including image
selection and accurate loading/save feedback; then apply the same controls elsewhere.
The parent Blocksy theme is not implicated in the confirmed one-story selection.

This docs branch is ready for a future requested PR. After any further main merge,
rebuild the rollback hatch again. Editorial publication and broad CSS cleanup remain
separate work.

## 2026-09-07 — Theme 3.2.62 homepage carousels candidate

> Deployment-route correction (2026-09-10): theme deployment references in this
> entry misidentified the tool or menu. Dalton uses WordPress.com native
> Deployments / Settings → Repositories. See **Theme 3.2.64 live; WordPress.com
> deployment route corrected** above. Original observations and gate results
> remain as recorded; plugin activation alone does not identify a deploy path.

### Headline

The approved two-carousel plan is implemented on `codex/home-carousels-3.2.62`.
Hero and Journal have independent automatic/manual selection and presentation
controls in Site Studio. Public adoption is explicit per carousel; reads and
private previews do not change live options. See `docs/CHANGELOG.md` and
`docs/superpowers/plans/2026-09-07-home-carousels.md` for the final behavior.
The editor guide is `docs/HOMEPAGE-CAROUSELS.md`.

### Verified live state

Not applicable: this is a topic-branch candidate.

No deployment, cache operation, production write, or live verification occurred.
Dalton owns the later manual deployment through Deployer for Git from main.

### What shipped and why

Nothing deployed. The candidate replaces stale featured-first carousel selection
with explicit automatic/manual modes, keeps display overrides out of articles,
and replaces the uneven news grid with readable responsive cards. Existing
Hero Command storage and Studio preview/revision APIs are reused.

### Commit ledger

| Repository | Commit | Meaning |
| --- | --- | --- |
| lunara-theme-blocks | `e2900718ac5448cfa19cfb621f34dbb22e2710f7` | Main branch base for this candidate. |
| lunara-theme-blocks | This branch's feature commit | Theme 3.2.62 carousel implementation, acceptance contracts and release record. |

### Gate ledger

- Final complete contract run: **92/92 PowerShell gates passed, zero failures**.
- Carousel runtimes: **105 assertions passed** (29 settings/private preview,
  29 browser editor, 18 public delivery, 29 public browser). Chromium checks use
  the actual PHP markup and bundled Splide; cover desktop/tablet/mobile,
  maximum-length mobile headlines, keyboard/playback, reduced motion and no JS.
- Syntax: **113 PHP files and 49 JavaScript files passed**; CSS brace balance
  and `git diff --check` passed. Latest changed browser harnesses also passed
  their targeted syntax checks.
- **Eight deliberate mutations were caught**: dropping unavailable IDs, accepting
  adopted legacy Hero saves, allowing reviews in Journal, oldest-first automatic
  ordering, ignored headline overrides, empty-manual fallback substitution,
  stale request memoization, and removal of the Studio request busy guard.
  Mutations ran in isolated copies outside the repository; clean sources passed
  afterward.
- Independent specification/code review and the final focused review have **no
  open findings**. The legacy Hero metabox ownership finding was fixed, tested
  and mutation-tested before review closure.
- Earlier complete runs were 90/91 and 91/92: the existing Studio workspace
  test raced short mocked responses during busy-state assertions. Explicit
  request barriers replaced the delays without changing product behavior or
  weakening assertions. Its complete targeted gate passed after that fix.
- Not run: GitHub PR CI, production canary, real WordPress Media Library or live
  publication-change acceptance. Media selection is mocked in browser contracts;
  PHP integration uses WordPress stubs. These local results are not live proof.

### Corrections

No historical release entries were rewritten. The initial full-suite run began
before feature edits and reached Studio afterward; it is not represented as an
immutable baseline. No new claim about the currently deployed version is made.

### Logged, not fixed

The earlier Boost handoff remains on `codex/boost-handoff-20260907`. Broader CSS
diet, dead guarded renderer cleanup and editorial publication are outside this
carousel change. Licensed font files were not copied into the repository.

### Punch-list and next move

The candidate is ready for a PR from `codex/home-carousels-3.2.62` into `main`;
PR creation remains Dalton's next call under `AGENTS.md`. No PR or main merge was
performed in this session, so no rollback hatch rebuild was due. After every
main merge rebuild branch `claude/rollback-exact-theme-3.2.43` (PR #159) and prove
its tree is `c55bf394594149db2888295c5d51f85f47b2b520`. After Dalton deploys, run
`bash tests/tools/lunara-canary-verify.sh 3.2.62` and inspect the actual homepage.
Then preview and Apply each carousel and curate its opening lineup. A production
canary and real WordPress Media Library interaction await deployment.

## 2026-09-07 — Theme 3.2.61 Oscars Portal Studio presentation controls and local candidate close

> Deployment-route correction (2026-09-10): theme deployment references in this
> entry misidentified the tool or menu. Dalton uses WordPress.com native
> Deployments / Settings → Repositories. See **Theme 3.2.64 live; WordPress.com
> deployment route corrected** above. Original observations and gate results
> remain as recorded; plugin activation alone does not identify a deploy path.

### Headline

The Oscars Portal Studio reaches presentation parity with the Reviews and
Journal archive studios — seven settings each, where the portal carried three.
The four new controls govern exactly the surfaces 3.2.57–3.2.59 built: the 2:3
poster wall, the hero, the 3:4 winner portraits, and overall grid density. In
the course of the build, `card_min_height` turned out to be an inert control:
stamped by two emitters, read by nothing, since the day it shipped.

### Verified live state

Not applicable. This candidate is local and unmerged; no production probe,
canary, deployment, cache operation or production write was run, so this entry
makes no new live-version claim. Live remains 3.2.59
(`3.2.59+20260907-173613`), verified GO earlier this session.

### What changed and why

- Four new presentation controls: `winners_min_width` (numeric) plus
  `density`, `lead_prominence` and `board_rhythm` (enums). The enums resolve
  through value maps in the route seed to one custom property each — the
  approach the Oscars ledger route already used — because a ruleset per choice
  would have grown a route sheet that has about 4 KB of headroom under a hard
  56 KB ceiling.
- `card_min_height` now governs the link, spotlight and research card grids.
  It previously emitted a custom property nothing read; the slider validated,
  saved, and changed no pixel. Same defect class as the 3.2.53 winner map.
- `page-oscars.php` and `inc/oscars-portal-critical.php` each carried a copy of
  the property list and provenance gate. Both now call one shared
  `lunara_oscars_portal_variable_declarations()`.

See the top 3.2.60 entry in `docs/CHANGELOG.md` for the complete code-level
release detail.

### Commit ledger

| Repository | SHA | Meaning |
| --- | --- | --- |
| `lunara-theme-blocks` | this commit | Theme 3.2.61: four presentation controls, the inert `card_min_height` made live, one shared declaration emitter, contracts, version sweep, identity contract, changelog, this entry. |

The rollback hatch is named by branch, never by SHA — branch
`claude/rollback-exact-theme-3.2.43`, tracked by PR #159. Verify with
`git rev-parse origin/claude/rollback-exact-theme-3.2.43^{tree}` against
`c55bf394594149db2888295c5d51f85f47b2b520` every time.

### Gate ledger

- **Eight mutations went red and were restored byte-exact:** a property emitted
  but not consumed (board rhythm, and separately winner width),
  `card_min_height` returned to inert, a seed map missing a validated choice, a
  Studio choice the seed cannot map, the template regrowing its own property
  list, the sanitizer accepting an unknown rhythm choice, and hero prominence
  dropped from the emitter.
- One mutation initially escaped — the sanitizer accepting an unknown choice —
  because no test covered it. Coverage was added and the mutation then went
  red. It is recorded as a real gap that existed, not as a clean first pass.
- One assertion written during the build was itself wrong: it required a
  missing rhythm key to be rejected, but validation runs on
  `array_replace_recursive( $defaults, $raw )`, so an absent leaf refills from
  default for every family alike. The test was corrected to the real contract
  rather than the code bent to the test.
- Route sheet unchanged at 53,293 bytes against its 57,344 ceiling; the route
  seed grew 413 bytes. Payload budget contract passes.
- Version sweep 3.2.60 → 3.2.61: 29 escaped and 102 plain occurrences across 47
  files, zero residuals, escaped form replaced first.

### Corrections

The version sweep initially rewrote the 3.2.59 entries in `docs/CHANGELOG.md`
and `docs/SESSION-LOG.md`, retitling a shipped release inside its own
historical record. That is precisely what this log forbids. Both files were
restored and the new entries re-applied above the preserved history. Documents
that record history must be excluded from a mechanical version sweep; only
identity and version-lock sites should move.

### Logged, not fixed

- A blanket version sweep has no notion of which files are records and which
  are locks. It caught the changelog and this log this time and was caught by
  eye, not by a gate. A sweep that skips `docs/` — or a contract asserting no
  historical release heading ever changes — would close it.
- Carried forward: nothing enforces the plugins-before-theme deploy order; the
  canary cannot report its own staleness; empty media anchors on posterless
  winner cards; the five older P2s.

### Punch-list carried forward

| Item | Status |
| --- | --- |
| Merge and deploy Theme 3.2.61 | **Dalton's click.** Theme-only; no plugin ordering constraint this release. |
| Re-run `lunara-canary-verify.sh 3.2.61` after that deploy | Ready |
| Licensed Klim Tiempos fonts exist only in WP uploads | **Unresolved off-site copy.** |

### Whose move it is next

Dalton owns the merge and the later manual deployment through Deployer for Git,
followed by the versioned canary.

No deployment, cache operation, production write, or live verification occurred.

---

## 2026-09-07 — Theme 3.2.60 Oscars hero backdrop and local candidate close

> Deployment-route correction (2026-09-10): theme deployment references in this
> entry misidentified the tool or menu. Dalton uses WordPress.com native
> Deployments / Settings → Repositories. See **Theme 3.2.64 live; WordPress.com
> deployment route corrected** above. Original observations and gate results
> remain as recorded; plugin activation alone does not identify a deploy path.

### Headline

Theme 3.2.59 went live at 17:36 UTC and the canary said GO. The live
page then showed the one thing the offline render could not: the hero
still wore the old near-opaque gradient and had no `has-backdrop` class,
so the drift never fired. 3.2.59 had edited the hero in
`inc/oscars-portal.php`; `/oscars/` renders through `page-oscars.php`.
Theme 3.2.60 moves the gradient and the class into the template that is
live and pins that template in the fluid contract. Assembled on
`claude/journal-voice-optimization-kf6b9o`. Nothing in this slice is
deployed or live.

**Addendum, 17:59 UTC, live state re-verified after Dalton's deploy.**
Theme 3.2.60 went live at 17:56 UTC (`3.2.60+20260907-175607`, Dalton's
Deployer for Git click). Read-only probes: `/oscars/` now emits the hero
section with `has-backdrop` and the 112deg gradient, 28 board art spans,
zero plugin hub duplicates; the canary
`bash tests/tools/lunara-canary-verify.sh 3.2.60` returned GO (three
cache-separated reads agree on the build, Journal and Oscars sentinels
both `LIVE_COHERENT`). Still open: Jetpack Boost's inline critical CSS on
the route is the same 135,541-byte pre-3.2.58 snapshot with nine
`1180px !important` rules; it needs Jetpack Boost → Critical CSS →
regenerate, which is Dalton's wp-admin click. CI on the `main` merge
commit and on this branch both completed green.

**Addendum, 17:55 UTC.** Dalton marked Theme 3.2.60
([PR #175](https://github.com/TheAntagonist2020/lunara-theme-blocks/pull/175))
ready and merged it while its CI `lint` job was still running on the head
(the same suite had passed 91 of 91 locally on that commit). The
exact-rollback hatch was rebuilt on the new `main` as a two-parent commit
(old hatch head, PR #175 merge) and verified tree-exact:
`claude/rollback-exact-theme-3.2.43^{tree}` is
`c55bf394594149db2888295c5d51f85f47b2b520` and the branch contains
`origin/main`. Nothing is deployed by this session. Dalton's clicks:
Deployer for Git for the theme, the canary with `3.2.60`, Jetpack Boost
critical CSS regeneration.

### Verified live state (read-only probes this session)

| Component | Live | On `main` | Gap |
| --- | --- | --- | --- |
| Theme | 3.2.59 (`3.2.59+20260907-173613`), Dalton's Deployer for Git click at 17:36 UTC | 3.2.59 | 3.2.60 is this candidate, not yet merged |
| Academy Awards Database | 2.7.83 | 2.7.83 | none; the hub duplicates are gone from the portal |
| Lunara Dispatch | 3.2.8 | 3.2.8 | none |
| Journal Foundation | 1.3.1 | 1.3.1 | none |
| Jetpack Boost critical CSS on `/oscars/` | still the 135,541-byte pre-3.2.58 snapshot, nine `1180px !important` rules | n/a | regenerate from Jetpack Boost → Critical CSS |

| Check | Result |
| --- | --- |
| `bash tests/tools/lunara-canary-verify.sh 3.2.59` | GO: three cache-separated reads agree on `3.2.59+20260907-173613`; Journal and Oscars sentinels both `LIVE_COHERENT` |
| Live `/oscars/` HTML (unbundled) | 28 `lunara-oscars-board-art` spans, every tile `has-art-photo`; 7 `FRONT RUNNER` labels; rotation cards carry `has-backdrop` (TMDB w780) or `has-poster-backdrop`; 4 door cards `has-backdrop`; 0 hub duplicates |
| Live hero markup | `lunara-oscars-portal-slot-hero` with a TMDB backdrop but no `has-backdrop` class and the 120deg gradient; computed `animation-name: none` |
| Live HTML rendered offline at 1440 with the deployed stylesheets | page 9,976 px, board 1,846 px, winners 714 px, rotation 861 px, no overflow; marquee card 1,278 of 1,284 px with autoplay stripped |
| Live HTML rendered offline at 390 | page 14,601 px, board 3,412 px, two tiles across, winners two across |

No deployment, cache operation, production write, or live verification occurred.
The canary and probes above are read-only observations of Dalton's 3.2.59
deploy, not actions of this session on this candidate. A branch push occurred
to `claude/journal-voice-optimization-kf6b9o` in the theme repository only.

### What shipped and why

See the 2026-09-07 Theme 3.2.60 entry in `docs/CHANGELOG.md`. The
reasoning that matters: the contract that "proved" the drift only proved
the shell had a keyframe; it never proved the live template emitted the
class the keyframe keys off. 3.2.60 pins the template.

### Commit ledger

| Repository | SHA | Meaning |
| --- | --- | --- |
| `lunara-theme-blocks` | this commit | Theme 3.2.60: hero gradient and `has-backdrop` in `page-oscars.php`, template pins in the fluid contract, version sweep, identity contract, changelog, this entry. |

### Gate ledger

- PHP lint on the changed template passed.
- PowerShell contracts: **91 of 91**, each in its own process, the three
  browser contracts on the container's Chromium.
  `release-identity-3-2-59.ps1` became `release-identity-3-2-60.ps1`.
- Mutation on the new pins, restored from a `cp` backup and confirmed
  byte-identical with `cmp`: the template hero class removed went RED.
- **Not run:** `tests/tools/lunara-canary-verify.sh 3.2.60`. Nothing was
  deployed, so there is nothing for it to verify. Dalton retains the later
  manual deployment through Deployer for Git, followed by the canary with
  argument `3.2.60`.

### Corrections

- The 3.2.59 entry's headline and changelog said the hero would drift.
  On the live route it did not, for the reason above. The 3.2.59 code
  change is real but sits in a renderer the route does not use.

### Logged, not fixed

- **Jetpack Boost critical CSS is still stale on `/oscars/`.** It now
  fights 3.2.59 the same way it fought 3.2.58. Jetpack Boost → Critical
  CSS → regenerate, a derived-file regeneration, not a cache clear.
- **`functions.php` carries a dead, guarded copy of the old portal
  renderer** with the 120deg gradient. Left alone per `CLAUDE.md`; a
  future session should delete the dead copies rather than edit them.

### Punch-list carried forward

| Item | Status | Whose call |
| --- | --- | --- |
| Merge 3.2.60; deploy via Deployer for Git; `bash tests/tools/lunara-canary-verify.sh 3.2.60` | open | Dalton |
| Regenerate Jetpack Boost critical CSS | open | Dalton |
| Rebuild the exact-rollback hatch after the merge | open, agent after merge | agent |
| Re-save one Oscar pick to fire the first image warm, or wait for the daily cron | open | Dalton |
| Jetpack Boost Image CDN quality 100 → 82 | open | Dalton |
| Delete the dead guarded renderer copies in `functions.php` | open | agent, a later session |
| Base stylesheet diet | open, carried | Dalton and agent |
| Auto-deploy stays off | unchanged | Dalton |

### Whose move it is next

Dalton's. Merge the 3.2.60 PR, deploy it with Deployer for Git, run the
canary with `3.2.60`, and regenerate Boost's critical CSS.

## 2026-09-07 — Theme 3.2.59 Oscars portal poster wall and local candidate close

> Deployment-route correction (2026-09-10): theme deployment references in this
> entry misidentified the tool or menu. Dalton uses WordPress.com native
> Deployments / Settings → Repositories. See **Theme 3.2.64 live; WordPress.com
> deployment route corrected** above. Original observations and gate results
> remain as recorded; plugin activation alone does not identify a deploy path.

### Headline

Dalton looked at the deployed 3.2.58 portal and said it was still
haphazard: "there's gotta be pictures... Dynamic dynamic dynamic." He was
right. 3.2.58 fixed the scale of the page and not its content: the board
was text tiles with no image data behind them, the winners were tiny
headshots beside text, the rotation was three empty rooms, and the hero
and door backdrops were blank because the plugin's TMDB cache is only
filled by admin importers. Theme 3.2.59 is the poster wall: every pick
tile carries its film poster or the nominee's headshot, the winners are
portraits, the rotation is a one-slide backdrop marquee that autoplays,
the hero drifts, and a daily WP-Cron warm fills the image caches the
render path reads. Assembled on `claude/journal-voice-optimization-kf6b9o`.
Nothing in this slice is deployed or live.

**Addendum, 17:35 UTC.** Dalton marked Theme 3.2.59
([PR #174](https://github.com/TheAntagonist2020/lunara-theme-blocks/pull/174))
ready and merged it. The exact-rollback hatch was rebuilt on the new
`main` as a two-parent commit (old hatch head, PR #174 merge) and verified
tree-exact: `claude/rollback-exact-theme-3.2.43^{tree}` is
`c55bf394594149db2888295c5d51f85f47b2b520` and the branch contains
`origin/main`. Nothing is deployed by this session. The order of Dalton's
clicks is unchanged: Deployer for Git for the theme (now that `main`
carries 3.2.59), the Academy Awards Database to 2.7.83, Jetpack Boost
critical CSS regeneration, then the canary with `3.2.59`.

**Addendum, 17:33 UTC.** Dalton reported "everything has been deployed."
Read-only probes say otherwise, and this is recorded so the next session
does not trust the report over the site: [PR #174](https://github.com/TheAntagonist2020/lunara-theme-blocks/pull/174)
is still an open draft (head 5ea8cca, not merged) and `origin/main` is
still 22cd6bc, so Deployer for Git can only have re-deployed 3.2.58.
`/oscars/` still reports `data-lunara-theme-version="3.2.58"` with and
without Jetpack Boost (`x-ac: BYPASS`, so not an edge cache), carries no
board art spans and no hero backdrop class, the Academy Awards Database
still reports 2.7.82 with both hub duplicates rendering, and Boost's
inline critical CSS is still the 135,541-byte pre-3.2.58 snapshot with
nine `1180px !important` rules. Dispatch 3.2.8 and Foundation 1.3.1 are
the only parts of the earlier click list that are live. The blocker is
the merge: Deployer deploys `main`, and 3.2.59 is not on `main` until
the draft is marked ready and merged.

### Verified live state (read-only probes this session)

| Component | Live | On `main` | Gap |
| --- | --- | --- | --- |
| Theme | 3.2.58 (`3.2.58+20260907-024206`), Dalton's Deployer for Git click at 02:42 UTC | 3.2.58 | 3.2.59 is this candidate, not yet merged |
| Lunara Dispatch | 3.2.8 | 3.2.8 | none; the 2026-09-04 re-update item is closed |
| Journal Foundation | 1.3.1 | 1.3.1 | none |
| Academy Awards Database | 2.7.82 | 2.7.83 | 2.7.83 (landing composer) still not on the site, so the hub duplicates still render inside the portal |
| Jetpack Boost critical CSS on `/oscars/` | 135,532-byte inline snapshot taken before 3.2.58, nine `1180px !important` rules on the portal | n/a | must be regenerated from Jetpack Boost → Critical CSS; a derived-file regeneration, not a cache clear |

| Check | Result |
| --- | --- |
| Offline render of the 3.2.59 candidate at 1440 px | column 1,392 px, page 9,901 px, board 1,846 px (28 poster tiles, six across), winners 714 px, rotation 887 px, no horizontal overflow |
| Offline render at 2560 px | column 1,720 px, page 9,556 px, eight tiles across, 19 text elements under 12 px |
| Offline render at 390 px | page 14,122 px, board 3,443 px (two tiles across), winners 1,374 px (two across), rotation one 4:5 slide per view |
| Offline render at 768 and 1920 px | 13,759 and 9,219 px; four and seven tiles across; no overflow |
| Computed-style probes | found the shell's three-up flex basis on the rotation track, the 180 px `max-height` on winner photos, and the category column collapsing under the status chip; each fixed in the route sheet by specificity, then re-rendered |

No deployment, cache operation, production write, or live verification occurred.
A branch push occurred to `claude/journal-voice-optimization-kf6b9o` in the
theme repository only.

### What shipped and why

See the 2026-09-07 entry in `docs/CHANGELOG.md` for the code-level detail.
The reasoning that matters:

- **Pictures come from what the site already has.** The Academy Awards
  Database stores local `nm…-profile` and `tt…-poster` attachments and
  exposes them through its visual-package methods. The board resolver
  reads those, the pick's own thumbnail, and matched review or movie
  posts. It never calls TMDB on a page view; it is memoised in a
  transient keyed to the picks' modified times.
- **Pinned ids beat guesswork.** A pick can now carry its `tt` and `nm`
  ids. Without them the resolver falls back to the entity URL, a
  person-name index built from the last eight ceremonies, and an
  exact-title post match. Saving a pick schedules a one-shot warm.
- **The warm is the fix for the blank backdrops.** Hero, door and
  rotation images were empty on the live page because only admin
  importers pass `$allow_remote`. A daily cron now fetches the large
  visual package for up to 60 portal titles with remote allowed and
  invalidates the board-art and home-snapshot transients.
- **Specificity, not hope.** Three shell rules outranked the route sheet
  (rotation track flex basis, winner photo `max-height`, category column
  next to the chip). The marquee rules now carry the section class, the
  photo rules lift the cap, and the tile stacks chip, category and call
  in every authority including the critical seed.
- **The route ceiling moved with a reason.** 45,000 to 57,344 bytes in
  both contracts that hold it, because three image-led blocks each need
  layer rules. Recorded in the changelog.

### Commit ledger

| Repository | SHA | Meaning |
| --- | --- | --- |
| `lunara-theme-blocks` | this commit | Theme 3.2.59: poster-wall board with local art resolution, pinned pick ids, portrait winners, backdrop marquee, drifting hero, daily warm, contracts, ceiling, version sweep, identity contract, changelog, this entry. |

### Gate ledger

- PHP lint on every changed file passed; 17 PHP runtime contracts passed.
- `php tests/fixtures/oscars-portal-board-harness.php`: 7 of 7 cases,
  the new `board-art-src-escaped` included.
- PowerShell contracts: **91 of 91**, each in its own process, the three
  browser contracts driven by the container's Chromium 1194 through
  `LUNARA_BROWSER_EXECUTABLE`. `release-identity-3-2-58.ps1` became
  `release-identity-3-2-59.ps1` with 3.2.58 as the prior version and the
  ten 3.2.59 coverage patterns.
- Mutations on the new pins, each restored from a `cp` backup and
  confirmed byte-identical with `cmp`: marquee flex basis removed went
  RED; art `src` emitted without `esc_url` went RED in both the board and
  fluid contracts; the seed row back to side-by-side went RED; the warmer
  without remote allowed went RED. Four for four.
- Budgets: route sheet 53,293 of 57,344 (raised from 45,000 this
  release); shell 185,822 of 204,800; critical seed 5,973 of 6,144;
  rendered vars plus seed under 12,288.
- JS syntax and CSS brace balance clean.
- **Not run:** `tests/tools/lunara-canary-verify.sh 3.2.59`. Nothing was
  deployed, so there is nothing for it to verify. Dalton retains the later
  manual deployment through Deployer for Git, followed by the canary with
  argument `3.2.59`.

### Corrections

- The 2026-09-05 entry's live-state table (addendum of 02:35 UTC) listed
  the theme as 3.2.57 and Dispatch as 3.2.7. Both moved after that
  addendum: Theme 3.2.58 went live at 02:42 UTC and Dispatch 3.2.8 is
  live. The table above supersedes it.

### Logged, not fixed

- **Jetpack Boost critical CSS is stale on `/oscars/`.** It fights 3.2.58
  and will fight 3.2.59 the same way until regenerated. This is a wp-admin
  action (Jetpack Boost → Critical CSS → regenerate), not a cache clear.
- **Academy Awards Database is still 2.7.82 on the site.** The Deployer
  reports zero updates available; the hub duplicates stay until it is
  updated to 2.7.83.
- **Board art on first anonymous view after deploy** will be whatever the
  local attachments already cover. The daily warm (and the one-shot warm
  on pick save) fills the rest; Dalton can force it by re-saving any pick.
- **Jetpack Boost Image CDN quality 100** and the base-stylesheet diet
  remain open from the 2026-09-04 and 2026-09-05 entries.

### Punch-list carried forward

| Item | Status | Whose call |
| --- | --- | --- |
| Update Academy Awards Database to 2.7.83 from Dashboard → Updates | open | Dalton |
| Regenerate Jetpack Boost critical CSS after 3.2.58 (and again after 3.2.59) | open | Dalton |
| Review the 3.2.59 renders and diff; merge; deploy via Deployer for Git; `bash tests/tools/lunara-canary-verify.sh 3.2.59` | open | Dalton |
| Rebuild the exact-rollback hatch after the merge | open, agent after merge | agent |
| Pin `tt` and `nm` ids on picks whose art does not resolve by name | open, as picks are edited | Dalton |
| Jetpack Boost Image CDN quality 100 → 82 | open | Dalton |
| Base stylesheet diet | open, carried | Dalton and agent |
| Auto-deploy stays off | unchanged | Dalton |

### Whose move it is next

Dalton's. Review the poster wall renders, merge the theme PR when
satisfied, update the Academy Awards plugin, regenerate Boost's critical
CSS, deploy the theme with Deployer for Git, and run the canary with
`3.2.59`.

## 2026-09-05 — Theme 3.2.58 Oscars portal rebuild and local candidate close

> Deployment-route correction (2026-09-10): theme deployment references in this
> entry misidentified the tool or menu. Dalton uses WordPress.com native
> Deployments / Settings → Repositories. See **Theme 3.2.64 live; WordPress.com
> deployment route corrected** above. Original observations and gate results
> remain as recorded; plugin activation alone does not identify a deploy path.

### Headline

Dalton called the Oscars portal second-rate and not dynamic. He was right
on both counts, and the offline render showed why: the page stopped scaling
at 1180 pixels, so on his monitor it used the middle 44 percent of the
screen; the prediction board was a 27-row list taking a third of the page;
and the bottom half was the Academy Awards plugin's own hub restating the
portal's spotlights and winners in a second design language. Theme 3.2.58
and Oscars Ledger 2.7.83 are assembled on
`claude/journal-voice-optimization-kf6b9o` in both repositories. Nothing in
this slice is deployed or live.

**Addendum, 15:21 UTC.** Dalton marked Oscars Ledger 2.7.83
([PR #28](https://github.com/TheAntagonist2020/lunara-plugin-oscars-ledger/pull/28))
ready and merged it to that repository's `main`. The theme's `main` did not
move, so the exact-rollback hatch still sits on it (verified: hatch contains
`origin/main`, tree `c55bf394594149db2888295c5d51f85f47b2b520`). The plugin
is on `main`, not yet on the site: Dashboard → Updates is still the next
click, before the theme PR merges and deploys.

**Addendum, 15:24 UTC.** Dalton marked Theme 3.2.58
([PR #173](https://github.com/TheAntagonist2020/lunara-theme-blocks/pull/173))
ready and merged it. Both halves of the release are on `main`. The
exact-rollback hatch was rebuilt on the new `main` as a two-parent commit
(old hatch head, PR #173 merge) and verified tree-exact:
`claude/rollback-exact-theme-3.2.43^{tree}` is
`c55bf394594149db2888295c5d51f85f47b2b520` and the branch contains
`origin/main`. Nothing is deployed. The order of Dalton's clicks is
unchanged: Dashboard → Updates for Oscars Ledger 2.7.83 (and the two
Journal plugins the restore reverted), then Deployer for Git for the theme,
then the canary with `3.2.58`.

**Addendum, 2026-09-07 02:35 UTC, live state re-verified after Dalton
shared a Codex handoff.** The handoff (stored at
`docs/handoffs/2026-09-07-journal-desk-foundation-1.3.x-handoff.md`)
records Journal Foundation 1.3.0 and 1.3.1, a private `/journal-desk/`
app built on the 1.2.14 baseline and merged as Foundation PR #21 and #22
on 2026-09-05. Verified against `origin/main` of the Foundation repo:
both merges are present, the plugin header reads 1.3.1, and the 1.2.14
voice work (compiler sections, schema keys, conditional engagement close)
is intact underneath the desk. Read-only probes of the site:

| Component | Live | On `main` | Gap |
| --- | --- | --- | --- |
| Journal Foundation | 1.3.1 active | 1.3.1 | none; supersedes the 1.2.14 re-update item below |
| Lunara Dispatch | 3.2.7 | 3.2.8 | 3.2.8 (voice fallback, verbosity, punctuation) still not on the site |
| Academy Awards Database | 2.7.82 | 2.7.83 | 2.7.83 (landing composer) still not on the site |
| Theme | 3.2.57 (`3.2.57+20260904-210401`) | 3.2.58 | `/oscars/` seed still carries `width:min(1180px`, no board grid, hub duplicates present |

The plugin listing also reports `updates_available: 0`, so Dashboard →
Updates may show nothing for Dispatch or the Academy Awards plugin until
Deployer for Git re-checks its sources. The `/journal-desk/` route
redirects anonymous requests to login and the 1.3.1-only
`journal/app/media` route answers 403 `lunara_desk_session_required`,
which is the installed-1.3.1 signal the handoff was missing. Nothing was
deployed or changed on the site by this session.

### Verified live state (read-only probes this session)

| Check | Result |
| --- | --- |
| `/oscars/` fetched anonymously, with and without Jetpack Boost (`?jb-disable-modules=all`) | 341 KB HTML, 20 stylesheets in cascade order unbundled, 12 scripts; Boost's inline critical CSS on this route is 132 KB |
| Route CSS bundle on `/oscars/` | 936 KB raw |
| Offline render of the live page at 1440 px | content column 1180 px, headline 63 px, page 11,962 px tall, board 2,326 px, 85 text elements under 12 px |
| Offline render at 2560 px | column still 1180 px, headline 64 px, page 12,164 px |
| Offline render at 390 px | page 17,357 px, board 5,700 px, plugin hub 5,745 px |
| Reveal-on-scroll | sections are `opacity: 0` until an IntersectionObserver fires; a harness that scrolls faster than a reader leaves them invisible, a reader does not. No-JS renders fully visible |
| `quality=100` image URLs on the Boost page | present on spotlight and winner images; absent from the unbundled page, so the parameter is Boost's Image CDN setting, not markup |
| Live theme, plugin versions, deploy state | not probed beyond the above; no live-version claim is made |

No deployment, cache operation, production write, or live verification occurred.
Branch pushes occurred to `claude/journal-voice-optimization-kf6b9o` in the
theme and Oscars Ledger repositories.

### What shipped and why

See the 2026-09-05 entry in `docs/CHANGELOG.md` for the code-level detail.
The reasoning that matters:

- **Three authorities, one number.** The 1180 cap lived in the route sheet,
  the shell, and the inline critical seed, and the seed outranks both by
  selector altitude. Changing one would have left the page clamped at first
  paint. All three moved to 1720 in one commit and the seed was regenerated
  through its own PHP for every render.
- **CSS-led, contract-safe.** The board contract pins the list markup, the
  coherency sentinel pins five section ids, and the route sheet has a
  45,000-byte ceiling. The board became a card grid without touching markup;
  a 3,985-byte duplicate of its rules was removed from the shell; every
  section id survives.
- **The plugin got a hook, not a hack.** The duplicate hub blocks could have
  been hidden with CSS. Instead Oscars Ledger 2.7.83 mirrors its own ceremony
  composer on the landing template, and the theme drops two keys on the
  portal page only. The plugin's default output is byte-identical.
- **Rendered before shipping.** Every change was rendered offline in the
  container's Chromium at five widths from the real page and assets. Dalton
  saw the before sheet; the after is in the changelog numbers.

### Commit ledger

| Repository | SHA | Meaning |
| --- | --- | --- |
| `lunara-plugin-oscars-ledger` | `0d97fe88b5edab3170e3b2a75c6a972bc027b360` | Oscars Ledger 2.7.83: landing section composer, contract, version pins. |
| `lunara-theme-blocks` | this commit | Theme 3.2.58: fluid portal, board grid, hub dedupe hook, poster-first highlights, cap removals, version sweep, identity contract, changelog, this entry. |

### Gate ledger

- **Oscars Ledger:** PHP lint on every file, JS syntax, CSS brace balance,
  and all 30 portable contracts passed (the two local-provenance contracts
  CI skips were skipped here too). New: `tests/landing-section-composer-contract.php`
  passed; mutation (emit without the filter) went RED; restored from a `cp`
  backup and confirmed byte-identical with `cmp`.
- **Theme:** PHP lint on every file passed; 17 PHP runtime contracts passed
  (one prints a JSON harness payload rather than a pass line; its PowerShell
  consumer is the assertion). PowerShell contracts: **91 of 91**, each in
  its own process, the two browser contracts driven by the container's
  Chromium 1194 through `LUNARA_BROWSER_EXECUTABLE`. The count is 91
  because `oscars-portal-fluid-contract.ps1` is new and
  `release-identity-3-2-57.ps1` became `release-identity-3-2-58.ps1`. JS
  syntax and CSS brace balance clean.
- **Mutations on the new contract,** each restored from a `cp` backup and
  confirmed byte-identical with `cmp`: the critical seed back to 1180px went
  RED on two assertions; the theme hook dropping only one duplicate block
  went RED; the poster-first gallery block deleted from the shell went RED.
  Three for three.
- **Budgets:** route sheet 44,120 of 45,000; shell 184,028 of 204,800;
  critical seed 5,544 of 6,144.
- **Not run:** `tests/tools/lunara-canary-verify.sh 3.2.58`. Nothing was
  deployed, so there is nothing for it to verify. Dalton retains the later
  manual deployment through Deployer for Git, followed by the canary with
  argument `3.2.58`.

### Corrections

None to prior entries.

### Logged, not fixed

- **Jetpack Boost Image CDN quality is 100.** Six spotlight and winner
  images weigh 300 to 550 KB each because of it. A wp-admin setting, Boost
  → Image CDN; 82 is the sane value.
- **Boost's critical CSS on this route is 132 KB inline.** It is generated
  from a 936 KB bundle; it shrinks when the base stylesheet does. The
  base-sheet diet from the 2026-09-04 performance findings remains open.
- **Rotating winners carousel** shows tall, mostly empty cards and a blank
  first slot. Pre-existing; not in this slice.
- **The theme's shell carries at least four generations of "compact"
  passes** for the portal that fight each other with `!important`. This
  release removed the caps that were visibly wrong and appended a final
  authority for the gallery; it did not archaeologize the rest.

### Punch-list carried forward

| Item | Status | Whose call |
| --- | --- | --- |
| Review the after-renders and the diff; merge Oscars Ledger 2.7.83 first, then Theme 3.2.58 | done, both merged 15:21 and 15:24 UTC; hatch rebuilt | Dalton |
| Deploy: Oscars Ledger from Dashboard → Updates, then the theme via Deployer for Git from the Control Desk, then `bash tests/tools/lunara-canary-verify.sh 3.2.58` | open | Dalton |
| Re-update Foundation 1.2.14 and Dispatch 3.2.8 from Dashboard → Updates (reverted by the 2026-09-04 restore) | Foundation done via 1.3.1; Dispatch 3.2.8 still open (site on 3.2.7) | Dalton |
| Jetpack Boost Image CDN quality 100 → 82 | logged above | Dalton |
| Base stylesheet diet (print and footer split, then the `!important` archaeology) | open, carried from 2026-09-04 | Dalton and agent |
| Auto-deploy stays off | unchanged | Dalton |

### Whose move it is next

Dalton's. Both PRs are merged. Update the plugin from Dashboard → Updates,
deploy the theme with Deployer for Git, run the canary with `3.2.58`, and
look at the portal on the big monitor.

## 2026-09-04 — The Journal voice was never reaching the model; Foundation 1.2.14 and Dispatch 3.2.8 put it there

> Deployment-route correction (2026-09-10): theme deployment references in this
> entry misidentified the tool or menu. Dalton uses WordPress.com native
> Deployments / Settings → Repositories. See **Theme 3.2.64 live; WordPress.com
> deployment route corrected** above. Original observations and gate results
> remain as recorded; plugin activation alone does not identify a deploy path.

### Headline

Dalton opened the session on the Journal prose: he cannot trust the voice and
has to go line by line through every draft. The cause is structural, not a
tuning problem. The full voice prompt in Dispatch is dead code on the live
stack; the Control Plane compiler in Journal Foundation is what the model
actually receives, and its entire voice instruction was one sentence. Every
draft since config 1.0.25 carries the same prompt hash and the same three-move
shape. Journal Foundation 1.2.14 moves the whole register, worked contrast
pairs included, into the compiler as code-owned defaults that survive every
stored config version. Dispatch 3.2.8 aligns its fallback, stops telling
OpenAI to be terse, and folds curly punctuation to ASCII so the validator's
non-ASCII warning stops firing on every draft. Both are pushed to
`claude/journal-voice-optimization-kf6b9o`. Nothing is merged, deployed, or
live. No theme code changed.

### Verified live state (read-only probes this session)

| Check | Result |
| --- | --- |
| Journal drafts 101898, 101897, 101902, 101886 via the Lunara MCP inspect tool | all four created by Dispatch 3.2.7 / Foundation 1.2.12 (per meta) on config 1.0.25, provider openai, model gpt-5.4-mini, `journal_status` needs_chatgpt_review |
| `_lunara_dispatch_prompt_hash` on all four | identical: `8b1b180cbf58…` |
| Draft shape, read by hand | paragraph one restates the source; paragraph two adds detail and a quote; paragraph three opens "The real story is" / "The takeaway is simple" / "If X … if not …". Zero first person. Zero closing questions. "not just X, it is Y" pivot in two of four |
| Recent journal titles, 15 listed | four use the "X Turns Y Into Z" template |
| Validation warnings | three of four carry "Content contains non-ASCII characters", caused by curly apostrophes and quotes from the model, not by names |
| Live plugin versions on `main` | Foundation 1.2.13, Dispatch 3.2.7. Draft meta reports Foundation 1.2.12 at generation time, so 1.2.13 may not be deployed yet; not probed further |
| Live theme build stamp, canary, deploy state | not probed; no live-version claim is made |

No deployment, cache operation, production write, or live generation occurred.
Branch pushes occurred to `claude/journal-voice-optimization-kf6b9o` in
three repositories.

### What shipped and why

See the 2026-09-04 entry in `docs/CHANGELOG.md` for the code-level detail.
The reasoning that matters for the handoff:

- **Fix the seat, not the note.** The Control Plane's "Current Refinement"
  textarea is the only voice lever Dalton has had, and it is appended as a
  note to a skeleton. Twenty-five config versions did not move the prose
  because the register was never in the prompt to begin with. The voice now
  lives in code, under `editorial.voice`, and the refinement note lands after
  it as steering.
- **The voice is the skill's voice.** Register, principles, structure,
  headline rules, drift catalog, poison phrases, and the engagement close are
  transcribed from the `lunara-journal` skill, with two contrast pairs added
  that rewrite actual sentences from this week's drafts. The skill requires
  an engagement question on every post; the old Dispatch prompt forbade
  forcing one. **Decision, Dalton's, after discussion in this session:** the
  question is conditional for automated entries. The landing sentence is the
  close; a question follows only when the entry has a genuine fork, roughly
  one in three. Reasoning: mandatory questions on daily automated volume read
  as a format within a week, and a mini model with reasoning off produces the
  poll version the prompt forbids. The hand-written skill keeps its rule.
  *Correction within this entry: the first commit of each plugin made the
  question mandatory on every entry; the follow-up commits in the ledger
  below made it conditional before any PR was opened.*
- **Verbosity, not reasoning.** `verbosity: low` was an instruction to be
  terse; `medium` is the fix. Reasoning stays off because it shares the
  2,200-token output cap and would truncate runs.
- **ASCII at the source.** Foundation warns on non-ASCII; the model writes
  curly quotes by habit. Normalizing in the Dispatch builder before the split
  keeps the warning meaningful for accented names.

### Commit ledger

| Repository | SHA | Meaning |
| --- | --- | --- |
| `lunara-plugin-journal-foundation` | `7a77042510d95e6b8fad5f3bbbbaabc9730afcff` | Foundation 1.2.14: full Journal voice in schema and compiler, validator house-tell warnings, two contracts, version pins. |
| `lunara-plugin-dispatch` | `254a7605823053e91be5f51069ffa6a0928fd2d2` | Dispatch 3.2.8: fallback prompt alignment, verbosity medium, ASCII punctuation normalizer, contract, version pins. |
| `lunara-plugin-journal-foundation` | `0b7e9d410783328654a28fcf2dfc5e0539e771e9` | Foundation 1.2.14 follow-up: engagement question conditional. |
| `lunara-plugin-dispatch` | `e054843238733b47841839af14074f6529b02223` | Dispatch 3.2.8 follow-up: engagement question conditional. |
| `lunara-theme-blocks` | this commit and its follow-ups | Changelog and session-log entries, plus one contract change (below). No theme code. |
| `lunara-plugin-journal-foundation` `main` | `45ed547061dfa29ff8d18a32df589cea718d8214` | PR #20 merged by Dalton at 20:11 UTC. Foundation 1.2.14 is on `main`, not yet deployed. |
| `lunara-plugin-dispatch` `main` | `82955845d98a24d3675879fa5a3d1512d743f87e` | PR #13 merged by Dalton at 20:12 UTC. Dispatch 3.2.8 is on `main`, not yet deployed. |

**Ledger addendum, PRs opened at Dalton's request in this session:**
Foundation 1.2.14 is [PR #20](https://github.com/TheAntagonist2020/lunara-plugin-journal-foundation/pull/20);
Dispatch 3.2.8 is [PR #13](https://github.com/TheAntagonist2020/lunara-plugin-dispatch/pull/13).
Merge order: Foundation first. This docs-only branch in the theme repo has
its own PR so the session log reaches `main`; after that merge, rebuild the
exact-rollback hatch per `AGENTS.md`.

**Ledger addendum, after the merges:** all three PRs merged by Dalton on
2026-09-04: Foundation #20 at 20:11 UTC, Dispatch #13 at 20:12 UTC, theme
docs #172 at 20:27 UTC (`main` tip `bbbfea5b7ad9541f7fce5ab318cf675512fed276`).
The exact-rollback hatch `claude/rollback-exact-theme-3.2.43` was then
rebuilt at Dalton's request. It had not been rebuilt after PR #171 (the
carried 3.2.57 punch-list item), so it was two merges stale. The new head
is a two-parent commit: previous hatch tip plus the current `main` tip,
tree `c55bf394594149db2888295c5d51f85f47b2b520`. Two parents rather than the
earlier single-parent shape so the branch advances by fast-forward with no
history rewrite; `main` is an ancestor, so merging the hatch restores exactly
the 3.2.43 tree. Verified on the remote after the push with the `AGENTS.md`
one-liner. Not deployed; the hatch is a branch, not a release.

**Live-state addendum, about 20:30 UTC, read-only plugin list via the
WordPress.com connector:** `LUNARA Journal Foundation` **1.2.14** active at
`lunara-plugin-journal-foundation/`, `Lunara Dispatch Automation` **3.2.8**
active at `lunara-dispatch/`, `Deployer for Git (Pro)` 1.0.12 active. Both
plugin releases from this session are therefore already live, within twenty
minutes of their merges, with no deploy click that Dalton could find on the
Control Desk. The Desk's System tab has no Journal Foundation card and its
Source Control panel reads GitHub `main`, not the live install, so it could
not have shown this. `Lunara Core` is still **0.8.8** live while `main` has
carried 0.8.9 since 2026-08-31, and the 09:31 and 13:31 UTC drafts today were
generated on Foundation 1.2.12 while `main` had carried 1.2.13 since
2026-08-31, so whatever moved these two plugins today is not a blanket
"deploy main on merge." Most likely reading: Deployer for Git's per-project
auto-update is enabled for Foundation and Dispatch and fired on its own
schedule; that contradicts the runbook's "nothing goes live until a human
presses deploy" for those two plugins and is Dalton's to confirm in the
Deployer for Git settings. Logged as a fact and a question, not fixed.
Theme 3.2.57 was also deployed today at 16:04 UTC per the Desk's Deploy Truth
card; not probed further here and no canary was run in this session.

**Correction to the live-state addendum above, about 22:35 UTC, from the
WordPress.com activity log (read-only):** the "both live" reading was true
for 74 minutes and is no longer true. The mechanism is now known and the
"auto-update" guess above is wrong. Timeline, all UTC:

| Time | Event (actor per the activity log) |
| --- | --- |
| 20:15:46 | Dalton updated the Blocksy parent theme to 2.1.56 from wp-admin. |
| 20:15:47 | The WordPress updater, fed by Deployer for Git, updated Dispatch 3.2.7 to 3.2.8 in place (`lunara-dispatch/`) and Foundation 1.2.12 to 1.2.14 in place (`lunara-plugin-journal-foundation/`). So the deploy button for plugins is the ordinary Updates screen: Deployer for Git surfaces GitHub `main` as an available update. |
| 21:11 | Two GutenKit Blocks Pro update attempts failed (download failed). Unrelated. |
| 21:15 to 21:18 | Dalton used Deployer for Git's install action, which created **second copies** of three plugins in repo-named directories: Core 0.8.9 in `lunara-plugin-core/`, Dispatch 3.2.8 in `lunara-plugin-dispatch/`, Oscars Ledger 2.7.82 in `lunara-plugin-oscars-ledger/`. Four further unnamed installs followed. |
| 21:19:00 | Dalton deactivated Lunara Core 0.8.8 (`lunara-core/`) and at 21:19:15 activated the new copy, Core 0.8.9 (`lunara-plugin-core/`). |
| 21:29:30 | Dalton started a Jetpack Backup restore to the 20:15:46 backup point, one second before the plugin updates. |
| 21:42:04 | Restore complete. Foundation 1.2.12, Dispatch 3.2.7, and Core 0.8.8 are active again. The duplicate `lunara-plugin-dispatch/` (3.2.8) and `lunara-plugin-core/` (0.8.9) directories remain on disk, inactive. |
| 21:42:04 | Dispatch draft 101913 generated, stamped Foundation 1.2.12, Dispatch 3.2.7, prompt hash `8b1b180c…`. Old code. |

Consequences: no Dispatch run has executed on 1.2.14 / 3.2.8, so the voice
work is untested on the live site. The 21:42 draft is not evidence either way.
Why Dalton restored is not in the log; the last change before the restore was
the Core 0.8.9 swap into a second directory, which is the likeliest trigger
and is Dalton's to confirm. The Control Desk shows no deploy control for
plugins because none exists: the Updates screen is the control.

### Gate ledger

- **Foundation, run the way `lint.yml` runs it:** PHP lint on every file
  passed; `release-contract.php` 279 assertions; `wp-behavior-contract`,
  `control-plane-sources-runtime`, `site-studio-workflow-runtime`,
  `automation-contract` (52), `automation-source-bridge-runtime`,
  `automation-attention-runtime`, `hub-telemetry-runtime` all passed; JSON
  syntax clean. New: `prompt-compiler-voice-runtime.php` and
  `validator-house-tells-runtime.php` passed and are wired into CI. Run on
  the container's PHP only; CI's 7.4 / 8.2 / 8.3 matrix will confirm. Nothing
  in the change uses syntax newer than 7.4.
- **Dispatch, run the way `lint.yml` runs it:** PHP lint passed;
  `dispatch-stabilization-contract` plus all eight CI runtimes passed; JS
  syntax and CSS brace balance clean. Also run: `openai-cost-guard-runtime`,
  `dispatch-ai-fallback-runtime`, `dispatch-heartbeat-runtime`,
  `source-packet-runtime`, all passed. New: `journal-voice-runtime.php`
  passed and is wired into CI alongside the cost guard.
- **Mutations, each restored from a `cp` backup and confirmed byte-identical
  with `cmp`:** dropping the REGISTER emission went RED in the compiler
  contract; turning house-tell warnings into errors went RED in the validator
  contract; reverting verbosity to `low` went RED in the cost guard; removing
  the normalizer call from the split path went RED; reinstating "Do not force
  a question" went RED. Five for five.
- **Theme CI on the docs PR went red, then green.** `lint` on
  [PR #172](https://github.com/TheAntagonist2020/lunara-theme-blocks/pull/172)
  failed in `tests/release-identity-3-2-57.ps1` on "The 3.2.57 changelog
  entry must be the newest release entry." The contract pinned the 3.2.57
  changelog heading to the absolute top of `docs/CHANGELOG.md`. Every
  previous changelog entry was a theme release, so the pin had never met a
  plugin-only entry; `AGENTS.md` says the changelog covers all seven repos,
  so one was always coming. The 2026-08-31 session removed the same pin from
  the session-log half of this contract for the same reason. Changed the
  changelog assertion to "newest **theme** release entry": no heading above
  3.2.57 may name a theme version, so a future theme release still has to
  regenerate the contract, while plugin-only entries may sit above it. The
  heading-exists-exactly-once and content-coverage assertions are untouched.
  Reproduced the failure locally with PowerShell 7.4.6 installed into the
  agent scratchpad, then green after the edit. Mutations, each restored from
  a `cp` backup and confirmed with `cmp`: a fake "Theme 3.2.58" heading above
  the 3.2.57 entry went RED on the new assertion; renaming the 3.2.57 heading
  went RED on both the exists-once and newest-theme assertions. Only this one
  contract was re-run locally; CI runs the full suite on the push.
- **Not run:** any live generation. The compiled prompt was rendered locally
  and read in full, but no OpenAI call was made and no draft was produced.
  The proof is the next Dispatch run after deploy, read by Dalton.
  `lunara-canary-verify.sh` was not run; no theme release exists to verify.

### Corrections

None to the durable record. The 2026-09-02 entry's "Logged, not fixed" deck
duplication stands and is carried below.

### Logged, not fixed

- **Dispatch quality gate rewards the tics.** `has_originality_signal()` in
  `class-post-builder.php` passes a section on "not just", "the signal",
  "the pattern", "reads like", "the takeaway": several are now on the
  cut-on-sight list. The gate is a skip filter and its list is broad enough
  that clean copy passes on "studio" or "filmmaker", so it does not block
  the new voice, but it should be rebuilt around the new register in a
  separate pass with its own mutation test.
- **Provenance label will lag.** `_lunara_journal_prompt_version` on new
  drafts will still read `journal-1.0.25` after deploy because the config
  version does not change when code-owned defaults change. The
  `_lunara_dispatch_prompt_hash` will change, which is the true signal. If
  Dalton wants the label to move too, saving any Control Plane change will
  mint 1.0.26.
- **Deck equals first paragraph** on every dispatch entry, carried from
  2026-09-02. Now that paragraph one is asked to be a claim rather than a
  summary, the duplicated line will at least be the sharp one.

### Punch-list carried forward

| Item | Status | Whose call |
| --- | --- | --- |
| Review the compiled prompt (Journal → Control Plane, read-only compiled box) | open | Dalton |
| Merge Foundation 1.2.14 to `main` | done, PR #20 merged 20:11 UTC | Dalton |
| Merge Dispatch 3.2.8 to `main` | done, PR #13 merged 20:12 UTC | Dalton |
| Deploy Foundation 1.2.14 and Dispatch 3.2.8 | **reverted** by the 21:29 restore (correction above). Re-run from Dashboard → Updates, those two rows only | Dalton |
| Say what broke between 21:19 and 21:29 that prompted the restore, so the trigger can be isolated from the two plugin releases | open | Dalton |
| Remove the inactive duplicate plugin directories `lunara-plugin-dispatch/` and `lunara-plugin-core/`; update in place from the Updates screen instead of installing second copies | open | Dalton |
| Read the first Dispatch draft generated on 1.2.14 / 3.2.8 against the register; confirm the prompt hash moved off `8b1b180c…` | blocked until the plugins are re-updated; the 21:42 draft ran on old code | Dalton and agent |
| Deploy Theme 3.2.57, then `bash tests/tools/lunara-canary-verify.sh 3.2.57` | 3.2.57 live since 16:04 UTC per Deploy Truth; canary not yet run this session | Dalton |
| Read the first Dispatch draft after deploy against the register; confirm the prompt hash changed | open | Dalton |
| Merge Theme 3.2.57 and rebuild the exact-rollback hatch | done: 3.2.57 merged as PR #171 before this session; hatch rebuilt 2026-09-04 (addendum above) | Dalton |
| Rebuild the Dispatch originality gate around the new register | logged above | Dalton |
| Deck-equals-first-paragraph | logged above, carried | Dalton |
| Auto-deploy stays off | unchanged | Dalton |

### Whose move it is next

Dalton's. Both plugin releases were live for 74 minutes and were reverted by
his 21:29 restore. Re-update Foundation and Dispatch from Dashboard →
Updates, say what prompted the restore, remove the duplicate plugin
directories, then read the compiled prompt in the Control Plane and judge the
next Dispatch draft. The engagement-question decision is made and recorded
above. If it still reads like a
trade desk, the next lever is the model, not the prompt.

## 2026-09-02 — Theme 3.2.57 journal lede parity and local candidate close

> Deployment-route correction (2026-09-10): theme deployment references in this
> entry misidentified the tool or menu. Dalton uses WordPress.com native
> Deployments / Settings → Repositories. See **Theme 3.2.64 live; WordPress.com
> deployment route corrected** above. Original observations and gate results
> remain as recorded; plugin activation alone does not identify a deploy path.

### Headline

Theme 3.2.57 is assembled on `claude/text-difference-investigation-gt06xh`
and pushed for review. It is a one-selector stylesheet change: journal entries
no longer enlarge their first body paragraph, so a dispatch post reads in one
size from the first word to the last. Reviews keep their lede. Dalton raised
this from a draft preview where paragraphs two and three of the Star Trek
Starfleet entry looked like a different typeface from paragraph one. They were
not: same Tiempos Text, 28% smaller. Nothing in this slice is deployed or live.

### Verified live state (read-only probes this session)

| Check | Result |
| --- | --- |
| Draft journal 101876 raw content via the Lunara MCP inspect tool | three bare paragraphs, no markup, no classes; `journal_deck` is the first paragraph verbatim, set by Dispatch ingest |
| Published `/journal/the-new-doomsday-trailer-…/` HTML (anonymous curl) | content wrapper children are plain `<p>` elements; Jetpack Boost concatenated bundles plus inline `lunara-journal-single-guardrail-css` |
| Live 3.2.56 CSS bundles and inline guardrail, traced by hand | paragraph one: `clamp(1.1rem, 0.98rem + 0.4vw, 1.28rem) !important` from the `> p:first-of-type` rule; paragraphs two onward: `clamp(1rem, 1.05vw, 1.12rem) !important` from the guardrail `p` rule |
| Computed sizes at 1280 / 1440 / 1920 px | 20.5 / 20.5 / 20.5 px versus 16.0 / 16.0 / 17.9 px |
| Font family on both paragraphs | identical, inherited from the wrapper; no rule in the cascade sets a family on `p:first-of-type` |
| Live build stamp, canary, deploy state | not probed; no live-version claim is made in this entry |

No deployment, cache operation, production write, or live verification occurred.
A branch push did occur, to `claude/text-difference-investigation-gt06xh` only.

### What shipped and why

The journal selector was removed from the shared review/journal lede rule in
`style.css`. Every journal paragraph now takes the single-journal guardrail
body clamp, which was evidently the intent when both rules landed together in
Theme 3.2.18 and could not happen because `> p:first-of-type` outranks the
guardrail `p` selector on specificity. Reviews keep the enlarged opening
paragraph because a review has a distinct excerpt and a long body; a dispatch
entry's first paragraph is its own deck repeated, so the lift only re-read the
deck at a third size. The one surviving non-important generic lede rule loses
to the `!important` guardrail clamp, so no second edit was needed.

A new contract, `tests/journal-single-lede-parity.ps1`, holds the boundary.
The version moved to 3.2.57 with the usual test-pin sweep and a regenerated
release-identity contract. See the top 3.2.57 entry in `docs/CHANGELOG.md`
for the code-level detail.

### Commit ledger

| Repository | SHA | Meaning |
| --- | --- | --- |
| `lunara-theme-blocks` | `3835ca23ad2dac0ccc947ef58bb5fc681ce53a09` | Theme 3.2.57: journal lede parity, version sweep, identity and lede-parity contracts, changelog. |
| `lunara-theme-blocks` | this commit | Session-log entry. |

### Gate ledger

- **Baseline, CSS edit only, before the version sweep and new tests:** 87/89
  PowerShell contracts and 18/18 PHP runtime contracts passed. The two failures
  were `public-route-stabilization.ps1` and
  `site-studio-private-preview-contract.ps1`, both throwing before any
  assertion because the pinned Playwright runtime was not installed. CI runs
  `npm ci --ignore-scripts` first; doing the same here resolved it.
- **Final run on the complete candidate:** 88/90 PowerShell contracts (the
  count is 90 because `journal-single-lede-parity.ps1` is new and
  `release-identity-3-2-56.ps1` became `release-identity-3-2-57.ps1`), 18/18
  PHP runtime contracts. The same two browser contracts failed again, this time
  on browser-executable resolution inside the agent container, which has no
  `/usr/bin/chromium`. Re-run with `LUNARA_BROWSER_EXECUTABLE` pointed at the
  container's Chromium 1194: both passed. Net 90/90 and 18/18, each contract
  in a fresh process.
- **Mutation:** re-added the `body.single-journal … > p:first-of-type` selector
  to the review lede rule. `journal-single-lede-parity.ps1` went RED on
  "Reviews must keep exactly one enlarged opening-paragraph rule in
  style.css." Restored from a `cp` backup, GREEN, and `style.css` confirmed
  byte-identical to the pre-mutation copy with `cmp`.
- **Release identity:** `release-identity-3-2-57.ps1` passed alone and inside
  the full run: exact `Version: 3.2.57` header, zero plain or regex-escaped
  `3.2.56` in top-level test sources, the dated 3.2.54 Oscars provenance pin
  intact, `.deployignore` locks intact, changelog and session headings present.
- **CI static checks, run locally the way `lint.yml` runs them:** PHP lint on
  every `.php` file passed, `node --check` on every `.js` file passed, CSS
  brace balance passed.
- **Not run:** `tests/tools/lunara-canary-verify.sh 3.2.57`. Nothing was
  deployed, so there is nothing for it to verify. The browser gates ran on the
  container's Chromium rather than a Playwright-downloaded build; the pinned
  `playwright-core` 1.62.1 drove it.

### Corrections

None to the durable record. The changelog and this entry both say the two
competing rules arrived in Theme 3.2.18; that is what `git log -S` reports for
both strings, and it is stated as history, not as intent.

### Logged, not fixed

- **Deck duplication on dispatch posts.** Journal Foundation ingest sets
  `journal_deck` from the excerpt, and the excerpt from the first 260
  characters of the content, so on every automation-created entry the hero
  deck is the first body paragraph verbatim. The 3.2.57 change makes it read
  as one size, not as two; the sentence still appears twice. Whether the deck
  should be a distinct line, or the first paragraph dropped from the body, is
  an editorial and pipeline call for Dalton, in `lunara-plugin-journal-foundation`
  and `lunara-plugin-dispatch`, not the theme.
- **Three redundant wrapper font-family declarations.** `style.css` and
  `lunara-shell.css` each still set Georgia with `!important` on the journal
  content wrapper before the later Tiempos token rule wins. Harmless because
  order settles it, but a future reorder would silently swap the reading face.
  Not touched, to keep this release to one selector.

### Punch-list carried forward

| Item | Status | Whose call |
| --- | --- | --- |
| Merge the branch to `main` and rebuild the exact-rollback hatch | open | Dalton |
| Deploy Theme 3.2.57 via Deployer for Git from the Control Desk, then `bash tests/tools/lunara-canary-verify.sh 3.2.57` | open, no plugin release precedes it | Dalton |
| Deck-equals-first-paragraph on dispatch entries | logged above | Dalton |
| Auto-deploy stays off | unchanged | Dalton |

### Whose move it is next

Dalton's. Review the diff, merge if it reads right, and deploy manually with
Deployer for Git when ready; the canary argument is `3.2.57`. No plugin
repositories changed in this session.

## 2026-08-31 — Canary was reporting a false ROLLBACK; deploy-order gap found and closed

### Headline

**The live canary had been telling an operator to roll back a healthy site
since 2026-08-29.** Theme 3.2.56 moved the canonical route root from
`<main id="primary">` to `<div id="primary">`; both canonical coherency
sentinels scanned only `<main>` tags, found zero roots, and failed closed on
every downstream contract. The release was fine the entire time. Separately,
3.2.56 was deployed on 2026-08-29 **ahead of the plugin releases it depends
on**, which were built but never merged.

### Verified live state (probed this session, read-only)

| Check | Result |
| --- | --- |
| Live build stamp | `3.2.56+20260829-203401` |
| `lunara-canary-verify.sh 3.2.56` — **before** the fix | **exit 1 — ROLLBACK** (false) |
| `lunara-canary-verify.sh 3.2.56` — **after** the fix | **exit 0 — GO** |
| Journal / Oscars sentinels after fix | both `LIVE_COHERENT` |
| `/`, `/journal/`, `/reviews/`, `/oscars/` | HTTP 200, zero PHP error markers |
| `/oscars/` winner cards | 23 rendering |
| Root element on both routes | `<div id="primary">`, version binding `3.2.56`, tiempos marker present, zero legacy roots |

### The canary defect

`document.match(/<main\b[^>]*>/gi)` in both sentinels restricted the root
census to `<main>` elements. The live pages carried every marker the gate
wanted — one `id="primary"` root, the route class, the version binding, the
tiempos marker — on a `<div>`.

**The move was deliberate and the repo already required it.**
`tests/public-route-stabilization.ps1:56` asserts route templates
`-notmatch '<main\s+id="primary"'` — *"must not reopen a nested main
landmark."* The sentinels were pinned to a pattern the theme's own contract
forbids. The fix aligns them with the contract that is actually enforced.

Widened, not lowered: exactly one modern root, the version binding matching the
deployed version, the tiempos marker, and zero legacy roots are all still
required, and a legacy root is still "carries the route class, lacks the modern
id" — the 3.2.48 mixed identity.

### Deploy-order violation (already happened, now closed at the repo level)

Theme 3.2.56 went live 2026-08-29 while `main` carried Core **0.8.8** and
Journal Foundation **1.2.12**. The 3.2.56 slice depends on Core 0.8.9 and
Foundation 1.2.13. Codex had built and pushed both (`bb30860`, `2cf29cc` —
the exact SHAs named in the 2026-08-29 entry's own ledger) but no PR was ever
opened for either, so Dispatch and the theme merged while those two sat on
unmerged branches.

Opened and merged this session: Core **PR #32** → 0.8.9, Journal Foundation
**PR #19** → 1.2.13. All four repos now carry the intended versions on `main`.
**Nothing has been deployed** — the plugin deploys remain Dalton's click.

### Commit ledger

| Repo | SHA | Meaning |
| --- | --- | --- |
| theme | `d936cbc` | `main` tip — merge of PR #169, the canary fix |
| theme | `4dbe6f7` | merge of PR #168, Theme 3.2.56 — **live since 2026-08-29** |
| core | `55edd85` | merge of PR #32 — Core 0.8.9 |
| journal-foundation | `d640719` | merge of PR #19 — Foundation 1.2.13 |
| dispatch | `ce81e1f` | Dispatch 3.2.7 |

The rollback hatch is named by branch, never by SHA — see the 2026-08-24 entry
for why. It was found **stale** at the start of this session (still parented on
`aa0faf8` after PR #168 merged) and was rebuilt; it is rebuilt again after
PR #169. Branch `claude/rollback-exact-theme-3.2.43`, PR #159; verify with
`git rev-parse origin/claude/rollback-exact-theme-3.2.43^{tree}` against
`c55bf394594149db2888295c5d51f85f47b2b520` every time.

### Gate ledger

- **Live canary against 3.2.56: exit 0, GO** after the fix.
- **Mutation testing, 5/5 caught** (each exit 1): version binding stripped;
  wrong version on the root; legacy root injected in the 3.2.48 shape;
  duplicated modern root; tiempos marker removed. Unmutated live capture
  replays at exit 3, as designed.
- PowerShell contracts: **87 passed / 2 failed** — and the identical 87/2 on a
  sweep of pristine `main` with the patch removed. Not claimed as green.
- PHP lint 108/108. `node --check` clean on both gates.
- **Not run:** the two failing contracts cannot execute in this sandbox —
  `site-studio-private-preview-contract.ps1` and `public-route-stabilization.ps1`
  fail under a full sweep with `browserType.launch: spawn /opt/pw-browsers
  EACCES`. Both pass individually, neither reads the sentinel sources, and the
  failure reproduces without this change. Environment-limited, not a result.

### Corrections

None to prior entries. The 2026-08-29 entry correctly stated that nothing in
the 3.2.56 slice was integrated or deployed; the deployment and the partial
integration both happened after it was written.

### Logged, not fixed

- **A deployed theme can outrun its plugins with nothing to stop it.** The
  runbook states the plugins-before-theme order, but no gate enforces it. A
  pre-deploy check comparing the theme's required plugin versions against what
  is live would have caught this on 2026-08-29.
- The canary could not distinguish "site broken" from "gate broken" for two
  days. Fail-closed is the right direction, but a gate that cannot self-report
  staleness invites exactly the rollback of a healthy site that nearly happened.
- Carried forward: empty media anchors on posterless winner cards, and the five
  older P2s.

### Punch-list carried forward

| Item | Status |
| --- | --- |
| **Deploy Core 0.8.9, Foundation 1.2.13, Dispatch 3.2.7** via the Control Desk | **Dalton's click.** Foundation before Dispatch. Theme already live. |
| Re-run `lunara-canary-verify.sh 3.2.56` after those deploys | Ready |
| Oscars Portal Studio presentation controls (#16) | Open — Dalton asked for it this session; deliberately sequenced after the deploys rather than started mid-flight |
| Licensed Klim Tiempos fonts exist only in WP uploads | **Unresolved off-site copy.** The one asset a repo backup cannot restore. |

### Whose move it is next

**Dalton's**, on the deploy. Everything at the repo level is done and verified.

---

## 2026-08-29 — Theme 3.2.56 final hardening and local candidate close

> Deployment-route correction (2026-09-10): theme deployment references in this
> entry misidentified the tool or menu. Dalton uses WordPress.com native
> Deployments / Settings → Repositories. See **Theme 3.2.64 live; WordPress.com
> deployment route corrected** above. Original observations and gate results
> remain as recorded; plugin activation alone does not identify a deploy path.

### Headline

Theme 3.2.56 is assembled locally on
`codex/site-studio-editorial-3.2.56`. Reviews Archive, Journal Archive, Review
Single, Utility Search, and Site Footer now use the shared plain-language
Site Studio workspace, backed by their canonical owners. The companion Core,
Journal Foundation, and Dispatch compatibility releases are also committed in
local topic worktrees. Nothing in this slice is integrated, deployed, or live.

### Verified live state

Not applicable. No production probe, canary, deployment, cache operation, or
production write was run, so this entry makes no new live-version claim.

### What changed and why

- Added the five 3.2.56 presentation workspaces, strict candidate schemas,
  canonical adapters, exact-route private previews, stable public markers,
  contextual section focus, archive order/visibility controls, and guided
  handoffs to the tools that retain record or workflow ownership.
- Kept unsaved work in browser memory until explicit Preview Changes or Save
  Live. Ordinary saves remain immediate; removals and restores retain explicit
  confirmation and revision safety.
- Kept Utility Search's 404-only Primary Return Route in Classic controls; the
  fixed `/search/?q=Lunara` preview cannot truthfully represent that separate
  route, while search-result and no-result presentation remain in Site Studio.
- Hardened both archive providers against stale or incomplete candidates,
  mapped their real validation codes to exact inspector controls, and required
  every private-preview transient to pass write and strict readback before a
  token is returned. Known storage failures now give a safe retry instruction
  instead of incorrectly telling the editor to review nonexistent field errors.
- Removed the legacy version-change whole-domain purge, the Header and Hero
  administration purges, and the remaining visible claim that a save purges
  cache. Site Studio and ordinary theme administration trigger no domain purge.
- A new real-browser gate caught that anchored Control Desk handoffs were being
  rejected by the safe admin validator. The validator now permits only
  normalized same-origin anchors inside wp-admin and still rejects external,
  credentialed, control-character, and out-of-admin destinations.

See the top 3.2.56 entry in `docs/CHANGELOG.md` for the complete code-level
release detail and ownership boundaries.

### Commit ledger

| Repository | SHA | Meaning |
| --- | --- | --- |
| `lunara-plugin-core` | `bb30860b2ac680dac33c76e30e3728a9cf85c88b` | Core 0.8.9 Review Studio handoff and redacted Site Studio status. |
| `lunara-plugin-journal-foundation` | `2cf29cc7e72c6790dea939267f9a013b7e14e3fb` | Foundation 1.2.13 labeled source rows, authoritative workflow handoff, and redacted status. |
| `lunara-plugin-dispatch` | `74127e1010a181d15c24ad3fc8347ebb2dc4db4d` | Dispatch 3.2.7 Foundation-aware read-only legacy status and guided automation handoff. |
| `lunara-theme-blocks` | `a4a342e0930863265b4487552e5c2badd6cb9502` | Theme 3.2.56 editorial/utility workspaces, release identity, tests, changelog, and runbook. |

### Gate ledger

- Mutation REDs caught missing raw archive paths, unmapped real provider
  errors, failed/mismatched transient storage, the Utility preview-bridge map
  removal, invalid legacy control state, and all four removed purge behavior or
  instruction groups. Every mutation was restored before the final run.
- Independent final review returned READY with no Critical or Important
  blocker after separate adapter/REST and preview/cache spot checks. Both prior
  P1s, the preview-durability P2, and the Utility wording P3 are resolved.
- Fresh final regression on the committed implementation bytes: 89 discovered
  top-level PowerShell contracts, 89 passed, 0 failed, each in a fresh process;
  elapsed time 255.2 seconds. An earlier pass before the final operational-
  message polish was also 89/89 in 241.0 seconds.
- One intermediate Journal browser run and one independent workspace run hit
  the unchanged iframe-navigation timing race; each passed immediately when
  rerun, and both passed inside the definitive 89/89 run.
- Static/structure: PHP lint 108/108, JavaScript syntax 45/45, PowerShell parse
  90/90, and CSS braces 7,113/7,113 across 23 files. Working and staged diff
  checks passed; no licensed font, archive, credential, or secret-like path is
  part of the candidate.
- Not run by design: integration, push, PR, merge, deployment, canary,
  production/live probe, cache action, or production write.

### Corrections

The 2026-08-28 review-fix entry said the stale Hero Command purge claim had
been removed, but that local change had not actually entered the integrated
tree. This 3.2.56 entry records the slice where the Hero notice and its
administration purge are truly removed.

### Logged, not fixed

- Journal Foundation's option transaction cannot fully serialize two human
  administrators who submit the same workflow at the exact same instant. Its
  strict validation, readback, rollback, and revision safeguards remain in
  place; this low-probability coordination race is carried forward rather than
  hidden.
- Production behavior is unverified because this candidate is intentionally
  local and undeployed.
- The real-browser harness can very rarely observe iframe navigation during an
  assertion. The immediate retries and definitive full run passed; this is
  logged as test-timing noise rather than represented as a product defect.

### Punch-list carried forward

- Start Theme 3.2.57 Oscars, plugin coordination, IMDb title-map migration, and
  System Health only after the 3.2.56 integration boundary is approved.
- Integrate and deploy Core 0.8.9, Journal Foundation 1.2.13, and Dispatch 3.2.7
  before Theme 3.2.56. Foundation must precede Dispatch and the theme consumer.
- Dalton retains the later manual deployment through Deployer for Git, followed
  by the approved versioned canary and route/device smoke tests.

### Whose move it is next

The integration decision is next. Dalton owns every later manual Deployer for
Git production deployment: the three compatibility plugins first, then Theme
3.2.56, followed by the versioned canary and route/device smoke tests. Codex
starts 3.2.57 only after that 3.2.56 boundary is approved.

No deployment, cache operation, production write, live verification, push, merge, or PR occurred.

## 2026-08-29 — Theme 3.2.55 final hardening and local candidate close

> Deployment-route correction (2026-09-10): theme deployment references in this
> entry misidentified the tool or menu. Dalton uses WordPress.com native
> Deployments / Settings → Repositories. See **Theme 3.2.64 live; WordPress.com
> deployment route corrected** above. Original observations and gate results
> remain as recorded; plugin activation alone does not identify a deploy path.

### Headline

Theme 3.2.55 is review-clean and closed as a local release candidate on
`codex/site-studio-registry-3.2.55`. Final review hardening prevents WordPress
admin chrome from shifting private-preview geometry and rejects incomplete
Save/Restore success payloads before the workspace adopts them. The candidate
is not integrated, deployed, or live.

### Verified live state

Not applicable. No production probe, canary, deployment, cache operation, or
production write was run, so this entry makes no new live-version claim.

### What changed and why

- Authorized private previews now run at `template_redirect` priority `-1`
  and call `show_admin_bar( false )` only after the complete owner, route,
  surface, user, token, expiry, dependency, and state checks succeed. This
  beats Core's priority-zero admin-bar initializer without changing normal or
  denied requests.
- REST Save and Restore responses now require complete state, revision/safety
  identifiers, timestamps, and changed-section metadata. The browser applies
  the same exact-envelope boundary before state adoption, live-frame
  navigation, candidate clearing, or Revision History refresh.
- The top-level foundation runner now executes the mutation-envelope case.
  Private-preview denial coverage includes malformed queries, dependency and
  consumer failures, recovered-state rejection, collisions, the full denial
  matrix, and noncanonical subdirectory routes; every denied path proves it
  leaves admin-bar state untouched.

See the top 3.2.55 entry in `docs/CHANGELOG.md` for the complete code-level
release detail and canonical ownership boundaries.

### Commit ledger

| Repository | SHA | Meaning |
| --- | --- | --- |
| `lunara-theme-blocks` | `7c4f553` | Final review hardening for private-preview geometry and strict mutation results. |
| `lunara-theme-blocks` | this local release-close commit | Theme 3.2.55 identity, current test expectations, and durable local-only release records. |

The earlier 3.2.55 dependency commits remain recorded in the immediately
following session entry.

### Gate ledger

- Review RED proved the original priority-zero preview handler ran too late to
  prevent Core's admin-bar bump. The corrected runtime models Core at exact
  priority zero and locks Site Studio at exact priority `-1`.
- Mutation checks caught priority `-1` changing to zero, preview-handler
  removal, path-specific premature admin-bar suppression, REST envelope-guard
  bypass, and malformed browser Save/Restore success payloads.
- Two independent final reviews returned PASS with no Critical or Important
  finding; the final test-only denial-matrix follow-up also returned PASS.
- Post-hardening focused gates passed for foundation, private preview,
  real-Chrome workspace, and the 3.2.55 release identity.
- Fresh full regression: 88 live-discovered top-level PowerShell contracts,
  88 passed, 0 failed, each in a fresh process; elapsed time 209.8 seconds.
- Static/structure: PHP lint 107/107, JavaScript syntax 44/44, PowerShell parse
  89/89, and CSS braces 7,113/7,113 across 23 files.
- `git show --check 7c4f553`, staged/working diff checks, exact commit scope,
  and final release-scope checks passed before the local close.
- Not run by design: integration, push, PR, merge, deployment, canary,
  production/live probe, cache action, or production write.

### Ruling

The preview handler stays at `template_redirect` priority `-1` rather than
moving authorization to an earlier lifecycle hook. That preserves the full
front-page/query/user/dependency authorization context while executing before
Core's priority-zero admin-bar initialization. If this ruling is wrong, the
cost is a preview-only geometry regression contained by the private-preview
module and its Core-order runtime contract; normal public requests remain
untouched.

### Punch-list carried forward

- Keep this 3.2.55 candidate local until the approved integration boundary.
- Continue with Theme 3.2.56 Editorial/Utility surfaces and its plugin-first
  compatibility work.
- Dalton retains every eventual production deployment through Deployer for
  Git, followed by the approved canary and route/device smoke tests.

### Whose move it is next

Codex owns continued local 3.2.56 implementation and verification. Dalton owns
the later manual Deployer for Git deployment boundary after separate
integration approval.

No deployment, cache operation, production write, live verification, push, merge, or PR occurred.

## 2026-08-29 — Theme 3.2.55 local release-candidate closure

> Deployment-route correction (2026-09-10): theme deployment references in this
> entry misidentified the tool or menu. Dalton uses WordPress.com native
> Deployments / Settings → Repositories. See **Theme 3.2.64 live; WordPress.com
> deployment route corrected** above. Original observations and gate results
> remain as recorded; plugin activation alone does not identify a deploy path.

### Headline

Theme 3.2.55 is assembled locally on
`codex/site-studio-registry-3.2.55` through the reviewed Revision History
refresh dependency at `4cfe2b9`. The release identity, mutation campaign,
fresh regression suite, and static/scope gates are complete; the uncommitted
bytes are frozen for independent review. It is not integrated or live.

### Verified live state

Not applicable in this local release-close session. No production probe or
canary was run, so there is no new measured live-state table and no live
version claim.

### What shipped and why

Nothing shipped from this session. The local candidate combines the hardened
registry/adapter/REST foundation, canonical Global/Home/Method pilots, atomic
Homepage mod/content transactions, the dedicated responsive Visual Site Map
workspace, private preview and section bridge, twelve-entry restore safety,
and in-place history refresh. See the top 3.2.55 entry in
`docs/CHANGELOG.md` for code-level detail and ownership boundaries.

### Commit ledger

| Repository | SHA | Meaning |
| --- | --- | --- |
| `lunara-theme-blocks` | `4d72367` | Review-clean registry, adapter, REST, preview, revision, and provider foundation. |
| `lunara-theme-blocks` | `d617dd0` | Review-clean Global/Home/Method pilot transactions. |
| `lunara-theme-blocks` | `d7a4426` | Dedicated Site Studio workspace shell and local state machine. |
| `lunara-theme-blocks` | `f266ea2` | Private preview substitution, public markers, and section bridge. |
| `lunara-theme-blocks` | `4cfe2b9` | Review-clean in-place Revision History refresh and adversarial browser closure. |
| `lunara-theme-blocks` | uncommitted review diff | 3.2.55 identity, current test expectations, and local release records; intentionally not staged or committed. |

### Gate ledger

- RED-first release contract: PASS as a RED; it exited 1 against the untouched
  dependency tree for the intended 3.2.54 stylesheet/current-test identity and
  missing 3.2.55 changelog/session records, not for syntax or harness failure.
- Corrected pre-edit census: 80 current-candidate occurrences across 32 test
  files (52 plain plus 28 regex-escaped), with only the dated 2026-08-17
  Oscars 3.2.54 provenance pin excluded and preserved.
- External backup: 37/37 existing authorized and deliberate-mutation files
  matched SHA-256 under
  `C:\lunara-external-backups\task4-commit4-4cfe2b9-20260829-01`.
- Release mutation campaign: 10/10 intended nonzero REDs, including both
  prior-identity source forms, all three coherency/public fixtures, the dated
  provenance pin, both deploy exclusions, and false live/deployment claims.
  The 38-file post-migration green snapshot matched SHA-256 38/38 after the
  campaign.
- Focused release group: 8/8 passed, covering release identity, foundation,
  general Site Studio, pilot, real-Chrome workspace, private preview,
  responsive assets, and public-route stabilization.
- Fresh full regression: 88 live-discovered top-level PowerShell contracts,
  88 passed, 0 failed, each in a fresh process; elapsed time 197.4 seconds.
- Static/structure: PHP lint 107/107, JavaScript syntax 44/44, PowerShell
  parse 89/89, CSS braces 7,113/7,113 across 23 files, and the workspace ES5
  source gate plus all 18 representative syntax mutations passed.
- Release/scope audit: exact 36-file intended status, 80/80 migrated source
  occurrences, sole dated prior pin, one production added line (the version
  header), zero cache/purge or licensed-font additions, zero package/plugin/
  deploy drift, zero mutation sentinels, empty staged diff, `git diff --check`,
  and dependency `git show --check` all passed.
- Not run by design: canary, production/live probe, deployment, cache action,
  production write, integration, push, merge, or PR.

### Corrections

No count or durable-fact correction was required. The dual-form byte census
matched the corrected release brief exactly; no expected count was forced.

One byte-restoration ruling was required: `apply_patch` logically restored the
two `.deployignore` mutations but normalized the three touched CRLF hunk lines.
The controller authorized one deterministic exception limited to that file.
The exact external source and destination paths plus original SHA-256
`FD6E99BC6784A513FB5975FB398086D9BB46503B9AE3C670C8AFBF25F9C731F0`
were verified before native `Copy-Item` restored the repository-only config;
raw hashes then matched and its Git diff was empty. If that ruling is wrong,
the cost is restoring this one config file again from the same verified copy.

### Logged, not fixed

The candidate remains undeployed and therefore has no live 3.2.55 canary or
public-route verification. That is a release boundary, not evidence of a live
defect.

### Punch-list carried forward

- Freeze the verified uncommitted release-close diff and obtain independent
  review of both that diff and the whole 3.2.55 slice.
- Create the local release-close commit only after review is clean and the
  controller explicitly authorizes that later step.
- Any later integration and deployment remain outside this session.

### Whose move it is next

The independent reviewer/controller owns review of the frozen local diff.
Dalton retains the later manual Deployer for Git deployment boundary after
separate integration approval. No deployment, cache operation, production write, live verification, push, merge, or PR occurred.

## 2026-08-28 (re-review round 3) — Cache negation bound to its action

### Headline

The Theme 3.2.54 visible-guidance guard now distinguishes negation that
directly governs a cache-clearing action from unrelated earlier negation.
Production behavior is unchanged from `e153cda`; no deployment or live
operation occurred.

### What changed

- Each visible clear/flush/purge action is classified independently. Direct
  forms such as `Do not clear caches` and `Caches must never be cleared` remain
  allowed, while `Do not hesitate to clear caches` is correctly affirmative.
- Colons, en dashes, and em dashes are clause boundaries, preventing a distant
  `not` from excusing a later affirmative cache-clearing instruction.
- The implementation remains semantic rather than an exact-string allowlist,
  and the prior contextual/plural helper and overflow-fixture protections are
  unchanged.

### Evidence

- Colon-separated, em-dash-separated, and negative-auxiliary affirmative
  guidance: RED.
- Direct active and passive canonical negatives: GREEN.
- Prior `_x`, `_nx`, semicolon-mixed, `.site *`, universal `*`, and Journal
  22px overflow mutations: RED as required.
- Restored focused contract and affected regression set: GREEN, 7/7.
- PHP 100/100, JavaScript 40/40, PowerShell parse, CSS 22/22, and diff checks:
  PASS.

### Live state

No live check, deploy, cache operation, production write, push, merge, or PR
occurred. The public site remains at the previously measured Theme 3.2.53
state.

## 2026-08-28 (re-review round 2) — Semantic and subtree guards closed

### Headline

The Theme 3.2.54 stabilization tests are hardened locally against the second
review's false-green mutations. Production behavior is unchanged from
`ece3634`; no deployment or live operation occurred.

### What changed

- The visible Control Desk census now includes contextual and plural WordPress
  translation helpers, including both `_n`/`_nx` message branches, without
  scanning non-visible technical literals.
- Cache guidance is evaluated clause by clause. Genuinely negative forms such
  as `Caches must never be cleared as a deployment fix` are allowed, while an
  affirmative clear/flush/purge clause fails even when another clause in the
  same message is negative.
- The box-sizing sentinel now lives inside `.site > #canonical-main`, alongside
  the measured route subtree, so both universal and `.site *` fixture repairs
  are observable. Scoped fixture-owned declarations remain valid.

### Evidence

- Contextual `_x` affirmative guidance: RED.
- Contextual-plural `_nx` affirmative branch: RED.
- Alternate canonical negative guidance: GREEN.
- Mixed negative plus affirmative guidance: RED.
- `.site *` and universal border-box repairs: 20-case RED each.
- Journal production border-box removal: 22px overflow RED at 390 and 430.
- Restored focused contract and affected regression set: GREEN, 7/7.

### Live state

No live check, deploy, cache operation, production write, push, merge, or PR
occurred. The public site remains at the previously measured Theme 3.2.53
state.

## 2026-08-28 (review fix) — Public stabilization contracts hardened

### Headline

Two Task 2 review findings are fixed locally and remain undeployed. The
Control Desk no longer contains affirmative visible cache-clearing guidance,
and the public-route browser fixture no longer supplies universal border-box
geometry before measuring production overflow.

### What changed

- The Object Cache status note now states the canonical rule to never clear
  caches as a deployment fix. The stale Hero Command notice no longer claims
  that the homepage cache was purged.
- The cache guidance gate now scans visible translated strings for affirmative
  clear/flush/purge instructions in verb-first, passive, and noun-style forms,
  while allowing explicit `never`, `no`, `without`, and `not` guidance.
- The browser fixture applies box sizing only to its outer-main and action
  scaffolding. A sentinel must retain the browser-default `content-box`, and
  the outer main is edge-to-edge so route overflow cannot hide inside fixture
  gutters.

**Correction (2026-08-29):** The stale Hero claim above had not actually
entered the integrated tree. See the 2026-08-29 Theme 3.2.56 entry, where the
Hero notice and its administration purge are truly removed.

### TDD and verification

- Initial strengthened RED: the contract found both stale Control Desk
  strings and 20 universal-box-sizing failures across four routes × five
  widths.
- Journal mutation RED: removing the production filter scroller border-box
  declaration produced 22px document overflow at 390 and 430.
- Cache noun mutation RED: `Cache flushes are required after every
  deployment.` was rejected by the inflection-aware guard.
- Final focused contract and affected Control Desk/Journal regressions passed;
  PHP, JavaScript, and PowerShell syntax plus `git diff --check` passed.

### Live state and next step

No live probe, deploy, cache operation, production write, push, merge, or PR
occurred. The public site remains at the previously measured Theme 3.2.53
state; review and integration of this local branch are the next steps.

## 2026-08-28 — Theme 3.2.54 public stabilization prepared locally

### Headline

Theme 3.2.54 is committed on
`codex/site-studio-public-stabilization-3.2.54`, but it is **not deployed**.
Home, Reviews, Journal, and Oscars now keep the header-owned canonical
`<main>` as the document's only main landmark. Their existing `#primary`
route roots remain intact as neutral wrappers, including route classes,
ordering hooks, and version markers.

### What changed

- Replaced viewport-derived mobile route/grid sizing with parent-relative,
  border-box containment and removed route-wide clipping that hid defects.
  Intentional Review rails, Journal filter/sort scrollers, Oscars carousels,
  media crops, and line clamps remain local and intact.
- Consolidated both Oscars winner lanes on a conditional media-link renderer.
  Visual anchors are named from canonical winner context; posterless cards
  emit no empty media anchor and retain their named text destination.
- Removed `rocket_clean_domain()` from Design Tokens and replaced four visible
  cache-flush instructions with the standing no-cache-clearing rule.
- Added the portable four-route browser contract at 390, 430, 768, 782, and
  1440, extended the Oscars runtime branches, and updated current release
  expectations to 3.2.54 without rewriting historical documentation facts.

### Verified live state

Read-only 390px probes returned HTTP 200 and Theme 3.2.53 for all four public
routes, so this session makes no deployment or canary claim. Each probe used
one live A/A pair and was classified `BASELINE_NOISY`: Home TTFB/LCP was
1630/2168 ms then 72.2/708 ms; Reviews 1670.4/2284 then 110.3/860 ms; Journal
1146.5/1904 then 77.3/868 ms; Oscars 1994.7/2840 then 76.9/936 ms. All routes
reported zero broken requests and zero document overflow. Oscars transferred
about 1.50 MB and used text LCP; that payload is logged, not attributed to a
verified media bottleneck or changed from this noisy cohort.

### Commit ledger

- `40fc456` — `Stabilize public routes for Theme 3.2.54`
- Documentation close commit follows this entry.

### Gate ledger

- New contract RED: 18 known 3.2.53 defect groups, including nested route
  mains, masking overflow, unconditional Oscars media anchors, the Design
  Tokens cache call, and stale visible cache-flush guidance.
- Target GREEN: four routes × five responsive baselines passed.
- Full PowerShell suite: 83/83 passed.
- Syntax/balance: PHP 100/100, JavaScript 40/40, CSS 22/22.
- Deliberate landmark, overflow-mask, and posterless-anchor mutations: all
  three failed for their intended assertion and passed after restoration.
- `git diff --check`: passed; only normal LF/CRLF working-copy warnings were
  emitted for existing PowerShell files.

### Logged, not fixed

- Live remains Theme 3.2.53 until a separate authorized deployment.
- The one-pair live A/A performance cohort is too noisy for release comparison
  or bottleneck attribution. Oscars' roughly 1.50 MB route payload deserves a
  larger controlled cohort before optimization work is authorized.

### Next step

Review and integrate the branch through the normal workflow. Deployment,
cache operations, production writes, and live canary proof were intentionally
outside this task.

## 2026-08-24 (later) — Agent handoff made portable; two stale docs defused

> Deployment-route correction (2026-09-10): theme deployment references in this
> entry misidentified the tool or menu. Dalton uses WordPress.com native
> Deployments / Settings → Repositories. See **Theme 3.2.64 live; WordPress.com
> deployment route corrected** above. Original observations and gate results
> remain as recorded; plugin activation alone does not identify a deploy path.

### Headline

The working agreement is now **`AGENTS.md`**, the file Codex reads by
convention, and `CLAUDE.md` is a thin pointer to it. Any agent — Codex, Claude,
or a person — cold-starting on this repo now lands on the same four-step
handoff. In the course of writing it, a live hazard surfaced: the reading list
established earlier today pointed at `ARCHITECTURE.md`, which instructs the
reader to do the one thing the standing rules forbid.

### The hazard, and why it mattered

`ARCHITECTURE.md` is a historical snapshot of the retired
`lunara-film-premium-20260503-living-pulse` theme. It carries a banner saying
so. But two of its TL;DR items are not merely outdated — they are the inverse
of current practice:

| `ARCHITECTURE.md` says | Current standing rule |
| --- | --- |
| §TL;DR 3 — "All deploys are scp from a Windows machine" | Deployment is the push-button *Deployer for Git (Pro)* action in the Lunara Control Desk |
| §TL;DR 4 — **"Always Clear Cache after a deploy"** | **Never clear cache as a fix.** A release needing a flush to look correct is a broken release — the 3.2.48 incident |

A general "this is historical" banner was not enough protection. An agent
skimming for deploy instructions would find a numbered, confident TL;DR and act
on it. The earlier entry today made this worse by listing the file as required
reading without qualification.

Both files now carry a banner naming the specific contradictions, and
`AGENTS.md` has a **"Do not trust these two files"** section immediately after
the cold-start list. `README.md` got its first banner — its Paths section still
describes a Windows working copy and a live theme directory that no longer
apply.

### What shipped and why

- **`AGENTS.md`** (new, 181 lines) — canonical. Cold-start order, the
  stale-doc warning, the mandatory session-log close, the standing rules, the
  engineering discipline, the seven-repo map, the branch/PR convention, and the
  gate commands.
- **`CLAUDE.md`** (rewritten, 124 → 24 lines) — now a pointer, plus a five-line
  irreducible summary so nothing critical is lost if a reader stops there.
  Two full copies would have drifted, and this project has already been bitten
  by exactly that shape of bug: `functions.php` carries `function_exists()`
  copies of functions `inc/` defines first, and the dead copy looks perfectly
  editable. A second working agreement would fail the same way.
- **`.deployignore`** — `AGENTS.md` added; nothing here reaches the live theme.

### Verified live state

Not re-probed. No code shipped and no deploy occurred in this pass, so the
3.2.53 verification in the entry below still stands unchanged. Listing
unmeasured facts in this table is exactly what the format forbids.

### Commit ledger

See the entry below for the release ledger; this pass is docs-only on top of
it. Per the rule established in that entry, **the rollback hatch is named by
branch — `claude/rollback-exact-theme-3.2.43`, tracked by PR #159 — never by
SHA**, and is rebuilt after this merge like any other.

### Gate ledger

- `tests/journal-archive-studio-contract.ps1` — exit 0
- `tests/performance-measurement-gate.ps1` — exit 0

Those are the two contracts that assert on `.deployignore`, the only file in
this change that any gate reads. **Not run:** the remaining 80 PowerShell
contracts, the PHP runtime suite, and the canary — no PHP, JS, CSS, or live
surface is touched by a documentation diff.

### Corrections

The entry below listed `ARCHITECTURE.md` as item 4 of the required reading
without qualification. That was wrong, and this entry supersedes it. The
earlier entry is left as written, per the standing rule.

### Logged, not fixed

- **The six plugin repos carry no agent file.** An agent starting cold in
  `lunara-plugin-oscars-ledger` or any sibling gets no working agreement at
  all. A one-screen `AGENTS.md` in each, pointing at this repo, would close it.
  Not done here — six repos, six PRs, and it is Dalton's call whether that
  churn is worth it now.
- Carried forward unchanged from the entry below: the empty media anchors on
  posterless winner cards, and the five older P2s.

### Punch-list carried forward

Unchanged from the entry below — Oscars Portal Studio presentation controls
(#16, awaiting go-ahead) and the Show Linked Reviews toggle, both Dalton's
call.

### Whose move it is next

**Dalton's.** Nothing is blocked. Optional: say the word and the six plugin
repos each get a pointer `AGENTS.md`.

---

## 2026-08-24 — Session record: 3.2.53 confirmed live; log + workflow established

### Headline

**The Latest Ceremony Winners section on `/oscars/` is rendering in
production for the first time in its existence.** Theme 3.2.53 was deployed
2026-08-19 at 22:48:06 UTC — thirteen minutes after the merge landed — and this
session verified it live.

### Verified live state (probed this session, read-only)

| Check | Result |
| --- | --- |
| Live build stamp | `3.2.53+20260819-224806` |
| `lunara-canary-verify.sh 3.2.53` | **exit 0 — GO** |
| Three cache-separated anonymous reads | all agree: `3.2.53` |
| Journal sentinel | `LIVE_COHERENT` (exit 0) |
| Oscars sentinel | `LIVE_COHERENT` (exit 0) |
| `#oscars-winners` section present | yes — 1 section container |
| Winner cards rendered | **12** in the portal section (22 across the page, the rest in the rotating lane) |
| Navigator `#oscars-winners` link | back automatically, as designed |
| Ceremony shown | **98th Academy Awards** — *One Battle after Another*, Best Picture |

The section is not a stub. It is real ledger data: Best Picture, Director, the
four acting categories, both Writing categories, Visual Effects, Cinematography,
Film Editing, and Original Score, each card linking into the title and category
routes.

Note the ceremony is **98**, not 97. The ledger's max ceremony advanced since the
work was written; the code reads `get_max_ceremony()` rather than a pinned
number, so it followed the data without a change. That is the design working.

### What 3.2.53 actually fixed

`lunara_get_home_oscars_snapshot()` built a `$winner_map` from the ceremony
rollup and then **omitted it from the array it returned.** `page-oscars.php`
read `$snapshot['winner_map']`, got nothing, passed an empty map to the card
builder, which returns early on empty input — so the portal produced zero cards
and the section's render condition was never satisfied. One missing array key
stood between 12,138 rows of ledger data and the section built to display them.

**Why it survived so long:** the homepage rotating showcase rebuilds the map
from the rollup itself rather than reading the snapshot. So the winner cards
visibly worked on `/` while the portal's copy was dead. A shared surface with
two implementations only needs one of them to be right to *look* right.

Full engineering detail is in `docs/CHANGELOG.md` under
*2026-08-19 — Theme 3.2.53 Oscars Latest Ceremony Winners Transport*.

### Commit ledger

| Repo | SHA | Meaning |
| --- | --- | --- |
| theme | `ee1fe58` | `main` tip at the close of this session — merge of PR #165 |
| theme | `191113a` | merge of PR #164 — this log and the workflow change |
| theme | `7ba712a` | the session-log implementation commit |
| theme | `676bbed` | merge of PR #163, Theme 3.2.53 — **the release that is live** |
| theme | `06df5a4` | the 3.2.53 implementation commit |
| oscars-ledger | `2ebc990` | plugin 2.7.82 — unchanged this cycle |

**The rollback hatch is deliberately not given a SHA here.** It is rebuilt onto
a new tip after *every* merge to `main`, docs-only ones included, so any SHA
written down goes stale at the next merge — and a stale rollback SHA is worse
than none, because someone reaching for it in an emergency would merge a commit
parented on the wrong tip and not get the exact tree back.

Reach for it by name, which does not move:

- Branch `claude/rollback-exact-theme-3.2.43`, tracked by **PR #159**.
- Verify before trusting it, every time:
  `git rev-parse origin/claude/rollback-exact-theme-3.2.43^{tree}` must equal
  `c55bf394594149db2888295c5d51f85f47b2b520`.

At the close of this session that branch was `7740af6`, parented on `ee1fe58`,
tree verified exact. Two heads preceded it within this session alone — `f3ad4c2`
and `11dabf0` — which is the whole argument for naming the branch instead of
the commit.

PR #163: `+396 / −83` across 36 files, CI `lint` green, merged.
PR #159: the standing rollback PR. **Open and unfired.** Its `−20,610` diff is a
*loaded* number, not a fired one — it is what rollback *would* remove, not what
was removed. Body was rewritten this cycle to say so up front, because that
number was misread once already.

### Gate ledger (3.2.53, all green before merge)

- 82/82 PowerShell contract suites
- 15/15 PHP runtime tests
- 100 files PHP lint — 0 failures
- 39 JS files syntax-clean
- 18 stylesheets brace-balanced
- Both sentinel offline fixtures exit 0
- Version migration 3.2.52 → 3.2.53 across 32 files, zero residuals
- Mutation testing: 7 deliberate breakages, each confirmed to fail the suite
  before restoring. **One caveat recorded honestly:** mutation M4 (removing the
  `! is_array()` guard alone) is *not* caught, because the `??` in the same
  expression is a redundant second protection. M5 (removing both) is caught with
  a fatal. This was documented in the PR rather than claimed as 7/7 clean.

### Corrections made to the durable record

The 3.2.52 changelog entry originally attributed the empty Winners section to
"a ceremony with nominees recorded but no winners yet." **That was wrong.** The
ceremony always carried winner rows; the data never reached the template. The
correction is written into the 3.2.52 entry itself, pointing at 3.2.53. The
3.2.52 fix still stands on its own terms — an in-page link must not outlive the
section it points at — but it hardened the symptom, not the cause.

### New observation from the live probe (logged, not fixed)

**P2 — empty media anchors on posterless winner cards.** Six of the 22 winner
cards on `/oscars/` emit
`<a class="lunara-ceremony-winner-media-link" href="…"></a>` with no content:
a link with no accessible name, and a layout hole where the poster would be.
Only 8 of 22 cards currently carry a visual. Two possible fixes — suppress the
anchor when there is no visual, or give it a text fallback. Not shipped; not
asked for. Recorded so it is not rediscovered from scratch.

### Punch-list carried forward

| # | Item | Status |
| --- | --- | --- |
| 16 | **Oscars Portal Studio presentation controls.** Journal and Reviews studios each carry ~19 presentation controls at ~2,390 lines; the Oscars Portal Studio has 0 density/rhythm controls and 3 range sliders at 1,319 lines. | **Open — awaiting Dalton's go-ahead.** A parity build, not a fix. |
| — | **Show Linked Reviews** on the Oscars portal. Defaults `false` in three consistent places with a full Customizer registration — a deliberate editorial default, not a bug. | One toggle, Dalton's call. |
| — | Empty media anchors on posterless winner cards (above). | Logged. |
| — | priority-1002 preset-preview emitters lack `no-store`. | Logged P2. |
| — | Reader escaping / `wp_kses_post` hardening for plugin HTML fields. | Logged P2. |
| — | `lunara-shell.css` lacks Boost exclusions. | Logged P2. |
| — | `cache_urls` missing non-`oscars`-slug portal pages. | Logged P2. |
| — | `oscars.css` double-ownership retirement. | Logged P2. |

### Disproved this cycle

- **"The journal hub is neglected."** It is not. `/journal/` is at full parity
  with Reviews — 2,377 lines against Reviews' 2,399, all seven archive slots
  live, 25 cards, retention rail, pagination. A first check appeared to fail
  only because it grepped for section *IDs*; the journal archive uses *classes*
  (`lunara-journal-archive-slot-*`). Re-checked correctly, it is complete.
- **"The Oscars database never got finished."** It shipped. 12,138 rows in
  `lunara-plugin-oscars-ledger/data/oscars.csv`, ceremonies from 1927/28
  forward, plus 11,291 rows of TMDB mappings. 2.5 MB, in the repo, live.

### Also shipped this session — the workflow change itself

Dalton asked for this log, and asked that keeping it become part of the workflow
rather than a one-off. Three files carry that:

- **`docs/SESSION-LOG.md`** (this file) — the record.
- **`CLAUDE.md`** at the repo root — the standing working agreement. Any session
  opening this repo reads it first: what to read before touching anything, the
  required shape of a log entry, the rules that do not expire, and the hard-won
  engineering lessons (mutation testing, `cp` not `git checkout --`, suspect the
  grep before believing an absence, check both consumers of shared data).
- **`docs/GO-LIVE-RUNBOOK.md` §5** — "Record it," now a numbered step in the
  deploy ritual between rollback and standing constraints. A deploy is finished
  when it is written down, not when it is verified. Sections renumbered
  accordingly; the runbook's build-stamp and verifier examples were refreshed
  from 3.2.52 to the live 3.2.53.

All three are excluded from the deploy by `.deployignore`, so none of this
reaches the live theme.

### Whose move it is next

**Dalton's.** Nothing is blocked on this session. 3.2.53 is live and verified;
the rollback hatch is armed and tree-exact. The two open decisions are the
Oscars Portal Studio parity build (#16) and the Show Linked Reviews toggle —
both his call, neither urgent.

---

## Backfill — release ledger prior to this log's existence

Reconstructed from `git log --first-parent` on `main`, not from memory. Detail
for each lives in `docs/CHANGELOG.md`.

| Date | Merge | Release |
| --- | --- | --- |
| 2026-08-19 | `676bbed` | Theme 3.2.53 — Oscars Latest Ceremony Winners transport |
| 2026-08-19 | `9db9898` | Theme 3.2.52 — Oscars navigator link integrity |
| 2026-08-17 | `c0a5da8` | PR #161 |
| 2026-08-17 | `6a4420a` | PR #160 |
| 2026-08-17 | `2ebc990` | Oscars Ledger 2.7.82 — read API + composer hooks |
| 2026-08-16 | `459bb06` | PR #158 — 3.2.49 reissue + journal cache-coherency sentinel |
| 2026-08-16 | `e42e1db` | Rollback theme 3.2.48 → 3.2.43 |
| 2026-08-16 | `b5616cd` | Journal recovery 3.2.48 |
| 2026-08-15 | `edb7029` | Rollback journal recovery 3.2.47 |
| 2026-08-15 | `7236b41` | Rollback theme 3.2.46 → 3.2.43 |
| 2026-08-15 | `b108409` | Journal Archive Studio 3.2.44 |
| 2026-08-15 | `b7e1ecb` | Theme 3.2.43 — mobile Reviews card geometry *(the known-good rollback tree)* |

**The 3.2.48 incident** is the reason auto-deploy is off and the sentinels
exist. An auto-shipped release split the anonymous canonical `/journal/` cache
with nobody watching: anonymous visitors got a *mix* of old and new markup for
the same URL. It looked fine on a logged-in reload and broken to the public.
Every rule in the runbook's §5 traces back to it.
