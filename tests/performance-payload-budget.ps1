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
$frontend = Read-ThemeFile 'inc/frontend.php'
$style = Read-ThemeFile 'style.css'
$shell = Read-ThemeFile 'assets/css/lunara-shell.css'
$publicGuardrails = Read-ThemeFile 'assets/css/lunara-public-guardrails.css'
$homeModules = Read-ThemeFile 'assets/css/lunara-home-modules.css'
$lateOscars = Read-ThemeFile 'assets/css/lunara-oscars-late-guardrails.css'
$reviewComponents = Read-ThemeFile 'assets/css/lunara-review-components.css'
$publicRuntime = Read-ThemeFile 'assets/js/lunara-public-runtime.js'
$scrollCarousel = Read-ThemeFile 'assets/js/lunara-scroll-carousel.js'
$homeRuntime = Read-ThemeFile 'assets/js/lunara-home-runtime.js'
$grain = Read-ThemeFile 'assets/images/lunara-grain.svg'

$criticalMatch = [regex]::Match(
    $header,
    '(?s)<style id="lunara-critical-shell-repair">.*?</style>'
)

Assert-True $criticalMatch.Success 'The critical shell safety layer must remain present.'

$criticalBytes = [Text.Encoding]::UTF8.GetByteCount($criticalMatch.Value)
$shellBytes = [Text.Encoding]::UTF8.GetByteCount($shell)
$publicGuardrailBytes = [Text.Encoding]::UTF8.GetByteCount($publicGuardrails)
$homeModuleBytes = [Text.Encoding]::UTF8.GetByteCount($homeModules)
$lateOscarsBytes = [Text.Encoding]::UTF8.GetByteCount($lateOscars)
$reviewComponentBytes = [Text.Encoding]::UTF8.GetByteCount($reviewComponents)
$publicRuntimeBytes = [Text.Encoding]::UTF8.GetByteCount($publicRuntime)
$scrollCarouselBytes = [Text.Encoding]::UTF8.GetByteCount($scrollCarousel)
$homeRuntimeBytes = [Text.Encoding]::UTF8.GetByteCount($homeRuntime)
$grainBytes = [Text.Encoding]::UTF8.GetByteCount($grain)
Assert-True ($criticalBytes -le 12288) "Critical shell CSS exceeds its 12 KB budget: $criticalBytes bytes."
Assert-True ($shellBytes -le 204800) "Cacheable shell CSS exceeds its 200 KB transition budget: $shellBytes bytes."
Assert-True ($publicGuardrailBytes -le 61440) "Public guardrail CSS exceeds its 60 KB budget: $publicGuardrailBytes bytes."
Assert-True ($homeModuleBytes -le 61440) "Homepage module CSS exceeds its 60 KB budget: $homeModuleBytes bytes."
Assert-True ($lateOscarsBytes -le 20480) "Late Oscars CSS exceeds its 20 KB budget: $lateOscarsBytes bytes."
Assert-True ($reviewComponentBytes -le 20480) "Review component CSS exceeds its 20 KB budget: $reviewComponentBytes bytes."
Assert-True ($publicRuntimeBytes -le 20480) "Public runtime exceeds its 20 KB budget: $publicRuntimeBytes bytes."
Assert-True ($scrollCarouselBytes -le 10240) "Scroll carousel runtime exceeds its 10 KB budget: $scrollCarouselBytes bytes."
Assert-True ($homeRuntimeBytes -le 10240) "Home runtime exceeds its 10 KB budget: $homeRuntimeBytes bytes."
Assert-True ($grainBytes -le 2048) "Grain texture exceeds its 2 KB budget: $grainBytes bytes."
Assert-True ($shell -notmatch '<\?php') 'The cacheable shell stylesheet must remain static CSS.'
Assert-True ($publicGuardrails -notmatch '<\?php') 'Public guardrails must remain static CSS.'
Assert-True ($homeModules -notmatch '<\?php') 'Homepage modules must remain static CSS.'
Assert-True ($lateOscars -notmatch '<\?php') 'Late Oscars guardrails must remain static CSS.'
Assert-True ($reviewComponents -notmatch '<\?php') 'Review components must remain static CSS.'
Assert-True ($shell -match 'body\.home \.lunara-front-page > \.lunara-home-section') 'The cacheable shell stylesheet appears incomplete.'
Assert-True ($publicGuardrails -match 'body\.home \.lunara-journal-home-grid') 'The public guardrail stylesheet appears incomplete.'
Assert-True ($homeModules -match 'body\.home \.lunara-oscar-facts-section') 'The homepage module stylesheet appears incomplete.'
Assert-True ($lateOscars -match 'body\.aat-shell-page \.aat-container') 'The late Oscars stylesheet appears incomplete.'
Assert-True ($reviewComponents -match 'lunara-pair-cards') 'The review component stylesheet appears incomplete.'
Assert-True ($setup -match "lunara_resolve_theme_asset\(\s*'assets/css/lunara-shell\.css'") 'The split loader must enqueue the cacheable shell stylesheet.'
Assert-True ($fallback -match "lunara_resolve_theme_asset\(\s*'assets/css/lunara-shell\.css'") 'The fallback loader must enqueue the cacheable shell stylesheet.'
Assert-True ($setup -match "add_action\(\s*'wp_enqueue_scripts'\s*,\s*'lunara_enqueue_shell_styles'\s*,\s*100\s*\)") 'The cacheable shell must load after route-specific theme styles.'

