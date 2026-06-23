# Homepage Mobile Card Runway

contract: Preserve the current homepage architecture, content queries, section order, desktop Reviews-first order, and mobile Journal-first order.

contract: Add a scoped mobile-only public CSS layer that turns the homepage Latest Reviews run into a denser editorial rail below `820px`, reducing long poster-card slabs without hiding Review links, scores, trailer badges, titles, quotes, or images.

contract: Keep Journal lead treatment and Oscar Facts signature lane behavior intact; this pass only tightens the mobile run rhythm after the front-door handoff.

contract: Do not change theme mods, post data, URLs, schema, image metadata, fonts, or Splide/Oscar Facts behavior.

invariant: Review card imagery stays 3:4 and uses existing image sources.

invariant: Mobile card compaction must not create horizontal overflow or empty media chambers.

test: `powershell -ExecutionPolicy Bypass -File tests\homepage-mobile-card-runway.ps1`

test: Public homepage QA at `390`, `768`, and `1280` confirms no overflow, no broken images, one H1, no Control Desk leakage, desktop Reviews-first, mobile Journal-first, and a compact mobile Reviews rail.

deferred: Rebuilding the entire homepage, changing Homepage Studio controls, changing Review archive layout, changing Oscar Picks lane density, and sourcing new imagery.

## Working Notes

- Live 390px inspection showed Latest Reviews mobile cards around 535-790px tall each.
- The target is denser editorial rhythm, not hiding content or making the cards feel cheap.
