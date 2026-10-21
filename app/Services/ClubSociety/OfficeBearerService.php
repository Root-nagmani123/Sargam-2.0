<?php

namespace App\Services\ClubSociety;

use Illuminate\Support\Facades\DB;

/**
 * The single definition of "who won an election".
 *
 * Both the Election Drive result screen and the Officer Bearers listing build on
 * this, so the two can never disagree about who was elected.
 *
 * The rule (tie-break confirmed by the module owner, see
 * docs/deploy-notes-club-society.md §0; the rest is as implemented):
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
            // A random tie-break draw, when one was run for this post (F-007).
            ->leftJoin('election_drive_tie_pick as tp', function ($join) {
                $join->on('tp.election_drive_pk', '=', 'e.pk')
                     ->on('tp.club_society_master_pk', '=', 'n.club_society_master_pk')
                     ->on('tp.club_society_role_master_pk', '=', 'n.club_society_role_master_pk')
                     ->on('tp.nominee_student_pk', '=', 'n.nominee_student_pk');
            })
            // Seats for the post come from the role programme mapping.
            ->leftJoin('club_society_role_programme_mapping as rpm', function ($join) {
                $join->on('rpm.course_master_pk', '=', 'nd.course_master_pk')
                     ->on('rpm.club_society_master_pk', '=', 'n.club_society_master_pk')
                     ->on('rpm.club_society_role_master_pk', '=', 'n.club_society_role_master_pk');
            })
            ->where('e.active_inactive', 1)
            ->where('n.status', 'accepted')
            // A nominee who withdrew from the post is out of it, even if some
            // nominators' rows still read accepted (PR #340 F-003).
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('nomination as nw')
                  ->whereColumn('nw.nomination_drive_pk', 'n.nomination_drive_pk')
                  ->whereColumn('nw.club_society_master_pk', 'n.club_society_master_pk')
                  ->whereColumn('nw.club_society_role_master_pk', 'n.club_society_role_master_pk')
                  ->whereColumn('nw.nominee_student_pk', 'n.nominee_student_pk')
                  ->where('nw.status', 'withdrawn');
            })
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
                DB::raw('MAX(CASE WHEN tp.pk IS NULL THEN 0 ELSE 1 END) as tie_picked'),
                DB::raw('MAX(tp.tied_nominee_pks) as tie_candidates'),
            ])
            ->groupBy(
                'e.pk', 'e.election_drive_name', 'nd.course_master_pk', 'c.couse_short_name', 'c.course_name',
                'n.club_society_master_pk', 'cs.club_society_name',
                'n.club_society_role_master_pk', 'r.club_society_role_name',
                'n.nominee_student_pk', 'st.display_name', 'st.generated_OT_code', 'st.photo_path'
            );

        // Both switches: a result is only live while its election is (F-006).
        if ($requirePublished) {
            $aggregated->where('e.result_status', 1)
                       ->where('e.election_publish_status', 1);
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

        // 2. Rank within each (drive, society, role). MySQL 8 window function.
        //    Equal votes: a nominee seated by the random draw goes first; with no
        //    draw, ties fall back to lower nominee pk (F-007), stable between requests.
        return DB::query()
            ->fromSub($aggregated, 't')
            ->select([
                't.*',
                DB::raw('ROW_NUMBER() OVER (
                    PARTITION BY t.election_drive_pk, t.club_society_master_pk, t.club_society_role_master_pk
                    ORDER BY t.votes DESC, t.tie_picked DESC, t.nominee_student_pk ASC
                ) as rank_in_post'),
            ]);
    }

    /**
     * The nominees tied at the last seat of one post, from its ranked rows.
     *
     * Returns null when there is no tie. Otherwise `votes` is the tied count,
     * `rows` the tied nominees and `seats` how many of them can be seated (the
     * seats left after everyone with more votes). Decided on votes alone, so a
     * post whose tie was already drawn still reports it.
     *
     * @param  \Illuminate\Support\Collection  $rows  one post's rows from rankedQuery()
     * @return array{votes: int, rows: \Illuminate\Support\Collection, seats: int}|null
     */
    public static function tieAtLastSeat($rows): ?array
    {
        $rows  = $rows->sortByDesc(fn ($r) => (int) $r->votes)->values();
        $seats = (int) ($rows->first()->seats ?? 0);

        if ($seats <= 0 || $rows->count() <= $seats
            || (int) $rows[$seats - 1]->votes !== (int) $rows[$seats]->votes) {
            return null;
        }

        $votes = (int) $rows[$seats - 1]->votes;
        $above = $rows->filter(fn ($r) => (int) $r->votes > $votes)->count();

        return [
            'votes' => $votes,
            'rows'  => $rows->filter(fn ($r) => (int) $r->votes === $votes)->values(),
            'seats' => $seats - $above,
        ];
    }

    /**
     * Whether the saved random draw still decides this tie (F-011, F-012).
     *
     * Valid while the drawn nominees still fill the tied seats and every nominee
     * now in the tie took part in the draw. A withdrawal outside the tie, or of a
     * tied nominee who was not drawn, leaves it valid; a withdrawn pick, or a tie
     * that now includes someone who was not in the draw, makes it stale.
     *
     * @param  array{votes: int, rows: \Illuminate\Support\Collection, seats: int}  $tie  from tieAtLastSeat()
     */
    public static function drawStillValid(array $tie): bool
    {
        $picked = $tie['rows']->filter(fn ($r) => (int) ($r->tie_picked ?? 0) === 1);
        if ($picked->count() !== $tie['seats']) {
            return false;
        }

        $drawnFrom = array_map('intval', explode(',', (string) $picked->first()->tie_candidates));

        return $tie['rows']->every(fn ($r) => in_array((int) $r->nominee_student_pk, $drawnFrom, true));
    }

    /**
     * Flag a tie at the last seat on one post's ranked rows (ordered by rank).
     *
     * Sets `tied` on each tied nominee and `tie_drawn` on every row once the
     * random draw has been run for the post. Who is elected is decided by the
     * ranking alone (rank_in_post); this only feeds the result screen.
     *
     * @param  \Illuminate\Support\Collection  $rows  one post's rows from rankedQuery()
     */
    public static function markTies($rows)
    {
        $rows = $rows->values();
        $tie  = self::tieAtLastSeat($rows);
        $tiedVotes = $tie['votes'] ?? null;
        $drawn = $tie !== null && self::drawStillValid($tie);

        return $rows->each(function ($row) use ($tiedVotes, $drawn) {
            $row->tied      = $tiedVotes !== null && (int) $row->votes === $tiedVotes;
            $row->tie_drawn = $drawn;
        });
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
