<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('payease:reconcile-monnify-balances')->dailyAt('02:00');
Schedule::command('disbursement:revert-expired-otps')->hourly();
Schedule::command('payease:backup-database')->dailyAt('03:00');
Schedule::command('horizon:snapshot')->everyFiveMinutes();
Schedule::command('payease:process-recurring-payments')->hourly();
