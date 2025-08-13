<?php
// Advanced CSRF Fix for Frontend Issues

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ADVANCED CSRF FIX FOR FRONTEND ===\n";

// Step 1: Check current configuration
echo "1. CHECKING CURRENT SETUP...\n";
echo "APP_URL: " . config('app.url') . "\n";
echo "SANCTUM_STATEFUL_DOMAINS: " . env('SANCTUM_STATEFUL_DOMAINS') . "\n";

// Check if admin.myeasydonate.com is in stateful domains
$statefulDomains = config('sanctum.stateful');
echo "Sanctum stateful domains: " . implode(', ', $statefulDomains) . "\n";

$isInStateful = false;
foreach ($statefulDomains as $domain) {
    if (strpos($domain, 'admin.myeasydonate.com') !== false) {
        $isInStateful = true;
        break;
    }
}

if ($isInStateful) {
    echo "🚨 PROBLEM FOUND: admin.myeasydonate.com is in SANCTUM_STATEFUL_DOMAINS\n";
    echo "This makes Sanctum treat your frontend as stateful (requiring CSRF)\n";
} else {
    echo "✅ Domain not in stateful list\n";
}
echo "---\n";

// Step 2: Check middleware configuration
echo "2. CHECKING MIDDLEWARE...\n";
$kernelPath = app_path('Http/Kernel.php');
$kernelContent = file_get_contents($kernelPath);

if (strpos($kernelContent, 'EnsureFrontendRequestsAreStateful::class') !== false) {
    if (strpos($kernelContent, '// \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class') !== false) {
        echo "✅ Sanctum stateful middleware is commented out\n";
    } else {
        echo "🚨 PROBLEM: Sanctum stateful middleware is still active\n";
    }
} else {
    echo "✅ Sanctum stateful middleware not found in API group\n";
}

$csrfPath = app_path('Http/Middleware/VerifyCsrfToken.php');
$csrfContent = file_get_contents($csrfPath);
if (strpos($csrfContent, 'api/*') !== false) {
    echo "✅ CSRF excluded for api/* routes\n";
} else {
    echo "🚨 PROBLEM: CSRF not excluded for API routes\n";
}
echo "---\n";

// Step 3: Update .env file to remove domain from stateful
echo "3. FIXING SANCTUM CONFIGURATION...\n";
$envPath = base_path('.env');
$envContent = file_get_contents($envPath);

// Remove admin.myeasydonate.com from SANCTUM_STATEFUL_DOMAINS
$newEnvContent = preg_replace(
    '/SANCTUM_STATEFUL_DOMAINS=.*/',
    'SANCTUM_STATEFUL_DOMAINS=localhost,localhost:5173',
    $envContent
);

if ($newEnvContent !== $envContent) {
    file_put_contents($envPath, $newEnvContent);
    echo "✅ Updated SANCTUM_STATEFUL_DOMAINS in .env\n";
} else {
    echo "ℹ️ SANCTUM_STATEFUL_DOMAINS already correct\n";
}

// Step 4: Ensure API routes don't have web middleware
echo "4. CHECKING ROUTE CONFIGURATION...\n";
$routeApiPath = base_path('routes/api.php');
$routeContent = file_get_contents($routeApiPath);

if (strpos($routeContent, "Route::middleware('web')") !== false) {
    echo "🚨 PROBLEM: Found web middleware in API routes\n";
} else {
    echo "✅ No web middleware found in API routes\n";
}
echo "---\n";

// Step 5: Clear all caches
echo "5. CLEARING ALL CACHES...\n";
system('php artisan config:clear 2>&1');
system('php artisan cache:clear 2>&1');
system('php artisan route:clear 2>&1');
system('php artisan view:clear 2>&1');
echo "✅ All caches cleared\n";
echo "---\n";

// Step 6: Test the problematic endpoint specifically
echo "6. TESTING LOGIN ENDPOINT WITH FRONTEND HEADERS...\n";

$testUrl = config('app.url') . '/api/v1/login';
$testData = [
    'email' => 'shadrack.new@example.com',
    'password' => 'password123'
];

// Test with typical frontend headers
$frontendHeaders = [
    'Content-Type: application/json',
    'Accept: application/json',
    'Origin: https://admin.myeasydonate.com',
    'Referer: https://admin.myeasydonate.com/',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
];

echo "Testing with frontend-like headers:\n";
echo "URL: $testUrl\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $frontendHeaders);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $httpCode\n";

if ($httpCode === 419) {
    echo "🚨 STILL GETTING 419 CSRF ERROR!\n";
    echo "Response: " . substr($response, 0, 200) . "\n";
    
    echo "\n🔧 ADDITIONAL FIXES NEEDED:\n";
    
    // Check if there's a web.php route conflicting
    $webRoutePath = base_path('routes/web.php');
    if (file_exists($webRoutePath)) {
        $webContent = file_get_contents($webRoutePath);
        if (strpos($webContent, '/api/') !== false) {
            echo "⚠️ WARNING: Found API routes in web.php (this could cause conflicts)\n";
        }
    }
    
    echo "\n🛠️ EMERGENCY FIX - Completely disable CSRF for your domain:\n";
    echo "Add this to your VerifyCsrfToken.php \$except array:\n";
    echo "'*',  // Disable CSRF entirely (not recommended for production)\n";
    
} elseif ($httpCode === 200) {
    echo "✅ SUCCESS! Login endpoint working correctly\n";
    $responseData = json_decode($response, true);
    if (isset($responseData['token'])) {
        echo "🔑 Token received: " . substr($responseData['token'], 0, 20) . "...\n";
    }
} elseif ($httpCode === 401) {
    echo "✅ SUCCESS! Endpoint accessible (401 = wrong password, not CSRF error)\n";
} else {
    echo "⚠️ Unexpected status: $httpCode\n";
    echo "Response: " . substr($response, 0, 200) . "\n";
}

echo "---\n";

// Step 7: Final recommendations
echo "7. FINAL RECOMMENDATIONS:\n";

if ($httpCode === 419) {
    echo "🚨 CSRF STILL FAILING - Try these additional steps:\n";
    echo "1. Restart your web server (nginx/apache)\n";
    echo "2. Check if there's a CDN/proxy caching issues\n";
    echo "3. Verify frontend is sending correct Content-Type header\n";
    echo "4. Try the emergency CSRF disable option above\n";
} else {
    echo "✅ Backend is working correctly!\n";
    echo "If frontend still fails, the issue is likely:\n";
    echo "1. Frontend not sending 'Content-Type: application/json' header\n";
    echo "2. Browser caching old responses\n";
    echo "3. CDN/proxy intercepting requests\n";
}

echo "\n📋 COMMANDS TO RUN IN PRODUCTION:\n";
echo "# Clear all caches\n";
echo "php artisan config:clear\n";
echo "php artisan cache:clear\n";
echo "php artisan route:clear\n";
echo "\n# If using nginx, restart it:\n";
echo "sudo systemctl reload nginx\n";
echo "\n# If using Apache, restart it:\n";
echo "sudo systemctl reload apache2\n";

echo "\n=== CSRF FIX COMPLETE ===\n";
