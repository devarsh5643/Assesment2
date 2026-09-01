# Adelaide Artisan Bakery - PHP Server Starter (PowerShell)
# Run this script to start the PHP development server

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  Adelaide Artisan Bakery - PHP Server" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Check if PHP is installed
try {
    $phpVersion = php --version 2>$null
    if (-not $phpVersion) {
        throw "PHP not found"
    }
    Write-Host "✓ PHP found:" -ForegroundColor Green
    Write-Host "  $($phpVersion.Split([Environment]::NewLine)[0])`n" -ForegroundColor Gray
} catch {
    Write-Host "✗ ERROR: PHP is not installed or not in PATH" -ForegroundColor Red
    Write-Host "`nTo fix this:" -ForegroundColor Yellow
    Write-Host "  1. Download XAMPP from: https://www.apachefriends.org/" -ForegroundColor Gray
    Write-Host "  2. Run the installer (select PHP)" -ForegroundColor Gray
    Write-Host "  3. Restart PowerShell" -ForegroundColor Gray
    Write-Host "  4. Run this script again`n" -ForegroundColor Gray
    exit 1
}

Write-Host "Starting web server..." -ForegroundColor Cyan
Write-Host "Visit: http://localhost:8000" -ForegroundColor Yellow
Write-Host "Admin: http://localhost:8000/admin?token=change-this-development-token" -ForegroundColor Yellow
Write-Host "`nPress Ctrl+C to stop the server`n" -ForegroundColor Gray

# Start the server
& php -S localhost:8000
