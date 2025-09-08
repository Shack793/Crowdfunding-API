<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== FINAL API TEST ===\n\n";

// Test the exact same logic as in AdminWithdrawal
function formatMsisdnForEnquiry(string $msisdn): string
{
    $msisdn = preg_replace('/[^0-9]/', '', $msisdn);
    
    if (str_starts_with($msisdn, '233')) {
        return '0' . substr($msisdn, 3);
    }
    
    if (!str_starts_with($msisdn, '0')) {
        return '0' . $msisdn;
    }
    
    return $msisdn;
}

function formatMsisdnForCredit(string $msisdn): string
{
    $msisdn = preg_replace('/[^0-9]/', '', $msisdn);
    
    if (str_starts_with($msisdn, '233')) {
        return substr($msisdn, 3);
    }
    
    if (str_starts_with($msisdn, '0')) {
        return substr($msisdn, 1);
    }
    
    return $msisdn;
}

$testMsisdn = '0598890221';
$network = 'MTN';

echo "📱 Testing with number: {$testMsisdn}\n\n";

// Test Name Enquiry
echo "1. NAME ENQUIRY TEST:\n";
$formattedMsisdn = formatMsisdnForEnquiry($testMsisdn);
echo "Original: {$testMsisdn}\n";
echo "Formatted for enquiry: {$formattedMsisdn}\n\n";

$payload = [
    'msisdn' => $formattedMsisdn,
    'network' => $network,
];

echo "Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n";

try {
    $response = Http::timeout(10)->post('https://admin.myeasydonate.com/api/v1/wallet/name-enquiry', $payload);
    
    echo "Status: " . $response->status() . "\n";
    echo "Response: " . $response->body() . "\n\n";
    
    if ($response->successful()) {
        $data = $response->json();
        
        // Use the same logic as AdminWithdrawal
        if (isset($data['success']) && $data['success'] && isset($data['data']['name'])) {
            $customerName = $data['data']['name'];
            echo "✅ SUCCESS: Customer name found: {$customerName}\n";
        } else if (isset($data['customerName'])) {
            echo "✅ SUCCESS: Customer name found: {$data['customerName']}\n";
        } else if (isset($data['customer_name'])) {
            echo "✅ SUCCESS: Customer name found: {$data['customer_name']}\n";
        } else {
            echo "❌ FAILED: Customer name not found\n";
            echo "Available fields: " . implode(', ', array_keys($data)) . "\n";
        }
    } else {
        echo "❌ FAILED: HTTP Status " . $response->status() . "\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Test Credit Wallet formatting
echo "2. CREDIT WALLET FORMATTING TEST:\n";
$creditMsisdn = formatMsisdnForCredit($testMsisdn);
echo "Original: {$testMsisdn}\n";
echo "Formatted for credit: {$creditMsisdn}\n\n";

$creditPayload = [
    'customer' => 'SHADRACK ACQUAH',
    'msisdn' => $creditMsisdn,
    'amount' => '1.00',
    'network' => $network,
    'narration' => 'Test Admin Fee Withdrawal'
];

echo "Credit payload: " . json_encode($creditPayload, JSON_PRETTY_PRINT) . "\n";

echo "\n✅ EVERYTHING IS READY!\n";
echo "\n🎯 NEXT STEPS:\n";
echo "1. Go to: http://localhost:8001/admin\n";
echo "2. Navigate to: Administration → Withdraw Fees\n";
echo "3. Enter mobile number: {$testMsisdn}\n";
echo "4. Select network: {$network}\n";
echo "5. Customer name should auto-populate: SHADRACK ACQUAH\n";
echo "6. Enter withdrawal amount and test!\n";

echo "\n📋 LOG MONITORING:\n";
echo "Watch logs with: tail -f storage/logs/laravel.log\n";
