<?php

namespace App\Support;

use App\Models\CourseMaster;

/**
 * The Active / Archived pills shared by the Peer Evaluation admin grids.
 *
 * "Archived" means here exactly what it means on Course Master, so the rule is
 * CourseMaster's own scopeActiveRunning() / scopeArchived() and nothing else.
 * Those two are written as exact complements (2 + 143 = 145 today), so a course
 * lands in exactly one pill and none can fall through a gap.
 *
 * One class because there are now two consumers (Manage Events, Manage Reflection
 * Fields) and three shapes of query, and they must not drift:
 *
 *   forCourses()  - the query IS over course_master        (direct scopes)
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
            ? $query->archived()
            : $query->activeRunning();
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

                $model = new CourseMaster();

                if ($archived) {
                    $model->scopeArchived($sub);
                } else {
                    $model->scopeActiveRunning($sub);
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
}
