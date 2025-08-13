#!/bin/bash

echo "=== STORAGE SETUP SCRIPT FOR PRODUCTION ==="
echo "Domain: https://admin.myeasydonate.com"
echo ""

# Clear config cache
echo "1. Clearing configuration cache..."
php artisan config:clear

# Clear application cache
echo "2. Clearing application cache..."
php artisan cache:clear

# Create storage symlink
echo "3. Creating storage symlink..."
php artisan storage:link

# Check if storage directories exist
echo "4. Checking storage directories..."
echo "   - storage/app/public exists: $([ -d 'storage/app/public' ] && echo 'YES' || echo 'NO')"
echo "   - public/storage symlink exists: $([ -L 'public/storage' ] && echo 'YES' || echo 'NO')"

# Create campaign images directory if it doesn't exist
echo "5. Creating campaign images directory..."
mkdir -p storage/app/public/campaigns
chmod 755 storage/app/public/campaigns

# List existing campaign images
echo "6. Checking existing campaign images..."
if [ -d "storage/app/public/campaigns" ]; then
    echo "   Campaign images found:"
    ls -la storage/app/public/campaigns/ | head -10
else
    echo "   No campaign images directory found"
fi

# Set proper permissions
echo "7. Setting proper permissions..."
chmod -R 755 storage/
chmod -R 755 public/storage

echo ""
echo "=== SETUP COMPLETE ==="
echo "Storage URL should now work: https://admin.myeasydonate.com/storage/campaigns/"
echo ""
echo "If images still don't work, you may need to:"
echo "1. Copy images from old server to storage/app/public/campaigns/"
echo "2. Update database URLs if they contain full URLs"
