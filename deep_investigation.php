<?php

echo "Deep Dive: Investigating Database Structure and Relationships\n";
echo "============================================================\n\n";

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Campaign;
use App\Models\Contribution;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "🔍 INVESTIGATING USER ID 1\n";
echo "==========================\n";

$user = User::find(1);
if (!$user) {
    echo "❌ User ID 1 not found!\n";
    exit;
}

echo "✅ User found: {$user->name} (ID: {$user->id})\n\n";

// 1. Check database tables exist
echo "1. DATABASE TABLES CHECK:\n";
echo "-------------------------\n";
$tables = ['users', 'campaigns', 'contributions', 'withdrawals', 'wallets'];
foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        $count = DB::table($table)->count();
        echo "✅ {$table}: {$count} records\n";
    } else {
        echo "❌ {$table}: Table not found!\n";
    }
}
echo "\n";

// 2. Check campaigns table structure and data
echo "2. CAMPAIGNS TABLE ANALYSIS:\n";
echo "----------------------------\n";
$campaignColumns = Schema::getColumnListing('campaigns');
echo "Columns: " . implode(', ', $campaignColumns) . "\n";

$userCampaigns = DB::table('campaigns')->where('user_id', $user->id)->get();
echo "User's campaigns count: " . $userCampaigns->count() . "\n";

if ($userCampaigns->count() > 0) {
    echo "Campaign details:\n";
    foreach ($userCampaigns as $campaign) {
        echo "  - ID: {$campaign->id}, Title: {$campaign->title}, User_ID: {$campaign->user_id}, Status: {$campaign->status}\n";
    }
} else {
    echo "❌ No campaigns found for user!\n";
}
echo "\n";

// 3. Check contributions table structure and data
echo "3. CONTRIBUTIONS TABLE ANALYSIS:\n";
echo "--------------------------------\n";
$contributionColumns = Schema::getColumnListing('contributions');
echo "Columns: " . implode(', ', $contributionColumns) . "\n";

// Check all contributions
$allContributions = DB::table('contributions')->get();
echo "Total contributions in system: " . $allContributions->count() . "\n";

if ($allContributions->count() > 0) {
    echo "Sample contributions:\n";
    foreach ($allContributions->take(5) as $contrib) {
        echo "  - ID: {$contrib->id}, Campaign_ID: {$contrib->campaign_id}, User_ID: " . ($contrib->user_id ?? 'NULL') . ", Amount: {$contrib->amount}, Status: {$contrib->status}\n";
    }
    echo "\n";
    
    // Check contributions linked to user's campaigns
    $campaignIds = $userCampaigns->pluck('id')->toArray();
    if (!empty($campaignIds)) {
        $contributionsToUserCampaigns = DB::table('contributions')
            ->whereIn('campaign_id', $campaignIds)
            ->get();
        
        echo "Contributions to user's campaigns: " . $contributionsToUserCampaigns->count() . "\n";
        
        if ($contributionsToUserCampaigns->count() > 0) {
            echo "Details:\n";
            foreach ($contributionsToUserCampaigns as $contrib) {
                echo "  - ID: {$contrib->id}, Campaign_ID: {$contrib->campaign_id}, Amount: {$contrib->amount}, Status: {$contrib->status}\n";
            }
            
            // Status breakdown
            $statusBreakdown = DB::table('contributions')
                ->whereIn('campaign_id', $campaignIds)
                ->select('status', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                ->groupBy('status')
                ->get();
            
            echo "\nStatus breakdown:\n";
            foreach ($statusBreakdown as $status) {
                echo "  - {$status->status}: {$status->count} contributions, Total: {$status->total}\n";
            }
        } else {
            echo "❌ No contributions found to user's campaigns!\n";
        }
    }
} else {
    echo "❌ No contributions found in the system!\n";
}
echo "\n";

// 4. Test the exact queries from the controller
echo "4. TESTING CONTROLLER QUERIES:\n";
echo "------------------------------\n";

// Test Campaign query
$totalCampaignsQuery = Campaign::where('user_id', $user->id);
echo "Campaign query SQL: " . $totalCampaignsQuery->toSql() . "\n";
echo "Campaign query bindings: " . json_encode($totalCampaignsQuery->getBindings()) . "\n";
$totalCampaigns = $totalCampaignsQuery->count();
echo "Result: {$totalCampaigns} campaigns\n\n";

// Test Contribution query
$contributionQuery = Contribution::whereHas('campaign', function ($query) use ($user) {
    $query->where('user_id', $user->id);
})->whereIn('status', ['completed', 'successful']);

echo "Contribution query SQL: " . $contributionQuery->toSql() . "\n";
echo "Contribution query bindings: " . json_encode($contributionQuery->getBindings()) . "\n";

$totalContributions = $contributionQuery->sum('amount');
$totalContributionCount = $contributionQuery->count();

echo "Result: {$totalContributions} total amount, {$totalContributionCount} count\n\n";

// 5. Check foreign key relationships
echo "5. FOREIGN KEY RELATIONSHIP CHECK:\n";
echo "----------------------------------\n";

// Check if contributions have valid campaign_id references
$orphanedContributions = DB::table('contributions')
    ->leftJoin('campaigns', 'contributions.campaign_id', '=', 'campaigns.id')
    ->whereNull('campaigns.id')
    ->count();

echo "Orphaned contributions (no matching campaign): {$orphanedContributions}\n";

// Check if campaigns have valid user_id references
$orphanedCampaigns = DB::table('campaigns')
    ->leftJoin('users', 'campaigns.user_id', '=', 'users.id')
    ->whereNull('users.id')
    ->count();

echo "Orphaned campaigns (no matching user): {$orphanedCampaigns}\n";

echo "\n";

// 6. Manual relationship test
echo "6. MANUAL RELATIONSHIP TEST:\n";
echo "----------------------------\n";

try {
    $userModel = User::with(['campaigns', 'contributions'])->find($user->id);
    echo "User campaigns via relationship: " . $userModel->campaigns->count() . "\n";
    echo "User contributions via relationship: " . $userModel->contributions->count() . "\n";
    
    // Test campaign -> contributions relationship
    if ($userModel->campaigns->count() > 0) {
        $firstCampaign = $userModel->campaigns->first();
        $campaignContributions = $firstCampaign->contributions;
        echo "First campaign contributions: " . $campaignContributions->count() . "\n";
        
        if ($campaignContributions->count() > 0) {
            echo "Sample campaign contribution statuses: ";
            echo $campaignContributions->pluck('status')->unique()->implode(', ') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error testing relationships: " . $e->getMessage() . "\n";
}

echo "\n🎯 DIAGNOSIS:\n";
echo "=============\n";

if ($totalCampaigns == 0) {
    echo "❌ ISSUE: User has no campaigns\n";
} elseif ($allContributions->count() == 0) {
    echo "❌ ISSUE: No contributions exist in the system\n";
} elseif (empty($campaignIds) || DB::table('contributions')->whereIn('campaign_id', $campaignIds)->count() == 0) {
    echo "❌ ISSUE: No contributions linked to user's campaigns\n";
} else {
    echo "❌ ISSUE: Contributions exist but status filtering might be wrong\n";
    echo "   Check if status values match ['completed', 'successful']\n";
}
