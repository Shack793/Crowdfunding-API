<?php

echo "Testing Current API Response vs Expected\n";
echo "=======================================\n\n";

// Test the actual API endpoint to see what user is being authenticated
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://admin.myeasydonate.com/api/v1/userdashboard");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    // Add the Authorization header here with your actual token
    // 'Authorization: Bearer YOUR_TOKEN_HERE'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "🔍 ACTUAL API RESPONSE:\n";
echo "=======================\n";
echo "HTTP Code: {$httpCode}\n";

if ($response) {
    $data = json_decode($response, true);
    echo "Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT);
    
    if ($httpCode == 200 && isset($data['user_id'])) {
        echo "\n\n🎯 ANALYSIS:\n";
        echo "============\n";
        echo "Authenticated User ID: " . $data['user_id'] . "\n";
        
        if ($data['user_id'] == 1) {
            echo "✅ Same user as our test (User ID 1)\n";
            echo "❌ ISSUE: API is returning zeros but database has data\n";
            echo "   This suggests a code deployment issue or different environment\n";
        } else {
            echo "❌ DIFFERENT USER: API is using User ID " . $data['user_id'] . " (not User ID 1)\n";
            echo "   This explains why the response is different\n";
        }
        
        echo "\nExpected vs Actual:\n";
        echo "- Expected totalContributions: 205,360.00\n";
        echo "- Actual totalContributions: " . ($data['totalContributions'] ?? 'N/A') . "\n";
        echo "- Expected totalCampaigns: 7\n";
        echo "- Actual totalCampaigns: " . ($data['totalCampaigns'] ?? 'N/A') . "\n";
    }
} else {
    echo "No response received\n";
}

echo "\n\n📋 POSSIBLE ISSUES:\n";
echo "==================\n";
echo "1. 🔑 Authentication: Make sure you're using a valid Bearer token\n";
echo "2. 👤 Different User: The authenticated user might not be User ID 1\n";
echo "3. 🌍 Environment: You might be testing local vs production\n";
echo "4. 🔄 Deployment: Code changes might not be deployed to production\n";
echo "5. 💾 Caching: API responses might be cached\n";

echo "\n\n✅ SOLUTIONS:\n";
echo "=============\n";
echo "1. Deploy the updated UserDashboardController.php to production\n";
echo "2. Clear any API response caches\n";
echo "3. Test with the correct authentication token\n";
echo "4. Verify which user is actually authenticated\n";
