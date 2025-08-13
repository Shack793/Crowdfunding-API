<?php

echo "Debugging User Dashboard - Contribution Status Analysis\n";
echo "=====================================================\n\n";

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Campaign;
use App\Models\Contribution;
use App\Models\Withdrawal;

// Get user ID 1 (from your API response)
$user = User::find(1);

if (!$user) {
    echo "❌ User with ID 1 not found!\n";
    exit;
}

echo "🔍 Analyzing data for User ID: {$user->id} ({$user->name})\n\n";

// Check user's campaigns
echo "1. USER'S CAMPAIGNS:\n";
echo "-------------------\n";
$userCampaigns = Campaign::where('user_id', $user->id)->get();
echo "Total campaigns: " . $userCampaigns->count() . "\n";

foreach ($userCampaigns as $campaign) {
    echo "  - Campaign ID: {$campaign->id}, Title: {$campaign->title}, Status: {$campaign->status}\n";
}
echo "\n";

// Check all contributions to user's campaigns
echo "2. ALL CONTRIBUTIONS TO USER'S CAMPAIGNS:\n";
echo "----------------------------------------\n";
$allContributions = Contribution::whereHas('campaign', function ($query) use ($user) {
    $query->where('user_id', $user->id);
})->get();

echo "Total contributions found: " . $allContributions->count() . "\n";

if ($allContributions->count() > 0) {
    echo "Contribution details:\n";
    foreach ($allContributions as $contrib) {
        echo "  - ID: {$contrib->id}, Amount: {$contrib->amount}, Status: {$contrib->status}, Campaign: {$contrib->campaign_id}\n";
    }
} else {
    echo "❌ No contributions found to user's campaigns!\n";
}
echo "\n";

// Check contribution status distribution
echo "3. CONTRIBUTION STATUS ANALYSIS:\n";
echo "-------------------------------\n";
$statusCounts = Contribution::whereHas('campaign', function ($query) use ($user) {
    $query->where('user_id', $user->id);
})->groupBy('status')
  ->selectRaw('status, count(*) as count, sum(amount) as total_amount')
  ->get();

foreach ($statusCounts as $status) {
    echo "  - Status: {$status->status}, Count: {$status->count}, Total Amount: {$status->total_amount}\n";
}

if ($statusCounts->isEmpty()) {
    echo "❌ No contributions found with any status!\n";
}
echo "\n";

// Check all contributions in the system (to see if there are any at all)
echo "4. GLOBAL CONTRIBUTION CHECK:\n";
echo "----------------------------\n";
$globalContributions = Contribution::count();
$globalAmount = Contribution::sum('amount');
echo "Total contributions in system: {$globalContributions}\n";
echo "Total amount in system: {$globalAmount}\n";

if ($globalContributions > 0) {
    echo "Sample contributions:\n";
    $samples = Contribution::take(5)->get();
    foreach ($samples as $contrib) {
        echo "  - ID: {$contrib->id}, Amount: {$contrib->amount}, Status: {$contrib->status}, Campaign: {$contrib->campaign_id}, User: {$contrib->user_id}\n";
    }
}
echo "\n";

// Check user's withdrawals
echo "5. USER'S WITHDRAWALS:\n";
echo "---------------------\n";
$withdrawals = Withdrawal::where('user_id', $user->id)->get();
echo "Total withdrawals: " . $withdrawals->count() . "\n";
foreach ($withdrawals as $withdrawal) {
    echo "  - ID: {$withdrawal->id}, Amount: {$withdrawal->amount}, Status: {$withdrawal->status}\n";
}
echo "\n";

// Check wallet
echo "6. USER'S WALLET:\n";
echo "----------------\n";
$wallet = $user->wallet;
if ($wallet) {
    echo "Wallet found:\n";
    echo "  - Balance: {$wallet->balance}\n";
    echo "  - Total Withdrawn: {$wallet->total_withdrawn}\n";
    echo "  - Withdrawal Count: {$wallet->withdrawal_count}\n";
    echo "  - Status: {$wallet->status}\n";
} else {
    echo "❌ No wallet found for user!\n";
}
echo "\n";

echo "SUMMARY:\n";
echo "--------\n";
if ($allContributions->count() == 0) {
    echo "❌ ISSUE FOUND: No contributions to user's campaigns\n";
    echo "   This explains why totalContributions is 0.00\n";
    echo "   Check if:\n";
    echo "   1. Contributions exist in the database\n";
    echo "   2. Contributions are properly linked to campaigns\n";
    echo "   3. Campaigns are properly linked to users\n";
} else {
    echo "✅ Contributions found, checking status filtering...\n";
}
