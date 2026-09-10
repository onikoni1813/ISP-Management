<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Master Plan Rule 30: Schedule Automated ISP Expiry SMS daily at 09:00 AM
Schedule::command('isp:send-expiry-sms')->dailyAt('09:00');
