<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Tenda', 'icon' => 'tent'],
            ['name' => 'Carrier', 'icon' => 'backpack'],
            ['name' => 'Sleeping Bag', 'icon' => 'moon'],
            ['name' => 'Matras', 'icon' => 'rectangle-horizontal'],
            ['name' => 'Kompor Outdoor', 'icon' => 'flame'],
            ['name' => 'Trekking Pole', 'icon' => 'tree-pine'],
        ];

        foreach ($items as $index => $item) {
            Category::updateOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'name' => $item['name'],
                    'description' => 'Kategori ' . $item['name'],
                    'icon' => $item['icon'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
