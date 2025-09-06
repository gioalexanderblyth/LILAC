@echo off
title LILAC System - Automatic Setup
color 0A

echo ================================================
echo      LILAC System - Automatic Setup
echo      Central Philippine University
echo ================================================
echo.

:: Check if running as administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] This script requires administrator privileges.
    echo Please right-click and select "Run as administrator"
    echo.
    pause
    exit /b 1
)

echo [INFO] Starting LILAC System setup...
echo.

:: Define paths
set "CURRENT_DIR=%~dp0"
set "XAMPP_PATH=C:\xampp"
set "HTDOCS_PATH=%XAMPP_PATH%\htdocs"
set "LILAC_DEST=%HTDOCS_PATH%\LILAC"

:: Check if XAMPP is installed
if not exist "%XAMPP_PATH%" (
    echo [ERROR] XAMPP not found at %XAMPP_PATH%
    echo Please install XAMPP first from: https://www.apachefriends.org/
    echo.
    pause
    exit /b 1
)

echo [✓] XAMPP found at %XAMPP_PATH%

:: Stop existing XAMPP services
echo [INFO] Stopping existing XAMPP services...
taskkill /f /im httpd.exe >nul 2>&1
taskkill /f /im mysqld.exe >nul 2>&1
timeout /t 2 >nul

:: Check if LILAC already exists in htdocs
if exist "%LILAC_DEST%" (
    echo [WARNING] LILAC folder already exists in htdocs
    set /p "overwrite=Do you want to overwrite it? (y/n): "
    if /i not "%overwrite%"=="y" (
        echo [INFO] Setup cancelled by user.
        pause
        exit /b 0
    )
    echo [INFO] Removing existing LILAC folder...
    rmdir /s /q "%LILAC_DEST%"
)

:: Copy LILAC folder to htdocs
echo [INFO] Copying LILAC files to htdocs...
xcopy "%CURRENT_DIR%*" "%LILAC_DEST%\" /E /I /Y /Q >nul
if %errorLevel% neq 0 (
    echo [ERROR] Failed to copy files to htdocs
    pause
    exit /b 1
)
echo [✓] LILAC files copied successfully

:: Start XAMPP services
echo [INFO] Starting XAMPP services...
start /wait /min "%XAMPP_PATH%\xampp_start.exe"

:: Alternative method if xampp_start.exe doesn't exist
if not exist "%XAMPP_PATH%\xampp_start.exe" (
    echo [INFO] Using alternative startup method...
    start /b "%XAMPP_PATH%\apache\bin\httpd.exe"
    start /b "%XAMPP_PATH%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP_PATH%\mysql\bin\my.ini"
)

:: Wait for services to start
echo [INFO] Waiting for services to start...
timeout /t 5 >nul

:: Check if Apache is running
netstat -an | findstr ":80 " >nul
if %errorLevel% equ 0 (
    echo [✓] Apache started successfully
) else (
    echo [WARNING] Apache may not be running properly
)

:: Check if MySQL is running
netstat -an | findstr ":3306 " >nul
if %errorLevel% equ 0 (
    echo [✓] MySQL started successfully
) else (
    echo [WARNING] MySQL may not be running properly
)

echo.
echo [INFO] Opening LILAC installer in your default browser...
timeout /t 2 >nul

:: Open the installer in default browser
start http://localhost/LILAC/install.php

echo.
echo ================================================
echo      SETUP COMPLETE!
echo ================================================
echo.
echo Next steps:
echo 1. Browser should open with LILAC installer
echo 2. Fill in database details:
echo    - Host: localhost
echo    - Username: root
echo    - Password: (leave blank)
echo    - Database: lilac_system
echo 3. Click "Install LILAC System"
echo.
echo After installation, access LILAC at:
echo http://localhost/LILAC/dashboard.html
echo.
echo XAMPP Control Panel will also open for monitoring
echo ================================================

:: Open XAMPP Control Panel
start "%XAMPP_PATH%\xampp-control.exe"

echo.
pause 