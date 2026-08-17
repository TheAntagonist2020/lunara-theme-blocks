$ErrorActionPreference = 'Stop'

$themeRoot = Split-Path -Parent $PSScriptRoot
$frontendPath = Join-Path $themeRoot 'inc/frontend.php'
$criticalPath = Join-Path $themeRoot 'inc/review-archive-critical.php'
$rendererPath = Join-Path $PSScriptRoot 'reviews-archive-critical-render.php'
$boostRuntimePath = Join-Path $PSScriptRoot 'reviews-archive-boost-filter-runtime.php'
$fixturePath = Join-Path $PSScriptRoot 'fixtures/reviews-production-boost-critical-3.2.39.css'
$archiveCssPath = Join-Path $themeRoot 'assets/css/lunara-review-archive.css'
$loader = Get-Content -Raw -LiteralPath (Join-Path $themeRoot 'functions-loader.php')
$frontend = Get-Content -Raw -LiteralPath $frontendPath
$criticalModule = Get-Content -Raw -LiteralPath $criticalPath
$archiveCss = Get-Content -Raw -LiteralPath $archiveCssPath
$style = Get-Content -Raw -LiteralPath (Join-Path $themeRoot 'style.css')
$browserRuntime = Get-Content -Raw -LiteralPath (Join-Path $PSScriptRoot 'reviews-archive-first-paint-runtime.js')
$reviewRendering = Get-Content -Raw -LiteralPath (Join-Path $themeRoot 'inc/review-rendering.php')
$archiveTemplate = Get-Content -Raw -LiteralPath (Join-Path $themeRoot 'archive-review.php')
$pageTemplate = Get-Content -Raw -LiteralPath (Join-Path $themeRoot 'page-reviews.php')
$directorTemplate = Get-Content -Raw -LiteralPath (Join-Path $themeRoot 'taxonomy-lunara_director.php')
$stagingGate = Get-Content -Raw -LiteralPath (Join-Path $themeRoot 'docs/staging/plans/2026-08-15-reviews-archive-cls-3-2-42.md')

function Assert-True {
    param([bool] $Condition, [string] $Message)

    if (-not $Condition) {
        throw $Message
    }
}

function Get-TextSha256 {
    param([string] $Text)

    $sha = [Security.Cryptography.SHA256]::Create()
    try {
        return [BitConverter]::ToString(
            $sha.ComputeHash([Text.Encoding]::UTF8.GetBytes($Text))
        ).Replace('-', '').ToLowerInvariant()
    } finally {
        $sha.Dispose()
    }
}

function Invoke-CriticalRenderer {
    param([hashtable] $Orders, [hashtable] $Visibility)

    $payload = @{
        orders = $Orders
        visibility = $Visibility
    } | ConvertTo-Json -Compress -Depth 5
    $encoded = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($payload))
    $output = & php $rendererPath $encoded
    Assert-True ($LASTEXITCODE -eq 0) 'The pure critical-CSS renderer must execute successfully.'
    return ($output -join [Environment]::NewLine)
}

$scenarios = @(
    @{
        name = 'default'
        orders = @{ hero = 1; utility = 2; grid = 2; pagination = 3; 'pairing-desk' = 4 }
        visibility = @{ hero = $true; grid = $true; 'pairing-desk' = $true }
    },
    @{
        name = 'pairing-first'
        orders = @{ hero = 4; utility = 2; grid = 2; pagination = 3; 'pairing-desk' = 1 }
        visibility = @{ hero = $true; grid = $true; 'pairing-desk' = $true }
    },
    @{
        name = 'hero-hidden'
        orders = @{ hero = 1; utility = 2; grid = 2; pagination = 3; 'pairing-desk' = 4 }
        visibility = @{ hero = $false; grid = $true; 'pairing-desk' = $true }
    },
    @{
        name = 'director'
        orders = @{ hero = 1; utility = 2; grid = 2; pagination = 3; 'pairing-desk' = 4 }
        visibility = @{ hero = $true; grid = $true; 'pairing-desk' = $false }
    },
    @{
        name = 'page-template'
        orders = @{ hero = 1; utility = 2; grid = 2; pagination = 3; 'pairing-desk' = 4 }
        visibility = @{ hero = $true; grid = $true; 'pairing-desk' = $true }
    },
    @{
        name = 'slug-page'
        orders = @{ hero = 1; utility = 2; grid = 2; pagination = 3; 'pairing-desk' = 4 }
        visibility = @{ hero = $true; grid = $true; 'pairing-desk' = $true }
    }
)

