<?php

namespace App\Providers;

use App\Policies\StatsPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePolicies();
        $this->configureSchedule();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    protected function configurePolicies(): void
    {
        Gate::policy('stats', StatsPolicy::class);
    }

    protected function configureSchedule(): void
    {
        Event::listen(ScheduledTaskStarting::class, function (ScheduledTaskStarting $event): void {
            logger('Starting: '.$event->task->getSummaryForDisplay());
        });

        Event::listen([ScheduledTaskFinished::class, ScheduledBackgroundTaskFinished::class], function (ScheduledTaskFinished|ScheduledBackgroundTaskFinished $event): void {
            logger('Finished: '.$event->task->getSummaryForDisplay());
        });

        Event::listen(ScheduledTaskFailed::class, function (ScheduledTaskFailed $event): void {
            logger()->error('Failed: '.$event->task->getSummaryForDisplay(), [
                'exception' => $event->exception->getMessage(),
            ]);
        });
    }
}
