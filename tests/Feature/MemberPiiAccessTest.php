<?php

namespace Tests\Feature;

use App\DataTables\MemberDataTable;
use App\Http\Middleware\EnsureMemberPiiAccess;
use App\Http\Middleware\EnsureMemberRecordAccess;
use App\Models\CasteCategoryMaster;
use App\Models\EmployeeMaster;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The member module's personal-data guarantees, executed rather than read.
 *
 * Three findings meet here, and all three are about the same rows:
 *
 *   F-015  the four endpoints that hand out member personal data were gated by
 *          `auth` and nothing else, so any authenticated account could download
 *          the whole roster or one person's full profile sheet. Both sides of
 *          the gate are exercised below - the DENIED case first, because a test
 *          that only proves the allowed path proves nothing about a gate.
 *   F-016  the listing shipped every employee_master column to render ten,
 *          putting pan_no, dob and both addresses into the browser for a grid
 *          that displays none of them.
 *   F-017  duplicate member creation was prevented only in the page, while the
 *          table's emp_id index is not unique.
 *
 * Skips - never fails - when the application database is unreachable, per the
 * suite convention in phpunit.xml. The guard sits BEFORE the transaction opens:
 * a skip after that point is unreachable and the file errors instead of
 * skipping. Every write happens inside a transaction that is always rolled
 * back; this suite runs against the development schema.
 */
