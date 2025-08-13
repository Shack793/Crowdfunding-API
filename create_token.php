<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
$token = $user->createToken('test-token');

echo "Token: " . $token->plainTextToken . "\n";
echo "User ID: " . $user->id . "\n";
echo "User Name: " . $user->name . "\n";
