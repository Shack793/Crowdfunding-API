<?php

/**
 * Generate a Fresh Authentication Token
 * This script helps you get a new token for testing
 */

echo "🔑 Authentication Token Generator\n";
echo str_repeat("=", 40) . "\n\n";

// You can either:
// 1. Use this script to create a test user and token
// 2. Or use the login endpoint with Postman

echo "Option 1: Create a test user and token programmatically\n";
echo str_repeat("-", 50) . "\n";

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Create or find a test user
    $user = App\Models\User::firstOrCreate(
        ['email' => 'shadrackacquah793@gmail.com'],
        [
            'name' => 'Shadrack Acquah',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]
    );

    // Create a new token
    $token = $user->createToken('test-token')->plainTextToken;

    echo "✅ User created/found:\n";
    echo "   Email: {$user->email}\n";
    echo "   ID: {$user->id}\n\n";
    
    echo "✅ Fresh token generated:\n";
    echo "   Token: {$token}\n\n";
    
    echo "🚀 Use this token in Postman:\n";
    echo "   Authorization: Bearer {$token}\n\n";

    echo "📝 Test with curl:\n";
    echo "curl -X POST http://127.0.0.1:8000/api/v1/withdrawal/send-verification-code \\\n";
    echo "  -H \"Content-Type: application/json\" \\\n";
    echo "  -H \"Accept: application/json\" \\\n";
    echo "  -H \"Authorization: Bearer {$token}\" \\\n";
    echo "  -d '{\"email\": \"shadrackacquah793@gmail.com\"}'\n\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nOption 2: Use the login endpoint instead\n";
    echo str_repeat("-", 40) . "\n";
    echo "POST http://127.0.0.1:8000/api/v1/login\n";
    echo "Body: {\n";
    echo "  \"email\": \"shadrackacquah793@gmail.com\",\n";
    echo "  \"password\": \"your_password\"\n";
    echo "}\n";
}

echo "\n🎯 Once you have a valid token, your withdrawal endpoint should work!\n";
