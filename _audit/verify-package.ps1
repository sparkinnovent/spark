# Verifies a built distribution zip.
#
# The build script asserts these while staging; this checks the artefact that
# actually came out, because a wrong exclusion list or a late copy can undo a
# correct staging pass.
#
#   powershell -File _audit\verify-package.ps1 [-Path release\spark-conference-1.0.0.zip]

param(
    [string]$Path = ""
)

$ErrorActionPreference = "Stop"

$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)

if ($Path -eq "") {
    $found = Get-ChildItem (Join-Path $root "release") -Filter "spark-conference-*.zip" -ErrorAction SilentlyContinue |
             Sort-Object Name
    if (-not $found) { Write-Host "No package in release/. Build one first."; exit 2 }
    $Path = $found[-1].FullName
}
if (-not (Test-Path $Path)) { Write-Host "Not found: $Path"; exit 2 }

$pass = 0; $fail = 0; $failures = @()

function Check([string]$label, [bool]$ok, [string]$detail = "") {
    $script:pass++
    if ($ok) { Write-Host ("  ok    " + $label) }
    else {
        $script:fail++
        $script:failures += $label
        $suffix = if ($detail -ne "") { " - " + $detail } else { "" }
        Write-Host ("  FAIL  " + $label + $suffix)
    }
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::OpenRead($Path)
try {
    $names = @($zip.Entries | ForEach-Object { $_.FullName })

    Write-Host ("Package: " + (Split-Path $Path -Leaf))
    Write-Host ("Size:    {0:N1} MB" -f ((Get-Item $Path).Length / 1MB))
    Write-Host ("{0} entries." -f $names.Count)
    Write-Host ""

    Write-Host "--- must be present ---"
    <#
        ZipFile writes the separators of the OS that built it - on Windows these
        entries come back with backslashes, so every path here must use them too.
        Verified against the real artefact: readme.md is packaged in lowercase
        because Windows created it that way, while the working-tree file is
        README.md.
    #>
    $required = @(
        'index.php', 'secure.php', 'config.sample.php',
        'security.php', 'download.php', 'Dbcontroller.php', 'Mail.php',
        'Notify.php', 'Util.php',
        'admin\index.php', 'admin\login.php', 'admin\dashboard.php',
        'install\index.php', 'install\schema.sql',
        'install\migrate-2026-01-security.sql',
        'vendor\autoload.php'
    )
    $missing = @($required | Where-Object { $names -notcontains $_ })
    Check "every required file is packaged" ($missing.Count -eq 0) ($missing -join ', ')

    Write-Host ""
    Write-Host "--- must NOT be present ---"
    $forbidden = @(
        'config.php', 'error_log', 'htaccess.zip', '.user.ini',
        'uitmktco_gdiid25.sql', 'install\install.lock',
        'member.process-bak2023.php', 'verify_ex_abstract.process_2023.php'
    )
    $leaked = @($forbidden | Where-Object { $names -contains $_ })
    Check "no secrets, logs or stale files" ($leaked.Count -eq 0) ($leaked -join ', ')

    foreach ($dir in @('_audit/', '_retired/', '.git/', '.venv-infographic/', 'sus-apps-script/')) {
        $n = @($names | Where-Object { $_.StartsWith($dir) }).Count
        Check ("$dir is excluded") ($n -eq 0) ("$n entries")
    }

    # Analysis and evaluation files share no common folder, so match on shape.
    $junk = @($names | Where-Object {
        $n = Split-Path $_ -Leaf
        $n -match '^analyze_expert_.*\.py$' -or
        $n -match '^create_.*infographic.*\.py$' -or
        $n -match '^create_sus_.*\.py$' -or
        $n -match '^expert-(usability|evaluation)-' -or
        $n -match '^sus-' -or
        $n -match '^simulate-sus-'
    })
    Check "analysis and evaluation artefacts are excluded" ($junk.Count -eq 0) (($junk | Select-Object -First 3) -join ', ')

    Write-Host ""
    Write-Host "--- upload protection ---"
    $guards = @(
        'upload\.htaccess', 'upload\payment\.htaccess', 'upload\abstract\.htaccess',
        'upload\review\.htaccess', 'upload\turnitin\.htaccess',
        'upload\certificate\.htaccess', 'upload\project\.htaccess',
        'upload\receipt\.htaccess', 'admin\upload\.htaccess', 'download\.htaccess'
    )
    $missingGuards = @($guards | Where-Object { $names -notcontains $_ })
    Check "all ten upload guards are packaged" ($missingGuards.Count -eq 0) ($missingGuards -join ', ')

    # A guard that does not disable the engine is worse than none.
    $weak = @()
    foreach ($g in $guards) {
        if ($names -notcontains $g) { continue }
        $entry = $zip.GetEntry($g)
        $reader = New-Object System.IO.StreamReader($entry.Open())
        $body = $reader.ReadToEnd()
        $reader.Close()
        if ($body -notmatch 'engine off' -and $body -notmatch 'RemoveHandler' -and $body -notmatch 'SetHandler') {
            $weak += $g
        }
    }
    Check "every packaged guard disables the PHP engine" ($weak.Count -eq 0) ($weak -join ', ')

    Write-Host ""
    Write-Host "--- no participant data ---"
    $dataDirs = @('upload\', 'admin\upload\', 'download\')
    $dataFiles = @($names | Where-Object {
        $n = $_
        if ($n.EndsWith('\')) { return $false }
        $inDir = $false
        foreach ($d in $dataDirs) { if ($n.StartsWith($d)) { $inDir = $true } }
        $inDir -and -not $n.EndsWith('.htaccess') -and -not $n.EndsWith('.gitkeep')
    })
    Check "no participant files ship" ($dataFiles.Count -eq 0) (($dataFiles | Select-Object -First 3) -join ', ')

    Write-Host ""
    Write-Host "--- README is the current one ---"
    <#
        Windows stores the file as readme.md and ZipFile keeps that casing.
        Look it up case-insensitively so the check holds whichever way the
        working tree names it, then assert the content.
    #>
    $readmeEntry = @($zip.Entries | Where-Object { $_.FullName -match '^(?i)readme\.md$' }) | Select-Object -First 1
    if ($null -eq $readmeEntry) {
        Check "README.md is present" $false "missing"
    } else {
        $reader = New-Object System.IO.StreamReader($readmeEntry.Open())
        $readme = $reader.ReadToEnd()
        $reader.Close()
        $local = [System.IO.File]::ReadAllText((Join-Path $root "README.md"), [System.Text.Encoding]::UTF8)
        Check "README.md is present and non-trivial" ($readme.Length -gt 5000) ("$($readme.Length) bytes")
        Check "README.md matches the working tree" ($readme.TrimEnd() -eq $local.TrimEnd()) ("packaged $($readme.Length) vs local $($local.Length) bytes")
        Check "README names the project Spark" ($readme.StartsWith("# Spark"))
        Check "README documents the PHP floor" ($readme -match '\|\s*PHP\s*\|\s*8\.\d\s*\|')
        Check "README ships at the root, not buried" ($readmeEntry.FullName -notmatch '\\')
    }

    Write-Host ""
    Write-Host "--- generator assets ---"
    $assets = @(
        'dist\img\cert\SIJIL_PARTICIPANT.png',
        'dist\css\adminlte.min.css',
        'dist\js\adminlte.js'
    )
    $missingAssets = @($assets | Where-Object { $names -notcontains $_ })
    Check "generator assets are packaged" ($missingAssets.Count -eq 0) ($missingAssets -join ', ')
    Check "fonts for certificate rendering are packaged" (@($names | Where-Object { $_.StartsWith('font\') }).Count -gt 0)
}
finally {
    $zip.Dispose()
}

Write-Host ""
Write-Host ("=" * 66)
Write-Host ("$pass passed, $fail failed")
if ($fail -gt 0) {
    Write-Host ""
    Write-Host "FAILURES:"
    $failures | ForEach-Object { Write-Host ("  - " + $_) }
}
Write-Host ("=" * 66)

exit $(if ($fail -eq 0) { 0 } else { 1 })
