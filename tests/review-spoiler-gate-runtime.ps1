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
$runtimePath = Join-Path $root 'assets\js\lunara-review-spoiler-gate.js'
$runtime = Get-Content -LiteralPath $runtimePath -Raw
$fixture = Join-Path $PSScriptRoot 'fixtures\review-spoiler-gate-runtime-harness.php'

Assert-True ((Get-Item -LiteralPath $runtimePath).Length -le 8192) 'Full-spoiler Review runtime exceeds its 8 KB budget.'
Assert-True ($runtime -match "lunaraFullSpoilerAcknowledged:") 'The restored gate must preserve session-scoped acknowledgement keys.'
Assert-True ($runtime -match "document\.body\.classList\.add\('has-lunara-spoiler-gate'\)") 'The restored gate must activate protected-content CSS.'
Assert-True ($runtime -match "data-lunara-spoiler-protected") 'The restored gate must target protected Review regions.'
Assert-True ($runtime -match "classList\.toggle\('is-revealed'") 'The restored gate must reveal protected Review regions.'
Assert-True ($runtime -match "setAttribute\('aria-hidden'") 'The restored gate must keep accessibility state synchronized.'
Assert-True ($runtime -match "firstProtected\.focus\(\{ preventScroll: true \}\)") 'The restored gate must move focus into revealed criticism.'
Assert-True ($runtime -match "prefers-reduced-motion: reduce") 'The restored gate must respect reduced-motion preferences.'
Assert-True ($runtime -notmatch '\.innerHTML\s*=') 'The restored gate must not reinterpret editorial HTML.'
Assert-True ($runtime -notmatch '\beval\s*\(') 'The restored gate must not execute dynamic code.'

foreach ($source in @($setup, $fallback)) {
    Assert-True ($source -match 'function\s+lunara_enqueue_review_spoiler_gate_runtime') 'A theme loading path is missing the full-spoiler runtime loader.'
    Assert-True ($source -match "is_singular\(\s*'review'\s*\)") 'The full-spoiler runtime must stay on Review singles.'
    Assert-True ($source -match 'lunara_is_full_spoiler_review\(\s*\$post_id\s*\)') 'The runtime must load only when the Review is explicitly full-spoiler.'
    Assert-True ($source -match "assets/js/lunara-review-spoiler-gate\.js") 'A theme loading path is missing the full-spoiler runtime path.'
    Assert-True ($source -match "wp_script_add_data\(\s*'lunara-review-spoiler-gate'\s*,\s*'strategy'\s*,\s*'defer'\s*\)") 'The restored runtime must remain deferred.'
    Assert-True ($source -match "add_action\(\s*'wp_enqueue_scripts'\s*,\s*'lunara_enqueue_review_spoiler_gate_runtime'\s*,\s*31\s*\)") 'The restored runtime must enqueue after the shared Phase 1C assets.'
    Assert-True ($source -match 'function\s+lunara_review_spoiler_gate_delay_exclusions') 'A theme loading path is missing the WP Rocket delay exclusion.'
    Assert-True ($source -match "add_filter\(\s*'rocket_delay_js_exclusions'\s*,\s*'lunara_review_spoiler_gate_delay_exclusions'\s*\)") 'The spoiler runtime must bypass WP Rocket Delay JS.'
}

Assert-True (Test-Path -LiteralPath $fixture) "Missing runtime fixture: $fixture"
$rawOutput = & php $fixture 2>&1
$exitCode = $LASTEXITCODE
$output = $rawOutput -join [Environment]::NewLine
Assert-True ($exitCode -eq 0) "Full-spoiler loader harness failed:`n$output"

try {
    $result = $output | ConvertFrom-Json
} catch {
    throw "Full-spoiler loader harness returned invalid JSON:`n$output"
}

Assert-True ([bool] $result.split_fallback_parity) 'Split and fallback full-spoiler runtime loaders did not behave identically.'
Assert-True ([bool] $result.exclusion_parity) 'Split and fallback WP Rocket exclusions did not behave identically.'
$expectedCases = @('home', 'ordinary-review', 'full-spoiler-review', 'journal-single', 'admin-full-spoiler', 'feed-full-spoiler', 'missing-post-id', 'missing-asset')
foreach ($caseName in $expectedCases) {
    $case = $result.cases.PSObject.Properties[$caseName].Value
    Assert-True ($null -ne $case) "Missing runtime case: $caseName"
    Assert-True ([bool] $case.passed) "Runtime case failed: $caseName"
}

Assert-True ($result.cases.'full-spoiler-review'.split.scripts.Count -eq 1) 'A full-spoiler Review must enqueue exactly one gate runtime.'
Assert-True ($result.cases.'full-spoiler-review'.split.script_data.Count -eq 1) 'A full-spoiler Review must mark the gate runtime deferred.'
foreach ($caseName in @('home', 'ordinary-review', 'journal-single', 'admin-full-spoiler', 'feed-full-spoiler', 'missing-post-id', 'missing-asset')) {
    Assert-True ($result.cases.$caseName.split.scripts.Count -eq 0) "$caseName must omit the gate runtime."
}

Write-Host "Full-spoiler Review runtime contract passed ($($expectedCases.Count) cases; split/fallback parity verified)."
