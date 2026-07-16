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

Assert-True ($heroCommand -match "'enabled'\s*=>\s*0") 'Hero Command must remain disabled by default.'
Assert-True ($heroCommand -match 'isset\(\s*\$_POST\[''lunara_hero_command_cinematic_opener''\]\s*\)') 'Hero Command must read the explicit Cinematic Hero opener checkbox.'
Assert-True ($heroCommand -match 'set_theme_mod\(\s*''lunara_home_cinematic_front_door_enabled''\s*,') 'Saving Hero Command must persist the Cinematic Hero homepage-opener setting.'
Assert-True ($heroCommand -match 'name="lunara_hero_command_cinematic_opener"') 'Hero Command must render the Cinematic Hero opener control.'
Assert-True ($heroCommand -match 'Use Cinematic Hero as homepage opener') 'The homepage-opener control must use the approved editorial label.'
Assert-True ($heroCommand -match 'lunara_home_cinematic_front_door_is_enabled\(\)') 'The homepage-opener control must reflect the current cinematic gate.'

Write-Host 'Hero Command cinematic opener contract passed.'
