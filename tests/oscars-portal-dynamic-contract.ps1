$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

# Theme 3.2.59 — the Oscars portal reads as live.
# Proves the dynamic module's pure behavior through its harness, then pins
# the wiring: loader, template consumption behind function_exists guards,
# Customizer registration, the runtime's portal branch, the shell rules,
# the flush key, the anonymous-cacheable forbidden list, and the budgets.

$root = Split-Path -Parent $PSScriptRoot
$failures = [System.Collections.Generic.List[string]]::new()
function Assert-Contract {
    param([bool]$Condition, [string]$Message)
    if (-not $Condition) { $failures.Add($Message) }
}

$fixture = Join-Path $PSScriptRoot 'fixtures\oscars-portal-dynamic-harness.php'
Assert-Contract (Test-Path -LiteralPath $fixture) 'Missing runtime fixture: tests/fixtures/oscars-portal-dynamic-harness.php'
$output = & php $fixture 2>&1 | Out-String
Assert-Contract ($LASTEXITCODE -eq 0) "Dynamic harness failed:`n$output"
$result = $null
try { $result = $output | ConvertFrom-Json } catch { $failures.Add("Dynamic harness returned invalid JSON:`n$output") }
if ($null -ne $result) {
    foreach ($caseName in @('sanitize-date', 'season-clock-phases', 'season-clock-render', 'board-summary', 'board-summary-render', 'todays-pull-offset', 'todays-pull-render')) {
        $case = $result.cases.$caseName
        Assert-Contract ($null -ne $case) "Missing harness case: $caseName"
        if ($null -ne $case) {
            Assert-Contract ([bool]$case.passed) ("Harness case failed: $caseName " + ($case.checks | ConvertTo-Json -Compress))
        }
    }
}

function Read-Text { param([string]$Relative) return [IO.File]::ReadAllText((Join-Path $root $Relative)) }
$module   = Read-Text 'inc/oscars-portal-dynamic.php'
$loader   = Read-Text 'functions-loader.php'
$template = Read-Text 'page-oscars.php'
$portal   = Read-Text 'inc/oscars-portal.php'
$custom   = Read-Text 'inc/customizer.php'
$runtime  = Read-Text 'assets/js/lunara-public-runtime.js'
$shell    = Read-Text 'assets/css/lunara-shell.css'
$home     = Read-Text 'inc/home-sections.php'

# 1. Loader and the anonymous-cacheable boundary.
Assert-Contract ($loader -match "require_once \`$lunara_inc \. 'oscars-portal-dynamic\.php'") 'The loader must require inc/oscars-portal-dynamic.php.'
foreach ($forbidden in @('wp_create_nonce', 'wp_nonce_field', 'setcookie', 'is_user_logged_in', 'get_current_user_id', 'current_user_can')) {
    Assert-Contract (-not $module.Contains($forbidden)) "The dynamic module must not call $forbidden on the anonymous-cacheable portal."
}

# 2. Template consumption, always behind function_exists guards.
Assert-Contract ($template -match "function_exists\( 'lunara_oscars_render_season_clock' \)") 'page-oscars.php must consume the season clock behind a function_exists guard.'
Assert-Contract ($template -match "get_theme_mod\( 'lunara_oscars_next_ceremony_date', '' \)") 'The hero must read the ceremony date mod with an empty default.'
Assert-Contract ($template -match "function_exists\( 'lunara_oscars_render_todays_pull' \)") "page-oscars.php must consume Today's Pull behind a function_exists guard."
$pullIndex = $template.IndexOf('lunara_oscars_render_todays_pull')
$factsIndex = $template.IndexOf('<div class="lunara-oscars-portal-facts-grid">')
Assert-Contract ($pullIndex -gt 0 -and $factsIndex -gt 0 -and $pullIndex -lt $factsIndex) "Today's Pull must render above the facts grid."

