<?php

namespace App\Console\Commands;

use App\Models\Rental;
use Illuminate\Console\Command;

class CheckOverdueRentals extends Command
{
    protected $signature = 'rentals:check-overdue';
    protected $description = 'Ubah rental active yang melewati rental_end menjadi overdue';

    public function handle(): int
    {
        $count = Rental::query()
            ->where('status', 'active')
            ->whereDate('rental_end', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        $this->info("Overdue rentals updated: {$count}");

        return self::SUCCESS;
    }
}
