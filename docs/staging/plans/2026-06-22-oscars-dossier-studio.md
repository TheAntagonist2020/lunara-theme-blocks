# Oscars Dossier Studio Implementation Plan

Date: 2026-06-22
Spec: `docs/staging/specs/2026-06-22-oscars-dossier-studio.md`

## Summary

Build the first private Theme Studio control layer for Oscars route-family presentation. The MVP gives Dalton direct presets and bounded tuning controls for ceremony, category, title, and person dossier rhythm while leaving the Oscars plugin data model untouched.

## Tasks

- [x] T1: Add the failing Oscars Dossier Studio contract
  - Files:
    - `tests/theme-studio-oscars-dossier-controls.ps1`
  - Acceptance:
    - The test fails before production changes.
    - The test asserts the Theme Studio panel marker, expected control names, preset names, preview links, and public CSS hooks.

- [x] T2: Add private Studio controls and presets
  - Files:
    - `inc/control-desk.php`
    - `assets/css/lunara-control-desk.css`
  - Acceptance:
    - `Oscars Dossier Studio` renders inside `Lunara > Theme Studio`.
    - Controls are capability-protected.
    - Values save through nonce-protected POST.
    - Invalid values clamp to the allowed contract.
    - Preset buttons apply coherent grouped values.
    - Preview links are visible for ceremony, category, title, person, and Oscars portal samples.

- [x] T3: Add scoped public CSS and admin-only preview overrides
  - Files:
    - `inc/frontend.php`
    - `tests/theme-studio-oscars-dossier-controls.ps1`
  - Acceptance:
    - Theme emits bounded Oscars dossier CSS only on Oscars route-family surfaces.
    - Anonymous users cannot activate unsaved preview query overrides.
    - Capable admins can preview preset values without saving.
    - Non-Oscars pages receive no Oscars Studio CSS.

- [x] T4: Local verification and review
  - Files:
    - changed theme files only
  - Acceptance:
    - PHP lint passes for changed PHP files.
    - Focused contract passes.
    - `git diff --check` passes.
    - Review confirms the implementation matches the spec, avoids unrelated churn, and introduces no public/private leakage risk.

- [x] T5: Deploy, QA, and preserve continuity
  - Files:
    - changed runtime theme files
    - `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\LUNARA_WORLD_CHANGELOG.md`
    - active session log under `09_DOCS_AND_NOTES`
    - `09_DOCS_AND_NOTES\LUNARA_WEBSITE_HANDOFF.md`
  - Acceptance:
    - Back up changed live files before deploy.
    - Deploy only changed files.
    - Remote PHP lint passes.
    - Local and remote SHA256 hashes match.
    - WordPress cache is flushed.
    - Public route smoke passes.
    - Responsive visual QA at 390, 768, and 1280 passes for representative Oscars routes.
    - Evidence is stored only under `10_VISUAL_EVIDENCE`.
    - Theme repo is committed and pushed after verification.

## Suggested Commit

`Add Oscars Dossier Studio controls`
