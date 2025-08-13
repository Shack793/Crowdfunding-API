<?php
/**
 * Final CSRF Test Script
 * Tests API endpoint directly to confirm CSRF bypass is working
 */

// Configuration
$base_url = 'https://admin.myeasydonate.com';
$api_url = $base_url . '/api/campaigns';

echo "=== FINAL CSRF TEST ===\n";
echo "Testing URL: $api_url\n\n";

// Test 1: Basic GET request without any tokens
echo "Test 1: GET request without CSRF token\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'User-Agent: CSRF-Test-Script'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $http_code\n";

if ($http_code == 419) {
    echo "❌ FAIL: Still getting CSRF error (419)\n";
    echo "Response: " . substr($response, 0, 200) . "\n";
} elseif ($http_code == 401) {
    echo "✅ SUCCESS: CSRF bypassed (401 = auth required, not CSRF)\n";
} elseif ($http_code == 200) {
    echo "✅ SUCCESS: CSRF bypassed (200 = no auth required)\n";
} else {
    echo "⚠️  OTHER: Unexpected response code\n";
    echo "Response: " . substr($response, 0, 200) . "\n";
}

echo "\n";

// Test 2: POST request without CSRF token (should not get 419)
echo "Test 2: POST request without CSRF token\n";
$post_data = json_encode(['test' => 'data']);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    'User-Agent: CSRF-Test-Script'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $http_code\n";

if ($http_code == 419) {
    echo "❌ FAIL: Still getting CSRF error (419)\n";
    echo "Response: " . substr($response, 0, 200) . "\n";
} elseif ($http_code == 401) {
    echo "✅ SUCCESS: CSRF bypassed (401 = auth required, not CSRF)\n";
} elseif ($http_code == 405) {
    echo "✅ SUCCESS: CSRF bypassed (405 = method not allowed, not CSRF)\n";
} elseif ($http_code == 422) {
    echo "✅ SUCCESS: CSRF bypassed (422 = validation error, not CSRF)\n";
} else {
    echo "⚠️  OTHER: Unexpected response code\n";
    echo "Response: " . substr($response, 0, 200) . "\n";
}

echo "\n=== CONFIGURATION CHECK ===\n";

// Check current Laravel configuration
if (file_exists('app/Http/Middleware/VerifyCsrfToken.php')) {
    $csrf_content = file_get_contents('app/Http/Middleware/VerifyCsrfToken.php');
    if (strpos($csrf_content, "'api/*'") !== false || strpos($csrf_content, "'*'") !== false) {
        echo "✅ CSRF Middleware: API routes excluded\n";
    } else {
        echo "❌ CSRF Middleware: API routes NOT excluded\n";
    }
} else {
    echo "⚠️  CSRF Middleware: File not found\n";
}

if (file_exists('app/Http/Kernel.php')) {
    $kernel_content = file_get_contents('app/Http/Kernel.php');
    if (strpos($kernel_content, '// \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class') !== false) {
        echo "✅ Kernel: Sanctum stateful middleware commented out\n";
    } elseif (strpos($kernel_content, '\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class') === false) {
        echo "✅ Kernel: Sanctum stateful middleware not present\n";
    } else {
        echo "❌ Kernel: Sanctum stateful middleware is active\n";
    }
} else {
    echo "⚠️  Kernel: File not found\n";
}

echo "\n=== SUMMARY ===\n";
echo "If both tests show SUCCESS (no 419 errors), then CSRF is properly bypassed.\n";
echo "Your frontend should now work without CSRF tokens.\n";
