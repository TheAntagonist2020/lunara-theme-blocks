# Homepage First Viewport Polish

contract: Preserve the existing Gutenberg-driven homepage, Homepage Studio controls, route URLs, desktop Reviews-first order, and mobile Journal-first order.

contract: Add a scoped public CSS layer that tightens the logo-led masthead into a premium editorial front door on desktop without adding new fonts, raw CSS controls, schema, routes, or content mutations.

contract: Desktop masthead layout should read as a command desk: identity and dek on the left, route cards in a compact right rail, and the first content lane visible sooner.

contract: Mobile remains stacked, readable, scrollable, and logo-safe, with no blur-stretch, no horizontal overflow, and no brown/gray drift.

invariant: The approved Lunara typography system remains the only public type language.

invariant: The homepage front door still exposes one screen-reader H1 and the three route cards for Reviews, Journal, and Oscars.

invariant: Oscar Facts signature lane behavior, verified-image rules, and Splide pilot remain untouched.

test: `powershell -ExecutionPolicy Bypass -File tests\homepage-first-viewport-polish.ps1`

test: Existing Homepage Studio and Oscar Facts homepage tests still pass.

test: Public homepage QA at `390`, `768`, and `1280` confirms no horizontal overflow, no broken images, no empty media chambers, no private/admin leakage, and visible first-viewport editorial identity.

deferred: Adding new Theme Studio controls, changing homepage content order, changing mobile ordering, moving blocks, or making a bespoke custom theme.

## Working Notes

- This pass follows the shipped Trade Front Door and Oscar Facts Signature Lane work.
- The desired feel is a denser publication-grade opening screen, not a new homepage architecture.
