<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Needs the server cron: * * * * * php artisan schedule:run
Schedule::command('reviews:send')->hourly()->withoutOverlapping();
Schedule::command('alerts:check')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('reports:monthly')->monthlyOn(1, '08:00')->timezone('Africa/Nairobi');
Schedule::command('birthdays:send')->dailyAt('09:00')->timezone('Africa/Nairobi');
