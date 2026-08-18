$ErrorActionPreference = 'Stop'

function Assert-True {
    param([bool] $Condition, [string] $Message)
    if (-not $Condition) { throw $Message }
}

$root = Split-Path -Parent $PSScriptRoot
$frontendPath = Join-Path $root 'inc/frontend.php'
$assetPath = Join-Path $root 'assets/css/lunara-review-single.css'
$stylePath = Join-Path $root 'style.css'

Assert-True (Test-Path $assetPath) 'The cacheable Review single asset is missing.'

$frontend = Get-Content -LiteralPath $frontendPath -Raw
$asset = Get-Content -LiteralPath $assetPath -Raw
$style = Get-Content -LiteralPath $stylePath -Raw
$assetBytes = [Text.Encoding]::UTF8.GetByteCount($asset)
$frontendBytes = [Text.Encoding]::UTF8.GetByteCount($frontend)

Assert-True ($style -match 'Version:\s*3\.2\.51') 'Theme version must preserve the Reviews Archive route extraction.'
Assert-True ($assetBytes -le 77824) "Review route asset exceeds its 76 KB transition budget: $assetBytes bytes."
Assert-True ($frontendBytes -le 345000) "frontend.php still carries excessive static delivery code: $frontendBytes bytes."
Assert-True ($asset -notmatch '<\?php|<style|</style>') 'The Review route asset must contain CSS only.'

$moved = [ordered]@{
    'lunara-review-debrief-polish-css' = 'lunara_output_review_debrief_polish_css'
    'lunara-review-reader-spine-css' = 'lunara_output_review_reader_spine_css'
    'lunara-review-desktop-editorial-repair-css' = 'lunara_output_review_desktop_editorial_repair_css'
    'lunara-review-mobile-editorial-repair-css' = 'lunara_output_review_mobile_editorial_repair_css'
    'lunara-review-pair-it-with-polish-css' = 'lunara_output_review_pair_it_with_polish_css'
    'lunara-review-spoiler-bridge-css' = 'lunara_output_review_spoiler_bridge_css'
    'lunara-review-share-strip-css' = 'lunara_output_review_share_strip_css'
    'lunara-review-related-retention-css' = 'lunara_output_review_related_retention_css'
    'lunara-review-full-scroll-rhythm-css' = 'lunara_output_review_full_scroll_rhythm_css'
}

foreach ($id in $moved.Keys) {
    $function = $moved[$id]
    Assert-True ($asset -match [regex]::Escape($id)) "The Review route asset is missing $id."
    Assert-True ($frontend -notmatch ('<style id="' + [regex]::Escape($id) + '"')) "Static inline style remains: $id."
    Assert-True ($frontend -notmatch ('function\s+' + [regex]::Escape($function) + '\s*\(')) "Retired emitter remains: $function."
    Assert-True ($frontend -notmatch ("add_action\(\s*'wp_head'\s*,\s*'" + [regex]::Escape($function) + "'")) "Retired wp_head hook remains: $function."
}

foreach ($dynamicId in @(
    'lunara-review-card-image-focus-vars',
    'lunara-pair-it-with-vars',
    'lunara-review-single-studio-css',
    'lunara-review-pair-it-with-controls-css'
)) {
    Assert-True ($frontend -match ('<style id="' + [regex]::Escape($dynamicId) + '"')) "Request-specific Review style was incorrectly removed: $dynamicId."
}

Assert-True ($frontend -match "function\s+lunara_enqueue_review_single_styles\s*\(\)") 'Review route enqueue function is missing.'
Assert-True ($frontend -match "is_singular\(\s*'review'\s*\)") 'Review route asset must be singular-Review scoped.'
Assert-True ($frontend -match "lunara_resolve_theme_asset\(\s*'assets/css/lunara-review-single\.css'\s*\)") 'Review route asset must use the theme asset resolver.'
Assert-True ($frontend -match "'lunara-review-components'\s*,\s*'lunara-shell'") 'Review route asset must load after components and the shared shell.'
Assert-True ($frontend -match "add_action\(\s*'wp_enqueue_scripts'\s*,\s*'lunara_enqueue_review_single_styles'\s*,\s*110\s*\)") 'Review route asset must enqueue after the shared shell.'
Assert-True ($frontend -match "rocket_rucss_external_exclusions[\s\S]*?lunara-review-single\.css") 'WP Rocket must preserve the complete Review route asset.'

Write-Host "Review route CSS extraction contract passed ($assetBytes cacheable CSS bytes; $frontendBytes frontend.php bytes)."
