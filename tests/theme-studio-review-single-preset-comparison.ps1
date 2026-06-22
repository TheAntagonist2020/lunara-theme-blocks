$ErrorActionPreference = 'Stop'

$themeRoot = Split-Path -Parent $PSScriptRoot

function Assert-True {
    param(
        [bool] $Condition,
        [string] $Message
    )

    if (-not $Condition) {
        throw $Message
    }
}

function Read-ThemeFile {
    param([string] $RelativePath)

    $path = Join-Path $themeRoot $RelativePath
    Assert-True (Test-Path $path) "Missing expected file: $RelativePath"
    return Get-Content -Raw $path
}

$controlDesk = Read-ThemeFile 'inc/control-desk.php'
$adminCss = Read-ThemeFile 'assets/css/lunara-control-desk.css'

Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_review_single_preset_comparison_strip') 'Review Single Studio must render a preset comparison strip.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_review_single_preset_comparison_item') 'Review Single comparison strip must render normalized preset items.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_review_single_studio[\s\S]+lunara_control_desk_render_review_single_preset_comparison_strip\(\s*\$presets,\s*\$active_preset_key\s*\)') 'Review Single Studio must call the comparison strip renderer.'
Assert-True ($controlDesk -match 'lunara-control-desk-review-comparison-strip') 'Review Single comparison strip must use a stable wrapper class.'
Assert-True ($adminCss -match '\.lunara-control-desk-review-comparison-strip') 'Control Desk CSS must style the Review comparison strip.'

foreach ($preset in @(
    'editorial-balance',
    'cinematic-feature',
    'compact-dispatch',
    'spoiler-shield'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$preset'")) "Comparison strip must be based on existing preset specs that include $preset."
}

foreach ($label in @(
    'Package density',
    'Hero scale',
    'Rail mode',
    'Debrief prominence',
    'Pair It With density',
    'Spoiler treatment',
    'Trailer prominence',
    'Section rhythm',
    'Debrief poster width',
    'Related review count'
)) {
    Assert-True ($controlDesk -match [regex]::Escape($label)) "Comparison strip must expose $label."
}

Assert-True ($controlDesk -match 'lunara_control_desk_review_single_preset_specs\(\)') 'Comparison strip must reuse the existing Review Single preset specs.'
Assert-True ($controlDesk -match "__\(\s*'Default'\s*,\s*'lunara-film'\s*\)") 'Comparison strip must have a Default fallback for missing preset values.'
Assert-True ($controlDesk -match 'esc_html') 'Comparison strip output must escape labels and values.'
Assert-True ($controlDesk -match "lunara_control_desk_review_single_preset_preview_url\(\s*'/reviews/sinners-2025/'") 'Sinners preset preview links must use the canonical Review route.'
Assert-True ($controlDesk -notmatch "lunara_control_desk_review_single_preset_preview_url\(\s*'/review/sinners-2025/'") 'Sinners preset preview links must not use the broken singular Review sample path.'
Assert-True ($controlDesk -notmatch 'lunara-review-compare') 'Comparison strip must not introduce a new public comparison query variable.'
Assert-True ($controlDesk -notmatch '<textarea[^>]+lunara_review_single') 'Comparison strip must not expose raw CSS textareas.'

Write-Host 'Review Single preset comparison contract passed.'
