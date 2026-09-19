# Theme 3.2.88 surface pass

Branch: `grok/surface-pass-3.2.88` (from main `300efbe` / live Theme 3.2.87).

`style.css` Version is still 3.2.87 on this first code commit. Bump the
header to `Version: 3.2.88` before merge so the canary can tell the
deploy from 3.2.87.

## Live baselines (2026-09-18, 780px probe)

- Home ~5,644px
- Oscars **25,082px**; Board **9,396px**; list 654px; columns `317px 317px`; cards 317×638
- Cause: 3.2.82 `--oscars-card-floor: 232px` forces 1–2 columns below ~1180px
- Reviews already compact from 3.2.84; leftover shell padding-top 76px

## What the overlay does

File: `assets/css/lunara-surface-pass.css`
Loader: `inc/surface-pass.php` at `wp_head` 1006
Hooked from `functions-loader.php` after `setup.php`

1. Home hero margin-bottom 20px; phone 16:10 frame + cover image
2. Oscars card floor 168px ≤1180px, 148px ≤540px
3. Feature poster fill; research landing header min-height 0
4. Reviews archive hero padding-top 28px

No PR opened. No deploy. Dalton's button.
