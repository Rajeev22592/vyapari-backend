<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Scheduler: ingest AGMARKNET every 30 minutes for today and hourly for yesterday
Schedule::command('prices:ingest-agmarknet --date='.now()->toDateString())
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->name('ingest:agmarknet:today');

Schedule::command('prices:ingest-agmarknet --date='.now()->subDay()->toDateString())
    ->hourly()
    ->withoutOverlapping()
    ->name('ingest:agmarknet:yesterday');