# 3. Board summary lives inside the renderer, above the untouched list.
Assert-Contract ($portal -match "function_exists\( 'lunara_oscars_board_summary' \)") 'The board renderer must build its summary behind a function_exists guard.'
$summaryIndex = $portal.IndexOf('echo $summary_html')
$listIndex = $portal.IndexOf('<ol class="lunara-oscars-board-list">')
Assert-Contract ($summaryIndex -gt 0 -and $listIndex -gt 0 -and $summaryIndex -lt $listIndex) 'The board summary must render above the list.'

# 4. Customizer registration and sanitizer.
Assert-Contract ($custom -match "'setting'\s*=>\s*'lunara_oscars_next_ceremony_date'") 'The Customizer must register lunara_oscars_next_ceremony_date.'
Assert-Contract ($custom -match "'sanitize'\s*=>\s*'lunara_oscars_sanitize_ceremony_date'") 'The ceremony date must sanitize through lunara_oscars_sanitize_ceremony_date.'

# 5. Runtime: portal reveals win over the plugin-page branch, hero excluded, safety timer, counters, scroll-spy, live clock.
Assert-Contract ($runtime -match "var isPortal=document\.body\.classList\.contains\('lunara-oscars-portal-page'\)") 'The reveal runtime must detect the portal.'
$portalBranch = $runtime.IndexOf('if(isPortal){')
$pluginBranch = $runtime.IndexOf('if(isPluginPage){')
Assert-Contract ($portalBranch -gt 0 -and $pluginBranch -gt 0 -and $portalBranch -lt $pluginBranch) 'The portal branch must win over the plugin-page branch.'
Assert-Contract ($runtime.Contains("':not(.lunara-oscars-portal-slot-hero)'")) 'The hero must never be a reveal target.'
Assert-Contract ($runtime.Contains("window.setTimeout(function(){document.querySelectorAll('.lunara-reveal:not(.is-visible)')")) 'The reveal safety timer must exist.'
Assert-Contract ($runtime.Contains('.lunara-oscars-portal-stat-value,.lunara-oscars-portal-fact-value,.lunara-oscars-season-days')) 'Counters must cover the portal stat, fact, and season-day values.'
Assert-Contract ($runtime.Contains('/^\d{4}$/.test(text)')) 'Counters must skip four-digit years.'
Assert-Contract ($runtime.Contains("setAttribute('aria-current','location')")) 'The navigator scroll-spy must mark the current link.'
Assert-Contract ($runtime.Contains('data-lunara-season-clock')) 'The runtime must recompute the season clock at view time.'

# 6. Shell rules for the new classes.
foreach ($rule in @('.lunara-oscars-season-clock', '.lunara-oscars-board-summary', '.lunara-oscars-todays-pull', '.lunara-oscars-navigator a[aria-current]', '@media (hover: hover) and (pointer: fine)')) {
    Assert-Contract ($shell.Contains($rule)) "Shell must carry $rule."
}

# 7. The import flush clears the new day-keyed transient.
Assert-Contract ($home.Contains("delete_transient( 'lunara_oscars_todays_pull_v1_' . `$day )")) "The import flush must clear the Today's Pull key."

# 8. Budgets.
$shellBytes = (Get-Item (Join-Path $root 'assets/css/lunara-shell.css')).Length
$runtimeBytes = (Get-Item (Join-Path $root 'assets/js/lunara-public-runtime.js')).Length
$routeBytes = (Get-Item (Join-Path $root 'assets/css/lunara-oscars-portal.css')).Length
Assert-Contract ($shellBytes -le 204800) "Shell exceeds 204,800 bytes: $shellBytes."
Assert-Contract ($runtimeBytes -le 20480) "Public runtime exceeds 20,480 bytes: $runtimeBytes."
Assert-Contract ($routeBytes -le 45000) "Route sheet exceeds 45,000 bytes: $routeBytes."

if ($failures.Count -gt 0) {
    $details = $failures | ForEach-Object { " - $_" }
    throw "Oscars portal dynamic contract failed:`n$($details -join "`n")"
}

Write-Host 'Oscars portal dynamic contract passed: harness cases, wiring, runtime branches, shell rules, flush key, budgets.'
