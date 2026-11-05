<?php

namespace App\Console\Commands;

use App\Services\NoticeBellNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Announce notices whose display date has arrived.
 *
 * A notice saved with a future display date stays out of the feed and off the
 * bell until that date (see NoticeBellNotifier::publish()). This command, run
 * by the scheduler, rings the bell for every active, unexpired notice that is
 * now due and has not been announced yet. A notice whose date passed while the
 * scheduler was down is still picked up on the next run.
 */
class SendDueNoticeBells extends Command
{
    protected $signature = 'notices:send-due-bells';

    protected $description = 'Send bell notifications for notices whose display date has arrived';

    public function handle(NoticeBellNotifier $notifier): int
    {
        $today = now()->toDateString();

        $due = DB::table('notices_notification')
            ->where('active_inactive', 1)
            ->whereNull('bell_sent_at')
            ->where('display_date', '<=', $today)
            ->where(fn ($q) => $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', $today))
            ->orderBy('pk')
            ->get(['pk', 'created_by']);

        $failed = 0;

        foreach ($due as $notice) {
            try {
                $sent = $notifier->publish((int) $notice->pk, false, $notice->created_by ? (int) $notice->created_by : null);
                $this->line("Notice {$notice->pk}: {$sent} notification(s)");
            } catch (\Throwable $e) {
                $failed++;
                report($e);
                $this->error("Notice {$notice->pk}: failed ({$e->getMessage()})");
            }
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
