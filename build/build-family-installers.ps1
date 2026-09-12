<#
    Packages the hand-maintained extension source under academy/, blog/, codex/ (plus
    the two shared extensions under plugins/) into installable Joomla zips.

    This does NOT generate anything from a stock Joomla core anymore - it only zips up
    whatever already exists on disk. The academy/, blog/, and codex/ folders are the
    real, hand-maintained source; edit the plugin/module/component code directly, then
    re-run this script to re-package it.
#>
param()

$ErrorActionPreference = 'Stop'
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = Split-Path $ScriptDir -Parent

function New-Zip([string]$SourceDir, [string]$DestZip) {
    if (Test-Path -LiteralPath $DestZip) { Remove-Item -LiteralPath $DestZip -Force }
    Compress-Archive -Path (Join-Path $SourceDir '*') -DestinationPath $DestZip -CompressionLevel Optimal
}

function Build-VideoFeaturePackage([string]$RepoRoot) {
    $source = Join-Path $RepoRoot 'plugins'
    $staging = Join-Path $RepoRoot 'build/video-feature'
    $distOut = Join-Path $RepoRoot 'dist'
    if (Test-Path -LiteralPath $staging) { Remove-Item -LiteralPath $staging -Recurse -Force }
    New-Item -ItemType Directory -Path $staging, $distOut -Force | Out-Null

    New-Zip "$source/editors-xtd/video" "$staging/plg_editors-xtd_video.zip"
    New-Zip "$source/content/video" "$staging/plg_content_video.zip"

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
    [IO.File]::WriteAllText("$staging/pkg_video_feature.xml", $manifest, [Text.UTF8Encoding]::new($false))
    Compress-Archive -LiteralPath "$staging/pkg_video_feature.xml", "$staging/plg_editors-xtd_video.zip", "$staging/plg_content_video.zip" -DestinationPath "$distOut/pkg_video_feature.zip" -CompressionLevel Optimal -Force
    Write-Output "Built $distOut/pkg_video_feature.zip"
}

function Build-GenesisProfilePackage([string]$RepoRoot) {
    $source = Join-Path $RepoRoot 'plugins/user/genesisprofile'
    $distOut = Join-Path $RepoRoot 'dist'
    New-Item -ItemType Directory -Path $distOut -Force | Out-Null
    $output = Join-Path $distOut 'plg_user_genesisprofile.zip'
    New-Zip $source $output
    Write-Output "Built $output"
}

Build-VideoFeaturePackage $RepoRoot
Build-GenesisProfilePackage $RepoRoot

