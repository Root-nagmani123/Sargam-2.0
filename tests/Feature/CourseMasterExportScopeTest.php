<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Course Master export: the rows a download carries are the rows the actor
 * may see, and nothing else.
 *
 * The export has no permission gate of its own - its only access control is
 * the row scoping in CourseMasterDataTable::applyListingScope(), shared with
 * the grid. These tests pin that scoping from the outside:
 *
 *  - the expected row set is computed here, independently, from the actor's
 *    role ids and course_master.user_role_master_pk - not by calling the
 *    method under test, which would make the assertion a tautology;
 *  - the actor is a NON-administrator whose roles map to some courses, so the
 *    scoped and unscoped answers differ (an administrator would pass against
 *    a query with the scope removed);
 *  - the grid feed must return the same set as the CSV.
 *
 * Read-only, but wrapped in a rolled-back transaction all the same. Skips -
 * never fails - when the database is unreachable or holds no suitable
 * fixture, per the suite convention in phpunit.xml.
 */
class CourseMasterExportScopeTest extends TestCase
{
    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('the Course Master export test needs the application database');
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

    public function test_an_unauthenticated_export_is_redirected_to_login(): void
    {
        $this->get('/programme/export/csv')->assertRedirect();
    }

    /**
     * @dataProvider statusViews
     */
    public function test_a_scoped_actor_exports_exactly_their_own_courses(string $status): void
    {
        [$actor, $roleIds] = $this->scopedActor();

        $expected = $this->expectedCourseNames($roleIds, $status);
        $exported = $this->exportedCourseNames($actor, ['status_filter' => $status]);

        $this->assertSame($expected, $exported, "the {$status} export must carry exactly the actor's own courses");

        $outsider = $this->outOfScopeCourse($roleIds, $status, $expected);

        if ($outsider !== null) {
            $this->assertNotContains(
                $outsider,
                $exported,
                'a course outside the actor\'s roles must not appear in the export'
            );
        }
    }

