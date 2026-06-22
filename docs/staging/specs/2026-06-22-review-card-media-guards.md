# Review Card Media Guards

## Goal

Every homepage and review-card path must treat missing or unusable media as an intentional text-led editorial card, not as an empty poster chamber.

## Contract

- The active homepage Latest Reviews renderer keeps its `has-visual` / `has-no-visual` card state.
- Legacy review shortcode/fallback renderers must compute real card media before printing a poster wrapper.
- Cards without media must not output `.lunara-review-grid-poster-wrap`.
- Cards without media must receive a scoped text-led state class.
- Review scores move inline when a card has no visual.
- Existing review links, quotes, score badges, trailer badges, and typography remain unchanged.

## Non-Goals

- No public URL changes.
- No post query changes.
- No database schema changes.
- No third-party carousel/plugin changes.
- No redesign of Reviews or homepage surfaces in this pass.

## Verification

- Focused PowerShell contract test covers active, fallback, and legacy shortcode renderers.
- PHP lint changed theme PHP files.
- `git diff --check` passes.
- Live homepage, journal, reviews, review single, and Oscars routes return 200.
- Responsive homepage evidence confirms no blank review-card image chambers.
