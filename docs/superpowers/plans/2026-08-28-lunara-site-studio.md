# Lunara Site Studio Implementation Plan

**Spec authority:** Dalton's approved Site Studio plan in the 2026-08-28 Codex task.

## Global Constraints

- Preserve Lunara Film's current public visual identity; this is a control, responsiveness, accessibility, and reliability project rather than a brand redesign.
- Site Studio is the primary plain-language navigation and presentation/curation shell. Existing theme mods, options, post content, post meta, and plugin repositories remain canonical storage.
- The approved interface is a Visual Site Map plus search, a public-order section rail, a real same-origin responsive preview, and a contextual inspector. Essentials is open; Mobile, Fine Tune, Advanced, and Revision History are progressive disclosures.
- Unsaved state stays local until Preview Changes or Save Live. Preview is owner-bound, allowlisted, private/no-store/noindex, and expires after 30 minutes. There is no persistent draft or autosave system.
- Ordinary Save Live actions publish without confirmation. Removal, restoration, and destructive operations require confirmation.
- Operational plugin tasks use guided handoffs with redacted status; credentials, workflows, records, and destructive actions stay in their owning plugins.
- Preserve existing Site Studio surface IDs and `lunara_site_studio_admin_url()` bookmarks. Missing dependencies render an unavailable state, never a fatal or blank screen.
- Design surfaces retain `edit_theme_options`; plugin operations retain their stricter capability. Never expose or copy secrets.
- Keep Customizer and purpose-built plugin tools functional as Classic/fallback paths. Convert duplicate Control Desk design forms to summaries and deep links only after their Site Studio replacement is verified.
- Twelve revisions per migrated surface. Restore revalidates and snapshots the state it replaces. Homepage snapshots are atomic across theme mods and synchronized front-page block content.
- Never clear the whole-domain cache, deploy, push to `main`, open a PR unless asked, or commit licensed Klim fonts. Plugin compatibility deploys precede theme consumption. Dalton alone deploys through Deployer for Git.
- Use strict TDD for each behavior change: observe the new test fail for the intended reason, implement minimally, observe it pass, then run affected regression suites and mutation checks.

## Task 1: Make the Local Baseline Portable

**Release:** test infrastructure, no public version change.

- Replace the Unix-only child-process environment-prefix in `tests/oscars-portal-studio-runtime.php` with a cross-platform process launch that passes `LUNARA_OSCARS_PORTAL_MODE` through the child environment without shell interpolation.
- Use the current failing `oscars-portal-studio-contract.ps1` run as RED, then prove all accessors/degraded/no-plugin lanes pass on Windows and retain Linux CI compatibility.
- Run the remaining 82-contract baseline plus PHP syntax, JavaScript syntax, CSS balance, and `git diff --check`.
- Do not alter production behavior in this task.

## Task 2: Ship Theme 3.2.54 Public Stabilization

**Release:** `3.2.54` on `codex/site-studio-public-stabilization-3.2.54`.

- Add behavior-first route contracts for 390px overflow, valid landmark structure, and accessible Oscars media links; confirm each fails against 3.2.53 for the expected reason.
- Correct the responsible Home, Reviews, Journal, and Oscars grid/flex/component constraints. Do not hide global overflow or clip important actions.
- Remove nested `<main>` landmarks while preserving canonical route roots and canary identity markers.
- Give every rendered Oscars media anchor an accessible name derived from canonical title/context without inventing visible copy.
- Retain the current visual design and route data contracts. Establish 390, 430, 768, 782, and 1440 browser baselines.
- Measure route TTFB/LCP/media delivery read-only; change only verified bottlenecks and record anything not fixed.
- Remove `rocket_clean_domain()` from Design Tokens and replace visible cache-flush guidance with the canonical no-cache-clearing rule.
- Update release identity, changelog, and session log. Run targeted RED/GREEN checks, all 82 contracts, syntax/balance/diff gates, and deliberate mutations.

## Task 3: Build the 3.2.55 Registry, Adapter, and Admin API Foundation

**Release:** `3.2.55`, stacked only after Task 2 review is clean.

- Write failing runtime contracts for normalized registry entries, per-surface capability, dependency degradation, bookmark compatibility, unique ownership, callable adapters, redacted status, and selected-surface-only loading.
- Normalize `lunara_site_studio_surfaces()` and apply the `lunara_site_studio_surfaces` filter. Entries declare id, group, label, description, aliases, owner, kind, capability, preview support/route, adapter or admin URL, dependency/status callbacks, danger level, sections, and Classic fallback.
- Add `Lunara_Site_Studio_Surface_Adapter` for canonical read, validate, save, preview, revision listing, and restore.
- Add authenticated `lunara-site-studio/v1` registry/state/preview/save/revisions/restore endpoints with cookie authentication, REST nonce, per-surface capability, field errors, and redacted output.
- Generalize the hardened owner-bound preview and twelve-revision patterns without introducing duplicate public settings.
- Make Design Tokens the sole CSS-variable emitter; Customizer continues writing compatible theme mods but stops emitting competing variables.

