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
    'lunara_oscars_profile_media_treatment',
    'lunara_oscars_profile_media_width',
    'lunara_oscars_profile_media_height'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Oscars Dossier Studio must define $key."
    Assert-True ($frontend -match [regex]::Escape("'$key'")) "Oscars Dossier public CSS must read $key."
}

foreach ($option in @(
    'poster-frame',
    'cinematic-crop',
    'archival-fit'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$option'")) "Oscars Dossier Studio must support $option."
}

foreach ($preset in @(
    'historical-dossier',
    'ceremony-feature',
    'compact-ledger',
    'profile-spotlight'
)) {
    $pattern = "'$preset'[\s\S]+lunara_oscars_profile_media_treatment[\s\S]+lunara_oscars_profile_media_width[\s\S]+lunara_oscars_profile_media_height"
    Assert-True ($controlDesk -match $pattern) "Preset $preset must include the profile image controls."
}

Assert-True ($controlDesk -match 'lunara_oscars_profile_media_width[\s\S]+''min''\s*=>\s*220[\s\S]+''max''\s*=>\s*520') 'Profile media width must clamp from 220 to 520.'
Assert-True ($controlDesk -match 'lunara_oscars_profile_media_height[\s\S]+''min''\s*=>\s*320[\s\S]+''max''\s*=>\s*700') 'Profile media height must clamp from 320 to 700.'

foreach ($variable in @(
    '--lunara-oscars-profile-media-width',
    '--lunara-oscars-profile-media-height',
    '--lunara-oscars-profile-media-fit',
    '--lunara-oscars-profile-media-aspect'
)) {
    Assert-True ($frontend.Contains($variable)) "Oscars Dossier public CSS must emit $variable."
}

foreach ($selector in @(
    'body.aat-shell-page .aat-container.aat-profile-file .aat-entity-hero .aat-entity-poster-wrap',
    'body.aat-shell-page .aat-profile-file .aat-entity-poster-wrap',
    'body.aat-shell-page .aat-container.aat-profile-file .aat-entity-hero .aat-entity-poster-wrap img',
    'body.aat-shell-page .aat-profile-file .aat-entity-poster-wrap img',
    'body.aat-shell-page .aat-profile-file .aat-entity-poster',
    'body.aat-shell-page .aat-profile-file .aat-entity-portrait'
)) {
    Assert-True ($frontend.Contains($selector)) "Profile image CSS must stay scoped to $selector."
}

Assert-True ($frontend -match 'object-fit:\s*var\(--lunara-oscars-profile-media-fit\)') 'Profile media must use the selected object-fit variable.'
Assert-True ($frontend -match 'object-position:\s*var\(--lunara-oscars-image-focus\)') 'Profile media must keep using selected image focus.'
Assert-True ($frontend -notmatch '<textarea[^>]+lunara_oscars') 'Oscars Dossier Studio must not expose raw CSS textareas.'

Write-Host 'Oscars profile image controls theme contract passed.'
