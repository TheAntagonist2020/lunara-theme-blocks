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
$fixture = Join-Path $PSScriptRoot 'fixtures\oscars-late-css-route-scope-harness.php'

Assert-True (Test-Path -LiteralPath $fixture) "Missing runtime fixture: $fixture"

$rawOutput = & php $fixture 2>&1
$exitCode = $LASTEXITCODE
$output = $rawOutput -join [Environment]::NewLine

Assert-True ($exitCode -eq 0) "Oscars late-CSS runtime harness failed:`n$output"

try {
    $result = $output | ConvertFrom-Json
} catch {
    throw "Oscars late-CSS runtime harness returned invalid JSON:`n$output"
}

Assert-True ([bool] $result.split_fallback_parity) 'Split and fallback implementations did not behave identically.'

$expectedCases = @(
    'empty-vars',
    'portal',
    'entity-with-id',
    'entity-without-id',
    'hub-ceremony',
    'hub-category',
    'hub-ceremonies-index',
    'hub-categories-index',
    'hub-about',
    'admin-entity',
    'feed-hub'
)

foreach ($caseName in $expectedCases) {
    $case = $result.cases.PSObject.Properties[$caseName].Value
    Assert-True ($null -ne $case) "Missing runtime case: $caseName"
    Assert-True ([bool] $case.passed) "Runtime case failed: $caseName"
}

Assert-True ($result.cases.'entity-with-id'.split.Count -eq 1) 'Entity route with an ID must print exactly one stylesheet.'
Assert-True ($result.cases.'entity-without-id'.split.Count -eq 0) 'Entity route without an ID must omit the stylesheet.'
Assert-True ($result.cases.portal.split.Count -eq 0) 'Oscars portal with no direct AAT route vars must omit the stylesheet.'
Assert-True ($result.cases.'admin-entity'.split.Count -eq 0) 'Admin requests must omit the stylesheet.'
Assert-True ($result.cases.'feed-hub'.split.Count -eq 0) 'Feed requests must omit the stylesheet.'

$printed = $result.cases.'entity-with-id'.split[0]
Assert-True ($printed[0] -eq 'lunara-oscars-late-guardrails') 'Unexpected late-CSS stylesheet handle.'
Assert-True ($printed[1] -eq 'assets/css/lunara-oscars-late-guardrails.css') 'Unexpected late-CSS stylesheet path.'

Write-Host "Oscars late-CSS route runtime contract passed ($($expectedCases.Count) cases; split/fallback parity verified)."
