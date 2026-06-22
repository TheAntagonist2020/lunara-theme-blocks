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
$debrief = Read-ThemeFile 'inc/debrief.php'
$adminCss = Read-ThemeFile 'assets/css/lunara-control-desk.css'

Assert-True ($debrief -match 'function\s+lunara_parse_pair_it_with_value') 'Pair It With source authority must reuse the existing parser contract.'
Assert-True ($debrief -match 'function\s+lunara_parse_pair_it_with_value\s*\(\s*\$value,\s*\$post_id\s*=\s*0,\s*\$resolve_poster\s*=\s*true\s*\)') 'Pair It With parser must support deferred poster resolution for admin list performance.'
Assert-True ($debrief -match '\$resolve_poster\s*&&\s*''''\s*!==\s*\$tt') 'Pair It With parser must skip poster lookup when deferred.'

Assert-True ($controlDesk -match "'review-pairing'") 'Image Quality Console must define a review-pairing surface.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_review_pairing_source_rows') 'Control Desk must build Review Pair It With source rows.'
Assert-True ($controlDesk -match 'lunara_parse_pair_it_with_value') 'Pairing source rows must reuse lunara_parse_pair_it_with_value().'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_review_pairing_source_rows\s*\(\s*\$limit\s*=\s*80,\s*\$resolve_posters\s*=\s*false\s*\)') 'Pairing source rows must default to lightweight deferred poster checks.'
Assert-True ($controlDesk -match 'lunara_parse_pair_it_with_value\s*\(\s*\$raw,\s*\$post->ID,\s*\$resolve_posters\s*\)') 'Pairing source rows must pass the deferred poster flag into the parser.'
Assert-True ($controlDesk -match 'poster_deferred') 'Pairing source rows must expose whether poster preview resolution was deferred.'
Assert-True ($controlDesk -match 'Poster preview deferred') 'Pairing source rows must explain deferred poster previews instead of showing a false missing-poster warning.'
Assert-True ($controlDesk -match 'lunara_get_career_context_meta') 'Pairing source rows must include Career Context using the current meta helper.'
Assert-True ($controlDesk -match 'Theme Echo') 'Pairing source rows must label Theme Echo.'
Assert-True ($controlDesk -match 'Counter-Program') 'Pairing source rows must label Counter-Program.'
Assert-True ($controlDesk -match 'Career Context') 'Pairing source rows must label Career Context.'

foreach ($field in @(
    'pairing_slot',
    'expected_title',
    'resolved_title',
    'imdb_title_id',
    'poster_html',
    'warnings'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$field'")) "Pairing rows must expose $field."
}

Assert-True ($controlDesk -match 'Missing IMDb title ID') 'Rows must flag missing IMDb IDs.'
Assert-True ($controlDesk -match 'No poster resolved') 'Rows must flag unresolved posters.'
Assert-True ($controlDesk -match 'Resolved title differs') 'Rows must flag resolved-title drift.'
Assert-True ($controlDesk -match 'Pairing source locked') 'Rows must mark clean locked pairings as ready.'

Assert-True ($controlDesk -match 'Pair It With sources') 'Image Quality Console must render a Pair It With sources lane label.'
Assert-True ($controlDesk -match 'Pairing source audit') 'Image Quality Console must render Pairing source audit row markers.'
Assert-True ($controlDesk -match 'lcd_iq_surface') 'Pairing lane must integrate through existing Image Quality surface filters.'
Assert-True ($controlDesk -match 'Pairing sources') 'Filter rail or priority lanes must expose Pairing sources.'
Assert-True ($controlDesk -match 'Pairing source backlog') 'Priority lanes must expose a Pairing source backlog shortcut.'

Assert-True ($controlDesk -match 'lunara-control-desk-pairing-source-preview') 'Pairing rows must render a dedicated poster preview chamber.'
Assert-True ($controlDesk -match 'lunara-control-desk-pairing-source-meta') 'Pairing rows must render pairing metadata separately from generic image dimensions.'
Assert-True ($controlDesk -match 'lunara_control_desk_render_pairing_source_row') 'Pairing rows must render through a dedicated read-only renderer.'
Assert-True ($controlDesk -match 'if\s*\(\s*''review-pairing''\s*===\s*\$surface\s*\)') 'Generic image-source mutation form must skip review-pairing rows.'

Assert-True ($adminCss -match 'lunara-control-desk-pairing-source-preview') 'Admin CSS must style Pair It With source previews.'
Assert-True ($adminCss -match 'lunara-control-desk-pairing-source-meta') 'Admin CSS must style Pair It With source metadata.'

Assert-True ($controlDesk -notmatch 'name="lunara_image_source_surface"\s+value="review-pairing"') 'Pairing source rows must not expose the generic image source save form.'

Write-Host 'Theme Studio Review Pair It With source authority contract passed.'
