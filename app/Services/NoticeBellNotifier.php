<?php

namespace App\Services;

use App\Models\NoticeAudienceMap;
use App\Models\NoticeNotification as Notice;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bell notifications for the notice module (Admin > Notice).
 *
 * A notice keeps at most one bell entry per recipient: every publish withdraws
 * the previous entries first, so an edit or a re-activation does not stack a
 * second card on top of the first, and an audience that was narrowed loses its
 * entry along with the notice.
 *
 * Recipients are worked out from the notice's side, and must stay the people
 * notice_feed_query_by_role() shows the notice to: ringing someone's bell for a
 * notice the feed will not show them opens an empty Notices tab, and missing
 * someone it does show means the notice goes unannounced to them.
 */
class NoticeBellNotifier
{
    public const TYPE = 'notice';
    public const MODULE = 'Notice';

    private const INSERT_CHUNK = 500;

    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Replace the notice's bell entries with fresh ones for its current audience.
     * A notice that is inactive or already expired is withdrawn and not re-sent,
     * since the feed shows neither. One whose display date is still ahead is
     * withdrawn and left for notices:send-due-bells to announce on that date.
     *
     * @return int Number of bell entries created
     */
    public function publish(int $noticePk, bool $updated = false, ?int $senderCredentialPk = null): int
    {
        $this->withdraw($noticePk);

        $notice = Notice::find($noticePk);
        $today = date('Y-m-d');

        if (! $notice
            || (int) ($notice->active_inactive ?? 1) !== 1
            || ($notice->expiry_date && $notice->expiry_date < $today)
            || ($notice->display_date && $notice->display_date > $today)) {
            return 0;
        }

        // Marked before sending: should the inserts fail part-way, the scheduler
        // does not keep re-announcing to the recipients already rung.
        $this->markSent($noticePk, now());

        $sender = $senderCredentialPk
            ? DB::table('user_credentials')->where('pk', $senderCredentialPk)->value('user_id')
            : null;

        [$title, $message] = $this->wording($notice, $updated);

        $created = 0;

        foreach (array_chunk($this->receiverUserIds($notice, $senderCredentialPk), self::INSERT_CHUNK) as $chunk) {
            $created += $this->notifications->createMultiple(
                $chunk,
                self::TYPE,
                self::MODULE,
                (int) $notice->pk,
                $title,
                $message,
                $sender !== null ? (int) $sender : null
            );
        }

        return $created;
    }

    /** Remove every bell entry this notice has rung, and mark it unannounced. */
    public function withdraw(int $noticePk): int
    {
        $this->markSent($noticePk, null);

        return Notification::where('type', self::TYPE)
            ->where('module_name', self::MODULE)
            ->where('reference_pk', $noticePk)
            ->delete();
    }

    /**
     * Record when the notice was announced (null: not yet). Skipped until the
     * bell_sent_at migration has run, so a deploy that ships the code first
     * does not turn every notice save, toggle or delete into an error.
     */
    private function markSent(int $noticePk, $at): void
    {
        static $hasColumn = null;
        $hasColumn ??= Schema::hasColumn('notices_notification', 'bell_sent_at');

        if ($hasColumn) {
            DB::table('notices_notification')->where('pk', $noticePk)->update(['bell_sent_at' => $at]);
        }
    }

    /**
     * Personal notices are addressed by name, and the bell is keyed on
     * user_credentials.user_id alone, which is not unique across categories (an
     * employee and a trainee can share one). The title stays generic for them so
     * the notice's subject is never shown on the other account's bell.
     */
    private function wording(Notice $notice, bool $updated): array
    {
        $validTill = $notice->expiry_date ? date('d M Y', strtotime($notice->expiry_date)) : null;

        if ($notice->audience_mode === Notice::MODE_INDIVIDUAL) {
            $title = $updated ? 'A notice addressed to you was updated' : 'You have a new notice';
            $message = 'Open the Notices tab to read it.';
        } else {
            $title = ($updated ? 'Notice updated: ' : 'New notice: ') . $notice->notice_title;
            $message = trim(($notice->notice_type ?: 'Notice') . ($validTill ? ' · valid till ' . $validTill : ''));
        }

        return [mb_substr($title, 0, 255), $message];
    }

