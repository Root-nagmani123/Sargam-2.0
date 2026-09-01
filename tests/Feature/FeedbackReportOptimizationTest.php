<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\FeedbackController;
use App\Models\User;
use App\Support\FeedbackReportCache;
use App\Support\FeedbackReportGrouping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Tests\TestCase;

/**
 * Guards the invariants the session-feedback query optimisation depends on.
 *
 * Read-only throughout: this suite runs against the database .env points at (see the
 * comment in phpunit.xml), so nothing here writes, and every test skips when the
 * fixture data it needs is absent.
 *
 * The optimisation replaced wide GROUP BY lists with the primary keys that determine
 * them, and replaced a materialised row count with a distinct-key count. If someone
 * later widens a key again, or the schema loses a functional dependency these rely on,
 * these tests fail rather than the reports silently changing.
 */
class FeedbackReportOptimizationTest extends TestCase
{
    /** Qualifying-row predicate shared by the Feedback Database report queries. */
    private const QUALIFIES = "(tf.is_submitted = 1 AND ("
        . "(tf.content IS NOT NULL AND tf.content <> '') OR "
        . "(tf.presentation IS NOT NULL AND tf.presentation <> '') OR "
        . "(tf.remark IS NOT NULL AND TRIM(tf.remark) <> '')))";

