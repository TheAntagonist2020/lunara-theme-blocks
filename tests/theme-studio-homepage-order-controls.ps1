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
$css = Read-ThemeFile 'assets/css/lunara-control-desk.css'

Assert-True ($controlDesk -match 'lunara_control_desk_homepage_order_preset_specs') 'Homepage Studio must define bounded section-order presets.'
Assert-True ($controlDesk -match 'lunara_control_desk_homepage_order_for_preset') 'Homepage Studio must map presets to sanitized section order strings.'
Assert-True ($controlDesk -match 'lunara_home_section_order_preset') 'Homepage Studio must store the selected order preset.'
Assert-True ($controlDesk -match 'set_theme_mod\(\s*''lunara_home_section_order''') 'Saving a preset must update the existing public section order setting.'
Assert-True ($controlDesk -match 'lunara_control_desk_render_homepage_order_preset_control') 'Homepage Studio must render a selectable order preset control.'
Assert-True ($controlDesk -match 'name="lunara_homepage_order_preset"') 'Order preset UI must post through the existing Homepage Studio save path.'

foreach ($preset in @('editorial-default','journal-first','oscars-forward')) {
    Assert-True ($controlDesk -match [regex]::Escape("'$preset'")) "Homepage Studio must include the $preset preset."
}

foreach ($label in @('Editorial default','Journal first','Oscars forward')) {
    Assert-True ($controlDesk -match [regex]::Escape($label)) "Homepage Studio must show the $label label."
}

Assert-True ($controlDesk -notmatch '<textarea[^>]+lunara_home_section_order') 'Homepage Studio must not introduce a raw section-order textarea.'
Assert-True ($controlDesk -notmatch 'name="lunara_home_section_order"') 'Homepage Studio must not expose the raw order setting directly.'

Assert-True ($css -match '\.lunara-control-desk-homepage-order-presets') 'Homepage order preset UI must have scoped CSS.'
Assert-True ($css -match '\.lunara-control-desk-homepage-order-card') 'Homepage order preset cards must have scoped CSS.'
Assert-True ($css -match '\.lunara-control-desk-homepage-order-card\.is-selected') 'Selected homepage order preset must be visually distinct.'

Write-Host 'Homepage Studio order controls contract passed.'
