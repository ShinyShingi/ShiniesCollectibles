<?php

use App\Jobs\CheckPriceAlerts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule price checking every 10 minutes
Schedule::command('price:check-scheduled --limit=20')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Clean up old price checks daily
Schedule::command('price:check-scheduled --limit=0')
    ->daily()
    ->description('Clean up old price check data');

// Check for price alerts every 30 minutes
Schedule::job(new CheckPriceAlerts())
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->description('Check for price alerts and send notifications');
