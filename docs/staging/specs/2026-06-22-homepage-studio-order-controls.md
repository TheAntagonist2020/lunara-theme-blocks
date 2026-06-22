contract: Homepage Studio exposes bounded section-order presets instead of requiring raw Customizer slug editing.

contract: The preset source of truth is stored in `lunara_home_section_order_preset`, and saving a preset writes a sanitized derived `lunara_home_section_order` string.

contract: Presets include Editorial default, Journal first, and Oscars forward, with clear desk-facing copy explaining what each affects.

invariant: Public homepage URLs, Gutenberg block content, existing section slugs, visibility toggles, and section-order CSS remain unchanged.

invariant: The control is admin-only and uses the existing Homepage Studio nonce/save path.

invariant: No raw CSS textarea or freeform order editor is introduced in this pass.

test: `powershell -ExecutionPolicy Bypass -File tests\theme-studio-homepage-order-controls.ps1`

test: PHP lint `inc\control-desk.php` and `functions.php` if touched.

test: `/`, `/journal/`, `/reviews/`, `/reviews/sinners-2025/`, and `/oscars/` return 200 after deploy, with no Control Desk/admin leakage.

deferred: Drag-and-drop custom section ordering, per-breakpoint section ordering, and full homepage block migration out of Gutenberg.
