<?php
// Database URL Update Script

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

echo "=== UPDATING IMAGE URLs IN DATABASE ===\n";

// Update campaigns table - image_url column
$campaignsUpdated = Campaign::where('image_url', 'like', 'https://crowdfundingapi.wgtesthub.com%')
    ->update([
        'image_url' => DB::raw("REPLACE(image_url, 'https://crowdfundingapi.wgtesthub.com', 'https://admin.myeasydonate.com')")
    ]);

echo "Updated $campaignsUpdated campaign image URLs in campaigns table\n";

// Update campaigns table - thumbnail column
$thumbnailsUpdated = Campaign::where('thumbnail', 'like', 'https://crowdfundingapi.wgtesthub.com%')
    ->update([
        'thumbnail' => DB::raw("REPLACE(thumbnail, 'https://crowdfundingapi.wgtesthub.com', 'https://admin.myeasydonate.com')")
    ]);

echo "Updated $thumbnailsUpdated campaign thumbnails in campaigns table\n";

// Update media table - file_url column
$mediaUpdated = DB::table('media')
    ->where('file_url', 'like', 'https://crowdfundingapi.wgtesthub.com%')
    ->update([
        'file_url' => DB::raw("REPLACE(file_url, 'https://crowdfundingapi.wgtesthub.com', 'https://admin.myeasydonate.com')")
    ]);

echo "Updated $mediaUpdated media file URLs in media table\n";

// Show some sample updated URLs
echo "\n=== SAMPLE UPDATED URLs ===\n";

$sampleCampaigns = Campaign::whereNotNull('image_url')
    ->where('image_url', 'like', 'https://admin.myeasydonate.com%')
    ->take(3)
    ->get(['id', 'title', 'image_url']);

echo "Sample campaign image URLs:\n";
foreach ($sampleCampaigns as $campaign) {
    echo "- {$campaign->title}: {$campaign->image_url}\n";
}

$sampleMedia = DB::table('media')
    ->whereNotNull('file_url')
    ->where('file_url', 'like', 'https://admin.myeasydonate.com%')
    ->take(3)
    ->get(['id', 'campaign_id', 'file_url']);

echo "\nSample media file URLs:\n";
foreach ($sampleMedia as $media) {
    echo "- Campaign ID {$media->campaign_id}: {$media->file_url}\n";
}

echo "\n=== UPDATE COMPLETE ===\n";
echo "Total updates: " . ($campaignsUpdated + $thumbnailsUpdated + $mediaUpdated) . " records\n";
