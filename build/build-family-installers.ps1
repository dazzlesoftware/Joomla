param(
    [switch]$SkipBuild
)

$ErrorActionPreference = 'Stop'
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$SourceRoot = Split-Path (Split-Path $ScriptDir -Parent) -Parent
$Dist = Join-Path $SourceRoot 'dist'

if (-not $SkipBuild) {
    & (Join-Path $ScriptDir 'build-components.ps1')
    & (Join-Path $ScriptDir 'build-family-modules.ps1')
    & (Join-Path $ScriptDir 'build-integration-plugins.ps1')
    & (Join-Path $ScriptDir 'build-mail-task-plugins.ps1')
}

foreach ($family in @('academy', 'blog', 'codex')) {
    $title = (Get-Culture).TextInfo.ToTitleCase($family)
    $upper = $family.ToUpperInvariant()
    $familyDist = Join-Path $Dist $family
    $familyBuild = Join-Path $familyDist 'build'
    $familyOutput = Join-Path $familyDist 'dist'
    $pluginsDist = Join-Path $familyDist 'plugins'
    New-Item -ItemType Directory -Path $familyBuild, $familyOutput, $pluginsDist -Force | Out-Null
    $packageRoot = Join-Path $familyBuild "pkg_$family"

    if (Test-Path -LiteralPath $packageRoot) {
        $resolved = [IO.Path]::GetFullPath($packageRoot)
        $expectedRoot = [IO.Path]::GetFullPath($Dist) + [IO.Path]::DirectorySeparatorChar
        if (-not $resolved.StartsWith($expectedRoot, [StringComparison]::OrdinalIgnoreCase)) {
            throw "Refusing to replace package directory outside dist: $resolved"
        }
        Remove-Item -LiteralPath $resolved -Recurse -Force
    }
    New-Item -ItemType Directory -Path $packageRoot -Force | Out-Null

    $componentZip = Join-Path $familyOutput "com_$family.zip"
    & tar.exe -a -cf $componentZip -C (Join-Path $familyDist "com_$family") *
    if ($LASTEXITCODE -ne 0) { throw "Could not create $componentZip" }
    Copy-Item -LiteralPath $componentZip -Destination $packageRoot -Force

    # The mailqueue (task) and branding (system) plugins are bundled INTO the
    # integrations/plugins sub-package (plugins.zip), not shipped as separate
    # top-level package members.
    $integrationsRoot = Join-Path $familyBuild 'plugins'
    Copy-Item -LiteralPath (Join-Path $familyOutput "plg_task_${family}_mailqueue.zip") -Destination $integrationsRoot -Force

    $brandingRoot = Join-Path $pluginsDist 'branding'
    if (Test-Path -LiteralPath $brandingRoot) { Remove-Item -LiteralPath $brandingRoot -Recurse -Force }
    New-Item -ItemType Directory -Path $brandingRoot -Force | Out-Null
    Copy-Item -Path (Join-Path $SourceRoot 'extensions\post-branding\*') -Destination $brandingRoot -Recurse -Force
    Get-ChildItem -Path $brandingRoot -Recurse -File | ForEach-Object {
        $content = [IO.File]::ReadAllText($_.FullName).Replace('FamilyName', $title).Replace('FamilyTitle', $title).Replace('familyname', $family)
        [IO.File]::WriteAllText($_.FullName, $content, [Text.UTF8Encoding]::new($false))
    }
    Rename-Item -LiteralPath (Join-Path $brandingRoot 'familynamebranding.xml') -NewName "${family}branding.xml"
    $brandingZip = Join-Path $familyOutput "plg_system_${family}branding.zip"
    & tar.exe -a -cf $brandingZip -C $brandingRoot *
    if ($LASTEXITCODE -ne 0) { throw "Could not create $brandingZip" }
    Copy-Item -LiteralPath $brandingZip -Destination $integrationsRoot -Force

    # Each family gets its own copy of the plugin-group loader (imports the family's
    # own custom plugin group on onAfterInitialise) instead of sharing one loader
    # across all three products — so a customer who only buys Academy gets a plugin
    # that only ever mentions Academy.
    $loaderRoot = Join-Path $pluginsDist 'loader'
    if (Test-Path -LiteralPath $loaderRoot) { Remove-Item -LiteralPath $loaderRoot -Recurse -Force }
    New-Item -ItemType Directory -Path $loaderRoot -Force | Out-Null
    Copy-Item -Path (Join-Path $SourceRoot 'extensions\post-family-loader\*') -Destination $loaderRoot -Recurse -Force
    Get-ChildItem -Path $loaderRoot -Recurse -File | ForEach-Object {
        $content = [IO.File]::ReadAllText($_.FullName).Replace('FamilyName', $title).Replace('FamilyTitle', $title).Replace('familyname', $family)
        [IO.File]::WriteAllText($_.FullName, $content, [Text.UTF8Encoding]::new($false))
    }
    Rename-Item -LiteralPath (Join-Path $loaderRoot 'familynameloader.xml') -NewName "${family}loader.xml"
    $loaderZip = Join-Path $familyOutput "plg_system_${family}loader.zip"
    & tar.exe -a -cf $loaderZip -C $loaderRoot *
    if ($LASTEXITCODE -ne 0) { throw "Could not create $loaderZip" }
    Copy-Item -LiteralPath $loaderZip -Destination $integrationsRoot -Force

    # Now that mailqueue + branding + loader exist, fold them into the integrations
    # manifest and assemble the final plugins.zip / modules.zip package files.
    $integrationsManifestPath = Join-Path $integrationsRoot "pkg_${family}_integrations.xml"
    [xml]$integrationsManifest = Get-Content -LiteralPath $integrationsManifestPath -Raw
    $filesNode = $integrationsManifest.extension.files
    $mailqueueFile = $integrationsManifest.CreateElement('file')
    $mailqueueFile.SetAttribute('type', 'plugin'); $mailqueueFile.SetAttribute('id', "${family}_mailqueue"); $mailqueueFile.SetAttribute('group', 'task')
    $mailqueueFile.InnerText = "plg_task_${family}_mailqueue.zip"
    [void]$filesNode.AppendChild($mailqueueFile)
    $brandingFile = $integrationsManifest.CreateElement('file')
    $brandingFile.SetAttribute('type', 'plugin'); $brandingFile.SetAttribute('id', "${family}branding"); $brandingFile.SetAttribute('group', 'system')
    $brandingFile.InnerText = "plg_system_${family}branding.zip"
    [void]$filesNode.AppendChild($brandingFile)
    $loaderFile = $integrationsManifest.CreateElement('file')
    $loaderFile.SetAttribute('type', 'plugin'); $loaderFile.SetAttribute('id', "${family}loader"); $loaderFile.SetAttribute('group', 'system')
    $loaderFile.InnerText = "plg_system_${family}loader.zip"
    [void]$filesNode.AppendChild($loaderFile)
    $integrationsManifest.Save($integrationsManifestPath)

    $pluginsZip = Join-Path $familyOutput 'plugins.zip'
    Compress-Archive -Path (Join-Path $integrationsRoot '*') -DestinationPath $pluginsZip -Force
    Copy-Item -LiteralPath $pluginsZip -Destination $packageRoot -Force
    Copy-Item -LiteralPath (Join-Path $familyOutput 'modules.zip') -Destination $packageRoot -Force

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

    $outputZip = Join-Path $familyOutput "pkg_$family.zip"
    Compress-Archive -Path (Join-Path $packageRoot '*') -DestinationPath $outputZip -CompressionLevel Optimal -Force
    Write-Output "Built $outputZip"
}
