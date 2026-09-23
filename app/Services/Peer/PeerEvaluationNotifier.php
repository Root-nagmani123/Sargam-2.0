<?php

namespace App\Services\Peer;

use App\Services\NotificationService;
use App\Support\PeerEvaluationForm;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * What an officer trainee hears about a peer evaluation — the one place that decides.
 *
 * Everything an admin does to a group funnels into announceGroup(): creating the
 * evaluation, assigning OTs to it, switching the form on or off, deactivating the
 * event above it, or switching off its last criterion. The caller says WHICH
 * group changed, never what the change meant — the group is re-read through
 * PeerEvaluationForm::closedReason(), so the notification can never disagree with
 * what the OT finds when they follow it.
 *
 * Three things are worth telling an OT, and they are three notification types
 * because they answer different questions:
 *
 *   TYPE           the form is open — "you can fill it now"
 *   TYPE_ASSIGNED  you are on an evaluation that is not open yet
 *   TYPE_CLOSED    the form you were invited to fill has been closed
 *
 * ONE PER CHANGE, NOT ONE EVER. Open and closed are a STATE, and an OT is told
 * when their state changes — so open, closed, open again is three notifications,
 * while a form that merely stays open (the daily command re-running for a
 * fortnight) is one. What decides is the OT's LATEST state notification for that
 * group, not whether any exists; see stateToAnnounce().
 *
 * NO RETROSPECTIVE BURSTS. A closure is only announced to OTs who were actually
 * told the form was open, and an assignment only for an evaluation that has not
 * already finished. Deactivating a course's worth of old events therefore tells
 * nobody anything, rather than notifying a whole batch about forms they never saw.
 */
class PeerEvaluationNotifier
{
    /** The form is open to fill. */
    public const TYPE = 'peer_evaluation';

    /** You have been put on an evaluation that cannot be filled yet. */
    public const TYPE_ASSIGNED = 'peer_evaluation_assigned';

    /** The form you were invited to fill is no longer open. */
    public const TYPE_CLOSED = 'peer_evaluation_closed';

    /**
     * The two types that answer "can I fill this right now".
     *
     * An assignment notice is about membership and answers neither, which is why
     * it is not in here: being told you were added must not stop you being told,
     * later, that the form has opened.
     */
    private const STATE_TYPES = [self::TYPE, self::TYPE_CLOSED];

