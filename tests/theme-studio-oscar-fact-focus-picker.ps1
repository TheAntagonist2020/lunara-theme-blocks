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

Assert-True ($controlDesk -match 'lunara-control-desk-oscar-fact-focus-picker') 'Theme Studio must render a scoped Oscar Fact focus picker.'
Assert-True ($controlDesk -match 'role="radiogroup"') 'Oscar Fact focus picker must expose a radiogroup.'
Assert-True ($controlDesk -match 'type="radio"') 'Oscar Fact focus picker must use radio inputs, not a brittle duplicate select.'
Assert-True ($controlDesk -match 'name="lunara_image_source_visual_focus"') 'Oscar Fact focus picker must write the existing focus field name.'
Assert-True ($controlDesk -match 'checked\(\s*\$visual_focus,\s*\$focus_key\s*\)') 'Oscar Fact focus picker must mark the saved focus as selected.'
Assert-True ($controlDesk -match 'lunara_control_desk_oscar_fact_focus_picker_order') 'Oscar Fact focus picker must use an explicit 3x3 order.'

foreach ($focus in @('left-high','center-high','right-high','left','center','right','left-low','center-low','right-low')) {
    Assert-True ($controlDesk -match [regex]::Escape("'$focus'")) "Oscar Fact focus picker must include $focus."
}

Assert-True ($controlDesk -notmatch '<select\s+name="lunara_image_source_visual_focus"') 'Oscar Fact focus UI should not keep the old focus dropdown once the picker exists.'

Assert-True ($css -match '\.lunara-control-desk-oscar-fact-focus-picker') 'Oscar Fact focus picker must have scoped CSS.'
Assert-True ($css -match 'grid-template-columns:\s*repeat\(3,\s*minmax') 'Oscar Fact focus picker must render as a 3x3 grid.'
Assert-True ($css -match ':checked\s*\+\s*span') 'Oscar Fact focus picker must style the selected radio tile.'
Assert-True ($css -match ':focus-visible\s*\+\s*span') 'Oscar Fact focus picker must show keyboard focus.'

Write-Host 'Theme Studio Oscar Fact focus picker contract passed.'
