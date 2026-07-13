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

$splideJsPath = Join-Path $themeRoot 'assets/vendor/splide/splide.min.js'
$splideCssPath = Join-Path $themeRoot 'assets/vendor/splide/splide-core.min.css'
$splideLicensePath = Join-Path $themeRoot 'assets/vendor/splide/LICENSE.txt'
$pilotJsPath = Join-Path $themeRoot 'assets/js/lunara-splide-pilot.js'

Assert-True (Test-Path $splideJsPath) 'Splide production JS must be vendored under assets/vendor/splide.'
Assert-True (Test-Path $splideCssPath) 'Splide core CSS must be vendored under assets/vendor/splide.'
Assert-True (Test-Path $splideLicensePath) 'Splide MIT license must be retained with vendored assets.'
Assert-True (Test-Path $pilotJsPath) 'Lunara-owned Splide pilot initializer must exist.'

$license = Get-Content -Raw $splideLicensePath
Assert-True ($license -match 'MIT License') 'Vendored Splide license must identify MIT License.'
Assert-True ($license -match 'Naotoshi Fujita') 'Vendored Splide license must retain copyright attribution.'

$pilotJs = Get-Content -Raw $pilotJsPath
Assert-True ($pilotJs -match 'data-lunara-splide-pilot') 'Pilot JS must target the Lunara Splide pilot marker.'
Assert-True ($pilotJs -match 'window\.Splide') 'Pilot JS must initialize through window.Splide.'
Assert-True ($pilotJs -match 'prefers-reduced-motion') 'Pilot JS must account for reduced-motion users.'
Assert-True ($pilotJs -match 'lunara-splide-ready') 'Pilot JS must mark successful initialization for QA.'
Assert-True ($pilotJs -match 'function syncHeight') 'Pilot JS must keep the Splide track height synced to the active slide.'
Assert-True ($pilotJs -match 'track\.style\.height') 'Pilot JS must set track height instead of leaving the carousel stretched to the tallest slide.'
Assert-True ($pilotJs -match 'resize') 'Pilot JS must resync active slide height after viewport changes.'

$frontend = Read-ThemeFile 'inc/frontend.php'
$functions = Read-ThemeFile 'functions.php'
$homeModules = Read-ThemeFile 'assets/css/lunara-home-modules.css'
Assert-True ($frontend -match 'function lunara_enqueue_home_splide_pilot_assets') 'Homepage Splide assets must be enqueued from a named frontend helper.'
Assert-True ($frontend -match 'is_front_page\(\)') 'Splide pilot enqueue must be scoped to the front page.'
Assert-True ($frontend -match 'assets/vendor/splide/splide\.min\.js') 'Frontend enqueue must reference vendored Splide JS.'
Assert-True ($frontend -match 'assets/vendor/splide/splide-core\.min\.css') 'Frontend enqueue must reference vendored Splide core CSS.'
Assert-True ($frontend -match 'assets/js/lunara-splide-pilot\.js') 'Frontend enqueue must reference the Lunara pilot initializer.'
Assert-True (($frontend + $functions) -match 'data-lunara-splide-pilot') 'Homepage Oscar Facts markup/CSS must opt into the Splide pilot marker.'
Assert-True ($functions -match '_lunara_fact_visual_treatment') 'Oscar Facts must support a saved visual treatment for exact-source image handling.'
Assert-True ($functions -match '_lunara_fact_visual_focus') 'Oscar Facts must support a saved visual focus value for per-image framing.'
Assert-True ($functions -match 'lunara_sanitize_oscar_fact_visual_focus') 'Oscar Facts visual focus must be sanitized against allowed values.'
Assert-True ($functions -match '--lunara-fact-image-position') 'Oscar Facts cards must expose a safe CSS custom property for image framing.'
Assert-True ($functions -notmatch '--lunara-fact-image-url') 'Archival Oscar Facts must not trigger an eager CSS background request.'
Assert-True ($homeModules.Contains('object-position: var(--lunara-fact-image-position')) 'Homepage Oscar Facts images must honor per-card visual focus.'
Assert-True ($homeModules -match 'has-archival-visual') 'Homepage Oscar Facts must expose an archival visual mode for non-cropping exact-source stills.'
Assert-True ($homeModules -match 'object-fit:\s*contain') 'Archival Oscar Facts visuals must preserve the full image instead of forcing a crop.'
Assert-True ($homeModules -notmatch 'background-image:\s*var\(--lunara-fact-image-url') 'Archival Oscar Facts styling must not bypass native image lazy loading.'
Assert-True ($homeModules -match 'has-archival-visual[\s\S]*?radial-gradient') 'Archival Oscar Facts must retain a lightweight visual backing plate.'

$reviewRendering = Read-ThemeFile 'inc/review-rendering.php'
Assert-True ($reviewRendering -notmatch 'splide') 'Reviews archive rendering must remain native during the homepage Splide pilot.'

Write-Host 'Homepage Splide pilot contract passed.'
