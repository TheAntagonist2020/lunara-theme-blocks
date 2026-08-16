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
$delivery  = Get-Content -Raw (Join-Path $root 'inc/hero-delivery.php')
$pilot     = Get-Content -Raw (Join-Path $root 'assets/js/lunara-splide-pilot.js')
$style     = Get-Content -Raw (Join-Path $root 'style.css')

Assert-True ($delivery -match 'function\s+lunara_get_home_cinematic_hero_preload_descriptor\s*\(') 'Hero response and markup hints must share one final-image descriptor.'
Assert-True ($delivery -match "add_action\(\s*'template_redirect'\s*,\s*'lunara_send_home_cinematic_hero_preload_header'\s*,\s*0\s*\)") 'The URL-only HTTP hero hint must run after routing and before template output.'
Assert-True ($delivery -match 'function\s+lunara_get_home_cinematic_hero_http_link_value\s*\(') 'The exact URL-only HTTP hint must be independently testable.'
Assert-True ($delivery -match 'header\(\s*''Link:\s*''\s*\.\s*\$link\s*,\s*false\s*\)') 'The URL-only HTTP Link hint must append without replacing existing WordPress response headers.'
$httpStart = $delivery.IndexOf('function lunara_get_home_cinematic_hero_http_link_value')
$httpEnd   = $delivery.IndexOf("if ( ! function_exists( 'lunara_send_home_cinematic_hero_preload_header'", $httpStart)
Assert-True ($httpStart -ge 0 -and $httpEnd -gt $httpStart) 'HTTP Link resolver boundaries are missing.'
$httpResolver = $delivery.Substring($httpStart, $httpEnd - $httpStart)
Assert-True ($httpResolver -match 'if\s*\(\s*''''\s*!==\s*\$descriptor\[''srcset''\]\s*\)\s*\{\s*return\s+'''';') 'Responsive heroes must not ship a fixed HTTP candidate without viewport information.'

Assert-True ($pilot -match "'IntersectionObserver'\s+in\s+window") 'Below-fold Splide mounting must be visibility-gated when the browser supports it.'
Assert-True ($pilot -match "rootMargin:\s*'800px 0px'") 'The Oscar Facts carousel must initialize shortly before it approaches the viewport.'
Assert-True ($pilot -match 'observer\.disconnect\(\);\s*\r?\n\s*initPilot\(root\);') 'The lazy observer must disconnect before mounting Splide once.'
Assert-True ($pilot -match "if\s*\(!\('IntersectionObserver'\s+in\s+window\)\)\s*\{\s*\r?\n\s*initPilot\(root\)") 'Older browsers must retain the functional immediate-mount fallback.'
Assert-True ($style -match 'Version:\s*3\.2\.49') 'HTTP hero hint and lazy Splide gate must report Theme 3.2.49.'

Write-Host 'Homepage HTTP hero hint and lazy Splide gate passed.'
