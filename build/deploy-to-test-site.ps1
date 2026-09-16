param(
    [string]$DistRoot = (Split-Path $PSScriptRoot -Parent),
    [Parameter(Mandatory)][string]$SiteRoot
)

$ErrorActionPreference = 'Stop'
$dist = $DistRoot
$site = $SiteRoot

function Invoke-Robocopy([string]$src, [string]$dst, [string[]]$extraArgs = @()) {
    $resolvedDestination = [IO.Path]::GetFullPath($dst)
    $allowedRoot = [IO.Path]::GetFullPath($site).TrimEnd('\', '/') + [IO.Path]::DirectorySeparatorChar
    if (-not $resolvedDestination.StartsWith($allowedRoot, [StringComparison]::OrdinalIgnoreCase)) {
        throw "Refusing to mirror outside test site: $resolvedDestination"
    }
    if (-not (Test-Path -LiteralPath $src -PathType Container)) {
        throw "Deployment source missing: $src"
    }
    $args = @($src, $dst, '/MIR', '/NFL', '/NDL', '/NJH', '/NJS', '/NP') + $extraArgs
    & robocopy @args | Out-Null
    if ($LASTEXITCODE -ge 8) { throw "robocopy failed ($LASTEXITCODE): $src -> $dst" }
}

$families = @(
    @{n='academy'; t='Academy'},
    @{n='blog';    t='Blog'},
    @{n='codex';   t='Codex'}
)

foreach ($f in $families) {
    $name = $f.n
    $title = $f.t
    $fr = "$dist\$name"
    Write-Output "=== Deploying $title ==="

    # --- Component ---
    Invoke-Robocopy "$fr\com_$name\admin" "$site\administrator\components\com_$name"
    Copy-Item -LiteralPath "$fr\com_$name\script.php" -Destination "$site\administrator\components\com_$name\script.php" -Force
    Invoke-Robocopy "$fr\com_$name\site" "$site\components\com_$name"
    Invoke-Robocopy "$fr\com_$name\api" "$site\api\components\com_$name"
    Invoke-Robocopy "$fr\com_$name\media" "$site\media\com_$name"
    Copy-Item -Path "$fr\com_$name\language\en-GB\*" -Destination "$site\administrator\language\en-GB\" -Force
    Copy-Item -Path "$fr\com_$name\site-language\en-GB\*" -Destination "$site\language\en-GB\" -Force

    # --- Content-group plugins (everything under plugins/ except mailqueue/branding/loader) ---
    Get-ChildItem -LiteralPath "$fr\plugins" -Directory | Where-Object { $_.Name -notin @('mailqueue', 'branding', 'loader') } | ForEach-Object {
        $el = $_.Name
        $langDir = Join-Path $_.FullName 'language\en-GB'
        if (Test-Path -LiteralPath $langDir) {
            Copy-Item -Path "$langDir\*" -Destination "$site\administrator\language\en-GB\" -Force
        }
        Invoke-Robocopy $_.FullName "$site\plugins\$name\$el" @('/XD', 'language')
    }

    # --- Mail queue task plugin ---
    $mailSrc = "$fr\plugins\mailqueue"
    $mailLang = Join-Path $mailSrc 'language\en-GB'
    if (Test-Path -LiteralPath $mailLang) {
        Copy-Item -Path "$mailLang\*" -Destination "$site\administrator\language\en-GB\" -Force
    }
    Invoke-Robocopy $mailSrc "$site\plugins\task\${name}_mailqueue" @('/XD', 'language')

    # --- Branding system plugin ---
    $brandSrc = "$fr\plugins\branding"
    $brandLang = Join-Path $brandSrc 'language\en-GB'
    if (Test-Path -LiteralPath $brandLang) {
        Copy-Item -Path "$brandLang\*" -Destination "$site\administrator\language\en-GB\" -Force
    }
    Invoke-Robocopy $brandSrc "$site\plugins\system\${name}branding" @('/XD', 'language')

    # --- Plugin-group loader system plugin ---
    $loaderSrc = "$fr\plugins\loader"
    $loaderLang = Join-Path $loaderSrc 'language\en-GB'
    if (Test-Path -LiteralPath $loaderLang) {
        Copy-Item -Path "$loaderLang\*" -Destination "$site\administrator\language\en-GB\" -Force
    }
    Invoke-Robocopy $loaderSrc "$site\plugins\system\${name}loader" @('/XD', 'language')

    # --- Modules ---
    Get-ChildItem -LiteralPath "$fr\modules" -Directory | ForEach-Object {
        $mode = $_.Name
        $module = "mod_${name}_${mode}"
        $modLang = Join-Path $_.FullName 'language\en-GB'
        if (Test-Path -LiteralPath $modLang) {
            Copy-Item -Path "$modLang\*" -Destination "$site\language\en-GB\" -Force
        }
        Invoke-Robocopy $_.FullName "$site\modules\$module" @('/XD', 'language')
    }

    Write-Output "=== $title deployed ==="
}

# Shared plugins use the standard Joomla group/element directory layout.
foreach ($relativePath in @('content/video', 'editors-xtd/video', 'user/genesisprofile')) {
    Invoke-Robocopy "$dist/plugins/$relativePath" "$site/plugins/$relativePath"
}

Write-Output "=== All families and shared plugins deployed ==="
