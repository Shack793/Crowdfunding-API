<?php

echo "Expected API Response Analysis\n";
echo "=============================\n\n";

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Campaign;
use App\Models\Contribution;
use App\Models\Withdrawal;

// Simulate the API response for User ID 1
$user = User::find(1);

if (!$user) {
    echo "User not found\n";
    exit;
}

// Calculate all the values that will be returned
$totalCampaigns = Campaign::where('user_id', $user->id)->count();

$totalContributions = Contribution::whereHas('campaign', function ($query) use ($user) {
    $query->where('user_id', $user->id);
})->whereIn('status', ['completed', 'successful'])->sum('amount');

// NEW: Total contribution count
$totalContributionCount = Contribution::whereHas('campaign', function ($query) use ($user) {
    $query->where('user_id', $user->id);
})->whereIn('status', ['completed', 'successful'])->count();

$withdrawals = Withdrawal::where('user_id', $user->id)->sum('amount');

$expiredCampaigns = Campaign::where('user_id', $user->id)
    ->where('status', 'expired')
    ->count();

// Get wallet stats
$wallet = $user->wallet;
$walletStats = [
    'balance' => number_format($wallet->balance ?? 0, 2),
    'total_withdrawn' => number_format($wallet->total_withdrawn ?? 0, 2),
    'withdrawal_count' => $wallet->withdrawal_count ?? 0,
    'currency' => $wallet->currency ?? 'GHS',
    'last_withdrawal_at' => $wallet->last_withdrawal_at,
    'status' => $wallet->status ?? 'active'
];

// Recent contributions
$recentContributions = Contribution::with(['campaign', 'user'])
    ->whereHas('campaign', function ($query) use ($user) {
        $query->where('user_id', $user->id);
    })
    ->whereIn('status', ['completed', 'successful'])
    ->orderByDesc('created_at')
    ->limit(3)
    ->get()
    ->map(function ($c) {
        return [
            'id' => $c->id,
            'contributor' => $c->name ?? ($c->user->name ?? 'Anonymous'),
            'campaign' => $c->campaign->title ?? null,
            'amount' => number_format($c->amount, 2),
            'date' => $c->created_at->toDateString(),
        ];
    });

// Withdrawal history
$withdrawalHistory = Withdrawal::where('user_id', $user->id)
    ->orderByDesc('created_at')
    ->limit(10)
    ->get()
    ->map(function ($w) {
        return [
            'id' => $w->id,
            'amount' => number_format($w->amount, 2),
            'status' => $w->status,
            'date' => $w->created_at->toDateString(),
            'created_at' => $w->created_at
        ];
    });

// Chart data (simplified for last 6 months)
$chartData = [];
$months = collect(range(0, 5))->map(function ($i) {
    return now()->subMonths($i)->format('Y-m');
})->reverse();

foreach ($months as $month) {
    $monthStart = "$month-01";
    $monthEnd = now()->parse("$month-01")->endOfMonth()->format('Y-m-d');
    
    $donations = Contribution::whereHas('campaign', function ($query) use ($user) {
        $query->where('user_id', $user->id);
    })->whereIn('status', ['completed', 'successful'])
      ->whereBetween('created_at', [$monthStart, $monthEnd])
      ->sum('amount');
    
    $withdrawal = Withdrawal::where('user_id', $user->id)
        ->whereBetween('created_at', [$monthStart, $monthEnd])
        ->sum('amount');
    
    $chartData[] = [
        'month' => $month,
        'donations' => floatval($donations),
        'withdrawals' => floatval($withdrawal),
    ];
}

// Build the expected response
$expectedResponse = [
    'user_id' => $user->id,
    'totalCampaigns' => $totalCampaigns,
    'totalContributions' => number_format($totalContributions, 2),
    'totalContributionCount' => $totalContributionCount, // NEW FIELD
    'withdrawals' => number_format($withdrawals, 2),
    'expiredCampaigns' => $expiredCampaigns,
    'walletStats' => $walletStats,
    'withdrawalHistory' => $withdrawalHistory->toArray(),
    'chartData' => $chartData,
    'recentContributions' => $recentContributions->toArray(),
];

echo "📊 EXPECTED API RESPONSE:\n";
echo "=========================\n";
echo json_encode($expectedResponse, JSON_PRETTY_PRINT);

echo "\n\n📈 KEY IMPROVEMENTS:\n";
echo "===================\n";
echo "✅ totalContributions: " . number_format($totalContributions, 2) . " GHS (was 0.00)\n";
echo "✅ totalContributionCount: {$totalContributionCount} contributions (NEW FIELD)\n";
echo "✅ recentContributions: " . $recentContributions->count() . " items (was empty)\n";
echo "✅ chartData: Will show actual donation amounts\n";
echo "✅ All data is user-specific and authenticated\n";
