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

$functions = Read-ThemeFile 'functions.php'
$controlDesk = Read-ThemeFile 'inc/control-desk.php'
$header = Read-ThemeFile 'header.php'
$helpers = Read-ThemeFile 'inc/helpers.php'
$css = Read-ThemeFile 'assets/css/lunara-control-desk.css'

Assert-True ($functions -match 'lunara_get_home_section_mobile_order_map') 'Theme must expose a mobile homepage order map helper.'
Assert-True ($functions -match 'lunara_home_section_mobile_order') 'Theme must read the mobile homepage section-order setting.'
Assert-True ($functions -match '@media\(max-width:820px\)') 'Theme must emit a mobile-only homepage order media block.'
Assert-True ($functions -match '\$mobile_order_css\s*\.=') 'Theme must build dedicated mobile section-order CSS.'
Assert-True ($functions -match 'lunara-home-slot-') 'Theme mobile order CSS must target homepage slot classes.'
Assert-True ($helpers -match "'oscar-picks'\s*=>") 'Canonical homepage registry must include the Oscar Picks lane.'
Assert-True ($helpers -match "'oscar-facts'\s*=>") 'Canonical homepage registry must include the Oscar Facts lane.'
Assert-True ($header -match 'lunara_get_home_section_order_map') 'Critical header CSS must use the desktop homepage order map.'
Assert-True ($header -match 'lunara_get_home_section_mobile_order_map') 'Critical header CSS must use the mobile homepage order map.'
Assert-True ($header -notmatch 'body\.home \.lunara-home-slot-latest-reviews \{\s*order:\s*2\s*!important;\s*\}') 'Critical header CSS must not hardcode Latest Reviews as the desktop/mobile winner.'

Assert-True ($controlDesk -match 'lunara_control_desk_homepage_order_for_preset\(\s*\$order_preset,\s*''desktop''') 'Homepage Studio must derive desktop order from the selected preset.'
Assert-True ($controlDesk -match 'lunara_control_desk_homepage_order_for_preset\(\s*\$order_preset,\s*''mobile''') 'Homepage Studio must derive mobile order from the selected preset.'
Assert-True ($controlDesk -match 'set_theme_mod\(\s*''lunara_home_section_mobile_order''') 'Saving Homepage Studio must persist the mobile section order.'
Assert-True ($controlDesk -match "'desktop_order'\s*=>") 'Preset specs must store desktop slug order separately.'
Assert-True ($controlDesk -match "'mobile_order'\s*=>") 'Preset specs must store mobile slug order separately.'
Assert-True ($controlDesk -match "'mobile_order'\s*=>\s*array\(\s*'hero',\s*'dispatch',\s*'latest-reviews'") 'Editorial default mobile order must put Journal before Latest Reviews.'
Assert-True ($controlDesk -notmatch 'Per-breakpoint ordering stays deferred') 'Homepage Studio copy must not claim per-breakpoint ordering is deferred.'

Assert-True ($css -match '\.lunara-control-desk-homepage-order-grid') 'Responsive order preview must keep scoped admin styling.'

Write-Host 'Homepage Studio responsive order contract passed.'
