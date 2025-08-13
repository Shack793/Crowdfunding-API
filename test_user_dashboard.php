<?php

echo "Testing User Dashboard API Endpoint\n";
echo "===================================\n\n";

// Test userdashboard endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://admin.myeasydonate.com/api/v1/userdashboard");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    // You'll need to add the Bearer token here for authentication
    // 'Authorization: Bearer YOUR_TOKEN_HERE'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "User Dashboard Response (HTTP $httpCode):\n";
echo str_repeat("-", 50) . "\n";
echo $response ? json_encode(json_decode($response), JSON_PRETTY_PRINT) : "No response";
echo "\n\n";

echo "Note: You need to add a valid Bearer token to the Authorization header for authentication.\n";
echo "The endpoint now returns user-specific data including:\n";
echo "- User's own campaigns count\n";
echo "- Total contributions received on user's campaigns\n";
echo "- User's withdrawals\n";
echo "- User's wallet statistics\n";
echo "- User's withdrawal history\n";
echo "- Recent contributions to user's campaigns\n";
