<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The toggle-status allow-list, executed.
 *
 * ToggleStatusAllowListTest proves the list is narrow and complete by reading
 * it; ToggleStatusSchemaTest proves its tables and columns exist. Neither sends
 * a request, so neither would notice if the guard stopped running — a refusal
 * swallowed by a surrounding catch, or a guard placed after the write, both
 * leave those tests green.
 *
 * This one drives the real route through the real middleware stack: a payload
 * outside the list must be refused and must not reach the row, and a payload
 * inside it must still work. The second half matters as much as the first,
 * because an allow-list that is too narrow breaks admin screens silently.
 *
 * Skips - never fails - when the application database is unreachable, per the
 * suite convention in phpunit.xml. The write happens inside a transaction that
 * is always rolled back.
 */
class ToggleStatusEndpointTest extends TestCase
{
    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('the toggle-status endpoint test needs the application database');
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

    /**
     * The refusals, end to end. The endpoint answers 422 for every rejected
     * shape - an unlisted table is not distinguished from a malformed id,
     * deliberately, so the response tells a prober nothing about the list.
     *
     * @dataProvider refusedPayloads
     */
    public function test_the_endpoint_refuses_a_payload_outside_the_allow_list(array $payload): void
    {
        $this->actingAs($this->actor())
            ->post('/admin/toggle-status', $payload)
            ->assertStatus(422);
    }

    public static function refusedPayloads(): array
    {
        return [
            'a table the list does not name' => [
                ['table' => 'user_credentials', 'column' => 'active_inactive', 'id' => 1, 'status' => 1],
            ],
            'a listed table, a column that is not its status' => [
                ['table' => 'faculty_expertise_master', 'column' => 'expertise_name', 'id' => 1, 'status' => 1],
            ],
            'a key column the screen does not use' => [
                ['table' => 'faculty_expertise_master', 'column' => 'active_inactive', 'id_column' => 'expertise_name', 'id' => 1, 'status' => 1],
            ],
            'no table at all' => [
                ['column' => 'active_inactive', 'id' => 1, 'status' => 1],
            ],
            'a non-numeric id' => [
                ['table' => 'faculty_expertise_master', 'column' => 'active_inactive', 'id' => '1 OR 1=1', 'status' => 1],
            ],
            'a status outside 0/1' => [
                ['table' => 'faculty_expertise_master', 'column' => 'active_inactive', 'id' => 1, 'status' => 9],
            ],
        ];
    }

    /** A refused request must stop short of the database, not merely report an error. */
    public function test_a_refused_request_does_not_reach_the_row(): void
    {
        $row = DB::table('faculty_expertise_master')->orderBy('pk')->first();

        if (! $row) {
            $this->markTestSkipped('no faculty_expertise_master row to target');
        }

        $before = DB::table('faculty_expertise_master')->where('pk', $row->pk)->value('expertise_name');

        $this->actingAs($this->actor())
            ->post('/admin/toggle-status', [
                'table' => 'faculty_expertise_master',
                'column' => 'expertise_name',
                'id' => $row->pk,
                'status' => 1,
            ])
            ->assertStatus(422);

        $this->assertSame(
            $before,
            DB::table('faculty_expertise_master')->where('pk', $row->pk)->value('expertise_name'),
            'the refused column must be untouched'
        );
    }

    /** The other half: a permitted pair still reaches the row and writes it. */
    public function test_a_permitted_pair_still_writes_the_row(): void
    {
        $row = DB::table('faculty_expertise_master')->orderBy('pk')->first();

        if (! $row) {
            $this->markTestSkipped('no faculty_expertise_master row to toggle');
        }

        $target = (int) $row->active_inactive === 1 ? 0 : 1;

        $this->actingAs($this->actor())
            ->post('/admin/toggle-status', [
                'table' => 'faculty_expertise_master',
                'column' => 'active_inactive',
                'id' => $row->pk,
                'status' => $target,
            ])
            ->assertOk();

        $this->assertSame(
            $target,
            (int) DB::table('faculty_expertise_master')->where('pk', $row->pk)->value('active_inactive'),
            'a permitted toggle must still reach the row'
        );
    }

