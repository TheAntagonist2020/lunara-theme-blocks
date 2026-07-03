# Claude Code Setup — Improvement Candidates (2026-07-03)

Diagnosis-only reflection across past Claude Code sessions on the six Lunara repos
(`lunara-theme-blocks`, `lunara-plugin-oscars-ledger`, `lunara-plugin-core`,
`lunara-plugin-dispatch`, `lunara-plugin-ai-assistant-classic`, `lunara-plugin-imdb-guard`).
No changes have been made; every item below is a candidate, not a commitment.

## Evidence base

Fresh remote containers keep no local transcripts, so the sessions were reconstructed from
what they left behind:

- **Git history of all 6 repos** — 174 commits; 45 carry `Co-Authored-By: Claude` trailers,
  and all 45 point at a **single session ID** (`session_01JACe63QYTiH292M6ZdADLD`) spanning
  **June 28 → July 3**, including 00:30–03:30 runs.
- **All 31 GitHub PRs** (theme 19, oscars 7, dispatch 2, ai-assistant 2, imdb-guard 1, core 0),
  including every bot review comment (gemini-code-assist + Copilot; zero human reviews).
- **Artifacts the sessions wrote about themselves**: the verified current-state dossier and
  premium opportunity map (`lunara-plugin-oscars-ledger/docs/`, 2026-06-28), 16 spec + 14 plan
  files in `docs/staging/`, `LIVE_DEPLOY_CHECKLIST.md`, `ARCHITECTURE.md`, 22 PHP contract
  tests (executed during this analysis), 37 PowerShell theme tests, and `.deployignore` files.

Current setup state: **no repo has a `CLAUDE.md`, a `.claude/` directory, project skills,
hooks, CI workflows, or any lint/build config.** The only standing assets are the custom
`lunara` MCP server (live-site diagnose/inspect tools) and WordPress.com GitHub Deployments
(added June 24–26, replacing manual scp).

---

## Clusters, ranked by expected impact

### 1. No way to see the site — the deploy-then-fix loop  →  **NEW SKILL**

**Pattern.** The single most repeated failure mode is shipping visual work blind and fixing it
after it breaks on the live site:

- The opportunity map marks **14 items `[NEEDS-EYES-ON]`** and states "there is no verified
  rendered view of the site"; the dossier records "a complete inability — this session — to
  confirm any performance/CWV number."
- Deploy-then-fix PRs: theme **#9** (Oscars portal overridden by critical-shell CSS, found on
  live), **#13** (Pairing Desk absent after the 3.1.22 deploy), **#16** (duplicate reveal
  systems reconciled only after the cascade fight), **#18** (blank carousel slides — a bug
  gemini flagged on PR #6 five days earlier, ignored, then rediscovered on live), oscars **#6**
  (tall-section reveal bug already shipped on live ceremony pages).
- PR bodies describe **ad-hoc Playwright harnesses rebuilt per PR** against live-fetched HTML,
  never committed anywhere.

**Candidate.** A project skill (e.g. `/eyes-on`) that codifies the visual pass the sessions
kept improvising: fetch the affected lunarafilm.com pages, screenshot at 390/768/1280 with the
Playwright + Chromium already preinstalled in remote sessions, diff before/after, and run a
Lighthouse pass. The reusable harness lives in the repo instead of being rebuilt each session.
This directly attacks the top recurring blocker and half the bug-fix PRs.

### 2. Zero CI, and the only review signal is about to disappear  →  **NEW AUTOMATION**

**Pattern.**
- No `.github/workflows` in any repo; the only check that has ever run on a PR is the Copilot
  review bot. No branch protection: after July 1, **every PR was self-merged 7–17 seconds
  after opening**, so bot reviews consistently landed 1–8 minutes *post-merge* and produced
  zero in-PR follow-up commits.
- Review feedback that was ignored recurred as real bugs (blank-slides, above).
- **gemini-code-assist is being sunset July 17, 2026** (stamped on every review) — after which
  these repos have literally no review signal.
- Verification is hand-claimed per PR (`php -l` clean) and 27 consecutive PRs each included a
  manual `style.css`/`readme.txt` version bump.

**Candidate.** A minimal GitHub Actions workflow per repo: `php -l` over changed files, the
repo's contract tests (see cluster 8), and a version-bump consistency check (plugin header vs
`readme.txt` vs the version constant). Optionally require the check before merge. This
replaces the vanishing bots with something that actually gates, and it runs the checks the
sessions already claim to run by hand.

### 3. No CLAUDE.md anywhere, while the tribal knowledge is huge and partly wrong  →  **FIX**

**Pattern.** Each session re-learns the estate from scratch — and the docs it finds are traps:

- The dossier proves `ARCHITECTURE.md` is **"INVERTED"**: it calls `inc/` "DEAD CODE — NOT
  loaded" when `inc/` is what's live; it says `header.php` is ~6 KB (actual: 238 KB); it
  documents scp deploys that were replaced by GitHub Deployments on June 24–26.
