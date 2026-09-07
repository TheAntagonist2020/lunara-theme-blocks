# Independent homepage carousels

Approved implementation: the circled homepage hero and Journal lane become
independently configurable under Site Studio > Homepage. Preserve Lunara's
cinematic styling and leave Lunara Method and Oscars composition outside scope.

## Behavior

- Hero: one large story, published Review + Journal sources. Journal: all Journal
  types only, three cards desktop, two tablet, one mobile.
- Independent Automatic / Manual modes. Automatic selects six newest published,
  non-password-protected sources by publication date, ignoring legacy feature
  ordering. Manual allows search, add, remove, drag and keyboard order controls.
  Changing mode retains the manual list.
- Heading, autoplay, interval (default seven seconds, bounded three to thirty).
  Manual items allow image attachment, focal point, zoom, full-frame/cover,
  headline, excerpt, kicker and CTA overrides without editing source content.
  Existing global/per-item Hero overlay controls remain available for the hero.
- Private desktop/tablet/mobile Preview, then explicit Apply. Hero extends the
  existing Hero Command option; Journal has a separate canonical option. Reads
  never adopt new behavior. Prior presentation stays active until Apply. Exact
  private revision restore can return to the pre-adoption state.
- Empty manual hides; unavailable selected items remain visible with a warning
  in the editor but are skipped publicly. Single items stay static. Missing and
  failed artwork gets a consistent placeholder. Published dates appear on news.
- Arrows, swipe, focused keyboard navigation and visible pause/play; hover/focus
  pause, reduced-motion support. Without JavaScript the first hero and every
  Journal card remain readable. No new slider library or persistent public cache.

## Implementation and review

New public settings/delivery modules connect through the existing live renderer
entrypoints. The same adopted hero data feeds rendering and LCP preloads,
including empty/single cases. Dedicated carousel inspectors use the established
Site Studio capability/nonce guards, private preview storage and revision APIs.
Retire old carousel-only controls after adoption; clarify shared feature flags.

Verify settings independence, source eligibility, exact ordering, retained manual
lists, overrides, media selection, empty/single/multiple cases, private preview
isolation/expiry, rejected save retention and pre-adoption restore. Verify real
rendered HTML and Splide at 1440/820/390 widths, equal news-card height, navigation,
pause, reduced motion, missing images and JS failure. Run the complete repository
contracts and syntax checks, plus mutations that prove the key regressions fail.

## Delivery

Topic branch: codex/home-carousels-3.2.62. No production settings are changed by
the implementation session. Follow the repository PR convention; Dalton deploys
main manually through Deployer for Git. After deployment, verify the actual
homepage and run tests/tools/lunara-canary-verify.sh 3.2.62. Rebuild and verify
the tree-exact rollback hatch after each main merge. Then curate the opening
hero and Journal lineups; publishing fresh coverage remains an editorial task.
