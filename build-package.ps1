# Builds a clean distribution zip of the Spark system.
# Usage:  powershell -ExecutionPolicy Bypass -File build-package.ps1 [-OutDir .\release] [-Version 1.0.0]
#
# Uses robocopy for fast staging (much faster than Get-ChildItem/Copy-Item on
# thousands of small files), then .NET ZipFile for compression.

param(
    [string]$OutDir = ".\release",
    [string]$Version = "1.0.0"
)

$ErrorActionPreference = "Stop"

$root  = Split-Path -Parent $MyInvocation.MyCommand.Path
$stage = Join-Path $env:TEMP "spark-build-$(Get-Random)"
$zip   = Join-Path $root (Join-Path $OutDir "spark-conference-$Version.zip")

New-Item -ItemType Directory -Force -Path $stage | Out-Null
New-Item -ItemType Directory -Force -Path (Join-Path $root $OutDir) | Out-Null

# --- robocopy with exclusions -----------------------------------------------
$xd = @(  # excluded directories (top level)
    'release', '.git', 'cgi-bin', '.well-known',
    '_audit',           # proofs and test suites - never ship these
    '_retired',         # removed files, incl. a 1.3 GB archive
    '.venv-infographic',# python venv for the infographic scripts, not the app
    'sus-apps-script'   # SUS form backend, an evaluation tool, not the product
)
# NOTE: install/ IS included - a fresh deployment needs the web installer.
# The README tells the operator to delete it afterwards, and .htaccess blocks it.
$xf = @(  # excluded files
    'config.php', 'error_log', 'htaccess.zip',
    'php.ini', 'php2222.inis',
    '.user.ini', '.user-bak.ini', '.htaccess-bak', 'uitmktco_gdiid25.sql',
    'composer.lock', 'build-package.ps1', 'install.lock', '.gitkeep',
    'sus-study.html', 'simulate-sus-45.js', 'sus-simulation-45.csv',
    'sus-simulation-45.json', 'sus-simulation-45.md',
    'expert-evaluation-3-simulated.md',
    'analyze_expert_usability.py', 'create_sus_infographic.py',
    'create_sus_infographic_ms.py', '.gitignore', '.venv-infographic',
    'index-2021.php', 'index-2023.php', 'ex_abstract_2023.php',
    'download-loa2023.php', 'send-loa-kt-2023.php', 'send-loa-pub-2023.php',
    'verify_ex_abstract.process_2023_2.php', 'send-loa-kt-bak.php',
    'member.process-bak2023.php', 'upload_project_kt.process-bak.php',
    'upload_project.process-old.php', 'verify_ex_abstract.process_2023.php',
    '*.bak', '*_bak*'
)

robocopy $root $stage /E /NFL /NDL /NJH /NJS /NP /XD @xd /XF @xf
if ($LASTEXITCODE -ge 8) { throw "robocopy failed with exit code $LASTEXITCODE" }

<#
    The document generators load their certificates and fonts by exact filename,
    e.g. generate-award.php reads ./dist/img/cert/SIJIL_PARTICIPANT.png and
    generate-cert.php loads ./font/century-gothic. Dropping either to save size
    breaks PDF output with no error at build time - so assert them here.
#>
$runtimeAssets = @(
    'dist\img\cert\SIJIL_PARTICIPANT.png',
    'dist\css\adminlte.min.css',
    'dist\js\adminlte.js'
)
$missingAssets = $runtimeAssets | Where-Object { !(Test-Path (Join-Path $stage $_)) }
if ($missingAssets) {
    throw ("Packaging failed: generator asset missing: " + ($missingAssets -join ', '))
}
if (!(Get-ChildItem (Join-Path $stage 'font') -Recurse -File -ErrorAction SilentlyContinue)) {
    throw "Packaging failed: font/ is empty - generate-cert.php cannot render"
}

