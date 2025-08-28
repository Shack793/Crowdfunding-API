<?php

/**
 * Test Authentication Error Responses
 * This script tests the improved authentication error handling
 */

echo "🔍 Testing Authentication Error Responses\n";
echo str_repeat("=", 50) . "\n\n";

// Test 1: No token provided
echo "1. 🧪 Testing request with NO token\n";
echo str_repeat("-", 30) . "\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/api/v1/withdrawal/send-verification-code');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'test@example.com']));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response1 = curl_exec($ch);
$httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Status: $httpCode1\n";
echo "Response:\n";
echo json_encode(json_decode($response1), JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Invalid token provided
echo "2. 🧪 Testing request with INVALID token\n";
echo str_repeat("-", 30) . "\n";

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer invalid_token_12345'
]);

$response2 = curl_exec($ch);
$httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Status: $httpCode2\n";
echo "Response:\n";
echo json_encode(json_decode($response2), JSON_PRETTY_PRINT) . "\n\n";

// Test 3: Malformed Authorization header
echo "3. 🧪 Testing request with MALFORMED auth header\n";
echo str_repeat("-", 30) . "\n";

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: NotBearer invalid_format'
]);

$response3 = curl_exec($ch);
$httpCode3 = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Status: $httpCode3\n";
echo "Response:\n";
echo json_encode(json_decode($response3), JSON_PRETTY_PRINT) . "\n\n";

curl_close($ch);

echo "✅ Authentication error testing completed!\n";
echo "\n📋 Summary:\n";
echo "- Test 1 (No token): HTTP $httpCode1\n";
echo "- Test 2 (Invalid token): HTTP $httpCode2\n";
echo "- Test 3 (Malformed header): HTTP $httpCode3\n";
echo "\n🔍 Check the logs for detailed debugging info:\n";
echo "Get-Content storage/logs/laravel.log -Tail 20\n";