    private function skipUnlessFeedbackData(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('topic_feedback')) {
            $this->markTestSkipped('topic_feedback table not present.');
        }
        if (DB::table('topic_feedback')->where('is_submitted', 1)->limit(1)->count() === 0) {
            $this->markTestSkipped('No submitted feedback rows to exercise.');
        }
    }

    private function actingAsSuperAdmin(): void
    {
        $admin = User::query()
            ->whereIn('pk', function ($q) {
                $q->select('model_has_roles.model_id')
                    ->from('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('roles.name', 'Super Admin');
            })
            ->first();

        if (! $admin) {
            $this->markTestSkipped('No Super Admin user available.');
        }

        Auth::login($admin);
    }

    private function request(array $params = []): Request
    {
        $r = Request::create('/test', 'GET', $params);
        $this->app->instance('request', $r);

        return $r;
    }

    private function callPrivate(object $obj, string $method, ...$args)
    {
        $m = (new ReflectionClass($obj))->getMethod($method);
        $m->setAccessible(true);

        return $m->invoke($obj, ...$args);
    }

    /**
     * The narrow key must yield exactly the same groups as the original wide key.
     *
     * Counting distinct tuples of each key set is the direct statement of that: if any
     * dropped column could split a group, the wide count would exceed the narrow one.
     */
    public function test_database_grid_group_key_is_equivalent_to_the_original_wide_key(): void
    {
        $this->skipUnlessFeedbackData();

        $from = 'FROM topic_feedback tf
                 JOIN timetable t ON tf.timetable_pk = t.pk
                 JOIN faculty_master f ON tf.faculty_pk = f.pk
                 JOIN course_master c ON t.course_master_pk = c.pk
                 WHERE ' . self::QUALIFIES;

        $wide = DB::selectOne("SELECT COUNT(*) AS n FROM (
            SELECT 1 $from
            GROUP BY f.pk, f.full_name, f.email_id, f.Permanent_Address,
                     c.course_name, t.subject_topic, t.START_DATE, t.pk
        ) x")->n;

        $narrow = DB::selectOne("SELECT COUNT(*) AS n FROM (
            SELECT 1 $from GROUP BY " . implode(', ', FeedbackReportGrouping::DATABASE_GRID) . "
        ) x")->n;

        $this->assertSame(
            (int) $wide,
            (int) $narrow,
            'Grouping on (f.pk, t.pk) no longer produces the same groups as the original '
            . 'eight-column key — a dropped column is no longer functionally dependent.'
        );

        // c.pk joins f.pk and t.pk so that every selected table contributes a primary key;
        // see the note in FeedbackReportGrouping on constant-false predicates.
        $this->assertSame(['f.pk', 't.pk', 'c.pk'], FeedbackReportGrouping::DATABASE_GRID);
    }

    /**
     * Faculty Average key: fm.full_name was dropped as dependent on tf.faculty_pk.
     * The course/date/session columns must still be present — nothing determines them.
     */
    public function test_faculty_average_group_key_is_equivalent(): void
    {
        $this->skipUnlessFeedbackData();

        $from = 'FROM topic_feedback tf
                 JOIN timetable tt ON tf.timetable_pk = tt.pk
                 JOIN course_master cm ON tt.course_master_pk = cm.pk
                 JOIN faculty_master fm ON tf.faculty_pk = fm.pk
                 WHERE tf.is_submitted = 1';

        $wide = DB::selectOne("SELECT COUNT(*) AS n FROM (
            SELECT 1 $from
            GROUP BY tf.faculty_pk, tf.topic_name, cm.course_name, fm.full_name,
                     tt.START_DATE, tt.class_session
        ) x")->n;

        $narrow = DB::selectOne("SELECT COUNT(*) AS n FROM (
            SELECT 1 $from
            GROUP BY " . implode(', ', FeedbackReportGrouping::FACULTY_AVERAGE) . "
        ) x")->n;

        $this->assertSame((int) $wide, (int) $narrow);

        // Guard the reasoning: this key carries no timetable/course primary key, so the
        // course and session columns must not be dropped from it.
        $this->assertContains('cm.course_name', FeedbackReportGrouping::FACULTY_AVERAGE);
        $this->assertContains('tt.class_session', FeedbackReportGrouping::FACULTY_AVERAGE);
    }

    /** Faculty View key: twelve columns collapse to the three that determine them. */
    public function test_faculty_view_group_key_is_equivalent(): void
    {
        $this->skipUnlessFeedbackData();

        $from = 'FROM topic_feedback tf
                 JOIN timetable tt ON tf.timetable_pk = tt.pk
                 JOIN course_master cm ON tt.course_master_pk = cm.pk
                 JOIN faculty_master fm ON tf.faculty_pk = fm.pk
                 WHERE tf.is_submitted = 1';

        $wide = DB::selectOne("SELECT COUNT(*) AS n FROM (
            SELECT 1 $from
            GROUP BY tf.topic_name, cm.pk, cm.course_name, cm.active_inactive, cm.end_date,
                     fm.full_name, tt.faculty_type, tf.faculty_pk, tt.START_DATE,
                     tt.END_DATE, tt.class_session, tf.timetable_pk
        ) x")->n;

        $narrow = DB::selectOne("SELECT COUNT(*) AS n FROM (
            SELECT 1 $from
            GROUP BY " . implode(', ', FeedbackReportGrouping::FACULTY_VIEW) . "
        ) x")->n;

        $this->assertSame((int) $wide, (int) $narrow);

        // topic_name lives on topic_feedback and is not determined by timetable_pk.
        $this->assertContains('tf.topic_name', FeedbackReportGrouping::FACULTY_VIEW);
    }

    /**
     * The cheap COUNT(DISTINCT f.pk, t.pk) path must agree with materialising the
     * grouped query and counting its rows.
     */
    public function test_database_row_count_matches_the_materialised_count(): void
    {
        $this->skipUnlessFeedbackData();
        $this->actingAsSuperAdmin();

        $controller = new FeedbackController();

        $courseId = DB::table('topic_feedback as tf')
            ->join('timetable as t', 'tf.timetable_pk', '=', 't.pk')
            ->value('t.course_master_pk');

        $filterSets = [
            [],
            ['course_id' => $courseId],
            ['search_term' => 'a'],
            // Conditional filter: must fall back to the materialised path and still agree.
            ['cond_field' => 'content', 'cond_operator' => '>=', 'cond_value' => 80],
        ];

        foreach ($filterSets as $params) {
            $request = $this->request($params);

            $fast = $this->callPrivate($controller, 'databaseRowCount', $request);

            $grouped = $this->callPrivate($controller, 'baseDatabaseQuery', $request);
            $slow = DB::table(DB::raw("({$grouped->toSql()}) as sub"))
                ->mergeBindings($grouped)
                ->count();

            $this->assertSame(
                (int) $slow,
                (int) $fast,
                'Row count disagreed for filters ' . json_encode($params)
            );
        }
    }

    /**
     * Paging must be a total order: walking every page yields each row exactly once.
     * Without a deterministic tie-break, OFFSET/LIMIT could repeat or skip rows.
     */
    public function test_paging_yields_every_row_exactly_once(): void
    {
        $this->skipUnlessFeedbackData();
        $this->actingAsSuperAdmin();

        $controller = new FeedbackController();
        $perPage = 25;

        $expected = $this->callPrivate($controller, 'baseDatabaseQuery', $this->request())->get()->count();
        if ($expected === 0) {
            $this->markTestSkipped('Feedback Database report is empty.');
        }

        $seen = [];
        $duplicates = [];
        for ($page = 1; $page <= (int) ceil($expected / $perPage); $page++) {
            $body = json_decode(
                $controller->getDatabaseData($this->request(['per_page' => $perPage, 'page' => $page]))->getContent(),
                true
            );

            foreach ($body['data'] ?? [] as $row) {
                $key = $row['timetable_pk'] . ':' . $row['faculty_id'];
                if (isset($seen[$key])) {
                    $duplicates[] = $key;
                }
                $seen[$key] = true;
            }
        }

        $this->assertSame([], $duplicates, 'The same row appeared on more than one page.');
        $this->assertCount($expected, $seen, 'Paging did not reach every row.');
    }

    /**
     * Feedback Details paging must reach every row exactly once.
     *
     * Its ORDER BY (session date, faculty name, student first name) is not unique, so before a
     * tie-break was added OFFSET/LIMIT showed one row on two pages and left another unreachable
     * — measured 4,708 reported but only 4,707 reachable.
     */
    public function test_feedback_details_paging_reaches_every_row_exactly_once(): void
    {
        $this->skipUnlessFeedbackData();
        $this->actingAsSuperAdmin();

        $controller = new FeedbackController();

        // Every course carrying feedback in this database has ended, so the report must be
        // asked for archived courses or it reports on an empty set.
        $courseId = DB::table('topic_feedback as tf')
            ->join('timetable as t', 'tf.timetable_pk', '=', 't.pk')
            ->groupBy('t.course_master_pk')
            ->orderByRaw('COUNT(*) DESC')
            ->value('t.course_master_pk');

        $params = ['course_type' => 'archived', 'program_id' => $courseId];

        $first = $controller->feedbackDetails($this->request($params + ['page' => 1]))->getData();
        $total = (int) ($first['totalRecords'] ?? 0);
        if ($total === 0) {
            $this->markTestSkipped('Feedback Details reports no rows for the busiest course.');
        }

        $perPage = 10;
        $seen = [];
        $duplicates = 0;

        for ($page = 1; $page <= (int) ceil($total / $perPage); $page++) {
            $data = $controller->feedbackDetails($this->request($params + ['page' => $page]))->getData();

            foreach ($data['groupedData'] ?? [] as $group) {
                foreach ($group as $row) {
                    $id = $row['feedback_id'] ?? null;
                    if ($id === null) {
                        continue;
                    }
                    if (isset($seen[$id])) {
                        $duplicates++;
                    }
                    $seen[$id] = true;
                }
            }
        }

        $this->assertSame(0, $duplicates, 'A feedback row appeared on more than one page.');
        $this->assertCount(
            $total,
            $seen,
            'Paging did not reach every row — ' . (count($seen)) . " of $total reachable."
        );
    }

    /**
     * The report's ORDER BY must be a total order.
     *
     * The walk above is a functional check, but whether ties actually surface as a duplicate
     * depends on the execution plan — refreshing table statistics was enough to hide it on this
     * dataset. So assert the invariant itself instead: the sort columns are demonstrably not
     * unique, therefore the query must carry a unique tie-break, or paging is undefined again
     * the next time the plan shifts.
     */
    public function test_feedback_details_orders_by_a_unique_tie_break(): void
    {
        $this->skipUnlessFeedbackData();
        $this->actingAsSuperAdmin();

        // 1. The declared sort columns really are ambiguous on live data.
        $tiedGroups = DB::selectOne("
            SELECT COUNT(*) AS n FROM (
                SELECT 1
                FROM topic_feedback tf
                JOIN timetable tt ON tf.timetable_pk = tt.pk
                JOIN faculty_master fm ON tf.faculty_pk = fm.pk
                JOIN student_master sm ON tf.student_master_pk = sm.pk
                WHERE tf.is_submitted = 1
                GROUP BY tt.START_DATE, fm.full_name, sm.first_name
                HAVING COUNT(*) > 1
            ) x
        ")->n;

        $this->assertGreaterThan(
            0,
            (int) $tiedGroups,
            'No ties in the sort columns, so this guard proves nothing — revisit the assertion below.'
        );

        // 2. The query the report actually runs must break those ties on a unique column.
        $statements = [];
        DB::listen(function ($q) use (&$statements) {
            $statements[] = $q->sql;
        });

        $controller = new FeedbackController();
        $controller->feedbackDetails($this->request(['course_type' => 'archived']));

        $ordered = array_values(array_filter(
            $statements,
            fn ($sql) => str_contains($sql, 'from `topic_feedback`') && str_contains($sql, 'order by')
        ));

        $this->assertNotEmpty($ordered, 'Feedback Details ran no ordered query against topic_feedback.');

        foreach ($ordered as $sql) {
            $orderBy = substr($sql, strrpos($sql, 'order by'));
            $this->assertStringContainsString(
                '`tf`.`pk`',
                $orderBy,
                "Feedback Details ordered by non-unique columns with no unique tie-break, so "
                . "OFFSET/LIMIT paging is undefined:\n$orderBy"
            );
        }
    }

    /**
     * The deferred join must return the same page as a plain OFFSET/LIMIT over the same
     * builder — same rows, same order. It only skips carrying the wide payload through the
     * sort, so any divergence means the two disagree about which rows the page holds.
     */
    public function test_feedback_details_deferred_join_matches_a_direct_slice(): void
    {
        $this->skipUnlessFeedbackData();

        $controller = new FeedbackController();

        $query = DB::table('topic_feedback as tf')
            ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
            ->join('course_master as cm', 'tt.course_master_pk', '=', 'cm.pk')
            ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
            ->join('student_master as sm', 'tf.student_master_pk', '=', 'sm.pk')
            ->select('tf.pk as feedback_id', 'tf.content', 'tf.remark', 'fm.full_name', 'sm.first_name')
            ->where('tf.is_submitted', 1)
            ->orderBy('tt.START_DATE', 'DESC')
            ->orderBy('fm.full_name')
            ->orderBy('sm.first_name')
            ->orderBy('tf.pk');

        foreach ([[0, 10], [30, 10], [137, 7]] as [$offset, $perPage]) {
            $deferred = collect($this->callPrivate($controller, 'feedbackDetailsPage', clone $query, $offset, $perPage))
                ->pluck('feedback_id')->all();

            $direct = (clone $query)->offset($offset)->limit($perPage)->pluck('feedback_id')->all();

            $this->assertSame(
                $direct,
                $deferred,
                "Deferred join disagreed with a direct slice at offset $offset."
            );
        }
    }

    /** The EXISTS-driven faculty dropdown must list exactly what the DISTINCT join listed. */
    public function test_faculty_dropdown_matches_the_distinct_join(): void
    {
        $this->skipUnlessFeedbackData();
        $this->actingAsSuperAdmin();

        $controller = new FeedbackController();

        $expected = DB::table('topic_feedback as tf')
            ->join('timetable as t', 'tf.timetable_pk', '=', 't.pk')
            ->join('faculty_master as f', 'tf.faculty_pk', '=', 'f.pk')
            ->whereRaw(self::QUALIFIES)
            ->distinct()
            ->pluck('f.pk')
            ->map(fn ($pk) => (int) $pk)
            ->sort()
            ->values()
            ->all();

        $actual = collect($this->callPrivate($controller, 'databaseFacultyOptions', $this->request()))
            ->pluck('pk')
            ->map(fn ($pk) => (int) $pk)
            ->sort()
            ->values()
            ->all();

        $this->assertSame($expected, $actual);
    }

    /** Busting the generation must make previously cached entries unreachable. */
    public function test_cache_generation_bust_invalidates_entries(): void
    {
        $key = 'test:' . uniqid('', true);

        $first = FeedbackReportCache::remember($key, 60, fn () => 'original');
        $this->assertSame('original', $first);
        $this->assertSame('original', FeedbackReportCache::remember($key, 60, fn () => 'recomputed'));

        $before = FeedbackReportCache::generation();
        FeedbackReportCache::bust();
        $this->assertGreaterThan($before, FeedbackReportCache::generation());

        $this->assertSame(
            'recomputed',
            FeedbackReportCache::remember($key, 60, fn () => 'recomputed'),
            'Entry survived a generation bump — invalidation is not working.'
        );
    }

    /** A cache store failure must not take a report down. */
    public function test_cache_failure_falls_back_to_computing_the_value(): void
    {
        config(['cache.default' => 'no-such-store']);

        $this->assertSame(
            'computed',
            FeedbackReportCache::remember('test:fallback', 60, fn () => 'computed')
        );
    }

    /**
     * Put the container's request into faculty-portal mode with no accessible courses.
     *
     * ScopesSessionFeedbackReports reads these off request() rather than the injected
     * Request, so the container instance is what has to be replaced.
     */
    private function actAsFacultyPortalUserWithoutCourses(string $method, string $verb): Request
    {
        $request = Request::create('/faculty-report-probe', $verb, ['faculty_name' => 'All Faculty']);
        $request->attributes->set('is_faculty_feedback_report', true);
        $request->attributes->set('faculty_report_faculty_pk', 1);
        $request->attributes->set('faculty_report_course_ids', []);

        $this->app->instance('request', $request);

        return $request;
    }

    /**
     * A viewer scoped to zero courses must get an empty report, not a database error.
     *
     * applyFeedbackReportCourseScope() emits whereRaw('1 = 0') for such a viewer. A GROUP BY
     * that names fewer columns than it selects survives only while MySQL can infer the missing
     * dependencies across the join equalities -- and a constant-false predicate makes the
     * optimiser drop those joins, so the inference fails and ONLY_FULL_GROUP_BY raises 1055.
     * Every reduced key in FeedbackReportGrouping therefore has to carry one primary key per
     * joined table whose columns it selects. This test is what catches it if one goes missing.
     *
     * @dataProvider scopedReportMethods
     */
    public function test_a_viewer_with_no_accessible_courses_does_not_break_the_report(
        string $method,
        string $verb
    ): void {
        $this->skipUnlessFeedbackData();
        $this->actingAsSuperAdmin();

        $request = $this->actAsFacultyPortalUserWithoutCourses($method, $verb);

        try {
            (new FeedbackController())->{$method}($request);
        } catch (\Illuminate\Database\QueryException $e) {
            $this->fail(
                $method . '() raised a database error for a viewer scoped to zero courses: '
                . $e->getMessage()
            );
        }

        $this->assertTrue(true, $method . '() completed for a zero-course viewer.');
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function scopedReportMethods(): array
    {
        return [
            'faculty average'        => ['showFacultyAverage', 'GET'],
            'faculty average excel'  => ['exportExcel', 'GET'],
            'faculty average pdf'    => ['exportPdf', 'GET'],
            'faculty average print'  => ['printFacultyAverage', 'GET'],
            'faculty feedback export' => ['exportFacultyFeedback', 'POST'],
            'faculty feedback print' => ['printFacultyFeedback', 'GET'],
            'feedback details'       => ['feedbackDetails', 'GET'],
            'feedback details export' => ['exportFeedbackDetails', 'POST'],
        ];
    }

    /**
     * Every reduced key must name a primary key for each joined table it selects from.
     *
     * The failure above is only visible at runtime on one code path; this asserts the rule
     * directly against the constants, so widening a projection without adding the matching
     * key fails here too.
     */
    public function test_every_reduced_group_key_carries_a_primary_key_per_joined_table(): void
    {
        $expected = [
            'DATABASE_GRID'   => ['f.pk', 't.pk', 'c.pk'],
            'FACULTY_AVERAGE' => ['fm.pk'],
            'FACULTY_VIEW'    => ['tt.pk', 'cm.pk', 'fm.pk'],
        ];

        foreach ($expected as $constant => $requiredKeys) {
            $actual = constant(FeedbackReportGrouping::class . '::' . $constant);

            foreach ($requiredKeys as $key) {
                $this->assertContains(
                    $key,
                    $actual,
                    $constant . ' no longer groups ' . $key . '. Without it the dependency for that '
                    . "table's columns rests on the join equality, which MySQL discards under a "
                    . 'constant-false WHERE, raising error 1055.'
                );
            }
        }
    }
}
