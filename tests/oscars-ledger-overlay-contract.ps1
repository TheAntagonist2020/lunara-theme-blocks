$ErrorActionPreference = 'Stop'

# Oscars ledger overlay contract: the theme dresses the plugin's ceremony,
# category, title, and person virtual routes through three delivery layers —
# wp_head-6 saved-only Dossier variables, a wp_head-7 structural seed, and an
# enqueue-112 overlay stylesheet — printed on exactly the entity/hub ledger
# routes and never on the portal, admin, feeds, or the query-less home shape.

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

function Read-ThemeFile {
    param([string] $RelativePath)

    $path = Join-Path $themeRoot $RelativePath
    Assert-True (Test-Path -LiteralPath $path) "Missing expected file: $RelativePath"
    return Get-Content -LiteralPath $path -Raw
}

$fixture = Join-Path $PSScriptRoot 'fixtures/oscars-ledger-overlay-harness.php'
Assert-True (Test-Path -LiteralPath $fixture) "Missing runtime fixture: $fixture"

$rawOutput = & php $fixture 2>&1
$exitCode = $LASTEXITCODE
$output = $rawOutput -join [Environment]::NewLine

Assert-True ($exitCode -eq 0) "Oscars ledger overlay harness failed:`n$output"

try {
    $result = $output | ConvertFrom-Json
} catch {
    throw "Oscars ledger overlay harness returned invalid JSON:`n$output"
}

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
    Assert-True ($null -ne $case) "Missing harness case: $caseName"
    Assert-True ([bool] $case.passed) "Harness case failed: $caseName"
}
Assert-True ($result.cases.portal.enqueued.Count -eq 0) 'The ledger overlay must never enqueue on the theme portal.'
Assert-True ($result.cases.'entity-with-id'.enqueued -contains 'lunara-oscars-ledger') 'Entity routes must enqueue the ledger overlay.'

foreach ($checkName in @(
    'overlay-deps-fall-back-to-shell',
    'overlay-deps-chain-behind-plugin',
    'anonymous-vars-and-1002-value-identical',
    'sixteen-shared-custom-properties',
    'saved-values-reach-both-blocks',
    'priority6-stays-saved-only-under-preview',
    'priority1002-adopts-admin-preview',
    'seed-nonempty-within-6144',
    'seed-consumes-priority6-variables',
    'seed-carries-no-important',
    'hub-seam-registered',
    'entity-seam-registered',
    'hub-seam-identity',
    'entity-seam-identity',
    'seam-emits-no-output',
    'hub-seam-normalizes-malformed',
    'entity-seam-normalizes-malformed'
)) {
    $check = $result.checks.PSObject.Properties[$checkName].Value
    Assert-True ($null -ne $check) "Missing harness check: $checkName"
    Assert-True ([bool] $check.passed) "Harness check failed: $checkName"
}

# Budgets: the executed seed stays inside 6144 bytes; the cacheable overlay
# stylesheet stays inside 20000 bytes on disk.
Assert-True ($result.seed_bytes -gt 0 -and $result.seed_bytes -le 6144) "The ledger structural seed must stay at or below 6144 bytes; measured $($result.seed_bytes)."
$overlayBytes = (Get-Item -LiteralPath (Join-Path $themeRoot 'assets/css/lunara-oscars-ledger.css')).Length
Assert-True ($overlayBytes -gt 0 -and $overlayBytes -le 20000) "The ledger overlay stylesheet must stay at or below 20000 bytes; measured $overlayBytes."

# Static wiring: hook priorities, Boost/Rocket protection, and the preserved
# priority-1002 emitter.
$frontend = Read-ThemeFile 'inc/frontend.php'
$family   = Read-ThemeFile 'inc/oscars-family.php'
$ledgerCritical = Read-ThemeFile 'inc/oscars-ledger-critical.php'

