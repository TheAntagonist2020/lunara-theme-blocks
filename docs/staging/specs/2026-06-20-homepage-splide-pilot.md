contract: Homepage Oscar Facts may opt into a theme-owned Splide pilot through scoped front-page assets only.

contract: Splide production files live under `assets/vendor/splide/` with the MIT license retained; no third-party carousel plugin assets are installed or enqueued.

contract: The pilot keeps the existing Oscar Facts block content, URLs, image data, Lunara typography, and public route behavior unchanged.

invariant: Reviews archive dynamic rail remains native and does not load Splide.

invariant: Reduced-motion users do not receive forced autoplay or slide animation.

invariant: The homepage remains usable if Splide fails to load; the existing Lunara carousel markup remains in place as fallback.

test: `powershell -ExecutionPolicy Bypass -File tests\homepage-splide-pilot.ps1`

test: Homepage visual QA at 390, 768, and 1280 confirms no horizontal overflow, visible Oscar Facts slides, controls/dots present, and no admin/control-desk leakage.

deferred: Migrating Reviews, Journal, Oscars portal, or other homepage lanes to Splide.
