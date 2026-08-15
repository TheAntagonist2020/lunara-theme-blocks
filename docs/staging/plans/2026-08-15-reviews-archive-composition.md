# Theme 3.2.40 — Reviews Archive Composition Release Plan

1. Merge only after the local PHP lint, all theme contracts, the isolated
   Reviews runtime, and independent code review are green.
2. Deploy the theme only to WordPress.com staging from the approved `main`
   commit. Do not deploy or modify Core, Oscars, Journal Foundation, Dispatch,
   WP Rocket, Jetpack Boost, CDN, or WordPress options during this gate.
3. In Reviews Archive Studio, verify copy edits, Automatic/pinned lead, all
   four visibility toggles, and several non-default lane orders. Confirm the
   toolbar always precedes and travels with Review Grid.
4. Verify default Reviews page 1/page 2 uniqueness, one-item last-page
   pagination, all three sort modes, a Review Year filter, and the canonical
   `/review-year/2026/` route. Confirm director archives retain one H1 and their
   own visible structure.
5. Validate desktop and mobile HTML: no nested anchors, no horizontal overflow,
   desktop lead at or below 760 px, no TMDB `/original`, only the true lead
   eager/high when Hero is off, and every support/run image lazy.
6. Measure cold/warm anonymous Reviews HTML transfer, route CSS transfer, LCP,
   and CLS. Require HTML at or below 190 KB and no greater than 10% LCP/CLS
   regression before production is considered.
7. Hold production for a separate explicit approval. Roll back to the previous
   successful Theme 3.2.39 deployment on missing CSS, broken navigation,
   duplicated/missing cards, lane-order drift, image regression, or performance
   regression.
