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
$heroDelivery = Read-ThemeFile 'inc/hero-delivery.php'
$heroCarousel = Read-ThemeFile 'assets/js/lunara-hero-carousel.js'
$themeFunctions = Read-ThemeFile 'functions.php'
$themeCss = Read-ThemeFile 'style.css'

Assert-True ($heroCommand -match "'enabled'\s*=>\s*0") 'Hero Command must remain disabled by default.'
Assert-True ($heroCommand -match 'isset\(\s*\$_POST\[''lunara_hero_command_cinematic_opener''\]\s*\)') 'Hero Command must read the explicit Cinematic Hero opener checkbox.'
Assert-True ($heroCommand -match 'set_theme_mod\(\s*''lunara_home_cinematic_front_door_enabled''\s*,') 'Saving Hero Command must persist the Cinematic Hero homepage-opener setting.'
Assert-True ($heroCommand -match 'name="lunara_hero_command_cinematic_opener"') 'Hero Command must render the Cinematic Hero opener control.'
Assert-True ($heroCommand -match 'Use Cinematic Hero as homepage opener') 'The homepage-opener control must use the approved editorial label.'
Assert-True ($heroCommand -match 'lunara_home_cinematic_front_door_is_enabled\(\)') 'The homepage-opener control must reflect the current cinematic gate.'
Assert-True ($heroCommand -match "'focal_x'\s*=>\s*max\(\s*0,\s*min\(\s*100,") 'Hero Command must clamp horizontal focal position to a safe percentage.'
Assert-True ($heroCommand -match "'focal_y'\s*=>\s*max\(\s*0,\s*min\(\s*100,") 'Hero Command must clamp vertical focal position to a safe percentage.'
Assert-True ($heroCommand -match "'zoom'\s*=>\s*max\(\s*100,\s*min\(\s*112,") 'Hero Command must constrain per-slide zoom without allowing letterboxing.'
Assert-True ($heroCommand -match 'data-hero-field="focal_x"') 'Hero Command must expose per-slide horizontal focal control.'
Assert-True ($heroCommand -match 'data-hero-field="focal_y"') 'Hero Command must expose per-slide vertical focal control.'
Assert-True ($heroCommand -match 'data-hero-field="zoom"') 'Hero Command must expose per-slide zoom control.'
Assert-True ($heroCommand -match '''fit''\s*=>\s*\$fit') 'Hero Command must retain the sanitized per-slide frame mode.'
Assert-True ($heroCommand -match 'data-hero-field="fit"') 'Hero Command must expose the per-slide frame-mode selector.'
Assert-True ($heroCommand -match 'Full frame \(no crop\)') 'Hero Command must offer an explicit no-crop framing choice.'
Assert-True ($heroDelivery -match '--lunara-hero-focal-x:%d%%;--lunara-hero-focal-y:%d%%;--lunara-hero-zoom-start:%.2F;--lunara-hero-zoom-end:%.2F;') 'The active hero image renderer must emit sanitized per-slide framing variables.'
Assert-True ($heroDelivery -match '''full''\s*===\s*\$fit\s*\?\s*'' is-full-frame''') 'The active hero image renderer must mark full-frame slides before paint.'
Assert-True ($themeCss -match 'object-position:\s*var\(--lunara-hero-focal-x,\s*50%\)\s*var\(--lunara-hero-focal-y,\s*30%\)') 'Hero imagery must consume the per-slide focal point.'
Assert-True ($themeCss -match 'scale\(var\(--lunara-hero-zoom-start,\s*1\)\)') 'Hero imagery must consume the per-slide zoom while preserving the full frame by default.'
Assert-True ($themeCss -match '\.lunara-cinematic-hero-img\.is-full-frame[\s\S]*object-fit:\s*contain\s*!important') 'Full-frame hero images must preserve the complete native composition.'
Assert-True ($themeCss -match '\.lunara-cinematic-hero-img\.is-full-frame[\s\S]*animation:\s*none\s*!important') 'Full-frame hero images must not re-crop themselves through motion.'
Assert-True ($themeCss -match '\.lunara-cinematic-hero-slide\.is-full-frame\s+\.lunara-cinematic-hero-bg\s*\{[\s\S]*?transform:\s*none\s*!important') 'Full-frame mode must reset the legacy background-layer scale so neither edge is trimmed.'
Assert-True ($heroCarousel -match "is-hero-mounted', 'is-hero-static', 'is-rendered") 'A single-slide cinematic hero must release Splide visibility when rotation is skipped.'
Assert-True ($themeFunctions -match '\$is_static\s*=\s*count\(\s*\$slides\s*\)\s*<\s*2') 'The server-rendered carousel must identify a one-slide static opener before first paint.'
Assert-True ($themeFunctions -match '\$hero_classes\s*\.\=\s*'' is-hero-static''') 'The server-rendered static opener must expose its layout class before JavaScript runs.'
Assert-True ($themeCss -match 'lunara-cinematic-hero-carousel\.is-hero-static') 'Static cinematic heroes must have a dedicated layout guard.'
Assert-True ($themeCss -match 'is-hero-static[\s\S]*visibility:\s*visible\s*!important') 'Static cinematic heroes must remain visible when delayed JavaScript has not mounted Splide.'
Assert-True ($themeCss -match 'is-hero-static \.lunara-cinematic-hero-slide[\s\S]*flex: 0 0 100%') 'A static cinematic hero slide must fill the carousel width.'
Assert-True ($themeCss -match 'is-hero-static \.lunara-cinematic-hero-track::before[\s\S]*content: none') 'Static cinematic heroes must not render the scroll curtain.'

Write-Host 'Hero Command cinematic opener contract passed.'
