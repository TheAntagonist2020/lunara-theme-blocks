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
$exactVersionHeaders = @($styleLines | Where-Object { $_ -ceq 'Version: 3.2.56' })
Assert-Contract ($versionHeaderLines.Count -eq 1 -and $exactVersionHeaders.Count -eq 1) `
    'style.css must contain exactly one exact Version: 3.2.56 header.'

$priorVersion = @('3', '2', '55') -join '.'
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
$changelogHeading = "## 2026-08-29 $releaseSeparator Theme 3.2.56 Site Studio Editorial and Utility Workspaces"
$changelog = [IO.File]::ReadAllText((Join-Path $root 'docs/CHANGELOG.md'))
$changelogHeadings = @($changelog.Replace("`r`n", "`n").Split("`n") | Where-Object { $_ -like '## *' })
$changelogHeadingCount = @($changelogHeadings | Where-Object { $_ -ceq $changelogHeading }).Count
Assert-Contract ($changelogHeadingCount -eq 1) 'The 3.2.56 changelog heading must exist exactly once.'
Assert-Contract ($changelogHeadings.Count -gt 0 -and $changelogHeadings[0] -ceq $changelogHeading) `
    'The 3.2.56 changelog entry must be the newest release entry.'
$changelogEntry = Get-TopEntry -Text $changelog -Heading $changelogHeading
foreach ($coverage in @(
    @{ Pattern = '(?is)Reviews Archive.+Journal Archive.+Review Single.+Utility Search.+Site Footer'; Label = 'all five editorial and utility surfaces' },
    @{ Pattern = '(?is)canonical.+no second settings'; Label = 'canonical ownership without duplicate storage' },
    @{ Pattern = '(?is)section order.+visibility.+canonical'; Label = 'canonical archive ordering and visibility' },
    @{ Pattern = '(?is)private preview.+q=Lunara.+section bridge'; Label = 'fixed-query private preview and section bridge' },
    @{ Pattern = '(?is)twelve.+revision.+safety\s+snapshot'; Label = 'twelve revisions and restore safety' },
    @{ Pattern = '(?is)plain-language.+1440.+768.+390'; Label = 'responsive plain-language workspace' },
    @{ Pattern = '(?is)Core 0\.8\.9.+Journal\s+Foundation 1\.2\.13.+Dispatch 3\.2\.7'; Label = 'plugin-first compatibility releases' },
    @{ Pattern = '(?is)same-origin admin anchors.+fail closed'; Label = 'safe guided handoff anchors' },
    @{ Pattern = '(?is)404-only.+Classic controls'; Label = '404-only Classic controls ownership boundary' },
    @{ Pattern = '(?is)Removed.+version-change.+Header.+Hero.+purge'; Label = 'version-change, Header, and Hero purge removal' }
)) {
    Assert-Contract ($changelogEntry -match $coverage.Pattern) `
        "The 3.2.56 changelog entry must cover $($coverage.Label)."
}

$sessionHeading = "## 2026-08-29 $releaseSeparator Theme 3.2.56 final hardening and local candidate close"
$sessionLog = [IO.File]::ReadAllText((Join-Path $root 'docs/SESSION-LOG.md'))
$sessionHeadings = @($sessionLog.Replace("`r`n", "`n").Split("`n") | Where-Object { $_ -like '## *' })
$sessionHeadingCount = @($sessionHeadings | Where-Object { $_ -ceq $sessionHeading }).Count
Assert-Contract ($sessionHeadingCount -eq 1) 'The final 3.2.56 local-candidate session heading must exist exactly once.'
Assert-Contract ($sessionHeadings.Count -gt 0 -and $sessionHeadings[0] -ceq $sessionHeading) `
    'The final 3.2.56 local-candidate close must be the newest session entry.'
$sessionEntry = Get-TopEntry -Text $sessionLog -Heading $sessionHeading
Assert-Contract ($sessionEntry.Contains('docs/CHANGELOG.md')) `
    'The newest session entry must point to docs/CHANGELOG.md for release detail.'
Assert-Contract ($sessionEntry.Contains('No deployment, cache operation, production write, live verification, push, merge, or PR occurred.')) `
    'The newest session entry must explicitly record every release action that did not occur.'
Assert-Contract ($sessionEntry -match '(?is)Dalton.+manual.+Deployer for Git') `
    'The newest session entry must name Dalton and manual Deployer for Git as the later deployment boundary.'
Assert-Contract ($sessionEntry -notmatch '(?im)^\s*(Deployment completed|Deployed to|Live canary passed|LIVE_COHERENT)\b') `
    'The local closure must not contain an affirmative deployment or live-canary claim.'

if ($script:Failures.Count -gt 0) {
    $details = $script:Failures | ForEach-Object { " - $_" }
    throw "Theme 3.2.56 release identity contract failed:`n$($details -join "`n")"
}

Write-Host 'Theme 3.2.56 release identity contract passed: exact stylesheet identity, stale-version census, deploy exclusions, and newest local-only release records.'
