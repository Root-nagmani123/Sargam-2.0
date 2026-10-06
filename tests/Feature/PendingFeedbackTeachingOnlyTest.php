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
 * The expression is evaluated by MySQL itself against synthetic faculty_details
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

    public function test_a_teaching_slot_stored_with_a_string_pk_still_counts(): void
    {
        $this->assertSame(1, $this->pendingFor($this->details('Teaching', (string) self::VIEWER)));
    }

    public function test_a_legacy_session_without_faculty_details_still_counts(): void
    {
        $this->assertSame(1, $this->pendingFor(null));
    }
}
