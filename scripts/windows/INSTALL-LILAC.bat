@echo off
title One-Click LILAC Setup
color 0B

echo ================================================
echo         🌺 LILAC SYSTEM INSTALLER 🌺
echo      Central Philippine University
echo         One-Click Setup - Just Wait!
echo ================================================
echo.

:: Run as administrator automatically
if not "%1"=="am_admin" (powershell start -verb runas '%0' am_admin & exit /b)

echo [✓] Running with administrator privileges
echo [INFO] Please wait while we set up LILAC...
echo.

:: Copy files to htdocs
if not exist "C:\xampp\htdocs" mkdir "C:\xampp\htdocs"
echo [INFO] Copying LILAC files...
xcopy "%~dp0*" "C:\xampp\htdocs\LILAC\" /E /I /Y /Q /EXCLUDE:excludes.txt >nul 2>&1

:: Start XAMPP
echo [INFO] Starting XAMPP services...
if exist "C:\xampp\xampp_start.exe" (
    start /min "C:\xampp\xampp_start.exe"
) else (
    if exist "C:\xampp\xampp-control.exe" (
        start /min "C:\xampp\xampp-control.exe"
    )
)

:: Wait and open installer
echo [INFO] Opening LILAC installer...
timeout /t 8 >nul
start http://localhost/LILAC/install.php

echo.
echo ================================================
echo     🎉 SETUP COMPLETE! 🎉
echo.
echo ✅ LILAC files copied to htdocs
echo ✅ XAMPP started
echo ✅ Browser opened with installer
echo.
echo NEXT STEPS:
echo 1. Fill database form (use defaults)
echo 2. Click "Install LILAC System"
echo 3. Access: http://localhost/LILAC/dashboard.html
echo ================================================
echo.

timeout /t 10 