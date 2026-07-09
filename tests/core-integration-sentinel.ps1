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
$loader = Read-ThemeFile 'functions-loader.php'

Assert-True ($loader -match "define\(\s*'LUNARA_SPLIT_LOADER_ACTIVE'\s*,\s*true\s*\)") 'The modular loader must define its theme-owned completion sentinel.'
Assert-True ($functions -notmatch "define\(\s*'LUNARA_CORE_VERSION'") 'The theme must not impersonate the Lunara Core plugin version constant.'
Assert-True ($loader.IndexOf("define( 'LUNARA_SPLIT_LOADER_ACTIVE'") -gt $loader.LastIndexOf('require_once')) 'The loader sentinel must be defined only after every modular include completes.'

$fallbackGuards = [regex]::Matches($functions, "if\s*\(\s*!\s*defined\(\s*'LUNARA_SPLIT_LOADER_ACTIVE'\s*\)")
Assert-True ($fallbackGuards.Count -eq 4) 'All four monolithic Core/loader fallback groups must use the split-loader sentinel.'

foreach ($relativePath in @(
    'inc/reviews-cpt.php',
    'inc/debrief.php',
    'inc/carousel.php',
    'inc/review-rendering.php'
)) {
    $module = Read-ThemeFile $relativePath
    Assert-True ($module -match "!\s*defined\(\s*'LUNARA_CORE_VERSION'\s*\)") "$relativePath must continue to yield ownership to the active Lunara Core plugin."
}

Write-Host 'Core integration sentinel contract passed.'