$requiredSeedFragments = @(
    '#primary.lra{',
    '&>.lunara-review-archive-slot-hero',
    'display:flex!important;flex-direction:column!important',
    '&>:is(.lunara-review-archive-slot-utility,.lunara-review-archive-slot-grid)',
    '&>.lunara-review-archive-slot-pagination',
    '&>.lunara-review-archive-slot-pairing-desk',
    '&>.lunara-review-archive-slot-retention{margin:0!important;order:var(--lunara-reviews-archive-order-grid,2)!important;width:100%!important}',
    '& .lunara-review-archive-gallery{min-width:0!important;width:100%!important}',
    'margin:0 auto!important;width:100%!important',
    '.lunara-review-archive-hero-shell',
    '.lunara-review-archive-toolbar-head',
    '.lunara-review-archive-year-filter',
    '.lunara-review-feature-title',
    '.lunara-review-feature-footer',
    '.lunara-pairing-desk-section',
    '.lunara-pair-cards-grid',
    '.lunara-pair-card-poster img',
    '.lunara-pairing-desk-backdrop{display:none!important}',
    '@media(max-width:820px)',
    '@media(max-width:540px)'
)

$seedHashes = @()
$seedBytes = @()
foreach ($scenario in $scenarios) {
    $seed = Invoke-CriticalRenderer $scenario.orders $scenario.visibility
    $bytes = [Text.Encoding]::UTF8.GetByteCount($seed)
    $seedBytes += $bytes
    $seedHashes += Get-TextSha256 $seed

    Assert-True ($bytes -gt 0 -and $bytes -le 12288) "The $($scenario.name) universal structural seed must be nonempty and at or below 12 KB; measured $bytes bytes."
    Assert-True (([regex]::Matches($seed, '\{')).Count -eq ([regex]::Matches($seed, '\}')).Count) "The $($scenario.name) structural seed must have balanced braces."
    Assert-True ($seed -notmatch '(?:^|\})\+\#' -and $seed -notmatch '\+CSS' -and $seed -notmatch '(?m)^\s*\+') "The $($scenario.name) structural seed must not contain patch-marker pluses."
    Assert-True ($seed -notmatch '(?:html|body|#primary)\s*\{[^}]*overflow(?:-x)?:hidden') "The $($scenario.name) seed must not hide page-level overflow."
    Assert-True (([regex]::Matches($seed, '#primary')).Count -eq ([regex]::Matches($seed, '#primary\.lra')).Count) "The $($scenario.name) seed must not target a bare #primary outside the renderer-owned lra scope."

    foreach ($fragment in $requiredSeedFragments) {
        Assert-True $seed.Contains($fragment) "The $($scenario.name) universal seed is missing structural fragment: $fragment"
    }
}

Assert-True (($seedHashes | Select-Object -Unique).Count -eq 1) 'The structural seed must remain universal across saved order, hidden Hero, and director routes.'
Assert-True (($seedBytes | Select-Object -Unique).Count -eq 1) 'Every supported Reviews archive route must receive the same bounded structural seed.'
$seedByteCount = $seedBytes[0]
$seedSha256 = $seedHashes[0]

Assert-True ($criticalModule -match 'function\s+lunara_reviews_archive_critical_css\s*\(') 'The first-paint contract must live in the pure split-module composer.'
Assert-True ($criticalModule -match 'compact, universal structural guard') 'The module documentation must describe the universal lane contract truthfully.'
Assert-True ($criticalModule -match "'#primary\.lra\{'" -and $criticalModule -match 'Native nesting') 'The structural composer must document and emit its wrapper-owned native nesting contract.'
Assert-True ($criticalModule -match "str_replace\(\s*';}',\s*'}'") 'The structural minifier must remove optional final declaration semicolons.'
$criticalLoader = "require_once `$lunara_inc . 'review-archive-critical.php';"
$frontendLoader = "require_once `$lunara_inc . 'frontend.php';"
Assert-True ($loader.IndexOf($criticalLoader) -ge 0) 'The split loader must require the Reviews critical module.'
Assert-True ($loader.IndexOf($criticalLoader) -lt $loader.IndexOf($frontendLoader)) 'The critical composer must load before frontend output hooks.'

