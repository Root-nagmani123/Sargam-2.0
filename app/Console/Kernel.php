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
        // COE: tell faculty when a component's exam is over and its marks entry opens.
        $schedule->command('coe:notify-marks-entry-ready')->dailyAt('07:00')->withoutOverlapping();
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
