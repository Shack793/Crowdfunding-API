<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Wallet;
use App\Http\Controllers\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== DEBUGGING INSUFFICIENT BALANCE ERROR ===\n\n";

// Get user and authenticate
$user = User::first();
Auth::login($user);

echo "User: " . $user->email . "\n";

// Get wallet info
$wallet = $user->wallet;
echo "Current Balance: " . $wallet->balance . "\n";
echo "Currency: " . $wallet->currency . "\n\n";

// Test the exact data you used
$testData = [
    'amount' => '10.00',
    'transaction_id' => 'POSTMAN_TEST_001',
    'status' => 'success'
];

echo "Testing with your exact data:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Debug the validation and comparison
echo "=== DEBUGGING STEP BY STEP ===\n";

// Step 1: Validate data
echo "1. Validating data...\n";
$amount = (float) $testData['amount'];
echo "   - Amount as string: '" . $testData['amount'] . "'\n";
echo "   - Amount as float: " . $amount . "\n";
echo "   - Wallet balance: " . $wallet->balance . "\n";
echo "   - Balance comparison (wallet >= amount): " . ($wallet->balance >= $amount ? 'TRUE' : 'FALSE') . "\n";
echo "   - Balance comparison (wallet < amount): " . ($wallet->balance < $amount ? 'TRUE' : 'FALSE') . "\n\n";

// Step 2: Check wallet balance type
echo "2. Checking wallet balance type and value:\n";
echo "   - Balance type: " . gettype($wallet->balance) . "\n";
echo "   - Balance value: " . var_export($wallet->balance, true) . "\n";
echo "   - Is numeric: " . (is_numeric($wallet->balance) ? 'YES' : 'NO') . "\n\n";

// Step 3: Test controller method directly
echo "3. Testing controller method...\n";
$controller = new WalletController();
$request = Request::create('/api/v1/wallet/update-after-withdrawal', 'POST', $testData);
$request->merge($testData);

try {
    $response = $controller->updateWalletAfterWithdrawal($request);
    echo "   Response Status: " . $response->getStatusCode() . "\n";
    echo "   Response Data: " . $response->getContent() . "\n";
    
    // Check wallet after
    $walletAfter = $user->fresh()->wallet;
    echo "   Balance after: " . $walletAfter->balance . "\n";
    
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== END DEBUG ===\n";
