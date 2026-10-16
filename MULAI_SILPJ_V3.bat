@echo off
setlocal DisableDelayedExpansion
cd /d "%~dp0"
set "V3_PHP=php"
where php >nul 2>nul
if not errorlevel 1 goto run
if exist "C:\xampp\php\php.exe" set "V3_PHP=C:\xampp\php\php.exe"
:run
"%V3_PHP%" "scripts\start-local.php"
if errorlevel 1 pause
