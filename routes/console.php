<?php

use Illuminate\Support\Facades\Schedule;

// Uncomment the line below to check if scheduler is alive in storage/logs/
// Schedule::call(fn () => logger('scheduler is alive'))->everyFiveSeconds();

Schedule::command('blog:stats')->dailyAt('02:30');