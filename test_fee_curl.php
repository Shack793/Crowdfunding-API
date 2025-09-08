<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "=== CURL TEST FOR WITHDRAWAL FEE RECORD ===\n\n";

// Generate fresh token
$user = User::first();
$token = $user->createToken('curl-fee-test')->plainTextToken;

echo "User: " . $user->email . "\n";
echo "Fresh Token: " . $token . "\n\n";

// Simple test data
$testData = [
    'amount' => 50.00,
    'method' => 'mobile_money',
    'network' => 'MTN',
    'metadata' => [
        'phone_number' => '0244123456',
        'description' => 'cURL test withdrawal'
    ]
];

echo "Test Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// cURL command for manual testing
echo "=== MANUAL CURL COMMAND ===\n";
echo "curl -X POST \"http://admin.myeasydonate.com/api/v1/withdrawal-fees/record\" \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -H \"Accept: application/json\" \\\n";
echo "  -H \"Authorization: Bearer " . $token . "\" \\\n";
echo "  -d '" . json_encode($testData) . "'\n\n";

// Automated cURL test
echo "=== AUTOMATED CURL TEST ===\n";

$url = 'http://admin.myeasydonate.com/api/v1/withdrawal-fees/record';

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($testData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ],
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);

curl_close($curl);

echo "HTTP Status Code: " . $httpCode . "\n";

if ($error) {
    echo "cURL Error: " . $error . "\n";
} else {
    echo "Response:\n";
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Raw Response: " . $response . "\n";
    }
}

echo "\n=== POSTMAN SAMPLE DATA ===\n";
echo "Method: POST\n";
echo "URL: http://admin.myeasydonate.com/api/v1/withdrawal-fees/record\n\n";
echo "Headers:\n";
echo "Content-Type: application/json\n";
echo "Accept: application/json\n";
echo "Authorization: Bearer " . $token . "\n\n";
echo "Body (JSON):\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n";

echo "\n=== TEST COMPLETED ===\n";
