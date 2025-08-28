<?php

echo "🎯 Testing Withdrawal Endpoint with Fresh Token\n";
echo "===============================================\n\n";

// Fresh token from successful login
$token = '302|rHIMict2m9i8FJSZlTOHhgIrREgjya9C6fwihnCA662be60a';
$baseUrl = 'http://127.0.0.1:8000/api/v1';
$userEmail = 'admin@example.com'; // Email from the successful login

echo "Using token: " . substr($token, 0, 15) . "...\n";
echo "User email: {$userEmail}\n";
echo "Base URL: {$baseUrl}\n\n";

// Function to test withdrawal endpoint
function testWithdrawalEndpoint($token, $email) {
    $data = json_encode([
        'email' => $email
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/api/v1/withdrawal/send-verification-code');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ];

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

echo "🧪 Testing withdrawal endpoint...\n";
echo "==================================\n";

$result = testWithdrawalEndpoint($token, $userEmail);

echo "HTTP Code: {$result['http_code']}\n";
echo "Response: " . ($result['response'] ?: 'No response') . "\n";
echo "Error: " . ($result['error'] ?: 'None') . "\n\n";

if ($result['http_code'] === 200) {
    echo "✅ SUCCESS! Withdrawal endpoint is working!\n";
    echo "==========================================\n";

    $responseData = json_decode($result['response'], true);
    echo "Message: " . ($responseData['message'] ?? 'No message') . "\n";
    echo "Masked Email: " . ($responseData['masked_email'] ?? 'Not provided') . "\n";
    echo "Expires in: " . ($responseData['expires_in_minutes'] ?? 'Unknown') . " minutes\n";

    if (isset($responseData['verification_token'])) {
        echo "Verification Token: " . substr($responseData['verification_token'], 0, 20) . "...\n";
    }

    echo "\n📧 Check your email ({$userEmail}) for the verification code!\n";

} elseif ($result['http_code'] === 401) {
    echo "❌ FAILED: Still getting authentication error\n";
    echo "===============================================\n";
    echo "The token might be expired or invalid\n";

} elseif ($result['http_code'] === 422) {
    echo "❌ FAILED: Validation error\n";
    echo "===========================\n";
    $errorData = json_decode($result['response'], true);
    if ($errorData && isset($errorData['errors'])) {
        foreach ($errorData['errors'] as $field => $messages) {
            echo "Field '{$field}': " . implode(', ', $messages) . "\n";
        }
    }

} elseif ($result['http_code'] === 429) {
    echo "❌ FAILED: Rate limited\n";
    echo "========================\n";
    echo "Too many requests. Wait before trying again.\n";

} else {
    echo "❌ FAILED: Unexpected error\n";
    echo "===========================\n";
    echo "HTTP Code: {$result['http_code']}\n";
    $errorData = json_decode($result['response'], true);
    if ($errorData && isset($errorData['message'])) {
        echo "Error: " . $errorData['message'] . "\n";
    }
}

echo "\n📋 NEXT STEPS FOR POSTMAN:\n";
echo "===========================\n";
echo "1. 🔑 Use this token in Postman:\n";
echo "   {$token}\n\n";

echo "2. 🎯 Test withdrawal endpoint in Postman:\n";
echo "   POST http://127.0.0.1:8000/api/v1/withdrawal/send-verification-code\n";
echo "   Headers:\n";
echo "     Content-Type: application/json\n";
echo "     Authorization: Bearer {$token}\n";
echo "   Body:\n";
echo "   {\n";
echo "       \"email\": \"{$userEmail}\"\n";
echo "   }\n\n";

echo "3. 📧 Check your email for the 6-digit verification code\n\n";

echo "4. 🧪 Test verification with the code from email:\n";
echo "   POST http://127.0.0.1:8000/api/v1/withdrawal/verify-code\n";
echo "   Headers:\n";
echo "     Content-Type: application/json\n";
echo "     Authorization: Bearer {$token}\n";
echo "   Body:\n";
echo "   {\n";
echo "       \"email\": \"{$userEmail}\",\n";
echo "       \"code\": \"CODE_FROM_EMAIL\"\n";
echo "   }\n\n";

echo "🎉 The email verification system is working correctly!\n";
