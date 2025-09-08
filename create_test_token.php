<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

// Get first user
$user = User::first();

if ($user) {
    // Create token
    $token = $user->createToken('test-token')->plainTextToken;
    echo "User ID: " . $user->id . "\n";
    echo "User Email: " . $user->email . "\n";
    echo "Token: " . $token . "\n";
    
    // Check if user has wallet
    $wallet = $user->wallet;
    if ($wallet) {
        echo "Wallet Balance: " . $wallet->balance . "\n";
        echo "Wallet Currency: " . $wallet->currency . "\n";
    } else {
        echo "Creating wallet for user...\n";
        $wallet = \App\Models\Wallet::create([
            'user_id' => $user->id,
            'balance' => 1000.00, // Starting balance for testing
            'currency' => 'GHS'
        ]);
        echo "Wallet created with balance: " . $wallet->balance . "\n";
    }
} else {
    echo "No users found\n";
}
