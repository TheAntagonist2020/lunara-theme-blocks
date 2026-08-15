# Theme 3.2.42 — Reviews First-Paint Staging Gate

Theme 3.2.42 keeps the reviewed Reviews Archive Studio work while repairing
the production-only interaction with stale Jetpack Boost critical and
aggregate CSS. This remains a theme-only staging candidate.

## Automated prerequisites

- The renderer-owned `#primary.lra` wrapper must be present on the Review CPT
  archive, director taxonomy, explicit Reviews page template, and the
  slug-selected default page.
- The universal nested structural seed must remain at or below 12,288 raw
  bytes; the external route asset must remain at or below 45 KB.
- Exact archived Boost critical and aggregate replay must keep deferred
  delivery, critical withdrawal, and settled seed-removal geometry within one
  pixel at 1440, 782, and 390 pixels.
- The direct `lunara-review-archive.css` link and both nonempty authority
  style blocks must occur before `<body>`; the link may not be aggregated,
  async, `media="not all"`, or driven by an onload media flip.

## Mandatory device/browser smoke

Before production approval, open the staging Reviews archive on a real iPhone
in Safari. This is mandatory because the compact first-paint guard uses native
CSS nesting. Confirm the Hero, toolbar/year filter, lead card, pagination, and
Pairing Desk render in their final order before scrolling; there must be no
flash, lane jump, horizontal overflow, missing styles, or backdrop escape.
Repeat once with Hero disabled and once on a director archive.

## Performance gate

- Mobile median CLS must be at or below 0.02; desktop median CLS at or below
  0.03 across five cold traces.
- Raw LCP and LCP minus TTFB may regress no more than 10% from Theme 3.2.39.
- Reviews HTML remains at or below 190 KB and route CSS at or below 45 KB.

Do not change Jetpack Boost, WP Rocket, CDN, plugin, post, or WordPress-option
state during this gate. A failure returns staging to Theme 3.2.39; production
requires a separate explicit approval.
