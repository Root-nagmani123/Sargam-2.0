<?php

namespace App\Services\Timetable;

use Illuminate\Support\Facades\DB;

/**
 * "Which timetable sessions belong to this faculty", in one place.
 *
 * The dashboard's Total Sessions card and the Timetable Session Report have to
 * agree — a card that says 27 must open a report showing 27 — so both count
 * through here rather than each writing its own predicate.
 *
 * It is also the server-side lock: a faculty user may only ever see their own
 * sessions, so the report forces {@see lockedFacultyPk()} over whatever
 * faculty_pk the request carries instead of trusting the filter dropdown.
 */
class FacultySessionScope
{
    /**
     * The roles a faculty can hold in a session, as stored in
     * timetable.faculty_details[*].role and offered by the Add/Edit Event form.
     */
    public const ROLES = ['Teaching', 'Sectional', 'Administration'];

    public const ROLE_TEACHING = 'Teaching';

    /**
     * Not a stored role: a supporting faculty is one listed in the legacy
     * internal_faculty column while someone else holds the session in
     * faculty_master. 133 sessions across 25 faculties are only reachable that
     * way, so the report offers it as a fourth choice on the Role filter.
     */
    public const ROLE_SUPPORTING = 'Supporting';

    /** What the report's Role filter offers — the stored roles plus Supporting. */
    public const FILTER_ROLES = ['Teaching', 'Sectional', 'Administration', 'Supporting'];

    /** A role string the report/card may filter by, or null for "any role". */
    public static function normaliseRole(?string $role): ?string
    {
        return in_array($role, self::FILTER_ROLES, true) ? $role : null;
    }

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
     *
     * @return array{0: string, 1: array<int, string>} [sql, bindings]
     */
    public static function facultyMasterPredicate(int $facultyPk, string $alias = 't'): array
    {
        $column = $alias === '' ? 'faculty_master' : $alias . '.faculty_master';
        $json = "COALESCE(NULLIF({$column}, ''), '[]')";

        return [
            "((JSON_VALID({$json}) AND (JSON_CONTAINS({$json}, ?) OR JSON_CONTAINS({$json}, ?))) OR {$column} = ?)",
            ['"' . $facultyPk . '"', (string) $facultyPk, (string) $facultyPk],
        ];
    }

    public static function applyFaculty($query, int $facultyPk, string $alias = 't'): void
    {
        [$sql, $bindings] = self::facultyMasterPredicate($facultyPk, $alias);

        $query->whereRaw($sql, $bindings);
    }

    /**
     * The other column a faculty can appear in: internal_faculty, the supporting
     * faculty on an event. Always a JSON array here (832 rows) or empty (46),
     * with elements as strings or numbers — the same two shapes faculty_master
     * carries, so the same pair of JSON_CONTAINS checks.
     *
     * @return array{0: string, 1: array<int, string>} [sql, bindings]
     */
    public static function internalFacultyPredicate(int $facultyPk, string $alias = 't'): array
    {
        $column = $alias === '' ? 'internal_faculty' : $alias . '.internal_faculty';
        $json = "COALESCE(NULLIF({$column}, ''), '[]')";

        return [
            "(JSON_VALID({$json}) AND (JSON_CONTAINS({$json}, ?) OR JSON_CONTAINS({$json}, ?)))",
            ['"' . $facultyPk . '"', (string) $facultyPk],
        ];
    }

    /**
     * "This faculty is on this session in ANY capacity" — teaching it, or
     * supporting it. What the Timetable Session Report filters a chosen faculty
     * by, so its per-faculty count matches the sessions they actually attend.
     *
     * The dashboard's Total Sessions card deliberately does NOT use this: it
     * counts sessions the faculty TEACHES, and {@see applyFaculty()} stays the
     * narrower rule it is built on.
     */
    public static function applyFacultyWithSupporting($query, int $facultyPk, string $alias = 't'): void
    {
        [$masterSql, $masterBindings] = self::facultyMasterPredicate($facultyPk, $alias);
        [$internalSql, $internalBindings] = self::internalFacultyPredicate($facultyPk, $alias);

        $query->whereRaw("({$masterSql} OR {$internalSql})", array_merge($masterBindings, $internalBindings));
    }

    /**
     * Supporting only: in internal_faculty and NOT in faculty_master.
     *
     * The "not in faculty_master" half matters — on events written by the current
     * form the two columns hold the same people, so without it every teaching
     * session would read as supporting too.
     *
     * @return array{0: string, 1: array<int, string>} [sql, bindings]
     */
    public static function supportingOnlyPredicate(int $facultyPk, string $alias = 't'): array
    {
        [$masterSql, $masterBindings] = self::facultyMasterPredicate($facultyPk, $alias);
        [$internalSql, $internalBindings] = self::internalFacultyPredicate($facultyPk, $alias);

        return [
            "({$internalSql} AND NOT {$masterSql})",
            array_merge($internalBindings, $masterBindings),
        ];
    }

