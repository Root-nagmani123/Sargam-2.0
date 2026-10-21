<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

class OtPendingFeedbackCountTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as rollbackSetUp;
    }

    private int $course;

    private int $student;

    private int $faculty;

    private int $venue;

    protected function setUp(): void
    {
        $this->rollbackSetUp();
        Log::shouldReceive('error')->never();
        $this->faculty = (int) DB::table('faculty_master')->value('pk');
        $this->venue = (int) DB::table('venue_master')->value('venue_id');
        if (! DB::table('faculty_master')->where('pk', $this->faculty)->exists()
            || ! DB::table('venue_master')->where('venue_id', $this->venue)->exists()) {
            $this->markTestSkipped('needs faculty and venue reference rows');
        }
        $this->course = (int) DB::table('course_master')->insertGetId([
            'course_name' => 'F-075 count course', 'course_year' => 2026, 'active_inactive' => 1,
        ]);
        $this->student = (int) DB::table('student_master')->insertGetId([
            'service_master_pk' => 0, 'first_name' => 'F075', 'user_id' => 'f075-'.uniqid(),
        ]);
        DB::table('student_master_course__map')->insert([
            'student_master_pk' => $this->student, 'course_master_pk' => $this->course, 'active_inactive' => 1,
        ]);
        session(['user_roles' => []]);
    }

    private function sessionRow(?string $master = null, ?string $details = null, array $changes = []): int
    {
        $pk = (int) DB::table('timetable')->insertGetId($changes + [
            'course_master_pk' => $this->course,
            'subject_master_pk' => 0, 'subject_module_master_pk' => 0, 'course_group_type_master' => 0,
            'group_name' => '[]', 'venue_id' => $this->venue, 'subject_topic' => 'F-075 session',
            'START_DATE' => '2026-01-05', 'END_DATE' => '2026-01-05',
            'class_session' => '09:00 AM - 10:00 AM', 'feedback_checkbox' => 1,
            'faculty_master' => $master ?? json_encode([(string) $this->faculty]),
            'faculty_details' => $details ?? json_encode([['faculty_pk' => $this->faculty, 'role' => 'Teaching']]),
        ]);
        $this->attendance($pk);

        return $pk;
    }

    private function attendance(int $timetable): void
    {
        DB::table('course_student_attendance')->insert([
            'Student_master_pk' => $this->student, 'group_type_master_course_master_map_pk' => 0,
            'course_master_pk' => $this->course, 'timetable_pk' => $timetable, 'status' => '1',
        ]);
    }

    private function pending(): int
    {
        $method = new \ReflectionMethod(UserController::class, 'getOtPendingFeedbackCount');
        $method->setAccessible(true);

        return $method->invoke(app(UserController::class), $this->student);
    }

    public function test_sql_count_deduplicates_attendance_enrolment_and_faculty_entries_and_is_fresh_after_submission(): void
    {
        $pk = $this->sessionRow(json_encode([(string) $this->faculty, (string) $this->faculty]));
        $this->attendance($pk);
        DB::table('student_master_course__map')->insert([
            'student_master_pk' => $this->student, 'course_master_pk' => $this->course, 'active_inactive' => 1,
        ]);
        $this->assertSame(1, $this->pending());
        DB::table('topic_feedback')->insert([
            'timetable_pk' => $pk, 'student_master_pk' => $this->student, 'faculty_pk' => $this->faculty,
            'topic_name' => 'F-075 session', 'is_submitted' => 0,
        ]);
        $this->assertSame(1, $this->pending(), 'drafts are still pending');
        DB::table('topic_feedback')->where('timetable_pk', $pk)->update(['is_submitted' => 1]);
        $this->assertSame(0, $this->pending(), 'submission is reflected immediately');
    }

    public function test_master_shapes_preserve_the_original_string_membership_rule(): void
    {
        $f = (string) $this->faculty;
        foreach ([json_encode([$f]), json_encode($f), json_encode([[[$f]]])] as $master) {
            $this->sessionRow($master);
        }
        foreach ([json_encode([$this->faculty]), $f, json_encode(['0'.$f]), '{"faculty":"'.$f.'"}', 'invalid JSON'] as $master) {
            $this->sessionRow($master);
        }
        $this->assertSame(3, $this->pending());
    }

    public function test_each_faculty_on_a_session_is_counted_and_submitted_independently(): void
    {
        $other = DB::table('faculty_master')->where('pk', '!=', $this->faculty)->value('pk');
        if ($other === null) {
            $this->markTestSkipped('needs two faculty reference rows');
        }
        $pk = $this->sessionRow(
            json_encode([(string) $this->faculty, (string) $other]),
            json_encode([
                ['faculty_pk' => $this->faculty, 'role' => 'Teaching'],
                ['faculty_pk' => (int) $other, 'role' => 'Teaching'],
            ])
        );
        $this->assertSame(2, $this->pending());
        DB::table('topic_feedback')->insert([
            'timetable_pk' => $pk, 'student_master_pk' => $this->student, 'faculty_pk' => $this->faculty,
            'topic_name' => 'F-075 session', 'is_submitted' => 1,
        ]);
        $this->assertSame(1, $this->pending());
    }

    public function test_teaching_feedback_and_session_eligibility_are_not_broadened(): void
    {
        $this->sessionRow();
        $this->sessionRow(details: json_encode([['faculty_pk' => $this->faculty, 'role' => 'Sectional']]));
        $this->sessionRow(details: json_encode([['faculty_pk' => (string) $this->faculty, 'role' => 'Teaching']]));
        $this->sessionRow(details: 'invalid JSON');
        $this->sessionRow(details: 'null');
        $this->sessionRow(changes: ['feedback_checkbox' => 0]);
        $this->sessionRow(changes: ['END_DATE' => '2099-01-05']);
        $this->sessionRow(changes: ['class_session' => 'invalid time']);
        $absent = $this->sessionRow();
        DB::table('course_student_attendance')->where('timetable_pk', $absent)->update(['status' => '0']);
        $this->assertSame(1, $this->pending());
        DB::table('student_master_course__map')->where('student_master_pk', $this->student)->update(['active_inactive' => 0]);
        $this->assertSame(0, $this->pending());
    }

    public function test_ot_group_scope_remains_required_without_duplicate_counting(): void
    {
        $this->actingAs((new User)->forceFill(['pk' => 990075, 'user_id' => $this->student, 'user_category' => 'S']));
        session(['user_roles' => ['Student-OT']]);
        $pk = $this->sessionRow();
        $this->assertSame(0, $this->pending());
        DB::table('course_group_timetable_mapping')->insert(['timetable_pk' => $pk, 'group_pk' => 0]);
        $this->assertSame(0, $this->pending());
        DB::table('student_course_group_map')->insert([
            'student_master_pk' => $this->student, 'group_type_master_course_master_map_pk' => 0, 'active_inactive' => 1,
        ]);
        $this->assertSame(1, $this->pending());
        DB::table('course_group_timetable_mapping')->insert(['timetable_pk' => $pk, 'group_pk' => 0]);
        $this->assertSame(1, $this->pending());
    }

    public function test_the_database_returns_only_a_count_without_sorting_or_a_faculty_scan(): void
    {
        $this->sessionRow();
        $queries = DB::connection()->pretend(fn () => $this->pending());
        $query = collect($queries)->first(fn ($q) => str_contains($q['query'], '`timetable` as `t`'));
        $this->assertNotNull($query);
        $this->assertStringContainsString('COUNT(DISTINCT t.pk, f.pk)', $query['query']);
        $this->assertStringContainsString('f.pk = CAST(listed_faculty.faculty_txt AS UNSIGNED)', $query['query']);
        $this->assertStringNotContainsString('order by', $query['query']);
    }

    public function test_pending_count_group_indexes_have_the_required_column_order(): void
    {
        foreach ([
            'course_group_timetable_mapping' => ['idx_cgtm_timetable_group', ['timetable_pk', 'group_pk']],
            'student_course_group_map' => ['idx_scgm_student_group', ['student_master_pk', 'group_type_master_course_master_map_pk']],
        ] as $table => [$name, $columns]) {
            $index = collect(DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$name]));
            $this->assertSame($columns, $index->sortBy('Seq_in_index')->pluck('Column_name')->all());
        }
    }
    
}

