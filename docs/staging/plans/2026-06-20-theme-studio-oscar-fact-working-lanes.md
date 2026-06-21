# Theme Studio Oscar Fact Working Lanes Plan

## T1: Contract

Goal: Describe and protect the Oscar Fact state filter before production code.

Files:

- `docs/staging/specs/2026-06-20-theme-studio-oscar-fact-working-lanes.md`
- `tests/theme-studio-oscar-fact-working-lanes.ps1`

Acceptance:

- The test fails before implementation because `lcd_iq_fact_state` and its UI labels do not exist.

## T2: Filter Model

Goal: Add a sanitized `fact_state` dimension to Image Quality Console filtering.

Files:

- `inc/control-desk.php`

Acceptance:

- `verified`, `unverified`, and `needs-image` are accepted values.
- Oscar Fact state filtering is derived from `attachment_id` and `visual_verified`.
- Non-Oscar rows are excluded when a fact state is selected.

## T3: Admin UI

Goal: Render the working lane inside Theme Studio.

Files:

- `inc/control-desk.php`

Acceptance:

- The filter panel includes `Oscar Fact state`.
- The priority rail includes `Verified facts`, `Unverified facts`, and `Needs image`.
- Filter URLs include `lcd_iq_fact_state` only when needed.

## T4: Verify

Goal: Prove the change is clean and does not disrupt public surfaces.

Acceptance:

- Contract test passes.
- PHP lint passes.
- `git diff --check` passes.
- Public route smoke checks remain clean after deploy.
