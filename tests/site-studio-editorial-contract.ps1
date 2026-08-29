$ErrorActionPreference = 'Stop'

$themeRoot = Split-Path -Parent $PSScriptRoot

function Assert-True {
    param([bool] $Condition, [string] $Message)
    if (-not $Condition) { throw $Message }
}

$runtime = & php (Join-Path $PSScriptRoot 'site-studio-editorial-runtime.php') 2>&1
Assert-True ($LASTEXITCODE -eq 0) ("Site Studio editorial runtime failed:`n" + ($runtime -join [Environment]::NewLine))
Assert-True (($runtime -join "`n") -match 'site-studio editorial runtime: all assertions passed') 'Editorial runtime did not reach its completion marker.'

$registry = Get-Content -Raw (Join-Path $themeRoot 'inc/site-studio-registry.php')
$adapters = Get-Content -Raw (Join-Path $themeRoot 'inc/site-studio-adapters.php')
$preview = Get-Content -Raw (Join-Path $themeRoot 'inc/site-studio-preview.php')
$workspace = Get-Content -Raw (Join-Path $themeRoot 'inc/site-studio.php')
$controller = Get-Content -Raw (Join-Path $themeRoot 'assets/js/lunara-site-studio.js')
$bridge = Get-Content -Raw (Join-Path $themeRoot 'assets/js/lunara-site-studio-preview.js')
$reviews = Get-Content -Raw (Join-Path $themeRoot 'inc/review-rendering.php')
$journal = (Get-Content -Raw (Join-Path $themeRoot 'archive-journal.php')) + (Get-Content -Raw (Join-Path $themeRoot 'inc/journal-archive-studio.php'))
$single = Get-Content -Raw (Join-Path $themeRoot 'single-review.php')
$search = Get-Content -Raw (Join-Path $themeRoot 'search.php')
$notFound = Get-Content -Raw (Join-Path $themeRoot '404.php')
$frontend = Get-Content -Raw (Join-Path $themeRoot 'inc/frontend.php')

foreach ($surface in @('review-single', 'utility-search', 'site-footer')) {
    Assert-True ($registry -match [regex]::Escape("'$surface'")) "Registry is missing $surface."
}
Assert-True ($registry -match "'reviews-archive'[\s\S]+?'sections'\s*=>\s*array\(\s*'hero'\s*,\s*'grid'\s*,\s*'pagination'\s*,\s*'pairing-desk'\s*\)") 'Reviews registry order must remain exactly canonical.'
Assert-True ($registry -match "'journal-archive'[\s\S]+?'sections'\s*=>\s*array\(\s*'hero'\s*,\s*'deskbar'\s*,\s*'filters'\s*,\s*'toolbar'\s*,\s*'grid'\s*,\s*'retention'\s*,\s*'pagination'\s*\)") 'Journal registry order must match its canonical provider.'

foreach ($factory in @(
    'lunara_site_studio_review_single_adapter',
    'lunara_site_studio_utility_search_adapter',
    'lunara_site_studio_footer_adapter'
)) {
    Assert-True ($adapters -match [regex]::Escape($factory)) "Missing adapter $factory."
}
Assert-True ($adapters -notmatch "lunara_site_studio_utility_search_keys[\s\S]{0,2500}lunara_utility_search_preset") 'Utility Search adapter allowlist must not include the compatibility-only preset marker.'
Assert-True ($adapters -notmatch 'lunara_footer_show_social') 'Footer adapter must not expose the phantom social control.'

foreach ($surface in @('reviews-archive', 'journal-archive', 'review-single', 'utility-search', 'site-footer')) {
    Assert-True ($preview -match [regex]::Escape("'$surface'")) "Private preview resolver is missing $surface."
    Assert-True ($controller -match [regex]::Escape("'$surface'")) "Workspace controller is missing $surface."
    Assert-True ($bridge -match [regex]::Escape("'$surface'")) "Preview bridge is missing $surface."
}
Assert-True ($workspace -notmatch "\$pilots\s*=\s*array\(\s*'global-design'\s*,\s*'homepage-structure'\s*,\s*'lunara-method'\s*\)") 'Workspace must no longer be limited to the three foundation pilots.'
Assert-True ($workspace -match 'Open Review Studio') 'Review Single inspector must provide the recognizable Core guided handoff.'
Assert-True ($workspace -match 'Open full archive controls') 'Provider archives must retain a recognizable advanced-tool handoff.'

Assert-True ($reviews -match 'data-lunara-site-studio-section="hero"') 'Reviews hero needs a stable preview marker.'
Assert-True ($reviews -match 'data-lunara-site-studio-section="grid"') 'Reviews grid family needs a stable preview marker.'
foreach ($section in @('hero', 'deskbar', 'filters', 'toolbar', 'grid', 'retention', 'pagination')) {
    Assert-True ($journal -match [regex]::Escape(('data-lunara-site-studio-section="{0}"' -f $section))) "Journal needs a stable $section marker."
}
foreach ($section in @('hero', 'criticism', 'debrief', 'pair-it-with')) {
    Assert-True ($single -match [regex]::Escape(('data-lunara-site-studio-section="{0}"' -f $section))) "Review Single needs a stable $section marker."
}
foreach ($section in @('search-command', 'direct-matches', 'result-run', 'recovery')) {
    Assert-True (($search + $notFound) -match [regex]::Escape(('data-lunara-site-studio-section="{0}"' -f $section))) "Utility routes need a stable $section marker."
}
Assert-True ($frontend -match 'data-lunara-site-studio-section="footer"') 'The live custom footer renderer needs a stable Footer marker.'

foreach ($source in @($registry, $adapters, $preview, $workspace)) {
    Assert-True ($source -notmatch '(?i)cache[_ -]?(purge|flush|clear)|rocket_clean_[a-z0-9_]+|wp_cache_flush') 'Site Studio editorial code must not add cache purge behavior or instructions.'
}

Write-Host 'site-studio-editorial: all assertions passed.'
