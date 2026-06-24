# Homepage Text-Led Card Chamber Cleanup Plan

## Steps

1. [x] Add a failing contract test for homepage text-led card chamber cleanup.
2. [x] Add a scoped CSS emitter that compacts homepage Review and Journal `has-no-visual` cards and tightens the Oscar Facts header band.
3. [x] Run the focused contract, related homepage contracts, PHP lint, and `git diff --check`.
4. [x] Deploy only changed runtime files, flush cache, run public smoke checks, and capture responsive evidence.
5. [x] Update continuity docs and commit/push the theme repo.

## Acceptance

- Homepage Review cards without visual media no longer occupy poster-card vertical space.
- Homepage Journal cards without visual media no longer occupy media-card vertical space.
- Visual cards keep the existing 3:4 or 16:10 media discipline.
- The Oscar Facts header band reads as a compact command strip before the carousel.
- The cleanup is scoped to `body.home` and does not affect Reviews archive, Journal archive, single Reviews, or Oscars surfaces.
- No empty image wrappers are visible publicly.
