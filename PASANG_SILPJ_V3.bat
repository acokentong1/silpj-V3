@echo off
cd /d "%~dp0"
set "SILPJ_PHP=php"
where php >nul 2>nul
if errorlevel 1 set "SILPJ_PHP=C:\xampp\php\php.exe"
"%SILPJ_PHP%" scripts\install-v3.php
pause
