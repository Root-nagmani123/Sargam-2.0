<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The OT-facing side of Peer Evaluation, in one place.
 *
 * The admin screens added by "peer evaluation screen fixes" (Manage Events,
 * Manage Evaluation Columns, Manage Reflection Fields, Evaluation Reports) all
 * write scope onto their rows: a column or a reflection field carries a
 * course_id, an event_id and a group_id, any of which may be NULL. Nothing on
 * the OT-facing form read that scope back, so every visible column of every
 * course appeared on every group's form.
 *
 * This class is the one reader of those rules. Both halves of the flow use it -
 * user_index() to decide what to RENDER, store() to decide what to ACCEPT - so a
 * field that cannot be shown also cannot be submitted. Keeping them on separate
 * queries is what let the old store() accept any column_id in the table.
 *
 * ⚠️ IDENTITY. Three different ids are all called some flavour of "user":
 *     user_credentials.pk        - the login row; auth()->id(); peer_scores.evaluator_id
 *     user_credentials.user_name - the login HANDLE (varchar)
 *     peer_group_members.user_id - the same login handle, copied by
 *                                  PeerGroupSource::syncMembers()
 *     peer_group_members.member_pk - a student_master.pk (NOT a login id)
 *     peer_group_members.id      - what peer_scores.member_id points at
 * So an evaluator is matched to their group row through the HANDLE
 * (PeerGroupSource::EVALUATOR_JOIN), never by comparing numeric ids - those
 * columns hold different things and comparing them matches by coincidence.
 */
final class PeerEvaluationForm
{
    /**
     * Rows in scope for a group.
     *
     * peer_columns and peer_reflection_fields both carry the same optional
     * course -> event -> group triple, so both narrow the same way: a level that
     * is NULL means "not restricted at that level", i.e. broader. A row with all
     * three NULL is global and appears on every form.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  object  $group  a peer_groups row
     */
    private static function scopeToGroup($query, object $group)
    {
        return $query
            ->where(function ($q) use ($group) {
                $q->where('group_id', $group->id)->orWhereNull('group_id');
            })
            ->where(function ($q) use ($group) {
                $q->whereNull('event_id');
                if (filled($group->event_id)) {
                    $q->orWhere('event_id', $group->event_id);
                }
            })
            ->where(function ($q) use ($group) {
                $q->whereNull('course_id');
                if (filled($group->course_id)) {
                    $q->orWhere('course_id', $group->course_id);
                }
            });
    }

    /**
     * The evaluator's own row inside a group, or null if they are not in it.
     *
     * This is also the self-exclusion key: the returned row's id is the one
     * peer_group_members.id an OT must never score.
     */
    public static function membershipOf(int $groupId, int $userPk): ?object
    {
        return self::joinEvaluator(DB::table('peer_group_members'))
            ->where('peer_group_members.group_id', $groupId)
            ->where('user_credentials.pk', $userPk)
            ->select('peer_group_members.*')
            ->first();
    }

    /**
     * Join peer_group_members to the login that scores as them.
     *
     * The handle guards are not optional: peer_group_members.user_id is nullable
     * and the legacy Excel import leaves it blank, so without them one member row
     * with a NULL handle would match every evaluator whose user_name is also NULL
     * under MySQL's comparison of the COLLATEd expressions.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    private static function joinEvaluator($query)
    {
        return $query
            ->join('user_credentials', function ($join) {
                $join->whereRaw(PeerGroupSource::EVALUATOR_JOIN);
            })
            ->whereNotNull('peer_group_members.user_id')
            ->where('peer_group_members.user_id', '<>', '');
    }

    /**
     * Why a group is not accepting submissions, or null when it is open.
     *
     * Returns a sentence rather than a bool so the form and the POST handler
     * report the same reason instead of each inventing wording.
     */
    public static function closedReason(object $group): ?string
    {
        if (! $group->is_active) {
            return 'This peer group is no longer active.';
        }

        if (! $group->is_form_active) {
            return 'The evaluation form for this group is not open yet.';
        }

        if (blank($group->event_id)) {
            return null;
        }

        $event = DB::table('peer_events')->where('id', $group->event_id)->first();

        if (! $event) {
            return null;
        }

        if (! $event->is_active) {
            return 'The event for this group is no longer active.';
        }

        // start_date / end_date arrived with the Manage Events screen and are
        // day-granular, so the window is inclusive of both ends.
        $today = Carbon::today();

        if (filled($event->start_date) && $today->lt(Carbon::parse($event->start_date))) {
            return 'This evaluation opens on ' . Carbon::parse($event->start_date)->format('d/m/Y') . '.';
        }

        if (filled($event->end_date) && $today->gt(Carbon::parse($event->end_date))) {
            return 'This evaluation closed on ' . Carbon::parse($event->end_date)->format('d/m/Y') . '.';
        }

        return null;
    }

