$ErrorActionPreference = 'Stop'

# Prediction Board behavioral + static contract — the renderer's FIRST
# coverage (inc/oscars-portal.php, lunara_render_oscars_prediction_board).
# The harness executes the real extracted function body against fixture
# picks; the static half pins how the renderer builds what the harness saw.

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

$fixture = Join-Path $PSScriptRoot 'fixtures/oscars-portal-board-harness.php'
Assert-True (Test-Path -LiteralPath $fixture) "Missing runtime fixture: $fixture"

$rawOutput = & php $fixture 2>&1
$exitCode = $LASTEXITCODE
$output = $rawOutput -join [Environment]::NewLine

Assert-True ($exitCode -eq 0) "Oscars Prediction Board harness failed:`n$output"

try {
    $result = $output | ConvertFrom-Json
} catch {
    throw "Oscars Prediction Board harness returned invalid JSON:`n$output"
}

$expectedCases = @(
    'empty-picks-exact-empty-string',
    'hostile-strings-entity-escaped',
    'status-class-bounded-via-sanitize-key',
    'board-shape-and-order',
    'no-state-conditional-output',
    'hostile-url-meta-neutralized'
)

foreach ($caseName in $expectedCases) {
    $case = $result.cases.PSObject.Properties[$caseName].Value
    Assert-True ($null -ne $case) "Missing harness case: $caseName"
    Assert-True ([bool] $case.passed) "Harness case failed: $caseName"
}

# Static pins on the shipped renderer source. The renderer is the last
# function in inc/oscars-portal.php; the tempered (?!function\s) scan keeps
# every pin anchored inside its body regardless.
$rendererPath = Join-Path $themeRoot 'inc/oscars-portal.php'
$renderer = Get-Content -LiteralPath $rendererPath -Raw

Assert-True ($renderer -match 'function\s+lunara_render_oscars_prediction_board\s*\(') 'The Prediction Board renderer must exist in inc/oscars-portal.php.'

# Status discipline as it actually exists: sanitize_key bounds the raw meta at
# read time (a sanitize_key-APPROPRIATE use — the value feeds a CSS class
# token, not an identity decision), and esc_attr escapes it at emission.
Assert-True ($renderer -match "sanitize_key\(\s*\(string\)\s*get_post_meta\(\s*\`$pick_id\s*,\s*'_lunara_pick_status'") 'The pick status must be bounded through sanitize_key at meta-read time.'
Assert-True ($renderer -match "'\s?is-status-'\s*\.\s*esc_attr\(\s*\`$row\['status'\]\s*\)") 'The is-status-* class must emit the sanitized token through esc_attr.'

# The board id is stable and emitted exactly once by the renderer.
Assert-True (([regex]::Matches($renderer, 'id="oscars-board"')).Count -eq 1) 'The renderer must own exactly one stable #oscars-board id.'

# href discipline: every href the renderer emits must route through esc_url.
# Bounded to the renderer body via the tempered (?!function\s) scan.
$boardBody = [regex]::Match($renderer, 'function\s+lunara_render_oscars_prediction_board\s*\((?:(?!function\s)[\s\S])*').Value
Assert-True ($boardBody.Length -gt 0) 'Unable to isolate the Prediction Board renderer body.'
$hrefEmissionCount = ([regex]::Matches($boardBody, 'href=')).Count
$escUrlHrefCount = ([regex]::Matches($boardBody, 'href="<\?php\s+echo\s+esc_url\(')).Count
Assert-True ($hrefEmissionCount -ge 1) 'The Prediction Board renderer must emit at least one href.'
Assert-True ($hrefEmissionCount -eq $escUrlHrefCount) 'Every href the Prediction Board renderer emits must route through esc_url.'

# Empty board renders nothing: both the no-posts and no-rows paths return the
# exact empty string before any markup buffer opens.
Assert-True ($renderer -match 'function\s+lunara_render_oscars_prediction_board\((?:(?!function\s)[\s\S])*?empty\(\s*\$posts\s*\)(?:(?!function\s)[\s\S])*?return\s+''''') 'The renderer must return the exact empty string when no picks exist.'
Assert-True ($renderer -match 'empty\(\s*\$rows\s*\)(?:(?!function\s)[\s\S])*?return\s+''''') 'The renderer must return the exact empty string when no rows survive.'

# Anonymous-cacheable: zero nonce/cookie/user-conditional output inside the
# renderer body (tempered scan bounded to the function).
foreach ($forbidden in @('wp_create_nonce', 'wp_nonce_field', 'setcookie', 'is_user_logged_in', 'get_current_user_id', 'current_user_can')) {
    Assert-True ($renderer -notmatch ('function\s+lunara_render_oscars_prediction_board\((?:(?!function\s)[\s\S])*' + [regex]::Escape($forbidden))) "The Prediction Board renderer must not call $forbidden on the anonymous-cacheable portal."
}

# The template consumes the renderer through one guarded call and echoes it as
# the board slot; the navigator links to the stable anchor only when nonempty.
$pageTemplate = Get-Content -LiteralPath (Join-Path $themeRoot 'page-oscars.php') -Raw
Assert-True ($pageTemplate -match "function_exists\(\s*'lunara_render_oscars_prediction_board'\s*\)\s*\?\s*lunara_render_oscars_prediction_board\(\)\s*:\s*''") 'page-oscars.php must consume the board through its function_exists guard.'
Assert-True ($pageTemplate -match ('if\s*\(\s*''''\s*!==\s*\$lunara_board_html\s*\)\s*:\s*\?><a href="#oscars-board"')) 'The navigator must link to #oscars-board only when the board rendered.'

Write-Host "Oscars Prediction Board contract passed ($($expectedCases.Count) harness cases + static pins)."