    /**
     * The five tables whose status decides who can do what, or what the
     * institute publishes, are refused to a signed-in non-administrator.
     *
     * This is the escalation path Trap 29 describes: before the gate, any
     * authenticated account of any role could POST user_role_master and
     * deactivate a role. The ordinary reference masters are deliberately NOT
     * gated here - see the comment on the check in UserController.
     *
     * @dataProvider privilegedTables
     */
    public function test_a_privileged_table_is_refused_to_a_non_administrator(string $table, string $column): void
    {
        $actor = $this->actor();

        // The guard would pass vacuously if the fixture user happened to be an
        // administrator, so the premise is asserted rather than assumed.
        $this->assertFalse(
            $this->isAdministrator($actor),
            'this test needs a NON-administrator actor; the fixture user has changed'
        );

        // The allow-list names tables that are not present on every database
        // (this one has no `news`), so an absent table is a skip, not an error.
        if (! Schema::hasTable($table)) {
            $this->markTestSkipped("{$table} is not present on this connection");
        }

        $row = DB::table($table)->first();

        if (! $row) {
            $this->markTestSkipped("no {$table} row to target");
        }

        $before = DB::table($table)->where('pk', $row->pk)->value($column);

        $this->actingAs($actor)
            ->post('/admin/toggle-status', [
                'table'  => $table,
                'column' => $column,
                'id'     => $row->pk,
                'status' => (int) $before === 1 ? 0 : 1,
            ])
            ->assertForbidden();

        $this->assertSame(
            $before,
            DB::table($table)->where('pk', $row->pk)->value($column),
            'a refused privileged toggle must not reach the row'
        );
    }

    public static function privilegedTables(): array
    {
        return [
            'roles'         => ['user_role_master', 'active_inactive'],
            'FC register'   => ['fc_registration_master', 'active_inactive'],
            'FC exemption'  => ['fc_exemption_master', 'visible'],
            'news'          => ['news', 'status'],
            'notices'       => ['notices_notification', 'active_inactive'],
        ];
    }

