<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "=== TESTING WITHDRAWAL FEE RECORD ENDPOINT ===\n\n";

// Get authentication token
$user = User::first();
$token = $user->createToken('fee-test-token')->plainTextToken;

echo "User: " . $user->email . "\n";
echo "Token: " . $token . "\n\n";

// Test endpoint URL
$url = 'http://admin.myeasydonate.com/api/v1/withdrawal-fees/record';
echo "Testing endpoint: " . $url . "\n\n";

// Test cases for different scenarios
$testCases = [
    [
        'name' => 'Mobile Money Withdrawal - MTN',
        'data' => [
            'amount' => 100.00,
            'method' => 'mobile_money',
            'network' => 'MTN',
            'metadata' => [
                'phone_number' => '0244123456',
                'description' => 'Test MTN withdrawal'
            ]
        ]
    ],
    [
        'name' => 'Bank Transfer Withdrawal',
        'data' => [
            'amount' => 250.50,
            'method' => 'bank_transfer',
            'metadata' => [
                'bank_name' => 'GCB Bank',
                'account_number' => '1234567890',
                'description' => 'Test bank transfer'
            ]
        ]
    ],
    [
        'name' => 'Mobile Money Withdrawal - Vodafone',
        'data' => [
            'amount' => 75.00,
            'method' => 'mobile_money',
            'network' => 'Vodafone',
            'metadata' => [
                'phone_number' => '0201987654',
                'description' => 'Test Vodafone withdrawal'
            ]
        ]
    ],
    [
        'name' => 'Small Amount Withdrawal',
        'data' => [
            'amount' => 10.00,
            'method' => 'mobile_money',
            'network' => 'AirtelTigo',
            'metadata' => [
                'phone_number' => '0277555444',
                'description' => 'Small test withdrawal'
            ]
        ]
    ]
];

foreach ($testCases as $index => $testCase) {
    echo str_repeat("=", 60) . "\n";
    echo "Test " . ($index + 1) . ": " . $testCase['name'] . "\n";
    echo str_repeat("=", 60) . "\n";
    
    echo "Request Data:\n";
    echo json_encode($testCase['data'], JSON_PRETTY_PRINT) . "\n\n";
    
    // Make cURL request
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($testCase['data']),
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
            echo $response . "\n";
        }
    }
    
    echo "\n";
    
    // Add a small delay between requests
    sleep(1);
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "TESTING VALIDATION ERRORS\n";
echo str_repeat("=", 60) . "\n";

// Test validation errors
$invalidTestCases = [
    [
        'name' => 'Missing Amount',
        'data' => [
            'method' => 'mobile_money',
            'network' => 'MTN'
        ]
    ],
    [
        'name' => 'Invalid Method',
        'data' => [
            'amount' => 50.00,
            'method' => 'invalid_method',
            'network' => 'MTN'
        ]
    ],
    [
        'name' => 'Invalid Network',
        'data' => [
            'amount' => 50.00,
            'method' => 'mobile_money',
            'network' => 'InvalidNetwork'
        ]
    ],
    [
        'name' => 'Amount Too Large',
        'data' => [
            'amount' => 15000.00,
            'method' => 'mobile_money',
            'network' => 'MTN'
        ]
    ]
];

foreach ($invalidTestCases as $index => $testCase) {
    echo "\nValidation Test " . ($index + 1) . ": " . $testCase['name'] . "\n";
    echo "Data: " . json_encode($testCase['data']) . "\n";
    
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($testCase['data']),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ],
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    echo "Status: " . $httpCode . "\n";
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "Message: " . ($decoded['message'] ?? 'No message') . "\n";
        if (isset($decoded['errors'])) {
            echo "Errors: " . json_encode($decoded['errors']) . "\n";
        }
    }
}

echo "\n=== TEST COMPLETED ===\n";
