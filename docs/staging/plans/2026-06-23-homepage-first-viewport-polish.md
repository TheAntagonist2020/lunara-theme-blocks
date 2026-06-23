# Homepage First Viewport Polish Plan

## Scope

Tighten the homepage opening screen while preserving the current WordPress block architecture and Homepage Studio settings.

## Tasks

1. Add a contract test for the first-viewport CSS layer.
2. Add homepage-only late CSS for the desktop command-desk masthead and tighter first-section rhythm.
3. Run local tests and PHP lint.
4. Deploy only changed theme files after backup.
5. Verify public routes, responsive homepage visuals, cache, hashes, and continuity docs.

## Acceptance

- Desktop masthead uses a two-column editorial command layout at wide viewports.
- Mobile remains stacked and does not change the Journal-first rhythm.
- The first content lane sits closer to the masthead without crowding.
- No new public URL, database, post content, or Theme Studio setting changes.
- No font drift, admin leakage, empty image chambers, broken images, or horizontal overflow.
