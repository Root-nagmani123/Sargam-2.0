<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GET /faculty_dashboard must render.
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
    protected function setUp(): void
    {
        parent::setUp();

        // Probe before any query: with no connection the actor lookup below throws,
        // so this is the only place a missing database can become a skip.
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('the faculty dashboard test renders a real page and needs the application database');
        }
    }

    public function test_the_faculty_dashboard_renders_a_complete_page(): void
    {
        $id = DB::table('model_has_roles as mr')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->where('r.name', 'Super Admin')->value('mr.model_id');

        // With a database but no actor, fail rather than skip: a skipped guard reads
        // as a green suite while the page it protects can be 500ing again.
        $user = $id ? User::find($id) : null;
        $this->assertNotNull(
            $user,
            'no Super Admin user in this database - this test needs one to request /faculty_dashboard as'
        );

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
}
