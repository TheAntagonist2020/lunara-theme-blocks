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

## 2026-09-05 — Theme 3.2.58 Oscars portal rebuild and local candidate close

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
| Re-update Foundation 1.2.14 and Dispatch 3.2.8 from Dashboard → Updates (reverted by the 2026-09-04 restore) | open, carried | Dalton |
| Jetpack Boost Image CDN quality 100 → 82 | logged above | Dalton |
| Base stylesheet diet (print and footer split, then the `!important` archaeology) | open, carried from 2026-09-04 | Dalton and agent |
| Auto-deploy stays off | unchanged | Dalton |

### Whose move it is next

Dalton's. Both PRs are merged. Update the plugin from Dashboard → Updates,
deploy the theme with Deployer for Git, run the canary with `3.2.58`, and
look at the portal on the big monitor.

## 2026-09-04 — The Journal voice was never reaching the model; Foundation 1.2.14 and Dispatch 3.2.8 put it there

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
