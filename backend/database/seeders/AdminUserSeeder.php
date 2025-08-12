<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\UserRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(
            ['role_name' => 'admin'],
            [
                'role_id' => 1,
                'description' => 'System Administrator with full access',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create instructor role if it doesn't exist
        $instructorRole = Role::firstOrCreate(
            ['role_name' => 'instructor'],
            [
                'role_id' => 2,
                'description' => 'Course Instructor with teaching access',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create student role if it doesn't exist
        $studentRole = Role::firstOrCreate(
            ['role_name' => 'student'],
            [
                'role_id' => 3,
                'description' => 'Student with learning access',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create admin user if it doesn't exist
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@netflow.bd'],
            [
                'user_id' => '00000000-0000-0000-0000-000000000001',
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'phone' => '+8801234567890',
                'status' => 'active',
                'email_verified' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Assign admin role to admin user
        UserRole::firstOrCreate(
            [
                'user_id' => $adminUser->user_id,
                'role_id' => $adminRole->role_id,
            ],
            [
                'assigned_at' => now(),
                'assigned_by' => $adminUser->user_id,
            ]
        );

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@netflow.bd');
        $this->command->info('Password: admin123');
    }
}
