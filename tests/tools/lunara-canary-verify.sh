#!/usr/bin/env bash
# Lunara post-deploy canary verifier.
#
#   tests/tools/lunara-canary-verify.sh <expected-theme-version>
#   tests/tools/lunara-canary-verify.sh 3.2.52
#
# Runs the whole go/no-go protocol in one shot, in the order that matters:
#   1. three cache-separated anonymous reads of the live build stamp, so a
#      split-brain edge (one version here, another there) cannot hide behind a
#      single lucky hit
#   2. the Journal anonymous canonical cache-coherency sentinel
#   3. the Oscars anonymous canonical cache-coherency sentinel
#
# Exit 0 — GO. Every check proved the expected version is coherently live.
# Exit 1 — ROLLBACK. Anything else. A sentinel that could not run, replayed
#          instead of probing, or returned an unexpected code counts as NOT
#          verified; only a real live pass is proof.
# Exit 2 — usage error.
#
# Deliberately sends no Cache-Control/Pragma request headers. The entire point
# is to observe what an anonymous visitor actually receives from the edge, not
# what the origin would regenerate if asked politely.

set -u

EXPECTED="${1:-}"

if [ -z "$EXPECTED" ]; then
  echo "usage: $0 <expected-theme-version>   (e.g. 3.2.52)" >&2
  exit 2
fi

# Resolve the theme root from this script's own location so the verifier works
# from any checkout on any machine, not just one operator's home directory.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
THEME_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"

cd "$THEME_DIR" || { echo "cannot enter $THEME_DIR" >&2; exit 2; }

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

fail=0
note() { printf '%s\n' "$*"; }
rule() { printf '%s\n' "------------------------------------------------------------"; }

rule
note "LUNARA CANARY VERIFY - expecting theme $EXPECTED"
rule

# --- 1. identity: three cache-separated anonymous reads -----------------------
note ""
note "[1/3] Live build identity (3 cache-separated anonymous reads)"
seen_versions=""
for i in 1 2 3; do
  code=$(curl -sS -o "$WORK/read-$i.html" -w '%{http_code}' "https://lunarafilm.com/journal/" 2>/dev/null)
  stamp=$(grep -o '<meta name="lunara-build" content="[^"]*"' "$WORK/read-$i.html" 2>/dev/null \
          | sed 's/.*content="//; s/"$//')
  ver="${stamp%%+*}"
  bytes=$(wc -c < "$WORK/read-$i.html" | tr -d ' ')
  note "      read $i: http=$code bytes=$bytes build=${stamp:-<none>}"
  seen_versions="$seen_versions $ver"
  if [ "$code" != "200" ]; then
    note "      ^ FAIL: non-200 response"
    fail=1
  fi
  if [ "$ver" != "$EXPECTED" ]; then
    note "      ^ FAIL: serving '$ver', expected '$EXPECTED'"
    fail=1
  fi
  # Separate the reads so three consecutive hits cannot all be one warm object.
  [ "$i" -lt 3 ] && curl -sS -o /dev/null "https://lunarafilm.com/?canary-bust=$i$$" 2>/dev/null
done

uniq_count=$(printf '%s\n' $seen_versions | sort -u | wc -l | tr -d ' ')
if [ "$uniq_count" != "1" ]; then
  note "      FAIL: split brain - edge served more than one version:$seen_versions"
  fail=1
else
  note "      all three reads agree on:$seen_versions"
fi

# --- 2 & 3. the two anonymous canonical cache-coherency sentinels -------------
run_sentinel() {
  label="$1"; script="$2"; step="$3"
  note ""
  note "[$step] $label sentinel"
  if [ ! -f "$script" ]; then
    note "      FAIL: sentinel missing at $script"
    fail=1
    return
  fi
  node "$script" --expected-version "$EXPECTED" > "$WORK/$label.json" 2>&1
  rc=$?
  case "$rc" in
    0) note "      LIVE_COHERENT (exit 0)" ;;
    1) note "      INCOHERENT (exit 1) - failures:"
       python3 - "$WORK/$label.json" <<'PY' 2>/dev/null || sed -n '1,25p' "$WORK/$label.json"
import json,sys
d=json.load(open(sys.argv[1]))
for f in (d.get("failures") or [])[:12]:
    print("        -", f)
det=d.get("detail") or {}
keys=("modernRootCount","legacyRootCount","rootVersion","expectedVersion","danglingAnchorLinks")
bits=[f"{k}={det[k]}" for k in keys if k in det]
if bits: print("        detail:", ", ".join(bits))
PY
       fail=1 ;;
    2) note "      USAGE_ERROR (exit 2) - the check did not run; treat as NOT verified"
       sed -n '1,15p' "$WORK/$label.json"
       fail=1 ;;
    3) note "      REPLAY_COHERENT (exit 3) - replay is never proof; treat as NOT verified"
       fail=1 ;;
    *) note "      unexpected exit $rc - treat as NOT verified"
       sed -n '1,15p' "$WORK/$label.json"
       fail=1 ;;
  esac
}

run_sentinel journal "tests/tools/lunara-journal-canonical-coherency-gate.js" "2/3"
run_sentinel oscars  "tests/tools/lunara-oscars-canonical-coherency-gate.js"  "3/3"

# --- verdict -----------------------------------------------------------------
note ""
rule
if [ "$fail" -eq 0 ]; then
  note "VERDICT: GO - $EXPECTED is coherently live on both canonical routes."
  rule
  exit 0
fi
note "VERDICT: ROLLBACK - $EXPECTED is NOT proven coherently live."
note ""
note "Rollback path (see docs/GO-LIVE-RUNBOOK.md):"
note "  1. merge the standing exact-rollback PR against the current main tip"
note "  2. redeploy from the Control Desk"
note "  3. re-run this verifier against the restored version"
rule
exit 1
