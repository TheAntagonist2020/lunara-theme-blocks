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
$studio = Read-ThemeFile 'inc/journal-archive-studio.php'
$critical = Read-ThemeFile 'inc/journal-archive-critical.php'
$routeCss = Read-ThemeFile 'assets/css/lunara-journal-archive.css'

foreach ($key in @(
    'lunara_journal_archive_density',
    'lunara_journal_archive_lead_prominence',
    'lunara_journal_archive_desk_rhythm'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Journal Archive Studio must define the $key select control."
    Assert-True ($studio -match [regex]::Escape("get_theme_mod( '$key'")) "The composite public resolver must preserve the legacy $key owner."
    Assert-True ($studio -match [regex]::Escape("set_theme_mod( '$key'")) "Focused Studio saves must write through to the canonical $key owner."
}

foreach ($key in @(
    'lunara_journal_archive_section_gap',
    'lunara_journal_archive_hero_min_height',
    'lunara_journal_archive_card_min_height',
    'lunara_journal_archive_media_min_height'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Journal Archive Studio must define the $key numeric control."
    Assert-True ($studio -match [regex]::Escape("get_theme_mod( '$key'")) "The composite public resolver must preserve the legacy $key owner."
    Assert-True ($studio -match [regex]::Escape("set_theme_mod( '$key'")) "Focused Studio saves must write through to the canonical $key owner."
}

foreach ($option in @('compact','editorial','showcase','restrained','standard','feature','quick','balanced','immersive')) {
    Assert-True ($controlDesk -match [regex]::Escape("'$option'")) "Journal Archive Studio must support the $option option."
}

Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_journal_archive_studio[\s\S]{0,220}?lunara_journal_archive_studio_render_control_surface') 'Theme Studio must delegate to the focused Journal Archive owner.'
Assert-True ($studio -match 'admin_post_lunara_save_journal_archive_studio') 'Journal Archive Studio must save through a nonce-protected admin-post handler.'
Assert-True ($studio -match 'check_admin_referer\(\s*''lunara_save_journal_archive_studio''') 'Journal Archive Studio save handler must verify a nonce.'
Assert-True ($studio -match 'current_user_can\(\s*''edit_theme_options''') 'Journal Archive Studio must remain capability protected.'
Assert-True ($studio -notmatch '<textarea[^>]+lunara_journal_archive[^>]+(?:css|style)') 'Journal Archive Studio must not expose raw CSS textareas.'

foreach ($variable in @(
    '--lunara-journal-archive-section-gap',
    '--lunara-journal-archive-shell-gap',
    '--lunara-journal-archive-hero-min',
    '--lunara-journal-archive-card-min',
    '--lunara-journal-archive-media-min',
    '--lunara-journal-archive-grid-gap',
    '--lunara-journal-archive-excerpt-clamp',
    '--lunara-journal-archive-retention-gap'
)) {
    Assert-True ($critical.Contains($variable)) "The synchronous Journal geometry composer must emit $variable."
}

foreach ($selector in @(
    '#primary.lunara-journal-archive-page',
    '.lunara-journal-archive-slot-hero',
    '.lunara-journal-archive-slot-deskbar',
    '.lunara-journal-archive-slot-grid',
    '.lunara-journal-archive-card',
    '.lunara-journal-archive-slot-retention'
)) {
    Assert-True ($routeCss.Contains($selector)) "Journal archive route CSS must own $selector."
}

Assert-True ($frontend -match 'is_post_type_archive\(\s*''journal''\s*\)') 'Journal archive delivery must stay scoped to Journal archive routes.'
Assert-True ($routeCss -match 'line-clamp') 'Journal archive density controls must tune text depth, not only spacing.'
Assert-True ($routeCss -notmatch 'body\.post-type-archive-journal') 'Route geometry must use the renderer-owned #primary marker instead of fragile body-class scope.'
Assert-True ($frontend -match 'lunara_output_journal_archive_media_guard_js') 'Journal archive must include a media failure guard.'
Assert-True ($frontend -match 'lunara-journal-archive-media-guard-js') 'Journal archive media guard must emit a named script for evidence.'
Assert-True ($frontend -match 'is-media-failed') 'Journal archive media guard must mark failed media cards.'
Assert-True ($frontend -match 'naturalWidth') 'Journal archive media guard must detect failed image decoding, not only missing markup.'

Write-Host 'Journal Archive Studio density contract passed.'
