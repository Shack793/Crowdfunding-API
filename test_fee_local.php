<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Http\Controllers\Api\WithdrawalFeeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== TESTING WITHDRAWAL FEE RECORD CONTROLLER LOCALLY ===\n\n";

// Get user and authenticate
$user = User::first();
Auth::login($user);

echo "Authenticated as: " . $user->email . "\n\n";

// Create controller instance
$controller = new WithdrawalFeeController();

// Test data
$testData = [
    'amount' => 100.00,
    'method' => 'mobile_money',
    'network' => 'MTN',
    'metadata' => [
        'phone_number' => '0244123456',
        'description' => 'Local test withdrawal'
    ]
];

echo "Test Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Create request
$request = Request::create('/api/v1/withdrawal-fees/record', 'POST', $testData);
$request->headers->set('Content-Type', 'application/json');
$request->headers->set('Accept', 'application/json');
$request->merge($testData);

try {
    echo "Calling recordFee method...\n";
    $response = $controller->recordFee($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Data: " . $response->getContent() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== LOCAL TEST COMPLETED ===\n";
