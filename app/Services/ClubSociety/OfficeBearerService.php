<?php

namespace App\Services\ClubSociety;

use Illuminate\Support\Facades\DB;

/**
 * The single definition of "who won an election".
 *
 * Both the Election Drive result screen and the Officer Bearers listing build on
 * this, so the two can never disagree about who was elected.
 *
 * The rule (no design was supplied for it — see the banner on the result page):
 * among a post's ACCEPTED nominees, rank by the number of distinct people who
 * nominated them, and elect the top N, where N is that role's `number_of_post`
 * from the Club/ Society Role Programme Mapping. A role with no mapping has
 * N = 0 and therefore elects nobody.
 */
class OfficeBearerService
{
    /**
     * Public URL for a student photo, or null when the file is not on disk.
     *
     * `student_master.photo_path` holds a bare filename on the `public` disk,
     * and a good number of rows point at files that were never copied over.
     * Resolving here means the markup simply omits the <img> instead of firing
     * a request that is certain to 404 on every redraw.
     */
    public static function photoUrl(?string $photoPath): ?string
    {
        $photoPath = ltrim(trim((string) $photoPath), '/');

        if ($photoPath === '') {
            return null;
        }

        return file_exists(public_path('storage/' . $photoPath))
            ? asset('storage/' . $photoPath)
            : null;
    }

    /**
     * Accepted nominees for published-result elections, each with its vote count,
     * the seats available for that post, and its rank within the post.
     *
     * @param  array  $filters  status (active|archive), course_master_pk,
     *                          club_society_master_pk, election_drive_pk,
     *                          require_published (bool, default true)
     */
    public static function rankedQuery(array $filters = [])
    {
        $requirePublished = $filters['require_published'] ?? true;

        // 1. One row per (drive, society, role, nominee) with votes + seats.
        $aggregated = DB::table('election_drive as e')
            ->join('nomination_drive as nd', 'nd.pk', '=', 'e.nomination_drive_pk')
            ->join('course_master as c', 'c.pk', '=', 'nd.course_master_pk')
            // Only societies actually polling in this election count.
            ->join('election_drive_society as eds', function ($join) {
                $join->on('eds.election_drive_pk', '=', 'e.pk');
            })
            ->join('nomination as n', function ($join) {
                $join->on('n.nomination_drive_pk', '=', 'nd.pk')
                     ->on('n.club_society_master_pk', '=', 'eds.club_society_master_pk');
            })
            ->join('club_society_master as cs', 'cs.pk', '=', 'n.club_society_master_pk')
            ->join('club_society_role_master as r', 'r.pk', '=', 'n.club_society_role_master_pk')
            ->leftJoin('student_master as st', 'st.pk', '=', 'n.nominee_student_pk')
            // Seats for the post come from the role programme mapping.
            ->leftJoin('club_society_role_programme_mapping as rpm', function ($join) {
                $join->on('rpm.course_master_pk', '=', 'nd.course_master_pk')
                     ->on('rpm.club_society_master_pk', '=', 'n.club_society_master_pk')
                     ->on('rpm.club_society_role_master_pk', '=', 'n.club_society_role_master_pk');
            })
            ->where('e.active_inactive', 1)
            ->where('n.status', 'accepted')
            ->select([
                'e.pk as election_drive_pk',
                'e.election_drive_name',
                'nd.course_master_pk',
                DB::raw("COALESCE(NULLIF(c.couse_short_name, ''), c.course_name) as course_name"),
                'n.club_society_master_pk',
                'cs.club_society_name',
                'n.club_society_role_master_pk',
                'r.club_society_role_name as role_name',
                'n.nominee_student_pk',
                'st.display_name as officer_bearer_name',
                'st.generated_OT_code as ot_code',
                'st.photo_path',
                DB::raw('COUNT(DISTINCT n.nominated_by_student_pk) as votes'),
                DB::raw('COALESCE(MAX(rpm.number_of_post), 0) as seats'),
            ])
            ->groupBy(
                'e.pk', 'e.election_drive_name', 'nd.course_master_pk', 'c.couse_short_name', 'c.course_name',
                'n.club_society_master_pk', 'cs.club_society_name',
                'n.club_society_role_master_pk', 'r.club_society_role_name',
                'n.nominee_student_pk', 'st.display_name', 'st.generated_OT_code', 'st.photo_path'
            );

        if ($requirePublished) {
            $aggregated->where('e.result_status', 1);
        }

        // Active / Archived mirrors the COURSE's own status, as elsewhere.
        if (($filters['status'] ?? null) !== null) {
            $aggregated->where('c.active_inactive', $filters['status'] === 'archive' ? 0 : 1);
        }
        if (filled($filters['course_master_pk'] ?? null)) {
            $aggregated->where('nd.course_master_pk', $filters['course_master_pk']);
        }
        if (filled($filters['club_society_master_pk'] ?? null)) {
            $aggregated->where('n.club_society_master_pk', $filters['club_society_master_pk']);
        }
        if (filled($filters['election_drive_pk'] ?? null)) {
            $aggregated->where('e.pk', $filters['election_drive_pk']);
        }

        // 2. Rank within each (drive, society, role). MySQL 8 window function —
        //    ties break on nominee pk so the order is stable between requests.
        return DB::query()
            ->fromSub($aggregated, 't')
            ->select([
                't.*',
                DB::raw('ROW_NUMBER() OVER (
                    PARTITION BY t.election_drive_pk, t.club_society_master_pk, t.club_society_role_master_pk
                    ORDER BY t.votes DESC, t.nominee_student_pk ASC
                ) as rank_in_post'),
            ]);
    }

    /**
     * Only the winners: rank within the seats available. A post with no seats
     * configured (seats = 0) elects nobody, which is deliberate.
     */
    public static function electedQuery(array $filters = [])
    {
        return DB::query()
            ->fromSub(self::rankedQuery($filters), 'ob')
            ->whereColumn('ob.rank_in_post', '<=', 'ob.seats')
            ->where('ob.seats', '>', 0);
    }
}
