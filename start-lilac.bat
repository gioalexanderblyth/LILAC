@echo off
echo Starting LILAC System...

:: Open XAMPP Control Panel
echo Opening XAMPP Control Panel...
start "" "C:\xampp\xampp-control.exe"

:: Wait for control panel to open
timeout /t 3 >nul

:: Try to start Apache using XAMPP's command line
echo Starting Apache...
if exist "C:\xampp\xampp_start.exe" (
    start /b "C:\xampp\xampp_start.exe"
) else (
    echo Trying alternative Apache start...
    cd /d "C:\xampp"
    start /b "apache\bin\httpd.exe" -k start
)

:: Wait a bit
timeout /t 3 >nul

:: Try to start MySQL using XAMPP's command line
echo Starting MySQL...
if exist "C:\xampp\mysql_start.bat" (
    start /b "C:\xampp\mysql_start.bat"
) else (
    echo Trying alternative MySQL start...
    cd /d "C:\xampp\mysql\bin"
    start /b "mysqld.exe" --console
)

:: Wait for services to start
echo Waiting for services to start...
timeout /t 10 >nul

:: Check if services are running
echo Checking service status...
netstat -an | findstr ":80 " >nul
if %errorLevel% equ 0 (
    echo [✓] Apache is running
) else (
    echo [✗] Apache not running - please start manually from XAMPP Control Panel
)

netstat -an | findstr ":3306 " >nul
if %errorLevel% equ 0 (
    echo [✓] MySQL is running
) else (
    echo [✗] MySQL not running - please start manually from XAMPP Control Panel
)

:: Open LILAC in browser
echo Opening LILAC dashboard...
start "" "http://localhost/LILAC/dashboard.php"

echo.
echo LILAC System started! Check your browser.
echo If services aren't running, start them manually from XAMPP Control Panel.
pause 