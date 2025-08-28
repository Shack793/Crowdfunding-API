<?php

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;

echo "🔍 Laravel Logging Debug Test\n";
echo "===============================\n\n";

// Test different log levels
echo "1. 🧪 Testing different log levels...\n";
echo "=======================================\n";

$logLevels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

foreach ($logLevels as $level) {
    Log::$level("Test message at {$level} level", [
        'timestamp' => now()->toISOString(),
        'test_data' => 'debug_test_' . $level,
        'user_agent' => 'LaravelLoggingTest'
    ]);
    echo "✅ Logged at {$level} level\n";
}

echo "\n2. 🧪 Testing our EmailVerificationController debug logs...\n";
echo "===========================================================\n";

// Simulate the debug logs we added to the controller
Log::info('EmailVerificationController@sendVerificationCode called', [
    'headers' => ['test' => 'header'],
    'bearer_token' => 'test_token_123',
    'has_authorization_header' => true,
    'authorization_header' => 'Bearer test_token_123',
    'user_agent' => 'TestAgent/1.0',
    'ip_address' => '127.0.0.1',
    'all_request_data' => ['email' => 'test@example.com']
]);

Log::info('Authentication check result', [
    'user_found' => true,
    'user_id' => 123,
    'user_email' => 'test@example.com',
    'auth_check' => true,
    'auth_id' => 123,
    'auth_guard' => 'sanctum'
]);

echo "✅ Debug logs written\n";

echo "\n3. 📋 Check the logs\n";
echo "===================\n";
echo "Run this command to see the debug logs:\n";
echo "Get-Content storage/logs/laravel.log -Tail 20\n\n";

echo "You should see entries like:\n";
echo "[2025-08-27 15:00:00] local.INFO: EmailVerificationController@sendVerificationCode called\n";
echo "[2025-08-27 15:00:00] local.INFO: Authentication check result\n\n";

echo "4. 🔍 If you don't see the logs:\n";
echo "=================================\n";
echo "• Check if LOG_LEVEL is set in .env file\n";
echo "• Verify storage/logs directory permissions\n";
echo "• Check if the log file exists: storage/logs/laravel.log\n";
echo "• Try clearing the cache: php artisan config:clear\n\n";

echo "5. 🎯 Test with your actual endpoint:\n";
echo "=====================================\n";
echo "After running this test, try your Postman request again.\n";
echo "Then check the logs with:\n";
echo "Get-Content storage/logs/laravel.log -Tail 10\n\n";

echo "This will show you exactly what headers and authentication\n";
echo "data your request is sending to the server! 🚀\n";
