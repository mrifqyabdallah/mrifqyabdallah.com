<?php

use Illuminate\Support\Facades\Schedule;

Schedule::call(function (): void {})->everyHour()
    ->description('Cron/scheduler heartbeat');

Schedule::command('blog:stats')->dailyAt('02:30')
    ->description('Generate blog statistics');
