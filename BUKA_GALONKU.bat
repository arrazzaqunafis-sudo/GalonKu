@echo off
title GalonKu - Auto Launcher & Setup
color 0b

echo ========================================================
echo               GALONKU - AUTO LAUNCHER
echo ========================================================
echo.

cd /d "%~dp0"

echo [1/3] Menyiapkan alias folder di Laragon...
if exist "C:\laragon\www" (
    if not exist "C:\laragon\www\GalonKu" (
        mklink /J "C:\laragon\www\GalonKu" "%~dp0" >nul 2>&1
    )
    if not exist "C:\laragon\www\percobaan" (
        mklink /J "C:\laragon\www\percobaan" "%~dp0" >nul 2>&1
    )
)

echo [2/3] Memeriksa koneksi database & auto-install...
php -r "require 'koneksi.php'; echo 'OK';" >nul 2>&1

echo [3/3] Membuka aplikasi GalonKu di browser...
start http://localhost/GalonKu/admin.php
start http://localhost/GalonKu/GalonKu_Mobile_Starter/pwa/courier/

echo.
echo ========================================================
echo Aplikasi GalonKu berhasil dijalankan!
echo - Panel Admin : http://localhost/GalonKu/admin.php
echo - PWA Kurir   : http://localhost/GalonKu/GalonKu_Mobile_Starter/pwa/courier/
echo - Pelanggan   : http://localhost/GalonKu/GalonKu_Mobile_Starter/pwa/customer/
echo ========================================================
echo.
pause
