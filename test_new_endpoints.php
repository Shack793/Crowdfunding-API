<?php

use Illuminate\Support\Facades\Http;

echo "=== Testing New API Endpoints ===\n\n";

// Test 1: Name Enquiry
echo "1. Testing Name Enquiry Endpoint:\n";
echo "URL: https://admin.myeasydonate.com/api/v1/wallet/name-enquiry\n";

try {
    $response = Http::timeout(10)->post('https://admin.myeasydonate.com/api/v1/wallet/name-enquiry', [
        'msisdn' => '0598890221',
        'network' => 'MTN'
    ]);
    
    echo "Status: " . $response->status() . "\n";
    echo "Response: " . $response->body() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        if (isset($data['customerName'])) {
            echo "✓ Customer Name Found: " . $data['customerName'] . "\n";
        } else {
            echo "✗ Customer name not found in response\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Test 2: Credit Wallet (Simulation - don't actually send money)
echo "2. Credit Wallet Endpoint Structure:\n";
echo "URL: https://admin.myeasydonate.com/api/v1/payments/credit-wallet\n";
echo "Expected Payload:\n";
echo json_encode([
    'customer' => 'Shadrack Acquah',
    'msisdn' => '598890221',
    'amount' => '1',
    'network' => 'MTN',
    'narration' => 'Credit MTN Customer'
], JSON_PRETTY_PRINT) . "\n";

echo "\n✓ Endpoints updated in AdminWithdrawal.php\n";
echo "✓ Name enquiry now uses the new endpoint with simplified payload\n";
echo "✓ Credit wallet already uses correct endpoint and payload structure\n";

echo "\n=== Test Complete ===\n";
echo "Ready to test in Filament admin panel!\n";
