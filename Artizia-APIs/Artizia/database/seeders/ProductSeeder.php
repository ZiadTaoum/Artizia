<?php
// database/seeders/ProductSeeder.php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = User::byRole(Role::VENDOR)->get();
        $categories = Category::whereNotNull('parent_id')->get(); // Get subcategories

        if ($vendors->isEmpty() || $categories->isEmpty()) {
            return;
        }

        $products = [
            [
                'name' => 'iPhone 14 Pro',
                'description' => 'Latest iPhone with advanced camera system and A16 Bionic chip.',
                'short_description' => 'Premium smartphone with exceptional performance.',
                'price' => 999.99,
                'compare_price' => 1099.99,
                'sku' => 'IPHONE14PRO',
                'inventory_quantity' => 50,
                'category_name' => 'Smartphones',
            ],
            [
                'name' => 'MacBook Pro 16"',
                'description' => 'Powerful laptop for professionals with M2 Pro chip.',
                'short_description' => 'High-performance laptop for creative work.',
                'price' => 2499.99,
                'compare_price' => 2699.99,
                'sku' => 'MBP16M2',
                'inventory_quantity' => 25,
                'category_name' => 'Laptops',
            ],
            [
                'name' => 'Designer Dress',
                'description' => 'Elegant evening dress perfect for special occasions.',
                'short_description' => 'Beautiful designer dress.',
                'price' => 299.99,
                'compare_price' => 399.99,
                'sku' => 'DRESS001',
                'inventory_quantity' => 15,
                'category_name' => 'Women\'s Clothing',
            ],
            [
                'name' => 'Men\'s Casual Shirt',
                'description' => 'Comfortable cotton shirt for everyday wear.',
                'short_description' => 'Classic casual shirt.',
                'price' => 49.99,
                'sku' => 'SHIRT001',
                'inventory_quantity' => 100,
                'category_name' => 'Men\'s Clothing',
            ],
            [
                'name' => 'Wireless Earbuds',
                'description' => 'High-quality wireless earbuds with noise cancellation.',
                'short_description' => 'Premium wireless earbuds.',
                'price' => 199.99,
                'compare_price' => 249.99,
                'sku' => 'EARBUDS001',
                'inventory_quantity' => 75,
                'category_name' => 'Accessories',
            ],
            [
                'name' => 'Running Shoes',
                'description' => 'Comfortable running shoes with advanced cushioning.',
                'short_description' => 'Performance running shoes.',
                'price' => 129.99,
                'sku' => 'SHOES001',
                'inventory_quantity' => 60,
                'category_name' => 'Shoes',
            ],
        ];

        foreach ($products as $productData) {
            $category = $categories->where('name', $productData['category_name'])->first();
            $vendor = $vendors->random();

            if ($category) {
                unset($productData['category_name']);
                $productData['user_id'] = $vendor->id;
                $productData['category_id'] = $category->id;
                $productData['is_active'] = true;
                $productData['is_featured'] = rand(0, 1) == 1;
                $productData['weight'] = rand(100, 5000) / 100; // Random weight between 1-50 kg
                $productData['dimensions'] = [
                    'length' => rand(10, 100),
                    'width' => rand(10, 100),
                    'height' => rand(5, 50),
                ];

                Product::firstOrCreate(
                    ['sku' => $productData['sku']],
                    $productData
                );
            }
        }
    }
}