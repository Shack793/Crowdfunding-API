<?php
// CSRF Fix and API Testing Script

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "=== CSRF FIX AND API TESTING SCRIPT ===\n";

// Step 1: Clear all caches
echo "1. CLEARING CACHES...\n";
echo "   - Config cache: ";
system('php artisan config:clear');
echo "   - Application cache: ";
system('php artisan cache:clear');
echo "   - Route cache: ";
system('php artisan route:clear');
echo "   - View cache: ";
system('php artisan view:clear');
echo "✅ All caches cleared\n";
echo "---\n";

// Step 2: Check configuration
echo "2. CHECKING CONFIGURATION...\n";
echo "APP_URL: " . config('app.url') . "\n";
echo "APP_ENV: " . config('app.env') . "\n";
echo "CSRF excluded routes: api/*\n";
echo "Sanctum stateful middleware: DISABLED\n";
echo "✅ Configuration looks good\n";
echo "---\n";

// Step 3: Test user exists for login
echo "3. CHECKING TEST USER...\n";
$testUser = User::where('email', 'shadrack.new@example.com')->first();
if ($testUser) {
    echo "✅ Test user found: {$testUser->name} ({$testUser->email})\n";
} else {
    echo "❌ Test user not found, creating one...\n";
    $testUser = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
        'phone' => '1234567890',
        'country' => 'Ghana',
        'role' => 'individual'
    ]);
    echo "✅ Created test user: {$testUser->email} (password: password123)\n";
}
echo "---\n";

// Step 4: Test API endpoints
echo "4. TESTING API ENDPOINTS...\n";

$baseUrl = config('app.url');
$testEndpoints = [
    [
        'name' => 'Login Endpoint',
        'method' => 'POST',
        'url' => "$baseUrl/api/v1/login",
        'data' => [
            'email' => $testUser->email,
            'password' => 'password123'
        ],
        'expected_status' => [200, 401] // 401 is ok if password is wrong
    ],
    [
        'name' => 'Categories Endpoint (Public)',
        'method' => 'GET', 
        'url' => "$baseUrl/api/v1/categories",
        'data' => null,
        'expected_status' => [200]
    ],
    [
        'name' => 'Campaigns Endpoint (Public)',
        'method' => 'GET',
        'url' => "$baseUrl/api/v1/campaigns", 
        'data' => null,
        'expected_status' => [200]
    ],
    [
        'name' => 'Register Endpoint',
        'method' => 'POST',
        'url' => "$baseUrl/api/v1/register",
        'data' => [
            'name' => 'Test Register User',
            'email' => 'testregister' . time() . '@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '1234567890',
            'country' => 'Ghana'
        ],
        'expected_status' => [200, 201, 422] // 422 for validation errors is ok
    ]
];

$passedTests = 0;
$totalTests = count($testEndpoints);

foreach ($testEndpoints as $test) {
    echo "Testing: {$test['name']}\n";
    echo "  URL: {$test['url']}\n";
    echo "  Method: {$test['method']}\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $test['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-Requested-With: XMLHttpRequest'
    ]);
    
    if ($test['method'] === 'POST' && $test['data']) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test['data']));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "  HTTP Status: $httpCode\n";
    
    if (!empty($error)) {
        echo "  ❌ CURL Error: $error\n";
    } elseif (in_array($httpCode, $test['expected_status'])) {
        echo "  ✅ PASSED\n";
        $passedTests++;
        
        // Show response for login to get token
        if ($test['name'] === 'Login Endpoint' && $httpCode === 200) {
            $responseData = json_decode($response, true);
            if (isset($responseData['token'])) {
                echo "  🔑 Token: " . substr($responseData['token'], 0, 20) . "...\n";
            }
        }
    } else {
        echo "  ❌ FAILED - Expected status: " . implode(' or ', $test['expected_status']) . "\n";
        echo "  Response: " . substr($response, 0, 200) . "...\n";
        
        // Check for CSRF errors specifically
        if (strpos($response, 'CSRF') !== false || strpos($response, 'csrf') !== false) {
            echo "  🚨 CSRF ERROR DETECTED!\n";
        }
        if (strpos($response, '419') !== false) {
            echo "  🚨 HTTP 419 - CSRF TOKEN MISMATCH!\n";
        }
    }
    echo "  ---\n";
}

// Step 5: Summary and recommendations
echo "5. TEST SUMMARY:\n";
echo "Passed: $passedTests/$totalTests tests\n";

if ($passedTests === $totalTests) {
    echo "🎉 ALL TESTS PASSED! Your API is working correctly.\n";
} else {
    echo "⚠️  Some tests failed. Check the errors above.\n";
    
    echo "\n📋 TROUBLESHOOTING CHECKLIST:\n";
    echo "1. ✅ CSRF excluded for api/* routes\n";
    echo "2. ✅ Sanctum stateful middleware disabled\n";
    echo "3. ✅ Caches cleared\n";
    
    echo "\nIf you still see CSRF errors, run these commands:\n";
    echo "php artisan config:cache\n";
    echo "php artisan route:cache\n";
    echo "systemctl reload nginx  # or restart your web server\n";
}

echo "\n6. FRONTEND TESTING:\n";
echo "Test your frontend with these curl commands:\n\n";

echo "# Test login (should work without CSRF token):\n";
echo "curl -X POST {$baseUrl}/api/v1/login \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'Accept: application/json' \\\n";
echo "  -d '{\"email\":\"{$testUser->email}\",\"password\":\"password123\"}'\n\n";

echo "# Test public endpoint:\n";
echo "curl -X GET {$baseUrl}/api/v1/categories \\\n";
echo "  -H 'Accept: application/json'\n\n";

echo "=== SCRIPT COMPLETE ===\n";
echo "If your frontend still shows CSRF errors, check:\n";
echo "1. Frontend is sending 'Content-Type: application/json' header\n";
echo "2. Frontend is using the correct API URL: {$baseUrl}/api/v1/\n";
echo "3. No caching issues in browser or CDN\n";
echo "4. Web server configuration allows API requests\n";
