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

        // ज्ञानकोश notice feed: hourly catch-up (retries failures, pushes notices
        // whose display date has arrived, withdraws expired ones), and once a day
        // the published-id list that catches any withdrawal nobody signalled.
        // Both are no-ops while KNOWLEDGE_INGEST_ENABLED is off.
        $schedule->command('knowledge:sync-notices')->hourlyAt(15)->withoutOverlapping();
        $schedule->command('knowledge:sync-notices --reconcile')->dailyAt('02:30')->withoutOverlapping();
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
