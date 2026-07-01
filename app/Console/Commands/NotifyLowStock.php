<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class NotifyLowStock extends Command
{
    protected $signature = 'products:notify-low-stock';
    protected $description = 'Cek produk dengan stok kritis';

    public function handle(): int
    {
        $threshold = 2;

        $products = Product::query()
            ->where('stock_available', '<=', $threshold)
            ->where('is_active', true)
            ->get();

        foreach ($products as $product) {
            // nanti panggil notification admin
        }

        $this->info("Low stock products found: {$products->count()}");

        return self::SUCCESS;
    }
}
