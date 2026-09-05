$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

# Theme 3.2.59 — the Oscars portal scales to the screen.
# Holds the three width authorities together, the board card grid in both
# the route sheet and the critical seed, the plugin composer hook that drops
# the duplicate hub blocks, the poster-first gallery authority, and the
# removal of the compact-pass caps that squeezed fact and winner cards.

$root = Split-Path -Parent $PSScriptRoot
$failures = [System.Collections.Generic.List[string]]::new()
function Assert-Contract {
    param([bool]$Condition, [string]$Message)
    if (-not $Condition) { $failures.Add($Message) }
}

$route    = [IO.File]::ReadAllText((Join-Path $root 'assets/css/lunara-oscars-portal.css'))
$shell    = [IO.File]::ReadAllText((Join-Path $root 'assets/css/lunara-shell.css'))
$seedPhp  = [IO.File]::ReadAllText((Join-Path $root 'inc/oscars-portal-critical.php'))
$portal   = [IO.File]::ReadAllText((Join-Path $root 'inc/oscars-portal.php'))

# 1. One width, three authorities.
Assert-Contract ($route -match 'max-width:\s*min\(100%,\s*1720px\)\s*!important') 'Route sheet must cap the portal at 1720px.'
Assert-Contract ($shell -match 'width:\s*min\(1720px,\s*calc\(100vw - 48px\)\)\s*!important;\s*\r?\n\s*max-width:\s*1720px\s*!important') 'Shell authority must cap the portal at 1720px.'
Assert-Contract ($seedPhp -match 'max-width:1720px!important') 'Critical seed must cap the portal at 1720px.'
Assert-Contract ($seedPhp -match 'width:min\(1720px,calc\(100vw - 48px\)\)!important') 'Critical seed width must follow the 1720px cap.'
Assert-Contract (-not ($route -match 'min\(100%,\s*1180px\)')) 'Route sheet must not retain the 1180px portal cap.'
Assert-Contract (-not ($shell -match 'min\(1180px,\s*calc\(100vw - 48px\)\)')) 'Shell must not retain the 1180px portal cap.'
Assert-Contract (-not ($seedPhp -match 'max-width:1180px|min\(1180px')) 'Critical seed must not retain the 1180px portal cap.'

# 2. The board is a card grid, in the route sheet and in the seed that outranks it.
Assert-Contract ($route -match '\.lunara-oscars-board-list\s*\{[^}]*display:\s*grid;[^}]*grid-template-columns:\s*repeat\(auto-fill,\s*minmax\(min\(100%,\s*250px\),\s*1fr\)\)') 'Route sheet board list must auto-fill 250px tiles.'
Assert-Contract ($route -match 'grid-template-areas:\s*"category status"\s*"call call"') 'Route sheet board row must place category and status above the call.'
Assert-Contract ($seedPhp -match '\.lunara-oscars-board-list\{display:grid!important;gap:clamp\(8px,\.8vw,14px\)!important;grid-template-columns:repeat\(auto-fill,minmax\(min\(100%,250px\),1fr\)\)!important') 'Critical seed board list must be the same auto-fill grid.'
Assert-Contract ($seedPhp -match 'grid-template-areas:"category status" "call call"!important') 'Critical seed board row must carry the card areas.'
Assert-Contract (-not ($seedPhp -match 'minmax\(0,\.72fr\) minmax\(0,1fr\) auto')) 'Critical seed must not retain the three-column list row.'
Assert-Contract (-not ($shell -match 'THE BOARD')) 'Shell must not carry a second copy of the board rules.'

# 3. Wide screens get six-up rows where the count is six.
Assert-Contract ($route -match '@media \(min-width: 1500px\)') 'Route sheet must carry the 1500px wide layer.'
Assert-Contract ($seedPhp -match '@media\(min-width:1500px\)') 'Critical seed must carry the 1500px wide layer.'

# 4. One owner per block: the theme hooks the plugin composer and drops exactly the two duplicates.
Assert-Contract ($portal -match "add_filter\(\s*'aat_landing_route_sections',\s*'lunara_oscars_portal_landing_sections'\s*\)") 'Theme must hook aat_landing_route_sections.'
Assert-Contract ($portal -match "unset\(\s*\`$sections\['ceremony-marquee'\],\s*\`$sections\['winner-circle'\]\s*\)") 'Theme must drop the marquee and the winner circle, and nothing else.'
Assert-Contract ($portal -match "lunara_is_oscars_portal_page\(\)\s*\)\s*\{\s*\r?\n\s*return \`$sections;") 'The hook must be inert off the portal page.'

# 5. Poster-first gallery authority sits last in the shell.
$galleryStart = $shell.LastIndexOf('poster-first hub gallery', [StringComparison]::Ordinal)
Assert-Contract ($galleryStart -gt 0) 'Shell must carry the poster-first gallery block.'
if ($galleryStart -gt 0) {
    $gallery = $shell.Substring($galleryStart)
    Assert-Contract ($gallery -match 'aat-filmography-poster-wrap \{[^}]*aspect-ratio:\s*2 / 3\s*!important') 'Gallery posters must be 2:3.'
    Assert-Contract ($gallery -match 'grid-column:\s*auto\s*!important') 'Gallery cards must not span tracks.'
    Assert-Contract ($gallery -match 'repeat\(6, minmax\(0, 1fr\)\)') 'Gallery must be six across on desktop.'
    Assert-Contract ($gallery -match '@media \(max-width: 430px\)') 'Gallery must carry the phone layer.'
}

# 6. Compact-pass caps no longer squeeze fact and winner cards.
Assert-Contract (-not ($shell -match '\.lunara-oscars-portal-fact-card,\s*\r?\n[^{]*\{\s*\r?\n\s*border-radius: 14px !important;\s*\r?\n\s*max-width: 142px !important;')) 'Fact cards must not carry the 142px cap.'
Assert-Contract ($shell -match '\.lunara-ceremony-winner-card \{\s*\r?\n\s*max-width: none !important;') 'Ceremony winner cards must fill their column.'
Assert-Contract ($route -match '\.lunara-ceremony-winner-card:not\(:has\(\.lunara-ceremony-winner-media-link\)\)') 'Winner cards without media must collapse to one column.'

# 7. Budgets that the studio and payload contracts also hold, restated here so a regression names itself.
$routeBytes = [Text.Encoding]::UTF8.GetByteCount($route)
Assert-Contract ($routeBytes -le 45000) "Route sheet exceeds its 45,000-byte ceiling: $routeBytes."

if ($failures.Count -gt 0) {
    $details = $failures | ForEach-Object { " - $_" }
    throw "Oscars portal fluid contract failed:`n$($details -join "`n")"
}

Write-Host 'Theme 3.2.59 Oscars portal fluid contract passed: one 1720px cap in three authorities, board card grid, composer hook, poster-first gallery, caps removed.'
