<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== CREATING ADMIN USER FOR FILAMENT TESTING ===\n\n";

// Create or update admin user
$adminEmail = 'admin@admin.com';
$adminPassword = 'password';

$admin = User::where('email', $adminEmail)->first();

if (!$admin) {
    $admin = User::create([
        'name' => 'Super Admin',
        'email' => $adminEmail,
        'password' => Hash::make($adminPassword),
        'email_verified_at' => now(),
    ]);
    echo "✅ Created new admin user\n";
} else {
    $admin->update([
        'password' => Hash::make($adminPassword),
        'email_verified_at' => now(),
    ]);
    echo "✅ Updated existing admin user\n";
}

echo "Admin Credentials:\n";
echo "Email: {$adminEmail}\n";
echo "Password: {$adminPassword}\n\n";

echo "🔗 Access URLs:\n";
echo "Local Development: http://localhost:8000/admin\n";
echo "Live Server: http://admin.myeasydonate.com/admin\n\n";

echo "📊 What to Test:\n";
echo "1. Login with admin credentials\n";
echo "2. Check Dashboard for WithdrawalFeesOverview widget\n";
echo "3. Navigate to Administration → Withdraw Fees\n";
echo "4. Test mobile number verification (use a real number)\n";
echo "5. Process withdrawal (will update fee records)\n\n";

echo "Current Withdrawal Stats:\n";
echo "Available for Withdrawal: GHS " . \App\Models\WithdrawalFee::where('status', 'calculated')->sum('fee_amount') . "\n";
echo "Pending Transactions: " . \App\Models\WithdrawalFee::where('status', 'calculated')->count() . "\n";

echo "\n=== ADMIN USER READY ===\n";
