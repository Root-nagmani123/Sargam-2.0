<?php

namespace App\Console\Commands;

use App\Services\COE\ResultMarksService;
use Illuminate\Console\Command;

/**
 * Tells faculty that Evaluation & Marks Entry is open for a component once its
 * exam is over on an Active, CC-approved drive. Each faculty is told once per
 * component, so running it repeatedly is harmless. Scheduled daily in Kernel.
 */
class CoeNotifyMarksEntryReady extends Command
{
    protected $signature = 'coe:notify-marks-entry-ready {--drive= : Only this examination drive id} {--dry-run : Count, do not send}';

    protected $description = 'Notify faculty that Evaluation & Marks Entry is open for their exam components';

    public function handle(ResultMarksService $results): int
    {
        $drive = $this->option('drive');
        $result = $results->notifyEntryReady($drive !== null ? (int) $drive : null, (bool) $this->option('dry-run'));

        $this->info(sprintf(
            '%d component(s) open with marks pending; %d faculty %s; %d faculty without a linked login.',
            $result['components'],
            $result['notified'],
            $this->option('dry-run') ? 'would be notified' : 'notified',
            $result['unreachable'],
        ));

        return self::SUCCESS;
    }
}
