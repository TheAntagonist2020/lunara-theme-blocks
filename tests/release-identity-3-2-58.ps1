$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$script:Failures = [System.Collections.Generic.List[string]]::new()
$root = Split-Path -Parent $PSScriptRoot

function Add-ContractFailure {
    param([string]$Message)

    $script:Failures.Add($Message)
}

function Assert-Contract {
    param(
        [bool]$Condition,
        [string]$Message
    )

    if (-not $Condition) {
        Add-ContractFailure $Message
    }
}

function Get-LiteralOccurrenceCount {
    param(
        [string]$Text,
        [string]$Needle
    )

    if ([string]::IsNullOrEmpty($Needle)) {
        throw 'Literal occurrence needles must not be empty.'
    }

    $count = 0
    $offset = 0
    while ($offset -lt $Text.Length) {
        $found = $Text.IndexOf($Needle, $offset, [StringComparison]::Ordinal)
        if ($found -lt 0) {
            break
        }

        $count++
        $offset = $found + $Needle.Length
    }

    return $count
}

function Get-TopEntry {
    param(
        [string]$Text,
        [string]$Heading
    )

    $normalized = $Text.Replace("`r`n", "`n")
    $start = $normalized.IndexOf($Heading, [StringComparison]::Ordinal)
    if ($start -lt 0) {
        return ''
    }

    $next = $normalized.IndexOf("`n## ", $start + $Heading.Length, [StringComparison]::Ordinal)
    if ($next -lt 0) {
        return $normalized.Substring($start)
    }

    return $normalized.Substring($start, $next - $start)
}

$stylePath = Join-Path $root 'style.css'
$styleLines = @(Get-Content -LiteralPath $stylePath)
$versionHeaderLines = @($styleLines | Where-Object { $_ -match '^Version:' })
$exactVersionHeaders = @($styleLines | Where-Object { $_ -ceq 'Version: 3.2.58' })
Assert-Contract ($versionHeaderLines.Count -eq 1 -and $exactVersionHeaders.Count -eq 1) `
    'style.css must contain exactly one exact Version: 3.2.58 header.'

$priorVersion = @('3', '2', '57') -join '.'
$escapedPriorVersion = $priorVersion.Replace('.', '\.')
$trackedSources = @(Get-ChildItem -LiteralPath (Join-Path $root 'tests') -File | Where-Object { $_.Extension -in @('.ps1', '.php', '.js') })
Assert-Contract ($trackedSources.Count -gt 0) 'Test-source discovery must return at least one file.'

$plainCount = 0
$escapedCount = 0
$unexpectedHits = [System.Collections.Generic.List[string]]::new()

foreach ($source in $trackedSources) {
    $sourceLines = [IO.File]::ReadAllLines($source.FullName)

    for ($index = 0; $index -lt $sourceLines.Count; $index++) {
        $line = $sourceLines[$index]
        $linePlainCount = Get-LiteralOccurrenceCount -Text $line -Needle $priorVersion
        $lineEscapedCount = Get-LiteralOccurrenceCount -Text $line -Needle $escapedPriorVersion
        $plainCount += $linePlainCount
        $escapedCount += $lineEscapedCount

        if ($linePlainCount -gt 0 -or $lineEscapedCount -gt 0) {
            $unexpectedHits.Add(('{0}:{1} plain={2} escaped={3}' -f `
                $source.Name, ($index + 1), $linePlainCount, $lineEscapedCount))
        }
    }
}

Assert-Contract ($plainCount -eq 0) `
    "Current test sources must retain no stale plain prior identity; found $plainCount."
Assert-Contract ($escapedCount -eq 0) `
    "Current test sources must retain no regex-escaped prior identity; found $escapedCount."
if ($unexpectedHits.Count -gt 0) {
    $sample = @($unexpectedHits | Select-Object -First 12) -join '; '
    Add-ContractFailure "Unexpected prior identity hits remain ($($unexpectedHits.Count)): $sample"
}

