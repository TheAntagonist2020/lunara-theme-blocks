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
$functions = Read-ThemeFile 'functions.php'
$frontend = Read-ThemeFile 'inc/frontend.php'
$homeModules = Read-ThemeFile 'assets/css/lunara-home-modules.css'

Assert-True ($controlDesk -match "'lunara_home_oscar_picks_density'") 'Homepage Studio must expose Oscar Picks density.'
Assert-True ($controlDesk -match "'lunara_home_oscar_picks_count'") 'Homepage Studio must expose Oscar Picks count.'
Assert-True ($controlDesk -match "'lunara_home_oscar_picks_card_min_height'") 'Homepage Studio must expose Oscar Picks card height.'
Assert-True ($controlDesk -match "'lunara_home_oscar_picks_autoplay_interval'") 'Homepage Studio must expose Oscar Picks autoplay interval.'
Assert-True ($controlDesk -match "'lunara_home_oscar_picks_count'[\s\S]*?'min'\s*=>\s*4[\s\S]*?'max'\s*=>\s*16") 'Oscar Picks count must be clamped from 4 to 16.'
Assert-True ($controlDesk -match "'lunara_home_oscar_picks_autoplay_interval'[\s\S]*?'min'\s*=>\s*0[\s\S]*?'max'\s*=>\s*12000") 'Oscar Picks autoplay interval must support off and clamp at 12000ms.'

Assert-True ($functions -match "get_theme_mod\(\s*'lunara_home_oscar_picks_count'") 'Oscar Picks renderer must read the count theme mod.'
Assert-True ($functions -match "get_theme_mod\(\s*'lunara_home_oscar_picks_autoplay_interval'") 'Oscar Picks renderer must read the autoplay theme mod.'
Assert-True ($functions -match "get_theme_mod\(\s*'lunara_home_oscar_picks_density'") 'Oscar Picks renderer must read the density theme mod.'
Assert-True ($functions -match 'is-density-<\?php echo esc_attr\( \$oscar_picks_density \); \?>') 'Oscar Picks section must expose a public density class.'
Assert-True ($functions -match "max\( 4, min\( 16,") 'Oscar Picks count must be clamped in the renderer.'
Assert-True ($functions -match "max\( 0, min\( 12000,") 'Oscar Picks autoplay must be clamped in the renderer.'
Assert-True ($functions -match 'data-lunara-carousel-autoplay="<\?php echo \$pick_count > 1 \? \(int\) \$args\[''autoplay''\] : 0; \?>"') 'Oscar Picks autoplay must still disable when only one card exists.'

Assert-True ($frontend -match "lunara_home_oscar_picks_density") 'Homepage public CSS must read Oscar Picks density.'
Assert-True ($frontend -match "lunara_home_oscar_picks_card_min_height") 'Homepage public CSS must read Oscar Picks card height.'
Assert-True ($frontend -match "--lunara-home-oscar-picks-card-min") 'Homepage public CSS must emit an Oscar Picks card-height variable.'
Assert-True ($frontend -match "--lunara-home-oscar-picks-gap") 'Homepage public CSS must emit an Oscar Picks gap variable.'
Assert-True ($homeModules -match "body\.home \.lunara-oscar-picks-section \.lunara-oscar-picks-track") 'Homepage public CSS must scope Oscar Picks track tuning to the homepage.'

Write-Host 'Homepage Oscar Picks Studio controls contract passed.'
