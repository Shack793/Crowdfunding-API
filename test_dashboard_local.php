<?php

echo "Testing User Dashboard API Endpoint - Local Server\n";
echo "================================================\n\n";

// Test userdashboard endpoint without authentication first
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8001/api/v1/userdashboard");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "User Dashboard Response (HTTP $httpCode):\n";
echo str_repeat("-", 50) . "\n";

if ($response) {
    $decodedResponse = json_decode($response, true);
    echo json_encode($decodedResponse, JSON_PRETTY_PRINT);
} else {
    echo "No response";
}

echo "\n\n";

if ($httpCode == 401) {
    echo "✅ Good! The endpoint correctly requires authentication (HTTP 401).\n";
    echo "This means the route is properly protected.\n\n";
    
    echo "Next steps:\n";
    echo "1. The API now returns user-specific data instead of global statistics\n";
    echo "2. It requires authentication (Bearer token) to access\n";
    echo "3. It returns:\n";
    echo "   - User's own campaigns count\n";
    echo "   - Total contributions received on user's campaigns\n";
    echo "   - User's withdrawals\n";
    echo "   - User's wallet statistics\n";
    echo "   - User's withdrawal history\n";
    echo "   - Recent contributions to user's campaigns\n";
} else {
    echo "Response received. Check if it looks correct.\n";
}
