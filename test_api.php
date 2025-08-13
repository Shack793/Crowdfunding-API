<?php

$token = "225|mlPgJsmFP63I89NDdibyTRYcfwqT3GlqtjsLf6ic6e00ff9c";

// Test auth-test endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8001/api/v1/auth-test");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "Accept: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Auth Test Response (HTTP $httpCode):\n";
echo $response . "\n\n";

// Test userdashboard endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8001/api/v1/userdashboard");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "Accept: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "User Dashboard Response (HTTP $httpCode):\n";
echo $response . "\n";