    /**
     * @dataProvider statusViews
     */
    public function test_the_export_matches_the_grid_feed_for_a_scoped_actor(string $status): void
    {
        [$actor] = $this->scopedActor();

        $grid = $this->actingAs($actor)
            ->withSession(['user_roles' => []])
            ->getJson('/programme?' . http_build_query([
                'status_filter' => $status,
                'draw' => 1,
                'start' => 0,
                'length' => -1,
            ]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->json('data');

        $gridNames = collect($grid)
            ->map(fn ($row) => html_entity_decode(strip_tags((string) ($row['course_name'] ?? '')), ENT_QUOTES))
            ->map(fn ($name) => trim($name) === '' ? '-' : trim($name))
            ->sort()
            ->values()
            ->all();

        $this->app['auth']->forgetGuards();

        $this->assertSame(
            $gridNames,
            $this->exportedCourseNames($actor, ['status_filter' => $status]),
            "the {$status} export must carry the same rows as the grid"
        );
    }

    /** F-005: the filter label must not name a course the actor cannot see. */
    public function test_the_filter_label_does_not_name_an_out_of_scope_course(): void
    {
        [$actor, $roleIds] = $this->scopedActor();

        $outsider = DB::table('course_master')
            ->where(fn ($q) => $q->whereNotIn('user_role_master_pk', $roleIds)->orWhereNull('user_role_master_pk'))
            ->whereNotNull('course_name')
            ->where('course_name', '<>', '')
            ->orderBy('pk')
            ->first(['pk', 'course_name', 'end_date']);

        if (! $outsider) {
            $this->markTestSkipped('no course outside the fixture actor\'s scope');
        }

        $status = Carbon::parse($outsider->end_date)->lt(Carbon::today()) ? 'archive' : 'active';

        $csv = $this->csv($actor, ['status_filter' => $status, 'course_filter' => $outsider->pk]);

        $this->assertStringNotContainsString($outsider->course_name, $csv);
        $this->assertStringNotContainsString('Course: ', $csv, 'an out-of-scope course_filter must not produce a label');
    }

    /**
     * F-007: applyListingScope() treats course_filter=0 as "no filter", so the
     * label must not name a course either - the rows are the whole view.
     */
    public function test_a_course_filter_of_zero_filters_nothing_and_names_no_course(): void
    {
        [$actor, $roleIds] = $this->scopedActor();

        $expected = $this->expectedCourseNames($roleIds, 'archive');

        if ($expected === []) {
            // With no in-scope archived course there is nothing to mislabel.
            $this->markTestSkipped('the fixture actor has no archived course');
        }

        $query = ['status_filter' => 'archive', 'course_filter' => '0'];

        $this->assertSame($expected, $this->exportedCourseNames($actor, $query), 'course_filter=0 must not filter the rows');
        $this->assertStringNotContainsString('Course: ', $this->csv($actor, $query), 'course_filter=0 must not produce a label');
    }

    public static function statusViews(): array
    {
        return ['active' => ['active'], 'archived' => ['archive']];
    }

    /**
     * A non-administrator whose Spatie roles are mapped to at least one course
     * but not to every course - the one kind of actor for whom a missing scope
     * changes the answer.
     *
     * @return array{0: User, 1: list<int>}
     */
    private function scopedActor(): array
    {
        $adminIds = DB::table('model_has_roles as m')
            ->join('roles as r', 'r.id', '=', 'm.role_id')
            ->where('m.model_type', User::class)
            ->whereIn('r.name', ['Admin', 'Super Admin', 'PA'])
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

    /** @return list<string> sorted course names the actor should see in this view */
    private function expectedCourseNames(array $roleIds, string $status): array
    {
        $today = Carbon::today()->format('Y-m-d');

        return DB::table('course_master')
            ->whereIn('user_role_master_pk', $roleIds)
            ->where('end_date', $status === 'archive' ? '<' : '>=', $today)
            ->pluck('course_name')
            ->map(fn ($name) => ($name === null || $name === '') ? '-' : (string) $name)
            ->sort()
            ->values()
            ->all();
    }

    private function outOfScopeCourse(array $roleIds, string $status, array $inScopeNames): ?string
    {
        $today = Carbon::today()->format('Y-m-d');

        $name = DB::table('course_master')
            ->where(fn ($q) => $q->whereNotIn('user_role_master_pk', $roleIds)->orWhereNull('user_role_master_pk'))
            ->where('end_date', $status === 'archive' ? '<' : '>=', $today)
            ->whereNotIn('course_name', $inScopeNames)
            ->whereNotNull('course_name')
            ->where('course_name', '<>', '')
            ->value('course_name');

        return $name === null ? null : (string) $name;
    }

    /** @return list<string> sorted Course Name cells of the CSV's data rows */
    private function exportedCourseNames(User $actor, array $query): array
    {
        $lines = array_map('str_getcsv', preg_split('/\r\n|\n/', trim($this->csv($actor, $query))));

        $headerAt = null;
        foreach ($lines as $i => $cells) {
            if (($cells[0] ?? null) === 'S. No.') {
                $headerAt = $i;
                break;
            }
        }

        $this->assertNotNull($headerAt, 'the CSV must carry its column header row');

        $nameAt = array_search('Course Name', $lines[$headerAt], true);

        return collect(array_slice($lines, $headerAt + 1))
            ->filter(fn ($cells) => count($cells) > 1)
            ->map(fn ($cells) => (string) $cells[$nameAt])
            ->sort()
            ->values()
            ->all();
    }

    private function csv(User $actor, array $query): string
    {
        $this->app['auth']->forgetGuards();

        $response = $this->actingAs($actor)
            ->withSession(['user_roles' => []])
            ->get('/programme/export/csv?' . http_build_query($query))
            ->assertOk();

        return ltrim($response->streamedContent(), "\xEF\xBB\xBF");
    }
}
