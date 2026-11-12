<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Emails about new posts and events go out within five minutes of publishing.
Schedule::command('school:send-notifications')->everyFiveMinutes()->withoutOverlapping();
// Send the previous term's results and people backup once, up to seven days before the next/current term.
Schedule::command('school:send-term-backup --days=7')->dailyAt('02:00')->withoutOverlapping();
// Send queued emails a few at a time (set MAIL_BROADCAST_PER_MINUTE to match your email provider's limit).
Schedule::command('queue:work --stop-when-empty --max-time=50 --max-jobs='.(int) config('school.mail_broadcast_per_minute'))
    ->everyMinute()->withoutOverlapping()->when(fn () => config('queue.default') !== 'sync');
