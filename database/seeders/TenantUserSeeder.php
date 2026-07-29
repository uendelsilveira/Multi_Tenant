<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\TenantUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantUserSeeder extends Seeder
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
                'name' => 'Tenant Admin',
                'email' => 'admin@tenant.com',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
            ],
            [
                'name' => 'Tenant Manager',
                'email' => 'manager@tenant.com',
                'password' => Hash::make('password'),
                'role' => UserRole::Manager,
            ],
            [
                'name' => 'Tenant Operator',
                'email' => 'operator@tenant.com',
                'password' => Hash::make('password'),
                'role' => UserRole::Operator,
            ],
        ];

        foreach ($users as $userData) {
            TenantUser::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
