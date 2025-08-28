<?php

/**
 * Live Email Test Script
 * Tests the SMTP configuration with your actual email settings
 */

echo "📧 Live Email Test - WGCrowdfunding\n";
echo str_repeat("=", 50) . "\n\n";

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "🔧 Current Email Configuration:\n";
    echo "   MAIL_MAILER: " . config('mail.default') . "\n";
    echo "   MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
    echo "   MAIL_PORT: " . config('mail.mailers.smtp.port') . "\n";
    echo "   MAIL_USERNAME: " . config('mail.mailers.smtp.username') . "\n";
    echo "   MAIL_ENCRYPTION: " . config('mail.mailers.smtp.encryption') . "\n";
    echo "   MAIL_FROM_ADDRESS: " . config('mail.from.address') . "\n\n";

    echo "⚠️  IMPORTANT: Make sure to update MAIL_PASSWORD in .env file!\n";
    echo "   Replace 'your_email_password_here' with your actual password for admin@myeasydonate.com\n\n";

    $password = config('mail.mailers.smtp.password');
    if ($password === 'your_email_password_here' || empty($password)) {
        echo "❌ ERROR: Email password not configured!\n";
        echo "   Please update MAIL_PASSWORD in your .env file with the actual password for admin@myeasydonate.com\n";
        echo "   Then run this script again.\n";
        exit(1);
    }

    echo "🧪 Testing email configuration...\n\n";

    // Test 1: Send a simple test email
    echo "1. 📨 Sending test email to shadrackacquah793@gmail.com...\n";
    
    $testEmailSent = \Illuminate\Support\Facades\Mail::send([], [], function ($message) {
        $message->to('shadrackacquah793@gmail.com')
                ->subject('🧪 Test Email from WGCrowdfunding')
                ->html('
                <h2>✅ Email Configuration Test Successful!</h2>
                <p>This is a test email from your WGCrowdfunding application.</p>
                <p><strong>SMTP Server:</strong> myeasydonate.com</p>
                <p><strong>From:</strong> admin@myeasydonate.com</p>
                <p><strong>Date:</strong> ' . now()->format('Y-m-d H:i:s') . '</p>
                <p>If you received this email, your SMTP configuration is working correctly! 🎉</p>
                ');
    });

    echo "   ✅ Test email sent successfully!\n\n";

    // Test 2: Send a withdrawal verification code email
    echo "2. 🔐 Testing withdrawal verification email...\n";
    
    // Get or create user for testing
    $user = App\Models\User::where('email', 'shadrackacquah793@gmail.com')->first();
    
    if (!$user) {
        $user = App\Models\User::firstOrCreate(
            ['email' => 'shadrackacquah793@gmail.com'],
            [
                'name' => 'Shadrack Acquah',
                'password' => bcrypt('password123'),
                'email_verified_at' => now()
            ]
        );
        echo "   👤 Created test user account\n";
    }

    // Create verification code
    $verificationRecord = App\Models\WithdrawalVerificationCode::createForUser($user, '127.0.0.1', 'EmailTestScript');
    
    // Send the actual withdrawal verification notification
    $user->notify(new App\Notifications\WithdrawalEmailVerification($verificationRecord->code, 15));
    
    echo "   ✅ Withdrawal verification email sent!\n";
    echo "   🔑 Verification Code: " . $verificationRecord->code . "\n";
    echo "   ⏰ Expires in: 15 minutes\n\n";

    echo "🎉 Email Test Results:\n";
    echo str_repeat("-", 30) . "\n";
    echo "✅ SMTP connection successful\n";
    echo "✅ Test email sent to shadrackacquah793@gmail.com\n";
    echo "✅ Withdrawal verification email sent\n";
    echo "✅ Verification code: " . $verificationRecord->code . "\n\n";

    echo "📱 Check your email at shadrackacquah793@gmail.com\n";
    echo "📧 You should receive both:\n";
    echo "   1. Test email with configuration details\n";
    echo "   2. Withdrawal verification code email\n\n";

    echo "🔧 Next Steps:\n";
    echo "   1. Check your email inbox (and spam folder)\n";
    echo "   2. Use verification code: " . $verificationRecord->code . "\n";
    echo "   3. Test the API endpoint with the code\n\n";

    echo "🚀 Your email system is now live and working!\n";

} catch (Exception $e) {
    echo "❌ Email Test Failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "🔧 Troubleshooting Tips:\n";
    echo "1. Verify your email password in .env file\n";
    echo "2. Check if admin@myeasydonate.com exists and is active\n";
    echo "3. Ensure your server can connect to myeasydonate.com on port 465\n";
    echo "4. Check if your hosting provider blocks outgoing SMTP\n\n";
    
    echo "📋 Current Configuration:\n";
    echo "   Host: " . config('mail.mailers.smtp.host') . "\n";
    echo "   Port: " . config('mail.mailers.smtp.port') . "\n";
    echo "   Username: " . config('mail.mailers.smtp.username') . "\n";
    echo "   Encryption: " . config('mail.mailers.smtp.encryption') . "\n";
}

echo "\n🏁 Email test completed.\n";
