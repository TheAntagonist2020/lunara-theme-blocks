# Lunara Film — read `AGENTS.md`

The working agreement for this project lives in **[`AGENTS.md`](AGENTS.md)** at
the repo root. Read it before doing anything. It is shared by every agent and
human working here, so there is exactly one copy to keep true rather than two
that drift.

This project has been bitten by duplicate definitions before —
`functions.php` carries `function_exists()`-guarded copies of functions that
`inc/` already defines, and the dead copy looks perfectly editable. A second
working agreement would fail the same way: both would look authoritative, only
one would be current, and nobody would know which.

## The one-line version, so nothing is lost if you read no further

1. Read `docs/SESSION-LOG.md` (top entry) — that is the handoff.
2. Read `docs/GO-LIVE-RUNBOOK.md` — how anything ships and how it is proven.
3. **Never deploy** — that is Dalton's button. Merging to `main` is safe.
4. **Never clear cache as a fix** — `ARCHITECTURE.md` says to; it is a stale
   historical snapshot and it is wrong.
5. Append a session-log entry before the session ends.

Everything else — the standing rules, the engineering discipline, the gate
commands, the branch convention, the repo map — is in `AGENTS.md`.
