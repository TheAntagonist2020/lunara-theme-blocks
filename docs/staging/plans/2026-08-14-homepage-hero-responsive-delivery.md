# Theme 3.2.39 — Homepage Hero Responsive Delivery Plan

1. Preserve attachment identity in automatic, featured, Spotlight, and Hero
   Command slide data without changing editorial selection.
2. Introduce one cached final-image descriptor for native hero rendering and
   responsive head hints; retain exact URL-only fallback behavior.
3. Align the HTML preload with the final filtered `srcset`/`sizes`, and omit the
   unreliable fixed HTTP hint for responsive attachments.
4. Guard response hints before any hero data work on routes or ownership modes
   where the native Home front door is not rendered.
5. Verify uncropped image ownership, one-slide static fallback parity,
   first-versus-later loading priority, local lookup caching, remote lookup
   avoidance, and responsive preload equality with runtime and contract tests.
6. Deploy the theme to staging only, measure anonymous cold/warm mobile Home,
   and compare `currentSrc`, request count, transfer size, LCP, and CLS against
   the Theme 3.2.38 baseline before any production decision.

Rollback is the previous successful Theme 3.2.38 deployment. This release does
not alter posts, Hero Command settings, plugins, cache/CDN settings, public copy,
motion, or lower-page Journal image delivery.
