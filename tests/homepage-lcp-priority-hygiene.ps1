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

function Read-ThemeFile {
    param([string] $RelativePath)

    $path = Join-Path $themeRoot $RelativePath
    Assert-True (Test-Path $path) "Missing expected file: $RelativePath"
    return Get-Content -Raw $path
}

$setup = Read-ThemeFile 'inc/setup.php'
$frontend = Read-ThemeFile 'inc/frontend.php'
$cinematicHome = Read-ThemeFile 'inc/cinematic-home.php'
$headerCommand = Read-ThemeFile 'inc/header-command.php'
$homeSections = Read-ThemeFile 'inc/home-sections.php'
$reviewRendering = Read-ThemeFile 'inc/review-rendering.php'
$heroDelivery = Read-ThemeFile 'inc/hero-delivery.php'
$functions = Read-ThemeFile 'functions.php'
$frontPage = Read-ThemeFile 'front-page.php'
$style = Read-ThemeFile 'style.css'

$leadRenderer = [regex]::Match(
    $frontend,
    '(?ms)function lunara_home_front_door_lead_image\( \$lead \).*?^}'
).Value
Assert-True ('' -ne $leadRenderer) 'Could not isolate the Front Desk lead image renderer.'
Assert-True ($leadRenderer -match "'loading'\s*=>\s*'eager'") 'The true Front Desk LCP image must stay eager.'
Assert-True ($leadRenderer -match "'fetchpriority'\s*=>\s*'high'") 'The true Front Desk LCP image must stay high priority.'

$mastheadLogo = [regex]::Match(
    $frontend,
    "(?s)'class'\s*=>\s*'lunara-home-masthead-logo skip-lazy no-lazy'.*?'alt'\s*=>\s*''"
).Value
Assert-True ('' -ne $mastheadLogo) 'Could not isolate the Home masthead logo attributes.'
Assert-True ($mastheadLogo -match "'loading'\s*=>\s*'eager'") 'The Home masthead logo must remain eager so brand art is immediately visible.'
Assert-True ($mastheadLogo -match "'fetchpriority'\s*=>\s*'auto'") 'The Home masthead logo must use normal fetch priority.'
Assert-True ($mastheadLogo -notmatch "'fetchpriority'\s*=>\s*'high'") 'The Home masthead logo must not compete with the true LCP image.'

$logoPriorityHelper = [regex]::Match(
    $setup,
    '(?ms)function lunara_custom_logo_fetch_priority\(\).*?^}'
).Value
Assert-True ('' -ne $logoPriorityHelper) 'The route-aware custom-logo priority helper is missing.'
Assert-True ($logoPriorityHelper -match "is_front_page\(\)\s*\?\s*'auto'\s*:\s*'high'") 'Header logos must use normal priority on Home and retain high priority elsewhere.'
Assert-True (([regex]::Matches($setup, "'fetchpriority'\]\s*=\s*lunara_custom_logo_fetch_priority\(\)")).Count -eq 2) 'Both custom-logo attribute filters must use the route-aware priority helper.'
Assert-True ($frontend -match "class_exists\(\s*'WP_HTML_Tag_Processor'\s*\)") 'The Home masthead must finalize attributes through WordPress structured HTML processing.'
Assert-True ($frontend -match '\$logo_processor->set_attribute\(\s*''fetchpriority'',\s*''auto''\s*\)') 'The final Home masthead markup must use normal fetch priority.'
Assert-True ($frontend -match '\$logo_html\s*=\s*\$logo_processor->get_updated_html\(\)') 'The structured masthead update must replace the filtered attachment markup.'
Assert-True ($headerCommand -match '\$fetchpriority\s*=\s*function_exists\(\s*''lunara_custom_logo_fetch_priority''\s*\)') 'The optional Header Command logo must reuse the route-aware priority helper.'
Assert-True ($headerCommand -match 'fetchpriority="%4\$s"') 'Header Command markup must print the resolved route-aware priority.'

$activeLatestReviews = [regex]::Match(
    $homeSections,
    '(?ms)function lunara_render_homepage_latest_reviews\( \$attrs = array\(\) \).*?^}'
).Value
$fallbackLatestReviews = [regex]::Match(
    $functions,
    '(?ms)function lunara_render_homepage_latest_reviews\( \$attrs = array\(\) \).*?^}'
).Value
Assert-True ('' -ne $activeLatestReviews) 'Could not isolate the active Home Latest Reviews renderer.'
Assert-True ('' -ne $fallbackLatestReviews) 'Could not isolate the fallback Home Latest Reviews renderer.'
Assert-True ($activeLatestReviews -notmatch "'fetchpriority'\]\s*=\s*'high'") 'The lower active Latest Reviews rail must not claim LCP priority.'
Assert-True ($fallbackLatestReviews -notmatch "'fetchpriority'\]\s*=\s*'high'") 'The lower fallback Latest Reviews rail must not claim LCP priority.'
Assert-True ($activeLatestReviews -match "'loading'\s*=>\s*'lazy'") 'The lower active Latest Reviews rail must use native lazy loading.'
Assert-True ($activeLatestReviews -match "'fetchpriority'\s*=>\s*'low'") 'The lower active Latest Reviews rail must use low fetch priority.'
Assert-True ($fallbackLatestReviews -match "'loading'\s*=>\s*'lazy'") 'The lower fallback Latest Reviews rail must use native lazy loading.'
Assert-True ($fallbackLatestReviews -match "'fetchpriority'\s*=>\s*'low'") 'The lower fallback Latest Reviews rail must use low fetch priority.'

