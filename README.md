# Lunara Theme Blocks

Active source for the Lunara Film WordPress theme.

## Paths

- Local source: `G:\lunara-backups\work\lunara-theme-blocks-20260513-2300`
- Live theme: `/home/151589083/htdocs/wp-content/themes/lunara-theme-blocks-20260513-2300`
- Website continuity workspace: `C:\Users\silve_i21do49\OneDrive\Desktop\New folder`

## Source Control Notes

This repository is intended to track the active theme source only. Local backup files, logs, release archives, browser evidence, and temporary QA artifacts are intentionally ignored.

Do not commit live-only secrets or generated WordPress/cache files. API keys should remain defined in server configuration or plugins, not in this theme repository.

## Technical Specification

- Living technical specification: `docs/tech-spec.md`

## Verification Baseline

Before pushing or deploying changes:

- Run PHP lint on changed PHP files.
- Confirm changed public routes return `200`.
- Flush cache after deploys.
- Save visual evidence under the Desktop workspace `10_VISUAL_EVIDENCE` folder, not inside this repository.
