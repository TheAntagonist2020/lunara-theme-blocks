# Performance Measurement Gate

## Purpose

Lunara performance candidates must be judged against repeated, contemporaneous evidence. A single cold trace is diagnostic evidence, not a release decision.

## Fixed protocol

- Use a 390 x 844 mobile viewport at DPR 3.
- Emulate Fast 4G at 4 Mbps down, 3 Mbps up, and 20 ms latency.
- Apply 4x CPU slowdown.
- Run five cold pairs. Odd pairs run control then candidate; even pairs run candidate then control.
- A cold run means a new browser context, a completed WordPress.com staging access challenge, and then a cleared browser cache before the timed navigation. The anonymous staging-access cookie may remain so the security interstitial is not measured as Lunara page load. Do not flush or change WordPress.com CDN, server cache, Jetpack Boost, WP Rocket, plugins, or WordPress options.
- Follow each cold load with one warm reload in the same context.
- Capture the first cold and warm Chrome trace for each arm. Use all five measurements for the gate.
- Record median LCP, LCP coefficient of variation, CLS, decoded HTML, inline CSS bytes, and LCP resource timing.

## Noise gate

Before reopening Phase 1D, run an A/A baseline with both arms pointed at the unchanged theme 3.1.99 staging URL.

- Each arm must return five valid LCP samples.
- Each arm's cold LCP coefficient of variation must be at most 10%.
- The median absolute paired A/A LCP delta must be at most 10%.
- If this gate fails, do not judge or deploy another code candidate. Repeat later and retain the noisy result as evidence.

## Candidate gate

A candidate is eligible only after the noise gate passes.

- Verify exact served theme versions for both arms from the live `style.css` header. WordPress.com asset `?ver=` values are file timestamps, not release versions.
- Never compare WordPress.com staging with production as though they were interchangeable control and candidate hosts.
- Require candidate median cold LCP regression of no more than 10%.
- Require candidate median CLS to remain within the relative 10% gate, with a 0.005 measurement floor when control CLS is zero or near zero.
- Keep the existing route, H1, Search, image, console, overflow, and visual-geometry gates.
- HTML and inline CSS reductions are supporting evidence; they cannot overrule a failed LCP or CLS gate.

## Runner

Run `tests/tools/lunara-performance-benchmark.ps1` with explicit control URL, candidate URL, evidence directory, labels, and expected theme versions. Output includes `REPORT.md`, `summary.json`, `runs.json`, and sampled cold/warm traces under `traces/`.

The runner exits `0` for a stable A/A baseline or a passing comparable candidate, `2` for noisy measurements, `3` for a failed candidate, and `4` for a cross-origin comparison that must remain inconclusive.
