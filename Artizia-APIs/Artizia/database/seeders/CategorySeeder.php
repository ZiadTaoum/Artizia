<?php
// database/seeders/CategorySeeder.php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'Handmade Crafts', 'description' => 'Unique handcrafted items'],
            ['name' => 'Food & Beverages', 'description' => 'Homemade food and drinks'],
            ['name' => 'Games & Toys', 'description' => 'Custom games and handmade toys'],
            ['name' => 'Art & Decor', 'description' => 'Artwork and home decoration'],
            ['name' => 'Jewelry & Accessories', 'description' => 'Handmade jewelry and accessories'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}