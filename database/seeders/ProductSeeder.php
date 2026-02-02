<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'water', 'price' => null, 'description' => 'Pure drinking water'],
            ['name' => 'dry_food', 'price' => null, 'description' => 'Dry food items'],
            ['name' => 'hot_food', 'price' => null, 'description' => 'Cooked hot meals'],
            ['name' => 'miswak', 'price' => null, 'description' => 'Miswak for cleaning teeth'],
            ['name' => 'prayer_mat', 'price' => null, 'description' => 'Prayer rugs/mats'],
            ['name' => 'prayer_sheets', 'price' => null, 'description' => 'Prayer sheets'],
            ['name' => 'prayer_towels', 'price' => null, 'description' => 'Prayer towels'],
            ['name' => 'quran', 'price' => null, 'description' => 'Holy Quran copies'],
            ['name' => 'quran_holder', 'price' => null, 'description' => 'Quran stands/holders'],
            ['name' => 'tissues', 'price' => null, 'description' => 'Tissue papers'],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(
                ['name' => $product['name']],
                $product
            );
        }
    }
}
