# Review Card Media Guards Plan

## Steps

1. Add a focused contract test for media-state review cards.
2. Patch the monolithic fallback review shortcode renderer.
3. Patch the legacy `inc/shortcodes-home.php` review shortcode renderer.
4. Confirm the active homepage renderer remains protected.
5. Run lint, tests, and diff checks.
6. Deploy only changed files, flush cache, and capture responsive evidence.
7. Update continuity docs and commit/push the theme repo.

## Acceptance

- No renderer prints a review-card poster wrapper unless real image HTML or a fallback background URL exists.
- No-media review cards include `has-no-visual` or `is-text-led`.
- No-media score badges render inline, not as floating badges inside missing media.
- Existing media cards keep their `3:4` image chamber behavior.
