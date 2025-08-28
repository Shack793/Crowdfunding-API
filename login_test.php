<?php

echo "🔑 Login Script - Get Fresh Token\n";
echo "==================================\n\n";

$baseUrl = 'http://127.0.0.1:8000/api/v1';

// Function to make login request
function loginRequest($email, $password) {
    $loginData = json_encode([
        'email' => $email,
        'password' => $password
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/api/v1/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
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

// Test login with the user from our test database
echo "Testing login with known user...\n";
echo "===================================\n";

$testUsers = [
    ['email' => 'shadrack.new@example.com', 'password' => 'password123'],
    ['email' => 'test@example.com', 'password' => 'password123'],
    ['email' => 'admin@example.com', 'password' => 'password123']
];

foreach ($testUsers as $user) {
    echo "\n🔍 Testing login for: {$user['email']}\n";
    echo "------------------------------------------\n";

    $login = loginRequest($user['email'], $user['password']);

    echo "HTTP Code: {$login['http_code']}\n";

    if ($login['http_code'] === 200) {
        $responseData = json_decode($login['response'], true);
        if (isset($responseData['token'])) {
            echo "✅ Login successful!\n";
            echo "🔑 Token: {$responseData['token']}\n";
            echo "👤 User: " . ($responseData['user']['name'] ?? 'Unknown') . "\n";
            echo "📧 Email: " . ($responseData['user']['email'] ?? 'Unknown') . "\n";

            // Test the token immediately
            echo "\n🧪 Testing token with auth-test endpoint...\n";
            echo "---------------------------------------------\n";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/api/v1/auth-test');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $responseData['token']
            ]);

            $authTest = curl_exec($ch);
            $authCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            echo "Auth Test HTTP Code: {$authCode}\n";
            echo "Auth Test Response: " . substr($authTest, 0, 200) . "...\n";

            if ($authCode === 200) {
                echo "✅ Token is working! Use this token in Postman:\n";
                echo "   {$responseData['token']}\n";
            } else {
                echo "❌ Token test failed\n";
            }

            break; // Stop after first successful login
        } else {
            echo "❌ Login response missing token\n";
            echo "Response: " . substr($login['response'], 0, 200) . "...\n";
        }
    } else {
        echo "❌ Login failed\n";
        echo "Response: " . substr($login['response'], 0, 200) . "...\n";
        echo "Error: " . ($login['error'] ?: 'None') . "\n";
    }
}

echo "\n\n📋 MANUAL LOGIN INSTRUCTIONS:\n";
echo "===============================\n";
echo "If the automatic login above didn't work, try these steps:\n\n";

echo "1. 📧 Use Postman to login:\n";
echo "   POST http://127.0.0.1:8000/api/v1/login\n";
echo "   Headers: Content-Type: application/json\n";
echo "   Body:\n";
echo "   {\n";
echo "       \"email\": \"your-email@example.com\",\n";
echo "       \"password\": \"your-password\"\n";
echo "   }\n\n";

echo "2. 🔑 Copy the token from the response\n\n";

echo "3. 🧪 Test the token:\n";
echo "   GET http://127.0.0.1:8000/api/v1/auth-test\n";
echo "   Headers: Authorization: Bearer YOUR_TOKEN_HERE\n\n";

echo "4. 🎯 Use the token for withdrawal:\n";
echo "   POST http://127.0.0.1:8000/api/v1/withdrawal/send-verification-code\n";
echo "   Headers:\n";
echo "     Content-Type: application/json\n";
echo "     Authorization: Bearer YOUR_TOKEN_HERE\n";
echo "   Body:\n";
echo "   {\n";
echo "       \"email\": \"your-email@example.com\"\n";
echo "   }\n\n";

echo "🔍 COMMON ISSUES:\n";
echo "=================\n";
echo "• Make sure Laravel is running: php artisan serve\n";
echo "• Check if the email exists in your users table\n";
echo "• Verify the password is correct\n";
echo "• Ensure Sanctum is properly configured\n";
echo "• Check Laravel logs: tail -f storage/logs/laravel.log\n";
