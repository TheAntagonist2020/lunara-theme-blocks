$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

# The previous two releases made the Oscars portal scale to the screen and then made
# it a poster wall. Holds the three width authorities together, the board
# poster grid in both the route sheet and the critical seed, the tile art
# layer, the portrait winners grid, the backdrop marquee, the drifting hero,
# the plugin composer hook that drops the duplicate hub blocks, the
# poster-first gallery authority, and the removal of the compact-pass caps.

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
Assert-Contract ($route -match '\.lunara-oscars-board-list\s*\{[^}]*display:\s*grid;[^}]*grid-template-columns:\s*repeat\(auto-fill,\s*minmax\(min\(100%,\s*190px\),\s*1fr\)\)') 'Route sheet board list must auto-fill 190px poster tiles.'
Assert-Contract ($route -match 'grid-template-areas:\s*"status"\s*"category"\s*"call"') 'Route sheet board row must stack the status chip, the category and the call.'
Assert-Contract ($seedPhp -match '\.lunara-oscars-board-list\{display:grid!important;gap:clamp\(8px,\.8vw,14px\)!important;grid-template-columns:repeat\(auto-fill,minmax\(min\(100%,var\(--lunara-oscars-portal-board-min-width,190px\)\),1fr\)\)!important') 'Critical seed board list must be the same auto-fill poster grid, with the tile floor behind the Studio board-rhythm variable and its 190px shipped fallback.'

# --- 3.2.73 presentation controls -------------------------------------------
# Every custom property the emitter can stamp must be consumed by the seed.
# A property that is emitted and never read is a control that saves cleanly
# and changes nothing, which is exactly what card_min_height did from the day
# it shipped until 3.2.73.
foreach ($pair in @(
    @{ Property = 'section-gap';       Label = 'section gap' },
    @{ Property = 'hero-min-height';   Label = 'hero minimum height' },
    @{ Property = 'card-min-height';   Label = 'card minimum height' },
    @{ Property = 'winners-min-width'; Label = 'winner portrait minimum width' },
    @{ Property = 'grid-gap';          Label = 'grid density' },
    @{ Property = 'hero-columns';      Label = 'hero prominence' },
    @{ Property = 'board-min-width';   Label = 'poster wall rhythm' }
)) {
    Assert-Contract ($seedPhp -match [regex]::Escape("'--lunara-oscars-portal-$($pair.Property)'")) "The emitter must stamp the $($pair.Label) property."
    Assert-Contract ($seedPhp -match [regex]::Escape("var(--lunara-oscars-portal-$($pair.Property)")) "The seed must CONSUME the $($pair.Label) property, not merely stamp it."
}

# The Studio's validated choices and the seed's value maps are two lists that
# must stay identical. A choice that validates but has no mapped value would
# fail the emitter closed and silently blank every variable.
$studioPhp = [IO.File]::ReadAllText((Join-Path $root 'inc/oscars-portal-studio.php'))
foreach ($enum in @(
    @{ Key = 'density';         Choices = @('standard','compact','showcase') },
    @{ Key = 'lead_prominence'; Choices = @('balanced','feature','gallery') },
    @{ Key = 'board_rhythm';    Choices = @('standard','gallery','dense') }
)) {
    foreach ($choice in $enum.Choices) {
        Assert-Contract ($studioPhp -match [regex]::Escape("'$choice'")) "The Studio must offer the $($enum.Key) choice '$choice'."
        Assert-Contract ($seedPhp -match [regex]::Escape("'$choice'")) "The seed value maps must carry the $($enum.Key) choice '$choice'."
    }
}

