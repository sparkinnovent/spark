@echo off
REM Run every verification suite for this repo.
REM   _audit\run-all.bat
setlocal
cd /d "%~dp0.."

set FAILED=0

echo ================================================================
echo  1/7  PHP syntax check (all live files)
echo ================================================================
for %%f in (*.php) do (
    php -l "%%f" >nul 2>&1 || ( echo   SYNTAX FAIL: %%f & set FAILED=1 )
)
for %%f in (admin\*.php) do (
    php -l "%%f" >nul 2>&1 || ( echo   SYNTAX FAIL: admin\%%f & set FAILED=1 )
)
for %%f in (install\*.php) do (
    php -l "%%f" >nul 2>&1 || ( echo   SYNTAX FAIL: install\%%f & set FAILED=1 )
)
if "%FAILED%"=="0" echo   all files parse clean
echo.

echo ================================================================
echo  2/7  Security helpers (unit)
echo ================================================================
php "_audit\verify-fixes.php"
if errorlevel 1 set FAILED=1
echo.

echo ================================================================
echo  3/7  Config template vs installer vs application
echo ================================================================
php "_audit\verify-config.php"
if errorlevel 1 set FAILED=1
echo.

echo ================================================================
echo  4/7  PHP version floor vs README claim
echo ================================================================
php "_audit\verify-php-floor.php"
if errorlevel 1 set FAILED=1
echo.

echo ================================================================
echo  5/7  SUS parity + simulation integrity
echo ================================================================
node "_audit\verify-sus-parity.js"
if errorlevel 1 set FAILED=1
echo.

echo ================================================================
echo  6/7  HTTP behaviour (starts a dev server)
echo ================================================================
start /b php -S 127.0.0.1:8902 -t . >nul 2>&1
REM `timeout` refuses to run when stdin is redirected, which it is whenever this
REM script is called from a wrapper. Ping is the portable sleep.
ping -n 3 127.0.0.1 >nul 2>&1
php "_audit\verify-http.php"
if errorlevel 1 set FAILED=1
REM taskkill has the same stdin sensitivity; Stop-Process does not.
powershell -NoProfile -Command "Get-Process php -ErrorAction SilentlyContinue | Stop-Process -Force" >nul 2>&1
echo.

echo ================================================================
echo  7/7  Distribution package (skipped if none built)
echo ================================================================
if exist "release\spark-conference-1.0.0.zip" (
    powershell -NoProfile -ExecutionPolicy Bypass -File "_audit\verify-package.ps1"
    if errorlevel 1 set FAILED=1
) else (
    echo   no package in release\ - run build-package.ps1 first
)
echo.
echo.
echo ================================================================
if "%FAILED%"=="0" (
    echo  RESULT: all suites passed
) else (
    echo  RESULT: one or more suites failed - see output above
)
echo ================================================================

endlocal
exit /b %FAILED%
