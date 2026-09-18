<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function (): void {
    Cache::store('redis')->put('scheduler:heartbeat', now()->toIso8601String(), 180);
})->name('infrastructure-heartbeat')->everyMinute()->withoutOverlapping();

Schedule::command('media:recover-listings')->everyMinute()->withoutOverlapping();
Schedule::command('listings:expire')->everyMinute()->withoutOverlapping();

Schedule::command('media:cleanup')->everyTenMinutes()->withoutOverlapping();
