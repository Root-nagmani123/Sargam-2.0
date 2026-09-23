<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GET /faculty_dashboard must render for Faculty and Super Admin, and refuse
 * everyone else.
 *
 * It returned HTTP 500 on main. The layout chain named two views that do not
 * exist - <x-menu.material_management /> (added only on the unmerged branch
 * main_ui_new) and @include('admin.layouts.aside') (deleted in 21682a447, whose
 * contents were folded into admin/layouts/header) - and a missing view is a fatal
 * ViewException rather than an empty region.
 *
 * BladeViewReferencesResolveTest catches the cause by scanning source; this
 * catches it the way a user would, which also covers anything the scanner cannot
 * see, such as a view name built at runtime.
 *
 * faculty/dashboard.blade.php is the only view extending faculty.layouts.master,
 * so this single route covers that whole layout.
 */
class FacultyDashboardRendersTest extends TestCase
{
    public function test_the_faculty_dashboard_renders_a_complete_page(): void
    {
        $id = DB::table('model_has_roles as mr')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->where('r.name', 'Super Admin')->value('mr.model_id');

        if (! $id || ! ($user = User::find($id))) {
            $this->markTestSkipped('no Super Admin in this database');
        }

        $before = ob_get_level();
        $response = $this->actingAs($user)->get('/faculty_dashboard');

        $stray = '';
        while (ob_get_level() > $before) {
            $stray = ob_get_clean().$stray;
        }

        $response->assertOk();

        $content = $response->getContent();

        // A ViewException renders as a 500 page, but assert the shape too: a
        // half-rendered layout can still come back 200 if the failure is swallowed.
        $this->assertStringStartsWith('<!DOCTYPE html>', $content, 'the page did not start with a document');
        $this->assertStringContainsString('</html>', $content, 'the page was truncated before it closed');
        $this->assertStringNotContainsString(
            'Unable to locate a class or view for component',
            $content,
            'the layout still names a component that does not exist'
        );
        $this->assertSame('', $stray, 'the page flushed '.strlen($stray).' byte(s) outside the response body');
    }

    /**
     * The faculty layout renders the static admin sidebar partials, which the RBAC
     * menu table does not filter. Before the route was gated, an Officer Trainee got
     * 49 admin links here against 28 on their own dashboard (PR #317 F-019).
     */
    public function test_roles_other_than_faculty_and_super_admin_are_refused(): void
    {
        foreach (['Officer Trainee', 'Employee'] as $role) {
            $user = $this->userWhoseOnlyRoleIs($role);

            $this->app['auth']->forgetGuards();
            $this->actingAs($user)->get('/faculty_dashboard')->assertForbidden();
        }
    }

    public function test_faculty_is_admitted(): void
    {
        // Every Faculty account here also holds Employee, which is refused on its own,
        // so admitting one proves the Faculty branch rather than the Super Admin one.
        $id = DB::table('model_has_roles as mr')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->where('r.name', 'Faculty')
            ->whereNotExists(function ($q) {
                $q->from('model_has_roles as other')
                    ->join('roles as ro', 'ro.id', '=', 'other.role_id')
                    ->whereColumn('other.model_id', 'mr.model_id')
                    ->where('ro.name', 'Super Admin');
            })
            ->orderBy('mr.model_id')
            ->value('mr.model_id');

        if (! $id || ! ($user = User::find($id))) {
            $this->markTestSkipped('no Faculty user without Super Admin in this database');
        }

        $before = ob_get_level();
        $response = $this->actingAs($user)->get('/faculty_dashboard');
        while (ob_get_level() > $before) {
            ob_end_clean();
        }

        $response->assertOk();
    }

    /**
     * An actor holding a second, admitted role would pass under the old and the new
     * rule alike, so only a user with exactly this one role proves the gate.
     */
    private function userWhoseOnlyRoleIs(string $role): User
    {
        $id = DB::table('model_has_roles as mr')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->where('r.name', $role)
            ->whereNotExists(function ($q) use ($role) {
                $q->from('model_has_roles as other')
                    ->join('roles as ro', 'ro.id', '=', 'other.role_id')
                    ->whereColumn('other.model_id', 'mr.model_id')
                    ->where('ro.name', '!=', $role);
            })
            ->orderBy('mr.model_id')
            ->value('mr.model_id');

        if (! $id || ! ($user = User::find($id))) {
            $this->markTestSkipped('no user whose only role is '.$role.' in this database');
        }

        return $user;
    }
}
