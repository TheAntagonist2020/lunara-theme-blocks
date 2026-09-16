# Surface pass 2026-09-16

Branch: `grok/surface-pass-home-oscars-reviews`
Theme version unchanged: 3.2.80
Deploy: Dalton only.

Hook the sheet from `inc/setup.php` inside `lunara_print_public_guardrail_styles()`:

```php
lunara_print_cacheable_stylesheet( 'lunara-surface-pass', 'assets/css/lunara-surface-pass.css' );
```

That line is included in this branch's `inc/setup.php` when that file is committed alongside this note.

What it does:
- Home phone hero keeps a 16:9 image well instead of height 0.
- Home desktop hero min-height/margin tightened.
- Reviews archive hero padding-top 76px to 28px.
- Oscars feature posters keep a 2:3 well; default research landing header compacted.
