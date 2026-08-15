# Reviews Archive Composition — Theme 3.2.40

## Purpose

Theme 3.2.40 turns the existing Reviews route into a focused, editable archive
product without replacing Lunara's typography, card geometry, dynamic rail, or
canonical Review metadata. It also moves the route's static 42 KB presentation
out of the HTML response and into a cacheable stylesheet.

## Editorial contract

- Reviews Archive Studio edits the existing kicker, title, and deck settings.
- A bounded list of the 100 most recently modified published Reviews can set
  the canonical `_lunara_review_pinned` lead; Automatic removes every prior
  Review pin, including pins on non-public post statuses.
- The existing Hero, Review Grid, Pagination, and Pairing Desk lanes remain the
  only configurable lanes. The Review Order toolbar travels with Review Grid.
- Saved lane order is consumed by every direct public child; no legacy
  `!important` rule may override it.
- Disabling Hero retains one screen-reader H1. Director archives retain their
  own hero, grid, pagination, and default order regardless of Archive Studio
  visibility choices.

## Query contract

- A pin is promoted inside the default, unfiltered SQL ordering, so the normal
  page size and unique pagination remain intact. It is not promoted for another
  sort or a Review Year filter.
- The no-JavaScript Review Year selector appends the canonical
  `lunara_review_year` taxonomy clause without replacing an existing
  `tax_query`; sort and year survive pagination.
- `Recently Updated` uses `modified_desc`.
- A last page containing only the extracted lead still renders pagination.

## Markup and image contract

- The featured card is a non-anchor layout with independent media, title, and
  CTA links. The Oscar Ledger link is a sibling, so nested anchors are invalid
  by construction.
- Local media keeps WordPress attachment-native `srcset`, dimensions, and the
  closest registered Lunara image size.
- Canonical TMDB paths receive only distinct `w342`, `w500`, and `w780`
  candidates. Noncanonical TMDB paths and unknown providers retain the exact
  stored URL without invented candidates. No archive card requests `/original`.
- The lead remains lazy below the archive Hero. It becomes eager/high only when
  Hero is disabled; every support/run card remains lazy.

## Performance and visual acceptance

- `assets/css/lunara-review-archive.css` is route-scoped, cacheable, and
  protected from WP Rocket Remove Unused CSS.
- Only dynamic Archive Studio custom properties remain inline, under 2 KB.
- Anonymous Reviews HTML target: at most 190 KB, with at least about 35 KB
  removed from the 220,953-byte baseline.
- Desktop lead height must not exceed 760 px; mobile geometry, the shared type
  system, dynamic rail, reduced motion, and public visual density must remain
  intact.
- Source nested anchors: zero. Below-first-viewport eager content images: zero.

No plugin, post, cache/CDN setting, or production data is changed by this
theme release.