- Knowledge that must not be rediscovered the hard way is scattered: the WP.com edge cache TTL
  is 1 year (deploys are invisible without a manual cache clear), a mu-plugin guards a Blocksy
  doubled-header bug ("DO NOT REMOVE"), **a previous autonomous agent stripped ~270 KB of code
  unannounced**, API keys must be re-entered in wp-admin after certain deploys, and the two
  README path conventions (`/home/151589083/htdocs/...` vs `/srv/htdocs/...`) disagree.
- The June 28 session had to spend a large part of its budget producing the 225-line verified
  dossier just to establish ground truth.

**Candidate.** A short `CLAUDE.md` per repo distilled from the *verified* dossier (not from
`ARCHITECTURE.md`): what's actually loaded, deploy mechanism + post-deploy rituals, the
guard-rails (snapshot before destructive edits, never touch the mu-plugin), where tests live
and how to run them, and a pointer marking `ARCHITECTURE.md` as historical. Cheap, and it
compounds across every future session.

### 4. One mega-session for everything  →  **FIX** (process), plus codify the handoff

**Pattern.**
- All 45 Claude commits across all six repos over six days share **one session ID** — a single
  ever-resumed session doing security remediation, features, reviews, and deploys, paying
  constant context-compaction tax and entangling unrelated repos.
- The head branch `claude/tmdb-key-rotation-9hsb3b` — named for a task finished June 28 — was
  reused for **15 consecutive theme PRs and across 4 repos** as a serial PR-train.
- Session continuity was improvised through the repo: oscars **PR #1 is a docs-only draft
  whose stated purpose is to hand off context to the next session** (opened when network-egress
  limits forced a fresh session); it is still open.

**Candidate.** Two parts. (a) Process fix, encodable as one paragraph in `CLAUDE.md`: one
session per task, one branch per feature, retire the mega-branch. (b) The handoff instinct was
*right* — codify it as a small `/handoff` skill that writes a dated state-of-play doc to
`docs/` (the dossier format already invented) instead of abusing a draft PR as memory.

### 5. Secrets: committed keys, remediation "not fully closed"  →  **FIX**

**Pattern.**
- Real TMDB API keys were hardcoded in both `imdb-guard` and `oscars-ledger` and removed on
  June 28 (commits `febb779`, `d23a843`) — but **the literals remain reachable in git history**
  (no rewrite, no confirmed rotation; the dossier itself calls the remediation "not fully
  closed"). The fix had to be implemented **three separate times** (two repos + the diverged
  deploy branch).
- Bot reviews flagged secret-hygiene issues repeatedly (password-type fields, masked settings)
  — the write-only-field fixes landed, but the root exposure is unresolved.

**Candidate.** Rotate both keys (out-of-band, as the PR bodies already noted), then decide on
history: either rewrite or accept-and-rotate. Add a secret scan (GitHub secret scanning is
available via the existing MCP toolset, or gitleaks in the cluster-2 CI) so the next hardcoded
key never lands.

### 6. Manual post-deploy rituals keep causing incidents  →  **NEW AUTOMATION**

**Pattern.**
- Deploys only become visible after a **manual edge-cache clear** (1-year TTL); permalink
  flushes are manual (theme **PR #3 "Auto-flush rewrite rules on deploy" was opened June 28 to
  fix post-deploy 404s and is still sitting open**); ai-assistant #2 required a post-deploy
  settings re-save; `LIVE_DEPLOY_CHECKLIST.md` is a 10-step human runbook including raw SQL
  row-count health checks.
- Meanwhile the custom `lunara` MCP server already exposes `isonwp-diagnose` and inspection
  tools — the health-check half of the runbook is scriptable today.

**Candidate.** (a) Land or close the stale auto-flush PR — it was written to solve exactly
this. (b) A `/post-deploy` skill or checklist automation that runs the runbook via the lunara
MCP tools (diagnose, spot-check key pages, confirm version live) and reminds about the cache
clear. Kills the deploy-then-404 class of incident.

### 7. Hardcoded cross-repo version anchor  →  **FIX**

**Pattern.** The theme's Control Desk admin panel hardcodes the *expected* Oscars plugin
version and a pinned commit hash (`'2.7.88' === $aat_version ? 'ready' : 'weak'` in
`inc/control-desk.php`). Keeping that dashboard green consumed **30 tiny commits in two days**
("Expect … release" / "Update … source anchor"), and it silently rots every time the plugin
ships. Related drift: the live site ran a branch **31 versions ahead of main** (v2.7.89 vs
v2.7.58), which forced the duplicate TMDB fix and left two draft PRs stranded.

**Candidate.** Make the Control Desk read the installed plugin's version dynamically (the
constant is available at runtime) and drop the pinned-hash ritual; separately, reconcile the
deployed 2.7.89 line with `main` so there is one line of truth per repo. Removes an entire
category of busywork commits.

