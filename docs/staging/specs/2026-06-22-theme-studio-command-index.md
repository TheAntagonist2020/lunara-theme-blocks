# Theme Studio Command Index

contract: Add an admin-only `Theme Studio Command Index` near the top of `Lunara > Theme Studio`, after the summary cards and before the individual control panels. It must make the existing control surfaces feel like one customization cockpit instead of isolated panels.

interface:
- The index renders route/control cards for Brand, Homepage, Journal, Reviews, Image Authority, and Oscar Facts.
- Each card shows a status label, what the surface affects, the direct control anchor, desktop preview, 390px preview where relevant, and the next likely control layer.
- Cards use existing Theme Studio anchors and public URLs; they do not create new routes, post types, settings, or public output.
- The index is private to wp-admin and should not render on public routes.

data:
- The command index is driven by a small theme-owned PHP array/helper in `inc/control-desk.php`.
- Entry fields: `label`, `status`, `surface`, `affects`, `anchor`, `preview_url`, `mobile_preview_url`, `next`.
- No database schema change.
- No new theme mods.
- No raw CSS textarea or arbitrary user-supplied styling.

invariant:
- Existing Brand Console, Homepage Studio, Journal Archive Studio, Reviews Archive Studio, Image Quality Console, and ownership-map panels remain functional.
- Public homepage, Journal, Reviews, Review single, and Oscars routes remain unchanged except for cache state.
- Admin links are escaped, labels are translatable, and any privileged actions remain guarded by existing nonce/capability handlers.
- The index must reduce hunting without duplicating save logic.

test:
- A focused PowerShell contract test confirms the command-index helper, renderer, required labels, anchors, preview links, and no-save/no-public-output constraints.
- PHP lint passes for changed theme PHP files.
- `git diff --check` passes.
- Admin render verification confirms the index appears on `Lunara > Theme Studio`.
- Public route smoke confirms no command-index/private leakage.

deferred:
- New Review single-page controls.
- Oscars dossier presentation controls.
- Saving preset bundles across multiple panels.
- Drag-and-drop route-control ordering.
- Bespoke theme extraction.

## Working Notes

The purpose is not more knobs for their own sake. The purpose is orientation: Dalton should be able to open Theme Studio and immediately understand which surfaces are controllable now, which public page proves the change, and what control frontier comes next.
