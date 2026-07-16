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

$heroCommand = Read-ThemeFile 'inc/hero-command.php'
$heroCarousel = Read-ThemeFile 'assets/js/lunara-hero-carousel.js'
$themeFunctions = Read-ThemeFile 'functions.php'
$themeCss = Read-ThemeFile 'style.css'

Assert-True ($heroCommand -match "'enabled'\s*=>\s*0") 'Hero Command must remain disabled by default.'
Assert-True ($heroCommand -match 'isset\(\s*\$_POST\[''lunara_hero_command_cinematic_opener''\]\s*\)') 'Hero Command must read the explicit Cinematic Hero opener checkbox.'
Assert-True ($heroCommand -match 'set_theme_mod\(\s*''lunara_home_cinematic_front_door_enabled''\s*,') 'Saving Hero Command must persist the Cinematic Hero homepage-opener setting.'
Assert-True ($heroCommand -match 'name="lunara_hero_command_cinematic_opener"') 'Hero Command must render the Cinematic Hero opener control.'
Assert-True ($heroCommand -match 'Use Cinematic Hero as homepage opener') 'The homepage-opener control must use the approved editorial label.'
Assert-True ($heroCommand -match 'lunara_home_cinematic_front_door_is_enabled\(\)') 'The homepage-opener control must reflect the current cinematic gate.'
Assert-True ($heroCarousel -match "is-hero-mounted', 'is-hero-static', 'is-rendered") 'A single-slide cinematic hero must release Splide visibility when rotation is skipped.'
Assert-True ($themeFunctions -match '\$is_static\s*=\s*count\(\s*\$slides\s*\)\s*<\s*2') 'The server-rendered carousel must identify a one-slide static opener before first paint.'
Assert-True ($themeFunctions -match '\$hero_classes\s*\.\=\s*'' is-hero-static''') 'The server-rendered static opener must expose its layout class before JavaScript runs.'
Assert-True ($themeCss -match 'lunara-cinematic-hero-carousel\.is-hero-static') 'Static cinematic heroes must have a dedicated layout guard.'
Assert-True ($themeCss -match 'is-hero-static \.lunara-cinematic-hero-slide[\s\S]*flex: 0 0 100%') 'A static cinematic hero slide must fill the carousel width.'
Assert-True ($themeCss -match 'is-hero-static \.lunara-cinematic-hero-track::before[\s\S]*content: none') 'Static cinematic heroes must not render the scroll curtain.'

Write-Host 'Hero Command cinematic opener contract passed.'
