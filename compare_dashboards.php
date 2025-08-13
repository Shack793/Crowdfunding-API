<?php

$token = "225|mlPgJsmFP63I89NDdibyTRYcfwqT3GlqtjsLf6ic6e00ff9c";

echo "=== TESTING BOTH DASHBOARD ENDPOINTS ===\n\n";

// Test dashboard/stats endpoint (working one)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8001/api/v1/dashboard/stats");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "Accept: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "1. DASHBOARD/STATS Response (HTTP $httpCode):\n";
if ($httpCode == 200) {
    $data = json_decode($response, true);
    echo "✅ Success! User-specific data:\n";
    echo "- Active Campaigns: " . ($data['data']['stats']['activeCampaigns'] ?? 'N/A') . "\n";
    echo "- Total Donations: " . ($data['data']['stats']['totalDonations'] ?? 'N/A') . "\n";
    echo "- Withdrawals: " . ($data['data']['stats']['withdrawals'] ?? 'N/A') . "\n";
} else {
    echo "❌ Error: $response\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test userdashboard endpoint (problematic one)
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

echo "2. USERDASHBOARD Response (HTTP $httpCode):\n";
if ($httpCode == 200) {
    $data = json_decode($response, true);
    echo "✅ Success! User-specific data:\n";
    echo "- User ID: " . ($data['user_id'] ?? 'N/A') . "\n";
    echo "- Total Campaigns: " . ($data['totalCampaigns'] ?? 'N/A') . "\n";
    echo "- Total Contributions: " . ($data['totalContributions'] ?? 'N/A') . "\n";
    echo "- Withdrawals: " . ($data['withdrawals'] ?? 'N/A') . "\n";
} else {
    echo "❌ Error: $response\n";
}
