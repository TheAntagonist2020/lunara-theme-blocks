# Homepage Signature Density Controls Plan

## Steps

1. [x] Add a failing contract test for Homepage Studio signature-lane density controls.
2. [x] Add the three bounded density select controls and three bounded card-height controls to Homepage Studio.
3. [x] Extend homepage CSS output with density-driven public variables and scoped lane styling.
4. [x] Run the focused contract, PHP lint, and `git diff --check`.
5. [x] Deploy only changed files, flush cache, run public smoke checks, and capture responsive evidence.
6. [x] Update continuity docs and commit/push the theme repo.

## Acceptance

- Theme Studio exposes direct compact/editorial/showcase controls for Latest Reviews, Journal, and Oscar Facts.
- Numeric controls clamp safely and persist through the existing Homepage Studio save handler.
- Homepage CSS responds to the controls without affecting non-homepage routes.
- Latest Reviews keep 3:4 media discipline.
- Journal and Oscar Facts remain visually dynamic without empty chambers or horizontal overflow.