$cacheableAssets = @(
    'assets/css/lunara-public-guardrails.css',
    'assets/css/lunara-home-modules.css',
    'assets/css/lunara-oscars-late-guardrails.css'
)
foreach ($asset in $cacheableAssets) {
    $escapedAsset = [regex]::Escape($asset)
    Assert-True ($setup -match $escapedAsset) "The split loader is missing $asset."
    Assert-True ($fallback -match $escapedAsset) "The fallback loader is missing $asset."
}

Assert-True ($setup -match "add_action\(\s*'wp_head'\s*,\s*'lunara_print_public_guardrail_styles'\s*,\s*1005\s*\)") 'Public guardrails must retain their late head cascade position.'
Assert-True ($setup -match "add_action\(\s*'wp_head'\s*,\s*'lunara_print_home_module_styles'\s*,\s*44\s*\)") 'Homepage modules must load before the dynamic Front Desk variables.'
Assert-True ($setup -match "add_action\(\s*'wp_footer'\s*,\s*'lunara_print_late_oscars_guardrail_styles'\s*,\s*999\s*\)") 'Late Oscars guardrails must remain the final route safeguard.'

$dynamicSignature = [regex]::Match(
    $frontend,
    '(?s)<style id="lunara-homepage-studio-signature-css">.*?</style>'
)
Assert-True $dynamicSignature.Success 'The request-specific homepage variable block must remain present.'
$dynamicSignatureBytes = [Text.Encoding]::UTF8.GetByteCount($dynamicSignature.Value)
Assert-True ($dynamicSignatureBytes -le 4096) "Homepage variable CSS exceeds its 4 KB budget: $dynamicSignatureBytes bytes."
Assert-True ($dynamicSignature.Value -match '--lunara-home-section-gap') 'Homepage variable CSS is missing its primary custom property.'

$frontDoorVars = [regex]::Match(
    $frontend,
    '(?s)<style id="lunara-home-front-door-vars">.*?</style>'
)
Assert-True $frontDoorVars.Success 'The Front Desk settings variable block must remain present.'
$frontDoorVarBytes = [Text.Encoding]::UTF8.GetByteCount($frontDoorVars.Value)
Assert-True ($frontDoorVarBytes -le 2048) "Front Desk variable CSS exceeds its 2 KB budget: $frontDoorVarBytes bytes."

$retiredInlineIds = @(
    'lunara-journal-desk-polish',
    'lunara-os-responsive-guardrails',
    'lunara-full-spoiler-review-css',
    'lunara-home-card-media-hygiene-css',
    'lunara-home-review-card-cta-css',
    'lunara-home-review-rail-css',
    'lunara-home-oscar-facts-carousel-css',
    'lunara-home-journal-title-rhythm-css',
    'lunara-home-mobile-card-runway-css',
    'lunara-home-journal-mobile-runway-css',
    'lunara-home-text-led-card-chamber-css',
    'lunara-os-late-oscars-mobile-guardrails'
)
$phpSources = $setup + "`n" + $fallback + "`n" + $frontend + "`n" + $header
foreach ($id in $retiredInlineIds) {
    Assert-True ($phpSources -notmatch ('<style id="' + [regex]::Escape($id) + '">')) "Retired inline style remains in PHP: $id"
}

