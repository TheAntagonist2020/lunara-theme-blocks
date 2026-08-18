$ErrorActionPreference = 'Stop'

# Anonymous canonical Oscars portal cache-coherency sentinel contract.
#
# Clone of the Journal sentinel family: theme 3.2.48 was rolled back because
# the anonymous no-query canonical response kept serving legacy cached markup
# while every other cache key had advanced. This contract proves — offline,
# with no network — that the Oscars portal sentinel stays fail-closed,
# targets only the exact anonymous canonical https://lunarafilm.com/oscars/
# URL, and cannot be satisfied by query-bearing, cache-busted, or
# authenticated diagnostic variants.
#
# The live production probe is a deployment-verification step, invoked
# explicitly as:
#   node tests/tools/lunara-oscars-canonical-coherency-gate.js --expected-version <deployed version>
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

$template = Read-ThemeFile 'page-oscars.php'
Assert-True ($template -match 'data-lunara-theme-version="<\?php echo esc_attr\( \(string\) wp_get_theme\(\)->get\( ''Version'' \) \); \?>"') 'The portal root must stamp the deployed theme version so cached HTML identity is provable.'
Assert-True ($template -match 'id="primary" class="site-main lunara-oscars-portal') 'The portal root identity (#primary + lunara-oscars-portal) must be preserved for the coherency sentinel.'

# The sentinel's anchor census must stay grounded in what page-oscars.php
# actually renders: default-visible id-bearing sections only.
foreach ($anchorId in @('oscars-doors', 'oscars-spotlights', 'oscars-titles', 'oscars-research', 'oscars-winners', 'oscars-deep-cuts')) {
    Assert-True ($template -match ('id="' + [regex]::Escape($anchorId) + '"')) "page-oscars.php must render the default-visible #$anchorId anchor the sentinel demands."
}

$gateTool = Read-ThemeFile 'tests/tools/lunara-oscars-canonical-coherency-gate.js'
Assert-True ($gateTool -match "CANONICAL_OSCARS_URL = 'https://lunarafilm\.com/oscars/'") 'The sentinel must target the exact anonymous canonical Oscars portal URL.'
Assert-True ($gateTool -match "parsed\.search === ''") 'The sentinel must refuse every query-bearing or cache-busted URL as proof.'
Assert-True ($gateTool -match "parsed\.username === ''" -and $gateTool -match "parsed\.password === ''") 'The sentinel must refuse credentials-bearing (authenticated-variant) URLs as proof.'
Assert-True ($gateTool -match 'parsed\.href === CANONICAL_OSCARS_URL') 'The sentinel must demand exact canonical serialization, closing bare-?/# identity variants.'
Assert-True ($gateTool -match 'REPLAY_COHERENT_EXIT = 3') 'Replay success must exit distinctly from live proof so a diagnostic replay can never satisfy deployment verification.'
Assert-True ($gateTool -match 'expected-version') 'The sentinel must demand the deployed theme version instead of running open.'
Assert-True ($gateTool -match 'legacyRootCount') 'The sentinel must census legacy portal roots separately from the modern root.'
Assert-True ($gateTool -match 'lunara-oscars-shell-css') 'The sentinel must detect the legacy cross-route stylesheet mixed into a candidate response.'
Assert-True ($gateTool -match 'lunara-oscars-portal-critical-css') 'The sentinel must require the wp_head-7 structural seed before <body>.'
Assert-True ($gateTool -match "ROUTE_STYLESHEET_LINK_ID = 'lunara-oscars-portal-css'") 'The sentinel must require the portal route stylesheet link before <body>.'
Assert-True ($gateTool -match '\(#lunara-oscars-portal-vars\) is PROVENANCE-GATED') 'The sentinel must document that the Studio vars block is provenance-gated (required only when present, never demanded).'
Assert-True ($gateTool -match '!detail\.varsPresent \|\| detail\.varsInHead') 'The sentinel must enforce the vars block as optional-but-head-only, matching the provenance gate.'
Assert-True ($gateTool -match 'TiemposText-Bold\.woff2') 'The sentinel must own the Tiempos preload contract on the canonical route.'
$fetchHeaders = [regex]::Match($gateTool, 'headers:\s*\{[^}]*\}')
Assert-True $fetchHeaders.Success 'The live probe must declare an explicit anonymous request header set.'
Assert-True (-not ($fetchHeaders.Value -match 'Cache-Control|Pragma|Cookie|Authorization')) 'The live probe must stay anonymous and must not send cache-bypassing request headers; those would mask the stale-cache split it exists to detect.'

$nodeRuntime = & node (Join-Path $PSScriptRoot 'oscars-canonical-coherency-runtime.js') 2>&1
if ($LASTEXITCODE -ne 0) { throw ($nodeRuntime -join "`n") }

Write-Host ($nodeRuntime -join "`n")

# Version lock: this intentionally asserts the NEXT reissue identity. It is
# EXPECTED to fail until the 3.2.51 version migration lands as its own step;
# every assertion above it must already pass on the pre-migration tree.
$style = Read-ThemeFile 'style.css'
Assert-True ($style -match '(?m)^Version:\s*3\.2\.51\s*$') 'Theme version must be 3.2.51.'

Write-Host 'Oscars canonical cache-coherency sentinel contract passed.'