    /**
     * An administrator is still allowed through - the gate narrows, it does not
     * close - and the change that goes through leaves an audit record naming
     * the actor, the row and both values.
     */
    public function test_a_privileged_table_is_permitted_to_an_administrator_and_logged(): void
    {
        $row = DB::table('user_role_master')->first();

        if (! $row) {
            $this->markTestSkipped('no user_role_master row to toggle');
        }

        $admin  = $this->administrator();
        $before = (int) $row->active_inactive;
        $target = $before === 1 ? 0 : 1;

        Log::spy();

        $this->withSession(['user_roles' => ['Admin']])
            ->actingAs($admin)
            ->post('/admin/toggle-status', [
                'table'  => 'user_role_master',
                'column' => 'active_inactive',
                'id'     => $row->pk,
                'status' => $target,
            ])
            ->assertOk();

        $this->assertSame(
            $target,
            (int) DB::table('user_role_master')->where('pk', $row->pk)->value('active_inactive'),
            'an administrator must still be able to toggle a privileged table'
        );

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message, $context = []) => $message === 'Toggle-status change'
                && ($context['user'] ?? null) === $admin->getKey()
                && ($context['table'] ?? null) === 'user_role_master'
                && ($context['column'] ?? null) === 'active_inactive'
                && ($context['id'] ?? null) === (string) $row->pk
                && (int) ($context['from'] ?? -1) === $before
                && ($context['to'] ?? null) === $target
                && ($context['privileged'] ?? null) === true)
            ->once();
    }

    /**
     * A revoked administrator is refused at once, not at their next login.
     *
     * hasRole() answers from the session list written at login, so a session
     * still carrying 'Admin' after the Spatie role was removed used to pass.
     * The actor here holds NO administrator role in the role tables and the
     * session still says 'Admin' - exactly the state after a revocation.
     */
    public function test_a_session_only_administrator_is_refused_a_privileged_table(): void
    {
        $actor = $this->actor();

        $this->assertFalse(
            $this->isAdministrator($actor),
            'this test needs an actor with no administrator role in the role tables'
        );

        $row = DB::table('user_role_master')->first();

        if (! $row) {
            $this->markTestSkipped('no user_role_master row to target');
        }

        $before = DB::table('user_role_master')->where('pk', $row->pk)->value('active_inactive');

        $this->withSession(['user_roles' => ['Admin', 'Super Admin']])
            ->actingAs($actor)
            ->post('/admin/toggle-status', [
                'table'  => 'user_role_master',
                'column' => 'active_inactive',
                'id'     => $row->pk,
                'status' => (int) $before === 1 ? 0 : 1,
            ])
            ->assertForbidden();

        $this->assertSame(
            $before,
            DB::table('user_role_master')->where('pk', $row->pk)->value('active_inactive'),
            'a session-only administrator must not reach the row'
        );
    }

    /**
     * A failed write answers with a fixed message: the exception text, which
     * for a QueryException carries the SQL and bound values, stays in the log.
     *
     * `news` is allow-listed but absent from some databases, which is a real
     * failure to provoke without touching anything.
     */
    public function test_a_failed_write_does_not_return_the_database_error(): void
    {
        if (Schema::hasTable('news')) {
            $this->markTestSkipped('`news` exists on this connection, so the write would not fail');
        }

        $response = $this->actingAs($this->administrator())
            ->post('/admin/toggle-status', [
                'table'  => 'news',
                'column' => 'status',
                'id'     => 1,
                'status' => 1,
            ])
            ->assertStatus(500)
            ->assertExactJson(['message' => 'Status could not be updated.']);

        $this->assertStringNotContainsString('SQL', $response->getContent());
    }

    /**
     * The ordinary masters are untouched by the gate.
     *
     * Their own screens are reachable by any signed-in user, so gating the
     * switch alone would only 403 on a page the user can still open - and it
     * would break the functional roles (Estate, Mess-Admin, IST and the rest)
     * that maintain their own module's reference data today.
     */
    public function test_an_ordinary_master_is_still_open_to_a_non_administrator(): void
    {
        $row = DB::table('faculty_expertise_master')->orderBy('pk')->first();

        if (! $row) {
            $this->markTestSkipped('no faculty_expertise_master row to toggle');
        }

        $this->actingAs($this->actor())
            ->post('/admin/toggle-status', [
                'table'  => 'faculty_expertise_master',
                'column' => 'active_inactive',
                'id'     => $row->pk,
                'status' => (int) $row->active_inactive === 1 ? 0 : 1,
            ])
            ->assertOk();
    }

    /**
     * PR #330 F-002: Course Master and Group Mapping switches reach only the
     * rows the actor's own grid shows. A course-scoped account is refused on a
     * course outside its roles, and the row is untouched.
     *
     * @dataProvider courseScopedTables
     */
    public function test_a_course_scoped_account_is_refused_outside_its_courses(string $table): void
    {
        [$actor, $roleIds] = $this->courseScopedActor();

        $row = $this->courseScopedRow($table, $roleIds, false);

        if (! $row) {
            $this->markTestSkipped("no {$table} row outside the fixture actor's courses");
        }

        $this->withSession(['user_roles' => []])
            ->actingAs($actor)
            ->post('/admin/toggle-status', [
                'table'  => $table,
                'column' => 'active_inactive',
                'id'     => $row->pk,
                'status' => (int) $row->active_inactive === 1 ? 0 : 1,
            ])
            ->assertForbidden();

        $this->assertSame(
            (int) $row->active_inactive,
            (int) DB::table($table)->where('pk', $row->pk)->value('active_inactive'),
            'an out-of-scope toggle must not reach the row'
        );
    }

    /**
     * The must-succeed half: the same account still toggles a row inside its
     * courses, so the scope check narrows the switch rather than closing it.
     *
     * @dataProvider courseScopedTables
     */
    public function test_a_course_scoped_account_still_toggles_its_own_courses(string $table): void
    {
        [$actor, $roleIds] = $this->courseScopedActor();

        $row = $this->courseScopedRow($table, $roleIds, true);

        if (! $row) {
            $this->markTestSkipped("no {$table} row inside the fixture actor's courses");
        }

        $target = (int) $row->active_inactive === 1 ? 0 : 1;

        $this->withSession(['user_roles' => []])
            ->actingAs($actor)
            ->post('/admin/toggle-status', [
                'table'  => $table,
                'column' => 'active_inactive',
                'id'     => $row->pk,
                'status' => $target,
            ])
            ->assertOk();

        $this->assertSame(
            $target,
            (int) DB::table($table)->where('pk', $row->pk)->value('active_inactive'),
            'an in-scope toggle must still reach the row'
        );
    }

    /**
     * An administrator's grid shows every course, so the switch reaches a
     * course outside the scoped actor's roles too.
     *
     * @dataProvider courseScopedTables
     */
    public function test_an_administrator_toggles_any_course(string $table): void
    {
        [, $roleIds] = $this->courseScopedActor();

        $row = $this->courseScopedRow($table, $roleIds, false);

        if (! $row) {
            $this->markTestSkipped("no {$table} row outside the fixture actor's courses");
        }

        $target = (int) $row->active_inactive === 1 ? 0 : 1;

        $this->withSession(['user_roles' => []])
            ->actingAs($this->administrator())
            ->post('/admin/toggle-status', [
                'table'  => $table,
                'column' => 'active_inactive',
                'id'     => $row->pk,
                'status' => $target,
            ])
            ->assertOk();

        $this->assertSame($target, (int) DB::table($table)->where('pk', $row->pk)->value('active_inactive'));
    }

    public static function courseScopedTables(): array
    {
        return [
            'course master' => ['course_master'],
            'group mapping' => ['group_type_master_course_master_map'],
        ];
    }

    /**
     * A non-administrator whose Spatie roles map to some but not all courses -
     * the one actor for whom a missing scope check changes the answer.
     *
     * @return array{0: User, 1: list<int>}
     */
    private function courseScopedActor(): array
    {
        $adminIds = DB::table('model_has_roles as m')
            ->join('roles as r', 'r.id', '=', 'm.role_id')
            ->where('m.model_type', User::class)
            ->whereIn('r.name', ['Admin', 'Super Admin', 'SuperAdmin', 'PA'])
            ->pluck('m.model_id');

        $candidates = DB::table('model_has_roles as m')
            ->join('course_master as cm', 'cm.user_role_master_pk', '=', 'm.role_id')
            ->where('m.model_type', User::class)
            ->whereNotIn('m.model_id', $adminIds)
            ->distinct()
            ->orderBy('m.model_id')
            ->pluck('m.model_id');

        $total = DB::table('course_master')->count();

        foreach ($candidates as $id) {
            $user = User::find($id);

            if (! $user) {
                continue;
            }

            $roleIds = DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->where('model_id', $id)
                ->pluck('role_id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $mine = DB::table('course_master')->whereIn('user_role_master_pk', $roleIds)->count();

            if ($mine > 0 && $mine < $total) {
                return [$user, $roleIds];
            }
        }

        $this->markTestSkipped('no non-administrator whose roles map to some but not all courses');
    }

    /**
     * A row of $table whose course is (or is not) mapped to one of $roleIds,
     * computed here from course_master.user_role_master_pk rather than by the
     * code under test.
     */
    private function courseScopedRow(string $table, array $roleIds, bool $inScope): ?object
    {
        $courses = DB::table('course_master')
            ->when(
                $inScope,
                fn ($q) => $q->whereIn('user_role_master_pk', $roleIds),
                fn ($q) => $q->where(fn ($w) => $w->whereNotIn('user_role_master_pk', $roleIds)->orWhereNull('user_role_master_pk'))
            )
            ->pluck('pk');

        $key = $table === 'course_master' ? 'pk' : 'course_name';

        return DB::table($table)->whereIn($key, $courses)->orderBy('pk')->first(['pk', 'active_inactive']);
    }

    /**
     * A real user_credentials row: the endpoint is gated by auth only, but the
     * sidebar view composer that runs while a response renders reads the
     * actor's permissions, so a stub actor cannot get through the stack.
     */
    private function actor(): User
    {
        $user = User::query()->first();

        if (! $user) {
            $this->markTestSkipped('no user_credentials row to act as');
        }

        return $user;
    }

    /** A user who holds Admin or Super Admin in the role tables, not just the session. */
    private function administrator(): User
    {
        $user = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Admin', 'Super Admin', 'SuperAdmin']))
            ->first();

        if (! $user) {
            $this->markTestSkipped('no user holds an administrator role in the role tables');
        }

        return $user;
    }

    private function isAdministrator(User $user): bool
    {
        return $user->roles()->whereIn('name', ['Admin', 'Super Admin', 'SuperAdmin'])->exists();
    }
}
