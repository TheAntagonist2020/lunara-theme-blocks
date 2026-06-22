# Oscars Profile Image Controls Plan

## T1 Contract

Add a focused Theme Studio contract test for profile image chamber controls.

Acceptance:
- Test requires the new select and numeric controls.
- Test requires preset coverage, bounded ranges, public CSS variables, scoped selectors, and no raw CSS textarea.

## T2 Theme Studio Controls

Add the profile media treatment, width, and height controls to Oscars Dossier Studio.

Acceptance:
- Controls render through the existing select/number renderers.
- Save handler persists valid values and clamps numeric values through the existing bounded pipeline.
- Presets provide values for the new controls.

## T3 Public CSS

Emit scoped CSS variables and profile-image chamber rules.

Acceptance:
- CSS is scoped to `body.aat-shell-page .aat-profile-file`.
- Width and height values affect the profile media chamber.
- Treatment controls object-fit/aspect behavior without stretching art.
- Existing image focus continues to control object-position.

## T4 Verify And Ship

Run tests, lint, deploy only changed theme files, smoke public routes, capture evidence, update continuity, and commit/push the theme repo.

Acceptance:
- Local and remote hashes match.
- Public routes return `200`.
- No private/admin leakage.
- Evidence stays under `10_VISUAL_EVIDENCE`.
