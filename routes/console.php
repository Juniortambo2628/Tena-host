<?php

use App\Models\WifiEvent;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Needs the server cron: * * * * * php artisan schedule:run
Schedule::command('reviews:send')->hourly()->withoutOverlapping();
Schedule::command('alerts:check')->everyFiveMinutes()->withoutOverlapping();
// The WiFi log keeps 30 days (WifiEvent::prunable).
Schedule::command('model:prune', ['--model' => [WifiEvent::class]])->dailyAt('03:15');
Schedule::command('reports:monthly')->monthlyOn(1, '08:00')->timezone('Africa/Nairobi');
Schedule::command('birthdays:send')->dailyAt('09:00')->timezone('Africa/Nairobi');
Schedule::command('campaigns:run')->everyFiveMinutes()->withoutOverlapping();
// Property transfers: tell the guests of a transferred property who runs it now, in batches.
Schedule::command('transfers:notices')->everyFiveMinutes()->withoutOverlapping();
// Bookings also arrive by PMS webhook; this catches anything missed.
Schedule::command('pms:sync-guests')->hourly()->withoutOverlapping();
