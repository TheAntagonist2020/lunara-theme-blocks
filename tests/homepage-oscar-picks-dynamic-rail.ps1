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
$style = Read-ThemeFile 'style.css'
$header = Read-ThemeFile 'header.php'
$shell = Read-ThemeFile 'assets/css/lunara-shell.css'

Assert-True ($functions -match "function\s+lunara_render_oscar_picks_carousel") 'Oscar Picks must keep its named renderer.'
Assert-True ($functions -match "get_theme_mod\(\s*'lunara_home_oscar_picks_autoplay_interval'\s*,\s*6500\s*\)") 'Oscar Picks must define a bounded autoplay interval.'
Assert-True ($functions -match 'data-lunara-carousel-autoplay="<\?php echo \$pick_count > 1 \? \(int\) \$args\[''autoplay''\] : 0; \?>"') 'Oscar Picks autoplay must disable itself when only one card exists.'
Assert-True ($functions -match 'data-lunara-carousel-track') 'Oscar Picks track must opt into the shared carousel controller.'
Assert-True ($functions -match 'class="lunara-oscar-picks-track"[^>]*tabindex="0"') 'Oscar Picks track must be keyboard-focusable.'
Assert-True ($functions -match 'data-lunara-carousel-prev') 'Oscar Picks must expose a previous control.'
Assert-True ($functions -match 'data-lunara-carousel-next') 'Oscar Picks must expose a next control.'
Assert-True ($functions -match 'data-lunara-carousel-dot') 'Oscar Picks must expose dot controls.'
Assert-True ($functions -match 'role="tablist"') 'Oscar Picks dots must expose a tablist role.'
Assert-True ($functions -match 'aria-selected') 'Oscar Picks dots must publish selected state.'

Assert-True ($frontend -match 'const dots = Array\.from') 'Shared carousel controller must discover dot controls.'
Assert-True ($frontend -match 'function syncDots') 'Shared carousel controller must sync dot state.'
Assert-True ($frontend -match 'function scrollToIndex') 'Shared carousel controller must support direct dot navigation.'
Assert-True ($frontend -match 'getBoundingClientRect\(\)') 'Shared carousel direct navigation must use element geometry, not fragile offsets.'
Assert-True ($frontend -notmatch 'left:\s*card\.offsetLeft') 'Shared carousel direct navigation must not rely on raw offsetLeft.'
Assert-True ($frontend -match "(?s)'ArrowLeft'\s*===\s*event\.key.*?step\(-1\)") 'Shared carousel controller must support ArrowLeft.'
Assert-True ($frontend -match "(?s)'ArrowRight'\s*===\s*event\.key.*?step\(1\)") 'Shared carousel controller must support ArrowRight.'
Assert-True ($frontend -match 'prefers-reduced-motion') 'Shared carousel controller must preserve reduced-motion behavior.'
Assert-True ($frontend -match 'track\.children\.length > 1') 'Shared carousel autoplay must require multiple slides.'

Assert-True ($style -match 'lunara-oscar-picks-controls') 'Theme stylesheet must style Oscar Picks controls.'
Assert-True ($style -match 'lunara-carousel-dot\.active') 'Theme stylesheet must style the active dot.'
Assert-True ($style -match 'lunara-carousel-control') 'Theme stylesheet must style previous/next controls.'

Assert-True ($shell -notmatch 'body\.home \.lunara-oscar-picks-track,\s*body\.home \.lunara-oscar-facts-track\s*\{\s*display:\s*grid !important;') 'Cacheable shell CSS must not force dynamic homepage rails back to static grid mode.'
Assert-True ($shell -notmatch 'body\.home \.lunara-oscar-picks-track,\s*body\.home \.lunara-oscar-facts-track\s*\{(?s).*?overflow-x:\s*visible !important') 'Cacheable mobile shell must not make Oscar Picks horizontally inert.'
Assert-True ($shell -match 'body\.home \.lunara-oscar-picks-track\s*\{(?s).*?overflow-x:\s*auto !important') 'Cacheable mobile shell must preserve Oscar Picks horizontal scrolling.'

Write-Host 'Homepage Oscar Picks dynamic rail contract passed.'
