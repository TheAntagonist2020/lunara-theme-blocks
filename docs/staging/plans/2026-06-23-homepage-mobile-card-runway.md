# Homepage Mobile Card Runway Plan

spec: `docs/staging/specs/2026-06-23-homepage-mobile-card-runway.md`

- [x] T1: Add mobile card-runway contract
  goal: Guard the mobile-only homepage Reviews rail compaction and the preserved ordering contracts.
  files: `tests/homepage-mobile-card-runway.ps1`
  acceptance: `powershell -ExecutionPolicy Bypass -File tests\homepage-mobile-card-runway.ps1`
  spec: `docs/staging/specs/2026-06-23-homepage-mobile-card-runway.md#homepage-mobile-card-runway`

- [x] T2: Ship mobile Reviews rail compaction
  goal: Add scoped homepage-only CSS that compacts Latest Reviews cards below 820px without changing content, desktop layout, or the Journal-first mobile order.
  files: `inc/frontend.php`
  acceptance: focused contract plus PHP lint and homepage regression contracts pass.
  spec: `docs/staging/specs/2026-06-23-homepage-mobile-card-runway.md#homepage-mobile-card-runway`

- [x] T3: Deploy, verify, and preserve continuity
  goal: Deploy only changed runtime files, verify public behavior, capture evidence, update continuity docs, and push the theme repo.
  files: `LUNARA_WORLD_CHANGELOG.md`, `09_DOCS_AND_NOTES/LUNARA_WEBSITE_HANDOFF.md`, `09_DOCS_AND_NOTES/SESSION-LOG-2026-06-23.md`
  acceptance: live route smoke, remote lint, matching SHA256 hashes, responsive evidence, cache flush, continuity updates, and clean pushed repo.
  spec: `docs/staging/specs/2026-06-23-homepage-mobile-card-runway.md#homepage-mobile-card-runway`
