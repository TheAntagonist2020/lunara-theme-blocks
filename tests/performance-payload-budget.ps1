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
$grainBytes = [Text.Encoding]::UTF8.GetByteCount($grain)
Assert-True ($criticalBytes -le 12288) "Critical shell CSS exceeds its 12 KB budget: $criticalBytes bytes."
Assert-True ($shellBytes -le 204800) "Cacheable shell CSS exceeds its 200 KB transition budget: $shellBytes bytes."
Assert-True ($publicGuardrailBytes -le 61440) "Public guardrail CSS exceeds its 60 KB budget: $publicGuardrailBytes bytes."
Assert-True ($homeModuleBytes -le 61440) "Homepage module CSS exceeds its 60 KB budget: $homeModuleBytes bytes."
Assert-True ($lateOscarsBytes -le 20480) "Late Oscars CSS exceeds its 20 KB budget: $lateOscarsBytes bytes."
Assert-True ($grainBytes -le 2048) "Grain texture exceeds its 2 KB budget: $grainBytes bytes."
Assert-True ($shell -notmatch '<\?php') 'The cacheable shell stylesheet must remain static CSS.'
Assert-True ($publicGuardrails -notmatch '<\?php') 'Public guardrails must remain static CSS.'
Assert-True ($homeModules -notmatch '<\?php') 'Homepage modules must remain static CSS.'
Assert-True ($lateOscars -notmatch '<\?php') 'Late Oscars guardrails must remain static CSS.'
Assert-True ($shell -match 'body\.home \.lunara-front-page > \.lunara-home-section') 'The cacheable shell stylesheet appears incomplete.'
Assert-True ($publicGuardrails -match 'body\.home \.lunara-journal-home-grid') 'The public guardrail stylesheet appears incomplete.'
Assert-True ($homeModules -match 'body\.home \.lunara-oscar-facts-section') 'The homepage module stylesheet appears incomplete.'
Assert-True ($lateOscars -match 'body\.aat-shell-page \.aat-container') 'The late Oscars stylesheet appears incomplete.'
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
Assert-True ($setup -match "add_action\(\s*'wp_footer'\s*,\s*'lunara_print_home_module_styles'\s*,\s*142\s*\)") 'Homepage modules must retain their late footer cascade position.'
Assert-True ($setup -match "add_action\(\s*'wp_footer'\s*,\s*'lunara_print_late_oscars_guardrail_styles'\s*,\s*999\s*\)") 'Late Oscars guardrails must remain the final route safeguard.'

$dynamicSignature = [regex]::Match(
    $frontend,
    '(?s)<style id="lunara-homepage-studio-signature-css">.*?</style>'
)
Assert-True $dynamicSignature.Success 'The request-specific homepage variable block must remain present.'
$dynamicSignatureBytes = [Text.Encoding]::UTF8.GetByteCount($dynamicSignature.Value)
Assert-True ($dynamicSignatureBytes -le 4096) "Homepage variable CSS exceeds its 4 KB budget: $dynamicSignatureBytes bytes."
Assert-True ($dynamicSignature.Value -match '--lunara-home-section-gap') 'Homepage variable CSS is missing its primary custom property.'

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
Assert-True ($setup -match 'id="lunara-grain"') 'The split loader must emit static grain markup at wp_body_open.'
Assert-True ($setup -match 'id="lunara-vignette"') 'The split loader must emit static vignette markup at wp_body_open.'
Assert-True ($setup -match "add_action\(\s*'wp_body_open'\s*,\s*'lunara_inject_room_tone_markup'\s*,\s*1\s*\)") 'The split loader must register Room Tone markup before the public shell.'
Assert-True ($fallback -match 'id="lunara-grain"') 'The fallback loader must emit static grain markup.'
Assert-True (($setup + $fallback) -notmatch 'lunara-film-grain') 'The unused legacy grain node must stay removed.'
Assert-True ($style -match 'background-image:\s*url\("assets/images/lunara-grain\.svg"\)') 'Room Tone CSS must use the cacheable grain asset.'
Assert-True ($grain -match '<feTurbulence') 'The cacheable grain asset appears incomplete.'
Assert-True ($style -match 'Version:\s*3\.1\.96') 'Theme version must be 3.1.96 for the corrected Phase 1B staging release.'

Write-Host "Performance payload budget contract passed (critical: $criticalBytes; shell: $shellBytes; public: $publicGuardrailBytes; home: $homeModuleBytes; Oscars: $lateOscarsBytes; dynamic: $dynamicSignatureBytes; grain: $grainBytes bytes)."