# One emitter, two consumers. page-oscars.php must not carry its own copy of
# the property list; it previously duplicated the sprintf and the gate.
$portalTemplate = [IO.File]::ReadAllText((Join-Path $root 'page-oscars.php'))
Assert-Contract ($portalTemplate -match 'lunara_oscars_portal_variable_declarations') 'The portal template must stamp the root from the shared declaration emitter.'
Assert-Contract (-not ($portalTemplate -match '--lunara-oscars-portal-section-gap:%1\$dpx')) 'The portal template must not carry a second copy of the custom-property list.'
Assert-Contract ($seedPhp -match 'grid-template-areas:"status" "category" "call"!important;grid-template-columns:minmax\(0,1fr\)!important;grid-template-rows:auto auto 1fr!important') 'Critical seed board row must stack the same three areas.'
Assert-Contract (-not ($route -match '"category status"') -and -not ($seedPhp -match '"category status"')) 'No authority may keep the side-by-side category and status row that let long categories run under the chip.'
Assert-Contract (-not ($seedPhp -match 'minmax\(0,\.72fr\) minmax\(0,1fr\) auto')) 'Critical seed must not retain the three-column list row.'
Assert-Contract (-not ($shell -match 'THE BOARD')) 'Shell must not carry a second copy of the board rules.'
# 2b. Every tile is a 2:3 poster with the art behind the copy (3.2.73).
$routeRow = [regex]::Match($route, '\.lunara-oscars-board-row\s*\{[^}]*\}').Value
Assert-Contract ($routeRow -match 'aspect-ratio:\s*2 / 3;' -and $routeRow -match 'position:\s*relative;' -and $routeRow -match 'overflow:\s*hidden;') 'Route sheet board row must be a 2:3 positioned poster tile.'
Assert-Contract ($seedPhp -match '\.lunara-oscars-board-row\{[^}]*aspect-ratio:2/3!important;[^}]*overflow:hidden!important;[^}]*position:relative!important\}') 'Critical seed board row must be the same 2:3 positioned poster tile.'
$routeArt = [regex]::Match($route, '\.lunara-oscars-board-art\s*\{[^}]*\}').Value
Assert-Contract ($routeArt -match 'inset:\s*0;' -and $routeArt -match 'position:\s*absolute;') 'Route sheet must pin the tile art to the tile.'
Assert-Contract ($route -match '\.lunara-oscars-board-art img\s*\{[^}]*object-fit:\s*cover;') 'Route sheet tile art must cover the tile.'
Assert-Contract ($seedPhp -match '\.lunara-oscars-board-art\{display:block!important;inset:0!important;margin:0!important;position:absolute!important\}') 'Critical seed must pin the tile art before the route sheet arrives.'
Assert-Contract ($seedPhp -match '\.lunara-oscars-board-art img\{[^}]*object-fit:cover!important;[^}]*\}') 'Critical seed tile art must cover the tile.'
Assert-Contract ($route -match '\.lunara-oscars-board-row\.has-art::after') 'Tiles with art must carry the legibility gradient.'
Assert-Contract ($route -match '\.lunara-oscars-board-row\.is-status-lost \.lunara-oscars-board-art img\s*\{[^}]*grayscale') 'Lost picks must desaturate their art.'
Assert-Contract ($portal -match 'class="lunara-oscars-board-art" aria-hidden="true"><img src="<\?php echo esc_url\( \$art_src \); \?>" alt="" loading="lazy" decoding="async" />') 'The renderer must emit tile art as a lazy, decorative, esc_url image.'

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

# 6b. Winners are portraits, the rotation is a marquee, the hero drifts (3.2.73).
Assert-Contract ($route -match '\.lunara-ceremony-winners-grid \.lunara-ceremony-winner-card\s*\{[^}]*aspect-ratio:\s*3 / 4 !important;[^}]*overflow:\s*hidden !important;') 'Ceremony winner cards must be 3:4 portraits.'
Assert-Contract ($route -match '\.lunara-ceremony-winners-grid \.lunara-ceremony-winner-card\.has-poster::after') 'Winner portraits must carry the legibility gradient.'
Assert-Contract ($route -match '\.lunara-oscars-winner-carousel-track \.lunara-oscars-winner-carousel-card\s*\{[^}]*aspect-ratio:\s*21 / 9 !important;') 'Marquee slides must be 21:9.'
Assert-Contract ($route -match '\.lunara-oscars-rotating-winners-section \.lunara-oscars-winner-carousel-card\.has-backdrop::before,[^{]*\.lunara-oscars-winner-carousel-card\.has-poster-backdrop::before\s*\{[^}]*background-image:\s*var\(--lunara-card-backdrop\);') 'Marquee slides must paint the film backdrop from the card variable.'
Assert-Contract ($route -match '\.lunara-oscars-rotating-winners-section \.lunara-oscars-winner-carousel-track \.lunara-oscars-winner-carousel-card\s*\{[^}]*flex:\s*0 0 100% !important;[^}]*max-width:\s*100% !important;') 'The marquee must show one slide per view, outranking the shell three-up flex basis.'

