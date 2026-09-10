param([string]$SourceRoot = (Split-Path (Split-Path $PSScriptRoot -Parent) -Parent))

$ErrorActionPreference = 'Stop'
$source = Join-Path $SourceRoot 'dist/plugins'
$output = Join-Path $SourceRoot 'dist/build/video-feature'
$distOut = Join-Path $SourceRoot 'dist/dist'
if (Test-Path -LiteralPath $output) { Remove-Item -LiteralPath $output -Recurse -Force }
New-Item -ItemType Directory -Path $output, $distOut -Force | Out-Null

Compress-Archive -Path "$source/editors-xtd/video/*" -DestinationPath "$output/plg_editors-xtd_video.zip" -CompressionLevel Optimal
Compress-Archive -Path "$source/content/video/*" -DestinationPath "$output/plg_content_video.zip" -CompressionLevel Optimal

$manifest = @'
<?xml version="1.0" encoding="UTF-8"?>
<extension type="package" method="upgrade">
  <name>pkg_video_feature</name>
  <packagename>video_feature</packagename>
  <version>1.0.1</version>
  <creationDate>2026-09</creationDate>
  <author>Local Joomla Development</author>
  <license>GNU General Public License version 2 or later</license>
  <description>Native Joomla video editor button and content renderer.</description>
  <files>
    <file type="plugin" id="video" group="editors-xtd">plg_editors-xtd_video.zip</file>
    <file type="plugin" id="video" group="content">plg_content_video.zip</file>
  </files>
</extension>
'@
[IO.File]::WriteAllText("$output/pkg_video_feature.xml", $manifest, [Text.UTF8Encoding]::new($false))
Compress-Archive -LiteralPath "$output/pkg_video_feature.xml", "$output/plg_editors-xtd_video.zip", "$output/plg_content_video.zip" -DestinationPath "$distOut/pkg_video_feature.zip" -CompressionLevel Optimal -Force
