<?php

namespace App\Support;

/**
 * The Active / Archived pills shared by the Peer Evaluation admin grids.
 *
 * "Active" here means the course is RUNNING RIGHT NOW - enabled, already started
 * and not yet finished. A course whose start date is still in the future is
 * upcoming, not active, and must not appear under the Active pill or in the
 * Filter dropdowns that follow it.
 *
 * That is deliberately stricter than CourseMaster::scopeActiveRunning(), which
 * only checks `active_inactive = 1 AND end_date >= today` and so counts upcoming
 * courses as active. The ongoing-vs-upcoming split written here is the same one
 * the rest of the app uses (DashboardController, MemoNoticeController): ongoing
 * is `start_year <= today`, upcoming is `start_year > today`.
 *
 * The two pills are written as exact complements - archive() is the literal
 * De Morgan negation of active() - so every course lands in exactly one of them.
 * Upcoming courses therefore fall under Archived rather than into a gap; if they
 * fell into neither, an event created ahead of time on a not-yet-started course
 * would be invisible on both tabs and unreachable from the grid.
 *
 * One class because there are four consumers (Manage Events, Manage Reflection
 * Fields, Evaluation Reports, Manage Evaluation Columns) and two shapes of query,
 * and they must not drift:
 *
 *   forCourses()  - the query IS over course_master        (direct conditions)
 *   forRelated()  - the query is over a peer_* table       (correlated EXISTS)
 *
 * ⚠️ These are NOT interchangeable. Inside a subquery whose FROM is also
 * course_master, the inner table shadows the outer one, so the correlation
 * `course_master.pk = course_master.pk` is a tautology and EVERY course matches
 * BOTH pills. That bug shipped once already - keep the two entry points separate.
 */
final class PeerCourseStatusScope
{
    public const ACTIVE = 'active';
    public const ARCHIVE = 'archive';

    /** Anything that isn't explicitly 'archive' means 'active'. */
    public static function normalise($status): string
    {
        return ((string) $status === self::ARCHIVE) ? self::ARCHIVE : self::ACTIVE;
    }

    /**
     * Scope a query over course_master itself.
     */
    public static function forCourses($query, $status)
    {
        return self::normalise($status) === self::ARCHIVE
            ? self::archive($query)
            : self::active($query);
    }

    /**
     * Scope a query over a table that merely REFERENCES a course.
     *
     * @param  string  $courseIdColumn  qualified column, e.g. 'peer_events.course_id'
     * @param  bool    $includeUnscoped  also match rows whose course_id is NULL.
     *        Reflection fields use this: a field with no course is GLOBAL - it is
     *        on every form regardless of which courses are running - so hiding it
     *        from both pills would make it unreachable from the grid entirely.
     */
    public static function forRelated($query, $status, string $courseIdColumn, bool $includeUnscoped = false)
    {
        $archived = self::normalise($status) === self::ARCHIVE;

        $exists = function ($outer) use ($archived, $courseIdColumn) {
            $outer->whereExists(function ($sub) use ($archived, $courseIdColumn) {
                $sub->selectRaw('1')
                    ->from('course_master')
                    ->whereColumn('course_master.pk', $courseIdColumn);

                if ($archived) {
                    self::archive($sub);
                } else {
                    self::active($sub);
                }
            });
        };

        if (! $includeUnscoped) {
            return $query->where($exists);
        }

        return $query->where(function ($outer) use ($exists, $courseIdColumn) {
            $exists($outer);
            $outer->orWhereNull($courseIdColumn);
        });
    }

    /**
     * Enabled AND already started AND not yet finished.
     *
     * Columns are qualified because forRelated() applies this inside a subquery
     * that sits under a peer_* outer query; unqualified names there would be
     * resolved against whichever table happens to own them.
     */
    private static function active($query)
    {
        $today = now()->toDateString();

        return $query->where('course_master.active_inactive', 1)
            ->whereNotNull('course_master.start_year')
            ->where('course_master.start_year', '<=', $today)
            ->whereNotNull('course_master.end_date')
            ->where('course_master.end_date', '>=', $today);
    }

    /**
     * The exact complement of active(): disabled courses, upcoming ones, finished
     * ones, and anything missing a date. Written as the negation rather than as
     * its own condition so the two can never overlap or leave a course in neither.
     */
    private static function archive($query)
    {
        $today = now()->toDateString();

        return $query->where(function ($q) use ($today) {
            $q->where('course_master.active_inactive', '!=', 1)
                ->orWhereNull('course_master.active_inactive')
                ->orWhereNull('course_master.start_year')
                ->orWhere('course_master.start_year', '>', $today)
                ->orWhereNull('course_master.end_date')
                ->orWhere('course_master.end_date', '<', $today);
        });
    }
}