$journalLane = [regex]::Match(
    $functions,
    '(?ms)function lunara_render_homepage_journal_lane\(\).*?^}'
).Value
Assert-True ('' -ne $journalLane) 'Could not isolate the Home Journal lane renderer.'
Assert-True ($journalLane -match "'loading'\s*=>\s*'lazy'") 'The lower Home Journal lane must use native lazy loading.'
Assert-True ($journalLane -match "'fetchpriority'\s*=>\s*'low'") 'The lower Home Journal lane must use low fetch priority.'
Assert-True ($journalLane -notmatch 'skip-lazy|no-lazy') 'The lower Home Journal lane must remain eligible for native and optimizer lazy loading.'

$oscarPicks = [regex]::Match(
    $functions,
    '(?ms)function lunara_render_oscar_picks_carousel\( \$args = array\(\) \).*?^}'
).Value
$oscarFacts = [regex]::Match(
    $functions,
    '(?ms)function lunara_render_oscar_facts_carousel\( \$args = array\(\) \).*?^}'
).Value
Assert-True ('' -ne $oscarPicks) 'Could not isolate the Home Oscar Picks renderer.'
Assert-True ('' -ne $oscarFacts) 'Could not isolate the Home Oscar Facts renderer.'
Assert-True ($oscarPicks -match "'loading'\s*=>\s*'lazy'") 'The lower Home Oscar Picks carousel must use native lazy loading.'
Assert-True ($oscarPicks -match "'fetchpriority'\s*=>\s*'low'") 'The lower Home Oscar Picks carousel must use low fetch priority.'
Assert-True ($oscarFacts -match "'loading'\s*=>\s*'lazy'") 'The lower Home Oscar Facts carousel must use native lazy loading.'
Assert-True ($oscarFacts -match "'fetchpriority'\s*=>\s*'low'") 'The lower Home Oscar Facts carousel must use low fetch priority.'
Assert-True ($oscarFacts -notmatch '--lunara-fact-image-url|\$visual_image_url') 'Archival Oscar Facts must not bypass lazy loading through a CSS background image.'

Assert-True ($reviewRendering -match '\$is_priority_image\s*=\s*''eager''\s*===') 'The shared Review image helper must classify priority from the requested loading attributes.'
Assert-True ($reviewRendering -match 'if \( \$is_priority_image \) \{[\s\S]*?data-no-lazy[\s\S]*?data-skip-lazy[\s\S]*?\} else \{[\s\S]*?unset\( \$attrs\[''data-no-lazy''\], \$attrs\[''data-skip-lazy''\] \)') 'Only eager/high Review images may opt out of optimizer lazy loading.'
Assert-True ($reviewRendering -match 'lunara_remove_img_attribute\( \$html, ''data-no-lazy'' \)') 'Locked lazy Review markup must remove stale optimizer exclusions.'
Assert-True ($reviewRendering -match 'lunara_remove_img_attribute\( \$html, ''data-skip-lazy'' \)') 'Locked lazy Review markup must remove stale optimizer skip flags.'

