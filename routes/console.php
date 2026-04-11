<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('app:about', function () {
    $this->comment('Games backend Laravel scaffold');
});

// ── Tournament Status Scheduler ───────────────────────────────────────────────
// Runs every minute.
// Handles: draft→registration_open (when registration_start_at arrives)
//          registration_open→registration_closed (when registration_end_at passes)
//
// SETUP REQUIRED: Add this cron entry to your server's crontab (once):
//   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
//
Schedule::command('tournaments:advance-statuses')
    ->everyMinute()
    ->withoutOverlapping()   // agar pichla run abhi chal raha ho to skip karo
    ->runInBackground();     // PHP-FPM ya queue worker block na ho

// ── Legacy Bonus + Commission Scheduler ─────────────────────────────────────
// Processes pending legacy deposits and applies referral/deposit bonuses.
// Requires env keys:
//   PAYMENTAPI_KEY (nowpayments) and/or PAYFORMEE_USER_TOKEN (payformee)
//   LEGACY_INCOME_DEPOSIT_BONUS=true to enable deposit bonus rules
Schedule::command('legacy:process-bonus-commission')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