    /**
     * Restrict to sessions held in a given role — the report's Role filter, and
     * the Total Sessions card's "only role will be Teaching".
     *
     * With $facultyPk the role must be THAT faculty's ("sessions I teach");
     * without it, any faculty in the session holding the role is enough, which
     * is what an admin filtering the whole report means by "Teaching sessions".
     *
     * Roles live in faculty_details, and 553 of the 878 sessions predate that
     * column. Those legacy rows count as Teaching — the same fallback
     * {@see expected_feedback_count_sql()} and the student feedback listing
     * already make — so a faculty whose sessions are all legacy still sees them
     * rather than a zero.
     */
    public static function applyRole($query, string $role, ?int $facultyPk = null, string $alias = 't'): void
    {
        // Supporting is not a stored role — it is the internal_faculty column.
        if ($role === self::ROLE_SUPPORTING) {
            self::applySupportingRole($query, $facultyPk, $alias);

            return;
        }

        $details = $alias === '' ? 'faculty_details' : $alias . '.faculty_details';

        // JSON_VALID(NULL) is NULL, not 0, so it cannot be negated directly.
        $hasDetails = "COALESCE(JSON_VALID({$details}), 0) = 1";
        $noDetails = "COALESCE(JSON_VALID({$details}), 0) = 0";

        if ($facultyPk !== null) {
            $inDetails = "JSON_CONTAINS({$details}, JSON_OBJECT('faculty_pk', ?, 'role', ?))";
            $bindings = [$facultyPk, $role];
            [$legacySql, $legacyBindings] = self::facultyMasterPredicate($facultyPk, $alias);
        } else {
            $inDetails = "JSON_SEARCH({$details}, 'one', ?, NULL, '$[*].role') IS NOT NULL";
            $bindings = [$role];
            [$legacySql, $legacyBindings] = ['1 = 1', []];
        }

        $sql = "({$hasDetails} AND {$inDetails})";

        if ($role === self::ROLE_TEACHING) {
            $sql .= " OR ({$noDetails} AND {$legacySql})";
            $bindings = array_merge($bindings, $legacyBindings);
        }

        $query->whereRaw("({$sql})", $bindings);
    }

    /**
     * Role = Supporting.
     *
     * With a faculty chosen: the sessions THEY support but do not hold. Without
     * one: sessions that carry a supporting faculty at all, which is an EXISTS
     * over faculty_master rather than a JSON path, because the column stores pks
     * and "is this pk absent from the other column" cannot be asked of a path.
     */
    private static function applySupportingRole($query, ?int $facultyPk, string $alias): void
    {
        if ($facultyPk !== null) {
            [$sql, $bindings] = self::supportingOnlyPredicate($facultyPk, $alias);

            $query->whereRaw($sql, $bindings);

            return;
        }

        $internal = $alias === '' ? 'internal_faculty' : $alias . '.internal_faculty';
        $master = $alias === '' ? 'faculty_master' : $alias . '.faculty_master';
        $internalJson = "COALESCE(NULLIF({$internal}, ''), '[]')";
        $masterJson = "COALESCE(NULLIF({$master}, ''), '[]')";

        $query->whereRaw(
            "EXISTS (
                SELECT 1 FROM faculty_master fm_support
                WHERE JSON_VALID({$internalJson})
                  AND (JSON_CONTAINS({$internalJson}, CONCAT('\"', fm_support.pk, '\"'))
                    OR JSON_CONTAINS({$internalJson}, CAST(fm_support.pk AS CHAR)))
                  AND NOT (
                      (JSON_VALID({$masterJson}) AND (
                          JSON_CONTAINS({$masterJson}, CONCAT('\"', fm_support.pk, '\"'))
                          OR JSON_CONTAINS({$masterJson}, CAST(fm_support.pk AS CHAR))
                      ))
                      OR ({$master} REGEXP '^[0-9]+$' AND CAST({$master} AS UNSIGNED) = fm_support.pk)
                  )
            )"
        );
    }

    /**
     * The report's Active / Archive course toggle. Lifted from the report so the
     * card can count on the same footing the report opens with, instead of
     * counting sessions the landing page then filters away.
     *
     * Any other mode — 'all', the tab the Total Sessions card lands on — leaves
     * the query unfiltered, so active and ended courses are counted together.
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
    public static function countFor(int $facultyPk, string $courseMode = 'active', ?string $role = null): int
    {
        $query = DB::table('timetable as t')
            ->leftJoin('course_master as c', 't.course_master_pk', '=', 'c.pk');

        self::applyCourseMode($query, $courseMode);
        self::applyFaculty($query, $facultyPk);

        if ($role !== null) {
            self::applyRole($query, $role, $facultyPk);
        }

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