    /**
     * user_credentials.user_id of everyone the notice reaches, the author excluded.
     *
     * @return int[]
     */
    public function receiverUserIds(Notice $notice, ?int $excludeCredentialPk = null): array
    {
        $audience = $notice->audienceMaps()
            ->get(['audience_type', 'reference_pk'])
            ->groupBy('audience_type')
            ->map(fn ($rows) => $rows->pluck('reference_pk')->map(fn ($pk) => (int) $pk)->unique()->values()->all());

        $ids = fn (string $type) => $audience[$type] ?? [];

        $base = DB::table('user_credentials as uc')
            ->whereNotNull('uc.user_id')
            ->when($excludeCredentialPk, fn ($q) => $q->where('uc.pk', '!=', $excludeCredentialPk));

        if ((string) $notice->target_audience === 'All') {
            $queries = [$base];
        } elseif ($notice->isStaffFacultyAudience()) {
            $queries = $this->staffQueries($base, $notice->audience_mode, $ids);
        } elseif ($notice->isOfficerTraineeAudience()) {
            $queries = $this->traineeQueries($base, $notice->audience_mode, $ids);
        } else {
            $queries = [];
        }

        $userIds = [];

        foreach ($queries as $query) {
            foreach ($query->distinct()->pluck('uc.user_id') as $userId) {
                $userIds[(int) $userId] = true;
            }
        }

        return array_keys($userIds);
    }

    /**
     * Staff/Faculty half of notice_feed_query_by_role(). A category-E login's
     * user_id is the employee pk; a category-F login's is a faculty_master pk,
     * whose employee is faculty_master.employee_master_pk.
     */
    private function staffQueries($base, ?string $mode, callable $ids): array
    {
        $employee = "CASE WHEN uc.user_category = 'E' THEN uc.user_id ELSE fm.employee_master_pk END";

        $categorised = (clone $base)
            ->leftJoin('faculty_master as fm', function ($join) {
                $join->on('fm.pk', '=', 'uc.user_id')->where('uc.user_category', '=', 'F');
            })
            ->whereIn('uc.user_category', ['E', 'F']);

        if ($mode === Notice::MODE_INDIVIDUAL) {
            $employees = $ids(NoticeAudienceMap::TYPE_EMPLOYEE);

            return $employees === [] ? [] : [$categorised->whereIn(DB::raw($employee), $employees)];
        }

        $departments = $ids(NoticeAudienceMap::TYPE_DEPARTMENT);

        if ($departments === []) {
            // Every department: also reaches a login with no category that holds
            // a staff role, which the feed matches by role alone.
            $byRole = (clone $base)
                ->where(fn ($q) => $q->whereNull('uc.user_category')->orWhereNotIn('uc.user_category', ['E', 'F', 'S']))
                ->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('model_has_roles as mhr')
                        ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                        ->whereColumn('mhr.model_id', 'uc.pk')
                        ->where('mhr.model_type', User::class)
                        ->whereIn('r.name', notice_staff_faculty_role_names());
                });

            return [$categorised, $byRole];
        }

        // Pinned departments, matched on where the employee works now.
        $categorised->whereExists(function ($sub) use ($employee, $departments) {
            $sub->select(DB::raw(1))
                ->from('employee_master as em')
                ->whereRaw("em.pk = {$employee}")
                ->whereIn('em.department_master_pk', $departments);
        });

        return [$categorised];
    }

    /**
     * Officer Trainee half of notice_feed_query_by_role(), for category-S logins,
     * whose user_id is the student_master pk.
     */
    private function traineeQueries($base, ?string $mode, callable $ids): array
    {
        $query = (clone $base)->where('uc.user_category', 'S');
        $courses = $ids(NoticeAudienceMap::TYPE_COURSE);

        // A notice from before targeting (NULL mode) reaches only its pinned
        // courses; a targeted one with no course row reaches every course.
        if ($mode === null && $courses === []) {
            return [];
        }

        if ($courses !== []) {
            $query->whereExists(function ($sub) use ($courses) {
                $sub->select(DB::raw(1))
                    ->from('student_master_course__map as scm')
                    ->whereColumn('scm.student_master_pk', 'uc.user_id')
                    ->whereIn('scm.course_master_pk', $courses);
            });
        }

        if ($mode === Notice::MODE_GROUP) {
            $groups = $ids(NoticeAudienceMap::TYPE_GROUP);

            if ($groups === []) {
                return [];
            }

            $query->whereExists(function ($sub) use ($groups) {
                $sub->select(DB::raw(1))
                    ->from('student_course_group_map as scg')
                    ->whereColumn('scg.student_master_pk', 'uc.user_id')
                    ->whereIn('scg.group_type_master_course_master_map_pk', $groups)
                    ->where('scg.active_inactive', 1);
            });
        } elseif ($mode === Notice::MODE_INDIVIDUAL) {
            $students = $ids(NoticeAudienceMap::TYPE_STUDENT);

            if ($students === []) {
                return [];
            }

            $query->whereIn('uc.user_id', $students);
        }

        return [$query];
    }
}