### 8. The test suites exist but have rotted  →  **FIX** (feeds cluster 2)

**Pattern.** Running all 22 oscars contract tests headlessly today: **7 pass, 14 fail, 1
fatal.** Almost all failures are version drift — tests hardcode `2.7.58` while the plugin is
at 2.7.65 — because nothing runs them, so nothing forced updates through seven releases. The
fatal needs a DOCX at a Windows `E:\` path. The theme's 37 tests are PowerShell-only, unrunnable
in a Linux session. There is no runner, composer, or phpunit anywhere.

**Candidate.** Mechanical repairs: read the version dynamically from the plugin header instead
of hardcoding it; gate Windows-path-dependent tests behind an env check; add a trivial
`run-tests.sh` so a session (and the cluster-2 CI) can run everything in one command. The
tests themselves are good — the spec→contract discipline that produced them is worth keeping.

### 9. The spec → plan → contract-test discipline was invented, then abandoned  →  **NEW SKILL**

**Pattern.** June 22–26, sessions ran a genuinely strong cadence: a dated spec (contract /
storage / invariants / test / deferred sections) + a plan + a matching contract test per
feature — 16 features shipped that way, all traceable. From July 1 the speed-run era began
(PRs merged in seconds) and the discipline vanished; none of the 15 July theme PRs has a spec,
and two of them were bug-fixes for July features.

**Candidate.** Codify the existing convention as a `/spec-feature` skill (template generator +
"write the contract test first" step) so it survives session boundaries instead of living in
one session's habits. The July regression is measurable evidence the convention worked.

### 10. Stale PR / branch backlog  →  **FIX**, optional light automation

**Pattern.** 5 PRs are open with no activity: theme #1 (ACF bridge draft, June 24), theme #3
(auto-flush, June 28 — see cluster 6), dispatch #1 (its change already shipped inside #2),
oscars #1 (handoff doc) and #3 (duplicate key fix against the diverged branch). One PR was
closed unmerged (ai #1, superseded). These accumulate because nothing surfaces them.

**Candidate.** A one-time triage (merge/close each with a note), and — only if the backlog
recurs — a weekly scheduled routine that lists open PRs older than N days across the six
repos. The triage matters; the automation is optional.

### 11. Content/data pipeline runs through manual web uploads  →  **NOTHING (for now)**

**Pattern.** The ~19,700-line `data/oscars.csv` is maintained by GitHub drag-and-drop ("Add
files via upload," 10k-line diffs); `tech-spec(1).md` is a browser download-duplicate uploaded
via the web editor next to the existing `tech-spec.md`; editorial source text lives as pasted
markdown; DOCX sources live at Windows `E:\` paths outside the repos.

**Verdict.** Real friction, but low frequency and the human is genuinely in the loop on data
curation. Not worth tooling yet — revisit if CSV churn becomes regular. (The duplicate
`tech-spec(1).md` is a 30-second cleanup best bundled into cluster 10's triage.)

### 12. Commit-message hygiene  →  **NOTHING** (absorbed by cluster 3)

**Pattern.** A 759-line production change shipped as "Change greeting from 'Hello' to
'Goodbye'"; GitHub-web default messages ("Add files via upload", "Create tech-spec(1).md");
the theme's root commit carries an unrelated "source anchor" message.

**Verdict.** Annoying for archaeology (this analysis felt it) but self-correcting once work
flows through Claude sessions with cluster 3's `CLAUDE.md` conventions. No dedicated tooling.

---

## Suggested order of attack

| Priority | Cluster | Type | Why first |
|---|---|---|---|
| 1 | 3 — per-repo `CLAUDE.md` | Fix | Cheapest, compounds into every session, defuses the inverted-docs trap |
| 2 | 1 — `/eyes-on` visual verification skill | New skill | Attacks the #1 recurring blocker and ~half of the bug-fix PRs |
| 3 | 2 — minimal CI + version check | New automation | Review signal disappears July 17; checks already exist, just unwired |
| 4 | 5 — secrets closure (rotate + scan) | Fix | Open exposure; smallest remaining step of a fix already 90% done |
| 5 | 6 — post-deploy automation (incl. stale auto-flush PR) | New automation | Kills the deploy-then-404/invisible-deploy incidents |
| 6 | 8 — repair contract tests | Fix | Prerequisite for CI to be meaningful |
| 7 | 4 — session/branch hygiene + `/handoff` skill | Fix + skill | Ends the mega-session tax |
| 8 | 7 — dynamic Control Desk version | Fix | Deletes a whole category of busywork commits |
| 9 | 9 — `/spec-feature` skill | New skill | Restores the discipline that measurably worked |
| 10 | 10 — PR triage | Fix | One sitting; automation only if it recurs |
| — | 11, 12 | Nothing | Documented; revisit on recurrence |
