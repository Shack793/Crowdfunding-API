<?php

echo "Analyzing All Users and Their Data\n";
echo "=================================\n\n";

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Campaign;
use App\Models\Contribution;
use App\Models\Withdrawal;

echo "📊 USER ANALYSIS:\n";
echo "==================\n";

$users = User::all();

foreach ($users as $user) {
    echo "\n👤 USER ID {$user->id}: {$user->name}\n";
    echo str_repeat("-", 30) . "\n";
    
    // Get user's campaigns
    $totalCampaigns = Campaign::where('user_id', $user->id)->count();
    
    // Get contributions to user's campaigns
    $totalContributions = Contribution::whereHas('campaign', function ($query) use ($user) {
        $query->where('user_id', $user->id);
    })->whereIn('status', ['completed', 'successful'])->sum('amount');
    
    $totalContributionCount = Contribution::whereHas('campaign', function ($query) use ($user) {
        $query->where('user_id', $user->id);
    })->whereIn('status', ['completed', 'successful'])->count();
    
    // Get user's withdrawals
    $withdrawals = Withdrawal::where('user_id', $user->id)->sum('amount');
    
    // Get wallet info
    $wallet = $user->wallet;
    
    echo "Campaigns: {$totalCampaigns}\n";
    echo "Total Contributions: " . number_format($totalContributions, 2) . " GHS\n";
    echo "Contribution Count: {$totalContributionCount}\n";
    echo "Withdrawals: " . number_format($withdrawals, 2) . " GHS\n";
    echo "Wallet Balance: " . number_format($wallet->balance ?? 0, 2) . " GHS\n";
    
    if ($totalContributions > 0) {
        echo "✅ This user has contribution data\n";
    } else {
        echo "❌ This user has NO contribution data\n";
    }
    
    // Show recent contributions for this user
    $recentContributions = Contribution::with(['campaign'])
        ->whereHas('campaign', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->whereIn('status', ['completed', 'successful'])
        ->orderByDesc('created_at')
        ->limit(2)
        ->get();
    
    if ($recentContributions->count() > 0) {
        echo "Recent contributions:\n";
        foreach ($recentContributions as $contrib) {
            echo "  - {$contrib->amount} GHS to '{$contrib->campaign->title}' ({$contrib->status})\n";
        }
    }
}

echo "\n\n🎯 SUMMARY:\n";
echo "===========\n";

$usersWithData = User::whereHas('campaigns.contributions', function ($query) {
    $query->whereIn('status', ['completed', 'successful']);
})->get();

echo "Users with contribution data: " . $usersWithData->count() . "\n";

if ($usersWithData->count() > 0) {
    echo "User IDs with data: " . $usersWithData->pluck('id')->implode(', ') . "\n";
    
    echo "\n🔍 If your API is returning zeros, check:\n";
    echo "1. Which user ID is authenticated in your API call\n";
    echo "2. If the changes are deployed to the production server\n";
    echo "3. If there are any caching mechanisms\n";
} else {
    echo "❌ No users have contribution data!\n";
}
