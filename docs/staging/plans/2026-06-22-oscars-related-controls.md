# Oscars Related Controls Plan

## T1 Contract

Add spec and tests for new Oscars Dossier Studio controls.

Acceptance:
- Theme test requires the select and numeric controls, preset values, preview support, and public CSS variables.
- Plugin test requires a bounded related-review count helper, treatment class hooks, and version `2.7.31`.

## T2 Theme Controls

Add the new controls to Oscars Dossier Studio.

Acceptance:
- Controls render in Theme Studio.
- Save handler persists valid values and clamps numeric values.
- Presets provide defaults for the new controls.

## T3 Public CSS

Emit scoped CSS for related-review treatment and image focus.

Acceptance:
- CSS is scoped to `body.aat-shell-page`.
- `object-position` uses the selected focus.
- Related-review treatments preserve no-overflow behavior.

## T4 Plugin Runtime

Make the Oscars plugin obey the related-review count and treatment values.

Acceptance:
- Ceremony, category, and entity profile related-review sections use the bounded count.
- Related-review section/grid gets a safe treatment class.
- Plugin header, constant, README, and readme report `2.7.31`.

## T5 Verify And Ship

Run local tests, lint, deploy only changed files, smoke public routes, update continuity, and commit/push both repos.

Acceptance:
- Local and remote hashes match.
- Public routes return `200`.
- No private/admin leakage.
- Evidence stays under `10_VISUAL_EVIDENCE`.

