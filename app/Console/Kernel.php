<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Runs twice daily; system cron should call `php artisan schedule:run` every minute.
        $schedule->command('send:stock_alert')->twiceDaily(12, 17);

        // Catches peer evaluations whose window opens on a date rather than on
        // an admin click — a form switched on before its event starts is not
        // fillable yet, so nothing is sent then. Early enough that the OTs hear
        // about it at the start of the day it opens. Idempotent: the notifier
        // skips anyone already told about that group.
        $schedule->command('peer:notify-open-evaluations')->dailyAt('07:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
