@echo off
setlocal
title GalonKu - Auto Launcher
color 0b

cd /d "%~dp0"
set "PROJECT_DIR=%~dp0"
set "PROJECT_DIR=%PROJECT_DIR:~0,-1%"

for %%I in (
  "%PROJECT_DIR%"
  "%PROJECT_DIR%\.."
  "%PROJECT_DIR%\..\.."
  "%PROJECT_DIR%\..\..\.."
  "%PROJECT_DIR%\..\..\..\.."
) do (
  if exist "%%~fI\laragon.exe" set "LARAGON_DIR=%%~fI"
)

if not defined LARAGON_DIR (
  echo Laragon tidak ditemukan di sekitar folder proyek.
  echo Letakkan folder proyek GalonKu di dalam Laragon\www atau buka Laragon.exe terlebih dahulu.
  pause
  exit /b 1
)

set "WWW_DIR=%LARAGON_DIR%\www"
set "ALIAS_DIR=%WWW_DIR%\galonku-app"

if not exist "%WWW_DIR%" mkdir "%WWW_DIR%"
if not exist "%ALIAS_DIR%" (
  mklink /J "%ALIAS_DIR%" "%PROJECT_DIR%" >nul 2>&1
)

for %%P in (
  "%LARAGON_DIR%\bin\php\php-8.4.27-Win32-vs17-x64\php.exe"
  "%LARAGON_DIR%\bin\php\php.exe"
) do if exist "%%~P" set "PHP_EXE=%%~P"

if defined PHP_EXE (
  echo Menyiapkan database db_galonku...
  "%PHP_EXE%" -r "require '%PROJECT_DIR:\=\\%/koneksi.php'; echo 'Database siap';"
)

start "" "%LARAGON_DIR%\laragon.exe"

echo Menunggu Apache dan MySQL Laragon aktif...
timeout /t 4 /nobreak >nul

set "BASE_URL=http://localhost/galonku-app"
for /f "delims=" %%I in ('powershell -NoProfile -Command "$ip=(Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -notlike '127.*' -and $_.IPAddress -notlike '169.*' } | Sort-Object InterfaceMetric | Select-Object -First 1 -ExpandProperty IPAddress); if ($ip) { Write-Output $ip }"') do set "LAN_IP=%%I"

start "" "%BASE_URL%/admin.php"
start "" "%BASE_URL%/GalonKu_Mobile_Starter/pwa/courier/"
start "" "%BASE_URL%/GalonKu_Mobile_Starter/pwa/customer/"

if defined LAN_IP (
  echo.
  echo ========================================================
  echo GalonKu siap dibuka:
  echo Admin     : %BASE_URL%/admin.php
  echo Kurir     : %BASE_URL%/GalonKu_Mobile_Starter/pwa/courier/
  echo Pelanggan : %BASE_URL%/GalonKu_Mobile_Starter/pwa/customer/
  echo.
  echo URL untuk Android melalui jaringan LAN:
  echo Kurir     : http://%LAN_IP%/galonku-app/GalonKu_Mobile_Starter/pwa/courier/
  echo Pelanggan : http://%LAN_IP%/galonku-app/GalonKu_Mobile_Starter/pwa/customer/
  echo ========================================================
) else (
  echo.
  echo ========================================================
  echo GalonKu siap dibuka:
  echo Admin     : %BASE_URL%/admin.php
  echo Kurir     : %BASE_URL%/GalonKu_Mobile_Starter/pwa/courier/
  echo Pelanggan : %BASE_URL%/GalonKu_Mobile_Starter/pwa/customer/
  echo ========================================================
)

pause
endlocal
