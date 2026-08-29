# Working agreement — Lunara Film

**This file is the canonical operating agreement for any agent or engineer
working on lunarafilm.com — Codex, Claude, or a human.** `CLAUDE.md` points
here rather than restating it, so there is exactly one copy to keep true.

---

## Start here — the four-step cold start

1. **`docs/SESSION-LOG.md`** — where the project stands *right now* and whose
   move it is. Newest entry first; read the top entry at minimum. This is the
   handoff.
2. **`docs/GO-LIVE-RUNBOOK.md`** — how anything reaches production and how it
   is proven. Non-negotiable; §5 and §6 in particular.
3. **`docs/CHANGELOG.md`** — what changed in the code and why, per release.
   Covers all seven repos, not just the theme.
4. **This file**, to the end.

### Do not trust these two files

`ARCHITECTURE.md` and `README.md` are **historical snapshots retained for
recovery context.** They describe the retired
`lunara-film-premium-20260503-living-pulse` theme and a deploy process that no
longer exists.

`ARCHITECTURE.md` says *"Always Clear Cache after a deploy"* and documents scp
uploads from a Windows desktop. Both are **wrong now, and the first one is
actively harmful** — see the no-cache-clearing rule below. Read them for
archaeology, never for instructions.

The current architecture is: `functions.php` requires `functions-loader.php`,
which loads the live modules under `inc/`. See the duplicate-definitions
warning below before editing anything in either file.

---

## Close every session by appending to the session log

**Mandatory for any session that changes code, ships a release, or changes what
is live.** Append a new entry at the top of the newest-first list in
`docs/SESSION-LOG.md`, commit it, and push it — *before* the session ends, not
after someone asks. Agent containers are ephemeral and conversations get
summarized or truncated; the repo is the only thing that survives all of it.

An entry carries these sections. Omit one only when it genuinely does not apply,
and say so rather than leaving it silently blank:

- **Headline** — one paragraph. What is true now that was not true before.
- **Verified live state** — a table of what was actually probed, with results.
  Facts you measured, not facts you expect. If you did not verify it, do not
  table it.
- **What shipped and why** — the reasoning, not just the diff. Point at the
  `docs/CHANGELOG.md` entry for code-level detail rather than duplicating it.
- **Commit ledger** — repo, SHA, meaning.
- **Gate ledger** — every gate run, with counts, and every gate *not* run.
- **Corrections** — anything in the durable record that turned out wrong, fixed
  in place at the original entry with a pointer forward.
- **Logged, not fixed** — real problems found and deliberately not addressed, so
  they are never rediscovered from scratch.
- **Punch-list carried forward** — with status and whose call each item is.
- **Whose move it is next** — end every entry knowing this.

**Never rewrite a past entry to agree with the present.** If a past entry was
wrong, add a correction line *inside it* pointing at the entry that supersedes
it. A log tidied into correctness is worth nothing.

---

## Standing rules that do not expire

- **Deployment is Dalton's button, always.** The mechanism is the *Deployer for
  Git (Pro)* plugin, driven from the Lunara Control Desk in wp-admin — **not**
  WordPress.com's native Hosting → Deployments, and **not** scp. No agent
  tooling can or should trigger it. Merging to `main` is safe; nothing goes live
  until a human clicks.
- **Auto-deploy stays off** until canaries have been clean for several
  consecutive releases. A deliberate decision, not an oversight.
- **Verify after every deploy** with the version argument, which is required:
  `bash tests/tools/lunara-canary-verify.sh <version>`. A bare invocation exits
  2. Exit 0 is GO. Exit 1, 2, or 3 is not a pass — 3 (`REPLAY_COHERENT`) is
  never proof.
- **No cache clearing as a fix.** If a release needs a flush to look correct,
  the release is wrong. That is the 3.2.48 failure, exactly. Ignore
  `ARCHITECTURE.md` on this point.
- **Adding a key to a cached payload requires bumping the cache version.**
  Otherwise shape-old payloads are served to shape-new readers for the life of
  the TTL. Clear the retired key in the flush routine too. This is the 3.2.48
  failure class in miniature and it has already happened twice — most recently
  as the 3.2.53 defect.
