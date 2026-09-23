<?php

namespace App\Console\Commands;

use App\Services\Peer\PeerEvaluationNotifier;
use Illuminate\Console\Command;

/**
 * Tells officer trainees about peer evaluations whose window has opened.
 *
 * The admin actions cannot cover every case on their own. A form switched on
 * BEFORE its event's start date is not fillable yet, so nothing is sent at that
 * moment — correctly. Without this command the window would then open on its
 * start date in silence and the OTs would never hear about it.
 *
 * Safe to run as often as you like: an officer trainee is told when their view
 * of a group CHANGES, so a daily run over a window that stays open for a
 * fortnight does not ping anybody again - while a form that was closed and
 * reopened is announced afresh.
 *
 * Openings only. A closure is announced by the admin action that causes one
 * (PeerEvaluationNotifier::announceGroup), never swept up here: a nightly pass
 * over historical groups would announce every window that has ever ended.
 */
class NotifyOpenPeerEvaluations extends Command
{
    protected $signature = 'peer:notify-open-evaluations';

    protected $description = 'Notify officer trainees of peer evaluations that are open to fill';

    public function handle(PeerEvaluationNotifier $notifier): int
    {
        $result = $notifier->notifyAllOpenGroups();

        $this->info(sprintf(
            'Peer evaluation notifications: %d sent across %d group(s).',
            $result['notified'],
            $result['groups']
        ));

        return self::SUCCESS;
    }
}
