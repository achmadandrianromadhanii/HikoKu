<?php

namespace App\Console\Commands;

use App\Models\Rental;
use Illuminate\Console\Command;

class SendReturnReminders extends Command
{
    protected $signature = 'rentals:send-return-reminders';
    protected $description = 'Kirim reminder pengembalian H-1 dan hari-H';

    public function handle(): int
    {
        $tomorrow = now()->addDay()->toDateString();
        $today = now()->toDateString();

        $rentals = Rental::query()
            ->whereIn('status', ['confirmed', 'active'])
            ->where(function ($q) use ($tomorrow, $today) {
                $q->whereDate('rental_end', $tomorrow)
                    ->orWhereDate('rental_end', $today);
            })
            ->with('user')
            ->get();

        foreach ($rentals as $rental) {
            // nanti panggil Notification / Mail
        }

        $this->info("Return reminders checked: {$rentals->count()}");

        return self::SUCCESS;
    }
}
