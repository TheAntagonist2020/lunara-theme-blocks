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
$headerCommand = Read-ThemeFile 'inc/header-command.php'
$homeSections = Read-ThemeFile 'inc/home-sections.php'
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

Assert-True ($frontPage -match '(?s)if \(\s*''hero''\s*===\s*\$lunara_slug\s*\).*?call_user_func\(\s*\$lunara_callback,\s*array\(\s*''first_image_is_lcp''\s*=>\s*false\s*\)') 'The lower Home hero must explicitly opt out of LCP priority.'
Assert-True ($functions -match '(?s)register_block_type\(\s*''lunara/cinematic-hero''.*?if \( is_front_page\(\) \) \{\s*\$attributes\[''first_image_is_lcp''\]\s*=\s*false;') 'The editable Home cinematic-hero block must opt out after the Front Desk has claimed LCP.'
Assert-True ($functions -match 'function lunara_render_cinematic_hero_slide\( \$data, \$index = 0, \$first_image_is_lcp = true \)') 'Cinematic hero slides must retain a backward-compatible LCP context argument.'
Assert-True ($functions -match '\$is_priority_image\s*=\s*\$is_first\s*&&\s*\(bool\) \$first_image_is_lcp') 'Only the first slide in a true front-door context may receive high priority.'
Assert-True ($functions -match 'loading="lazy" decoding="async" fetchpriority="low"') 'Non-LCP cinematic hero images must use native lazy loading at low priority.'
Assert-True (([regex]::Matches($functions, "array_key_exists\(\s*'first_image_is_lcp'")).Count -eq 2) 'Both static and carousel hero renderers must honor the LCP context flag.'
Assert-True ($functions -match 'lunara_render_cinematic_hero_slide\( \$slide_data, \$slide_index, \$first_image_is_lcp \)') 'The carousel must pass its LCP context into every slide renderer.'
Assert-True ($style -match 'Version:\s*3\.2\.10') 'Theme version must be 3.2.10 for Journal typography and alignment.'

Write-Host 'Homepage LCP priority hygiene contract passed.'
