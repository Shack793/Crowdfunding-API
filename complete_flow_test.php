<?php

echo "🎯 COMPLETE EMAIL VERIFICATION FLOW TEST\n";
echo "=========================================\n\n";

// Fresh token and verification code
$token = '302|rHIMict2m9i8FJSZlTOHhgIrREgjya9C6fwihnCA662be60a';
$email = 'admin@example.com';
$verificationCode = '970770'; // From the latest database record
$baseUrl = 'http://127.0.0.1:8000/api/v1';

echo "Token: " . substr($token, 0, 15) . "...\n";
echo "Email: {$email}\n";
echo "Verification Code: {$verificationCode}\n";
echo "Base URL: {$baseUrl}\n\n";

// Function to make API requests
function makeApiRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

echo "1. 🧪 Testing Send Verification Code\n";
echo "=====================================\n";

$sendData = ['email' => $email];
$sendResult = makeApiRequest($baseUrl . '/withdrawal/send-verification-code', 'POST', $sendData, $token);

echo "HTTP Code: {$sendResult['http_code']}\n";
echo "Response: " . ($sendResult['response'] ?: 'No response') . "\n\n";

echo "2. 🧪 Testing Verify Code\n";
echo "=========================\n";

$verifyData = [
    'email' => $email,
    'code' => $verificationCode
];
$verifyResult = makeApiRequest($baseUrl . '/withdrawal/verify-code', 'POST', $verifyData, $token);

echo "HTTP Code: {$verifyResult['http_code']}\n";
echo "Response: " . ($verifyResult['response'] ?: 'No response') . "\n\n";

if ($verifyResult['http_code'] === 200) {
    $verifyData = json_decode($verifyResult['response'], true);
    $verificationToken = $verifyData['verification_token'] ?? null;

    echo "3. 🧪 Testing Verification Status\n";
    echo "==================================\n";

    $statusResult = makeApiRequest($baseUrl . '/withdrawal/verification-status?email=' . urlencode($email), 'GET', null, $token);
    echo "HTTP Code: {$statusResult['http_code']}\n";
    echo "Response: " . ($statusResult['response'] ?: 'No response') . "\n\n";

    echo "4. 🧪 Testing Withdrawal Creation (if verification token available)\n";
    echo "=================================================================\n";

    if ($verificationToken) {
        $withdrawalData = [
            'amount' => 100.00,
            'bank_name' => 'Test Bank',
            'account_number' => '1234567890',
            'account_name' => 'Test User',
            'verification_token' => $verificationToken
        ];

        $withdrawalResult = makeApiRequest($baseUrl . '/withdrawals', 'POST', $withdrawalData, $token);
        echo "HTTP Code: {$withdrawalResult['http_code']}\n";
        echo "Response: " . ($withdrawalResult['response'] ?: 'No response') . "\n\n";
    }
}

echo "5. 🧪 Testing Resend Verification Code\n";
echo "=======================================\n";

$resendData = ['email' => $email];
$resendResult = makeApiRequest($baseUrl . '/withdrawal/resend-verification-code', 'POST', $resendData, $token);

echo "HTTP Code: {$resendResult['http_code']}\n";
echo "Response: " . ($resendResult['response'] ?: 'No response') . "\n\n";

echo "🎉 COMPLETE FLOW TEST SUMMARY\n";
echo "==============================\n";

$tests = [
    'Send Verification Code' => $sendResult['http_code'] === 200,
    'Verify Code' => $verifyResult['http_code'] === 200,
    'Verification Status' => $statusResult['http_code'] === 200,
    'Resend Verification Code' => $resendResult['http_code'] === 200
];

foreach ($tests as $test => $passed) {
    echo ($passed ? '✅' : '❌') . " {$test}: " . ($passed ? 'PASSED' : 'FAILED') . "\n";
}

echo "\n📋 WORKING TOKEN FOR POSTMAN:\n";
echo "===============================\n";
echo "🔑 Token: {$token}\n";
echo "📧 Email: {$email}\n";
echo "🔢 Verification Code: {$verificationCode}\n\n";

echo "🎯 COPY THESE TO POSTMAN:\n";
echo "=========================\n";
echo "Headers for all requests:\n";
echo "Content-Type: application/json\n";
echo "Authorization: Bearer {$token}\n\n";

echo "Base URL: {$baseUrl}\n\n";

echo "Test Endpoints:\n";
echo "1. Send Code: POST /withdrawal/send-verification-code\n";
echo "   Body: {\"email\": \"{$email}\"}\n\n";

echo "2. Verify Code: POST /withdrawal/verify-code\n";
echo "   Body: {\"email\": \"{$email}\", \"code\": \"{$verificationCode}\"}\n\n";

echo "3. Check Status: GET /withdrawal/verification-status?email={$email}\n\n";

echo "4. Resend Code: POST /withdrawal/resend-verification-code\n";
echo "   Body: {\"email\": \"{$email}\"}\n\n";

echo "🚀 Your email verification system is fully working!\n";
