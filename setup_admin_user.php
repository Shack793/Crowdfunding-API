<?php

use Illuminate\Support\Facades\Artisan;

Artisan::call('tinker', [], [
    '--execute' => 'use App\Models\User; use Illuminate\Support\Facades\Hash; $admin = User::firstOrCreate([\'email\' => \'admin@admin.com\'], [\'name\' => \'Admin\', \'password\' => Hash::make(\'password\'), \'role\' => \'admin\', \'email_verified\' => true]); echo "Admin user: " . $admin->email . " (ID: " . $admin->id . ")";'
]);

echo "Admin user setup complete!\n";
echo "Try accessing: http://localhost:8000/admin/login\n";
echo "Email: admin@admin.com\n";
echo "Password: password\n";
