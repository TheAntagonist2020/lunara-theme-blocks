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

$functions = Read-ThemeFile 'functions.php'
$frontend = Read-ThemeFile 'inc/frontend.php'
$homeModules = Read-ThemeFile 'assets/css/lunara-home-modules.css'
$pilotJs = Read-ThemeFile 'assets/js/lunara-splide-pilot.js'

Assert-True ($functions -match 'lunara-oscar-facts-console') 'Oscar Facts markup must include a public ledger console.'
Assert-True ($functions -match 'lunara-oscar-facts-current') 'Oscar Facts markup must expose a current-slide counter target.'
Assert-True ($functions -match 'lunara-oscar-facts-total') 'Oscar Facts markup must expose a total-slide counter target.'
Assert-True ($functions -match 'lunara-oscar-facts-progress-bar') 'Oscar Facts markup must expose a progress-bar target.'
Assert-True ($functions -match 'data-lunara-facts-total') 'Oscar Facts carousel must publish the total slide count for the signature console.'
Assert-True ($functions -match 'data-slide-index') 'Oscar Fact cards must publish a slide index for synchronized state.'

Assert-True ($pilotJs -match 'function syncSignatureConsole') 'Splide pilot must synchronize the public ledger console.'
Assert-True ($pilotJs -match 'lunara-oscar-facts-current') 'Splide pilot must update the current-slide counter.'
Assert-True ($pilotJs -match 'lunara-oscar-facts-total') 'Splide pilot must update the total-slide counter.'
Assert-True ($pilotJs -match 'lunara-oscar-facts-progress-bar') 'Splide pilot must update the progress bar.'
Assert-True ($pilotJs -match '--lunara-oscar-facts-progress') 'Splide pilot must expose bounded progress as a CSS custom property.'
Assert-True ($pilotJs -match 'aria-current') 'Splide pilot must update aria-current on the active Oscar Fact slide.'
Assert-True ($pilotJs -match 'is-lunara-active') 'Splide pilot must add a Lunara active-state class for visual polish.'
Assert-True ($pilotJs -match 'is-lunara-prev') 'Splide pilot must add a Lunara previous-state class for visual polish.'
Assert-True ($pilotJs -match 'is-lunara-next') 'Splide pilot must add a Lunara next-state class for visual polish.'
Assert-True ($pilotJs -match 'prefers-reduced-motion') 'Splide pilot must preserve reduced-motion behavior.'

Assert-True ($homeModules -match 'lunara-oscar-facts-console') 'Cacheable homepage CSS must style the Oscar Facts ledger console.'
Assert-True ($homeModules -match 'lunara-oscar-facts-counter') 'Cacheable homepage CSS must style the Oscar Facts slide counter.'
Assert-True ($homeModules -match 'lunara-oscar-facts-progress') 'Cacheable homepage CSS must style the Oscar Facts progress rail.'
Assert-True ($homeModules -match '--lunara-oscar-facts-progress') 'Cacheable homepage CSS must consume the Oscar Facts progress custom property.'
Assert-True ($homeModules -match 'is-lunara-active') 'Cacheable homepage CSS must style the Lunara active-state slide.'
Assert-True ($homeModules -match 'is-lunara-prev|is-lunara-next') 'Cacheable homepage CSS must account for adjacent slide state.'
Assert-True ($homeModules -match '@media \(max-width: 780px\)') 'Signature lane CSS must include mobile-safe controls.'
Assert-True ($homeModules -match '@media \(prefers-reduced-motion: reduce\)') 'Signature lane CSS must include reduced-motion fallback.'

Assert-True ($functions -match '_lunara_fact_visual_verified') 'Oscar Facts must still require verified public visuals.'
Assert-True ($functions -match 'lunara_oscar_fact_visual_hold_ids') 'Oscar Facts must still exclude held visuals from public image chambers.'
Assert-True ($functions -match 'data-lunara-splide-pilot') 'Oscar Facts must remain on the existing theme-owned Splide pilot.'

Write-Host 'Homepage Oscar Facts signature lane contract passed.'
