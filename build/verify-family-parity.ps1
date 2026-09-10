$ErrorActionPreference = 'Stop'
$root = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
$families = @('academy', 'blog', 'codex')

foreach ($family in $families) {
    $upper = $family.ToUpperInvariant()
    $component = Join-Path $root "dist/$family/com_$family"
    $languageFiles = @(
        (Join-Path $component "language/en-GB/com_$family.ini"),
        (Join-Path $component "site-language/en-GB/com_$family.ini")
    )
    foreach ($file in $languageFiles) {
        if (!(Test-Path $file)) { throw "$family is missing $file" }
    }
    $defined = [System.Collections.Generic.HashSet[string]]::new([StringComparer]::OrdinalIgnoreCase)
    foreach ($file in $languageFiles) {
        foreach ($line in Get-Content $file) {
            if ($line -match '^\s*(COM_[A-Z0-9_]+)\s*=') { [void] $defined.Add($Matches[1]) }
        }
    }
    $used = [System.Collections.Generic.HashSet[string]]::new([StringComparer]::OrdinalIgnoreCase)
    Get-ChildItem $component -Recurse -File -Filter '*.php' | ForEach-Object {
        $content = [IO.File]::ReadAllText($_.FullName)
        $pattern = '[''\"](?<key>COM_FAMILY_[A-Z0-9_]+)[''\"]'.Replace('FAMILY', $upper)
        foreach ($match in [regex]::Matches($content, $pattern)) {
            $key = $match.Groups['key'].Value
            if (!$key.EndsWith('_')) { [void] $used.Add($key) }
        }
    }
    $missing = @($used | Where-Object { !$defined.Contains($_) } | Sort-Object)
    if ($missing.Count) { throw "$family has unresolved component language keys: $($missing -join ', ')" }
    Write-Output "${family}: $($used.Count) component language keys are defined."
}
