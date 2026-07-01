<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('cart:clean-expired')->hourly();
Schedule::command('rentals:check-overdue')->hourly();
Schedule::command('rentals:send-return-reminders')->dailyAt('08:00');
Schedule::command('products:notify-low-stock')->dailyAt('07:00');
