@echo off
cd /d "%~dp0"
set "SILPJ_PHP=php"
where php >nul 2>nul
if errorlevel 1 set "SILPJ_PHP=C:\xampp\php\php.exe"
"%SILPJ_PHP%" vendor/bin/phpunit --testsuite=V3
if errorlevel 1 (
 echo Pengujian gagal. Jangan gunakan pembaruan untuk operasional sebelum diperiksa.
 pause
 exit /b 1
)
echo Pengujian selesai. Tetap periksa tampilan dan cetakan pada salinan data.
pause
