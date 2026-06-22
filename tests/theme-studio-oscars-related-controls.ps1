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
    'lunara_oscars_related_reviews_treatment',
    'lunara_oscars_title_image_focus'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Oscars Dossier Studio must define the $key select control."
    Assert-True ($frontend -match [regex]::Escape("'$key'")) "Oscars Dossier CSS must read the $key setting."
}

Assert-True ($controlDesk -match [regex]::Escape("'lunara_oscars_related_reviews_count'")) 'Oscars Dossier Studio must define related-review count.'
Assert-True ($frontend -match [regex]::Escape("'lunara_oscars_related_reviews_count'")) 'Oscars Dossier CSS must read related-review count.'

foreach ($option in @(
    'standard-grid',
    'compact-rail',
    'feature-strip',
    'center-center',
    'center-top',
    'center-bottom',
    'left-center',
    'right-center'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$option'")) "Oscars Dossier Studio must support $option."
}

foreach ($preset in @(
    'historical-dossier',
    'ceremony-feature',
    'compact-ledger',
    'profile-spotlight'
)) {
    $pattern = "'$preset'[\s\S]+lunara_oscars_related_reviews_treatment[\s\S]+lunara_oscars_title_image_focus[\s\S]+lunara_oscars_related_reviews_count"
    Assert-True ($controlDesk -match $pattern) "Preset $preset must include the new related-review controls."
}

Assert-True ($controlDesk -match 'lunara_oscars_related_reviews_count[\s\S]+''min''\s*=>\s*2[\s\S]+''max''\s*=>\s*8') 'Related-review count must clamp from 2 to 8.'
Assert-True ($frontend -match '--lunara-oscars-related-review-min') 'Oscars CSS must emit related-review minimum width.'
Assert-True ($frontend -match '--lunara-oscars-image-focus') 'Oscars CSS must emit image focus.'
Assert-True ($frontend -match 'object-position:\s*var\(--lunara-oscars-image-focus\)') 'Oscars images must use selected object-position.'
Assert-True ($frontend -match 'aat-related-treatment-compact-rail') 'Oscars CSS must style compact rail treatment.'
Assert-True ($frontend -match 'aat-related-treatment-feature-strip') 'Oscars CSS must style feature strip treatment.'
Assert-True ($frontend -notmatch '<textarea[^>]+lunara_oscars') 'Oscars Dossier Studio must not expose raw CSS textareas.'

Write-Host 'Oscars related controls theme contract passed.'

