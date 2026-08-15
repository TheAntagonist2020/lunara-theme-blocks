# Lunara Site Studio v1 Release Plan

## Release identity

- Repository: `TheAntagonist2020/lunara-theme-blocks`
- Branch: `codex/archive-studio-northstar-3.2.37`
- Theme: `3.2.37`
- Scope: theme only; admin/editor workflow
- Deployment: none from this implementation branch

## Implementation sequence

1. Register an admin-only Site Studio submenu with strict surface routing and capability checks.
2. Extract the existing Lunara Method form into a shared renderer without changing its theme-mod keys or validation.
3. Reuse the Homepage Structure, Reviews Archive, and Journal Archive handlers with bounded return contexts.
4. Derive the Homepage Structure view from the canonical six-section registry, showing structure first and linking advanced tuning back to Control Desk.
5. Make homepage composition writes preserve noncanonical blocks, canonical attributes, and deterministic order.
6. Consolidate homepage block registration in `assets/js/lunara-blocks.js`; retire the duplicate inline registrar.
7. Replace six full server previews with compact editor cards, preserving Hero and Latest Reviews Inspector controls.
8. Add a front-page-only Lunara Studio callout and editor-only configuration/styles.
9. Run targeted contracts, all PowerShell contracts, PHP lint, JavaScript syntax checks, CSS balance checks, and `git diff --check`.

## Staging verification

After merge and a manual theme-only staging deployment:

1. Confirm `style.css` reports `3.2.37`.
2. Open `Lunara > Site Studio`; verify each navigation item renders only its selected surface.
3. Confirm Lunara Method shows the effective fallback wording when the stored text mods are blank, without creating theme mods on view.
4. Save one reversible Method copy change, verify the homepage, then restore it.
5. Open Home post `4055`; before saving, confirm its stored content hash remains `18e8be71` and its six block attributes remain unchanged.
6. Confirm each homepage block appears as a compact card, Hero image/excerpt controls work, no public section imagery loads in the editor canvas, and no horizontal overflow occurs.
7. Save a harmless Homepage Structure order change only after capturing content; verify any noncanonical block remains present and restore the original order.
8. Save Reviews and Journal archive settings separately and confirm each returns to its corresponding Site Studio view.
9. Smoke-test Home, Reviews, Journal, Oscars, Search, and one Review single for unchanged public geometry, fonts, navigation, console state, and HTTP status.

## Rollback

Redeploy the previous successful theme commit if Site Studio cannot save, homepage content changes unexpectedly, Inspector controls regress, or public output shifts. Because 3.2.37 adds no schema migration and reuses existing storage, rollback requires no data cleanup.

## Follow-up

After the staging gate passes, measure the Home editor against the captured baseline (32,813px canvas height, 2,792px width, 30 images, and about 11.8MB decoded resources). Then schedule Archive Studio v2 only for controls that cannot be safely expressed by the reused archive forms.
