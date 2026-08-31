<?php

namespace App\Services\Timetable;

use Illuminate\Support\Facades\DB;

/**
 * "Which timetable sessions belong to this faculty", in one place.
 *
 * The dashboard's Total Sessions card and the Timetable Session Report have to
 * agree — a card that says 79 must open a report showing 79 — so both count
 * through here rather than each writing its own predicate.
 *
 * It is also the server-side lock: a faculty user may only ever see their own
 * sessions, so the report forces {@see lockedFacultyPk()} over whatever
 * faculty_pk the request carries instead of trusting the filter dropdown.
 */
class FacultySessionScope
{
    /**
     * timetable.faculty_master holds a JSON array of faculty PKs as STRINGS
     * (`["84"]`, the shape the Add Event form writes), but 47 legacy rows hold a
     * bare scalar (`84`) instead, and a few carry numbers rather than strings.
     * All three have to match, so all three are checked.
     *
     * Deliberately NOT `JSON_CONTAINS(col, CAST(? AS JSON))`, which is what the
     * report used to do: MariaDB has no CAST ... AS JSON, so that filter threw a
     * SQL syntax error the moment anyone picked a faculty. JSON_VALID guards the
     * JSON_CONTAINS calls, because JSON_CONTAINS on a non-JSON value errors out.
     */
    public static function applyFaculty($query, int $facultyPk, string $alias = 't'): void
    {
        $column = $alias === '' ? 'faculty_master' : $alias . '.faculty_master';
        $json = "COALESCE(NULLIF({$column}, ''), '[]')";

        $query->whereRaw(
            "((JSON_VALID({$json}) AND (JSON_CONTAINS({$json}, ?) OR JSON_CONTAINS({$json}, ?))) OR {$column} = ?)",
            ['"' . $facultyPk . '"', (string) $facultyPk, (string) $facultyPk]
        );
    }

    /**
     * The report's Active / Archive course toggle. Lifted from the report so the
     * card can count on the same footing the report opens with (`active`),
     * instead of counting sessions the landing page then filters away.
     */
    public static function applyCourseMode($query, ?string $mode, string $courseAlias = 'c'): void
    {
        $currentDate = now()->toDateString();

        if ($mode === 'active') {
            $query->where($courseAlias . '.active_inactive', 1)
                ->where(function ($q) use ($courseAlias, $currentDate) {
                    $q->whereNull($courseAlias . '.end_date')
                        ->orWhereDate($courseAlias . '.end_date', '>=', $currentDate);
                });
        } elseif ($mode === 'archive') {
            $query->where($courseAlias . '.active_inactive', 1)
                ->whereDate($courseAlias . '.end_date', '<', $currentDate);
        }
    }

    /**
     * How many sessions this faculty has — the Total Sessions card's number.
     * Built on the same joins and filters the report uses, so clicking the card
     * lands on a report whose row count is this number.
     */
    public static function countFor(int $facultyPk, string $courseMode = 'active'): int
    {
        $query = DB::table('timetable as t')
            ->leftJoin('course_master as c', 't.course_master_pk', '=', 'c.pk');

        self::applyCourseMode($query, $courseMode);
        self::applyFaculty($query, $facultyPk);

        return $query->count();
    }

    /**
     * The faculty this viewer is confined to, or null when they may see everyone.
     *
     * Anyone on the faculty portal — Internal/Guest Faculty, and CC/ACC, who are
     * faculty too — is confined to their own sessions. Super Admin is not, so the
     * admin-side report keeps working as it does today.
     */
    public static function lockedFacultyPk(): ?int
    {
        if (! is_faculty_portal_user() || hasRole('Super Admin')) {
            return null;
        }

        return get_auth_faculty_master_pk();
    }
}
