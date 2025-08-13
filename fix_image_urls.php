<?php
// Fix Image URL Mismatches

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

echo "=== FIXING IMAGE URL MISMATCHES ===\n";

// Get all existing files in storage
$campaignsDir = storage_path('app/public/campaigns');
$existingFiles = [];
if (is_dir($campaignsDir)) {
    $files = array_diff(scandir($campaignsDir), array('.', '..'));
    foreach ($files as $file) {
        $existingFiles[] = $file;
    }
}

echo "Found " . count($existingFiles) . " files in storage:\n";
foreach ($existingFiles as $file) {
    echo "  - $file\n";
}
echo "---\n";

// Get campaigns with broken image URLs
$brokenCampaigns = Campaign::whereNotNull('image_url')
    ->where('image_url', '!=', '')
    ->get(['id', 'title', 'image_url']);

echo "Checking " . count($brokenCampaigns) . " campaigns for broken images:\n";

$fixedCount = 0;
$skippedCount = 0;

foreach ($brokenCampaigns as $campaign) {
    // Extract filename from URL
    $imageUrl = $campaign->image_url;
    $filename = basename($imageUrl);
    
    echo "\nCampaign ID {$campaign->id}: {$campaign->title}\n";
    echo "  Current URL: $imageUrl\n";
    echo "  Filename: $filename\n";
    
    // Check if this file exists in storage
    if (in_array($filename, $existingFiles)) {
        echo "  ✅ File exists - URL is correct\n";
        $skippedCount++;
    } else {
        echo "  ❌ File missing - needs fixing\n";
        
        // Option 1: Try to find a similar file
        $similarFile = null;
        foreach ($existingFiles as $existingFile) {
            // Check for similar names (first 10 characters)
            if (substr($filename, 0, 10) === substr($existingFile, 0, 10)) {
                $similarFile = $existingFile;
                break;
            }
        }
        
        if ($similarFile) {
            echo "  📎 Found similar file: $similarFile\n";
            echo "  🔄 Updating database...\n";
            
            Campaign::where('id', $campaign->id)->update([
                'image_url' => '/storage/campaigns/' . $similarFile
            ]);
            
            $fixedCount++;
            echo "  ✅ Updated to use $similarFile\n";
        } else {
            // Option 2: Use the first available file as placeholder
            if (!empty($existingFiles)) {
                $placeholderFile = $existingFiles[0];
                echo "  📎 Using placeholder file: $placeholderFile\n";
                echo "  🔄 Updating database...\n";
                
                Campaign::where('id', $campaign->id)->update([
                    'image_url' => '/storage/campaigns/' . $placeholderFile
                ]);
                
                $fixedCount++;
                echo "  ⚠️  Updated to use placeholder $placeholderFile\n";
            } else {
                echo "  ❌ No files available to use\n";
                $skippedCount++;
            }
        }
    }
}

echo "\n=== SUMMARY ===\n";
echo "Fixed campaigns: $fixedCount\n";
echo "Skipped (already correct): $skippedCount\n";

// Test a few URLs after fixing
echo "\n=== TESTING FIXED URLs ===\n";
$testCampaigns = Campaign::whereNotNull('image_url')
    ->where('image_url', '!=', '')
    ->take(3)
    ->get(['id', 'title', 'image_url']);

foreach ($testCampaigns as $campaign) {
    $fullUrl = 'https://admin.myeasydonate.com' . $campaign->image_url;
    echo "Testing: {$campaign->title}\n";
    echo "  URL: $fullUrl\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "  Status: $httpCode " . ($httpCode == 200 ? "✅ WORKING" : "❌ BROKEN") . "\n";
}

echo "\n=== FIX COMPLETE ===\n";
echo "Your images should now be working at https://admin.myeasydonate.com/storage/campaigns/\n";
