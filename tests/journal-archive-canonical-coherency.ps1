$ErrorActionPreference = 'Stop'

# Anonymous canonical Journal cache-coherency sentinel contract.
#
# Theme 3.2.48 was rolled back from production because the anonymous no-query
# /journal/ response served legacy cached archive markup while every other
# cache key had advanced to the candidate. This contract proves — offline,
# with no network — that the sentinel guarding the 3.2.53 reissue stays
# fail-closed, targets only the exact canonical URL, and cannot be satisfied
# by query-bearing, cache-busted, or authenticated diagnostic variants.
#
# The live production probe is a deployment-verification step, invoked
# explicitly as:
#   node tests/tools/lunara-journal-canonical-coherency-gate.js --expected-version <deployed version>
# It is intentionally NOT run from this suite.

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

$style = Read-ThemeFile 'style.css'
Assert-True ($style -match '(?m)^Version:\s*3\.2\.53\s*$') 'Theme version must be 3.2.53.'

$template = Read-ThemeFile 'archive-journal.php'
Assert-True ($template -match 'data-lunara-theme-version="<\?php echo esc_attr\( \(string\) wp_get_theme\(\)->get\( ''Version'' \) \); \?>"') 'The modern Journal root must stamp the deployed theme version so cached HTML identity is provable.'
Assert-True ($template -match 'id="primary" class="lunara-archive-page lunara-journal-archive-page') 'The modern Journal root identity (#primary) must be preserved for the coherency sentinel.'

$gateTool = Read-ThemeFile 'tests/tools/lunara-journal-canonical-coherency-gate.js'
Assert-True ($gateTool -match "CANONICAL_JOURNAL_URL = 'https://lunarafilm\.com/journal/'") 'The sentinel must target the exact anonymous canonical Journal URL.'
Assert-True ($gateTool -match "parsed\.search === ''") 'The sentinel must refuse every query-bearing or cache-busted URL as proof.'
Assert-True ($gateTool -match "parsed\.username === ''" -and $gateTool -match "parsed\.password === ''") 'The sentinel must refuse credentials-bearing (authenticated-variant) URLs as proof.'
Assert-True ($gateTool -match 'parsed\.href === CANONICAL_JOURNAL_URL') 'The sentinel must demand exact canonical serialization, closing bare-?/# identity variants.'
Assert-True ($gateTool -match 'REPLAY_COHERENT_EXIT = 3') 'Replay success must exit distinctly from live proof so a diagnostic replay can never satisfy deployment verification.'
Assert-True ($gateTool -match 'expected-version') 'The sentinel must demand the deployed theme version instead of running open.'
Assert-True ($gateTool -match 'legacyRootCount') 'The sentinel must census legacy Journal roots separately from the modern root.'
Assert-True ($gateTool -match 'lunara-journal-archive-studio-css') 'The sentinel must detect legacy route assets mixed into a candidate response.'
Assert-True ($gateTool -match 'TiemposText-Bold\.woff2') 'The sentinel must own the Tiempos preload contract on the canonical route.'
$fetchHeaders = [regex]::Match($gateTool, 'headers:\s*\{[^}]*\}')
Assert-True $fetchHeaders.Success 'The live probe must declare an explicit anonymous request header set.'
Assert-True (-not ($fetchHeaders.Value -match 'Cache-Control|Pragma|Cookie|Authorization')) 'The live probe must stay anonymous and must not send cache-bypassing request headers; those would mask the stale-cache split it exists to detect.'

$nodeRuntime = & node (Join-Path $PSScriptRoot 'journal-archive-canonical-coherency-runtime.js') 2>&1
if ($LASTEXITCODE -ne 0) { throw ($nodeRuntime -join "`n") }

Write-Host ($nodeRuntime -join "`n")
Write-Host 'Journal canonical cache-coherency sentinel contract passed.'
