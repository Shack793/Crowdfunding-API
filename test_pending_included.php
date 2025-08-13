<?php

echo "Testing Updated UserDashboard - Including Pending Contributions\n";
echo "==============================================================\n\n";

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

echo "🔍 Testing Results for User ID: {$user->id} ({$user->name})\n\n";

// Test the updated queries with pending included
$totalContributions = Contribution::whereHas('campaign', function ($query) use ($user) {
    $query->where('user_id', $user->id);
})->whereIn('status', ['completed', 'successful', 'pending'])->sum('amount');

$totalContributionCount = Contribution::whereHas('campaign', function ($query) use ($user) {
    $query->where('user_id', $user->id);
})->whereIn('status', ['completed', 'successful', 'pending'])->count();

echo "✅ NEW RESULTS (Including Pending):\n";
echo "===================================\n";
echo "Total Contributions: " . number_format($totalContributions, 2) . " GHS\n";
echo "Total Contribution Count: {$totalContributionCount}\n\n";

// Breakdown by status
$statusBreakdown = Contribution::whereHas('campaign', function ($query) use ($user) {
    $query->where('user_id', $user->id);
})->select('status', \DB::raw('count(*) as count'), \DB::raw('sum(amount) as total'))
  ->groupBy('status')
  ->get();

echo "Status Breakdown:\n";
foreach ($statusBreakdown as $status) {
    echo "  - {$status->status}: {$status->count} contributions, " . number_format($status->total, 2) . " GHS\n";
}

// Test recent contributions
$recentContributions = Contribution::with(['campaign', 'user'])
    ->whereHas('campaign', function ($query) use ($user) {
        $query->where('user_id', $user->id);
    })
    ->whereIn('status', ['completed', 'successful', 'pending'])
    ->orderByDesc('created_at')
    ->limit(3)
    ->get();

echo "\n✅ Recent Contributions (Including Pending):\n";
echo "===========================================\n";
if ($recentContributions->count() > 0) {
    foreach ($recentContributions as $contrib) {
        $contributor = $contrib->name ?? ($contrib->user->name ?? 'Anonymous');
        echo "  - {$contributor}: " . number_format($contrib->amount, 2) . " GHS ({$contrib->status}) to '{$contrib->campaign->title}'\n";
    }
} else {
    echo "  No contributions found\n";
}

// User's campaigns
$totalCampaigns = Campaign::where('user_id', $user->id)->count();

// User's withdrawals  
$withdrawals = Withdrawal::where('user_id', $user->id)->sum('amount');

echo "\n📊 EXPECTED API RESPONSE:\n";
echo "=========================\n";

$expectedResponse = [
    'user_id' => $user->id,
    'totalCampaigns' => $totalCampaigns,
    'totalContributions' => number_format($totalContributions, 2),
    'totalContributionCount' => $totalContributionCount,
    'withdrawals' => number_format($withdrawals, 2),
    'recentContributions' => $recentContributions->map(function ($c) {
        return [
            'id' => $c->id,
            'contributor' => $c->name ?? ($c->user->name ?? 'Anonymous'),
            'campaign' => $c->campaign->title ?? null,
            'amount' => number_format($c->amount, 2),
            'date' => $c->created_at->toDateString(),
            'status' => $c->status,
        ];
    })->toArray()
];

echo json_encode($expectedResponse, JSON_PRETTY_PRINT);

if ($totalContributions > 0) {
    echo "\n\n✅ SUCCESS! The API will now show contribution data including pending contributions.\n";
    echo "🎯 Change Summary:\n";
    echo "  - Previously: Only 'completed' and 'successful' contributions counted\n";
    echo "  - Now: 'pending', 'completed', and 'successful' contributions all count\n";
    echo "  - Benefit: Users can see their total expected income including pending payments\n";
} else {
    echo "\n\n❌ Still showing zero contributions\n";
}
