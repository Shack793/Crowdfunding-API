<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('notifications')->insert([
            [
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\SampleNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => 1,
                'data' => json_encode(['format' => 'fil', 'message' => 'Welcome!']),
                'read_at' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'user_id' => 1,
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\SampleNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => 2,
                'data' => json_encode(['format' => 'fil', 'message' => 'Hello User 2!']),
                'read_at' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'user_id' => 2,
            ],
        ]);
    }
}
