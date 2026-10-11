# Theme 3.2.88 hotfix

PR #208 merged and WordPress.com deployed `60bc799` correctly.
The overlay file was on disk. It was not in the public style queue.
Live `/oscars/` stayed at 25,082px / Board 9,396px / `--oscars-card-floor: 232px`.
`style.css` on that commit still said `Version: 3.2.87`.

This branch:

- Enqueues `lunara-surface-pass` on `wp_enqueue_scripts` at 120
- Keeps the `wp_head` 1006 reprint if the queue has not already printed it
- Adds body class `lunara-surface-pass-3288` as the live canary

After merge and deploy, body must contain `lunara-surface-pass-3288` and
`--oscars-card-floor` at 780px must be `168px`, not `232px`.
