contract: Homepage Studio presets control desktop and mobile homepage section order separately.

contract: Desktop order continues to write `lunara_home_section_order`; mobile order writes the new sanitized `lunara_home_section_mobile_order`.

contract: Presets keep the same three public choices: Editorial default, Journal first, and Oscars forward.

invariant: Desktop defaults remain Reviews-first. Mobile defaults are Journal-forward for the Editorial default preset.

invariant: Public URLs, homepage section slugs, Gutenberg content, visibility toggles, and existing desktop order behavior remain compatible.

invariant: Responsive order rules are emitted only for the homepage and only at the existing mobile breakpoint.

invariant: The critical header shell must read the same sanitized desktop and mobile order maps; it must not hardcode homepage section order.

invariant: The canonical homepage registry in `inc/helpers.php` includes every renderable homepage slot controlled by Theme Studio, including Oscar Picks and Oscar Facts.

invariant: The control remains admin-only and uses the existing Homepage Studio nonce/save path.

test: `powershell -ExecutionPolicy Bypass -File tests\theme-studio-homepage-responsive-order.ps1`

test: PHP lint `functions.php`, `header.php`, `inc\helpers.php`, and `inc\control-desk.php`.

test: `/`, `/journal/`, `/reviews/`, `/reviews/sinners-2025/`, and `/oscars/` return 200 after deploy, with no Control Desk/admin leakage.

deferred: Drag-and-drop custom ordering, per-breakpoint custom freeform editors, and per-lane visual-density sliders.
