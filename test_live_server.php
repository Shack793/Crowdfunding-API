<?php

// Test the live server endpoint with your exact data

$url = 'http://admin.myeasydonate.com/api/v1/wallet/update-after-withdrawal';
$token = '313|UatMjlcbrfGrWWrLDbW9BrTG8HWrvcBGCFe4EqTOfb9eabcc'; // From our earlier test

$testData = [
    'amount' => '10.00',
    'transaction_id' => 'POSTMAN_TEST_001',
    'status' => 'success'
];

echo "Testing live server with your exact data:\n";
echo "URL: " . $url . "\n";
echo "Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

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
    echo "Response: \n";
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo $response . "\n";
    }
}

// Also let's check the current balance via API
echo "\n" . str_repeat("=", 50) . "\n";
echo "Checking wallet balance via API:\n";

$balanceUrl = 'http://admin.myeasydonate.com/api/v1/wallet/balance';
$curl2 = curl_init();

curl_setopt_array($curl2, [
    CURLOPT_URL => $balanceUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ],
]);

$balanceResponse = curl_exec($curl2);
$balanceHttpCode = curl_getinfo($curl2, CURLINFO_HTTP_CODE);
curl_close($curl2);

echo "Balance Check HTTP Code: " . $balanceHttpCode . "\n";
echo "Balance Response: " . $balanceResponse . "\n";