Assert-True ($frontend -match "add_action\(\s*'wp_head'\s*,\s*'lunara_output_oscars_ledger_vars_css'\s*,\s*6\s*\)") 'The saved-only Dossier variables must print at wp_head 6.'
Assert-True ($frontend -match "add_action\(\s*'wp_head'\s*,\s*'lunara_output_oscars_ledger_critical_css'\s*,\s*7\s*\)") 'The ledger structural seed must print at wp_head 7.'
Assert-True ($frontend -match "add_action\(\s*'wp_enqueue_scripts'\s*,\s*'lunara_enqueue_oscars_ledger_styles'\s*,\s*112\s*\)") 'The ledger overlay must enqueue at 112, behind the plugin chain.'
Assert-True ($frontend -match "add_action\(\s*'wp_head'\s*,\s*'lunara_output_oscars_dossier_studio_css'\s*,\s*1002\s*\)") 'The preserved priority-1002 Dossier emitter must keep its hook.'
Assert-True ($frontend -match "function\s+lunara_get_oscars_dossier_saved_control_values\((?:(?!function\s)[\s\S])*?lunara_get_oscars_dossier_studio_select_value\(\s*array\(\)") 'The wp_head-6 variables must resolve SAVED control values only (an empty preview array by construction).'
Assert-True ($frontend -match "function\s+lunara_keep_oscars_ledger_css_unaggregated[\s\S]{0,300}'lunara-oscars-ledger'") 'Jetpack Boost must not aggregate the ledger overlay stylesheet.'
Assert-True ($frontend -match "function\s+lunara_keep_oscars_ledger_css_synchronous[\s\S]{0,300}'lunara-oscars-ledger'") 'The ledger overlay stylesheet must stay render-blocking.'
Assert-True ($frontend -match "lunara_rocket_preserve_oscars_ledger_css[\s\S]{0,300}'lunara-oscars-ledger\.css'") 'WP Rocket RUCSS must exclude the ledger overlay stylesheet.'
Assert-True ($frontend -match "lunara_rocket_preserve_oscars_ledger_inline_css[\s\S]{0,400}'lunara-oscars-ledger-vars'[\s\S]{0,200}'lunara-oscars-ledger-critical-css'") 'WP Rocket RUCSS must preserve both inline ledger layers.'

# The identity seam is registered on both plugin composer hooks at the shape
# the plugin implodes, and unknown sections must pass through by construction.
Assert-True ($family -match "add_filter\(\s*'aat_hub_route_sections'\s*,\s*'lunara_oscars_compose_hub_route_sections'\s*,\s*10\s*,\s*2\s*\)") 'The hub composer seam must be registered.'
Assert-True ($family -match "add_filter\(\s*'aat_entity_route_sections'\s*,\s*'lunara_oscars_compose_entity_route_sections'\s*,\s*10\s*,\s*2\s*\)") 'The entity composer seam must be registered.'

# Cache safety: the anonymous-cacheable ledger paths carry zero nonce, cookie,
# or user-conditional output.
foreach ($forbidden in @('wp_create_nonce', 'wp_nonce_field', 'setcookie', 'is_user_logged_in')) {
    Assert-True ($ledgerCritical -notmatch [regex]::Escape($forbidden)) "inc/oscars-ledger-critical.php must not call $forbidden."
    Assert-True ($family -notmatch [regex]::Escape($forbidden)) "inc/oscars-family.php must not call $forbidden."
    foreach ($ledgerFunction in @('lunara_output_oscars_ledger_vars_css', 'lunara_output_oscars_ledger_critical_css', 'lunara_enqueue_oscars_ledger_styles', 'lunara_get_oscars_dossier_saved_control_values')) {
        Assert-True ($frontend -notmatch ('function\s+' + [regex]::Escape($ledgerFunction) + '\((?:(?!function\s)[\s\S])*' + [regex]::Escape($forbidden))) "$ledgerFunction must not call $forbidden on the anonymous-cacheable ledger routes."
    }
}

Write-Host "Oscars ledger overlay contract passed ($($expectedCases.Count) route cases; value-identity, seam purity, and budgets verified)."
