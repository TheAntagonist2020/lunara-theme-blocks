$ErrorActionPreference = 'Stop'

function Assert-True {
    param(
        [bool] $Condition,
        [string] $Message
    )

    if (-not $Condition) {
        throw $Message
    }
}

$root = Split-Path -Parent $PSScriptRoot
$setup = Get-Content -LiteralPath (Join-Path $root 'inc\setup.php') -Raw
$fallback = Get-Content -LiteralPath (Join-Path $root 'functions.php') -Raw
$style = Get-Content -LiteralPath (Join-Path $root 'style.css') -Raw
$globalCssPath = Join-Path $root 'assets\css\lunara-public-guardrails.css'
$reviewCssPath = Join-Path $root 'assets\css\lunara-review-single-guardrails.css'
$globalCss = Get-Content -LiteralPath $globalCssPath -Raw
$reviewCss = Get-Content -LiteralPath $reviewCssPath -Raw
$fixture = Join-Path $PSScriptRoot 'fixtures\review-single-css-route-scope-harness.php'

Assert-True ($style -match 'Version:\s*3\.2\.14') 'Theme version must identify the Review-single CSS route scope.'
Assert-True ((Get-Item -LiteralPath $globalCssPath).Length -le 38912) 'The global guardrail bundle did not shrink beneath its 38 KB gate.'
Assert-True ((Get-Item -LiteralPath $reviewCssPath).Length -le 12288) 'The Review-single guardrail bundle exceeds its 12 KB budget.'

Assert-True ($globalCss -notmatch 'body\.single-review\s+\.lunara-full-spoiler-warning') 'Review-single spoiler shield rules must not remain in the global guardrail bundle.'
Assert-True ($globalCss -notmatch 'body\.has-lunara-spoiler-gate') 'Review-single reveal state rules must not remain in the global guardrail bundle.'
Assert-True ($globalCss -notmatch '@keyframes\s+lunaraSpoilerReveal') 'Review-single reveal keyframes must not remain in the global guardrail bundle.'
Assert-True ($globalCss -match '\.lunara-review-grid-card\.is-full-spoiler-review\s+\.lunara-review-grid-kicker') 'Full-spoiler Review card badges must remain available on Home and archives.'
Assert-True ($globalCss -match '\.lunara-review-grid-card\.is-full-spoiler-review\s+\.lunara-review-grid-poster-wrap::after') 'Full-spoiler Review card poster badges must remain global.'

foreach ($selector in @(
    'body.single-review .lunara-full-spoiler-warning',
    'body.has-lunara-spoiler-gate .lunara-spoiler-protected-content:not(.is-revealed)',
    '@keyframes lunaraSpoilerReveal',
    '@media (prefers-reduced-motion: reduce)',
    '@media (max-width: 760px)'
)) {
    Assert-True ($reviewCss.Contains($selector)) "Review-single guardrail bundle is missing: $selector"
}
Assert-True ($reviewCss -notmatch '\.lunara-review-grid-card\.is-full-spoiler-review') 'Archive-card badge rules must not be trapped in the Review-single bundle.'
Assert-True ($reviewCss -notmatch '<\?php') 'Review-single guardrails must remain static CSS.'

foreach ($source in @($setup, $fallback)) {
    Assert-True ($source -match 'function\s+lunara_print_review_single_guardrail_styles') 'A theme loading path is missing the Review-single loader.'
    Assert-True ($source -match "is_singular\(\s*'review'\s*\)") 'The Review-single loader must use the canonical Review singular predicate.'
    Assert-True ($source -match "assets/css/lunara-review-single-guardrails\.css") 'A theme loading path is missing the Review-single asset path.'
    Assert-True ($source -match "add_action\(\s*'wp_head'\s*,\s*'lunara_print_review_single_guardrail_styles'\s*,\s*1005\s*\)") 'Review-single guardrails must stay adjacent to the public bundle and before later Review overrides.'
    $publicHookIndex = $source.IndexOf("add_action( 'wp_head', 'lunara_print_public_guardrail_styles', 1005 );")
    $reviewHookIndex = $source.IndexOf("add_action( 'wp_head', 'lunara_print_review_single_guardrail_styles', 1005 );")
    Assert-True ($publicHookIndex -ge 0 -and $reviewHookIndex -gt $publicHookIndex) 'The Review-single hook must register immediately after the public guardrail hook.'
}

Assert-True (Test-Path -LiteralPath $fixture) "Missing runtime fixture: $fixture"
$rawOutput = & php $fixture 2>&1
$exitCode = $LASTEXITCODE
$output = $rawOutput -join [Environment]::NewLine
Assert-True ($exitCode -eq 0) "Review-single CSS runtime harness failed:`n$output"

try {
    $result = $output | ConvertFrom-Json
} catch {
    throw "Review-single CSS runtime harness returned invalid JSON:`n$output"
}

Assert-True ([bool] $result.split_fallback_parity) 'Split and fallback Review-single loaders did not behave identically.'
$expectedCases = @('home', 'review-single', 'journal-single', 'post-single', 'reviews-archive', 'admin-review', 'feed-review')
foreach ($caseName in $expectedCases) {
    $case = $result.cases.PSObject.Properties[$caseName].Value
    Assert-True ($null -ne $case) "Missing runtime case: $caseName"
    Assert-True ([bool] $case.passed) "Runtime case failed: $caseName"
}

Assert-True ($result.cases.'review-single'.split.Count -eq 1) 'A Review single must print exactly one Review-single stylesheet.'
foreach ($caseName in @('home', 'journal-single', 'post-single', 'reviews-archive', 'admin-review', 'feed-review')) {
    Assert-True ($result.cases.$caseName.split.Count -eq 0) "$caseName must omit the Review-single stylesheet."
}

$printed = $result.cases.'review-single'.split[0]
Assert-True ($printed[0] -eq 'lunara-review-single-guardrails') 'Unexpected Review-single stylesheet handle.'
Assert-True ($printed[1] -eq 'assets/css/lunara-review-single-guardrails.css') 'Unexpected Review-single stylesheet path.'

Write-Host "Review-single CSS route-scope contract passed ($($expectedCases.Count) cases; split/fallback parity verified)."
