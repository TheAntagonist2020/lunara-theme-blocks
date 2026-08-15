$ErrorActionPreference = 'Stop'

function Assert-True {
    param(
        [bool] $Condition,
        [string] $Message
    )

    if ( -not $Condition ) {
        throw $Message
    }
}

$root      = Split-Path -Parent $PSScriptRoot
$functions = Get-Content -Raw (Join-Path $root 'functions.php')
$pilot     = Get-Content -Raw (Join-Path $root 'assets/js/lunara-splide-pilot.js')
$style     = Get-Content -Raw (Join-Path $root 'style.css')

Assert-True ($functions -match 'function\s+lunara_get_home_cinematic_hero_preload_url\s*\(') 'Hero response and markup hints must share one canonical URL resolver.'
Assert-True ($functions -match "add_action\(\s*'template_redirect'\s*,\s*'lunara_send_home_cinematic_hero_preload_header'\s*,\s*0\s*\)") 'The HTTP hero hint must run after routing and before template output.'
Assert-True ($functions -match 'header\(\s*''Link:\s*<''\s*\.\s*\$image\s*\.\s*''>;\s*rel=preload;\s*as=image;\s*fetchpriority=high''\s*,\s*false\s*\)') 'The HTTP Link hint must append without replacing existing WordPress response headers.'
Assert-True (([regex]::Matches($functions, 'lunara_get_home_cinematic_hero_preload_url\s*\(\s*\)')).Count -ge 2) 'The HTTP and HTML preload paths must resolve the same hero URL.'

Assert-True ($pilot -match "'IntersectionObserver'\s+in\s+window") 'Below-fold Splide mounting must be visibility-gated when the browser supports it.'
Assert-True ($pilot -match "rootMargin:\s*'800px 0px'") 'The Oscar Facts carousel must initialize shortly before it approaches the viewport.'
Assert-True ($pilot -match 'observer\.disconnect\(\);\s*\r?\n\s*initPilot\(root\);') 'The lazy observer must disconnect before mounting Splide once.'
Assert-True ($pilot -match "if\s*\(!\('IntersectionObserver'\s+in\s+window\)\)\s*\{\s*\r?\n\s*initPilot\(root\)") 'Older browsers must retain the functional immediate-mount fallback.'
Assert-True ($style -match 'Version:\s*3\.2\.38') 'HTTP hero hint and lazy Splide gate must report Theme 3.2.38.'

Write-Host 'Homepage HTTP hero hint and lazy Splide gate passed.'
