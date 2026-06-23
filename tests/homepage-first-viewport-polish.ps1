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

$frontend = Read-ThemeFile 'inc/frontend.php'
$functions = Read-ThemeFile 'functions.php'
$controlDesk = Read-ThemeFile 'inc/control-desk.php'

Assert-True ($frontend -match 'function lunara_home_first_viewport_polish_css\(\)') 'Homepage first-viewport polish must live in a named frontend CSS emitter.'
Assert-True ($frontend -match 'lunara-home-first-viewport-polish-css') 'Homepage first-viewport polish must render a distinct style id.'
Assert-True ($frontend -match "add_action\(\s*'wp_footer',\s*'lunara_home_first_viewport_polish_css',\s*135\s*\)") 'Homepage first-viewport polish must load after the existing homepage signature CSS.'
Assert-True ($frontend -match 'is_front_page\(\)') 'Homepage first-viewport polish must stay scoped to the front page.'

$match = [regex]::Match($frontend, 'function lunara_home_first_viewport_polish_css\(\) \{(?s).*?add_action\(\s*''wp_footer'',\s*''lunara_home_first_viewport_polish_css'',\s*135\s*\);')
Assert-True $match.Success 'Could not isolate homepage first-viewport polish block.'
$block = $match.Value

foreach ($needle in @(
    '@media(min-width:1120px)',
    'grid-template-columns:minmax(0,1.14fr) minmax(340px,.86fr)',
    'body.home .lunara-home-masthead-routes{grid-template-columns:minmax(0,1fr)',
    'max-height:clamp(148px,18vw,254px)',
    'body.home .lunara-front-page > .lunara-home-masthead + .lunara-home-section',
    'body.home main.lunara-front-page{gap:clamp(26px,4vw,58px)!important;}',
    'body.home .lunara-dispatches-section .lunara-home-section-summary{display:-webkit-box;-webkit-line-clamp:2;',
    'body.home .lunara-dispatches-section .lunara-journal-home-grid{margin-top:8px!important;}',
    'body.home .lunara-dispatches-section .lunara-home-section-title{opacity:1!important;transform:none!important;}',
    '@media(max-width:820px)',
    '@media(prefers-reduced-motion:reduce)'
)) {
    Assert-True ($block.Contains($needle)) "Homepage first-viewport polish is missing expected CSS: $needle"
}

Assert-True ($block -notmatch 'font-family\s*:') 'Homepage first-viewport polish must not introduce another font family.'
Assert-True ($block -notmatch 'set_theme_mod|get_option\(') 'Homepage first-viewport polish must not mutate or add settings.'
Assert-True ($functions -match 'lunara_get_home_section_mobile_order_map') 'Theme must still expose the mobile homepage order helper.'
Assert-True ($controlDesk -match "'mobile_order'\s*=>\s*array\(\s*'hero',\s*'dispatch',\s*'latest-reviews'") 'Homepage mobile order presets must still put Journal before Latest Reviews.'
Assert-True ($controlDesk -match "'desktop_order'\s*=>\s*array\(\s*'hero',\s*'latest-reviews',\s*'dispatch'") 'Homepage desktop order presets must still put Latest Reviews before Journal.'

foreach ($file in @($frontend, $functions)) {
    Assert-True ($file -match 'wp_get_attachment_image\(\s*\$custom_logo_id,\s*''full''') 'Footer logo renderers must use the full logo source, not a small rewritten thumbnail.'
    Assert-True ($file -match 'lunara-footer-logo skip-lazy no-lazy') 'Footer logo renderers must opt brand art out of lazy/thumbnail rewriting.'
}

Write-Host 'Homepage first-viewport polish contract passed.'
