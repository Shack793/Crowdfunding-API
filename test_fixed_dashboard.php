<?php

echo "Testing Updated UserDashboard - Expected Results\n";
echo "==============================================\n\n";

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Campaign;
use App\Models\Contribution;
use App\Models\Withdrawal;

// Get user ID 1
$user = User::find(1);

echo "🔍 Expected Results for User ID: {$user->id} ({$user->name})\n\n";

// Test the updated query
$totalContributions = Contribution::whereHas('campaign', function ($query) use ($user) {
    $query->where('user_id', $user->id);
})->whereIn('status', ['completed', 'successful'])->sum('amount');

echo "✅ Total Contributions (completed + successful): " . number_format($totalContributions, 2) . " GHS\n";

// Breakdown by status
$successful = Contribution::whereHas('campaign', function ($query) use ($user) {
    $query->where('user_id', $user->id);
})->where('status', 'successful')->sum('amount');

$completed = Contribution::whereHas('campaign', function ($query) use ($user) {
    $query->where('user_id', $user->id);
})->where('status', 'completed')->sum('amount');

echo "   - Successful status: " . number_format($successful, 2) . " GHS\n";
echo "   - Completed status: " . number_format($completed, 2) . " GHS\n";

// User's campaigns count
$totalCampaigns = Campaign::where('user_id', $user->id)->count();
echo "✅ Total Campaigns: {$totalCampaigns}\n";

// User's withdrawals
$withdrawals = Withdrawal::where('user_id', $user->id)->sum('amount');
echo "✅ Total Withdrawals: " . number_format($withdrawals, 2) . " GHS\n";

// Recent contributions
$recentContributions = Contribution::with(['campaign', 'user'])
    ->whereHas('campaign', function ($query) use ($user) {
        $query->where('user_id', $user->id);
    })
    ->whereIn('status', ['completed', 'successful'])
    ->orderByDesc('created_at')
    ->limit(3)
    ->get();

echo "✅ Recent Contributions Count: " . $recentContributions->count() . "\n";

if ($recentContributions->count() > 0) {
    echo "   Recent contributions:\n";
    foreach ($recentContributions as $contrib) {
        $contributor = $contrib->name ?? ($contrib->user->name ?? 'Anonymous');
        echo "   - {$contributor}: " . number_format($contrib->amount, 2) . " GHS to '{$contrib->campaign->title}'\n";
    }
}

echo "\n🎯 EXPECTED API RESPONSE:\n";
echo "========================\n";
echo "totalContributions should be: " . number_format($totalContributions, 2) . " (instead of 0.00)\n";
echo "recentContributions should have: " . $recentContributions->count() . " items (instead of empty array)\n";

if ($totalContributions > 0) {
    echo "\n✅ SUCCESS: The fix should now show proper contribution amounts!\n";
} else {
    echo "\n❌ Still an issue: No contributions found with successful/completed status\n";
}
