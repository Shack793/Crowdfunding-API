<?php

// Test the update-after-withdrawal endpoint

$url = 'http://admin.myeasydonate.com/api/v1/wallet/update-after-withdrawal';
$token = '313|UatMjlcbrfGrWWrLDbW9BrTG8HWrvcBGCFe4EqTOfb9eabcc';

// Test data samples
$testCases = [
    [
        'name' => 'Successful Withdrawal',
        'data' => [
            'amount' => '50.00',
            'transaction_id' => 'TXN_TEST_001',
            'status' => 'success'
        ]
    ],
    [
        'name' => 'Failed Withdrawal',
        'data' => [
            'amount' => '25.00',
            'transaction_id' => 'TXN_TEST_002',
            'status' => 'failed'
        ]
    ],
    [
        'name' => 'Pending Withdrawal',
        'data' => [
            'amount' => '75.50',
            'transaction_id' => 'TXN_TEST_003',
            'status' => 'pending'
        ]
    ]
];

foreach ($testCases as $testCase) {
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Testing: " . $testCase['name'] . "\n";
    echo str_repeat("=", 50) . "\n";
    
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
    
    echo "Request Data: " . json_encode($testCase['data'], JSON_PRETTY_PRINT) . "\n\n";
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    echo "HTTP Status Code: " . $httpCode . "\n";
    
    if ($error) {
        echo "cURL Error: " . $error . "\n";
    } else {
        echo "Response: \n";
        $decoded = json_decode($response, true);
        if ($decoded) {
            echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo $response . "\n";
        }
    }
    
    echo "\n";
}
