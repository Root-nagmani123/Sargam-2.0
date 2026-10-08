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
 * The expressions are evaluated by MySQL itself against synthetic faculty_details
 * values — no table row is read or written.
 */
class PendingFeedbackTeachingOnlyTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const VIEWER = 990001;

    private function pendingFor(?string $facultyDetails, int $submitted = 0): int
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
            [$facultyDetails, json_encode([(string) self::VIEWER]), $submitted]
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

    // F-048: the trainee form requires JSON_VALID(faculty_details) = 1.
    public function test_a_session_without_faculty_details_owes_nothing(): void
    {
        $this->assertSame(0, $this->pendingFor(null));
    }

    public function test_a_session_with_invalid_faculty_details_owes_nothing(): void
    {
        $this->assertSame(0, $this->pendingFor('not json'));
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
