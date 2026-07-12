$ErrorActionPreference = 'Stop'

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

# Transitional compatibility only. Counts may fall as content is migrated,
# but CI must reject new tags or any increase in registrations.
$registrationBudget = [ordered]@{
    'lunara_carousel'         = 2
    'lunara_debrief'          = 2
    'lunara_home'             = 1
    'lunara_journal_carousel' = 1
    'lunara_pair_it_with'     = 1
    'lunara_posts'            = 1
    'lunara_reviews'          = 1
    'lunara_shot_reel'        = 1
    'lunara_still'            = 2
    'lunara_trailer'          = 1
    'lunara_where_to_watch'   = 1
}

$phpFiles = Get-ChildItem -LiteralPath $themeRoot -Recurse -File -Filter '*.php' |
    Where-Object { $_.FullName -notmatch '[\\/](vendor|node_modules)[\\/]' }

$registrationPattern = [regex]::new(
    'add_shortcode\s*\(\s*[''\"]([^''\"]+)[''\"]',
    [Text.RegularExpressions.RegexOptions]::IgnoreCase
)
$doShortcodePattern = [regex]::new(
    '(?<![A-Za-z0-9_])do_shortcode\s*\(',
    [Text.RegularExpressions.RegexOptions]::IgnoreCase
)

$registrations = @{}
$locations = @{}
$doShortcodeCount = 0

foreach ($file in $phpFiles) {
    $content = Get-Content -Raw -LiteralPath $file.FullName
    $relative = [IO.Path]::GetRelativePath($themeRoot, $file.FullName)

    foreach ($match in $registrationPattern.Matches($content)) {
        $tag = $match.Groups[1].Value
        Assert-True $registrationBudget.Contains($tag) "New shortcode registration is forbidden: $tag in $relative. Build a native block instead."

        if (-not $registrations.ContainsKey($tag)) {
            $registrations[$tag] = 0
            $locations[$tag] = @()
        }

        $registrations[$tag]++
        $locations[$tag] += $relative
    }

    $doShortcodeCount += $doShortcodePattern.Matches($content).Count
}

foreach ($tag in $registrationBudget.Keys) {
    $actual = if ($registrations.ContainsKey($tag)) { [int] $registrations[$tag] } else { 0 }
    $limit = [int] $registrationBudget[$tag]
    $where = if ($locations.ContainsKey($tag)) { $locations[$tag] -join ', ' } else { 'none' }

    Assert-True ($actual -le $limit) "Shortcode registration budget grew for $tag`: $actual found, $limit allowed ($where)."
}

Assert-True ($doShortcodeCount -le 1) "Direct do_shortcode() calls grew to $doShortcodeCount. Native block renderers must call PHP render functions directly."

$homeBlocks = Get-Content -Raw -LiteralPath (Join-Path $themeRoot 'inc/home-blocks.php')
Assert-True ($homeBlocks -match 'return\s+do_blocks\(\s*\$content\s*\)') 'Home must remain a native block composition rendered through do_blocks().'
Assert-True (-not $doShortcodePattern.IsMatch($homeBlocks)) 'Home block composition must not regress to do_shortcode().'

$legacyBlocks = Get-Content -Raw -LiteralPath (Join-Path $themeRoot 'inc/blocks.php')
Assert-True ($legacyBlocks -match "'inserter'\s*=>\s*false") 'Legacy shortcode bridge blocks must stay hidden from the inserter during retirement.'

$summary = $registrationBudget.Keys |
    ForEach-Object {
        $actual = if ($registrations.ContainsKey($_)) { [int] $registrations[$_] } else { 0 }
        "$_=$actual/$($registrationBudget[$_])"
    }

Write-Host ("Shortcode retirement budget passed: " + ($summary -join '; ') + "; do_shortcode=$doShortcodeCount/1")