    public const MODULE = 'PeerEvaluation';

    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Tell a group's officer trainees where it stands.
     *
     * The entry point for every admin action that changes a group. Nothing is
     * sent when the OTs already know the current state.
     *
     * @param  list<int>|null  $memberPks  null = every member; otherwise only these
     * @return int  how many notifications were actually sent
     */
    public function announceGroup(int $groupId, ?array $memberPks = null): int
    {
        try {
            $group = DB::table('peer_groups')->where('id', $groupId)->first();

            if (! $group) {
                return 0;
            }

            $receivers = $this->receiversFor($groupId, $memberPks);

            if ($receivers->isEmpty()) {
                return 0;
            }

            return PeerEvaluationForm::closedReason($group) === null
                ? $this->announceOpen($group, $receivers)
                : $this->announceNotOpen($group, $receivers);
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
     * Notify a group's officer trainees IF its form is open.
     *
     * Kept as its own method because that is all the daily command wants: it
     * catches windows that open on their start date, with no admin action to
     * hang a notification off. announceGroup() is the one to call from a screen.
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

            $receivers = $this->receiversFor($groupId, $memberPks);

            return $receivers->isEmpty() ? 0 : $this->announceOpen($group, $receivers);
        } catch (\Throwable $e) {
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
     * today" in SQL: the open/closed rule spans peer_groups, peer_events and the
     * group's criteria and is already expressed once, in closedReason().
     * Re-expressing it as a query would give two definitions that could disagree.
     *
     * Openings only. Closures are announced by the admin action that causes one,
     * never swept up here: a nightly pass over historical groups would announce
     * every window that has ever ended, to everybody who was told it opened.
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

    /** "You can fill it now", to whoever does not already believe that. */
    private function announceOpen(object $group, Collection $receivers): int
    {
        return $this->send(
            $this->receiversNeedingState(self::TYPE, (int) $group->id, $receivers),
            self::TYPE,
            (int) $group->id,
            $this->openMessage($group)
        );
    }

    /**
     * The form is not open. Which is news to two different people.
     *
     * Somebody who was told it was open needs to hear that it has closed.
     * Somebody who has never been told anything is newly on an evaluation that
     * has not opened yet - worth saying once, or being added to a form that opens
     * next month is silent until it does.
     */
    private function announceNotOpen(object $group, Collection $receivers): int
    {
        $groupId = (int) $group->id;
        $state = $this->latestStateByReceiver($groupId, $receivers);

        $wereTold = $receivers->filter(fn (int $pk) => ($state[$pk] ?? null) === self::TYPE)->values();
        $sent = $this->send($wereTold, self::TYPE_CLOSED, $groupId, $this->closedMessage($group));

        // An evaluation that is over is not something to be introduced to.
        if (! $this->stillAhead($group)) {
            return $sent;
        }

        $newcomers = $receivers
            ->reject(fn (int $pk) => array_key_exists($pk, $state))
            ->reject(fn (int $pk) => in_array($pk, $this->alreadyAssigned($groupId, $receivers), true))
            ->values();

        return $sent + $this->send($newcomers, self::TYPE_ASSIGNED, $groupId, $this->assignedMessage($group));
    }

    /**
     * @param  Collection<int, int>  $receivers
     */
    private function send(Collection $receivers, string $type, int $groupId, string $message): int
    {
        if ($receivers->isEmpty()) {
            return 0;
        }

        // reference_pk is the group id; config/notifications.php turns it into
        // /peer-evaluation?group_id=<id>, so the click opens this group.
        $this->notifications->createMultiple(
            $receivers->all(),
            $type,
            self::MODULE,
            $groupId,
            'Peer Evaluation',
            $message
        );

        return $receivers->count();
    }

    /**
     * The officer trainees of a group, optionally narrowed to some of them.
     *
     * receiver_user_id is Auth::user()->user_id, which for an officer trainee is
     * their student_master.pk — the value member_pk holds.
     *
     * @param  list<int>|null  $memberPks
     * @return Collection<int, int>
     */
    private function receiversFor(int $groupId, ?array $memberPks): Collection
    {
        return DB::table('peer_group_members')
            ->where('group_id', $groupId)
            ->when($memberPks !== null, fn ($q) => $q->whereIn('member_pk', $memberPks ?: [-1]))
            ->whereNotNull('member_pk')
            ->distinct()
            ->pluck('member_pk')
            ->map(fn ($pk) => (int) $pk)
            ->filter()
            ->values();
    }

    /**
     * Which of these officer trainees do not already believe the state we are
     * about to announce.
     *
     * @param  Collection<int, int>  $receivers
     * @return Collection<int, int>
     */
    private function receiversNeedingState(string $type, int $groupId, Collection $receivers): Collection
    {
        $state = $this->latestStateByReceiver($groupId, $receivers);

        return $receivers->reject(fn (int $pk) => ($state[$pk] ?? null) === $type)->values();
    }

    /**
     * Each officer trainee's LATEST open/closed notification for the group.
     *
     * Latest, not "any": told open, then closed, an OT has to be tellable open
     * again when the admin switches the form back on. Ordered by pk ascending so
     * the newest row is the one left standing in the map.
     *
     * @param  Collection<int, int>  $receivers
     * @return array<int, string>  member_pk => notification type
     */
    private function latestStateByReceiver(int $groupId, Collection $receivers): array
    {
        $rows = DB::table('notifications')
            ->where('module_name', self::MODULE)
            ->whereIn('type', self::STATE_TYPES)
            ->where('reference_pk', $groupId)
            ->whereIn('receiver_user_id', $receivers->all())
            ->orderBy('pk')
            ->get(['receiver_user_id', 'type']);

        $state = [];

        foreach ($rows as $row) {
            $state[(int) $row->receiver_user_id] = $row->type;
        }

        return $state;
    }

    /**
     * Who has already had the "you have been added" notice for this group.
     *
     * Once ever, unlike the state pair: being added is not something that happens
     * again, and re-syncing a group's members from Course Group Mapping must not
     * re-announce it.
     *
     * @param  Collection<int, int>  $receivers
     * @return list<int>
     */
    private function alreadyAssigned(int $groupId, Collection $receivers): array
    {
        return DB::table('notifications')
            ->where('module_name', self::MODULE)
            ->where('type', self::TYPE_ASSIGNED)
            ->where('reference_pk', $groupId)
            ->whereIn('receiver_user_id', $receivers->all())
            ->pluck('receiver_user_id')
            ->map(fn ($pk) => (int) $pk)
            ->all();
    }

    /**
     * Is this evaluation still ahead of the officer trainee?
     *
     * Guards the assignment notice only. A group on a dead event, or one whose
     * window closed last month, is not worth introducing somebody to - and this
     * is what keeps a bulk deactivation, or a re-sync of old groups, from
     * notifying people about evaluations they will never fill.
     */
    private function stillAhead(object $group): bool
    {
        if (! $group->is_active) {
            return false;
        }

        if (blank($group->event_id)) {
            return true;
        }

        $event = DB::table('peer_events')->where('id', $group->event_id)->first();

        if (! $event) {
            return true;
        }

        if (! $event->is_active) {
            return false;
        }

        return blank($event->end_date) || ! Carbon::today()->gt(Carbon::parse($event->end_date));
    }

    private function openMessage(object $group): string
    {
        return 'The peer evaluation for ' . $this->label($group) . ' is open. You can fill it now.';
    }

    private function closedMessage(object $group): string
    {
        return 'The peer evaluation for ' . $this->label($group)
            . ' has been closed. You cannot fill it at the moment.';
    }

    /**
     * The assignment notice says WHEN, not just that. "You have been added" on its
     * own leaves the OT checking a form that will not open for weeks.
     */
    private function assignedMessage(object $group): string
    {
        $message = 'You have been added to the peer evaluation for ' . $this->label($group) . '.';

        $startDate = $group->event_id
            ? DB::table('peer_events')->where('id', $group->event_id)->value('start_date')
            : null;

        if (filled($startDate) && Carbon::today()->lt(Carbon::parse($startDate))) {
            return $message . ' It opens on ' . Carbon::parse($startDate)->format('d/m/Y') . '.';
        }

        return $message . ' You will be told when it is open to fill.';
    }

    /** "Bihar - Counsellor Group (Mid-term Review)" — the group, and its event. */
    private function label(object $group): string
    {
        $groupName = trim((string) ($group->group_name ?? '')) ?: 'your group';
        $eventName = $group->event_id
            ? DB::table('peer_events')->where('id', $group->event_id)->value('event_name')
            : null;

        return $groupName . ($eventName ? ' (' . $eventName . ')' : '');
    }
}
