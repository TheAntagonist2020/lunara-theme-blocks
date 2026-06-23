# Homepage Journal Mobile Runway Plan

spec: `docs/staging/specs/2026-06-23-homepage-journal-mobile-runway.md`

- [x] T1: Add Journal mobile runway contract
  goal: Guard the mobile-only homepage Journal lane compaction and preserved ordering contracts.
  files: `tests/homepage-journal-mobile-runway.ps1`
  acceptance: `powershell -ExecutionPolicy Bypass -File tests\homepage-journal-mobile-runway.ps1`
  spec: `docs/staging/specs/2026-06-23-homepage-journal-mobile-runway.md#homepage-journal-mobile-runway`

- [x] T2: Ship mobile Journal lane compaction
  goal: Add scoped homepage-only CSS that compacts Journal cards below `820px` without changing content, desktop layout, or the Journal-first mobile order.
  files: `inc/frontend.php`
  acceptance: focused contract plus PHP lint and homepage regression contracts pass.
  spec: `docs/staging/specs/2026-06-23-homepage-journal-mobile-runway.md#homepage-journal-mobile-runway`

- [ ] T3: Deploy, verify, and preserve continuity
  goal: Deploy only changed runtime files, verify public behavior, capture evidence, update continuity docs, and push the theme repo.
  files: `LUNARA_WORLD_CHANGELOG.md`, `09_DOCS_AND_NOTES/LUNARA_WEBSITE_HANDOFF.md`, `09_DOCS_AND_NOTES/SESSION-LOG-2026-06-23.md`
  acceptance: live route smoke, remote lint, matching SHA256 hashes, responsive evidence, cache flush, continuity updates, and clean pushed repo.
  spec: `docs/staging/specs/2026-06-23-homepage-journal-mobile-runway.md#homepage-journal-mobile-runway`
