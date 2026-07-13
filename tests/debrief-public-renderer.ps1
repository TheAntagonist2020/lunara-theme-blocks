$ErrorActionPreference = 'Stop'

$themeRoot = Split-Path -Parent $PSScriptRoot
$fixture = Join-Path $PSScriptRoot 'fixtures/debrief-public-renderer-harness.php'

function Assert-True {
    param(
        [bool] $Condition,
        [string] $Message
    )

    if (-not $Condition) {
        throw $Message
    }
}

Assert-True (Test-Path $fixture) 'Missing public Debrief renderer harness.'

$json = & php $fixture
Assert-True ($LASTEXITCODE -eq 0) 'Public Debrief renderer harness failed.'
$result = $json | ConvertFrom-Json

Assert-True ([bool] $result.production_locked) 'Production staging guard was not verified.'
Assert-True ([bool] $result.switch_off_zero) 'Switch-off zero canonical-call behavior was not verified.'
Assert-True ([bool] $result.canonical_atomic) 'Canonical atomic renderer was not verified.'
Assert-True ([int] $result.roles_rendered -eq 3) 'Canonical renderer must output exactly three roles.'
Assert-True ([int] $result.resolver_calls -eq 4) 'Canonical renderer must resolve one source and three companions.'
Assert-True ([bool] $result.cache_verified) 'Request cache behavior was not verified.'
Assert-True ([bool] $result.late_failure_legacy) 'Late resolver failure must produce whole legacy fallback.'
Assert-True ([bool] $result.partial_no_mix) 'Partial canonical data must not mix with legacy data.'
Assert-True ([bool] $result.empty_status_legacy) 'Empty canonical status must preserve whole legacy fallback.'
Assert-True ([int] $result.remote_calls -eq 0) 'Canonical renderer must make zero remote calls.'
Assert-True ([bool] $result.poster_fallback) 'Controlled missing-poster fallback was not verified.'
Assert-True ([bool] $result.oscar_independent) 'Canonical renderer must remain independent of Oscar data and helpers.'

$renderer = Get-Content -Raw (Join-Path $themeRoot 'inc/debrief-public.php')
$loader = Get-Content -Raw (Join-Path $themeRoot 'functions-loader.php')
$singleReview = Get-Content -Raw (Join-Path $themeRoot 'single-review.php')
$blocks = Get-Content -Raw (Join-Path $themeRoot 'inc/blocks.php')
$style = Get-Content -Raw (Join-Path $themeRoot 'style.css')

Assert-True ($loader.Contains("require_once `$lunara_inc . 'debrief-public.php';")) 'Loader must include the public Debrief renderer.'
Assert-True ($loader.IndexOf("'debrief.php'") -lt $loader.IndexOf("'debrief-public.php'")) 'Public renderer must load after the legacy compatibility renderer.'
Assert-True ($renderer -match 'function\s+lunara_get_review_debrief_render_parts') 'Renderer must expose one public decision controller.'
Assert-True ($renderer -match 'function\s+lunara_render_review_debrief') 'Renderer must expose the native complete-module wrapper.'
Assert-True ($renderer -match "'published'\s*!==") 'Canonical renderer must require explicit published status.'
Assert-True ($renderer -match 'lunara_debrief_validate_record\(\s*\$record\s*,\s*true\s*\)') 'Canonical renderer must use strict Core validation.'
Assert-True ($renderer -match "array\(\s*'theme_echo',\s*'counter_program',\s*'career_context'\s*\)") 'Canonical renderer must lock the three-role order.'
Assert-True ($renderer -match "'lunara-poster-library'") 'Canonical renderer must use the registered poster-library size.'
Assert-True ($renderer -match "'allow_aat_local_poster'\s*=>\s*false") 'Canonical renderer must not use the Oscars plugin poster library.'
Assert-True ($renderer -match "'resolve_awards'\s*=>\s*false") 'Canonical renderer must not request Oscar data.'
Assert-True ($renderer -match "'show_oscar_ledger'\s*=>\s*false") 'Canonical pairing cards must suppress Oscar Ledger markup.'
Assert-True ($renderer -notmatch 'wp_remote_get\s*\(') 'Public renderer module must not contain remote HTTP calls.'
Assert-True ($renderer -notmatch 'lunara_get_title_poster_html\s*\(') 'Public renderer module must not call the remote-capable poster helper.'
Assert-True ($renderer -notmatch 'do_shortcode\s*\(') 'Public renderer module must not execute shortcode strings.'
Assert-True ($renderer -notmatch 'DOMDocument') 'Public renderer module must not add content DOM parsing.'

Assert-True ($singleReview -match 'lunara_get_review_debrief_render_parts\(\s*\$post_id\s*\)') 'Single Review must request one atomic render decision.'
foreach ($legacyCall in @(
    'lunara_debrief_shortcode',
    'lunara_split_review_debrief_block',
    'lunara_render_pair_it_with_cards',
    'lunara_get_review_debrief_signature_media_html'
)) {
    Assert-True ($singleReview -notmatch [regex]::Escape($legacyCall)) "Single Review must not select $legacyCall directly."
}

Assert-True ($blocks -match 'lunara_debrief_public_renderer_enabled') 'Hidden bridge blocks must honor the staged native renderer switch.'
Assert-True ($blocks -match 'lunara_render_review_debrief') 'Debrief bridge block must use the native renderer while enabled.'
Assert-True ($blocks -match 'lunara_get_review_debrief_render_parts') 'Pair It With bridge block must use the atomic controller while enabled.'
Assert-True ($style -match 'Version:\s*3\.2\.11') 'Theme version must identify the complete Journal typography correction.'

Write-Host 'Debrief public renderer contract passed.'