Assert-True ($frontend -match 'lunara_reviews_archive_critical_css\(\s*\$lane_orders,\s*\$visibility\s*\)') 'The wp_head renderer must consume the pure universal composer.'
Assert-True ($frontend -match '<style id="lunara-review-archive-critical-css">%s</style>') 'Reviews routes must emit the composed seed under the stable critical style ID.'
Assert-True ($frontend -match "add_action\(\s*'wp_head',\s*'lunara_output_review_archive_critical_css',\s*9\s*\)") 'Critical seed must print in wp_head before body paint.'

$authority = [regex]::Match(
    $frontend,
    '<style id="lunara-review-archive-authority-vars">(?<css>[\s\S]*?)</style>'
)
Assert-True $authority.Success 'Reviews routes must emit saved Studio geometry and lane variables before first paint.'
Assert-True ($authority.Groups['css'].Value -match '#primary\.lra\s*\{') 'Authority variables must use the renderer-owned #primary.lra selector so stale Boost rules cannot win by specificity.'
foreach ($property in @(
    'section-gap',
    'lead-min',
    'lead-media-min',
    'lead-copy-pad',
    'order-hero',
    'order-utility',
    'order-grid',
    'order-pagination',
    'order-pairing'
)) {
    $propertyPattern = '--lunara-reviews-archive-' + [regex]::Escape($property) + ':[^\r\n]+!important\s*;'
    Assert-True ($authority.Groups['css'].Value -match $propertyPattern) "Authority variable $property must beat stale optimizer values from first paint."
}
Assert-True ($frontend -match "add_action\(\s*'wp_head',\s*'lunara_output_review_archive_authority_css',\s*7\s*\)") 'Dynamic Reviews geometry variables must print in wp_head.'
Assert-True ($frontend -match 'function\s+lunara_output_review_archive_authority_css[\s\S]{0,2600}lunara_reviews_archive_studio_get_public_config\(\s*!\s*\$is_director_archive\s*\)') 'Authority geometry must consume the last-valid Reviews Archive Studio config, with previews disabled on the Studio-exempt director routes.'
Assert-True ($frontend -match 'lunara_get_reviews_archive_section_order_map[\s\S]{0,650}lunara_get_review_archive_lane_order') 'Head-phase lane variables must come from the canonical saved Archive Studio map.'
Assert-True ($frontend -notmatch "add_action\(\s*'wp_footer',\s*'lunara_output_review_archive_authority_css'") 'Reviews geometry variables must never arrive from wp_footer.'

$boostResultJson = & php $boostRuntimePath
Assert-True ($LASTEXITCODE -eq 0) 'The isolated Jetpack Boost filter runtime must execute successfully.'
$boostResult = ($boostResultJson -join '') | ConvertFrom-Json
Assert-True (-not $boostResult.concat_archive_true -and -not $boostResult.concat_archive_false) 'The Reviews route handle must never enter Jetpack Boost concatenation.'
Assert-True ($boostResult.concat_other_true -and -not $boostResult.concat_other_false) 'The concat filter must preserve unrelated handle state.'
Assert-True (-not $boostResult.async_archive_true -and -not $boostResult.async_archive_false) 'The Reviews route handle must remain synchronous in Jetpack Boost.'
Assert-True ($boostResult.async_other_true -and -not $boostResult.async_other_false) 'The async filter must preserve unrelated handle state.'
Assert-True (-not $boostResult.concat_journal_true -and -not $boostResult.concat_journal_false) 'The Journal route handle must never enter Jetpack Boost concatenation.'
Assert-True ($boostResult.concat_journal_other) 'The Journal concat filter must preserve unrelated handle state.'
Assert-True (-not $boostResult.async_journal_true -and -not $boostResult.async_journal_false) 'The Journal route handle must remain synchronous in Jetpack Boost.'
Assert-True ($boostResult.async_journal_other) 'The Journal async filter must preserve unrelated handle state.'
Assert-True ($boostResult.registrations.Count -eq 4) 'Reviews and Journal must each register one concat and one async handle-scoped Boost filter.'
$expectedBoostRegistrations = @(
    @{ Hook = 'css_do_concat'; Callback = 'lunara_keep_review_archive_css_unaggregated' },
    @{ Hook = 'jetpack_boost_async_style'; Callback = 'lunara_keep_review_archive_css_synchronous' },
    @{ Hook = 'css_do_concat'; Callback = 'lunara_keep_journal_archive_css_unaggregated' },
    @{ Hook = 'jetpack_boost_async_style'; Callback = 'lunara_keep_journal_archive_css_synchronous' }
)
foreach ($expectedRegistration in $expectedBoostRegistrations) {
    $matches = @($boostResult.registrations | Where-Object { $_.hook -eq $expectedRegistration.Hook -and $_.callback -eq $expectedRegistration.Callback })
    Assert-True ($matches.Count -eq 1) "Expected one $($expectedRegistration.Hook) registration for $($expectedRegistration.Callback)."
}
foreach ($registration in $boostResult.registrations) {
    Assert-True ($registration.priority -eq 10 -and $registration.accepted_args -eq 2) "Boost filter $($registration.hook) must receive the stylesheet handle."
}

