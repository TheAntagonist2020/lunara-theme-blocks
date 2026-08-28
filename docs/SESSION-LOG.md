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
