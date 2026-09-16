<?php

namespace Tests\Feature;

use App\DataTables\MemberDataTable;
use App\Http\Middleware\EnsureMemberPiiAccess;
use App\Models\EmployeeMaster;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

        $permission = \Spatie\Permission\Models\Permission::findOrCreate(
            EnsureMemberPiiAccess::PII_PERMISSION,
            'web'
        );
        $user->givePermissionTo($permission);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

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

        $json = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/member?'.http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'search' => ['value' => '', 'regex' => 'false'],
                'columns' => $columns,
                'order' => [],
            ]))
            ->assertOk()
            ->json();

        $row = $json['data'][0] ?? null;

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
        $caste = \App\Models\CasteCategoryMaster::GetSeatName()->keys()->first();
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
