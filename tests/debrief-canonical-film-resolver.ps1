$ErrorActionPreference = 'Stop'

$themeRoot = Split-Path -Parent $PSScriptRoot
$fixture = Join-Path $PSScriptRoot 'fixtures/debrief-resolver-harness.php'

function Assert-True {
    param(
        [bool] $Condition,
        [string] $Message
    )

    if (-not $Condition) {
        throw $Message
    }
}

Assert-True (Test-Path -LiteralPath $fixture) 'Missing Debrief resolver PHP fixture.'

$output = & php $fixture 2>&1
if ($LASTEXITCODE -ne 0) {
    throw "Debrief resolver fixture failed:`n$($output -join "`n")"
}

$result = ($output -join "`n") | ConvertFrom-Json
Assert-True ($result.valid -eq $true) 'Canonical movie fixture must resolve as valid.'
Assert-True ($result.movie_id -eq 11) 'Canonical movie fixture must retain its movie ID.'
Assert-True ($result.poster_source -eq 'movie_featured') 'Featured image must remain first poster authority.'
Assert-True ($result.review_movie -eq 11) 'Review source-film resolution must use the Core relationship.'
Assert-True ($result.awards_opt_in -eq $true) 'Local Oscar counts must remain explicitly opt-in.'
Assert-True ($result.remote_calls -eq 0) 'The resolver must make zero remote calls.'

$loader = Get-Content -Raw (Join-Path $themeRoot 'functions-loader.php')
$resolver = Get-Content -Raw (Join-Path $themeRoot 'inc/debrief-resolver.php')

Assert-True ($loader.Contains("require_once `$lunara_inc . 'debrief-resolver.php';")) 'The local-only resolver must load before the legacy Debrief renderer.'
Assert-True ($loader.IndexOf("'debrief-resolver.php'") -lt $loader.IndexOf("'debrief.php'")) 'Resolver load order must precede the legacy renderer.'
Assert-True ($resolver -match 'function\s+lunara_debrief_resolve_movie') 'The stable movie resolver wrapper must exist.'
Assert-True ($resolver -match "method_exists\(\s*'Lunara_Debrief_Contract',\s*'is_public_film_reference'\s*\)") 'Resolver must delegate public-film validity to Core when available.'
Assert-True ($resolver -notmatch 'wp_remote_get|get_title_visual_package|get_tmdb_data_for_imdb_id|lunara_get_title_poster_html') 'The resolver must not call remote-capable poster paths.'
Assert-True ($resolver -notmatch 'add_action|add_filter|add_shortcode') 'Release A resolver must remain hook-free.'

Write-Host 'Debrief canonical film resolver contract passed.'
