<?php

namespace App\Support;

use App\Models\PeerEvent;
use App\Models\PeerGroup;
use Illuminate\Support\Facades\DB;

/**
 * Peer Evaluation's groups come from Course Group Mapping.
 *
 * `group_type_master_course_master_map` is the institution's real group list (212
 * active rows), managed by the Course Group Mapping screen. Every group picker in
 * the peer module offers rows from THERE, keyed by that table's `pk`.
 *
 * peer_groups is not replaced by it - it still owns the settings the mapping table
 * has nowhere to put (max_marks, buffer_marks, is_form_active) and now carries
 * `group_map_pk` pointing back at the mapping row. link() turns a picked mapping
 * group into the peer_groups row everything else already references.
 *
 * ⚠️ THE COLUMN NAMES IN THAT TABLE LIE:
 *     type_name   -> course_group_type_master.pk   (a varchar holding an id)
 *     course_name -> course_master.pk              (a varchar holding an id)
 *     group_name  -> the only one that is actually a name
 * Hence the CASTs below; comparing them as strings silently matches nothing.
 */
final class PeerGroupSource
{
    public const TABLE = 'group_type_master_course_master_map';

    /**
     * How a peer_group_members row resolves to the login that scored as them.
     *
     * Kept here so the reports grid and anything else needing it share one
     * definition - including the COLLATE, without which MySQL refuses the join.
     */
    public const EVALUATOR_JOIN =
        'user_credentials.user_name COLLATE utf8mb4_unicode_ci = peer_group_members.user_id COLLATE utf8mb4_unicode_ci';

    /**
     * Mapping groups, optionally narrowed to a course.
     *
     * Labelled "Group - Type" because the names alone are single letters ("A",
     * "B", "C") and are only meaningful beside their type.
     *
     * @return \Illuminate\Support\Collection<int, object{pk:int,label:string}>
     */
    public static function options($courseId = null)
    {
        $query = DB::table(self::TABLE . ' as m')
            ->leftJoin('course_group_type_master as t', 't.pk', '=', DB::raw('CAST(m.type_name AS UNSIGNED)'))
            ->where('m.active_inactive', 1)
            ->select([
                'm.pk',
                'm.group_name',
                't.type_name as type_label',
            ]);

        if (filled($courseId)) {
            $query->whereRaw('CAST(m.course_name AS UNSIGNED) = ?', [(int) $courseId]);
        }

        return $query->orderBy('t.type_name')->orderBy('m.group_name')->get()
            ->map(function ($row) {
                return (object) [
                    'pk' => (int) $row->pk,
                    'label' => self::label($row->group_name, $row->type_label),
                ];
            });
    }

    /**
     * "Group - Type", collapsed to one when the two are the same - several rows
     * are literally named after their type ("Full Group"), and "Full Group - Full
     * Group" reads like a bug.
     */
    public static function label($groupName, $typeLabel): string
    {
        $name = trim((string) $groupName) ?: 'Unnamed group';
        $type = trim((string) $typeLabel);

        if ($type === '' || strcasecmp($name, $type) === 0) {
            return $name;
        }

        return $name . ' - ' . $type;
    }

    /** One mapping row, or null. */
    public static function find($groupMapPk)
    {
        return DB::table(self::TABLE . ' as m')
            ->leftJoin('course_group_type_master as t', 't.pk', '=', DB::raw('CAST(m.type_name AS UNSIGNED)'))
            ->where('m.pk', $groupMapPk)
            ->select([
                'm.pk',
                'm.group_name',
                DB::raw('CAST(m.course_name AS UNSIGNED) as course_pk'),
                't.type_name as type_label',
            ])
            ->first();
    }

    /**
     * The peer_groups row for a picked mapping group, creating it on first use.
     *
     * Name and course come from the mapping, never from the form - the picker is
     * only a picker. Settings (max_marks, buffer_marks) keep whatever they had, or
     * take the table defaults on creation.
     */
    public static function link($eventId, $groupMapPk): ?PeerGroup
    {
        $mapping = self::find($groupMapPk);

        if (! $mapping) {
            return null;
        }

        $event = filled($eventId) ? PeerEvent::find($eventId) : null;
        $label = self::label($mapping->group_name, $mapping->type_label);

        $group = PeerGroup::firstOrNew([
            'event_id' => $event?->id,
            'group_map_pk' => (int) $mapping->pk,
        ]);

        $group->group_name = $label;
        // The event's course wins when there is one: an event already belongs to a
        // course, and a mapping row with a stale course_name shouldn't override it.
        $group->course_id = $event?->course_id ?: $mapping->course_pk;
        $group->is_active = true;
        $group->max_marks = $group->max_marks ?? 10;
        $group->buffer_marks = $group->buffer_marks ?? 100;
        $group->save();

        self::syncMembers($group);

        return $group;
    }

    /**
     * Bring the group's evaluated OTs in line with the mapping's real students.
     *
     * Additive by design: it inserts students who aren't there yet and refreshes
     * names/codes, but never deletes a member - peer_scores and
     * peer_evaluation_remarks reference peer_group_members.id, so removing one
     * would strand recorded evaluations.
     *
     * `user_id` is the student's LOGIN HANDLE, matched against
     * user_credentials.user_name (50/50 on live data) - not user_credentials.user_id,
     * which is a numeric column that matches barely a quarter of them.
     *
     * ⚠️ Those two columns have DIFFERENT COLLATIONS
     * (peer_group_members.user_id is utf8mb4_unicode_ci, user_credentials.user_name
     * is utf8mb4_0900_ai_ci), so joining them without an explicit COLLATE throws
     * "Illegal mix of collations". See PeerGroupSource::EVALUATOR_JOIN.
     *
     * @return int how many members the group has afterwards
     */
    public static function syncMembers(PeerGroup $group): int
    {
        if (! $group->group_map_pk) {
            return $group->members()->count();
        }

        $students = DB::table('student_course_group_map as m')
            ->join('student_master as s', 's.pk', '=', 'm.student_master_pk')
            ->where('m.group_type_master_course_master_map_pk', $group->group_map_pk)
            ->where('m.active_inactive', 1)
            ->orderBy('s.generated_OT_code')
            ->get(['s.pk', 's.first_name', 's.middle_name', 's.last_name', 's.generated_OT_code', 's.user_id']);

        if ($students->isEmpty()) {
            return $group->members()->count();
        }

        $courseName = $group->course_id
            ? DB::table('course_master')->where('pk', $group->course_id)->value('course_name')
            : null;
        $eventName = $group->event_id
            ? DB::table('peer_events')->where('id', $group->event_id)->value('event_name')
            : null;

        foreach ($students as $student) {
            $name = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                $student->first_name, $student->middle_name, $student->last_name,
            ]))));

            DB::table('peer_group_members')->updateOrInsert(
                [
                    'group_id' => $group->id,
                    'member_pk' => $student->pk,
                ],
                [
                    'user_name' => $name ?: 'Unnamed',
                    'ot_code' => $student->generated_OT_code,
                    'user_id' => $student->user_id,
                    'course_name' => $courseName,
                    'event_name' => $eventName,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return $group->members()->count();
    }
}
