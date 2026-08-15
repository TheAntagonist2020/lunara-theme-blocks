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

foreach ($key in @(
    'lunara_reviews_archive_density',
    'lunara_reviews_archive_lead_prominence',
    'lunara_reviews_archive_rail_density'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Reviews Archive Studio must define the $key select control."
    Assert-True ($frontend -match [regex]::Escape("'$key'")) "Reviews archive public CSS must read the $key setting."
}

foreach ($key in @(
    'lunara_reviews_archive_section_gap',
    'lunara_reviews_archive_lead_min_height',
    'lunara_reviews_archive_card_min_height',
    'lunara_reviews_archive_compact_media_width'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Reviews Archive Studio must define the $key numeric control."
    Assert-True ($frontend -match [regex]::Escape("'$key'")) "Reviews archive public CSS must read the $key setting."
}

foreach ($option in @('compact','editorial','showcase','restrained','standard','feature')) {
    Assert-True ($controlDesk -match [regex]::Escape("'$option'")) "Reviews Archive Studio must support the $option option."
}

Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_reviews_archive_studio') 'Theme Studio must render a Reviews Archive Studio panel.'
Assert-True ($controlDesk -match 'admin_post_lunara_save_reviews_archive_studio') 'Reviews Archive Studio must save through a nonce-protected admin-post handler.'
Assert-True ($controlDesk -match 'check_admin_referer\(\s*''lunara_save_reviews_archive_studio''') 'Reviews Archive Studio save handler must verify a nonce.'
Assert-True ($controlDesk -match 'current_user_can\(\s*''edit_theme_options''') 'Reviews Archive Studio must remain capability protected.'
Assert-True ($controlDesk -notmatch '<textarea[^>]+lunara_reviews_archive') 'Reviews Archive Studio must not expose raw CSS textareas.'

foreach ($variable in @(
    '--lunara-reviews-archive-section-gap',
    '--lunara-reviews-archive-shell-gap',
    '--lunara-reviews-archive-lead-min',
    '--lunara-reviews-archive-lead-media-min',
    '--lunara-reviews-archive-lead-copy-pad',
    '--lunara-reviews-archive-card-min',
    '--lunara-reviews-archive-compact-media-width',
    '--lunara-reviews-archive-rail-gap',
    '--lunara-reviews-archive-excerpt-clamp'
)) {
    Assert-True ($frontend.Contains($variable)) "Reviews archive public CSS must emit $variable."
}

foreach ($selector in @(
    'body.post-type-archive-review .lunara-review-archive-page',
    'body.page-template-page-reviews .lunara-review-archive-page',
    '.lunara-review-archive-shell',
    '.lunara-review-feature-card.is-lead',
    '.lunara-review-archive-dynamic-rail',
    '.lunara-review-archive-run-grid'
)) {
    Assert-True ($frontend.Contains($selector)) "Reviews archive CSS must stay scoped to $selector."
}

Assert-True ($frontend -match 'is_post_type_archive\(\s*''review''\s*\)') 'Reviews archive CSS must stay scoped to Review archive routes.'
Assert-True ($frontend -match 'line-clamp') 'Reviews archive density controls must tune text depth, not only spacing.'

Write-Host 'Reviews Archive Studio density contract passed.'