# --- remove live user data & personal files from the stage ------------------
# (robocopy /XF works on names anywhere; these need path-based removal)
foreach ($d in @('upload', 'admin\upload', 'admin\download', 'download')) {
    $p = Join-Path $stage $d
    if (Test-Path $p) {
        # Clear runtime data but keep the .htaccess guards - they are the only
        # thing stopping an uploaded .php from executing.
        Get-ChildItem $p -Recurse -File |
            Where-Object { $_.Name -ne '.htaccess' -and $_.Name -ne '.gitkeep' } |
            Remove-Item -Force

        # Remove now-empty subfolders (e.g. upload\project2025), but NEVER a
        # folder that still holds a .htaccess - deleting those would ship a
        # package with no upload protection at all.
        Get-ChildItem $p -Recurse -Directory |
            Sort-Object { $_.FullName.Length } -Descending |
            ForEach-Object {
                $hasGuard = Get-ChildItem $_.FullName -Filter '.htaccess' -Force -ErrorAction SilentlyContinue
                $hasFiles = Get-ChildItem $_.FullName -Recurse -File -Force -ErrorAction SilentlyContinue
                if (-not $hasGuard -and -not $hasFiles) {
                    Remove-Item $_.FullName -Force -Recurse -ErrorAction SilentlyContinue
                }
            }
    } else {
        New-Item -ItemType Directory -Force -Path $p | Out-Null
    }
    New-Item -ItemType File -Force -Path (Join-Path $p '.gitkeep') | Out-Null
}
Get-ChildItem $stage -Recurse -File |
    Where-Object { $_.Name -match '_photostaff_|_linda' } |
    Remove-Item -Force

<#
    Evaluation and analysis artefacts are not part of the product. They were
    named individually before, which meant every new chart slipped into the
    package. Match by shape instead.
#>
$junkPatterns = @(
    '^analyze_expert_.*\.py$',
    '^create_.*infographic.*\.py$',
    '^create_sus_.*\.py$',
    '^expert-usability-.*',
    '^expert-evaluation-.*',
    '^sus-.*',
    '^simulate-sus-.*'
)
Get-ChildItem $stage -Recurse -File |
    Where-Object {
        $n = $_.Name
        ($junkPatterns | Where-Object { $n -match $_ }) -ne $null
    } |
    Remove-Item -Force

# --- sanity checks -----------------------------------------------------------
$critical = @('index.php', 'admin\index.php', 'install\index.php',
              'install\schema.sql', 'Dbcontroller.php', 'Mail.php',
              'config.sample.php', 'security.php', 'README.md')
foreach ($f in $critical) {
    if (!(Test-Path (Join-Path $stage $f))) { throw "Packaging failed: missing $f" }
}
foreach ($f in @('config.php', 'uitmktco_gdiid25.sql', 'install\install.lock')) {
    if (Test-Path (Join-Path $stage $f)) { throw "Packaging failed: $f must not be included!" }
}

# The upload guards are load-bearing: without them anything written into these
# directories executes. A previous version of this script preserved .htaccess
# and then deleted the folder containing it.
$guards = @(
    'upload\.htaccess', 'upload\payment\.htaccess', 'upload\abstract\.htaccess',
    'upload\review\.htaccess', 'upload\turnitin\.htaccess',
    'upload\certificate\.htaccess', 'upload\project\.htaccess',
    'upload\receipt\.htaccess', 'admin\upload\.htaccess', 'download\.htaccess'
)
$missingGuards = $guards | Where-Object { !(Test-Path (Join-Path $stage $_)) }
if ($missingGuards) {
    throw ("Packaging failed: upload protection missing for: " + ($missingGuards -join ', '))
}
# No user data may survive into the package.
$leaked = Get-ChildItem (Join-Path $stage 'upload'), (Join-Path $stage 'download'),
                            (Join-Path $stage 'admin\upload') -Recurse -File -Force -ErrorAction SilentlyContinue |
          Where-Object { $_.Name -notin @('.htaccess', '.gitkeep') }
if ($leaked) {
    throw ("Packaging failed: " + @($leaked).Count + " participant file(s) would ship, e.g. " + $leaked[0].Name)
}
$memberSeed = Select-String -Path (Join-Path $stage 'install\schema.sql') -Pattern '^INSERT INTO' -List
if ($memberSeed -and $memberSeed.Line -notmatch 'members_role') {
    throw "Packaging failed: schema.sql contains non-seed INSERT data!"
}

# --- zip via .NET (faster & more reliable than Compress-Archive) -------------
if (Test-Path $zip) { Remove-Item $zip -Force }
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($stage, $zip, [System.IO.Compression.CompressionLevel]::Optimal, $false)
Remove-Item -Recurse -Force $stage

Write-Host ""
Write-Host "Package created: $zip" -ForegroundColor Green
Write-Host ("Size: {0:N1} MB" -f ((Get-Item $zip).Length / 1MB))
