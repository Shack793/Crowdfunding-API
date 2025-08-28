<?php

echo "API Endpoint Testing for Email Verification\n";
echo "===========================================\n\n";

// Test configuration
$base_url = 'http://localhost/Crowdfunding1/crowddonation/public/api/v1';
$user_email = 'shadrack.new@example.com'; // Update with actual user email

echo "🌐 Testing API Endpoints\n";
echo "========================\n";
echo "Base URL: {$base_url}\n";
echo "Test Email: {$user_email}\n\n";

// Function to make API requests
function makeApiRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Add default headers
    $defaultHeaders = [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-Requested-With: XMLHttpRequest'
    ];
    
    $allHeaders = array_merge($defaultHeaders, $headers);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
    
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

// Get user token (you'll need to implement this based on your auth system)
function getUserToken() {
    // For testing, you might need to create a token manually
    // This is a placeholder - implement based on your auth system
    return 'your-api-token-here';
}

$token = getUserToken();
$authHeaders = $token ? ["Authorization: Bearer {$token}"] : [];

echo "1. Testing Send Verification Code Endpoint\n";
echo "==========================================\n";

$sendData = [
    'email' => $user_email
];

$sendResponse = makeApiRequest(
    $base_url . '/withdrawal/send-verification-code',
    'POST',
    $sendData,
    $authHeaders
);

echo "HTTP Code: {$sendResponse['http_code']}\n";
echo "Response: " . ($sendResponse['response'] ?: 'No response') . "\n";
echo "Error: " . ($sendResponse['error'] ?: 'None') . "\n\n";

if ($sendResponse['http_code'] === 200) {
    $responseData = json_decode($sendResponse['response'], true);
    echo "✅ Send verification code - SUCCESS\n";
    echo "Message: " . ($responseData['message'] ?? 'No message') . "\n";
    echo "Masked Email: " . ($responseData['masked_email'] ?? 'Not provided') . "\n";
    
    $verificationToken = $responseData['verification_token'] ?? null;
    if ($verificationToken) {
        echo "Verification Token: {$verificationToken}\n";
    }
} else {
    echo "❌ Send verification code - FAILED\n";
    $errorData = json_decode($sendResponse['response'], true);
    if ($errorData && isset($errorData['message'])) {
        echo "Error Message: {$errorData['message']}\n";
    }
}

echo "\n" . str_repeat("-", 50) . "\n\n";

echo "2. Testing Verify Code Endpoint (Invalid Code)\n";
echo "==============================================\n";

$verifyData = [
    'email' => $user_email,
    'code' => '000000' // Invalid code for testing
];

$verifyResponse = makeApiRequest(
    $base_url . '/withdrawal/verify-code',
    'POST',
    $verifyData,
    $authHeaders
);

echo "HTTP Code: {$verifyResponse['http_code']}\n";
echo "Response: " . ($verifyResponse['response'] ?: 'No response') . "\n";

if ($verifyResponse['http_code'] === 422 || $verifyResponse['http_code'] === 400) {
    echo "✅ Invalid code rejection - WORKING (Expected behavior)\n";
} else {
    echo "❌ Invalid code handling - UNEXPECTED RESPONSE\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

echo "3. Testing Resend Verification Code Endpoint\n";
echo "============================================\n";

$resendData = [
    'email' => $user_email
];

$resendResponse = makeApiRequest(
    $base_url . '/withdrawal/resend-verification-code',
    'POST',
    $resendData,
    $authHeaders
);

echo "HTTP Code: {$resendResponse['http_code']}\n";
echo "Response: " . ($resendResponse['response'] ?: 'No response') . "\n";

if ($resendResponse['http_code'] === 200) {
    echo "✅ Resend verification code - SUCCESS\n";
    $resendData = json_decode($resendResponse['response'], true);
    echo "Message: " . ($resendData['message'] ?? 'No message') . "\n";
} else {
    echo "❌ Resend verification code - FAILED\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

echo "4. Testing Verification Status Endpoint\n";
echo "=======================================\n";

$statusResponse = makeApiRequest(
    $base_url . '/withdrawal/verification-status?email=' . urlencode($user_email),
    'GET',
    null,
    $authHeaders
);

echo "HTTP Code: {$statusResponse['http_code']}\n";
echo "Response: " . ($statusResponse['response'] ?: 'No response') . "\n";

if ($statusResponse['http_code'] === 200) {
    echo "✅ Verification status - SUCCESS\n";
    $statusData = json_decode($statusResponse['response'], true);
    echo "Has Active Code: " . ($statusData['has_active_code'] ? 'Yes' : 'No') . "\n";
    echo "Expires At: " . ($statusData['expires_at'] ?? 'Not provided') . "\n";
} else {
    echo "❌ Verification status - FAILED\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

echo "📋 API TESTING SUMMARY\n";
echo "======================\n";
echo "1. Send Verification Code: " . ($sendResponse['http_code'] === 200 ? '✅ Working' : '❌ Issues') . "\n";
echo "2. Verify Code (Invalid): " . (in_array($verifyResponse['http_code'], [400, 422]) ? '✅ Working' : '❌ Issues') . "\n";
echo "3. Resend Verification: " . ($resendResponse['http_code'] === 200 ? '✅ Working' : '❌ Issues') . "\n";
echo "4. Verification Status: " . ($statusResponse['http_code'] === 200 ? '✅ Working' : '❌ Issues') . "\n";

echo "\n";
echo "🔧 DEBUGGING NOTES:\n";
echo "===================\n";
if ($sendResponse['http_code'] !== 200) {
    echo "• Check if the API routes are properly defined\n";
    echo "• Verify the EmailVerificationController exists\n";
    echo "• Check authentication middleware configuration\n";
}
if ($token === 'your-api-token-here') {
    echo "• Update the getUserToken() function with proper token generation\n";
    echo "• Ensure user authentication is working\n";
}
echo "• Make sure Laravel is running on the correct URL\n";
echo "• Check Laravel logs for any errors\n";
echo "• Verify email configuration for actual email sending\n";

echo "\n";
echo "📁 TO CHECK LARAVEL LOGS:\n";
echo "=========================\n";
echo "tail -f storage/logs/laravel.log\n";

echo "\n";
echo "🚀 Ready for frontend implementation!\n";
