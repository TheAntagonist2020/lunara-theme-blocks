$ErrorActionPreference = 'Stop'
$themeRoot = Split-Path -Parent $PSScriptRoot
function Assert-True([bool] $Condition, [string] $Message) { if (-not $Condition) { throw $Message } }
$controlDesk = Get-Content -Raw -LiteralPath (Join-Path $themeRoot 'inc/control-desk.php')
$frontend = Get-Content -Raw -LiteralPath (Join-Path $themeRoot 'inc/frontend.php')
$preview = Get-Content -Raw -LiteralPath (Join-Path $themeRoot 'inc/site-studio-preview.php')
$provider = Get-Content -Raw -LiteralPath (Join-Path $themeRoot 'inc/site-studio-utility-recovery.php')
$writer = [regex]::Match($controlDesk, '(?ms)^function lunara_control_desk_apply_utility_search_values\b.*?(?=^function |\z)').Value
$handler = [regex]::Match($controlDesk, '(?ms)^function lunara_control_desk_save_utility_search_studio\b.*?(?=^function |\z)').Value
Assert-True ($writer -match 'return false;' -and $writer -notmatch 'set_theme_mod|remove_theme_mod|update_option|apply_mod_snapshot') 'Direct legacy preset Apply must be inert even without the new provider.'
Assert-True ($handler -match 'check_admin_referer' -and $handler.Contains('surface=utility-search')) 'Stale forms must validate their nonce and return to the shared owner.'
Assert-True ($handler -notmatch 'set_theme_mod|remove_theme_mod|apply_utility_search_values|update_option') 'Stale forms must not write any Search or404 setting.'

# Existing authenticated request-only links remain compatible; they are no longer active editors.
foreach ($preset in @('balanced-desk', 'ledger-signal', 'criticism-run', 'journal-desk', 'navigation-clean')) {
    Assert-True ($controlDesk.Contains("'$preset'")) "Retained old request-only links must still resolve $preset."
}
Assert-True ($frontend -match 'function\s+lunara_get_utility_search_preview_preset_values') 'The existing read-only preset resolver must remain available.'
Assert-True ($frontend.Contains("`$_GET['lunara-utility-preset']") -and $frontend -match "current_user_can\(\s*'edit_theme_options'\s*\)") 'Legacy request-only previews retain their authenticated boundary.'
Assert-True ($frontend.Contains('lunara_get_utility_search_studio_select_value') -and $frontend.Contains('lunara_get_utility_search_studio_number_value')) 'Public CSS must continue reading canonical preview-aware settings.'
Assert-True ($provider.Contains('lunara_site_studio_utility_search_legacy_preview_state_valid') -and $provider.Contains('lunara_site_studio_utility_search_install_legacy_preview_state')) 'Exact old private tokens require a separate frozen-schema read path.'
Assert-True ($preview.Contains('lunara_site_studio_utility_404_preview_request_valid')) '404 private previews must require a genuine missing route.'
Assert-True ($provider.Contains("'results' => array( 'q' => 'Lunara' ), 'start' => array()")) 'Search preview cases must be fixed, with a truly empty start query.'
& node (Join-Path $PSScriptRoot 'site-studio-utility-recovery-http-runtime.js')
Assert-True ($LASTEXITCODE -eq 0) 'Actual HTTP guard/router/template status, token isolation and no-store checks must pass.'
Write-Host 'Utility private preview and retired preset contracts passed.'
