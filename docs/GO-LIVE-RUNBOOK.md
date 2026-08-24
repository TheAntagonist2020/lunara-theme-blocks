# Go-Live Runbook

How a theme or plugin change gets from `main` onto lunarafilm.com, how it is
proven, and how it comes back off if it is wrong. Written so a cold session —
or a fresh machine — can run a deploy without re-deriving any of it.

---

## 1. How deployment actually works here

Deployment is **manual and push-button**. It is not WordPress.com's native
Hosting → Deployments feature.

- The mechanism is the **Deployer for Git (Pro)** plugin, active on the site.
  The repo's `.deployignore` is its config: docs, tests, `node_modules`, and
  local artifacts never reach the live theme.
- The button lives in the **Lunara Control Desk** in wp-admin. Deploys go from
  `main` into a timestamped theme directory.
- **Auto-deploy is deliberately off.** It has been off since the 3.2.48
  incident, in which an auto-shipped release split the anonymous canonical
  `/journal/` cache with nobody watching. Merging to `main` is therefore
  always safe; nothing goes live until a human presses deploy.
- **Control Desk → System Status → Deploy Truth** names the live version, the
  active theme directory, the deploy moment, and any file that drifted outside
  the repo→deploy pipeline.

Every page carries the live identity in its head:

```html
<meta name="lunara-build" content="3.2.53+20260819-224806" />
```

That is `version` + `deploy moment`. It is the fastest way to answer "what is
actually live right now."

## 2. Deploy order

Ship **plugins before the theme**. Plugin read-path APIs are additive and inert
on their own; the theme is what consumes them. Deploying the theme first can
leave it calling accessors that do not exist yet.

1. Plugin repo(s) with changes — e.g. Oscars Ledger.
2. The theme (`main`).

## 3. Verify — the only step that counts

Immediately after the deploy lands:

```bash
tests/tools/lunara-canary-verify.sh <expected-version>
# e.g. tests/tools/lunara-canary-verify.sh 3.2.53
```

This runs the whole protocol: three cache-separated anonymous reads of the live
build stamp (to catch a split-brain edge), then both anonymous canonical
cache-coherency sentinels — Journal and Oscars.

**Exit 0 is GO. Everything else is ROLLBACK.** In particular:

| Exit | Meaning | Verdict |
| --- | --- | --- |
| 0 | `LIVE_COHERENT` | GO |
| 1 | `INCOHERENT` | ROLLBACK |
| 2 | `USAGE_ERROR` — the check did not run | NOT verified |
| 3 | `REPLAY_COHERENT` — replayed, not probed | NOT verified, never proof |

A sentinel that could not run is not a pass. Silence is not success.

The sentinels deliberately send no `Cache-Control` or `Pragma` request headers,
and accept only the exact anonymous no-query canonical URL. A cache-busted or
query-bearing probe can never satisfy them, because that is not what a visitor
receives.

## 4. Rollback

Rollback is one revert commit plus a redeploy. Keep a **standing exact-rollback
PR** open at all times against the current `main` tip — a commit whose *tree* is
byte-identical to the last known-good release.

Verify tree-exactness before trusting it:

```bash
git rev-parse <rollback-head>^{tree}      # must equal
git rev-parse <known-good-commit>^{tree}  # this
```

Rebuild it onto the new tip after every merge to `main`, so it is always one
merge away from restoring the good tree.

To roll back: merge that PR, redeploy from the Control Desk, then re-run the
verifier against the restored version.

## 5. Record it — the step that makes the next session cheap

A deploy is not finished when it is verified. It is finished when it is
**written down.**

Before the session ends, append an entry to **`docs/SESSION-LOG.md`** — commit
it and push it. That file is the answer to "where does this project stand and
whose move is it," and it is deliberately redundant with everything else:
conversations get summarized, tabs get closed, agent containers are reclaimed.
The repo is the only thing that reliably survives all three.

Record it whichever way the deploy went. A rollback is worth logging in more
detail than a clean release, not less.

The required shape of an entry — and the standing rules a fresh session needs
before it touches anything — live in **`CLAUDE.md`** at the repo root.

Three documents, three questions, no overlap:

| Document | Answers |
| --- | --- |
| `docs/CHANGELOG.md` | *What changed in the code, and why.* |
| `docs/GO-LIVE-RUNBOOK.md` | *How it ships and how it is proven.* |
| `docs/SESSION-LOG.md` | *Where things stand, and whose move it is.* |

## 6. Standing constraints

- No cache clearing as a fix. If a release needs a cache flush to look correct,
  the release is wrong — that is precisely the 3.2.48 failure.
- Read-only probes against production only. No staging writes.
- Licensed Klim Tiempos faces are served from `/wp-content/uploads/lunara-fonts/v1/`
  and are **never** committed to the public repo. They are not restored by a
  redeploy — see §7.
- Re-enabling auto-deploy should wait until canaries have been clean for
  several consecutive releases, and is a deliberate decision, not a default.

## 7. What is durable, and what is not

Safe without any action:

- **All theme and plugin code** — GitHub. A wiped laptop loses nothing; clone
  and continue.
- **Site content, settings, and the Oscars database** — WordPress.com, with
  Jetpack backups plus UpdraftPlus active on the site.

Needs deliberate care:

- **Licensed Tiempos font files.** They live only in WordPress uploads and are
  intentionally excluded from git for licensing reasons. That makes uploads the
  single copy in the deploy path. Keep an independent off-site copy of the
  original Klim license package; a redeploy will not restore these.
- **Anything in an agent session's scratchpad.** Session containers are
  ephemeral. Tooling worth keeping belongs in `tests/tools/` — excluded from
  deploys by `.deployignore`, so it stays in the repo without shipping to the
  live theme.

## 8. Reference: the 3.2.48 failure class

The sentinels exist to catch one specific shape of failure. Anonymous visitors
were served a *mix* of old and new markup for the same canonical URL: some
responses carried the modern route root, others the legacy one, with structural
CSS and font markers disagreeing. The page looked fine on a logged-in reload and
broken to the public.

The sentinels prove coherence by binding the served HTML to the deployed theme
version (`data-lunara-theme-version` on the modern root), censusing modern
versus legacy roots, and requiring structural CSS to precede `<body>`. If a
future failure does not trip them, extend them — do not lower them.
