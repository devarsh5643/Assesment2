@echo off
echo.
echo ========================================
echo  Adelaide Artisan Bakery - PHP Server
echo ========================================
echo.
echo Checking for PHP installation...
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: PHP is not installed or not in PATH
    echo.
    echo Please install PHP from: https://www.php.net/downloads.php
    echo Or use XAMPP/WAMP which includes Apache + PHP
    echo.
    echo After installation, ensure PHP is in your system PATH
    echo and run this script again.
    echo.
    pause
    exit /b 1
)

echo PHP found: 
php --version
echo.
echo Starting web server on http://localhost:8000
echo.
echo Press Ctrl+C to stop the server
echo.
php -S localhost:8000
