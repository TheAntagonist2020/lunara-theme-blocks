$ErrorActionPreference = 'Stop'

# Oscars read-path ratchet.
#
# (a) The migrated Oscars route family carries ZERO direct $wpdb references:
#     the plugin reader (lunara_oscars_reader + method_exists guards) is the
#     only sanctioned door into Academy Awards data, and absence degrades to
#     hidden/empty output — never back to SQL.
# (b) The repo-wide count of direct `academy_awards` table references outside
#     tests/ is pinned exactly. Future migrations may only DECREASE this
#     number; any increase means a new theme-side awards-table read snuck in
#     around the reader boundary and must be rejected.

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

# --- (a) Zero direct SQL in the Oscars route family. ------------------------
$sqlFreeFiles = @(
    'inc/oscars-family.php',
    'inc/oscars-portal.php',
    'inc/oscars-portal-studio.php',
    'inc/oscars-portal-critical.php',
    'inc/oscars-ledger-critical.php',
    'page-oscars.php'
)

foreach ($relative in $sqlFreeFiles) {
    $path = Join-Path $themeRoot $relative
    Assert-True (Test-Path -LiteralPath $path) "Missing expected file: $relative"
    $content = Get-Content -LiteralPath $path -Raw
    Assert-True ($content -notmatch '\$wpdb') "$relative must carry zero direct `$wpdb references; the plugin read accessors are the only data door."
}

# --- (b) The pinned academy_awards reference count. -------------------------
# Pinned on 2026-08-17 at theme 3.2.51 + the three Oscars route-family
# commits (fc3742a, 07ff9c3, 9d568eb). Breakdown at pin time:
#   functions.php        12  (unsplit legacy fallback bundle)
#   inc/frontend.php      2
#   inc/debrief.php       3  (table-name candidates for the debrief resolver)
#   inc/home-sections.php 1
#   docs/                 4  (CHANGELOG + OPERATORS-GUIDE prose)
# Scope: every file under the theme root EXCEPT tests/, node_modules/, and
# .git/ — the same sweep as `grep -ro academy_awards` with those exclusions.
$pinnedCount = 22

$excludedRoots = @('tests', 'node_modules', '.git')
$actualCount = 0
$countsByFile = @{}

Get-ChildItem -LiteralPath $themeRoot -Recurse -File -Force | ForEach-Object {
    $relative = $_.FullName.Substring($themeRoot.Length).TrimStart([char]'\', [char]'/')
    $normalized = $relative -replace '\\', '/'
    $topSegment = ($normalized -split '/')[0]
    if ($excludedRoots -contains $topSegment) {
        return
    }
    $content = Get-Content -LiteralPath $_.FullName -Raw -ErrorAction SilentlyContinue
    if ($null -eq $content) {
        return
    }
    $matchCount = ([regex]::Matches($content, 'academy_awards')).Count
    if ($matchCount -gt 0) {
        $countsByFile[$normalized] = $matchCount
        $actualCount += $matchCount
    }
}

$breakdown = ($countsByFile.GetEnumerator() | Sort-Object Name | ForEach-Object { "$($_.Name)=$($_.Value)" }) -join ', '

Assert-True ($actualCount -le $pinnedCount) ("Direct academy_awards table references INCREASED: pinned $pinnedCount, found $actualCount ($breakdown). New theme-side awards-table reads are forbidden — go through lunara_oscars_reader() and the plugin's public accessors instead.")
Assert-True ($actualCount -eq $pinnedCount) ("Direct academy_awards table references DECREASED: pinned $pinnedCount, found $actualCount ($breakdown). Good — a migration retired direct reads. Re-pin `$pinnedCount in tests/oscars-read-path-ratchet.ps1 to the new lower number so the ratchet keeps holding; the count may only ever decrease.")

Write-Host "Oscars read-path ratchet passed: $($sqlFreeFiles.Count) route-family files are `$wpdb-free; academy_awards references pinned at $pinnedCount (may only decrease)."
