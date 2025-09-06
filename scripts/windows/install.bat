@echo off
title LILAC System Setup
color 0a

echo =====================================
echo    LILAC System Installation
echo =====================================
echo.

echo Checking if XAMPP is installed...
if exist "C:\xampp\xampp-control.exe" (
    echo [OK] XAMPP found at C:\xampp\
) else (
    echo [ERROR] XAMPP not found!
    echo.
    echo Please install XAMPP first:
    echo 1. Download from https://www.apachefriends.org/
    echo 2. Install with default settings
    echo 3. Run this installer again
    echo.
    pause
    exit /b 1
)

echo.
echo Checking LILAC installation directory...
if not exist "C:\xampp\htdocs\LILAC\" (
    echo Creating LILAC directory...
    mkdir "C:\xampp\htdocs\LILAC\"
)

echo.
echo Copying LILAC files...
xcopy ".\*" "C:\xampp\htdocs\LILAC\" /E /H /C /I /Y
echo [OK] Files copied successfully!

echo.
echo =====================================
echo    Next Steps (Manual):
echo =====================================
echo 1. Start XAMPP Control Panel
echo 2. Start Apache and MySQL services
echo 3. Open browser: http://localhost/phpmyadmin
echo 4. Create database: lilac_system
echo 5. Import file: sql/schema.sql
echo 6. Access: http://localhost/LILAC/dashboard.html
echo.
echo Would you like to:
echo [1] Start XAMPP Control Panel
echo [2] Open phpMyAdmin in browser
echo [3] Open LILAC Dashboard
echo [4] Exit
echo.
set /p choice="Enter your choice (1-4): "

if "%choice%"=="1" (
    start "" "C:\xampp\xampp-control.exe"
)
if "%choice%"=="2" (
    start "" "http://localhost/phpmyadmin"
)
if "%choice%"=="3" (
    start "" "http://localhost/LILAC/dashboard.html"
)

echo.
echo Installation helper completed!
echo See install.md for detailed instructions.
pause 