    /** Groups the user belongs to, newest event first, whether open or not. */
    public static function groupsFor(int $userPk): Collection
    {
        return self::joinEvaluator(
            DB::table('peer_groups')
                ->join('peer_group_members', 'peer_group_members.group_id', '=', 'peer_groups.id')
        )
            ->leftJoin('peer_events', 'peer_events.id', '=', 'peer_groups.event_id')
            ->leftJoin('course_master', 'course_master.pk', '=', 'peer_groups.course_id')
            ->where('user_credentials.pk', $userPk)
            ->where('peer_groups.is_active', 1)
            // A handle that appears on more than one member row of the same group
            // would otherwise list that group twice.
            ->distinct()
            ->select([
                'peer_groups.id',
                'peer_groups.group_name',
                'peer_groups.course_id',
                'peer_groups.event_id',
                'peer_groups.is_active',
                'peer_groups.is_form_active',
                'peer_groups.max_marks',
                'peer_groups.buffer_marks',
                'peer_events.event_name',
                'peer_events.start_date',
                'peer_events.end_date',
                'course_master.course_name',
            ])
            ->orderByDesc('peer_events.start_date')
            ->orderBy('peer_groups.group_name')
            ->get();
    }

    /**
     * The scored criteria on a group's form.
     *
     * Ordered by type so the "Rate Peers" block stays together and is not
     * interleaved with "Distribute Marks" - they are scored under different
     * rules and the OT needs to see them as two blocks.
     */
    public static function columnsFor(object $group): Collection
    {
        return self::scopeToGroup(DB::table('peer_columns'), $group)
            ->where('is_visible', 1)
            ->orderByRaw("FIELD(evaluation_type, 'rate_peers', 'distribute_marks')")
            ->orderBy('id')
            ->get();
    }

    /** The free-text questions under the grid, same scoping as the columns. */
    public static function reflectionFieldsFor(object $group): Collection
    {
        return self::scopeToGroup(DB::table('peer_reflection_fields'), $group)
            ->where('is_active', 1)
            ->orderBy('id')
            ->get();
    }

    /**
     * The OTs this evaluator scores: everyone in the group but themselves.
     *
     * member_pk is a student_master.pk, so the display name and OT code come from
     * student_master directly rather than back through user_credentials.
     */
    public static function peersFor(object $group, ?int $selfMemberId): Collection
    {
        $query = DB::table('peer_group_members')
            ->leftJoin('student_master', 'student_master.pk', '=', 'peer_group_members.member_pk')
            ->where('peer_group_members.group_id', $group->id);

        if ($selfMemberId !== null) {
            $query->where('peer_group_members.id', '<>', $selfMemberId);
        }

        return $query
            ->select([
                'peer_group_members.id',
                'peer_group_members.member_pk',
                'peer_group_members.ot_code',
                // student_master wins when the join lands; the denormalised
                // user_name on the member row is the fallback for rows imported
                // by the legacy Excel path, which never had a student_master.pk.
                DB::raw('COALESCE(NULLIF(TRIM(student_master.display_name), ""), peer_group_members.user_name, "Unnamed") as first_name'),
                'student_master.user_id',
            ])
            ->orderByRaw('COALESCE(peer_group_members.ot_code, "")')
            ->orderBy('peer_group_members.id')
            ->get();
    }

    /**
     * What this evaluator submitted last time, so the form reopens filled in.
     *
     * updateOrInsert in store() already makes submitting twice an edit rather
     * than a duplicate; without this the second visit would show empty boxes and
     * silently overwrite good answers with defaults.
     *
     * @return array{scores: array, remarks: array, reflections: array}
     */
    public static function existingAnswers(int $groupId, int $evaluatorPk): array
    {
        $scores = [];
        $rows = DB::table('peer_scores')
            ->where('group_id', $groupId)
            ->where('evaluator_id', $evaluatorPk)
            ->get(['member_id', 'column_id', 'score']);

        foreach ($rows as $row) {
            $scores[(int) $row->member_id][(int) $row->column_id] = $row->score;
        }

        return [
            'scores' => $scores,
            'remarks' => DB::table('peer_evaluation_remarks')
                ->where('group_id', $groupId)
                ->where('evaluator_id', $evaluatorPk)
                ->pluck('remarks', 'member_id')
                ->toArray(),
            'reflections' => DB::table('reflection_responses')
                ->where('group_id', $groupId)
                ->where('evaluator_id', $evaluatorPk)
                ->pluck('description', 'field_id')
                ->toArray(),
        ];
    }
}
