<?php
// database/seeders/RoleSeeder.php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => Role::ADMIN,
                'display_name' => 'Administrator',
                'description' => 'System administrator with full access'
            ],
            [
                'name' => Role::VENDOR,
                'display_name' => 'Vendor',
                'description' => 'Vendor who can sell products on the platform'
            ],
            [
                'name' => Role::CUSTOMER,
                'display_name' => 'Customer',
                'description' => 'Customer who can browse and purchase products'
            ]
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}