## Task 4: Build the 3.2.55 Visual Site Map and Global/Home Pilot

- Write failing admin browser/runtime contracts for search aliases, responsive iframe widths, dirty/discard state, progressive disclosure, keyboard/focus behavior, no overflow, status announcements, and click-to-select section messaging.
- Build a server-rendered Site Studio shell with dedicated CSS and JavaScript rather than extending the Control Desk monolith.
- Implement the approved Visual Site Map, lightweight lazy thumbnails, plain-language search, public-order section rail, actual same-origin preview, contextual inspector, and desktop/tablet/390 controls.
- Pilot Global Design, Homepage Structure, and Lunara Method through the adapter/API foundation.
- Snapshot and restore Homepage theme mods plus synchronized front-page block content as one transaction.
- Preserve existing handlers and Classic links until pilot verification succeeds.

## Task 5: Migrate 3.2.56 Editorial and Utility Presentation Surfaces

**Release:** `3.2.56`.

- Migrate Reviews Archive, Review Single, Journal Archive, and Utility Search into the shared workspace.
- Derive section vocabulary/order from canonical helper registries; do not duplicate section definitions.
- Reuse Reviews and Journal hardened preview/history providers. Wrap Review Single and Utility Search with the shared adapter and preview/revision services.
- Add a guided Core Review Studio handoff for per-review identity, score, Debrief, and image-slot work rather than duplicating those fields.
- Preserve Customizer and existing Control Desk routes as functional fallback paths.

## Task 6: Clarify Journal Foundation and Dispatch Ownership

**Repositories:** Journal Foundation first, Dispatch second, theme consumption last.

- Add tests proving Journal Foundation remains the effective workflow configuration owner and that status/config responses are redacted.
- Replace the raw Journal source JSON editor with labeled rows for enabled, label, URL, max items, and priority while continuing to save through the existing immutable-version repository and schema validator.
- When Foundation is active, make Dispatch legacy schedule/provider/prompt/source fields read-only and link to the authoritative workflow tool.
- Dispatch continues to own worker health, next/last run, run history, Run Now, Reset Seen, visual assignment, and provider secrets.
- Add guided Site Studio destinations for Journal Workflow and Journal Automation without copying configuration into theme storage.

## Task 7: Add 3.2.57 Plugin Status APIs and IMDb Persistence

**Repositories:** plugin additions first; theme consumption follows in Task 8.

- IMDb Guard: write failing migration/read/status tests, move the title map into non-autoloaded plugin-owned option storage, import the existing JSON once without data loss, expose a stable read API, retain a compatibility fallback during rollout, and expose redacted identity/artwork health.
- AI Assistant Classic: expose redacted configured provider/model, enabled editorial surfaces, and key-presence status without returning secrets.
- Core: expose redacted Review/artwork health and the canonical Carousel manager URL; keep per-review editing plugin-owned.
- Oscars Ledger: expose stable status/DTO helpers and canonical admin URLs for Tracker, Write-Ups, artwork coverage, and entity integrity; do not expose destructive operations as ordinary actions.
- Journal Foundation and Dispatch contribute their redacted status and canonical tool URLs through the registry filter.

## Task 8: Complete 3.2.57 Oscars, Guided Handoffs, and System Health

**Release:** `3.2.57`.

- Migrate Oscars Portal and dossier presentation controls into the shared workspace, retaining their existing canonical validation/history providers.
- Consume plugin status/read APIs instead of adding direct option, file, secret, or database reads.
- Add guided destinations for Tracker, Write-Ups, artwork coverage, entity integrity, automation health, Editorial Assistant, Core Carousel, and Review Identity & Artwork.
- Add System Health for dependency availability, ownership conflicts, preview failures, and redacted integrity/coverage summaries. Include no cache-clear control.
- Segregate imports, deletion, repair, teardown, key rotation, publishing, and bulk identity correction behind owning-plugin confirmations and capabilities.
- Convert duplicate Control Desk Theme Studio forms into current-state summaries and authoritative links after each replacement surface passes browser/runtime verification.

## Task 9: Cross-Repository Verification and Release Handoff

- Add the portable authenticated Playwright admin gate and invoke it from a PowerShell contract using pinned `playwright-core`. Cover 390, 430, 768, 782, and 1440 widths, labels/IDs/disclosures, keyboard/focus, 44px targets, live-region announcements, search, preview preservation, selected-surface asset isolation, and serious/critical accessibility violations.
- Run targeted suites for every repository, all 82 theme contracts, PHP syntax, JavaScript syntax, CSS balance, `git diff --check`, and deliberate mutations for ownership, capability, nonce, token, no-store, route, validation, and overflow guards.
- Independently review every task diff and perform a final whole-branch/cross-repository review. Resolve Critical/Important findings before handoff.
- Update theme changelog/session log with every repo SHA and every gate run or omitted. Do not claim live verification because no deployment occurs in this session.
- Leave reviewed topic branches and exact manual deployment order for Dalton. Do not push, open PRs, merge, deploy, clear caches, or run write probes against production.