Assert-True ($frontend -match 'rocket_rucss_inline_content_exclusions[\s\S]{0,300}lunara_rocket_preserve_review_archive_inline_css') 'Rocket must preserve the first-paint inline geometry contract.'
foreach ($styleId in @('lunara-review-archive-critical-css', 'lunara-review-archive-authority-vars')) {
    Assert-True ($frontend.Contains($styleId)) "Rocket preservation must name $styleId."
}

$fixture = [IO.File]::ReadAllText($fixturePath).TrimEnd("`r", "`n")
$fixtureBytes = [Text.Encoding]::UTF8.GetByteCount($fixture)
$fixtureSha = Get-TextSha256 $fixture
Assert-True ($fixtureBytes -eq 53864) "The archived production Boost critical fixture must remain 53,864 bytes; measured $fixtureBytes."
Assert-True ($fixtureSha -eq '0abce37ff14360da3914005227f713cc3f0394cc116c8ffc3ecb368629b1569e') "The production Boost fixture hash drifted: $fixtureSha"

Assert-True ($browserRuntime -match "fs\.readFileSync\(path\.join\(themeRoot, 'style\.css'\)") 'The browser regression must inject the actual current base stylesheet.'
Assert-True ($browserRuntime -match "assets/css/lunara-review-components\.css") 'The browser regression must inject the actual current Review component stylesheet.'
Assert-True ($browserRuntime -match "assets/css/lunara-review-archive\.css") 'The browser regression must inject the actual blocking Reviews route stylesheet.'
Assert-True ($browserRuntime -match "LUNARA_REVIEWS_STALE_AGGREGATE_FIXTURE") 'The browser regression must support replaying the exact production stale aggregate.'
Assert-True ($browserRuntime -match 'lunara-review-archive-year-filter') 'The first-paint fixture must include the no-JS Release Year control.'
Assert-True ($browserRuntime -match 'width="1500" height="2000"') 'The first-paint fixture must reserve a truthful intrinsic lead image.'
Assert-True ($browserRuntime -match "document\.getElementById\('current-structural-seed'\)\.remove\(\)") 'The browser regression must prove the persistent seed does not alter settled current-CSS geometry.'
Assert-True ($browserRuntime -match 'seedPersistenceDelta\s*>\s*1') 'The browser regression must hard-fail a settled seed-versus-canonical geometry mismatch.'
Assert-True ($browserRuntime -match "bodyClass:\s*'tax-lunara_director'") 'The browser regression must exercise the real director archive body class.'
Assert-True ($browserRuntime -match "bodyClass:\s*'page page-template-page-reviews'") 'The browser regression must exercise an explicitly selected Reviews page template.'
Assert-True ($browserRuntime -match "bodyClass:\s*'page page-template-default'") 'The browser regression must exercise the slug-selected Reviews page with the default body class.'
Assert-True ($browserRuntime -match 'showPairing:\s*false' -and $browserRuntime -match 'showYearFilter:\s*false' -and $browserRuntime -match 'showHeroActions:\s*false') 'The director fixture must omit Pairing, Release Year, and archive-only hero actions.'
Assert-True ($browserRuntime -match 'showRetention:\s*false') 'The director fixture must omit the Studio-owned retention/gallery lane.'
Assert-True ($browserRuntime -match 'class="lunara-review-archive-retention lunara-review-archive-slot-retention"' -and $browserRuntime -match 'lunara-review-archive-gallery-media') 'The first-paint fixture must model the Studio retention slot and root archive gallery markup.'
Assert-True ($browserRuntime -match 'lunara-review-archive-page lra is-label-font-tiempos') 'The first-paint fixture must exercise the resolver-emitted Tiempos label marker.'
Assert-True ($browserRuntime -match 'structural seed must be inert when the renderer-owned lra class is absent') 'The browser regression must prove the seed is inert without the internal route marker.'
Assert-True ($browserRuntime -match 'function\s+pairingCollisionSnapshot\s*\(' -and $browserRuntime -match 'function\s+assertPairingCollisionFree\s*\(') 'The first-paint Pairing fixture must detect numeral collisions geometrically.'
Assert-True ($browserRuntime -match 'assertPairingCollisionFree\(afterPairingCollisions' -and $browserRuntime -match 'assertPairingCollisionFree\(canonicalPairingCollisions') 'Pairing numeral clearance must survive deferred delivery and settled current CSS.'

