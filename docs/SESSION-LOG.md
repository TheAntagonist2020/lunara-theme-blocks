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

## 2026-08-29 — Theme 3.2.55 final hardening and local candidate close

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