$historicalVersion = @('3', '2', '54') -join '.'
$historicalHits = [System.Collections.Generic.List[object]]::new()
foreach ($source in $trackedSources) {
    $sourceLines = [IO.File]::ReadAllLines($source.FullName)
    for ($index = 0; $index -lt $sourceLines.Count; $index++) {
        if ($sourceLines[$index].Contains($historicalVersion)) {
            $historicalHits.Add([pscustomobject]@{
                FileName = $source.Name
                LineNumber = [int]($index + 1)
                LineText = $sourceLines[$index].Trim()
            })
        }
    }
}
$expectedHistoricalLine = "# Pinned on 2026-08-17 at theme $historicalVersion + the three Oscars route-family"
$historicalHitDetails = @($historicalHits | ForEach-Object {
    '{0}:{1}:{2}' -f $_.FileName, $_.LineNumber, $_.LineText
}) -join '; '
Assert-Contract (
    $historicalHits.Count -eq 1 -and
    $historicalHits[0].FileName -ceq 'oscars-read-path-ratchet.ps1' -and
    $historicalHits[0].LineNumber -is [int] -and
    $historicalHits[0].LineNumber -gt 0 -and
    $historicalHits[0].LineText -ceq $expectedHistoricalLine
) `
    ("Current tests must preserve exactly the dated Oscars $historicalVersion provenance pin; found: " + $historicalHitDetails)

$deployIgnoreLines = @(Get-Content -LiteralPath (Join-Path $root '.deployignore'))
foreach ($requiredDeployIgnore in @('docs', 'docs/**', 'tests', 'tests/**')) {
    $matchCount = @($deployIgnoreLines | Where-Object { $_ -ceq $requiredDeployIgnore }).Count
    Assert-Contract ($matchCount -eq 1) `
        ".deployignore must contain exactly one $requiredDeployIgnore repository-only lock."
}

$releaseSeparator = [char]0x2014
$changelogHeading = "## 2026-09-05 $releaseSeparator Theme 3.2.58 Oscars Portal Fluid Rebuild"
$changelog = [IO.File]::ReadAllText((Join-Path $root 'docs/CHANGELOG.md'))
$changelogHeadings = @($changelog.Replace("`r`n", "`n").Split("`n") | Where-Object { $_ -like '## *' })
$changelogHeadingCount = @($changelogHeadings | Where-Object { $_ -ceq $changelogHeading }).Count
Assert-Contract ($changelogHeadingCount -eq 1) 'The 3.2.58 changelog heading must exist exactly once.'
# Deliberately NOT asserted: that this entry is the first heading in the
# changelog. docs/CHANGELOG.md covers all seven repositories (AGENTS.md), so a
# plugin-only release lands above the newest theme release without moving the
# theme's identity. Pinning absolute position froze the changelog on the first
# such entry (2026-09-04, Journal Foundation 1.2.14 and Dispatch 3.2.8), the
# same way the session-log pin froze the log (see the note below and the
# 2026-08-31 change that removed it). What is asserted instead: the 3.2.58
# entry is the newest THEME release entry. No heading above it may name a
# theme version, so a later theme release still has to regenerate this file.
$themeReleaseHeadingPattern = '^## .+ Theme \d+\.\d+\.\d+\b'
$changelogHeadingIndex = [array]::IndexOf($changelogHeadings, $changelogHeading)
$newerThemeHeadings = @()
if ($changelogHeadingIndex -gt 0) {
    $newerThemeHeadings = @($changelogHeadings[0..($changelogHeadingIndex - 1)] | Where-Object { $_ -match $themeReleaseHeadingPattern })
}
Assert-Contract ($changelogHeadingIndex -ge 0 -and $newerThemeHeadings.Count -eq 0) `
    'The 3.2.58 changelog entry must be the newest theme release entry; only plugin-only entries may sit above it.'
$changelogEntry = Get-TopEntry -Text $changelog -Heading $changelogHeading
foreach ($coverage in @(
    @{ Pattern = '(?is)1180.+1720'; Label = 'the width cap moving from 1180 to 1720 pixels' },
    @{ Pattern = '(?is)three (places|authorities).+critical'; Label = 'the three width authorities changing together' },
    @{ Pattern = '(?is)board.+card grid'; Label = 'the prediction board card grid' },
    @{ Pattern = '(?is)aat_landing_route_sections'; Label = 'the plugin landing composer hook' },
    @{ Pattern = '(?is)Latest Ceremony.+Winner Circle'; Label = 'the two duplicate hub blocks dropped' },
    @{ Pattern = '(?is)Poster Highlights.+2:3'; Label = 'the poster-first highlight cards' },
    @{ Pattern = '(?is)clamp\(.+floor'; Label = 'the fluid type scale with floors' },
    @{ Pattern = '(?is)rendered offline.+390.+2560'; Label = 'the offline render verification' },
    @{ Pattern = '(?is)Oscars Ledger 2\.7\.83'; Label = 'the companion plugin release' },
    @{ Pattern = '(?is)Not changed.+quality'; Label = 'the deliberately unfixed image quality setting' }
)) {
    Assert-Contract ($changelogEntry -match $coverage.Pattern) `
        "The 3.2.58 changelog entry must cover $($coverage.Label)."
}

