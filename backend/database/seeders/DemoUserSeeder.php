<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a demo user for testing the login
        User::create([
            'user_id' => Str::uuid(),
            'email' => 'demo@netflow.bd',
            'username' => 'demo',
            'password' => Hash::make('password'),
            'first_name' => 'Demo',
            'last_name' => 'User',
            'phone' => '+8801234567890',
            'status' => 'active',
            'timezone' => 'Asia/Dhaka',
            'language' => 'en',
            'email_verified' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create an admin user
        User::create([
            'user_id' => Str::uuid(),
            'email' => 'admin@netflow.bd',
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'first_name' => 'Admin',
            'last_name' => 'User',
            'phone' => '+8801234567891',
            'status' => 'active',
            'timezone' => 'Asia/Dhaka',
            'language' => 'en',
            'email_verified' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
