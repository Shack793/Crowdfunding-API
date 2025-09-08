<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->boot();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== Admin Access Check ===\n";

// Check if admin user exists
$admin = User::where('email', 'admin@admin.com')->first();
if ($admin) {
    echo "✓ Admin user exists: {$admin->email}\n";
    echo "✓ User ID: {$admin->id}\n";
    echo "✓ User Name: {$admin->name}\n";
    echo "✓ Created: {$admin->created_at}\n";
    
    // Check password
    if (Hash::check('password', $admin->password)) {
        echo "✓ Password is correct\n";
    } else {
        echo "✗ Password is incorrect\n";
    }
} else {
    echo "✗ Admin user does not exist\n";
    
    // Create admin user
    echo "Creating admin user...\n";
    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@admin.com',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);
    echo "✓ Admin user created: {$admin->email}\n";
}

// Check database connection
try {
    $count = User::count();
    echo "✓ Database connection OK, total users: {$count}\n";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

// Check if the guard is configured properly
$guards = config('auth.guards');
echo "✓ Auth guards: " . implode(', ', array_keys($guards)) . "\n";

$defaultGuard = config('auth.defaults.guard');
echo "✓ Default guard: {$defaultGuard}\n";

// Check filament auth
$filamentAuth = config('filament.default_filesystem_disk');
echo "✓ Filament config loaded\n";

echo "\n=== Test Complete ===\n";
echo "Try accessing: http://localhost:8000/admin/login\n";
echo "Credentials: admin@admin.com / password\n";