$routeCssBytes = (Get-Item -LiteralPath $archiveCssPath).Length
Assert-True ($routeCssBytes -gt 30000 -and $routeCssBytes -le 45000) "Cacheable Reviews route CSS must stay within 45 KB; measured $routeCssBytes bytes."
Assert-True ($archiveCss -match 'lunara-review-archive-hero-shell') 'The complete visual archive system must remain in the external route asset.'
Assert-True ($archiveCss -match '#primary\.lra\s*\{[\s\S]{0,260}display:\s*flex\s*!important;[\s\S]{0,100}flex-direction:\s*column\s*!important;') 'The route asset must own canonical root geometry on every supported Reviews renderer.'
Assert-True (($archiveCss | Select-String -Pattern '#primary\.lra' -AllMatches).Matches.Count -ge 60) 'Structural archive selectors must be owned by the internal #primary.lra Reviews wrapper.'
Assert-True ($archiveCss -notmatch 'body:is\(\.post-type-archive-review') 'Route geometry must not depend on a particular WordPress body class.'
Assert-True ($reviewRendering -match '\$classes\s*=\s*trim\(\s*''site-main lunara-archive-page lra ''\s*\.\s*\$base_classes\s*\)') 'The shared renderer must append lra internally so caller-provided classes cannot remove the route marker.'
Assert-True ($archiveTemplate -match "'classes'\s*=>\s*'lunara-review-archive-page'") 'The CPT archive must emit the route-owned Reviews wrapper.'
Assert-True ($pageTemplate -match "'classes'\s*=>\s*'lunara-review-archive-page'") 'The Reviews page template must emit the route-owned Reviews wrapper.'
Assert-True ($directorTemplate -match "'classes'\s*=>\s*'lunara-review-archive-page\s+lunara-director-archive-page'") 'The director archive must emit the route-owned Reviews wrapper.'
Assert-True ($frontend -match "wp_enqueue_style\([\s\S]{0,180}'lunara-review-archive'[\s\S]{0,180}array\(\s*'lunara-review-components',\s*'lunara-shell'\s*\)") 'The Reviews route asset must keep its explicit component and shell dependencies.'
Assert-True ($frontend -notmatch '<style id="lunara-review-archive-authority-css">') 'The legacy 43 KB inline archive cascade must not return.'
Assert-True ($style -match '(?m)^Version:\s*3\.2\.51\s*$') 'Theme version must preserve the Reviews critical-delivery repair in 3.2.51.'
Assert-True ($stagingGate -match 'real iPhone[\s\S]{0,80}Safari' -and $stagingGate -match 'native\s+CSS nesting') 'The staging gate must require a real-iPhone Safari smoke for the nested critical guard.'

Write-Host "Theme 3.2.51 Reviews critical delivery contract passed: universal seed ${seedByteCount}B ($seedSha256); route CSS ${routeCssBytes}B; Boost fixture ${fixtureBytes}B."
