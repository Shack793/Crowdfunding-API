<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Create or update admin user
$admin = User::updateOrCreate(
    ['email' => 'admin@admin.com'],
    [
        'name' => 'Admin',
        'password' => Hash::make('password'),
        'role' => 'admin',
        'email_verified' => true,
        'phone' => '+1234567890',
        'country' => 'US',
    ]
);

echo "✓ Admin user created/updated: {$admin->email} (ID: {$admin->id})\n";
echo "✓ Role: {$admin->role}\n";
echo "✓ Email verified: " . ($admin->email_verified ? 'Yes' : 'No') . "\n";
echo "\nLogin credentials:\n";
echo "Email: admin@admin.com\n";
echo "Password: password\n";
echo "\nTry accessing: http://localhost:8000/admin/login\n";
