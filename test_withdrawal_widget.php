<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WithdrawalFee;
use App\Models\User;

echo "=== TESTING WITHDRAWAL FEES WIDGET & ADMIN WITHDRAWAL ===\n\n";

// Create some test fee records if none exist
$feeCount = WithdrawalFee::count();
echo "Current withdrawal fees in database: " . $feeCount . "\n";

if ($feeCount < 3) {
    echo "Creating test withdrawal fee records...\n";
    
    $user = User::first();
    if ($user) {
        // Create test fees
        WithdrawalFee::create([
            'user_id' => $user->id,
            'gross_amount' => 100.00,
            'fee_amount' => 2.50,
            'net_amount' => 97.50,
            'fee_percentage' => 2.5000,
            'currency' => 'GHS',
            'withdrawal_method' => 'mobile_money',
            'network' => 'MTN',
            'status' => 'calculated',
            'calculation_notes' => 'Test fee calculation',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Browser',
            'metadata' => ['test' => true]
        ]);

        WithdrawalFee::create([
            'user_id' => $user->id,
            'gross_amount' => 250.00,
            'fee_amount' => 3.75,
            'net_amount' => 246.25,
            'fee_percentage' => 1.5000,
            'currency' => 'GHS',
            'withdrawal_method' => 'bank_transfer',
            'status' => 'calculated',
            'calculation_notes' => 'Test bank transfer fee',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Browser',
            'metadata' => ['test' => true]
        ]);

        WithdrawalFee::create([
            'user_id' => $user->id,
            'gross_amount' => 75.00,
            'fee_amount' => 1.88,
            'net_amount' => 73.12,
            'fee_percentage' => 2.5000,
            'currency' => 'GHS',
            'withdrawal_method' => 'mobile_money',
            'network' => 'Vodafone',
            'status' => 'calculated',
            'calculation_notes' => 'Test Vodafone fee',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Browser',
            'metadata' => ['test' => true]
        ]);

        echo "✅ Created 3 test withdrawal fee records\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "WIDGET DATA PREVIEW\n";
echo str_repeat("=", 60) . "\n";

// Test widget calculations
$availableFees = WithdrawalFee::where('status', 'calculated')->sum('fee_amount');
$thisMonthFees = WithdrawalFee::where('status', 'applied')
    ->whereMonth('created_at', now()->month)
    ->whereYear('created_at', now()->year)
    ->sum('fee_amount');
$pendingCount = WithdrawalFee::where('status', 'calculated')->count();
$todayFees = WithdrawalFee::where('status', 'calculated')
    ->whereDate('created_at', today())
    ->sum('fee_amount');

echo "📊 Widget Statistics:\n";
echo "   Available for Withdrawal: GHS " . number_format($availableFees, 2) . "\n";
echo "   This Month Collected: GHS " . number_format($thisMonthFees, 2) . "\n";
echo "   Pending Transactions: " . $pendingCount . "\n";
echo "   Today's Fees: GHS " . number_format($todayFees, 2) . "\n";

echo "\n" . str_repeat("=", 60) . "\n";
echo "WITHDRAWAL FEES BREAKDOWN\n";
echo str_repeat("=", 60) . "\n";

$fees = WithdrawalFee::where('status', 'calculated')
    ->with('user')
    ->orderBy('created_at', 'desc')
    ->get();

if ($fees->count() > 0) {
    foreach ($fees as $fee) {
        echo "💰 Fee ID: {$fee->id}\n";
        echo "   User: {$fee->user->name} ({$fee->user->email})\n";
        echo "   Amount: GHS {$fee->gross_amount} → Fee: GHS {$fee->fee_amount} → Net: GHS {$fee->net_amount}\n";
        echo "   Method: {$fee->withdrawal_method}" . ($fee->network ? " ({$fee->network})" : "") . "\n";
        echo "   Status: {$fee->status}\n";
        echo "   Created: {$fee->created_at->format('Y-m-d H:i:s')}\n";
        echo "   " . str_repeat("-", 50) . "\n";
    }
} else {
    echo "❌ No pending withdrawal fees found\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "TESTING NAME ENQUIRY API\n";
echo str_repeat("=", 60) . "\n";

// Test the name enquiry API
$testMsisdn = '233574321997';
$testNetwork = 'MTN';

echo "Testing name enquiry for: {$testMsisdn} ({$testNetwork})\n";

try {
    $response = \Illuminate\Support\Facades\Http::timeout(10)->post('https://uniwallet.transflowitc.com/uniwallet/name/enquiry', [
        'productId' => 4,
        'merchantId' => 1457,
        'apiKey' => 'u2m0tblpemgr3e2ud9c21oqfe2ftqo4j',
        'msisdn' => $testMsisdn,
        'network' => $testNetwork,
    ]);

    echo "Response Status: " . $response->status() . "\n";
    echo "Response Body: " . $response->body() . "\n";

    if ($response->successful()) {
        $data = $response->json();
        if (isset($data['customerName'])) {
            echo "✅ Name enquiry successful: {$data['customerName']}\n";
        } else {
            echo "⚠️ Name enquiry returned no customer name\n";
        }
    } else {
        echo "❌ Name enquiry failed\n";
    }
} catch (\Exception $e) {
    echo "❌ Name enquiry error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "FILAMENT ACCESS INSTRUCTIONS\n";
echo str_repeat("=", 60) . "\n";

echo "🔗 Access the Filament admin panel:\n";
echo "   URL: http://admin.myeasydonate.com/admin\n";
echo "   \n";
echo "📊 Widget Location:\n";
echo "   Dashboard → WithdrawalFeesOverview widget\n";
echo "   \n";
echo "💳 Withdrawal Page:\n";
echo "   Navigation → Administration → Withdraw Fees\n";
echo "   \n";
echo "🔐 Authentication:\n";
echo "   Only users with 'super_admin' role can access withdrawal page\n";
echo "   Widget is visible to all admin users\n";

echo "\n=== TEST COMPLETED ===\n";
echo "The widget and withdrawal page are now ready for testing!\n";
