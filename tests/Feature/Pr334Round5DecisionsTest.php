<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Timetable\FacultySessionScope;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #334 round-5 findings whose fix needed a decision; the operator chose these
 * behaviours in the author session of 2026-10-09:
 *
 *   F-044  an individually named employee receives the notice even after moving
 *          out of its departments
 *   F-058  user_id is an employee key only for a category-E login
 */
class Pr334Round5DecisionsTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** An employee login (category E) with a department, and a second department. */
    private function employeeFixture(): array
    {
        $row = DB::table('user_credentials as u')
            ->join('employee_master as e', 'e.pk', '=', 'u.user_id')
            ->where('u.user_category', 'E')
            ->where('e.department_master_pk', '>', 0)
            ->orderBy('u.pk')
            ->first(['u.pk as user_pk', 'e.pk as employee', 'e.department_master_pk as dept']);
        $otherDept = $row ? DB::table('department_master')->where('pk', '>', 0)->where('pk', '!=', $row->dept)->value('pk') : null;
        if (! $row || ! $otherDept) {
            $this->markTestSkipped('needs an employee login with a department and a second department');
        }

        return [User::findOrFail($row->user_pk), (int) $row->employee, (int) $row->dept, (int) $otherDept];
    }

    /** A live Personal notice addressed to one employee in one department, through the store route. */
    private function personalNotice(int $employee, int $dept): int
    {
        $title = 'R5 decision probe '.random_int(100000, 999999);
        $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])->post(route('admin.notice.store'), [
            'notice_title' => $title, 'description' => 'probe', 'notice_type' => 'Personal',
            'display_date' => now()->toDateString(), 'expiry_date' => now()->addDays(10)->toDateString(),
            'target_audience' => 'Staff/Faculty',
            'department_master_pks' => [(string) $dept], 'staff_scope' => 'individual', 'employee_pks' => [(string) $employee],
        ])->assertSessionHasNoErrors();
        $pk = (int) DB::table('notices_notification')->where('notice_title', $title)->value('pk');
        DB::table('notices_notification')->where('pk', $pk)->update(['active_inactive' => 1]);

        return $pk;
    }

    /** Notice pks in $user's live feed. */
    private function feedFor(User $user, array $roles = []): array
    {
        $this->actingAs($user);
        session(['user_roles' => $roles]);

        return notice_feed_query_by_role('live')->pluck('notices_notification.pk')->map(fn ($pk) => (int) $pk)->all();
    }

    /* ---------------- F-044 ---------------- */

    public function test_a_named_recipient_who_moved_department_still_receives_the_notice(): void
    {
        [$login, $employee, $dept, $otherDept] = $this->employeeFixture();
        $notice = $this->personalNotice($employee, $dept);

        // Control: before the move the recipient sees it.
        $this->assertContains($notice, $this->feedFor($login->fresh()));

        DB::table('employee_master')->where('pk', $employee)->update(['department_master_pk' => $otherDept]);
        $this->assertContains($notice, $this->feedFor($login->fresh()), 'still a named recipient after the move');

        // Not widened: another employee of the notice's department, not named, does not see it.
        $colleague = DB::table('user_credentials as u')->join('employee_master as e', 'e.pk', '=', 'u.user_id')
            ->where('u.user_category', 'E')->where('e.department_master_pk', $dept)->where('e.pk', '!=', $employee)
            ->value('u.pk');
        if ($colleague) {
            $this->assertNotContains($notice, $this->feedFor(User::findOrFail($colleague)), 'an unnamed colleague in the department');
        }
    }

    /* ---------------- F-059 ---------------- */

    public function test_a_super_admin_on_a_trainee_category_login_reaches_the_staff_screens(): void
    {
        $pk = DB::table('user_credentials as u')
            ->join('model_has_roles as m', 'm.model_id', '=', 'u.pk')
            ->join('roles as r', 'r.id', '=', 'm.role_id')
            ->where('u.user_category', 'S')->where('r.name', 'Super Admin')->where('m.model_type', User::class)
            ->value('u.pk');
        if (! $pk) {
            $this->markTestSkipped('no category-S login holds Super Admin');
        }
        // A category-S login's session carries only Student-OT (LoginController); Super Admin comes from the database.
        $as = fn (User $u) => $this->as($u, ['Student-OT']);
        $screens = ['admin.notice.index', 'master.leave-nature.index', 'admin.leave-on-behalf.index'];

        foreach ($screens as $screen) {
            $as(User::findOrFail($pk))->get(route($screen))->assertOk();
        }

        // Control: a trainee without Super Admin is still refused.
        $ot = DB::table('user_credentials as u')->where('u.user_category', 'S')
            ->whereNotExists(fn ($q) => $q->from('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
                ->whereColumn('m.model_id', 'u.pk')->where('r.name', 'Super Admin'))
            ->orderByDesc('u.pk')->value('u.pk');
        foreach ($screens as $screen) {
            $as(User::findOrFail($ot))->get(route($screen))->assertForbidden();
        }
    }

    /* ---------------- F-060 ---------------- */

    public function test_a_training_administrator_who_also_holds_faculty_is_not_confined_to_own_sessions(): void
    {
        // A staff login holding no Super Admin (hasRole() falls back to the database), roles from the session.
        $pk = DB::table('user_credentials as u')->where('u.user_category', 'E')
            ->whereNotExists(fn ($q) => $q->from('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
                ->whereColumn('m.model_id', 'u.pk')->where('r.name', 'Super Admin'))
            ->orderBy('u.pk')->value('u.pk');
        if (! $pk) {
            $this->markTestSkipped('no staff login without Super Admin');
        }
        $locked = function (array $roles) use ($pk) {
            $this->actingAs(User::findOrFail($pk));
            session(['user_roles' => $roles]);

            return FacultySessionScope::lockedFacultyPk();
        };

        // Control: a faculty login is confined.
        $this->assertNotNull($locked(['Faculty']), 'a faculty login is confined to its own sessions');

        foreach (['Training', 'Training-Induction', 'Training MCTP Admin', 'Training IST'] as $role) {
            $this->assertNull($locked(['Faculty', $role]), "Faculty + {$role} sees every session, as on the calendar");
        }
    }

    /* ---------------- Trap 29 (evidence for the Engineering lead) ---------------- */

    /**
     * The generic toggle-status endpoint also writes notices_notification. This PR
     * changes who manages notices (F-039, F-059) but not that endpoint: the row stays
     * admin_only, decided on the database roles.
     */
    public function test_the_toggle_endpoint_still_lets_only_administrators_flip_a_notice(): void
    {
        $notice = $this->personalNotice(...array_slice($this->employeeFixture(), 1, 2));
        $toggle = fn (User $u, array $roles, int $to) => $this->as($u, $roles)->post(route('admin.toggleStatus'), [
            'table' => 'notices_notification', 'column' => 'active_inactive', 'id' => $notice, 'status' => $to,
        ]);
        $state = fn () => (int) DB::table('notices_notification')->where('pk', $notice)->value('active_inactive');

        // A notice author who is not an administrator: refused, nothing written.
        $author = DB::table('user_credentials as u')->where('u.user_category', '!=', 'S')
            ->whereExists(fn ($q) => $q->from('model_has_roles as m')->join('role_has_permissions as rp', 'rp.role_id', '=', 'm.role_id')
                ->join('permissions as p', 'p.id', '=', 'rp.permission_id')->whereColumn('m.model_id', 'u.pk')->where('p.name', 'admin_notice'))
            ->whereNotExists(fn ($q) => $q->from('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
                ->whereColumn('m.model_id', 'u.pk')->whereIn('r.name', ['Admin', 'Super Admin', 'SuperAdmin']))
            ->orderBy('u.pk')->value('u.pk');
        if ($author) {
            $user = User::findOrFail($author);
            $toggle($user, $user->roles()->pluck('name')->all(), 0)->assertForbidden();
            $this->assertSame(1, $state(), 'a non-administrator author cannot unpublish through the toggle');
        }

        // Super Admin (staff login): allowed, and it writes.
        $toggle($this->staffWithRole('Super Admin'), ['Super Admin'], 0)->assertOk();
        $this->assertSame(0, $state());

        // A category-S Super Admin is an administrator here too (database roles), consistent with F-059.
        $sAdmin = DB::table('user_credentials as u')->join('model_has_roles as m', 'm.model_id', '=', 'u.pk')
            ->join('roles as r', 'r.id', '=', 'm.role_id')->where('u.user_category', 'S')->where('r.name', 'Super Admin')->value('u.pk');
        if ($sAdmin) {
            $toggle(User::findOrFail($sAdmin), ['Student-OT'], 1)->assertOk();
            $this->assertSame(1, $state());
        }
    }

    /* ---------------- F-058 ---------------- */

    public function test_a_login_without_category_e_is_not_read_as_an_employee(): void
    {
        [$login, $employee, $dept] = $this->employeeFixture();
        $notice = $this->personalNotice($employee, $dept);

        // Control: the employee's own login sees their Personal notice.
        $this->assertContains($notice, $this->feedFor($login->fresh()));

        // A different login, no category, holding a staff role, whose user_id happens to equal that employee pk.
        $other = DB::table('user_credentials')->where('pk', '!=', $login->pk)->orderBy('pk')->value('pk');
        DB::table('user_credentials')->where('pk', $other)->update(['user_category' => null, 'user_id' => $employee]);

        $this->assertNotContains($notice, $this->feedFor(User::findOrFail($other), ['Employee']), "another login does not get the employee's Personal notice");
    }
}
