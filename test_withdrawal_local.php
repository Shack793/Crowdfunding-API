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

echo "=== Testing WalletController::updateWalletAfterWithdrawal ===\n\n";

// Get user and authenticate
$user = User::first();
Auth::login($user);

echo "Authenticated as: " . $user->email . "\n";

// Get initial wallet balance
$wallet = $user->wallet;
echo "Initial wallet balance: " . $wallet->balance . " " . $wallet->currency . "\n\n";

// Create controller instance
$controller = new WalletController();

// Test cases
$testCases = [
    [
        'name' => 'Successful Withdrawal Test',
        'data' => [
            'amount' => '25.00',
            'transaction_id' => 'LOCAL_TEST_001',
            'status' => 'success'
        ]
    ],
    [
        'name' => 'Failed Withdrawal Test',
        'data' => [
            'amount' => '15.00',
            'transaction_id' => 'LOCAL_TEST_002',
            'status' => 'failed'
        ]
    ],
    [
        'name' => 'Pending Withdrawal Test',
        'data' => [
            'amount' => '30.00',
            'transaction_id' => 'LOCAL_TEST_003',
            'status' => 'pending'
        ]
    ]
];

foreach ($testCases as $testCase) {
    echo str_repeat("-", 60) . "\n";
    echo "Test: " . $testCase['name'] . "\n";
    echo str_repeat("-", 60) . "\n";
    
    // Create request object with proper data
    $request = Request::create('/api/v1/wallet/update-after-withdrawal', 'POST', $testCase['data']);
    $request->headers->set('Content-Type', 'application/json');
    $request->headers->set('Accept', 'application/json');
    
    // Set the request data properly for Laravel
    $request->merge($testCase['data']);
    
    echo "Request Data: " . json_encode($testCase['data'], JSON_PRETTY_PRINT) . "\n";
    
    // Get wallet balance before
    $walletBefore = $user->fresh()->wallet;
    echo "Wallet balance before: " . $walletBefore->balance . "\n";
    
    try {
        // Call controller method
        $response = $controller->updateWalletAfterWithdrawal($request);
        
        echo "Response Status: " . $response->getStatusCode() . "\n";
        echo "Response Data: " . $response->getContent() . "\n";
        
        // Get wallet balance after
        $walletAfter = $user->fresh()->wallet;
        echo "Wallet balance after: " . $walletAfter->balance . "\n";
        echo "Balance change: " . ($walletAfter->balance - $walletBefore->balance) . "\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    echo "\n";
}

echo "\n=== Test Summary ===\n";
$finalWallet = $user->fresh()->wallet;
echo "Final wallet balance: " . $finalWallet->balance . " " . $finalWallet->currency . "\n";