- **Read-only probes against production only.** No staging writes.
- **Rebuild the exact-rollback hatch after every merge to `main`** — docs-only
  merges included — and verify tree-exactness before trusting it:

  ```bash
  git rev-parse origin/claude/rollback-exact-theme-3.2.43^{tree}
  # must equal c55bf394594149db2888295c5d51f85f47b2b520   (the 3.2.43 tree)
  ```

  Refer to the hatch by **branch name or PR #159, never by SHA.** The head moves
  at every merge, so a SHA written into a document goes stale immediately — and
  a stale one is worse than none: merging a rollback commit parented on the
  wrong tip does not restore the exact tree.
- **Licensed Klim Tiempos font files are never committed.** They live only in
  `/wp-content/uploads/lunara-fonts/v1/` and are not restored by a redeploy.
- **Deploy plugins before the theme.** Plugin read-path APIs are additive and
  inert alone; the theme consumes them.

---

## Engineering discipline — lessons this project already paid for

- **Mutation-test before every release.** Break the thing, confirm the test
  fails, restore. If a mutation is *not* caught, say so plainly and explain why
  — never round a partial result up to full coverage.
- **Back up files before mutating them, with `cp`, not git.** `git checkout --`
  reverts to HEAD and will destroy uncommitted work. This has already cost one
  session a full set of edits.
- **When a grep returns zero, suspect the grep first.** Three false alarms in
  this project came from searching for IDs where the markup uses classes.
  Confirm the selector exists before reporting an absence.
- **When two surfaces render the same data, check both.** The 3.2.53 defect
  survived indefinitely because one of the two consumers rebuilt the data itself
  and therefore always looked healthy.
- **Watch for dead duplicate definitions.** `functions.php` carries
  `function_exists()`-guarded copies of functions that `inc/` defines first via
  `functions-loader.php`. **The `inc/` copy is the live one.** Confirm which
  definition actually executes before editing — editing the dead copy produces a
  clean diff, a green test run, and no behavior change.

---

## Repository map

The theme is the hub. Six plugins sit behind it:

| Repo | What it is |
| --- | --- |
| `lunara-theme-blocks` | the child theme (`Template: blocksy`). This repo. |
| `lunara-plugin-oscars-ledger` | the Academy Awards database — 12,138 rows in `data/oscars.csv`, plus 11,291 TMDB mappings |
| `lunara-plugin-core` | core services |
| `lunara-plugin-journal-foundation` | the journal CPT and archive |
| `lunara-plugin-dispatch` | dispatch pipeline |
| `lunara-plugin-imdb-guard` | IMDb data guarding |
| `lunara-plugin-ai-assistant-classic` | assistant surface |

`docs/CHANGELOG.md` and `docs/SESSION-LOG.md` live in this repo and cover all
seven. Only this repo carries an agent file; if you are working in a plugin
repo, read this one first.

---

## Branch and PR convention

- Work on a topic branch: `codex/<topic>-<version>` or
  `claude/<topic>-<slug>`. Never commit directly to `main`.
- Open a PR into `main`. CI is a single `lint` workflow.
- **Do not open a PR unless asked**, and **never deploy** — merging is safe,
  deploying is Dalton's click.
- After any merge to `main`, rebuild the rollback hatch (above).
- If your branch's PR has already merged, restart the branch from the current
  `main` rather than stacking onto merged history.

---

## Gates available locally

```bash
# PowerShell contract suite — 89 top-level files in Theme 3.2.56
pwsh tests/<name>.ps1

# PHP runtime contracts
php tests/<name>-runtime.php

# Post-deploy verification — version argument REQUIRED
bash tests/tools/lunara-canary-verify.sh <version>
```

Run the full contract suite before any release. `.deployignore` keeps `docs`,
`tests`, `.github`, `AGENTS.md`, and `CLAUDE.md` out of the live theme, so
tooling and instructions live in the repo without shipping to production.
