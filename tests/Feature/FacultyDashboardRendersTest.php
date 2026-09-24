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
 * The page now extends admin.layouts.master (PR #317 F-024), so these tests cover
 * that layout on this route. They do not cover faculty.layouts.master, which no
 * view extends any more (F-026).
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
     * Before the route was gated the page rendered the faculty layout's static admin
     * sidebar partials, which the RBAC menu table does not filter, and an Officer
     * Trainee got 49 admin links here against 28 on their own dashboard (PR #317
     * F-019). The gate stays in place now that the page uses the admin layout.
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
     * Gating who may open the page is not enough: the faculty layout's static admin
     * sidebar showed a Faculty account 17 links to menus its roles are not granted
     * (PR #317 F-024). Every link on the page that is an RBAC menu must be one the
     * viewer holds the menu's permission for.
     */
    public function test_faculty_sees_only_menu_links_they_are_granted(): void
    {
        $user = $this->facultyUserWithoutSuperAdmin();

        $before = ob_get_level();
        $response = $this->actingAs($user)->get('/faculty_dashboard');
        while (ob_get_level() > $before) {
            ob_end_clean();
        }
        $response->assertOk();

        preg_match_all('/href\s*=\s*["\']([^"\'#]+)["\']/i', $response->getContent(), $m);
        $paths = collect($m[1])
            ->map(fn ($href) => trim((string) parse_url(html_entity_decode($href), PHP_URL_PATH), '/'))
            ->filter()
            ->unique();

        $menus = DB::table('menus')
            ->whereIn('route', $paths->all())
            ->whereNull('deleted_at')
            ->whereNotNull('permission_name')
            ->where('permission_name', '!=', '')
            ->get(['route', 'permission_name']);

        // A route can sit under several menus; holding any one of their permissions entitles it.
        $unentitled = $menus->groupBy('route')
            ->reject(fn ($rows) => $rows->contains(fn ($menu) => $user->can($menu->permission_name)))
            ->map(fn ($rows, $route) => $route.' ('.$rows->pluck('permission_name')->unique()->implode(', ').')')
            ->values()
            ->all();

        $this->assertSame([], $unentitled, 'the page links to menus this Faculty account is not granted');

        // The sidebar's menu items arrive later from sidebar.menu, so the check above sees
        // little of it. Also hold the page to the same account's own dashboard, which is
        // built from its RBAC grant: nothing here may link where that page does not.
        $this->app['auth']->forgetGuards();
        $before = ob_get_level();
        $dashboard = $this->actingAs($user)->get('/dashboard');
        while (ob_get_level() > $before) {
            ob_end_clean();
        }
        $dashboard->assertOk();

        preg_match_all('/href\s*=\s*["\']([^"\'#]+)["\']/i', $dashboard->getContent(), $d);
        $dashboardPaths = collect($d[1])
            ->map(fn ($href) => trim((string) parse_url(html_entity_decode($href), PHP_URL_PATH), '/'))
            ->filter()
            ->unique();

        $this->assertSame(
            [],
            $paths->diff($dashboardPaths)->values()->all(),
            'the page links where the same account\'s own dashboard does not'
        );
    }

    /**
     * faculty.layouts.master still renders the unfiltered static admin sidebar. No
     * view may extend it: a page built on it would show every holder admin links
     * their roles are not granted, and the tests above, bound to /faculty_dashboard,
     * would stay green (PR #317 F-026).
     */
    public function test_no_view_extends_the_unfiltered_faculty_layout(): void
    {
        $pattern = '/@extends\s*\(\s*[\'"]faculty\.layouts\.master[\'"]/';

        // Prove the detector fires before trusting it to find nothing.
        $this->assertSame(1, preg_match($pattern, "@extends('faculty.layouts.master')"));

        $offenders = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        foreach ($files as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $source = preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents($file->getPathname())) ?? '';

            if (preg_match($pattern, $source)) {
                $offenders[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }

        $this->assertSame([], $offenders, 'these views extend the unfiltered faculty layout');
    }

    private function facultyUserWithoutSuperAdmin(): User
    {
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

        return $user;
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
