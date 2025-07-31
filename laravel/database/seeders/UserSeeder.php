<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a Super Admin
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );

        // Create a Supervisor
        User::firstOrCreate(
            ['email' => 'supervisor@example.com'],
            [
                'name' => 'Supervisor User',
                'password' => Hash::make('password'),
                'role' => 'supervisor',
                'is_active' => true,
            ]
        );

        // Create a Caregiver
        User::firstOrCreate(
            ['email' => 'caregiver@example.com'],
            [
                'name' => 'Caregiver User',
                'password' => Hash::make('password'),
                'role' => 'caregiver',
                'supervisor_id' => User::where('email', 'supervisor@example.com')->first()->id ?? null,
                'is_active' => true,
            ]
        );

        echo "Default users created: admin@example.com, supervisor@example.com, caregiver@example.com (password: password)\n";
    }
}
