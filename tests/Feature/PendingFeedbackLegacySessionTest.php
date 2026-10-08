<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\FeedbackController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Round 6 (F-048) made the faculty portal's Pending Students owe the viewer
 * nothing on legacy sessions (NULL / invalid faculty_details). The trainee's
 * Student Feedback page still offers those sessions for every faculty_master
 * entry stored as a string pk, and submitFeedback() accepts them. So the portal
 * showed the trainee owing nothing, and once they submitted, "given" counted it
 * (capped by the all-faculty count) while the detail rows did not list it
 * (PR #334 F-054). Product decision 2026-10-08: the portal follows the trainee
 * page.
 *
 * Runs the real aggregate and detail-row code against rows written inside the
 * test transaction, which is rolled back.
 */
class PendingFeedbackLegacySessionTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const VIEWER = 990001;

    private const COLLEAGUE = 990002;

    private int $course;

    private int $student;

    private function timetableRow(?string $facultyDetails, string $facultyMaster): int
    {
        $pk = (int) DB::table('timetable')->insertGetId([
            'course_master_pk' => $this->course,
            'subject_master_pk' => 0,
            'subject_module_master_pk' => 0,
            'course_group_type_master' => 0,
            'group_name' => '[]',
            'venue_id' => 0,
            'subject_topic' => 'F-054 session',
            'START_DATE' => '2026-01-05',
            'END_DATE' => '2026-01-05',
            'class_session' => '09:00 AM - 10:00 AM',
            'feedback_checkbox' => 1,
            'faculty_details' => $facultyDetails,
            'faculty_master' => $facultyMaster,
        ]);

        DB::table('course_student_attendance')->insert([
            'Student_master_pk' => $this->student,
            'group_type_master_course_master_map_pk' => 0,
            'course_master_pk' => $this->course,
            'timetable_pk' => $pk,
            'status' => '1',
        ]);

        return $pk;
    }

    private function feedback(int $timetablePk, int $facultyPk): void
    {
        DB::table('topic_feedback')->insert([
            'timetable_pk' => $timetablePk,
            'student_master_pk' => $this->student,
            'topic_name' => 'F-054 session',
            'faculty_pk' => $facultyPk,
            'is_submitted' => 1,
        ]);
    }

    /** The student's row on the viewer's Pending Students page, or null when not listed. */
    private function pendingStudentsRow(string $tab): ?array
    {
        request()->attributes->set('is_faculty_feedback_report', true);
        request()->attributes->set('faculty_report_faculty_pk', self::VIEWER);
        request()->attributes->set('faculty_report_course_ids', [$this->course]);

        $request = Request::create('/', 'GET', ['filter_feedback_state' => $tab]);
        $controller = new FeedbackController;

        $agg = new \ReflectionMethod(FeedbackController::class, 'buildPendingStudentsAggregateSubquery');
        $agg->setAccessible(true);
        $aggRows = DB::query()
            ->fromSub($agg->invoke($controller, $request), 'agg_students')
            ->where('student_pk', $this->student)
            ->get();

        $merge = new \ReflectionMethod(FeedbackController::class, 'mergePendingGroupedAggregatesWithDetailRows');
        $merge->setAccessible(true);

        return $merge->invoke($controller, $request, $aggRows)[0] ?? null;
    }

    private ?string $savedSqlMode = null;

    protected function setUp(): void
    {
        parent::setUp();

        // The aggregate groups by sm.pk alone and selects the name columns, which
        // MySQL 5.7+ accepts as functionally dependent on the primary key. MariaDB
        // (the local XAMPP server) does not detect that and rejects the query under
        // ONLY_FULL_GROUP_BY, so relax that one mode for this session, then restore it.
        if (str_contains((string) DB::selectOne('SELECT VERSION() AS v')->v, 'MariaDB')) {
            $this->savedSqlMode = (string) DB::selectOne('SELECT @@SESSION.sql_mode AS m')->m;
            DB::statement("SET SESSION sql_mode = REPLACE(@@SESSION.sql_mode, 'ONLY_FULL_GROUP_BY', '')");
        }

        $this->course = (int) DB::table('course_master')->insertGetId([
            'course_name' => 'F-054 course',
            'course_year' => 2026,
            'active_inactive' => 1,
            'end_date' => '2099-12-31',
        ]);
        $this->student = (int) DB::table('student_master')->insertGetId([
            'service_master_pk' => 0,
            'first_name' => 'F054',
            'user_id' => 'f054-' . uniqid(),
        ]);
        DB::table('student_master_course__map')->insert([
            'student_master_pk' => $this->student,
            'course_master_pk' => $this->course,
            'active_inactive' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->savedSqlMode !== null) {
            DB::statement('SET SESSION sql_mode = ?', [$this->savedSqlMode]);
            $this->savedSqlMode = null;
        }

        parent::tearDown();
    }

    public function test_legacy_sessions_listing_the_viewer_reconcile_with_their_detail_rows(): void
    {
        $viewerOnly = json_encode([(string) self::VIEWER]);

        // Two legacy sessions the trainee page offers for the viewer: one answered, one not.
        $answered = $this->timetableRow(null, $viewerOnly);
        $this->feedback($answered, self::VIEWER);
        $this->timetableRow(null, $viewerOnly);

        // Legacy, but the viewer's pk is a number: the trainee page skips it.
        $this->timetableRow(null, json_encode([self::VIEWER]));

        // Viewer is Sectional: owes nothing, so a stray submission is not "given".
        $sectional = $this->timetableRow(json_encode([
            ['faculty_pk' => self::COLLEAGUE, 'faculty_type' => 1, 'role' => 'Teaching', 'feedback' => 'both'],
            ['faculty_pk' => self::VIEWER, 'faculty_type' => 1, 'role' => 'Sectional', 'feedback' => 'none'],
        ]), json_encode([(string) self::VIEWER, (string) self::COLLEAGUE]));
        $this->feedback($sectional, self::VIEWER);

        $row = $this->pendingStudentsRow('not_given');

        $this->assertNotNull($row, 'listed under Feedback not given: one legacy session is unanswered');
        $this->assertSame(1, $row['feedback_given']);
        $this->assertSame(1, $row['feedback_not_given']);

        $statuses = array_column($row['sessions'], 'feedback_status');
        sort($statuses);
        $this->assertSame(['given', 'not_given'], $statuses);
        $this->assertCount($row['feedback_given'] + $row['feedback_not_given'], $row['sessions']);
    }

    public function test_a_fully_answered_legacy_trainee_is_in_the_given_tab(): void
    {
        $answered = $this->timetableRow('not json', json_encode([(string) self::VIEWER]));
        $this->feedback($answered, self::VIEWER);

        $this->assertNull($this->pendingStudentsRow('not_given'));

        $row = $this->pendingStudentsRow('given');
        $this->assertNotNull($row, 'listed under Feedback given');
        $this->assertSame(1, $row['feedback_given']);
        $this->assertSame(0, $row['feedback_not_given']);
        $this->assertCount(1, $row['sessions']);
    }

    // F-059: on these shapes the PHP twin and the SQL total disagreed, so a session
    // was counted but not listed (or listed but not counted). Every counted session
    // must now be listed, and no other.
    public function test_every_counted_session_is_listed_whatever_its_json_shape(): void
    {
        $v = self::VIEWER;
        $viewerOnly = json_encode([(string) $v]);

        // Counted by the totals: the trainee Teaching-faculty page accepts these shapes.
        $this->timetableRow("{\"faculty_pk\":{$v},\"role\":\"Teaching\"}", $viewerOnly);
        $this->timetableRow("[{\"faculty_pk\":[{$v}],\"role\":\"Teaching\"}]", $viewerOnly);
        $this->timetableRow("[[{\"faculty_pk\":{$v},\"role\":\"Teaching\"}]]", $viewerOnly);
        $this->timetableRow("[{\"faculty_pk\":{$v}.0,\"role\":\"Teaching\"}]", $viewerOnly);
        // Not counted: a legacy faculty_master stored as an object.
        $this->timetableRow(null, "{\"0\":\"{$v}\"}");

        $row = $this->pendingStudentsRow('not_given');

        $this->assertNotNull($row);
        $this->assertSame(0, $row['feedback_given']);
        $this->assertSame(4, $row['feedback_not_given']);
        $this->assertCount($row['feedback_given'] + $row['feedback_not_given'], $row['sessions']);
    }

    // The "given" cap on its own: no legacy session involved.
    public function test_given_does_not_count_a_session_that_owes_the_viewer_nothing(): void
    {
        $this->timetableRow(json_encode([
            ['faculty_pk' => self::VIEWER, 'faculty_type' => 1, 'role' => 'Teaching', 'feedback' => 'both'],
        ]), json_encode([(string) self::VIEWER]));
        $sectional = $this->timetableRow(json_encode([
            ['faculty_pk' => self::COLLEAGUE, 'faculty_type' => 1, 'role' => 'Teaching', 'feedback' => 'both'],
            ['faculty_pk' => self::VIEWER, 'faculty_type' => 1, 'role' => 'Sectional', 'feedback' => 'none'],
        ]), json_encode([(string) self::VIEWER, (string) self::COLLEAGUE]));
        $this->feedback($sectional, self::VIEWER);

        $row = $this->pendingStudentsRow('not_given');

        $this->assertNotNull($row);
        $this->assertSame(0, $row['feedback_given'], 'the Sectional session owes nothing, so its stray row is not "given"');
        $this->assertSame(1, $row['feedback_not_given']);
        $this->assertCount(1, $row['sessions']);
    }
}
