<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', Role::ADMIN)->first();
        $vendorRole = Role::where('name', Role::VENDOR)->first();
        $customerRole = Role::where('name', Role::CUSTOMER)->first();

        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create Vendor Users
        $vendor1 = User::firstOrCreate(
            ['email' => 'vendor1@example.com'],
            [
                'name' => 'John Vendor',
                'password' => Hash::make('password'),
                'role_id' => $vendorRole->id,
                'is_seller' => true,
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        $vendor2 = User::firstOrCreate(
            ['email' => 'vendor2@example.com'],
            [
                'name' => 'Jane Seller',
                'password' => Hash::make('password'),
                'role_id' => $vendorRole->id,
                'is_seller' => true,
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create Vendor Profiles
        VendorProfile::firstOrCreate(
            ['user_id' => $vendor1->id],
            [
                'business_name' => 'John\'s Electronics Store',
                'business_description' => 'We sell quality electronics and gadgets.',
                'business_address' => '123 Main St, City, Country',
                'business_phone' => '+1234567890',
                'business_email' => 'business@johnelectronics.com',
                'status' => 'approved',
                'approved_at' => now(),
            ]
        );

        VendorProfile::firstOrCreate(
            ['user_id' => $vendor2->id],
            [
                'business_name' => 'Jane\'s Fashion Boutique',
                'business_description' => 'Trendy fashion for modern women.',
                'business_address' => '456 Fashion Ave, City, Country',
                'business_phone' => '+1234567891',
                'business_email' => 'contact@janefashion.com',
                'status' => 'approved',
                'approved_at' => now(),
            ]
        );

        // Create Customer Users
        User::firstOrCreate(
            ['email' => 'customer1@example.com'],
            [
                'name' => 'Alice Customer',
                'password' => Hash::make('password'),
                'role_id' => $customerRole->id,
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'customer2@example.com'],
            [
                'name' => 'Bob Buyer',
                'password' => Hash::make('password'),
                'role_id' => $customerRole->id,
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}