foreach ($family in @('academy', 'blog', 'codex')) {
    $title = (Get-Culture).TextInfo.ToTitleCase($family)
    $upper = $family.ToUpperInvariant()
    $familyDir = Join-Path $RepoRoot $family
    $familyDist = Join-Path $familyDir 'dist'
    $familyBuild = Join-Path $familyDir 'build'
    New-Item -ItemType Directory -Path $familyDist, $familyBuild -Force | Out-Null

    Write-Output "=== Packaging $title ==="

    # --- Component ---
    $componentZip = Join-Path $familyDist "com_$family.zip"
    New-Zip (Join-Path $familyDir "com_$family") $componentZip

    # --- Content-group plugins (everything under plugins/ except mailqueue/branding/loader) ---
    $integrationsRoot = Join-Path $familyBuild 'plugins'
    if (Test-Path -LiteralPath $integrationsRoot) { Remove-Item -LiteralPath $integrationsRoot -Recurse -Force }
    New-Item -ItemType Directory -Path $integrationsRoot -Force | Out-Null

    $pluginFiles = [System.Collections.Generic.List[string]]::new()
    Get-ChildItem -LiteralPath (Join-Path $familyDir 'plugins') -Directory |
        Where-Object { $_.Name -notin @('mailqueue', 'branding', 'loader') } |
        Sort-Object Name |
        ForEach-Object {
            $el = $_.Name
            $zipName = "plg_${family}_${el}.zip"
            $zip = Join-Path $familyDist $zipName
            New-Zip $_.FullName $zip
            Copy-Item -LiteralPath $zip -Destination $integrationsRoot -Force
            $pluginFiles.Add("    <file type=`"plugin`" id=`"$el`" group=`"$family`">$zipName</file>")
        }

    # --- Mail queue task plugin ---
    $mailZipName = "plg_task_${family}_mailqueue.zip"
    $mailZip = Join-Path $familyDist $mailZipName
    New-Zip (Join-Path $familyDir 'plugins/mailqueue') $mailZip
    Copy-Item -LiteralPath $mailZip -Destination $integrationsRoot -Force
    $pluginFiles.Add("    <file type=`"plugin`" id=`"${family}_mailqueue`" group=`"task`">$mailZipName</file>")

    # --- Branding system plugin ---
    $brandZipName = "plg_system_${family}branding.zip"
    $brandZip = Join-Path $familyDist $brandZipName
    New-Zip (Join-Path $familyDir 'plugins/branding') $brandZip
    Copy-Item -LiteralPath $brandZip -Destination $integrationsRoot -Force
    $pluginFiles.Add("    <file type=`"plugin`" id=`"${family}branding`" group=`"system`">$brandZipName</file>")

    # --- Plugin-group loader system plugin ---
    $loaderZipName = "plg_system_${family}loader.zip"
    $loaderZip = Join-Path $familyDist $loaderZipName
    New-Zip (Join-Path $familyDir 'plugins/loader') $loaderZip
    Copy-Item -LiteralPath $loaderZip -Destination $integrationsRoot -Force
    $pluginFiles.Add("    <file type=`"plugin`" id=`"${family}loader`" group=`"system`">$loaderZipName</file>")

    $integrationsManifest = @"
<?xml version="1.0" encoding="UTF-8"?>
<extension type="package" method="upgrade">
  <name>pkg_${family}_integrations</name>
  <packagename>${family}_integrations</packagename>
  <version>3.0.0</version>
  <creationDate>2026-09</creationDate>
  <author>Local Joomla Development</author>
  <license>GNU General Public License version 2 or later</license>
  <description>$title isolated post integration plugins</description>
  <files>
$($pluginFiles -join "`r`n")
  </files>
</extension>
"@
    [IO.File]::WriteAllText((Join-Path $integrationsRoot "pkg_${family}_integrations.xml"), $integrationsManifest, [Text.UTF8Encoding]::new($false))

    $pluginsZip = Join-Path $familyDist 'plugins.zip'
    Compress-Archive -Path (Join-Path $integrationsRoot '*') -DestinationPath $pluginsZip -Force

    # --- Modules ---
    $modulesRoot = Join-Path $familyBuild 'modules'
    if (Test-Path -LiteralPath $modulesRoot) { Remove-Item -LiteralPath $modulesRoot -Recurse -Force }
    New-Item -ItemType Directory -Path $modulesRoot -Force | Out-Null

    $moduleFiles = [System.Collections.Generic.List[string]]::new()
    Get-ChildItem -LiteralPath (Join-Path $familyDir 'modules') -Directory | Sort-Object Name | ForEach-Object {
        $mode = $_.Name
        $module = "mod_${family}_${mode}"
        $zip = Join-Path $familyDist "$module.zip"
        New-Zip $_.FullName $zip
        Copy-Item -LiteralPath $zip -Destination $modulesRoot -Force
        $moduleFiles.Add("    <file type=`"module`" id=`"$module`">$module.zip</file>")
    }

    $modulesManifest = @"
<?xml version="1.0" encoding="UTF-8"?>
<extension type="package" method="upgrade">
  <name>PKG_${upper}_MODULES</name>
  <packagename>${family}_modules</packagename>
  <version>1.0.0</version>
  <files>
$($moduleFiles -join "`r`n")
  </files>
</extension>
"@
    [IO.File]::WriteAllText((Join-Path $modulesRoot "pkg_${family}_modules.xml"), $modulesManifest, [Text.UTF8Encoding]::new($false))

    $modulesZip = Join-Path $familyDist 'modules.zip'
    Compress-Archive -Path (Join-Path $modulesRoot '*') -DestinationPath $modulesZip -Force

    # --- Final package (pkg_<family>.zip) ---
    $packageRoot = Join-Path $familyBuild "pkg_$family"
    if (Test-Path -LiteralPath $packageRoot) {
        $resolved = [IO.Path]::GetFullPath($packageRoot)
        $expectedRoot = [IO.Path]::GetFullPath($familyBuild) + [IO.Path]::DirectorySeparatorChar
        if (-not $resolved.StartsWith($expectedRoot, [StringComparison]::OrdinalIgnoreCase)) {
            throw "Refusing to replace package directory outside build: $resolved"
        }
        Remove-Item -LiteralPath $resolved -Recurse -Force
    }
    New-Item -ItemType Directory -Path $packageRoot -Force | Out-Null

    Copy-Item -LiteralPath $componentZip -Destination $packageRoot -Force
    Copy-Item -LiteralPath $pluginsZip -Destination $packageRoot -Force
    Copy-Item -LiteralPath $modulesZip -Destination $packageRoot -Force

    $manifest = @"
<?xml version="1.0" encoding="UTF-8"?>
<extension type="package" method="upgrade">
  <name>PKG_${upper}</name>
  <packagename>$family</packagename>
  <version>1.0.0</version>
  <creationDate>2026-09</creationDate>
  <author>Local Joomla Development</author>
  <license>GNU General Public License version 2 or later</license>
  <description>PKG_${upper}_XML_DESCRIPTION</description>
  <scriptfile>script.php</scriptfile>
  <files>
    <file type="component" id="com_$family">com_$family.zip</file>
    <file type="package" id="pkg_${family}_integrations">plugins.zip</file>
    <file type="package" id="pkg_${family}_modules">modules.zip</file>
  </files>
  <languages>
    <language tag="en-GB">language/en-GB/pkg_$family.ini</language>
    <language tag="en-GB">language/en-GB/pkg_$family.sys.ini</language>
  </languages>
</extension>
"@
    [IO.File]::WriteAllText((Join-Path $packageRoot "pkg_$family.xml"), $manifest, [Text.UTF8Encoding]::new($false))

    $installer = @"
<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\Database\DatabaseInterface;

final class pkg_${family}InstallerScript
{
    public function postflight(string `$type, InstallerAdapter `$adapter): bool
    {
        `$db = Factory::getContainer()->get(DatabaseInterface::class);
        `$folders = [`$db->quote('$family'), `$db->quote('task')];
        `$elements = [`$db->quote('${family}_mailqueue'), `$db->quote('${family}branding'), `$db->quote('${family}loader')];
        `$query = `$db->getQuery(true)
            ->update(`$db->quoteName('#__extensions'))
            ->set(`$db->quoteName('enabled') . ' = 1')
            ->where(`$db->quoteName('type') . ' = ' . `$db->quote('plugin'))
            ->where('((' . `$db->quoteName('folder') . ' = ' . `$db->quote('$family') . ') OR ('
                . `$db->quoteName('folder') . ' IN (' . implode(',', [`$db->quote('task'), `$db->quote('system')]) . ') AND '
                . `$db->quoteName('element') . ' IN (' . implode(',', `$elements) . ')))');
        `$db->setQuery(`$query)->execute();
        return true;
    }
}
"@
    [IO.File]::WriteAllText((Join-Path $packageRoot 'script.php'), $installer, [Text.UTF8Encoding]::new($false))

    $languageRoot = Join-Path $packageRoot 'language\en-GB'
    New-Item -ItemType Directory -Path $languageRoot -Force | Out-Null
    $language = "PKG_${upper}=`"$title Posts`"`r`nPKG_${upper}_XML_DESCRIPTION=`"Installs the $title component, integration plugins, modules, mail queue task plugin, and plugin-group loader.`"`r`n"
    [IO.File]::WriteAllText((Join-Path $languageRoot "pkg_$family.ini"), $language, [Text.UTF8Encoding]::new($false))
    [IO.File]::WriteAllText((Join-Path $languageRoot "pkg_$family.sys.ini"), $language, [Text.UTF8Encoding]::new($false))

    $outputZip = Join-Path $familyDist "pkg_$family.zip"
    Compress-Archive -Path (Join-Path $packageRoot '*') -DestinationPath $outputZip -CompressionLevel Optimal -Force
    Write-Output "Built $outputZip"

    Write-Output "=== $title packaged ==="
}

Write-Output "=== All families packaged ==="
