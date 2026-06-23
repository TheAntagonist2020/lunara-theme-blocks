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
$adminCss = Read-ThemeFile 'assets/css/lunara-control-desk.css'
$reviewRendering = Read-ThemeFile 'inc/review-rendering.php'
$trailers = Read-ThemeFile 'inc/trailers.php'
$debrief = Read-ThemeFile 'inc/debrief.php'

Assert-True ($reviewRendering -match 'function\s+lunara_is_full_spoiler_review') 'Retention health must be able to reuse the existing full-spoiler helper.'
Assert-True ($reviewRendering -match 'function\s+lunara_get_linked_spoiler_review') 'Retention health must be able to reuse the existing linked-spoiler helper.'
Assert-True ($reviewRendering -match 'function\s+lunara_get_related_review_posts') 'Retention health must be able to reuse the existing related-review helper.'
Assert-True ($reviewRendering -match 'function\s+lunara_get_review_imdb_title_id') 'Retention health must be able to reuse the existing Review IMDb helper.'
Assert-True ($trailers -match 'function\s+lunara_post_has_trailer') 'Retention health must be able to reuse the existing trailer helper.'
Assert-True ($debrief -match 'function\s+lunara_parse_pair_it_with_value') 'Retention health must be able to reuse the existing Pair It With parser.'
Assert-True ($debrief -match 'function\s+lunara_get_oscar_ledger_counts') 'Retention health must be able to reuse the existing Oscar Ledger count helper.'

foreach ($functionName in @(
    'lunara_control_desk_review_retention_rows',
    'lunara_control_desk_review_retention_row',
    'lunara_control_desk_review_retention_debrief_signal',
    'lunara_control_desk_review_retention_pairing_signal',
    'lunara_control_desk_review_retention_overall_state',
    'lunara_control_desk_render_review_retention_console'
)) {
    Assert-True ($controlDesk -match "function\s+$functionName") "Control Desk must define $functionName."
}

foreach ($helper in @(
    'lunara_post_has_trailer',
    'lunara_is_full_spoiler_review',
    'lunara_get_linked_spoiler_review',
    'lunara_parse_pair_it_with_value',
    'lunara_get_related_review_posts',
    'lunara_get_review_imdb_title_id',
    'lunara_get_oscar_ledger_counts'
)) {
    Assert-True ($controlDesk -match $helper) "Retention console must reference $helper."
}

foreach ($label in @(
    'Review Retention Health',
    'Retention package audit',
    'Trailer',
    'Spoiler',
    'Debrief',
    'Pair It With',
    'Related reviews',
    'Oscar Ledger',
    'Ready',
    'Watch',
    'Needs Work'
)) {
    Assert-True ($controlDesk -match [regex]::Escape($label)) "Retention console must render the '$label' label."
}

foreach ($field in @(
    'trailer',
    'spoiler',
    'debrief',
    'pairing',
    'related',
    'oscar',
    'overall'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$field'")) "Retention row must expose the $field signal."
}

Assert-True ($controlDesk -match 'lunara_control_desk_render_review_retention_console\(\)') 'Theme Studio must call the Review Retention Health renderer.'
Assert-True ($controlDesk -match "'post_type'\s*=>\s*'review'") 'Retention rows must query Review posts only.'
Assert-True ($controlDesk -match "'post_status'\s*=>\s*array\(") 'Retention rows must include intentional Review statuses.'
Assert-True ($controlDesk -match 'posts_per_page') 'Retention rows must cap the admin query for performance.'
Assert-True ($controlDesk -match 'esc_url\(\s*get_edit_post_link') 'Retention rows must expose safe edit links.'
Assert-True ($controlDesk -match 'esc_url\(\s*get_permalink') 'Retention rows must expose safe public preview links.'
Assert-True ($controlDesk -match 'No recent Review posts found') 'Retention console must render a deliberate empty state.'

foreach ($cssClass in @(
    'lunara-control-desk-retention-health',
    'lunara-control-desk-retention-row',
    'lunara-control-desk-retention-chip',
    'lunara-control-desk-retention-chip--ready',
    'lunara-control-desk-retention-chip--watch',
    'lunara-control-desk-retention-chip--needs-work'
)) {
    Assert-True ($controlDesk -match $cssClass -or $adminCss -match $cssClass) "Retention console must include $cssClass."
}

Assert-True ($controlDesk -notmatch 'admin_post_lunara_save_review_retention') 'Retention console must not register a save handler.'
Assert-True ($controlDesk -notmatch 'name="lunara_review_retention') 'Retention console must not expose mutable form controls.'
Assert-True ($controlDesk -notmatch 'update_post_meta\([^;]+review_retention') 'Retention console must not save Review retention metadata.'
Assert-True ($controlDesk -notmatch 'set_theme_mod\([^;]+review_retention') 'Retention console must not save theme mods.'
Assert-True ($controlDesk -notmatch 'update_option\([^;]+review_retention') 'Retention console must not save options.'

Write-Host 'Theme Studio Review Retention Health Console contract passed.'
