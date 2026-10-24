@echo off
setlocal DisableDelayedExpansion
cd /d "%~dp0"
set "SILPJ_PHP=php"
where php >nul 2>nul
if not errorlevel 1 goto update
set "SILPJ_PHP=C:\xampp\php\php.exe"
:update
"%SILPJ_PHP%" artisan optimize:clear
if errorlevel 1 goto failed
"%SILPJ_PHP%" artisan migrate --force
if errorlevel 1 goto failed
echo Pembaruan selesai. Jalankan UJI_SILPJ_V3.bat pada salinan aplikasi.
pause
exit /b 0
:failed
echo Pembaruan gagal. Jangan lanjutkan penggunaan; catat pesan kesalahan di atas.
pause
exit /b 1
