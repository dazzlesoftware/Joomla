param([string]$SourceRoot = (Split-Path (Split-Path $PSScriptRoot -Parent) -Parent))

$ErrorActionPreference = 'Stop'
$source = Join-Path $SourceRoot 'dist/plugins/user/genesisprofile'
$distOut = Join-Path $SourceRoot 'dist/dist'
$output = Join-Path $distOut 'plg_user_genesisprofile.zip'
New-Item -ItemType Directory -Path $distOut -Force | Out-Null

Compress-Archive -Path "$source/*" -DestinationPath $output -CompressionLevel Optimal -Force
Write-Output "Built $output"
