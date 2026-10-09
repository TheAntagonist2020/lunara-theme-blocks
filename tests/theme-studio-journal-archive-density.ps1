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
    'lunara_journal_archive_density',
    'lunara_journal_archive_lead_prominence',
    'lunara_journal_archive_desk_rhythm'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Journal Archive Studio must define the $key select control."
    Assert-True ($frontend -match [regex]::Escape("'$key'")) "Journal archive public CSS must read the $key setting."
}

foreach ($key in @(
    'lunara_journal_archive_section_gap',
    'lunara_journal_archive_hero_min_height',
    'lunara_journal_archive_card_min_height',
    'lunara_journal_archive_media_min_height'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Journal Archive Studio must define the $key numeric control."
    Assert-True ($frontend -match [regex]::Escape("'$key'")) "Journal archive public CSS must read the $key setting."
}

foreach ($option in @('compact','editorial','showcase','restrained','standard','feature','quick','balanced','immersive')) {
    Assert-True ($controlDesk -match [regex]::Escape("'$option'")) "Journal Archive Studio must support the $option option."
}

Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_journal_archive_studio') 'Theme Studio must render a Journal Archive Studio panel.'
Assert-True ($controlDesk -match 'admin_post_lunara_save_journal_archive_studio') 'Journal Archive Studio must save through a nonce-protected admin-post handler.'
Assert-True ($controlDesk -match 'check_admin_referer\(\s*''lunara_save_journal_archive_studio''') 'Journal Archive Studio save handler must verify a nonce.'
Assert-True ($controlDesk -match 'current_user_can\(\s*''edit_theme_options''') 'Journal Archive Studio must remain capability protected.'
Assert-True ($controlDesk -notmatch '<textarea[^>]+lunara_journal_archive') 'Journal Archive Studio must not expose raw CSS textareas.'

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
    Assert-True ($frontend.Contains($variable)) "Journal archive public CSS must emit $variable."
}

foreach ($selector in @(
    'body.post-type-archive-journal .lunara-journal-archive-page',
    'body.post-type-archive-journal .lunara-journal-archive-hero',
    'body.post-type-archive-journal .lunara-journal-archive-deskbar',
    'body.post-type-archive-journal .lunara-journal-archive-grid',
    'body.post-type-archive-journal .lunara-journal-archive-card',
    'body.post-type-archive-journal .lunara-journal-archive-retention'
)) {
    Assert-True ($frontend.Contains($selector)) "Journal archive CSS must stay scoped to $selector."
}

Assert-True ($frontend -match 'is_post_type_archive\(\s*''journal''\s*\)') 'Journal archive CSS must stay scoped to Journal archive routes.'
Assert-True ($frontend -match 'line-clamp') 'Journal archive density controls must tune text depth, not only spacing.'
Assert-True ($frontend -match 'lunara_output_journal_archive_media_guard_js') 'Journal archive must include a media failure guard.'
Assert-True ($frontend -match 'lunara-journal-archive-media-guard-js') 'Journal archive media guard must emit a named script for evidence.'
Assert-True ($frontend -match 'is-media-failed') 'Journal archive media guard must mark failed media cards.'
Assert-True ($frontend -match 'naturalWidth') 'Journal archive media guard must detect failed image decoding, not only missing markup.'

Write-Host 'Journal Archive Studio density contract passed.'
