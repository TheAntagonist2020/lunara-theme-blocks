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
    Assert-True (Test-Path -LiteralPath $path) "Missing expected file: $RelativePath"
    return Get-Content -LiteralPath $path -Raw
}

$style = Read-ThemeFile 'style.css'
$frontend = Read-ThemeFile 'inc/frontend.php'
$runtime = Read-ThemeFile 'assets/js/lunara-public-runtime.js'
$imageRuntimeMatch = [regex]::Match($runtime, '(?s)\A.*?\}\(\)\);')

Assert-True ($style -match 'Version:\s*3\.2\.52') 'Jetpack Boost image-runtime gate must report Theme 3.2.52.'
Assert-True ($frontend -match "pre_option_jetpack_boost_status_image-cdn-liar") 'Theme must disable only Boost Auto-Resize Lazy Images.'
Assert-True ($frontend -match "function\s+lunara_disable_jetpack_boost_auto_resize") 'Boost compatibility callback is missing.'
Assert-True ($frontend -match "return\s+'0';") 'Boost Auto-Resize compatibility callback must short-circuit to disabled.'
Assert-True ($frontend -match 'Image CDN and its quality controls remain active') 'Compatibility note must preserve the parent Image CDN contract.'

Assert-True $imageRuntimeMatch.Success 'The public image runtime could not be isolated for inspection.'
$imageRuntime = $imageRuntimeMatch.Value
Assert-True ($imageRuntime -notmatch 'shouldHydrateNow|getBoundingClientRect') 'Initial image hydration must not synchronously measure card geometry.'
Assert-True ($imageRuntime -notmatch 'Element\.prototype\.setAttribute|Object\.defineProperty|lunaraSrcsetGuardInstalled') 'The retired global srcset prototype monkey-patch must not return.'
Assert-True ($imageRuntime -match 'new\s+IntersectionObserver') 'Lazy card hydration must use IntersectionObserver.'
Assert-True ($imageRuntime -match "rootMargin:\s*'640px 0px'") 'Lazy card hydration must begin ahead of the viewport.'
Assert-True ($imageRuntime -match 'scanImages\(node\)') 'Dynamic content must scan only newly added subtrees.'
Assert-True ($imageRuntime -notmatch 'needsHydration|hydrateCards\(\)') 'DOM mutations must not trigger a full-document image rescan.'
Assert-True ($imageRuntime -match "lunaraHydratorState\s*=\s*'observed'") 'Observed images must be marked to prevent duplicate registration.'
Assert-True ($imageRuntime -match "addEventListener\('error',\s*markLoaded") 'Failed images must still escape invisible loading chambers.'
Assert-True ($imageRuntime -match 'setTimeout\(markLoaded,\s*1800\)') 'The bounded image visibility fallback must remain intact.'

Write-Host 'Jetpack Boost image-runtime gate contract passed.'
