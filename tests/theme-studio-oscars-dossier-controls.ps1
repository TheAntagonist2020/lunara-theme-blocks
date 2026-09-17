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

$controlDesk = Read-ThemeFile 'inc/control-desk.php'
$frontend = Read-ThemeFile 'inc/frontend.php'
$adminCss = Read-ThemeFile 'assets/css/lunara-control-desk.css'

foreach ($key in @(
    'lunara_oscars_dossier_preset',
    'lunara_oscars_dossier_density',
    'lunara_oscars_ceremony_rhythm',
    'lunara_oscars_major_race_prominence',
    'lunara_oscars_profile_scale',
    'lunara_oscars_writeup_prominence'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Oscars Dossier Studio must define the $key select control."
    Assert-True ($frontend -match [regex]::Escape("'$key'")) "Oscars Dossier public CSS must read the $key setting."
}

foreach ($key in @(
    'lunara_oscars_dossier_section_gap',
    'lunara_oscars_dossier_card_min'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Oscars Dossier Studio must define the $key numeric control."
    Assert-True ($frontend -match [regex]::Escape("'$key'")) "Oscars Dossier public CSS must read the $key setting."
}

foreach ($preset in @(
    'historical-dossier',
    'ceremony-feature',
    'compact-ledger',
    'profile-spotlight'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$preset'")) "Oscars Dossier Studio must include the $preset preset."
}

foreach ($option in @(
    'balanced',
    'dense',
    'showcase',
    'editorial',
    'ledger',
    'standard',
    'feature',
    'compact',
    'cinematic',
    'inline'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$option'")) "Oscars Dossier Studio must support the $option option."
}

Assert-True ($controlDesk -match 'function\s+lunara_control_desk_oscars_dossier_preset_specs') 'Oscars Dossier Studio must define named preset specs.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_oscars_dossier_studio') 'Theme Studio must render an Oscars Dossier Studio panel.'
Assert-True ($controlDesk -match 'lunara_control_desk_render_oscars_dossier_studio\(\)') 'Theme Studio tab must call the Oscars Dossier Studio renderer.'
Assert-True ($controlDesk -match 'admin_post_lunara_save_oscars_dossier_studio') 'Oscars Dossier Studio must save through a nonce-protected admin-post handler.'
Assert-True ($controlDesk -match 'check_admin_referer\(\s*''lunara_save_oscars_dossier_studio''') 'Oscars Dossier Studio save handler must verify a nonce.'
Assert-True ($controlDesk -match 'current_user_can\(\s*''edit_theme_options''') 'Oscars Dossier Studio must remain capability protected.'
Assert-True ($controlDesk -match 'name="lunara_oscars_dossier_preset"') 'Oscars Dossier Studio must render preset apply buttons.'
Assert-True ($controlDesk -match 'oscars_dossier_preset_applied') 'Oscars Dossier Studio must provide a preset-applied notice.'
Assert-True ($controlDesk -match 'add_query_arg\(\s*''lunara-oscars-preset''') 'Oscars Dossier Studio must render admin-only preset preview links.'
Assert-True ($controlDesk -match 'isset\(\s*\$presets\[\s*\$preset_key\s*\]\s*\)') 'Oscars Dossier Studio must reject invalid preset keys before applying values.'
Assert-True ($controlDesk -notmatch '<textarea[^>]+lunara_oscars') 'Oscars Dossier Studio must not expose raw CSS textareas.'

foreach ($preview in @(
    "home_url( '/oscars/' )",
    "home_url( '/oscars/ceremony/98/' )",
    "home_url( '/oscars/category/best-picture/' )"
)) {
    Assert-True ($controlDesk.Contains($preview)) "Oscars Dossier Studio must include preview URL $preview."
}
Assert-True ($controlDesk -match "add_query_arg\(\s*'lunara-width'\s*,\s*'390'") 'Oscars Dossier Studio must include 390px mobile preview links.'

foreach ($variable in @(
    '--lunara-oscars-dossier-section-gap',
    '--lunara-oscars-dossier-card-min',
    '--lunara-oscars-dossier-density-scale',
    '--lunara-oscars-dossier-hero-max',
    '--lunara-oscars-profile-media-max',
    '--lunara-oscars-writeup-max'
)) {
    Assert-True ($frontend.Contains($variable)) "Oscars Dossier public CSS must emit $variable."
}

foreach ($selector in @(
    'body.aat-shell-page .aat-container',
    'body.aat-shell-page .aat-ceremony-dossier',
    'body.aat-shell-page .aat-category-dossier',
    'body.aat-shell-page .aat-profile-file',
    'body.aat-shell-page .aat-ceremony-editorial-writeup',
    'body.aat-shell-page .aat-ceremony-major-races'
)) {
    Assert-True ($frontend.Contains($selector)) "Oscars Dossier public CSS must stay scoped to $selector."
}

Assert-True ($frontend -match 'function\s+lunara_output_oscars_dossier_studio_css') 'Oscars Dossier Studio must emit a named public CSS function.'
Assert-True ($frontend -match 'add_action\(\s*''wp_head''\s*,\s*''lunara_output_oscars_dossier_studio_css''') 'Oscars Dossier public CSS must be hooked through wp_head.'
Assert-True ($frontend -match 'function\s+lunara_get_oscars_dossier_preview_preset_values') 'Oscars Dossier frontend CSS must expose a request-local preview preset reader.'
Assert-True ($frontend -match '\$_GET\[\s*''lunara-oscars-preset''\s*\]') 'Oscars Dossier preview override must read the lunara-oscars-preset query key.'
Assert-True ($frontend -match 'current_user_can\(\s*''edit_theme_options''\s*\)[\s\S]*\$_GET\[\s*''lunara-oscars-preset''\s*\]') 'Oscars Dossier preview override must be gated to theme editors.'
Assert-True ($frontend -match 'lunara_control_desk_oscars_dossier_preset_specs') 'Oscars Dossier frontend preview must reuse the same preset specs as Theme Studio.'
Assert-True ($frontend -match 'is_page\(\s*''oscars''\s*\)[\s\S]*strpos\(\s*\$request_path,\s*''/oscars/''') 'Oscars Dossier CSS must stay scoped to Oscars surfaces.'
Assert-True ($frontend -notmatch 'body\.aat-shell-page\s+\.aat-era-chapter,\s*[\r\n]+\s*body\.aat-shell-page\s+\.aat-winner-circle-grid[\s\S]*grid-template-columns:\s*repeat\(auto-fit') 'Oscars Dossier Studio must not force era chapter sections into auto-fit card grids.'

foreach ($class in @(
    '.lunara-control-desk-oscars-dossier-studio',
    '.lunara-control-desk-oscars-grid',
    '.lunara-control-desk-oscars-preset-grid',
    '.lunara-control-desk-oscars-preview-grid'
)) {
    Assert-True ($adminCss.Contains($class)) "Oscars Dossier admin CSS must define $class."
}

Write-Host 'Oscars Dossier Studio controls contract passed.'
