<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->ask('Enter admin name', 'Admin');
        $email = $this->ask('Enter admin email', 'admin@example.com');
        $password = $this->secret('Enter admin password (min: 8 characters)');

        // Validate password length
        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters long!');
            return 1;
        }

        // Create the admin user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->info('Admin user created successfully!');
        $this->info('Email: ' . $email);
        $this->warn('Please change the password after first login!');

        return 0;
    }
}
