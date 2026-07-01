<?php

namespace App\Console\Commands;

use App\Models\CartItem;
use Illuminate\Console\Command;

class CleanExpiredCarts extends Command
{
    protected $signature = 'cart:clean-expired';
    protected $description = 'Hapus item cart yang sudah expired';

    public function handle(): int
    {
        $count = CartItem::query()->expired()->delete();

        $this->info("Expired cart items deleted: {$count}");

        return self::SUCCESS;
    }
}