$sessionHeading = "## 2026-09-05 $releaseSeparator Theme 3.2.58 Oscars portal rebuild and local candidate close"
$sessionLog = [IO.File]::ReadAllText((Join-Path $root 'docs/SESSION-LOG.md'))
$sessionHeadings = @($sessionLog.Replace("`r`n", "`n").Split("`n") | Where-Object { $_ -like '## *' })
$sessionHeadingCount = @($sessionHeadings | Where-Object { $_ -ceq $sessionHeading }).Count
Assert-Contract ($sessionHeadingCount -eq 1) 'The final 3.2.58 local-candidate session heading must exist exactly once.'
# Deliberately NOT asserted: that this entry is the newest in the session log.
# It was newest when written, but AGENTS.md requires every session to append a
# new entry at the top of docs/SESSION-LOG.md, so pinning position froze the log
# — the next compliant session could not pass CI. Position is a snapshot, not a
# property, in the same way a rollback SHA is (see the 2026-08-24 entry).
#
# What actually protects this release record is retained in full below: the
# heading exists exactly once, the entry still carries its CHANGELOG pointer,
# its explicit did-not-occur list, and the Dalton/Deployer-for-Git deployment
# boundary — and it still must not be rewritten to claim a deployment or a
# passing live canary. Those are read by heading, so later entries cannot
# weaken them.
$sessionEntry = Get-TopEntry -Text $sessionLog -Heading $sessionHeading
Assert-Contract ($sessionEntry.Contains('docs/CHANGELOG.md')) `
    'The newest session entry must point to docs/CHANGELOG.md for release detail.'
Assert-Contract ($sessionEntry.Contains('No deployment, cache operation, production write, or live verification occurred.')) `
    'The newest session entry must explicitly record every release action that did not occur.'
Assert-Contract ($sessionEntry -match '(?is)Dalton.+manual.+Deployer for Git') `
    'The newest session entry must name Dalton and manual Deployer for Git as the later deployment boundary.'
Assert-Contract ($sessionEntry -notmatch '(?im)^\s*(Deployment completed|Deployed to|Live canary passed|LIVE_COHERENT)\b') `
    'The local closure must not contain an affirmative deployment or live-canary claim.'

if ($script:Failures.Count -gt 0) {
    $details = $script:Failures | ForEach-Object { " - $_" }
    throw "Theme 3.2.58 release identity contract failed:`n$($details -join "`n")"
}

Write-Host 'Theme 3.2.58 release identity contract passed: exact stylesheet identity, stale-version census, deploy exclusions, and intact local-only release records.'
