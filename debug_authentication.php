<?php

echo "🔍 Authentication Debug Script\n";
echo "===============================\n\n";

// Test the token provided by the user
$token = 'evWWOuyXbTRey8QOzf3rlkY4UvdJZmtj3fKcB9ANc8e9b8a7';
$baseUrl = 'http://127.0.0.1:8000/api/v1';

echo "Testing token: " . substr($token, 0, 10) . "...\n";
echo "Base URL: {$baseUrl}\n\n";

// Function to make API requests
function testApiRequest($url, $method = 'GET', $token = null) {
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

echo "1. Testing auth-test endpoint (should show if token is valid):\n";
echo "------------------------------------------------------------\n";

$authTest = testApiRequest($baseUrl . '/auth-test', 'GET', $token);
echo "HTTP Code: {$authTest['http_code']}\n";
echo "Response: " . ($authTest['response'] ?: 'No response') . "\n";
echo "Error: " . ($authTest['error'] ?: 'None') . "\n\n";

echo "2. Testing withdrawal endpoint:\n";
echo "--------------------------------\n";

$withdrawalData = json_encode([
    'email' => 'shadrack.new@example.com'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/withdrawal/send-verification-code');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $withdrawalData);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$headers = [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$withdrawalResponse = curl_exec($ch);
$withdrawalHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$withdrawalError = curl_error($ch);

curl_close($ch);

echo "HTTP Code: {$withdrawalHttpCode}\n";
echo "Response: " . ($withdrawalResponse ?: 'No response') . "\n";
echo "Error: " . ($withdrawalError ?: 'None') . "\n\n";

echo "3. Testing user profile endpoint:\n";
echo "----------------------------------\n";

$userTest = testApiRequest($baseUrl . '/user', 'GET', $token);
echo "HTTP Code: {$userTest['http_code']}\n";
echo "Response: " . ($userTest['response'] ?: 'No response') . "\n";
echo "Error: " . ($userTest['error'] ?: 'None') . "\n\n";

echo "🔧 DEBUGGING RESULTS:\n";
echo "=====================\n";

if ($authTest['http_code'] === 200) {
    $authData = json_decode($authTest['response'], true);
    echo "✅ Auth test passed\n";
    echo "   Authenticated: " . ($authData['authenticated'] ? 'Yes' : 'No') . "\n";
    if ($authData['user']) {
        echo "   User: " . $authData['user']['name'] . " (" . $authData['user']['email'] . ")\n";
    }
    echo "   Token received: " . ($authData['token'] ? 'Yes' : 'No') . "\n";
} else {
    echo "❌ Auth test failed - Token is invalid or expired\n";
    echo "   This means you need to login again to get a fresh token\n";
}

if ($withdrawalHttpCode === 200) {
    echo "✅ Withdrawal endpoint working\n";
} else {
    echo "❌ Withdrawal endpoint failed\n";
    $errorData = json_decode($withdrawalResponse, true);
    if ($errorData && isset($errorData['message'])) {
        echo "   Error: " . $errorData['message'] . "\n";
    }
}

echo "\n📋 RECOMMENDED ACTIONS:\n";
echo "=======================\n";

if ($authTest['http_code'] !== 200) {
    echo "1. 🔄 Get a fresh token by logging in again\n";
    echo "2. 📝 Update your Postman environment with the new token\n";
    echo "3. 🧪 Test the auth-test endpoint first\n";
    echo "4. 🎯 Then test the withdrawal endpoint\n";
} else {
    echo "1. ✅ Your token is valid\n";
    echo "2. 🔍 Check if there's an issue with the withdrawal endpoint itself\n";
    echo "3. 📧 Verify the email address exists in your database\n";
}

echo "\n🔗 USEFUL ENDPOINTS TO TEST:\n";
echo "=============================\n";
echo "Auth Test: GET {$baseUrl}/auth-test\n";
echo "User Profile: GET {$baseUrl}/user\n";
echo "Login: POST {$baseUrl}/login\n";

echo "\n📧 WITHDRAWAL ENDPOINT:\n";
echo "======================\n";
echo "POST {$baseUrl}/withdrawal/send-verification-code\n";
echo "Headers: Authorization: Bearer YOUR_TOKEN\n";
echo "Body: {\"email\": \"your-email@example.com\"}\n";
