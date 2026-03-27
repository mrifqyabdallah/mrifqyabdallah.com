<?php

use Illuminate\Support\Facades\Schedule;

Schedule::call(function (): void {})->hourly()
    ->description('Cron/scheduler heartbeat');

Schedule::command('blog:stats')->dailyAt('23:55')
    ->description('Generate blog statistics');
