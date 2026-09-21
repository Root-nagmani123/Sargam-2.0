<?php

namespace App\Services\Peer;

use App\Services\NotificationService;
use App\Support\PeerEvaluationForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * "You can fill your peer evaluation" — the one place that decides who gets told.
 *
 * Shared by the admin actions that open a form (switching it on, adding members
 * to an already-open one) and by the daily command that catches the case those
 * cannot: a form switched on BEFORE its event starts. Nothing happens at the
 * moment of switching then — correctly, the form is not fillable yet — so
 * without the command the window would open on its start date in silence.
 *
 * Two rules, applied in both paths:
 *
 *   OPEN    PeerEvaluationForm::closedReason() must say the form is fillable
 *           right now. That is the same gate the officer-trainee page and
 *           store() apply, so nobody is ever invited to a form that would then
 *           turn them away — group inactive, form off, event inactive, or today
 *           outside the event's start/end dates.
 *
 *   ONCE    One notification per officer trainee per group. The command runs
 *           daily and the admin may toggle a form more than once; without this
 *           an OT would be pinged every day the window stayed open.
 */
class PeerEvaluationNotifier
{
    public const TYPE = 'peer_evaluation';

    public const MODULE = 'PeerEvaluation';

    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Notify a group's officer trainees, if the form is open and they have not
     * already been told about this group.
     *
     * @param  list<int>|null  $memberPks  null = every member of the group
     * @return int  how many notifications were actually sent
     */
    public function notifyOpenGroup(int $groupId, ?array $memberPks = null): int
    {
        try {
            $group = DB::table('peer_groups')->where('id', $groupId)->first();

            if (! $group || PeerEvaluationForm::closedReason($group) !== null) {
                return 0;
            }

            // receiver_user_id is Auth::user()->user_id, which for an officer
            // trainee is their student_master.pk — the value member_pk holds.
            $receivers = DB::table('peer_group_members')
                ->where('group_id', $groupId)
                ->when($memberPks !== null, fn ($q) => $q->whereIn('member_pk', $memberPks ?: [-1]))
                ->whereNotNull('member_pk')
                ->distinct()
                ->pluck('member_pk')
                ->map(fn ($pk) => (int) $pk)
                ->filter()
                ->values();

            if ($receivers->isEmpty()) {
                return 0;
            }

            $alreadyTold = DB::table('notifications')
                ->where('type', self::TYPE)
                ->where('module_name', self::MODULE)
                ->where('reference_pk', $groupId)
                ->whereIn('receiver_user_id', $receivers->all())
                ->pluck('receiver_user_id')
                ->map(fn ($pk) => (int) $pk)
                ->all();

            $toTell = $receivers->reject(fn (int $pk) => in_array($pk, $alreadyTold, true))->values();

            if ($toTell->isEmpty()) {
                return 0;
            }

            // reference_pk is the group id; config/notifications.php turns it into
            // /peer-evaluation?group_id=<id>, so the click opens this group.
            $this->notifications->createMultiple(
                $toTell->all(),
                self::TYPE,
                self::MODULE,
                $groupId,
                'Peer Evaluation',
                $this->message($group)
            );

            return $toTell->count();
        } catch (\Throwable $e) {
            // Never let notifying break the action that triggered it: an admin
            // switching a form on must not see a 500 because this failed.
            Log::error('Failed to notify peer group members', [
                'group_id' => $groupId,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Every group whose form is open right now — what the daily command runs.
     *
     * Deliberately walks all active groups rather than trying to select "opened
     * today" in SQL: the open/closed rule spans peer_groups and peer_events and
     * is already expressed once, in closedReason(). Re-expressing it as a query
     * would give two definitions that could disagree.
     *
     * @return array{groups: int, notified: int}
     */
    public function notifyAllOpenGroups(): array
    {
        $groupIds = DB::table('peer_groups')->where('is_active', 1)->pluck('id');

        $groups = 0;
        $notified = 0;

        foreach ($groupIds as $groupId) {
            $sent = $this->notifyOpenGroup((int) $groupId);

            if ($sent > 0) {
                $groups++;
                $notified += $sent;
            }
        }

        return ['groups' => $groups, 'notified' => $notified];
    }

    private function message(object $group): string
    {
        $groupName = trim((string) ($group->group_name ?? '')) ?: 'your group';
        $eventName = $group->event_id
            ? DB::table('peer_events')->where('id', $group->event_id)->value('event_name')
            : null;

        return 'The peer evaluation for ' . $groupName
            . ($eventName ? ' (' . $eventName . ')' : '')
            . ' is open. You can fill it now.';
    }
}
