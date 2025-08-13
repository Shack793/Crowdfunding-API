# Storage Setup Script for Production
Write-Host "=== STORAGE SETUP SCRIPT FOR PRODUCTION ===" -ForegroundColor Green
Write-Host "Domain: https://admin.myeasydonate.com" -ForegroundColor Yellow
Write-Host ""

# Clear config cache
Write-Host "1. Clearing configuration cache..." -ForegroundColor Cyan
php artisan config:clear

# Clear application cache
Write-Host "2. Clearing application cache..." -ForegroundColor Cyan
php artisan cache:clear

# Create storage symlink
Write-Host "3. Creating storage symlink..." -ForegroundColor Cyan
php artisan storage:link

# Check if storage directories exist
Write-Host "4. Checking storage directories..." -ForegroundColor Cyan
$storageExists = Test-Path "storage\app\public"
$symlinkExists = Test-Path "public\storage"
Write-Host "   - storage/app/public exists: $storageExists" -ForegroundColor $(if($storageExists){"Green"}else{"Red"})
Write-Host "   - public/storage symlink exists: $symlinkExists" -ForegroundColor $(if($symlinkExists){"Green"}else{"Red"})

# Create campaign images directory if it doesn't exist
Write-Host "5. Creating campaign images directory..." -ForegroundColor Cyan
New-Item -ItemType Directory -Force -Path "storage\app\public\campaigns"

# List existing campaign images
Write-Host "6. Checking existing campaign images..." -ForegroundColor Cyan
if (Test-Path "storage\app\public\campaigns") {
    Write-Host "   Campaign images found:" -ForegroundColor Green
    Get-ChildItem "storage\app\public\campaigns" | Select-Object -First 10 | Format-Table Name, Length, LastWriteTime
} else {
    Write-Host "   No campaign images directory found" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== SETUP COMPLETE ===" -ForegroundColor Green
Write-Host "Storage URL should now work: https://admin.myeasydonate.com/storage/campaigns/" -ForegroundColor Yellow
Write-Host ""
Write-Host "If images still don't work, you may need to:" -ForegroundColor Cyan
Write-Host "1. Copy images from old server to storage/app/public/campaigns/" -ForegroundColor White
Write-Host "2. Update database URLs if they contain full URLs" -ForegroundColor White
