@echo off
setlocal
cd /d "%~dp0"

set "PHP_EXE="
where php >nul 2>&1
if not errorlevel 1 set "PHP_EXE=php"

if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if not defined PHP_EXE if exist "%~dp0..\..\php\php.exe" set "PHP_EXE=%~dp0..\..\php\php.exe"

if not defined PHP_EXE (
    echo PHP was not found.
    echo.
    echo Install XAMPP, or place this folder inside C:\xampp\htdocs\Assesment2.
    echo Then double-click START_WEBSITE.bat again.
    echo.
    pause
    exit /b 1
)

"%PHP_EXE%" -r "exit(extension_loaded('pdo_sqlite') ? 0 : 1);"
if errorlevel 1 (
    echo PHP is installed, but PDO SQLite is not enabled.
    echo Enable extension=pdo_sqlite in php.ini and restart this file.
    echo.
    pause
    exit /b 1
)

echo Starting Adelaide Artisan Bakery at http://localhost:3000
echo Press Ctrl+C in this window to stop the website.
start "" "http://localhost:3000"
"%PHP_EXE%" -S 127.0.0.1:3000 router.php

if errorlevel 1 (
    echo.
    echo The server could not start. Port 3000 may already be in use.
    pause
)
