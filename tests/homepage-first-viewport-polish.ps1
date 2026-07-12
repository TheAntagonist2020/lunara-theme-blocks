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
$setup = Read-ThemeFile 'inc/setup.php'
$controlDesk = Read-ThemeFile 'inc/control-desk.php'
$homeModules = Read-ThemeFile 'assets/css/lunara-home-modules.css'

Assert-True ($frontend -match 'function lunara_home_first_viewport_polish_css\(\)') 'Homepage first-viewport polish must live in a named frontend CSS emitter.'
Assert-True ($homeModules -match 'lunara-home-first-viewport-polish-css') 'Homepage first-viewport polish must remain a named cacheable CSS section.'
Assert-True ($setup -match "add_action\(\s*'wp_head',\s*'lunara_print_home_module_styles',\s*44\s*\)") 'Cacheable first-viewport CSS must load immediately before the dynamic Front Desk variables.'
Assert-True ($frontend -match 'is_front_page\(\)') 'Homepage first-viewport polish must stay scoped to the front page.'
Assert-True ($frontend -match 'function lunara_home_front_door_lead_image\( \$lead \)') 'The front-desk lead must use a named LCP image renderer.'
Assert-True ($frontend -match "'fetchpriority'\s*=>\s*'high'") 'The front-desk LCP image must receive high fetch priority.'
Assert-True ($frontend -match "'loading'\s*=>\s*'eager'") 'The front-desk LCP image must load eagerly.'
Assert-True ($frontend -match 'wp_get_attachment_image\(\s*\$attachment_id,\s*''lunara-hero-spotlight''') 'Local front-desk media must retain WordPress responsive image markup.'
Assert-True ($frontend -match 'class="lunara-home-front-desk-lead<\?php echo \$lead_image') 'The front-desk lead must render the dedicated image candidate.'
Assert-True ($frontend -notmatch 'lunara-home-front-desk-lead[^>]+style="background-image') 'The front-desk LCP image must not be hidden in an inline CSS background.'

$match = [regex]::Match($homeModules, '(?s)/\*lunara-home-first-viewport-polish-css\*/(?<css>.*)$')
Assert-True $match.Success 'Could not isolate homepage first-viewport polish block.'
$block = $match.Groups['css'].Value

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

foreach ($needle in @(
    'body.home .lunara-home-masthead-logo-frame{display:grid;place-items:center;width:min(100%,1280px);height:clamp(148px,18vw,254px);',
    '@media(min-width:821px) and (max-width:1119px){body.home .lunara-home-masthead-logo-frame{height:clamp(148px,22vw,312px);}}',
    'body.home .lunara-home-masthead-logo-frame{width:100%;height:clamp(100px,18vw,148px);}'
)) {
    Assert-True ($homeModules.Contains($needle)) "Homepage logo frame is missing its responsive CLS reservation: $needle"
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
