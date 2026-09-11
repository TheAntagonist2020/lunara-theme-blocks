$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
& php (Join-Path $PSScriptRoot 'site-studio-home-oscars-runtime.php')
if ($LASTEXITCODE -ne 0) { throw 'Homepage Oscars adapter contracts failed.' }
& php (Join-Path $PSScriptRoot 'home-oscar-artwork-runtime.php')
if ($LASTEXITCODE -ne 0) { throw 'Homepage Oscars artwork contracts failed.' }
& php (Join-Path $PSScriptRoot 'site-studio-home-oscars-preview-runtime.php')
if ($LASTEXITCODE -ne 0) { throw 'Homepage Oscars preview contracts failed.' }
& node (Join-Path $PSScriptRoot 'site-studio-home-oscars-browser-runtime.js')
if ($LASTEXITCODE -ne 0) { throw 'Homepage Oscars browser contracts failed.' }
