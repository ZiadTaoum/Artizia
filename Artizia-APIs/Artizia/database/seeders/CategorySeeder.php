<?php
// database/seeders/CategorySeeder.php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'description' => 'Electronic devices and gadgets',
                'is_active' => true,
                'sort_order' => 1,
                'children' => [
                    ['name' => 'Smartphones', 'description' => 'Mobile phones and accessories'],
                    ['name' => 'Laptops', 'description' => 'Laptops and notebooks'],
                    ['name' => 'Accessories', 'description' => 'Electronic accessories'],
                ]
            ],
            [
                'name' => 'Fashion',
                'description' => 'Clothing and fashion items',
                'is_active' => true,
                'sort_order' => 2,
                'children' => [
                    ['name' => 'Men\'s Clothing', 'description' => 'Clothing for men'],
                    ['name' => 'Women\'s Clothing', 'description' => 'Clothing for women'],
                    ['name' => 'Shoes', 'description' => 'Footwear for all'],
                ]
            ],
            [
                'name' => 'Home & Garden',
                'description' => 'Home improvement and garden items',
                'is_active' => true,
                'sort_order' => 3,
                'children' => [
                    ['name' => 'Furniture', 'description' => 'Home furniture'],
                    ['name' => 'Garden Tools', 'description' => 'Tools for gardening'],
                ]
            ]
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $category = Category::firstOrCreate(
                ['name' => $categoryData['name']],
                $categoryData
            );

            foreach ($children as $childData) {
                $childData['parent_id'] = $category->id;
                $childData['is_active'] = true;
                $childData['sort_order'] = 0;

                Category::firstOrCreate(
                    ['name' => $childData['name'], 'parent_id' => $category->id],
                    $childData
                );
            }
        }
    }
}