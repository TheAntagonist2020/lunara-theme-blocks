$ErrorActionPreference = 'Stop'
$carouselRoot = Split-Path -Parent $PSScriptRoot
& php (Join-Path $PSScriptRoot 'home-carousel-settings-runtime.php')
if ($LASTEXITCODE -ne 0) { throw 'Homepage carousel settings contracts failed.' }
& node (Join-Path $PSScriptRoot 'home-carousel-editor-runtime.js')
if ($LASTEXITCODE -ne 0) { throw 'Homepage carousel editor contracts failed.' }
& php (Join-Path $PSScriptRoot 'home-carousels-runtime.php')
if ($LASTEXITCODE -ne 0) { throw 'Homepage carousel delivery failed.' }
& node --check (Join-Path $carouselRoot 'assets/js/lunara-home-carousels.js')
if ($LASTEXITCODE -ne 0) { throw 'Homepage carousel JavaScript syntax failed.' }
& node (Join-Path $PSScriptRoot 'home-carousels-browser-runtime.js')
if ($LASTEXITCODE -ne 0) { throw 'Homepage carousel browser contracts failed.' }
