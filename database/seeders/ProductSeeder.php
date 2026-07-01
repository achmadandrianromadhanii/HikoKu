<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['category' => 'Tenda', 'name' => 'Tenda Dome 2P', 'price' => 75000, 'deposit' => 150000],
            ['category' => 'Tenda', 'name' => 'Tenda Dome 4P', 'price' => 120000, 'deposit' => 200000],
            ['category' => 'Carrier', 'name' => 'Carrier 60L', 'price' => 50000, 'deposit' => 100000],
            ['category' => 'Sleeping Bag', 'name' => 'Sleeping Bag Extreme', 'price' => 40000, 'deposit' => 75000],
            ['category' => 'Matras', 'name' => 'Matras Lipat', 'price' => 20000, 'deposit' => 30000],
            ['category' => 'Kompor Outdoor', 'name' => 'Kompor Portable', 'price' => 30000, 'deposit' => 50000],
            ['category' => 'Trekking Pole', 'name' => 'Trekking Pole Carbon', 'price' => 25000, 'deposit' => 40000],
        ];

        foreach ($products as $item) {
            $category = Category::where('name', $item['category'])->first();

            Product::updateOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'category_id' => $category?->id,
                    'name' => $item['name'],
                    'description' => $item['name'] . ' untuk kebutuhan outdoor dan pendakian.',
                    'short_desc' => 'Peralatan outdoor berkualitas.',
                    'price_per_day' => $item['price'],
                    'stock_total' => 5,
                    'stock_available' => 5,
                    'weight_gram' => 1500,
                    'condition' => 'good',
                    'is_active' => true,
                    'is_featured' => true,
                ]
            );
        }
    }
}
