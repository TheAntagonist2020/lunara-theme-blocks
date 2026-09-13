$ErrorActionPreference = 'Stop'
$themeRoot = Split-Path -Parent $PSScriptRoot
function Assert-True([bool] $Condition, [string] $Message) { if (-not $Condition) { throw $Message } }
$controlDesk = Get-Content -Raw -LiteralPath (Join-Path $themeRoot 'inc/control-desk.php')
$provider = Get-Content -Raw -LiteralPath (Join-Path $themeRoot 'inc/site-studio-utility-recovery.php')
$workspace = Get-Content -Raw -LiteralPath (Join-Path $themeRoot 'inc/site-studio.php')
$panel = [regex]::Match($controlDesk, '(?ms)^function lunara_control_desk_render_utility_search_studio\b.*?(?=^function |\z)').Value
Assert-True ($panel.Length -gt 0) 'The existing Utility panel anchor must remain available.'
Assert-True ($panel.Contains('surface=utility-search') -and $panel.Contains('surface=utility-404')) 'The old comparison panel must hand off to the two current editors.'
Assert-True ($panel -notmatch '<form|render_utility_search_preset_card\(|render_utility_search_preset_comparison_strip\(') 'The handoff must not expose competing preset Apply or comparison controls.'

# The same choices remain editable as individual, previewable fields in Site Studio.
foreach ($path in @('presentation.density', 'presentation.result_treatment', 'presentation.result_media', 'presentation.recovery_prominence', 'focus.lead', 'focus.spotlight', 'geometry.section_gap', 'geometry.result_min_height', 'geometry.card_grid_min')) {
    Assert-True ($workspace.Contains("'$path'")) "Shared Search must retain the former comparison setting $path."
}
foreach ($field in @('kicker', 'no_query_title', 'excerpt_words', 'use_empty_title')) {
    Assert-True ($workspace.Contains("'content.$field'")) "Shared Search must also expose content.$field."
}
Assert-True ($provider.Contains("'mod' => 'lunara_utility_reentry_primary'")) 'The former404 primary comparison choice must remain in the independent404 editor.'
Assert-True ($workspace.Contains('data-search-preview-case="results"') -and $workspace.Contains('data-search-preview-case="start"')) 'A candidate must be inspectable in both real Search cases.'
Assert-True ($workspace.Contains('lunara_site_studio_render_revisions( $revisions )')) 'Shared comparison edits must retain History.'
Assert-True ($workspace.Contains('esc_html') -and $workspace.Contains('esc_attr')) 'Shared control labels and values must remain escaped.'
Assert-True ($controlDesk -notmatch 'lunara-utility-compare') 'Retirement must not invent a public comparison query.'
Write-Host 'Utility comparison migration retains all independent shared controls.'
