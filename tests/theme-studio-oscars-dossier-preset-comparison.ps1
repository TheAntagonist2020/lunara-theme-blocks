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

Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_oscars_dossier_preset_comparison_strip') 'Oscars Dossier Studio must render a preset comparison strip.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_oscars_dossier_preset_comparison_item') 'Oscars Dossier comparison strip must render normalized preset items.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_oscars_dossier_studio[\s\S]+lunara_control_desk_render_oscars_dossier_preset_comparison_strip\(\s*\$presets,\s*\$active_preset_key\s*\)') 'Oscars Dossier Studio must call the comparison strip renderer.'
Assert-True ($controlDesk -match 'lunara-control-desk-oscars-comparison-strip') 'Oscars Dossier comparison strip must use a stable wrapper class.'
Assert-True ($adminCss -match '\.lunara-control-desk-oscars-comparison-strip') 'Control Desk CSS must style the Oscars comparison strip.'

foreach ($preset in @(
    'historical-dossier',
    'ceremony-feature',
    'compact-ledger',
    'profile-spotlight'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$preset'")) "Comparison strip must be based on existing preset specs that include $preset."
}

foreach ($label in @(
    'Dossier density',
    'Ceremony rhythm',
    'Major-race prominence',
    'Profile scale',
    'Write-up prominence',
    'Related reviews shown',
    'Related-review treatment',
    'Profile image chamber',
    'Title/person image focus',
    'Dossier section gap',
    'Card minimum width',
    'Profile image width',
    'Profile image height'
)) {
    Assert-True ($controlDesk -match [regex]::Escape($label)) "Comparison strip must expose $label."
}

Assert-True ($controlDesk -match 'lunara_control_desk_oscars_dossier_preset_specs\(\)') 'Comparison strip must reuse the existing Oscars Dossier preset specs.'
Assert-True ($controlDesk -match "__\(\s*'Default'\s*,\s*'lunara-film'\s*\)") 'Comparison strip must have a Default fallback for missing preset values.'
Assert-True ($controlDesk -match 'esc_html') 'Comparison strip output must escape labels and values.'
Assert-True ($controlDesk -notmatch 'lunara-oscars-compare') 'Comparison strip must not introduce a new public comparison query variable.'
Assert-True ($controlDesk -notmatch '<textarea[^>]+lunara_oscars') 'Comparison strip must not expose raw CSS textareas.'

Write-Host 'Oscars Dossier preset comparison contract passed.'