Assert-True ($frontPage -match '(?s)if \(\s*''hero''\s*===\s*\$lunara_slug\s*\).*?call_user_func\(\s*\$lunara_callback,\s*array\(\s*''first_image_is_lcp''\s*=>\s*false\s*\)') 'The lower Home hero must explicitly opt out of LCP priority.'
Assert-True ($frontPage -match '\$lunara_front_door\s*=\s*\(string\)\s*lunara_render_home_front_door\(\)') 'Home must capture its active front-door markup before deciding whether a semantic H1 is missing.'
Assert-True ($frontPage.Contains('$lunara_block_composition      = $lunara_uses_block_composition')) 'Home must capture editable block composition before deciding whether a semantic H1 is missing.'
Assert-True ($frontPage.Contains('$lunara_heading_parts = array( $lunara_front_door, $lunara_block_composition );')) 'The semantic guard must inspect both front-door and editable block markup.'
Assert-True ($frontPage -match "class_exists\(\s*'WP_HTML_Tag_Processor'\s*\)") 'Home must use WordPress structured HTML processing when detecting a real H1.'
Assert-True ($frontPage -match "next_tag\(\s*array\(\s*'tag_name'\s*=>\s*'H1'\s*\)\s*\)") 'The structured Home heading guard must look specifically for an H1 element.'
Assert-True ($frontPage -match "preg_match\(\s*'/<h1\(\?:\\s\|>\)/i',\s*\`$lunara_heading_part\s*\)") 'Home must retain a compatibility H1 guard for older WordPress versions.'
Assert-True ($frontPage -match '<h1 class="screen-reader-text lunara-screen-reader-text">') 'The Home fallback H1 must remain geometry-neutral and accessible.'
Assert-True (([regex]::Matches($frontPage, 'echo \$lunara_front_door')).Count -eq 1) 'Home must emit the captured front-door markup exactly once.'
Assert-True (([regex]::Matches($frontPage, 'echo \$lunara_block_composition')).Count -eq 1) 'Home must emit captured editable block composition exactly once.'
$blockCompositionStart = $frontPage.IndexOf('if ( $lunara_uses_block_composition )')
$rendererMapStart = $frontPage.IndexOf('$lunara_section_renderers = array(')
$rendererMapEnd = $frontPage.IndexOf('$lunara_render_slugs = array_keys( $lunara_section_renderers );')
Assert-True ($blockCompositionStart -ge 0) 'Could not locate the editable Home block-composition gate.'
Assert-True ($rendererMapStart -gt $blockCompositionStart) 'The Customizer renderer map must remain after the editable block-composition gate.'
Assert-True ($rendererMapEnd -gt $rendererMapStart) 'Could not isolate the Customizer renderer map and single-hero gate.'
$blockCompositionGate = $frontPage.Substring($blockCompositionStart, $rendererMapStart - $blockCompositionStart)
$customizerHeroGate = $frontPage.Substring($rendererMapStart, $rendererMapEnd - $rendererMapStart)
Assert-True ($blockCompositionGate.Contains('echo $lunara_block_composition')) 'Editable block composition must still emit its captured markup.'
Assert-True ($blockCompositionGate -match 'get_footer\(\);\s*return;') 'Editable block composition must exit before Customizer sections render.'
Assert-True ($customizerHeroGate.Contains("'hero'           => 'lunara_render_cinematic_hero_carousel'")) 'The legacy hero mapping must remain available when the cinematic front door is absent.'
Assert-True ($frontPage -match '\$lunara_front_door_has_canonical_hero\s*=\s*''{2}\s*!==\s*\$lunara_front_door\s*&&\s*false\s*!==\s*strpos\(\s*\$lunara_front_door,\s*''data-lunara-home-hero-source=''\s*\)') 'The canonical-hero state must require both nonempty front-door markup and its explicit cinematic source marker.'
Assert-True ($customizerHeroGate -match 'if \( \$lunara_front_door_has_canonical_hero \)') 'The duplicate-hero guard must reuse the canonical-hero state.'
Assert-True (([regex]::Matches($customizerHeroGate, "unset\( \`$lunara_section_renderers\['hero'\] \)")).Count -eq 1) 'The duplicate-hero guard must remove the legacy hero exactly once before render order is calculated.'
Assert-True ($cinematicHome.Contains('data-lunara-home-hero-source="native"')) 'The native cinematic front-door wrapper must retain the duplicate-detection marker.'
Assert-True ($cinematicHome.Contains('data-lunara-home-hero-source="plugin"')) 'The plugin-backed cinematic front-door wrapper must retain the duplicate-detection marker.'
Assert-True ($functions -match '(?s)register_block_type\(\s*''lunara/cinematic-hero''.*?if \( is_front_page\(\) \) \{\s*\$attributes\[''first_image_is_lcp''\]\s*=\s*false;') 'The editable Home cinematic-hero block must opt out after the Front Desk has claimed LCP.'
Assert-True ($functions -match 'function lunara_render_cinematic_hero_slide\( \$data, \$index = 0, \$first_image_is_lcp = true \)') 'Cinematic hero slides must retain a backward-compatible LCP context argument.'
Assert-True ($functions -match '\$is_priority_image\s*=\s*\$is_first\s*&&\s*\(bool\) \$first_image_is_lcp') 'Only the first slide in a true front-door context may receive high priority.'
Assert-True ($heroDelivery -match '''loading''\s*=>\s*\$is_priority\s*\?\s*''eager''\s*:\s*''lazy''') 'Non-LCP cinematic hero images must use native lazy loading.'
Assert-True ($heroDelivery -match '''fetchpriority''\s*=>\s*\$is_priority\s*\?\s*''high''\s*:\s*''low''') 'Non-LCP cinematic hero images must use low fetch priority.'
Assert-True ($heroDelivery -match 'if\s*\(\s*\$is_priority\s*\)[\s\S]*?''data-no-lazy''[\s\S]*?''data-skip-lazy''') 'Only the true hero LCP may opt out of optimizer lazy loading.'
Assert-True (([regex]::Matches($functions, "array_key_exists\(\s*'first_image_is_lcp'")).Count -eq 2) 'Both static and carousel hero renderers must honor the LCP context flag.'
Assert-True ($functions -match 'lunara_render_cinematic_hero_slide\( \$slide_data, \$slide_index, \$first_image_is_lcp \)') 'The carousel must pass its LCP context into every slide renderer.'
Assert-True ($style -match 'Version:\s*3\.2\.42') 'Theme version must be 3.2.42 for the mobile CLS gate.'

Write-Host 'Homepage LCP priority hygiene contract passed.'
