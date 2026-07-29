<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CentralUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@usdeveloper.com.br',
                'password' => Hash::make('password'),
                'role' => UserRole::SuperAdmin,
            ],
            [
                'name' => 'Central Admin',
                'email' => 'admin@central.com',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
            ],
            [
                'name' => 'Central Manager',
                'email' => 'manager@central.com',
                'password' => Hash::make('password'),
                'role' => UserRole::Manager,
            ],
            [
                'name' => 'Central Operator',
                'email' => 'operator@central.com',
                'password' => Hash::make('password'),
                'role' => UserRole::Operator,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
