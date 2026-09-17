# Performance work — September 17, 2026

## Live changes and measurements

Theme 3.2.86 is live at build `3.2.86+20260917-165902`, after PR #206
merged as `d3373c6`. The versioned canary and the Journal/Oscars identity probes
passed. The exact 3.2.43 rollback branch was refreshed onto this main tip and its
tree matched `c55bf394594149db2888295c5d51f85f47b2b520`.

WordPress.com's global edge cache was disabled. It was enabled in the hosting
settings under Dalton's authorization to improve performance; the success notice
and a settings reload confirmed the change. No cache purge was performed.

Anonymous canonical URL requests changed from CDN BYPASS to MISS, then HIT.
These are two requests per route in each state, measured from this workstation;
they establish cache behavior, not a universal page-load guarantee.

| Route | Previous repeat TTFB | Edge-cache HIT TTFB |
| --- | ---: | ---: |
| Home | 0.198 s | 0.073 s |
| Journal | 1.684 s | 0.053 s |
| Reviews | 1.841 s | 0.092 s |
| Oscars | 2.056 s | 0.060 s |

All four cache HITs reported 1 ms CDN server time. The initial MISS requests
still took 1.6–2.5 seconds, so origin rendering remains a separate opportunity.
The [WordPress.com cache guide](https://wordpress.com/support/clear-your-sites-cache/)
documents this hosting feature; adding another caching plugin is unnecessary.

One fresh mobile Home test through WordPress.com's Performance screen reported
70/100, FCP 0.96 s, LCP 1.96 s, TTFB 0.04 s, TBT 0.08 s and CLS 0.88.
Its previous report was one day old: 60/100 and LCP 3.53 s. This is a comparison
of two reports, not a controlled attribution of every improvement to edge caching.
There was no CrUX history available. The fresh report's two layout shifts need
their own fix; a faster LCP does not establish visual stability.

## Theme 3.2.87 source changes

- Keep the first hero slide's exact responsive source and preload. Later slides
  give their sources to Splide's nearby loader, preparing adjacent slides instead
  of exposing the entire overlapping fade deck to native image loading at once.
  Pagination begins preparing a distant destination when its transition starts.
  Static and failed-JavaScript fallbacks keep a real first image.
- Load the Review debrief poster lazily below the article, retaining its source,
  dimensions, responsive sizes and wrapper.
- Give the hero flex items their final 100% width before Splide initializes.
  A tiny synchronous head style sets the width and flex basis, retaining natural
  caption height. This addresses a demonstrated startup geometry mismatch;
  the final production CLS must still be measured after deployment.
- Serve a generated shell stylesheet outside the Oscars portal. Remove only
  complete rules whose every selector is positively anchored to
  `body.lunara-oscars-portal-page`; retain all other rules and cascade order,
  cleaning only empty-line indentation and excess trailing blank lines.
  The full canonical shell remains on the portal, previews and fallback paths.
  Source size falls from 185,982 to 77,430 bytes: 108,552 fewer raw bytes and
  9,719 fewer bytes with local default gzip. Production compression/aggregation
  can differ; this is not a measured live transfer reduction yet.

The original `assets/css/lunara-shell.css` is the canonical editing surface.
After changing it, run `node tests/tools/build-shell-css.cjs`, then
`node tests/tools/build-shell-css.cjs --check`. The generator uses pinned
development-only CSS parsers and embeds a source hash. Its focused tests cover
mixed selectors, pseudo-classes, nested media, retained order and stale output.
No Node dependency or generator is deployed with the theme.

## Evidence and limits

Local evidence lives in `../_carousel-artifacts/performance-3.2.87/`: before/after
headers, timings, public HTML, CSS/JS inventories and release-verification logs.
The directory names the candidate; captured production pages are 3.2.86.
The earlier plugin cleanup is recorded in `PERFORMANCE-PLUGIN-AUDIT-2026-09-17.md`.

The source candidate requires the normal merge and Dalton's manual theme
deployment before any post-deploy speed or layout result can be claimed.
No plugin was additionally deactivated, no image quality was reduced, and no
production content or artwork was changed in this pass.

The phone browser fixture reproduced the hero bug at a 390px viewport: before
initialization the first slide measured 1,060.5px wide, then shrank to 390.4px.
With the seed, slide, image, caption and track dimensions match before and after
initialization. The complete carousel still gains its existing 96px controls area
on mounting in this fixture; this narrower fix does not claim zero total CLS.
The image-loading browser check confirmed that distant images remain deferred,
Next prepares the following slide, and a direct jump to slide four loads its art.

Focused source checks passed: 50 homepage carousel runtime assertions, 57
carousel lifecycle assertions, the responsive hero and Review composition
runtimes, five CSS parser/artifact cases, 17 shell route/fallback cases, relevant
PHP/JS syntax checks and whitespace checks. No broad suite or repeated production
speed test was run. An independent review found no release-blocking issue.

Full-shell versus reduced-shell browser comparisons passed on Home, Journal
and Reviews at 390px and 1280px. Compared route boxes were identical; the Home
hero also matched, apart from less than 0.001px on its animated desktop image.
These fixtures substituted the same local artwork and fonts on both sides and
blocked remote execution. They isolate CSS geometry rather than measure live
download performance or certify production Core Web Vitals.
