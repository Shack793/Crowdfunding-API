<?php

echo "🔍 Authentication Debug with Logging\n";
echo "=====================================\n\n";

// Test the authentication with the working token
$token = '302|rHIMict2m9i8FJSZlTOHhgIrREgjya9C6fwihnCA662be60a';
$baseUrl = 'http://127.0.0.1:8000/api/v1';
$email = 'admin@example.com';

echo "Testing with token: " . substr($token, 0, 15) . "...\n";
echo "Base URL: {$baseUrl}\n\n";

// Function to make API requests
function testApiRequest($url, $method = 'GET', $data = null, $token = null) {
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

echo "1. 🧪 Testing auth-test endpoint (should work)\n";
echo "===============================================\n";

$authTest = testApiRequest($baseUrl . '/auth-test', 'GET', null, $token);
echo "HTTP Code: {$authTest['http_code']}\n";
echo "Response: " . ($authTest['response'] ?: 'No response') . "\n";
echo "Error: " . ($authTest['error'] ?: 'None') . "\n\n";

echo "2. 🧪 Testing withdrawal endpoint (with logging)\n";
echo "=================================================\n";

$withdrawalData = [
    'email' => $email
];

$withdrawalTest = testApiRequest($baseUrl . '/withdrawal/send-verification-code', 'POST', $withdrawalData, $token);
echo "HTTP Code: {$withdrawalTest['http_code']}\n";
echo "Response: " . ($withdrawalTest['response'] ?: 'No response') . "\n";
echo "Error: " . ($withdrawalTest['error'] ?: 'None') . "\n\n";

echo "3. 📋 Check Laravel Logs\n";
echo "=========================\n";
echo "Run this command to see the detailed logs:\n";
echo "tail -f storage/logs/laravel.log\n\n";

echo "The logs will show:\n";
echo "• Request headers and bearer token\n";
echo "• Authentication check results\n";
echo "• User information\n";
echo "• Any errors that occurred\n\n";

echo "🔍 EXPECTED LOG OUTPUT:\n";
echo "=======================\n";
echo "You should see entries like:\n";
echo "[2025-08-27 14:56:58] local.INFO: EmailVerificationController@sendVerificationCode called\n";
echo "[2025-08-27 14:56:58] local.INFO: Authentication check result\n\n";

echo "🚨 IF YOU SEE AUTHENTICATION ERRORS:\n";
echo "=====================================\n";
echo "• Check if Laravel is running: php artisan serve\n";
echo "• Verify the token is correct\n";
echo "• Check Sanctum configuration\n";
echo "• Look for middleware issues\n\n";

echo "📞 NEXT STEPS:\n";
echo "==============\n";
echo "1. Run this test script\n";
echo "2. Check the Laravel logs: tail -f storage/logs/laravel.log\n";
echo "3. Share the log output if you still get 401 errors\n";
echo "4. The logs will tell us exactly what's failing\n\n";

echo "🎯 TEST AGAIN:\n";
echo "==============\n";
echo "After running this, try your Postman request again and check the logs!\n";
