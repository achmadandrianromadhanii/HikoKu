<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $category = Category::firstOrCreate(
            ['slug' => Str::slug($row['category'])],
            [
                'name' => $row['category'],
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        return new Product([
            'category_id' => $category->id,
            'name' => $row['name'],
            'slug' => Str::slug($row['name']) . '-' . Str::lower(Str::random(5)),
            'description' => $row['description'] ?? null,
            'short_desc' => $row['short_desc'] ?? null,
            'price_per_day' => $row['price_per_day'] ?? 0,
            'deposit_amount' => $row['deposit_amount'] ?? 0,
            'stock_total' => $row['stock_total'] ?? 0,
            'stock_available' => $row['stock_available'] ?? 0,
            'weight_gram' => $row['weight_gram'] ?? null,
            'condition' => $row['condition'] ?? 'good',
            'is_active' => true,
            'is_featured' => false,
            'avg_rating' => 0,
            'review_count' => 0,
        ]);
    }
}