class MemberPiiAccessTest extends TestCase
{
    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('the member PII tests need the application database');
        }

        DB::beginTransaction();
        $this->inTransaction = true;
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }

        parent::tearDown();
    }

    private function user(): User
    {
        $user = User::query()->orderBy('pk')->first();

        if (! $user) {
            $this->markTestSkipped('no user_credentials row to act as');
        }

        return $user;
    }

    /**
     * An authenticated account that is NOT entitled to member personal data.
     *
     * hasRole() reads the session's user_roles before it asks the role tables,
     * and login writes them there, so a session role is the production shape of
     * "this account holds exactly this role".
     */
    private function actAsNonEntitled(): void
    {
        $this->actingAs($this->user());
        session(['user_roles' => ['FC-Sec-Audit']]);

        $this->assertFalse(
            isSidebarPrivilegedUser(),
            'this case is meaningless unless the actor is genuinely non-privileged'
        );
    }

    private function actAsSuperAdmin(): void
    {
        $this->actingAs($this->user());
        session(['user_roles' => ['Super Admin']]);
    }

    private function anyMemberPk(): int
    {
        $pk = EmployeeMaster::query()->orderBy('pk')->value('pk');

        if ($pk === null) {
            $this->markTestSkipped('no employee_master row to address');
        }

        return (int) $pk;
    }

    /**
     * The grid's own JSON feed, fetched the way the page fetches it.
     *
     * Shared by the two tests that read it - the column-leak check and the
     * Action-column check - so both see exactly the payload the browser gets,
     * rendered cells included.
     */
    private function listingFeedRows(): array
    {
        $columns = [];
        foreach ([
            'DT_RowIndex', 'employee_name', 'employee_id', 'employee_type',
            'employee_group', 'department', 'mobile_no', 'email', 'status', 'actions',
        ] as $i => $name) {
            $columns[$i] = [
                'data' => $name,
                'name' => $name,
                'searchable' => 'false',
                'orderable' => 'false',
                'search' => ['value' => '', 'regex' => 'false'],
            ];
        }

        return $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/member?'.http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'search' => ['value' => '', 'regex' => 'false'],
                'columns' => $columns,
                'order' => [],
            ]))
            ->assertOk()
            ->json('data') ?? [];
    }

    /** The routes that hand out member personal data, as the report named them. */
    public static function piiRoutes(): array
    {
        return [
            'export csv' => ['member.export', ['format' => 'csv'], false],
            'export excel' => ['member.export', ['format' => 'excel'], false],
            'export pdf' => ['member.export', ['format' => 'pdf'], false],
            'export print' => ['member.export', ['format' => 'print'], false],
            'legacy excel-export' => ['member.excel.export', [], false],
            'row print sheet' => ['member.print', [], true],
            'row profile' => ['member.show', [], true],
        ];
    }

    /**
     * F-015, the denied case: every member PII endpoint refuses a non-entitled
     * account. These were HTTP 200 before this change - the export returned a
     * 700 KB PDF of every member, the print sheet one member's full profile.
     *
     * @dataProvider piiRoutes
     */
    public function test_member_pii_endpoints_refuse_a_non_entitled_account(string $name, array $params, bool $needsId): void
    {
        $this->actAsNonEntitled();

        if ($needsId) {
            $params['id'] = encrypt($this->anyMemberPk());
        }

        $this->get(route($name, $params))->assertForbidden();
    }

    /**
     * The other side of the same gate. Asserted separately from the refusal on
     * purpose: a gate that refuses everyone is not a fix, it is an outage.
     *
     * @dataProvider piiRoutes
     */
    public function test_member_pii_endpoints_serve_an_entitled_account(string $name, array $params, bool $needsId): void
    {
        $this->actAsSuperAdmin();

        if ($needsId) {
            $params['id'] = encrypt($this->anyMemberPk());
        }

        $this->get(route($name, $params))->assertOk();
    }

    /**
     * F-002: show() hands out the same full-profile payload as printMember(),
     * which logs; show() shipped without a matching call. An audit trail with
     * a silent gap in it is worse than none, because it looks complete.
     */
    public function test_show_writes_an_audit_line_for_an_entitled_account(): void
    {
        $this->actAsSuperAdmin();

        $memberPk = $this->anyMemberPk();

        $lines = [];
        Log::listen(function ($e) use (&$lines) {
            $lines[] = $e->message;
        });

        $this->get(route('member.show', ['id' => encrypt($memberPk)]))->assertOk();

        $this->assertContains('member.pii.show', $lines,
            'show() must write a member.pii.show audit line, same as printMember().');
    }

    /** The listing itself is deliberately NOT gated - the narrowing is the egress, not the screen. */
    public function test_the_member_listing_stays_open_to_an_ordinary_account(): void
    {
        $this->actAsNonEntitled();

        $this->get(route('member.index'))->assertOk();
    }

    /**
     * The narrowing is reversible by granting a permission, not by editing code.
     *
     * The honest risk in removing a capability is that some office was using it
     * and nobody knew. The remedy must not be to widen the role check, so the
     * gate admits the holder of a named permission as well. Nothing holds it
     * today, which is why every other case in this file still sees 403.
     */
    public function test_granting_the_named_permission_restores_access_without_a_code_change(): void
    {
        $user = $this->user();

        if (! method_exists($user, 'givePermissionTo')) {
            $this->markTestSkipped('the user model does not carry Spatie permissions on this head');
        }

        $this->actAsNonEntitled();
        $this->get(route('member.export', ['format' => 'csv']))->assertForbidden();

        $permission = Permission::findOrCreate(
            EnsureMemberPiiAccess::PII_PERMISSION,
            'web'
        );
        $user->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($user->fresh());
        session(['user_roles' => ['FC-Sec-Audit']]);

        $this->get(route('member.export', ['format' => 'csv']))->assertOk();

        // tearDown rolls the grant and the permission row back.
    }

    /**
     * F-016: the grid's JSON must carry the columns it renders and no more.
     *
     * Before this change the feed returned 73 keys per row for a ten-column
     * grid - pan_no, dob, father_name and both addresses among them - visible
     * in devtools, in any client-side cache, and in anything that proxies or
     * logs the response.
     */
    public function test_the_listing_feed_ships_only_the_columns_it_renders(): void
    {
        $this->actAsNonEntitled();

        $row = $this->listingFeedRows()[0] ?? null;

        if (! $row) {
            $this->markTestSkipped('no member rows in the listing');
        }

        // Named one by one rather than by a count, so the failure message says
        // WHICH personal-data column came back.
        foreach ([
            'pan_no', 'dob', 'current_address', 'permanent_address',
            'father_name', 'officalemail', 'marital_status', 'height',
        ] as $leaked) {
            $this->assertArrayNotHasKey($leaked, $row, "the grid feed still ships {$leaked}");
        }

        // The selected columns plus the computed ones Yajra adds. A ceiling, not
        // an exact figure: DT_RowIndex and the rendered cells are added by the
        // DataTable and their number is not this test's business.
        $this->assertLessThanOrEqual(
            count(MemberDataTable::LISTING_COLUMNS) + 12,
            count($row),
            'the feed is carrying more columns than the grid selects and renders: '.implode(', ', array_keys($row))
        );

        fwrite(STDERR, "\nmember feed keys: ".count($row)."\n");
    }

    /**
     * F-037: the Action column must not offer a link the gate will refuse.
     *
     * Edit is gated by member.record, whose rule is one step wider than
     * member.pii - an entitled account reaches every record, everyone else
     * reaches exactly its own - so the column is read row by row against that
     * rule rather than once for the page. Before this fix the link was rendered
     * on every row and 403'd on all but the actor's own, which is the dead
     * button the same screen already avoids for View, Print and the toolbar.
     */
    public function test_the_action_column_offers_edit_only_where_member_record_admits_it(): void
    {
        // The refusal side of this rule is easy to exercise and the GRANT side is
        // not: the actor's own record has to be on the page under test, and page
        // one is ordered by pk descending. The first version of this test guarded
        // only $rowsNotOwn, so when the default actor's record was not on the page
        // every $isOwn was false, the loop asserted false === false ten times, and
        // a regression that withheld Edit from the owner left it green.
        //
        // So the actor is chosen FROM the page rather than hoped onto it, and both
        // counters are asserted at the end.
        $this->actAsNonEntitled();

        $pagePks = array_values(array_filter(
            array_map(fn ($row) => $row['pk'] ?? null, $this->listingFeedRows()),
            fn ($pk) => $pk !== null
        ));

        if (! $pagePks) {
            $this->markTestSkipped('no member rows in the listing');
        }

        // The actor must be one the GATE admits, not merely one whose user_id
        // happens to sit on the page. Since F-024 those are different sets:
        // user_id equality is necessary but no longer sufficient, so picking the
        // first credential whose user_id is on the page would usually pick an
        // account the gate refuses, every $isOwn below would be false, and the
        // grant side would go unexercised again - the same vacuity this test was
        // rewritten once already to avoid.
        $owner = null;

        foreach (User::query()->whereIn('user_id', $pagePks)->orderBy('pk')->get() as $candidate) {
            $this->actingAs($candidate);
            session(['user_roles' => ['FC-Sec-Audit']]);

            if (EnsureMemberRecordAccess::ownedMemberPk() !== null) {
                $owner = $candidate;
                break;
            }
        }

        if (! $owner) {
            $this->markTestSkipped(
                'no user_credentials row on page one of the listing can be SHOWN to own its '
                .'record under the F-024 rule, so the own-record branch cannot be exercised '
                .'against this data'
            );
        }

        $this->actingAs($owner);
        session(['user_roles' => ['FC-Sec-Audit']]);
        $this->assertFalse(
            isSidebarPrivilegedUser(),
            'this case is meaningless unless the actor is genuinely non-privileged'
        );

        $rows = $this->listingFeedRows();

        if (! $rows) {
            $this->markTestSkipped('no member rows in the listing');
        }

        $rowsOwn = 0;
        $rowsNotOwn = 0;

        foreach ($rows as $row) {
            $this->assertArrayHasKey(
                'pk',
                $row,
                'the feed no longer carries pk, so this test cannot tell the rows apart'
            );

            // ASK THE GATE, do not restate it. This line used to re-implement
            // the comparison under a comment claiming it matched
            // EnsureMemberRecordAccess::handle(). When F-024 narrowed the real
            // rule the restatement stayed behind, so this test went on
            // certifying the OLD behaviour and stayed green while the grid
            // offered Edit to 359 accounts the gate refuses. An oracle that
            // restates the rule under test cannot detect a change to it.
            // PR #309 F-046.
            //
            // Cheap despite being per row: ownsMemberRecord() returns false
            // without touching the database unless the row pk equals the
            // actor's own user_id, and the one call that does reach the
            // database is memoised.
            $isOwn = EnsureMemberRecordAccess::ownsMemberRecord($row['pk']);
            $hasEdit = str_contains((string) $row['actions'], 'mbr-act--edit');

            $this->assertSame(
                $isOwn,
                $hasEdit,
                $isOwn
                    ? "member {$row['pk']} is this account's own record and the Action column withheld Edit"
                    : "member {$row['pk']} is not this account's record, and the Action column still offers an Edit link that member.record answers with 403"
            );

            $rowsOwn += $isOwn ? 1 : 0;
            $rowsNotOwn += $isOwn ? 0 : 1;
        }

        $this->assertGreaterThan(
            0,
            $rowsNotOwn,
            'every row on this page was the actor own record, so the refusal side was never exercised'
        );

        // The guard the first version of this test was missing.
        $this->assertGreaterThan(
            0,
            $rowsOwn,
            'the actor own record was not on the page under test, so the GRANT side of the '
            .'rule was never exercised and this test could not have failed if it broke'
        );
    }

    /**
     * R11-001, the denied case: the two destructive mutations refuse a
     * non-entitled account, and refuse it WITHOUT writing.
     *
     * These carried `auth` and nothing else until the gate was added, so any
     * authenticated account could deactivate any member and then delete them,
     * taking the member's user_credentials row and every role mapping with it.
     * The row is read either side of the calls, because a 403 that still wrote
     * would be the same defect with a tidier response.
     */
    public function test_the_member_mutations_refuse_a_non_entitled_account(): void
    {
        $this->actAsNonEntitled();

        $pk = $this->anyMemberPk();
        $before = EmployeeMaster::query()->where('pk', $pk)->first();

        if (! $before) {
            $this->markTestSkipped('no member row to act on');
        }

        // This class carries no DatabaseTransactions trait and the configured
        // database is a real one, so these calls wrap their own transaction.
        // That is defensive, not cosmetic: if the gate ever regressed, the
        // DELETE below would remove a live member, their user_credentials row
        // and every role mapping BEFORE the assertion could fail.
        $lines = [];
        Log::listen(function ($e) use (&$lines) {
            $lines[] = $e->message;
        });

        DB::beginTransaction();

        try {
            $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->post(route('member.toggle-status', $pk))
                ->assertForbidden();

            $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->delete(route('member.destroy', encrypt($pk)))
                ->assertForbidden();

            $after = EmployeeMaster::query()->where('pk', $pk)->first();

            $this->assertNotNull($after, "member {$pk} was deleted by an account the gate refuses");
            $this->assertSame(
                (string) $before->status,
                (string) $after->status,
                "member {$pk} had its status rewritten by an account the gate refuses"
            );

            // The mirror of the grant case: a refused call must not write an
            // audit line either. A mutation record for a mutation that never
            // happened is a false entry in the only trace these endpoints leave.
            $this->assertNotContains('member.pii.toggle_status', $lines,
                'a refused toggle-status wrote an audit line for a change it did not make');
            $this->assertNotContains('member.pii.destroy', $lines,
                'a refused destroy wrote an audit line for a deletion it did not make');
        } finally {
            DB::rollBack();
        }
    }

    /**
     * The grant side of the same pair: the gate admits an entitled account, on
     * BOTH routes, and both mutations leave an audit line.
     *
     * F-044. The first version of this test posted only toggle-status and
     * asserted only "not 403", which left the destructive half of the pair
     * unexercised - the same asymmetry R11-003 was raised about one round
     * earlier and in this same file: a refusal side built airtight and a grant
     * side that need never run. So this one drives DELETE to completion and
     * asserts the row is gone, rather than stopping at the gate.
     *
     * The audit assertions are the other half of round 11's condition 2. The
     * logMemberPii() calls shipped without them, so removing both calls left
     * the whole suite green - and that audit line is the ONLY trace either
     * mutation leaves.
     *
     * Both cases reach the controller and write to a real database. The class
     * has no DatabaseTransactions trait (setUp opens its own transaction, so
     * these nest as savepoints), and the DELETE removes an employee row, a
     * user_credentials row and every role mapping attached to it - so the
     * rollback is load-bearing, and the restoration is asserted rather than
     * assumed.
     */
    public function test_the_member_mutations_admit_an_entitled_account(): void
    {
        $this->actAsSuperAdmin();

        $lines = [];
        Log::listen(function ($e) use (&$lines) {
            $lines[] = $e->message;
        });

        DB::beginTransaction();

        try {
            // --- toggle-status -------------------------------------------------
            $pk = $this->anyMemberPk();

            $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->post(route('member.toggle-status', $pk));

            $this->assertNotSame(
                403,
                $response->getStatusCode(),
                'the gate refused an entitled account on member.toggle-status'
            );
            $this->assertContains(
                'member.pii.toggle_status',
                $lines,
                'member.toggle-status changed a member and left no audit line'
            );

            // --- destroy -------------------------------------------------------
            // Not the actor's own record: destroy() deletes the credential too,
            // and deleting the row the test is authenticated as would make the
            // rest of the case meaningless.
            $target = (int) EmployeeMaster::query()
                ->where('pk', '!=', (int) optional(auth()->user())->user_id)
                ->orderBy('pk', 'desc')
                ->value('pk');

            if (! $target) {
                $this->markTestSkipped('no member row other than the actor own record');
            }

            // destroy() refuses an ACTIVE member by design, so the precondition
            // is set here rather than hoped for; both writes are inside the
            // transaction that is rolled back below.
            DB::table('employee_master')->where('pk', $target)->update(['status' => 2]);

            $deleted = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->delete(route('member.destroy', encrypt($target)));

            $this->assertNotSame(
                403,
                $deleted->getStatusCode(),
                'the gate refused an entitled account on member.destroy'
            );
            $this->assertFalse(
                EmployeeMaster::query()->where('pk', $target)->exists(),
                "member {$target} survived a destroy the gate admitted - the route is gated but broken"
            );
            $this->assertContains(
                'member.pii.destroy',
                $lines,
                'member.destroy removed an employee, their credential and their role mappings, and left no audit line'
            );
        } finally {
            DB::rollBack();
        }

        // The rollback is the only thing standing between this test and a
        // deleted employee. Prove it worked.
        $this->assertTrue(
            EmployeeMaster::query()->where('pk', $target ?? 0)->exists(),
            'the rollback did not restore the member this test deleted'
        );
    }

    /**
     * R11-002: the listing cache must not serve one actor's Action column to
     * another.
     *
     * The payload is cached, and the Action column is rendered per actor - View
     * and Print only for an entitled account, Edit only on the row it owns. The
     * cache fingerprint did not include the actor, so whoever warmed an entry
     * decided what everybody else with the same filters saw until it expired
     * (default TTL 86400s).
     *
     * THE VACUITY TRAP THIS TEST HAS TO AVOID: the configured store is redis,
     * it is not reachable from the test runner, and
     * DataTableRedisCache::remember() CATCHES that and serves uncached. A
     * cross-actor test written without forcing a working store therefore passes
     * while proving nothing. So this one points the resolver at the array store
     * and then asserts the store was actually written before trusting the result.
     */
    public function test_the_listing_cache_is_not_shared_across_actors(): void
    {
        config(['cache.redis_backed_unified_store' => 'array']);
        $store = Cache::store('array');
        $store->clear();

        // 1. A non-entitled account warms the cache.
        $this->actAsNonEntitled();
        $coldRows = $this->listingFeedRows();

        if (! $coldRows) {
            $this->markTestSkipped('no member rows in the listing');
        }

        // The guard that makes the rest of this test mean anything: if the store
        // is empty, nothing was cached and the assertions below would hold even
        // with the actor left out of the key.
        $this->assertNotSame(
            [],
            $this->arrayStoreContents($store),
            'nothing reached the cache store, so this test cannot demonstrate anything '
            .'about cache sharing - check that remember() is not falling through'
        );

        foreach ($coldRows as $row) {
            $this->assertStringNotContainsString(
                'mbr-act--view',
                (string) $row['actions'],
                'a non-entitled account was served a View control'
            );
        }

        // 2. An entitled account asks for the SAME page, length, ordering,
        //    search and filters. Before the actor was part of the key this got
        //    the payload rendered above, stripped of every control it is
        //    entitled to.
        $this->actAsSuperAdmin();
        $warmRows = $this->listingFeedRows();

        $this->assertNotEmpty($warmRows, 'the entitled account got an empty feed');

        $withView = 0;
        foreach ($warmRows as $row) {
            $withView += str_contains((string) $row['actions'], 'mbr-act--view') ? 1 : 0;
        }

        $this->assertSame(
            count($warmRows),
            $withView,
            'the entitled account was served the non-entitled account cached payload: '
            .'View is missing from '.(count($warmRows) - $withView).' of '.count($warmRows).' rows'
        );
    }

    /**
     * The mechanism R11-002 turns on, asserted directly: two accounts that must
     * render differently must not compute the same cache identity.
     */
    public function test_the_cache_identity_separates_actors_that_render_differently(): void
    {
        $this->actAsNonEntitled();
        $nonEntitled = MemberDataTable::actionColumnCacheIdentity();

        $this->actAsSuperAdmin();
        $entitled = MemberDataTable::actionColumnCacheIdentity();

        $this->assertNotSame(
            $nonEntitled,
            $entitled,
            'an entitled and a non-entitled account share a cache identity, so they share a payload'
        );

        // And the converse, which is why this is not simply auth()->id():
        // entitled accounts all render identically, so they SHOULD share.
        $this->assertSame('entitled', $entitled);
    }

    /**
     * Condition 3 (F-045): the per-actor fan-out is bounded by a short TTL, so
     * sizing the store stops depending on a population nobody can measure here.
     *
     * Asserted on the mapping rather than on a live store, deliberately - there
     * is no reachable Redis on any host this project has been worked on, which
     * is the whole reason the condition went five rounds without evidence. What
     * CAN be pinned is which identities get the short TTL and which keep the
     * default, and that is the part a future edit could silently get wrong:
     * widening the short TTL to 'entitled' would throw away the one entry with
     * real reuse, and narrowing it away from 'own:<pk>' would restore the
     * unbounded fan-out without anything failing.
     */
    public function test_only_the_per_actor_cache_entries_get_the_short_ttl(): void
    {
        // The two SHARED identities keep the default TTL - null means "fall
        // through to MEMBER_DATATABLE_CACHE_SECONDS".
        $this->assertNull(
            MemberDataTable::cacheTtlForIdentity('entitled'),
            "'entitled' is one entry shared by every administrator - capping it throws away the only entry with real reuse"
        );
        $this->assertNull(
            MemberDataTable::cacheTtlForIdentity('own:none'),
            "'own:none' is one entry shared by every account that owns no record - also high reuse"
        );

        // Every PER-ACTOR identity is capped. This is the fan-out.
        $this->assertSame(
            MemberDataTable::PER_ACTOR_CACHE_SECONDS,
            MemberDataTable::cacheTtlForIdentity('own:10525'),
            'a per-actor entry kept the default TTL, which restores the unbounded fan-out condition 3 is about'
        );
        $this->assertSame(
            MemberDataTable::PER_ACTOR_CACHE_SECONDS,
            MemberDataTable::cacheTtlForIdentity('own:1'),
            'per-actor TTL must not depend on the pk value'
        );

        // The cap has to be short enough to matter: the point is that residency
        // is bounded by concurrency, not by the 86400s default.
        $this->assertLessThan(
            3600,
            MemberDataTable::PER_ACTOR_CACHE_SECONDS,
            'a per-actor TTL this long stops bounding residency by concurrency, which is what closes condition 3'
        );

        // And the identity an actually-resolved non-entitled actor computes must
        // be one the mapping treats as per-actor - otherwise the two halves agree
        // in the test and disagree in production.
        $this->actAsNonEntitled();
        $identity = MemberDataTable::actionColumnCacheIdentity();
        $this->assertStringStartsWith('own:', $identity);
        if ($identity !== 'own:none') {
            $this->assertSame(MemberDataTable::PER_ACTOR_CACHE_SECONDS, MemberDataTable::cacheTtlForIdentity($identity));
        }
    }

    /**
     * F-046, the defect itself: the grid must not offer Edit to an account the
     * gate refuses.
     *
     * The companion test above picks an actor the gate ADMITS, and for such an
     * actor the old rule and the new one agree - `user_id === pk` is true and
     * the contact proof is also true - so it cannot detect the drift. It passes
     * against the pre-fix grid. That is precisely the hole F-046 lived in, and
     * it is why this test exists separately rather than as another assertion
     * over there.
     *
     * The actor here is the 359-account shape: user_id DOES equal a member pk on
     * page one, so the old rule offers Edit, and the gate refuses because the
     * credential cannot prove the record is its own. Old rule and new rule
     * disagree, so the grid has to pick one, and the route has already picked.
     */
    public function test_the_grid_withholds_edit_from_an_account_the_gate_refuses(): void
    {
        $this->actAsNonEntitled();

        $pagePks = array_values(array_filter(
            array_map(fn ($row) => $row['pk'] ?? null, $this->listingFeedRows()),
            fn ($pk) => $pk !== null
        ));

        if (! $pagePks) {
            $this->markTestSkipped('no member rows in the listing');
        }

        // user_id points at a real row ON THIS PAGE - so the pre-fix rule says
        // "your own record" - but there is no contact proof, so the gate says no.
        $target = (string) $pagePks[0];
        $refused = $this->credentialFor($target, 'E', 'nobody+'.uniqid().'@example.invalid');

        $this->actingAs($refused);
        session(['user_roles' => ['FC-Sec-Audit']]);

        $this->assertNull(
            EnsureMemberRecordAccess::ownedMemberPk(),
            'the fixture meant to be refused is admitted, so this test proves nothing'
        );

        // The route's answer, established first, so the assertion below is
        // measured against the gate rather than against this test's opinion.
        $this->get('/member/edit/'.$target)->assertForbidden();

        $rows = $this->listingFeedRows();
        $this->assertNotEmpty($rows, 'the refused account got an empty feed');

        $seen = false;

        foreach ($rows as $row) {
            if ((string) ($row['pk'] ?? '') !== $target) {
                continue;
            }

            $seen = true;

            $this->assertStringNotContainsString(
                'mbr-act--edit',
                (string) $row['actions'],
                "the Action column offers Edit on member {$target} to an account that "
                .'member.record answers with 403 - the screen and the gate disagree'
            );
        }

        $this->assertTrue(
            $seen,
            "member {$target} was not on the page under test, so the disagreement was never exercised"
        );
    }

    /**
     * F-046, the half that only bites AFTER the grid is corrected.
     *
     * The cache identity used to be `own:<user_id>`. That was consistent while
     * the grid also decided Edit on user_id alone - both wrong together, so the
     * payload matched the key. The moment the grid asks the real gate, user_id
     * stops being the decision: 205 user_id values on testsargam6 are held by
     * more than one credential, and under the F-024 rule 197 of those groups
     * contain credentials that DISAGREE - one can prove the record is its own,
     * the other cannot. Keyed on user_id those two share an entry whose payload
     * differs between them, which is R11-002 all over again.
     *
     * So this asserts the property directly, on two credentials constructed to
     * sit in exactly that position: same user_id, opposite verdicts.
     */
    public function test_two_credentials_sharing_a_user_id_but_not_a_verdict_get_different_identities(): void
    {
        $member = EmployeeMaster::query()
            ->whereRaw("TRIM(COALESCE(email, '')) <> ''")
            ->orderBy('pk')
            ->first(['pk', 'email']);

        if (! $member) {
            $this->markTestSkipped('no employee_master row with an email to prove ownership against');
        }

        // Admitted: user_category 'E' and an email that matches the row.
        $admitted = $this->credentialFor($member->pk, 'E', $member->email);

        // Refused: the SAME user_id, so the old key would collide, but no
        // contact proof of any kind - the 327-account shape F-024 found.
        $refused = $this->credentialFor($member->pk, 'E', 'nobody+'.uniqid().'@example.invalid');

        $this->actingAs($admitted);
        session(['user_roles' => ['FC-Sec-Audit']]);
        $this->assertSame(
            (string) $member->pk,
            EnsureMemberRecordAccess::ownedMemberPk(),
            'the fixture meant to be admitted is not admitted, so this test proves nothing'
        );
        $admittedIdentity = MemberDataTable::actionColumnCacheIdentity();

        $this->actingAs($refused);
        session(['user_roles' => ['FC-Sec-Audit']]);
        $this->assertNull(
            EnsureMemberRecordAccess::ownedMemberPk(),
            'the fixture meant to be refused is admitted, so this test proves nothing'
        );
        $refusedIdentity = MemberDataTable::actionColumnCacheIdentity();

        $this->assertNotSame(
            $admittedIdentity,
            $refusedIdentity,
            'two credentials with the same user_id but opposite gate verdicts computed the same '
            .'cache identity, so one is served the other Action column - keyed on user_id this '
            .'assertion fails, which is the point of it'
        );

        // And the refused actor shares the one entry every non-owning actor
        // shares, rather than taking a private copy of an identical payload.
        $this->assertSame('own:none', $refusedIdentity);
    }

    /**
     * A user_credentials row pointing at $memberPk, created inside the test's
     * own transaction so it never survives the run.
     */
    private function credentialFor($memberPk, string $category, string $email): User
    {
        $pk = DB::table('user_credentials')->insertGetId([
            'user_id' => $memberPk,
            'user_category' => $category,
            'email_id' => $email,
            'user_name' => 'pr309-f046-'.uniqid(),
            'mobile_no' => '',
        ]);

        $user = User::query()->where('pk', $pk)->first();

        if (! $user) {
            $this->markTestSkipped('could not create a user_credentials fixture row');
        }

        return $user;
    }

    /** ArrayStore keeps its entries in a protected property; a test may look. */
    private function arrayStoreContents($store): array
    {
        $inner = $store->getStore();
        $ref = new \ReflectionClass($inner);

        if (! $ref->hasProperty('storage')) {
            return [];
        }

        $prop = $ref->getProperty('storage');
        $prop->setAccessible(true);

        return (array) $prop->getValue($inner);
    }

    /** The other side of the same column: an entitled account keeps Edit on every row. */
    public function test_the_action_column_keeps_edit_on_every_row_for_an_entitled_account(): void
    {
        $this->actAsSuperAdmin();

        $rows = $this->listingFeedRows();

        if (! $rows) {
            $this->markTestSkipped('no member rows in the listing');
        }

        foreach ($rows as $row) {
            $this->assertStringContainsString(
                'mbr-act--edit',
                (string) $row['actions'],
                "member {$row['pk']} lost its Edit link for an account the gate admits"
            );
        }
    }

    /** The eager loads are constrained too: a relation is one label, not a whole master row. */
    public function test_the_eager_loads_are_constrained_to_their_label_column(): void
    {
        foreach (MemberDataTable::LISTING_RELATIONS as $relation) {
            $this->assertStringContainsString(
                ':',
                $relation,
                "{$relation} is loaded unconstrained - it hydrates every column of its table"
            );
            $this->assertStringContainsString(
                'pk,',
                $relation,
                "{$relation} must keep its key column or Eloquent cannot match the rows back"
            );
        }
    }

    /**
     * F-017: two identical create requests, no browser involved, one row.
     *
     * The guard added in the previous round was a disabled button and an
     * in-flight boolean, both in the page. This is the failure it cannot see: a
     * re-POST after a refresh, a second tab, a replayed request, or a client
     * where the JS never loaded. employee_master.idx_emp_id is an index, not a
     * constraint, so nothing below the controller would have rejected the
     * second insert either.
     */
    public function test_two_identical_create_requests_insert_one_member(): void
    {
        $this->actAsSuperAdmin();

        $payload = $this->newMemberPayload();

        $first = $this->postJson(route('member.store'), $payload);
        $second = $this->postJson(route('member.store'), $payload);

        $inserted = EmployeeMaster::query()->where('emp_id', $payload['id'])->count();

        $this->assertSame(
            1,
            $inserted,
            'the second identical create must not produce a second member (first: '
            .$first->getStatusCode().', second: '.$second->getStatusCode().')'
        );

        $first->assertOk();
        $second->assertStatus(422);
        $this->assertArrayHasKey('id', $second->json('errors') ?? []);
    }

    /** The same refusal for an emp_id that already belongs to somebody else. */
    public function test_creating_a_member_with_an_existing_employee_id_is_refused(): void
    {
        $this->actAsSuperAdmin();

        $taken = EmployeeMaster::query()->whereNotNull('emp_id')->where('emp_id', '!=', '')->value('emp_id');

        if ($taken === null) {
            $this->markTestSkipped('no employee_master row carries an emp_id to collide with');
        }

        $payload = $this->newMemberPayload();
        $payload['id'] = $taken;

        $before = EmployeeMaster::query()->where('emp_id', $taken)->count();

        $this->postJson(route('member.store'), $payload)->assertStatus(422);

        $this->assertSame(
            $before,
            EmployeeMaster::query()->where('emp_id', $taken)->count(),
            'a duplicate emp_id must not reach the table'
        );
    }

    /**
     * A complete, valid create payload for a member who does not exist.
     *
     * Every value is unique per run: this suite shares a development database,
     * and a fixed emp_id would make the duplicate tests pass or fail on
     * leftovers rather than on the code under test.
     *
     * @return array<string, mixed>
     */
    private function newMemberPayload(): array
    {
        $marker = substr((string) uniqid(), -8);

        $type = DB::table('employee_type_master')->value('pk');
        $group = DB::table('employee_group_master')->value('pk');
        $designation = DB::table('designation_master')->value('pk');
        $department = DB::table('department_master')->value('pk');
        $role = DB::table('user_role_master')->value('pk');
        // The rule is Rule::in(GetSeatName()), which lists ACTIVE rows only, so
        // the first row of the table is not necessarily a legal value.
        $caste = CasteCategoryMaster::GetSeatName()->keys()->first();
        $appellation = DB::table('appellation_master')->where('active_inactive', 1)->value('pk');

        $location = DB::table('employee_master')
            ->select('country_master_pk', 'state_master_pk', 'state_district_mapping_pk', 'city')
            ->whereNotNull('country_master_pk')
            ->whereNotNull('state_master_pk')
            ->whereNotNull('city')
            ->first();

        if ($location === null) {
            $this->markTestSkipped('no employee_master row carries a resolvable address to borrow');
        }

        foreach ([
            'employee type' => $type,
            'employee group' => $group,
            'designation' => $designation,
            'department' => $department,
            'role' => $role,
            'active caste category' => $caste,
        ] as $label => $value) {
            if ($value === null) {
                $this->markTestSkipped("no {$label} row to build a valid member payload from");
            }
        }

        return [
            // step 1 - the name rules are regex:/^[A-Za-z\s]+$/, so the unique
            // marker goes on emp_id and userid, never on a name.
            'first_name' => 'Dup',
            'middle_name' => '',
            'last_name' => 'Guardtest',
            'father_husband_name' => 'Guard Senior',
            'marital_status' => 'Unmarried',
            'gender' => 'Male',
            'caste_category' => $caste,
            'appellation' => $appellation,
            'height' => '170',
            'date_of_birth' => '1990-01-01',
            // step 2
            'type' => $type,
            'id' => 'DUP'.$marker,
            'group' => $group,
            'designation' => $designation,
            'userid' => 'dup'.$marker,
            'section' => $department,
            // step 3
            'userrole' => [$role],
            // step 4 - current address, permanent address, communication.
            // country / state / district / city are foreign keys on
            // employee_master, not free text, so they are borrowed from a member
            // who already has them rather than invented.
            'address' => 'Test current address',
            'country' => (string) $location->country_master_pk,
            'state' => (string) $location->state_master_pk,
            'district' => (string) $location->state_district_mapping_pk,
            'city' => (string) $location->city,
            'postal' => '248179',
            'permanentaddress' => 'Test permanent address',
            'permanentcountry' => (string) $location->country_master_pk,
            'permanentstate' => (string) $location->state_master_pk,
            'permanentdistrict' => (string) $location->state_district_mapping_pk,
            'permanentcity' => (string) $location->city,
            'permanentpostal' => '248179',
            'personalemail' => 'dup'.$marker.'@example.invalid',
            'officialemail' => 'off'.$marker.'@example.invalid',
            'mnumber' => '9000000000',
            // step 5
            'homeaddress' => 'Test address',
            'residencenumber' => '1234567',
        ];
    }
}
