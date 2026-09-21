<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureMemberRecordAccess;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Closure evidence for PR #309 condition 4 (finding F-047).
 *
 * F-047: the profile dropdown's "Edit Profile" guard calls
 * EnsureMemberRecordAccess::ownedMemberPk() on EVERY admin page render, and
 * that resolves to a user_credentials x employee_master join. The same decision
 * is also asked by the member grid feed and again inside
 * authorizeMemberRecord(), so one page could ask the database the same question
 * several times.
 *
 * The remedy in place memoises the verdict in the container, keyed on the
 * credential pk and the requested pk. The condition's closure evidence is
 * "a rendered admin page showing the query count unchanged from the pre-change
 * baseline" - i.e. the guard must cost at most ONE join per request no matter
 * how often it is consulted. That is what this file measures.
 *
 * NON-VACUITY NOTE. The three cheap guards inside ownsMemberRecord() return
 * early for most credentials (no user_id, or user_category not 'E'), and for
 * those the join never runs at all - so "0 queries" would prove nothing. These
 * tests therefore assert the join runs EXACTLY once on the first ask, and only
 * then that repeating the ask adds none. An actor that cannot reach the join is
 * skipped rather than silently passing.
 */
class HeaderOwnershipMemoTest extends TestCase
{
    /** Queries that are the ownership join, not merely any query. */
    private function ownershipJoins(array $log): array
    {
        return array_values(array_filter($log, static function ($q) {
            // Laravel quotes identifiers, so the logged SQL reads
            // `user_credentials` as `uc` - strip backticks before matching.
            $sql = strtolower(str_replace('`', '', $q['query'] ?? ''));

            return str_contains($sql, 'user_credentials as uc')
                && str_contains($sql, 'employee_master as em');
        }));
    }

    /**
     * An actor the join actually runs for: user_category 'E' with a user_id.
     * 1,230 of the credentials in this database qualify.
     */
    private function actorThatReachesTheJoin(): User
    {
        $user = User::query()
            ->whereNotNull('user_id')
            ->whereRaw("UPPER(TRIM(user_category)) = 'E'")
            ->orderBy('pk')
            ->first();

        if (! $user) {
            $this->markTestSkipped(
                'no credential with user_category E and a user_id - the join would never run, '
                .'so this measurement would pass vacuously'
            );
        }

        return $user;
    }

    public function test_the_ownership_join_runs_exactly_once_however_often_it_is_asked(): void
    {
        $user = $this->actorThatReachesTheJoin();
        $this->actingAs($user);

        DB::enableQueryLog();
        DB::flushQueryLog();

        // First ask - this is the one that is allowed to hit the database.
        $first = EnsureMemberRecordAccess::ownsMemberRecord($user->user_id);
        $afterFirst = count($this->ownershipJoins(DB::getQueryLog()));

        // The header, the grid feed and authorizeMemberRecord() between them
        // ask this several times in one render.
        for ($i = 0; $i < 5; $i++) {
            $repeat = EnsureMemberRecordAccess::ownsMemberRecord($user->user_id);
            $this->assertSame($first, $repeat, 'the memoised verdict changed between asks');
        }

        $afterSix = count($this->ownershipJoins(DB::getQueryLog()));
        DB::disableQueryLog();

        // Non-vacuity: the join really did run once. If this is 0 the guards
        // short-circuited and the test below would prove nothing.
        $this->assertSame(1, $afterFirst,
            'the ownership join did not run on the first ask - this actor never reaches it, '
            .'so the memoisation measurement would be vacuous');

        $this->assertSame(1, $afterSix,
            "six asks produced {$afterSix} ownership joins; the per-request memo is not holding");

        fwrite(STDERR, 'ownership join: 1 query for 6 asks (verdict: '.var_export($first, true).")\n");
    }

    public function test_rendering_an_admin_page_costs_at_most_one_ownership_join(): void
    {
        $user = $this->actorThatReachesTheJoin();

        // Admin layouts leave an unclosed output buffer; unwind it or PHPUnit
        // reports this test risky rather than passing.
        $baseObLevel = ob_get_level();

        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->actingAs($user)->get('/master/employee-type');

        $joins = $this->ownershipJoins(DB::getQueryLog());
        DB::disableQueryLog();

        while (ob_get_level() > $baseObLevel) {
            ob_end_clean();
        }

        $response->assertOk();

        $this->assertLessThanOrEqual(1, count($joins),
            'a single admin page render performed '.count($joins)
            .' ownership joins; the header guard is not memoised');

        fwrite(STDERR, 'admin page render: '.count($joins)." ownership join(s)\n");
    }
}
