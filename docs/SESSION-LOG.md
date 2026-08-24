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
must append an entry here before it ends.** See `CLAUDE.md` at the repo root
for the required shape and the standing workflow rules.

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
| theme | `191113a` | `main` tip — merge of PR #164, this log and the workflow change |
| theme | `7ba712a` | the session-log implementation commit |
| theme | `676bbed` | merge of PR #163, Theme 3.2.53 — the release that is live |
| theme | `06df5a4` | the 3.2.53 implementation commit |
| theme | `11dabf0` | standing exact-rollback head, rebuilt on `191113a`; tree `c55bf39…` = 3.2.43 bit-for-bit |
| oscars-ledger | `2ebc990` | plugin 2.7.82 — unchanged this cycle |

The rollback head was `f3ad4c2` when this entry was first written, parented on
`676bbed`. It was rebuilt onto the new tip after PR #164 merged, which is the
standing rule: the hatch is always exactly one merge away from restoring the
good tree, so it is rebuilt after *every* merge to `main`, docs-only ones
included.

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
