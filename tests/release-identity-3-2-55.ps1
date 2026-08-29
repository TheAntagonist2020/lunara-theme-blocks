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
$exactVersionHeaders = @($styleLines | Where-Object { $_ -ceq 'Version: 3.2.55' })
Assert-Contract ($versionHeaderLines.Count -eq 1 -and $exactVersionHeaders.Count -eq 1) `
    'style.css must contain exactly one exact Version: 3.2.55 header.'

$priorVersion = @('3', '2', '54') -join '.'
$escapedPriorVersion = $priorVersion.Replace('.', '\.')
$trackedSources = @(& git -C $root ls-files -- 'tests/*.ps1' 'tests/*.php' 'tests/*.js')
if ($LASTEXITCODE -ne 0) {
    throw 'Unable to enumerate tracked test sources with git ls-files.'
}
Assert-Contract ($trackedSources.Count -gt 0) 'Tracked test-source discovery must return at least one file.'

$plainCount = 0
$escapedCount = 0
$pinCount = 0
$unexpectedHits = [System.Collections.Generic.List[string]]::new()
$pinPath = 'tests/oscars-read-path-ratchet.ps1'
$expectedPinLine = "# Pinned on 2026-08-17 at theme $priorVersion + the three Oscars route-family"

foreach ($relativePath in $trackedSources) {
    $normalizedPath = $relativePath.Replace('\', '/')
    $sourceLines = [IO.File]::ReadAllLines((Join-Path $root $relativePath))

    for ($index = 0; $index -lt $sourceLines.Count; $index++) {
        $line = $sourceLines[$index]
        $linePlainCount = Get-LiteralOccurrenceCount -Text $line -Needle $priorVersion
        $lineEscapedCount = Get-LiteralOccurrenceCount -Text $line -Needle $escapedPriorVersion
        $plainCount += $linePlainCount
        $escapedCount += $lineEscapedCount

        if ($linePlainCount -eq 1 -and $lineEscapedCount -eq 0 -and
            $normalizedPath -ceq $pinPath -and $line -ceq $expectedPinLine) {
            $pinCount++
            continue
        }

        if ($linePlainCount -gt 0 -or $lineEscapedCount -gt 0) {
            $unexpectedHits.Add(('{0}:{1} plain={2} escaped={3}' -f `
                $normalizedPath, ($index + 1), $linePlainCount, $lineEscapedCount))
        }
    }
}

Assert-Contract ($plainCount -eq 1) `
    "Tracked test sources must retain exactly one plain prior identity, the dated Oscars pin; found $plainCount."
Assert-Contract ($escapedCount -eq 0) `
    "Tracked test sources must retain no regex-escaped prior identity; found $escapedCount."
Assert-Contract ($pinCount -eq 1) `
    'The sole prior identity must be the exact 2026-08-17 Oscars read-path provenance pin.'
if ($unexpectedHits.Count -gt 0) {
    $sample = @($unexpectedHits | Select-Object -First 12) -join '; '
    Add-ContractFailure "Unexpected prior identity hits remain ($($unexpectedHits.Count)): $sample"
}

$deployIgnoreLines = @(Get-Content -LiteralPath (Join-Path $root '.deployignore'))
foreach ($requiredDeployIgnore in @('docs', 'docs/**', 'tests', 'tests/**')) {
    $matchCount = @($deployIgnoreLines | Where-Object { $_ -ceq $requiredDeployIgnore }).Count
    Assert-Contract ($matchCount -eq 1) `
        ".deployignore must contain exactly one $requiredDeployIgnore repository-only lock."
}

$changelogHeading = '## 2026-08-29 — Theme 3.2.55 Site Studio Foundation and Visual Site Map Pilot'
$changelog = [IO.File]::ReadAllText((Join-Path $root 'docs/CHANGELOG.md'))
$changelogHeadings = @($changelog.Replace("`r`n", "`n").Split("`n") | Where-Object { $_ -like '## *' })
$changelogHeadingCount = @($changelogHeadings | Where-Object { $_ -ceq $changelogHeading }).Count
Assert-Contract ($changelogHeadingCount -eq 1) 'The 3.2.55 changelog heading must exist exactly once.'
Assert-Contract ($changelogHeadings.Count -gt 0 -and $changelogHeadings[0] -ceq $changelogHeading) `
    'The 3.2.55 changelog entry must be the newest release entry.'
$changelogEntry = Get-TopEntry -Text $changelog -Heading $changelogHeading
foreach ($coverage in @(
    @{ Pattern = '(?is)normalized.+registry.+REST API'; Label = 'normalized registry and REST API' },
    @{ Pattern = '(?is)Global Design.+Homepage Structure.+Lunara Method'; Label = 'all three pilot surfaces' },
    @{ Pattern = '(?is)atomic.+theme mods.+post_content'; Label = 'atomic Homepage state' },
    @{ Pattern = '(?is)private preview.+section bridge'; Label = 'private preview and section bridge' },
    @{ Pattern = '(?is)dedicated.+responsive.+workspace.+1440.+768.+390'; Label = 'dedicated responsive workspace' },
    @{ Pattern = '(?is)twelve.+revision.+safety snapshot'; Label = 'twelve revisions and restore safety' },
    @{ Pattern = '(?is)Revision History.+refresh.+Save.+Restore'; Label = 'live Revision History refresh' },
    @{ Pattern = '(?is)Authorized private previews.+admin chrome.+Core'; Label = 'public-geometry admin chrome suppression' },
    @{ Pattern = '(?is)Save and Restore.+complete canonical mutation envelopes'; Label = 'strict Save and Restore mutation envelopes' }
)) {
    Assert-Contract ($changelogEntry -match $coverage.Pattern) `
        "The 3.2.55 changelog entry must cover $($coverage.Label)."
}

$sessionHeading = '## 2026-08-29 — Theme 3.2.55 final hardening and local candidate close'
$sessionLog = [IO.File]::ReadAllText((Join-Path $root 'docs/SESSION-LOG.md'))
$sessionHeadings = @($sessionLog.Replace("`r`n", "`n").Split("`n") | Where-Object { $_ -like '## *' })
$sessionHeadingCount = @($sessionHeadings | Where-Object { $_ -ceq $sessionHeading }).Count
Assert-Contract ($sessionHeadingCount -eq 1) 'The final 3.2.55 local-candidate session heading must exist exactly once.'
Assert-Contract ($sessionHeadings.Count -gt 0 -and $sessionHeadings[0] -ceq $sessionHeading) `
    'The final 3.2.55 local-candidate close must be the newest session entry.'
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
    throw "Theme 3.2.55 release identity contract failed:`n$($details -join "`n")"
}

Write-Host 'Theme 3.2.55 release identity contract passed: exact stylesheet identity, dual-form prior census, deploy exclusions, and newest local-only release records.'
