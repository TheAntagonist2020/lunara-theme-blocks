$ErrorActionPreference = 'Stop'

$themeRoot = Split-Path -Parent $PSScriptRoot

function Assert-True {
    param([bool] $Condition, [string] $Message)
    if (-not $Condition) { throw $Message }
}

$mediaPath = Join-Path $themeRoot 'inc\journal-archive-media.php'
$templatePath = Join-Path $themeRoot 'archive-journal.php'
$loaderPath = Join-Path $themeRoot 'functions-loader.php'
$payloadToolPath = Join-Path $PSScriptRoot 'tools\lunara-journal-payload-gate.js'
$routeCssPath = Join-Path $themeRoot 'assets\css\lunara-journal-archive.css'
$criticalPath = Join-Path $themeRoot 'inc\journal-archive-critical.php'

Assert-True (Test-Path -LiteralPath $mediaPath) 'Missing route-scoped Journal responsive media module.'
Assert-True (Test-Path -LiteralPath $payloadToolPath) 'Missing Journal payload normalization tool.'

$media = Get-Content -Raw -LiteralPath $mediaPath
$template = Get-Content -Raw -LiteralPath $templatePath
$loader = Get-Content -Raw -LiteralPath $loaderPath
$payloadTool = Get-Content -Raw -LiteralPath $payloadToolPath
$routeCss = Get-Content -Raw -LiteralPath $routeCssPath
$critical = Get-Content -Raw -LiteralPath $criticalPath

Assert-True ($loader -match 'require_once\s+\$lunara_inc\s*\.\s*''journal-archive-media\.php''') 'The split loader must load the Journal responsive media owner.'
Assert-True ($media -match 'wp_get_attachment_image\(\s*\$attachment_id\s*,\s*''lunara-hero-spotlight''') 'Journal cards must preserve the existing route-size native markup first.'
Assert-True ($media -match 'wp_get_attachment_image\(\s*\$attachment_id\s*,\s*''full''') 'Missing route srcsets must probe uncropped native responsive markup before CDN fallback.'
Assert-True ($media -match 'function\s+lunara_journal_archive_card_image_markup') 'Missing Journal card responsive renderer.'
Assert-True ($media -match 'function\s+lunara_journal_archive_card_is_visual_lead') 'Missing canonical visual-lead resolver.'
Assert-True ($media -match 'LUNARA_JOURNAL_ARCHIVE_MIN_SOURCE_WIDTH''\s*,\s*768') 'Journal archive media must document the 768px no-upscale boundary.'
Assert-True ($media -match 'jetpack_photon_url') 'Missing bounded WordPress.com Image CDN fallback.'
Assert-True ($media -match 'array\(\s*320\s*,\s*480\s*,\s*768\s*,\s*1200\s*,\s*1600\s*,\s*1920\s*\)') 'Journal CDN fallback widths must remain explicit and bounded.'
Assert-True ($media -match '''w''\s*=>\s*\$width' -and $media -notmatch '''resize''\s*=>|''fit''\s*=>|''h''\s*=>') 'Journal fallback must use width-only WordPress.com CDN requests that preserve source aspect.'
Assert-True ($media -notmatch 'wp_generate_attachment_metadata|wp_update_attachment_metadata|media_handle|download_url|image\.tmdb\.org') 'Public Journal rendering must never regenerate metadata, write media state, download images, or guess remote-provider derivatives.'
Assert-True ($template -match 'lunara_journal_archive_card_is_visual_lead\(\s*\$journal_card_index\s*\)') 'The archive template must resolve visual-lead state through the route-aware owner.'
Assert-True ($template -match 'lunara_journal_archive_card_image_attributes\(\s*\$is_visual_lead\s*,') 'The archive template must derive loading priority from canonical visual-lead state.'
Assert-True ($template -match 'lunara_journal_archive_card_image_markup\(\s*\$featured_id\s*,') 'The archive template must use the responsive media owner.'
Assert-True ($template -match '\$entry_kicker\s*=\s*\$is_visual_lead\s*\?\s*\$journal_labels\[''lead_kicker''\]\s*:\s*\$journal_labels\[''card_kicker''\]') 'Lead versus regular kicker must use canonical visual-lead state.'
Assert-True ($template -match '\$is_visual_lead\s*\?\s*'' is-lead''\s*:\s*''''') 'The is-lead class must use canonical visual-lead state.'
Assert-True ($template -notmatch '1\s*===\s*\$journal_card_index') 'No template-local first-card branch may bypass route-aware visual-lead state.'
Assert-True ($routeCss -match '#primary\.lunara-journal-archive-page\s+\.lunara-journal-archive-card\.is-lead\s+\.lunara-review-grid-poster\s*\{[^}]*opacity:\s*1\s*!important[^}]*transition:\s*none\s*!important') 'The page-one visual lead must paint synchronously without waiting for the shared image-reveal runtime.'
Assert-True ($routeCss -notmatch '#primary\.lunara-journal-archive-page\s+\.lunara-journal-archive-card\s+\.lunara-review-grid-poster\s*\{[^}]*opacity:\s*1') 'The synchronous first-paint exemption must not leak to ordinary Journal cards.'
Assert-True ($critical -match '#primary\.lunara-journal-archive-page\s+\.lunara-journal-archive-card\.is-lead\s+\.lunara-review-grid-poster\{opacity:1!important;transition:none!important\}') 'The critical seed must keep the page-one lead visible even before the route stylesheet settles.'
Assert-True ($payloadTool -match 'JOURNAL_PRODUCTION_HTML_MAX_BYTES\s*=\s*118000') 'Production Journal decoded HTML minus exactly one measured Boost critical block must retain the 118,000-byte cap.'
Assert-True ($payloadTool -match 'JOURNAL_PRODUCTION_TOTAL_MAX_BYTES\s*=\s*190000') 'Production Journal decoded HTML must retain the absolute 190,000-byte total cap.'
Assert-True ($payloadTool -match 'jetpack-boost-critical-css') 'Production payload normalization may identify only the exact Boost critical style block.'
Assert-True ($payloadTool -match 'JOURNAL_STAGING_MAX_DELTA_PCT\s*=\s*5') 'Matched staging HTML may regress by no more than five percent.'
Assert-True ($payloadTool -match 'wpcomstaging\.com') 'Staging normalization must be explicit to WordPress.com staging origins.'
Assert-True ($payloadTool -match 'global-styles-inline-css' -and $payloadTool -match 'gutenkit-frontend-common-inline-css') 'Payload measurement must inventory environment-owned inline blocks.'
Assert-True ($payloadTool -match 'response\.url') 'Payload measurement must validate the final response URL after redirects.'

$phpRuntime = & php (Join-Path $PSScriptRoot 'journal-archive-responsive-runtime.php') 2>&1
if ($LASTEXITCODE -ne 0) { throw ($phpRuntime -join "`n") }
$noPhotonRuntime = & php (Join-Path $PSScriptRoot 'journal-archive-no-photon-runtime.php') 2>&1
if ($LASTEXITCODE -ne 0) { throw ($noPhotonRuntime -join "`n") }
$nodeRuntime = & node (Join-Path $PSScriptRoot 'journal-archive-payload-gate-runtime.js') 2>&1
if ($LASTEXITCODE -ne 0) { throw ($nodeRuntime -join "`n") }

Write-Host ($phpRuntime -join "`n")
Write-Host ($noPhotonRuntime -join "`n")
Write-Host ($nodeRuntime -join "`n")
Write-Host 'Journal archive responsive delivery contract passed.'
