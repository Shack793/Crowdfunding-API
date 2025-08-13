<?php
// Enhanced Image URL Diagnosis Script

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

echo "=== COMPREHENSIVE IMAGE URL DIAGNOSIS ===\n";

// 1. Check Laravel configuration
echo "1. LARAVEL CONFIGURATION:\n";
echo "APP_URL: " . config('app.url') . "\n";
echo "ASSET_URL: " . config('app.asset_url') . "\n";
echo "Default Filesystem: " . config('filesystems.default') . "\n";
echo "Public Disk URL: " . Storage::disk('public')->url('') . "\n";
echo "---\n";

// 2. Check storage symlink
echo "2. STORAGE SYMLINK CHECK:\n";
$publicStoragePath = public_path('storage');
$storagePublicPath = storage_path('app/public');
$symlinkExists = is_link($publicStoragePath);
$targetCorrect = $symlinkExists && readlink($publicStoragePath) === $storagePublicPath;

echo "Public storage path: $publicStoragePath\n";
echo "Storage public path: $storagePublicPath\n";
echo "Symlink exists: " . ($symlinkExists ? 'YES' : 'NO') . "\n";
if ($symlinkExists) {
    echo "Symlink target: " . readlink($publicStoragePath) . "\n";
    echo "Target correct: " . ($targetCorrect ? 'YES' : 'NO') . "\n";
}
echo "---\n";

// 3. Check physical file existence
echo "3. PHYSICAL FILE CHECKS:\n";
$campaignsDir = storage_path('app/public/campaigns');
echo "Campaigns directory: $campaignsDir\n";
echo "Directory exists: " . (is_dir($campaignsDir) ? 'YES' : 'NO') . "\n";

if (is_dir($campaignsDir)) {
    $files = array_slice(scandir($campaignsDir), 2, 10); // Skip . and .., take first 10
    echo "Files in campaigns directory (first 10):\n";
    foreach ($files as $file) {
        $filePath = $campaignsDir . '/' . $file;
        echo "  - $file (" . filesize($filePath) . " bytes)\n";
    }
    
    // Check the specific image mentioned
    $specificFile = 'ZCVAxzL3eFOmmNADPEzalS9bSE8rmrppujPZyzuD.jpg';
    $specificPath = $campaignsDir . '/' . $specificFile;
    echo "Specific image '$specificFile' exists: " . (file_exists($specificPath) ? 'YES' : 'NO') . "\n";
    if (file_exists($specificPath)) {
        echo "  Size: " . filesize($specificPath) . " bytes\n";
        echo "  Permissions: " . substr(sprintf('%o', fileperms($specificPath)), -4) . "\n";
    }
} else {
    echo "Campaigns directory does not exist!\n";
}
echo "---\n";

// 4. Database URL analysis
echo "4. DATABASE URL ANALYSIS:\n";
$campaignsWithImages = Campaign::whereNotNull('image_url')
    ->where('image_url', '!=', '')
    ->take(5)
    ->get(['id', 'title', 'image_url', 'thumbnail']);

echo "Sample campaign URLs (first 5):\n";
foreach ($campaignsWithImages as $campaign) {
    echo "ID: {$campaign->id} - {$campaign->title}\n";
    echo "  Image URL: {$campaign->image_url}\n";
    echo "  Thumbnail: {$campaign->thumbnail}\n";
    
    // Test if URL is accessible
    $imageUrl = $campaign->image_url;
    if (!empty($imageUrl)) {
        // Construct full URL if it's relative
        if (strpos($imageUrl, 'http') !== 0) {
            $fullUrl = rtrim(config('app.url'), '/') . '/' . ltrim($imageUrl, '/');
        } else {
            $fullUrl = $imageUrl;
        }
        
        echo "  Full URL: $fullUrl\n";
        
        // Test URL accessibility
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        echo "  HTTP Status: $httpCode";
        if (!empty($error)) {
            echo " (Error: $error)";
        }
        echo "\n";
        
        // Check if it's a filename and test Laravel storage URL
        if (!strpos($imageUrl, '/') && !strpos($imageUrl, 'http')) {
            $storageUrl = Storage::disk('public')->url('campaigns/' . $imageUrl);
            echo "  Laravel Storage URL: $storageUrl\n";
            
            // Test Laravel storage URL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $storageUrl);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_exec($ch);
            $storageHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            echo "  Storage URL Status: $storageHttpCode\n";
        }
    }
    echo "  ---\n";
}

// 5. URL Pattern Analysis
echo "5. URL PATTERN STATISTICS:\n";
$fullUrlCount = Campaign::where('image_url', 'like', 'http%')->count();
$relativeUrlCount = Campaign::where('image_url', 'like', '/storage%')->count();
$filenameOnlyCount = Campaign::where('image_url', 'not like', 'http%')
    ->where('image_url', 'not like', '/storage%')
    ->whereNotNull('image_url')
    ->where('image_url', '!=', '')
    ->count();

echo "Campaigns with full URLs (http): $fullUrlCount\n";
echo "Campaigns with relative URLs (/storage): $relativeUrlCount\n";
echo "Campaigns with filename only: $filenameOnlyCount\n";
echo "---\n";

// 6. Media table analysis
echo "6. MEDIA TABLE ANALYSIS:\n";
$mediaWithFiles = DB::table('media')
    ->whereNotNull('file_url')
    ->where('file_url', '!=', '')
    ->take(3)
    ->get(['id', 'campaign_id', 'file_url', 'file_type']);

foreach ($mediaWithFiles as $media) {
    echo "Media ID: {$media->id} (Campaign: {$media->campaign_id})\n";
    echo "  File URL: {$media->file_url}\n";
    echo "  File Type: {$media->file_type}\n";
    
    // Test media URL accessibility
    $mediaUrl = $media->file_url;
    if (!empty($mediaUrl)) {
        if (strpos($mediaUrl, 'http') !== 0) {
            $fullMediaUrl = rtrim(config('app.url'), '/') . '/' . ltrim($mediaUrl, '/');
        } else {
            $fullMediaUrl = $mediaUrl;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullMediaUrl);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "  HTTP Status: $httpCode\n";
    }
    echo "  ---\n";
}

// 7. Specific troubleshooting recommendations
echo "7. TROUBLESHOOTING RECOMMENDATIONS:\n";
if (!$symlinkExists) {
    echo "❌ CRITICAL: Storage symlink missing! Run: php artisan storage:link\n";
}
if (!is_dir($campaignsDir)) {
    echo "❌ CRITICAL: Campaigns directory missing! Create: storage/app/public/campaigns/\n";
}
if ($fullUrlCount > 0) {
    echo "⚠️  WARNING: Found campaigns with full URLs that may need updating\n";
}
if ($filenameOnlyCount > 0) {
    echo "ℹ️  INFO: Found campaigns with filename-only URLs (this is normal for Laravel storage)\n";
}

echo "\n=== DIAGNOSIS COMPLETE ===\n";
echo "If images still don't show, check:\n";
echo "1. Web server configuration for /storage/ path\n";
echo "2. File permissions (755 for directories, 644 for files)\n";
echo "3. Domain pointing and DNS resolution\n";
echo "4. SSL certificate if using HTTPS\n";
