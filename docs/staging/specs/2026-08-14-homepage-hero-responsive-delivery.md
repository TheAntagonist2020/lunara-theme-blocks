# Homepage Hero Responsive Delivery — Theme 3.2.39

## Problem

The production native cinematic hero announced a fixed Photon preload at
`?resize=1600,900&quality=86&ssl=1` (about 134 KB), while WordPress.com rewrote
the visible LCP image to the 3038×1713 i0.wp.com original (about 348 KB) with no
`srcset`. Chrome therefore ignored the preload and estimated roughly 360 KB / 
350 ms of avoidable mobile work.

## Delivery contract

- Hero Command and the automatic deck remain the editorial source of truth.
- Each local slide preserves its attachment ID before URL right-sizing.
- Attachment images render once through `wp_get_attachment_image( ..., 'full' )`
  so WordPress produces uncropped responsive candidates and all final image
  filters run. The final markup is parsed into `src`, `srcset`, `sizes`, width,
  and height; the renderer and preload reuse that exact descriptor.
- Full Frame, cover, focal position, zoom, overlay, eager/high first-image
  semantics, and lazy/low later-image semantics remain unchanged.
- Responsive HTML preloads use `imagesrcset` and `imagesizes` with no fixed
  `href`. URL-only sources use one exact `href` matching the rendered `src` and
  may retain the HTTP Link hint.
- Responsive attachment hints are not built during `template_redirect`; they
  wait for `wp_head`, after image filters initialize. Non-home, disabled native
  front-door, and plugin-owned routes exit before resolving the hero deck.
- Only current-site uploads and their i0.wp.com proxy form may query attachment
  storage. The request cache prevents repeated local lookups; TMDB/external art
  remains URL-only and performs no attachment lookup.

## Selection assumptions

At 390 CSS pixels and DPR 2, a full-width hero asks for roughly 780 source
pixels. The current attachment offers 768w, 1080w, 1920w, and original-width
candidates, so Chrome is expected to choose 1080w (or 768w when its selection
heuristics/network state permit), not the 3038w original. A wider viewport or
higher DPR can legitimately select 1920w or the original; the invariant is that
the preload and final `<img>` expose the same candidates and sizes, not that one
candidate is forced for every device.

## Staging acceptance

- Anonymous mobile Home has one hero request, and the preload-selected resource
  equals `img.currentSrc`.
- At 390×844 / DPR 2, `currentSrc` is a sized candidate, not the 3038w original.
- The hero retains its current crop/full-frame geometry, focal position, and
  explicit dimensions; CLS does not regress by more than 10%.
- LCP does not regress by more than 10%; the 134 KB unused preload disappears;
  no second hero request appears.
- Reviews, Journal, Oscars, singles, disabled native-front-door, and plugin-owned
  routes emit no native Home hero preload and incur no hero-deck query.
- WordPress.com/Jetpack does not replace the responsive markup after output; if
  it does, rollback Theme 3.2.39 and inspect the staging response optimizer.

