<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\FeedbackController;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * On the faculty portal the Pending Feedback count expected one feedback from the
 * viewer for EVERY session they were listed on, whatever their role. Trainees are
 * only offered feedback for Teaching faculty, so a Sectional/Administration slot
 * stayed "1 not given" for ever (PR #334 F-040).
 *
 * The fix kept a fallback that still charged the viewer on sessions the trainee
 * form never offers: NULL / invalid faculty_details, and a string faculty_pk
 * (PR #334 F-048). The rule is now the trainee form's own. And the "Feedback
 * given" tab listed trainees whose only sessions with the viewer owed nothing,
 * at 0 given / 0 pending (F-050).
 *
 * F-048 went too far for legacy sessions (NULL / invalid faculty_details): the
 * trainee's Student Feedback page offers those for every faculty_master entry
 * stored as a string pk, so they owe the viewer 1 when listed there (F-054).
 *
 * The expressions are evaluated by MySQL itself against synthetic faculty_details
 * values — no table row is read or written.
 */
class PendingFeedbackTeachingOnlyTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const VIEWER = 990001;

    /** pendingFor() default: faculty_master lists the viewer as a string pk. */
    private const MASTER = '__viewer__';

    private function pendingFor(?string $facultyDetails, int $submitted = 0, ?string $facultyMaster = self::MASTER): int
    {
        request()->attributes->set('is_faculty_feedback_report', true);
        request()->attributes->set('faculty_report_faculty_pk', self::VIEWER);

        $m = new \ReflectionMethod(FeedbackController::class, 'pendingStudentsPendingExpressionSql');
        $m->setAccessible(true);
        $expr = $m->invoke(new FeedbackController);

        $row = DB::selectOne(
            "SELECT GREATEST({$expr}, 0) AS pending
             FROM (SELECT ? AS faculty_details, ? AS faculty_master) t
             CROSS JOIN (SELECT ? AS submitted_count) tf",
            [$facultyDetails, $facultyMaster === self::MASTER ? json_encode([(string) self::VIEWER]) : $facultyMaster, $submitted]
        );

        return (int) $row->pending;
    }

    private function details(string $role, $pk = self::VIEWER): string
    {
        return json_encode([
            ['faculty_pk' => 4242, 'faculty_type' => 1, 'role' => 'Teaching', 'feedback' => 'both'],
            ['faculty_pk' => $pk, 'faculty_type' => 1, 'role' => $role, 'feedback' => 'none'],
        ]);
    }

    public function test_a_session_where_the_viewer_is_teaching_owes_one_feedback(): void
    {
        $this->assertSame(1, $this->pendingFor($this->details('Teaching')));
        $this->assertSame(0, $this->pendingFor($this->details('Teaching'), 1));
    }

    public function test_a_session_where_the_viewer_is_sectional_owes_nothing(): void
    {
        $this->assertSame(0, $this->pendingFor($this->details('Sectional')));
    }

    public function test_a_session_where_the_viewer_is_administration_owes_nothing(): void
    {
        $this->assertSame(0, $this->pendingFor($this->details('Administration')));
    }

    // F-048: the trainee form matches JSON_OBJECT('faculty_pk', f.pk, ...) with an
    // int pk, so a string pk is offered to no trainee and owes the viewer nothing.
    public function test_a_teaching_slot_stored_with_a_string_pk_owes_nothing(): void
    {
        $this->assertSame(0, $this->pendingFor($this->details('Teaching', (string) self::VIEWER)));
    }

    // F-054: a legacy session is offered by the trainee's Student Feedback page
    // ("old logic") for each faculty_master entry stored as a string pk.
    public function test_a_legacy_session_listing_the_viewer_owes_one_feedback(): void
    {
        $this->assertSame(1, $this->pendingFor(null));
        $this->assertSame(1, $this->pendingFor(''));
        $this->assertSame(1, $this->pendingFor('not json'));
        $this->assertSame(1, $this->pendingFor(null, 0, json_encode(['4242', (string) self::VIEWER])));
        $this->assertSame(1, $this->pendingFor(null, 0, json_encode((string) self::VIEWER)), 'bare "pk"');
        $this->assertSame(0, $this->pendingFor(null, 1));
    }

    // F-054: the shapes the trainee page skips (CalendarController::OLD_FACULTY_JSON_TABLE).
    public function test_a_legacy_session_the_trainee_page_skips_owes_nothing(): void
    {
        $this->assertSame(0, $this->pendingFor(null, 0, json_encode([self::VIEWER])), 'numeric pk');
        $this->assertSame(0, $this->pendingFor(null, 0, (string) self::VIEWER), 'bare number');
        $this->assertSame(0, $this->pendingFor(null, 0, json_encode(['0' . self::VIEWER])), 'leading zero');
        $this->assertSame(0, $this->pendingFor(null, 0, json_encode(['4242'])), 'someone else');
        $this->assertSame(0, $this->pendingFor(null, 0, null), 'no faculty_master');
        $this->assertSame(0, $this->pendingFor('not json', 0, 'not json either'));
    }

    // F-059: the detail rows used a PHP twin of this SQL, which disagreed with it on
    // five shapes. They now select the SQL itself, so these values are what both the
    // totals and the detail rows see.
    public function test_the_f059_shapes_follow_the_sql_rule(): void
    {
        $v = self::VIEWER;

        $this->assertSame(1, $this->pendingFor("{\"faculty_pk\":{$v},\"role\":\"Teaching\"}"), 'bare object');
        $this->assertSame(1, $this->pendingFor("[{\"faculty_pk\":[{$v}],\"role\":\"Teaching\"}]"), 'pk in a list');
        $this->assertSame(1, $this->pendingFor("[[{\"faculty_pk\":{$v},\"role\":\"Teaching\"}]]"), 'nested');
        $this->assertSame(1, $this->pendingFor("[{\"faculty_pk\":{$v}.0,\"role\":\"Teaching\"}]"), 'float pk');
        $this->assertSame(0, $this->pendingFor(null, 0, "{\"0\":\"{$v}\"}"), 'legacy object master');
    }

    // F-059: there is one rule. The detail query selects the totals' expression for
    // the viewer, and the PHP twin is gone.
    public function test_the_detail_rows_select_the_totals_expression(): void
    {
        request()->attributes->set('is_faculty_feedback_report', true);
        request()->attributes->set('faculty_report_faculty_pk', self::VIEWER);
        $controller = new FeedbackController;

        $rule = new \ReflectionMethod(FeedbackController::class, 'viewerExpectedFeedbackSql');
        $rule->setAccessible(true);
        $detail = new \ReflectionMethod(FeedbackController::class, 'pendingStudentsDetailQuery');
        $detail->setAccessible(true);

        $sql = $detail->invoke($controller, \Illuminate\Http\Request::create('/', 'GET'), [1])->toSql();

        $this->assertStringContainsString($rule->invoke($controller, self::VIEWER, 't') . ' as viewer_expected', $sql);
        $this->assertFalse(method_exists(FeedbackController::class, 'viewerIsTeachingOnSession'));
    }
    /** Whether one trainee with these sessions lands in the given / not-given tab. */
    private function listedIn(string $tab, array $sessions): bool
    {
        request()->attributes->set('is_faculty_feedback_report', true);
        request()->attributes->set('faculty_report_faculty_pk', self::VIEWER);

        $rows = [];
        $bindings = [];
        foreach ($sessions as [$details, $submitted]) {
            $rows[] = 'SELECT ? AS faculty_details, ? AS faculty_master, ? AS submitted_count';
            array_push($bindings, $details, json_encode([(string) self::VIEWER]), $submitted);
        }

        // One student: all their session rows in one group. The expressions read
        // t.faculty_details and tf.submitted_count; both live on the derived t here.
        $query = DB::query()
            ->fromRaw('(' . implode(' UNION ALL ', $rows) . ') t', $bindings)
            ->selectRaw('1 AS student')
            ->groupBy('student');

        $m = new \ReflectionMethod(FeedbackController::class, 'applyPendingStudentsFeedbackStateHaving');
        $m->setAccessible(true);
        $m->invoke(new FeedbackController, $query, \Illuminate\Http\Request::create('/', 'GET', ['filter_feedback_state' => $tab]));

        $raw = str_replace('tf.submitted_count', 't.submitted_count', $query->toSql());

        return DB::select($raw, $query->getBindings()) !== [];
    }

    // F-050: a trainee whose only session with the viewer is Sectional owes the
    // viewer nothing, so they are in neither tab.
    public function test_a_sectional_only_trainee_is_in_neither_tab(): void
    {
        $sectional = [[$this->details('Sectional'), 0]];

        $this->assertFalse($this->listedIn('given', $sectional), 'not in Feedback given');
        $this->assertFalse($this->listedIn('not_given', $sectional), 'not in Feedback not given');
    }

    public function test_a_trainee_who_gave_the_viewers_teaching_feedback_is_in_the_given_tab(): void
    {
        $this->assertTrue($this->listedIn('given', [[$this->details('Teaching'), 1], [$this->details('Sectional'), 0]]));
        $this->assertFalse($this->listedIn('given', [[$this->details('Teaching'), 0]]));
        $this->assertTrue($this->listedIn('not_given', [[$this->details('Teaching'), 0]]));
    }
}