Assert-Contract ($shell -match '@keyframes lunara-oscars-hero-drift') 'Shell must define the hero drift.'
$pageTemplate = [IO.File]::ReadAllText((Join-Path $root 'page-oscars.php'))
Assert-Contract ($pageTemplate -match 'lunara-oscars-portal-slot-hero<\?php echo '''' !== \$hero_style \? '' has-backdrop'' : ''''; \?>"') 'The live page template must stamp has-backdrop on the hero it renders, or the drift never fires (3.2.73).'
Assert-Contract ($pageTemplate -match 'linear-gradient\(112deg, rgba\(7,16,27,\.9\) 0%, rgba\(7,16,27,\.66\) 34%, rgba\(7,16,27,\.34\) 58%') 'The live page template hero gradient must let the backdrop read through its middle.'
Assert-Contract (-not ($pageTemplate -match 'linear-gradient\(120deg, rgba\(7,16,27,\.92\)')) 'The live page template must not keep the near-opaque 120deg hero gradient.'
Assert-Contract ($shell -match '\.lunara-oscars-portal-hero\.has-backdrop\s*\{\s*\r?\n\s*animation:\s*lunara-oscars-hero-drift') 'The hero must drift only when it has a backdrop.'
Assert-Contract ($shell -match '@media \(prefers-reduced-motion: reduce\)\s*\{\s*\r?\n\s*body\.lunara-oscars-portal-page \.lunara-oscars-portal-hero\.has-backdrop\s*\{\s*\r?\n\s*animation:\s*none') 'Reduced motion must stop the hero drift.'
Assert-Contract ($pageTemplate -match 'style="--lunara-card-backdrop:url\(''<\?php echo esc_url\( \$w_bg \); \?>''\)"') 'The rotation card must hand its backdrop to CSS through esc_url.'
Assert-Contract ($portal -match "function\s+lunara_oscars_portal_warm_visuals\(") 'The daily backdrop warmer must exist.'
Assert-Contract ($portal -match "add_action\(\s*'lunara_oscars_portal_warm_visuals',\s*'lunara_oscars_portal_warm_visuals'\s*\)") 'The warmer must be hooked to its cron event.'
Assert-Contract ($portal -match "wp_schedule_event\(\s*time\(\)\s*\+\s*\d+,\s*'daily',\s*'lunara_oscars_portal_warm_visuals'\s*\)") 'The warmer must be scheduled daily.'
Assert-Contract ($portal -match "get_title_visual_package\(\s*\`$tt,\s*'large',\s*true\s*\)") 'Only the warmer may fetch remotely; it must ask for the large package with remote allowed.'
$rendererBody = [regex]::Match($portal, 'function\s+lunara_oscars_pick_visuals\s*\((?:(?!function\s)[\s\S])*').Value
Assert-Contract ($rendererBody.Length -gt 0 -and $rendererBody -notmatch 'get_title_visual_package\([^)]*true') 'The render-path visuals resolver must never allow a remote fetch.'

# 7. Budgets that the studio and payload contracts also hold, restated here so a regression names itself.
# The earlier poster-wall release raised the ceiling from 45,000 to 57,344 bytes (56 KB): the
# poster wall, the portrait winners and the marquee are three image-led
# blocks that each need their own layer rules. docs/CHANGELOG.md records it.
$routeBytes = [Text.Encoding]::UTF8.GetByteCount($route)
Assert-Contract ($routeBytes -le 57344) "Route sheet exceeds its 57,344-byte ceiling: $routeBytes."

if ($failures.Count -gt 0) {
    $details = $failures | ForEach-Object { " - $_" }
    throw "Oscars portal fluid contract failed:`n$($details -join "`n")"
}

Write-Host 'Theme 3.2.73 Oscars portal fluid contract passed: one 1720px cap in three authorities, board poster wall with tile art, portrait winners, backdrop marquee, drifting hero, daily warmer, composer hook, poster-first gallery, caps removed.'