Assert-True ($frontend -notmatch 'lunara_output_atmosphere_js|createImageData|toDataURL') 'The runtime canvas grain generator must stay removed.'
Assert-True ($header -notmatch '<script id="lunara-card-image-hydrator">') 'The image hydrator must stay in the cacheable public runtime.'
Assert-True (($setup + $fallback + $frontend) -notmatch "add_action\(\s*'wp_footer'\s*,\s*'(lunara_render_live_search_script|lunara_output_carousel_controls_js|lunara_output_image_fadein_js|lunara_output_scroll_reveal_js|lunara_output_stats_countup_js|lunara_output_match_cut_js|lunara_output_hero_cinema_js|lunara_output_oscar_lore_interaction_js)'") 'Retired inline runtime hooks must stay disabled.'
Assert-True ($frontend -match "assets/js/lunara-public-runtime\.js") 'The public cacheable runtime must be enqueued.'
Assert-True ($frontend -match "assets/js/lunara-scroll-carousel\.js") 'The route-scoped carousel runtime must be enqueued.'
Assert-True ($frontend -match "assets/js/lunara-home-runtime\.js") 'The Home runtime must be enqueued.'
Assert-True ($frontend -match "assets/css/lunara-review-components\.css") 'The route-scoped Review component stylesheet must be enqueued.'
Assert-True ($publicRuntime -match "addEventListener\('error',\s*markLoaded") 'The public image runtime must reveal failed images instead of leaving invisible card chambers.'
Assert-True ($publicRuntime -match 'setTimeout\(markLoaded,\s*1800\)') 'The public image runtime must retain its bounded visibility fallback.'
Assert-True ($publicRuntime -notmatch 'img\.src\s*=') 'The public runtime must leave lazy image URL promotion to WordPress.com rather than trusting DOM data attributes.'
Assert-True ($homeRuntime -notmatch '\.innerHTML\s*=') 'The Home lore runtime must not reinterpret editorial DOM text as HTML.'
Assert-True ($homeRuntime -match '\.textContent\s*=') 'The Home lore runtime must construct editorial detail text through safe DOM APIs.'
Assert-True ($fallback -notmatch '(?s)lunara-pairing-desk-section[^<]*<style') 'The Pairing Desk must not print its static component CSS inside Home HTML.'
Assert-True ($reviewComponents -match 'Pairing Desk showcase') 'The cacheable Review component bundle is missing the Pairing Desk showcase rules.'
Assert-True ($frontend -match "remove_action\(\s*'wp_enqueue_scripts'\s*,\s*'wp_enqueue_global_styles'\s*\)") 'Home must remove the WordPress core global-style callback before head rendering.'
Assert-True ($frontend -match "remove_action\(\s*'wp_footer'\s*,\s*'wp_enqueue_global_styles'\s*,\s*1\s*\)") 'Home must prevent WordPress core from printing global styles again in the footer.'
Assert-True ($frontend -match "add_action\(\s*'wp'\s*,\s*'lunara_disable_unused_home_global_styles'\s*,\s*0\s*\)") 'The Home global-style removal must run after query resolution and before head rendering.'
Assert-True ($frontend -match 'wp_dequeue_style\(\s*\$handle\s*\)') 'Home must dequeue only the proven-unused style handles through the scoped loop.'
Assert-True ($setup -match 'id="lunara-grain"') 'The split loader must emit static grain markup at wp_body_open.'
Assert-True ($setup -match 'id="lunara-vignette"') 'The split loader must emit static vignette markup at wp_body_open.'
Assert-True ($setup -match "add_action\(\s*'wp_body_open'\s*,\s*'lunara_inject_room_tone_markup'\s*,\s*1\s*\)") 'The split loader must register Room Tone markup before the public shell.'
Assert-True ($fallback -match 'id="lunara-grain"') 'The fallback loader must emit static grain markup.'
Assert-True (($setup + $fallback) -notmatch 'lunara-film-grain') 'The unused legacy grain node must stay removed.'
Assert-True ($style -match 'background-image:\s*url\("assets/images/lunara-grain\.svg"\)') 'Room Tone CSS must use the cacheable grain asset.'
Assert-True ($grain -match '<feTurbulence') 'The cacheable grain asset appears incomplete.'
Assert-True ($style -match 'Version:\s*3\.2\.1') 'Theme version must be 3.2.1 for the Home LCP priority-hygiene candidate.'

Write-Host "Performance payload budget contract passed (critical: $criticalBytes; shell: $shellBytes; public: $publicGuardrailBytes; home: $homeModuleBytes; review: $reviewComponentBytes; public JS: $publicRuntimeBytes; carousel JS: $scrollCarouselBytes; home JS: $homeRuntimeBytes; Oscars: $lateOscarsBytes; dynamic: $dynamicSignatureBytes; grain: $grainBytes bytes)."
