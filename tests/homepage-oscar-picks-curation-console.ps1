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

$controlDesk = Read-ThemeFile 'inc/control-desk.php'
$functions = Read-ThemeFile 'functions.php'
$adminCss = Read-ThemeFile 'assets/css/lunara-control-desk.css'

Assert-True ($controlDesk -match 'lunara_home_oscar_picks_manual_order') 'Homepage Studio must store a manual Oscar Picks order theme mod.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_parse_oscar_pick_order') 'Control Desk must parse and sanitize Oscar Pick order IDs.'
Assert-True ($controlDesk.Contains('get_post_type( $post_id ) !== ''lunara_oscar_pick''')) 'Order sanitizer must reject non-Oscar Pick post IDs.'
Assert-True ($controlDesk.Contains('get_post_status( $post_id ) !== ''publish''')) 'Order sanitizer must reject non-published Oscar Pick IDs.'
Assert-True ($controlDesk -match 'lunara_control_desk_render_homepage_oscar_picks_curation') 'Homepage Studio must render an Oscar Picks curation console.'
Assert-True ($controlDesk -match 'data-lunara-oscar-picks-curation') 'Curation console must expose a stable admin hook.'
Assert-True ($controlDesk -match 'name="lunara_home_oscar_picks_manual_order"') 'Curation console must submit the ordered ID field.'
Assert-True ($controlDesk -match 'name="lunara_home_oscar_picks_order_source"') 'Curation console must submit whether the displayed order is manual or fallback.'
Assert-True ($controlDesk -match 'lunara_home_oscar_picks_add') 'Curation console must provide an add-on-save path for held/recent picks.'
Assert-True ($controlDesk -match 'remove_theme_mod\(\s*''lunara_home_oscar_picks_manual_order''\s*\)') 'Empty/reset order must clear the theme mod.'
Assert-True ($controlDesk -match 'oscar_pick_order_is_default') 'Homepage Studio must avoid freezing the smart default order during unrelated saves.'

Assert-True ($functions -match 'lunara_home_oscar_picks_manual_order') 'Public Oscar Picks renderer must read the manual order theme mod.'
Assert-True ($functions -match "'ordered_ids'\s*=>") 'Oscar Picks query helper must accept ordered IDs.'
Assert-True ($functions -match "'orderby'\s*=>\s*'post__in'") 'Manual order must use post__in ordering.'
Assert-True ($functions -match "meta_value_num'\s*=>\s*'DESC'") 'Smart ceremony/date fallback must remain available.'

Assert-True ($adminCss -match 'lunara-control-desk-oscar-picks-curation') 'Admin CSS must style the Oscar Picks curation surface.'

Write-Host 'Homepage Oscar Picks curation console contract passed.'
