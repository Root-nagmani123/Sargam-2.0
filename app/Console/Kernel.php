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

        // File-cache entries retired by a FeedbackReportCache generation bump are never read
        // again, so FileStore never deletes them. Removes only already-expired files.
        $schedule->command('cache:prune-expired-files')->hourly()->withoutOverlapping();
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
