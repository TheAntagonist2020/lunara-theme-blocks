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

$header = Read-ThemeFile 'header.php'
$setup = Read-ThemeFile 'inc/setup.php'
$fallback = Read-ThemeFile 'functions.php'
$shell = Read-ThemeFile 'assets/css/lunara-shell.css'

$criticalMatch = [regex]::Match(
    $header,
    '(?s)<style id="lunara-critical-shell-repair">.*?</style>'
)

Assert-True $criticalMatch.Success 'The critical shell safety layer must remain present.'

$criticalBytes = [Text.Encoding]::UTF8.GetByteCount($criticalMatch.Value)
$shellBytes = [Text.Encoding]::UTF8.GetByteCount($shell)
Assert-True ($criticalBytes -le 12288) "Critical shell CSS exceeds its 12 KB budget: $criticalBytes bytes."
Assert-True ($shellBytes -le 204800) "Cacheable shell CSS exceeds its 200 KB transition budget: $shellBytes bytes."
Assert-True ($shell -notmatch '<\?php') 'The cacheable shell stylesheet must remain static CSS.'
Assert-True ($shell -match 'body\.home \.lunara-front-page > \.lunara-home-section') 'The cacheable shell stylesheet appears incomplete.'
Assert-True ($setup -match "lunara_resolve_theme_asset\(\s*'assets/css/lunara-shell\.css'") 'The split loader must enqueue the cacheable shell stylesheet.'
Assert-True ($fallback -match "lunara_resolve_theme_asset\(\s*'assets/css/lunara-shell\.css'") 'The fallback loader must enqueue the cacheable shell stylesheet.'
Assert-True ($setup -match "add_action\(\s*'wp_enqueue_scripts'\s*,\s*'lunara_enqueue_shell_styles'\s*,\s*100\s*\)") 'The cacheable shell must load after route-specific theme styles.'

Write-Host "Performance payload budget contract passed (critical: $criticalBytes bytes; shell: $shellBytes bytes)."
