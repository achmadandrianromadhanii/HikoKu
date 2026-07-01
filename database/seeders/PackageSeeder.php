<?php

namespace Database\Seeders;

use App\Models\PackageItem;
use App\Models\Product;
use App\Models\ProductPackage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $package = ProductPackage::updateOrCreate(
            ['slug' => Str::slug('Paket Pendaki Pemula')],
            [
                'name' => 'Paket Pendaki Pemula',
                'description' => 'Paket hemat untuk 2 orang.',
                'price_per_day' => 150000,
                'is_active' => true,
                'is_featured' => true,
            ]
        );

        $items = [
            ['product' => 'Tenda Dome 2P', 'quantity' => 1],
            ['product' => 'Sleeping Bag Extreme', 'quantity' => 2],
            ['product' => 'Matras Lipat', 'quantity' => 2],
            ['product' => 'Kompor Portable', 'quantity' => 1],
        ];

        foreach ($items as $item) {
            $product = Product::where('name', $item['product'])->first();

            if ($product) {
                PackageItem::updateOrCreate(
                    ['package_id' => $package->id, 'product_id' => $product->id],
                    ['quantity' => $item['quantity']]
                );
            }
        }
    }
}
