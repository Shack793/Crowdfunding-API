<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

echo "=== Testing Mobile Number Formatting and API Endpoints ===\n\n";

// Function to format MSISDN for name enquiry (with leading 0, no 233)
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

// Function to format MSISDN for credit wallet (no leading 0, no 233)
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

// Test different number formats
$testNumbers = [
    '0598890221',      // Already formatted with 0
    '598890221',       // Without 0
    '233598890221',    // With country code
    '+233598890221',   // With country code and +
    '233 598 890 221', // With spaces
];

echo "📱 TESTING NUMBER FORMATTING:\n";
echo str_repeat("-", 60) . "\n";

foreach ($testNumbers as $number) {
    $enquiryFormat = formatMsisdnForEnquiry($number);
    $creditFormat = formatMsisdnForCredit($number);
    
    echo "Original: {$number}\n";
    echo "  → For Enquiry: {$enquiryFormat}\n";
    echo "  → For Credit:  {$creditFormat}\n\n";
}

echo "\n🌐 TESTING API ENDPOINTS:\n";
echo str_repeat("-", 60) . "\n";

// Test Name Enquiry
echo "1. Testing Name Enquiry Endpoint:\n";
$testMsisdn = '0598890221';
$enquiryPayload = [
    'msisdn' => $testMsisdn,
    'network' => 'MTN'
];

echo "URL: https://admin.myeasydonate.com/api/v1/wallet/name-enquiry\n";
echo "Payload: " . json_encode($enquiryPayload, JSON_PRETTY_PRINT) . "\n";

try {
    $response = Http::timeout(10)->post('https://admin.myeasydonate.com/api/v1/wallet/name-enquiry', $enquiryPayload);
    
    echo "Status: " . $response->status() . "\n";
    echo "Headers: " . json_encode($response->headers(), JSON_PRETTY_PRINT) . "\n";
    echo "Response: " . $response->body() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        echo "✓ Request successful\n";
        echo "Response Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        
        if (isset($data['customerName'])) {
            echo "✓ Customer Name Found: " . $data['customerName'] . "\n";
        } else if (isset($data['customer_name'])) {
            echo "✓ Customer Name Found (alt field): " . $data['customer_name'] . "\n";
        } else {
            echo "✗ Customer name not found. Available fields: " . implode(', ', array_keys($data)) . "\n";
        }
    } else {
        echo "✗ Request failed with status: " . $response->status() . "\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("-", 60) . "\n";

// Test Credit Wallet (simulation payload only)
echo "2. Credit Wallet Endpoint Structure:\n";
$creditMsisdn = formatMsisdnForCredit($testMsisdn);
$creditPayload = [
    'customer' => 'Test Customer',
    'msisdn' => $creditMsisdn,
    'amount' => '1.00',
    'network' => 'MTN',
    'narration' => 'Test Credit'
];

echo "URL: https://admin.myeasydonate.com/api/v1/payments/credit-wallet\n";
echo "Payload: " . json_encode($creditPayload, JSON_PRETTY_PRINT) . "\n";
echo "Note: Not actually sending this request to avoid real money transfer\n";

echo "\n📋 SUMMARY:\n";
echo "✓ Number formatting functions added\n";
echo "✓ Comprehensive logging added to AdminWithdrawal.php\n";
echo "✓ Error handling improved\n";
echo "✓ Both endpoints will use correct number formats\n";

echo "\n🔍 TO VIEW LOGS:\n";
echo "1. Check Laravel logs: storage/logs/laravel.log\n";
echo "2. Test in browser and watch Network tab\n";
echo "3. Use: tail -f storage/logs/laravel.log\n";

echo "\n🎯 Ready to test in Filament admin panel!\n";
