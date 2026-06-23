# Homepage Oscar Facts Signature Lane

contract: The homepage Oscar Facts lane remains the existing theme-owned Splide carousel and keeps the current Oscar Fact query, public URLs, verified-image rules, visual focus metadata, and reduced-motion behavior.

contract: Add a compact public ledger console inside the Oscar Facts carousel shell with a visible current/total slide counter and progress rail.

contract: The Splide pilot updates the ledger console, active card state, previous/next card state, `aria-current`, and a bounded progress CSS custom property whenever the active slide changes.

contract: The public styling makes the lane feel like a premium Oscar Ledger signature module through stronger framing, active-slide hierarchy, progress treatment, and mobile-safe controls without introducing unrelated typography, clutter, or plugin-default chrome.

invariant: Oscar Facts still renders only verified non-held visuals publicly; unresolved held facts remain excluded from image-backed card chambers.

invariant: Homepage remains usable if Splide fails to initialize; the static Lunara carousel fallback stays intact.

invariant: No post content, Oscar Fact metadata, schema, public routes, homepage section order, or Theme Studio settings change in this pass.

test: `powershell -ExecutionPolicy Bypass -File tests\homepage-oscar-facts-signature-lane.ps1`

test: `powershell -ExecutionPolicy Bypass -File tests\homepage-splide-pilot.ps1`

test: Public homepage QA at `390`, `768`, and `1280` confirms no horizontal overflow, no broken images, no empty media chambers, no admin leakage, initialized Splide, visible console, and working slide controls.

deferred: Re-sourcing held Oscar Fact images, changing the Oscar Fact query, adding new Theme Studio knobs, or migrating other homepage lanes to Splide.

## Working Notes

- Dalton approved continuing after the `Trade Front Door` homepage package was applied.
- Use `inc/frontend.php`, `functions.php`, and `assets/js/lunara-splide-pilot.js` only unless verification exposes a missing hook.
