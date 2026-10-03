<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CalendarController;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Who may READ a course's info sheet (review findings F-005, F-014).
 *
 * weeklyInfoMeta() feeds only the editor, so it answers editors only.
 * weeklyInfoPdf() for a named course answers its editors and anyone whose
 * timetable scope includes the course - a trainee in one of its groups, a
 * faculty member teaching in it, staff whose role covers it - and refuses the
 * rest; a course outside that scope must not print its profile, coordinators
 * or participant count.
 *
 * Writes run inside DatabaseTransactions and are rolled back.
 */
class WeeklyInfoReadAccessTest extends TestCase
{
    use DatabaseTransactions;

    private const COURSE_IN = 90310001;    // the trainee and the faculty member belong here

    private const COURSE_OUT = 90310002;   // ...and have nothing to do with this one

    private const FACULTY_PK = 90310011;

    private const GROUP_PK = 90310021;

    private const SESSION_PK = 90310031;

    private const STUDENT_PK = 90310041;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('course_week_notes')) {
            $this->markTestSkipped('course_week_notes is not migrated on this database.');
        }

        foreach ([self::COURSE_IN, self::COURSE_OUT] as $pk) {
            DB::table('course_master')->insert([
                'pk' => $pk, 'course_name' => "Fixture course $pk", 'couse_short_name' => "FC$pk",
                'course_year' => 2026, 'start_year' => '2026-01-05', 'end_date' => '2026-01-30',
            ]);
        }
        DB::table('faculty_master')->insert([
            'pk' => self::FACULTY_PK, 'faculty_type' => '1', 'first_name' => 'Read', 'full_name' => 'Read Access Faculty',
            'country_master_pk' => 0, 'state_master_pk' => 0, 'state_district_mapping_pk' => 0, 'city_master_pk' => 0,
        ]);
        DB::table('group_type_master_course_master_map')->insert([
            'pk' => self::GROUP_PK, 'type_name' => 1, 'course_name' => self::COURSE_IN, 'group_name' => 'Full Group',
        ]);
        DB::table('timetable')->insert([
            'pk' => self::SESSION_PK, 'course_master_pk' => self::COURSE_IN, 'subject_master_pk' => 0,
            'subject_module_master_pk' => 0, 'course_group_type_master' => 1, 'group_name' => json_encode([(string) self::GROUP_PK]),
            'venue_id' => 0, 'subject_topic' => 'Read access session', 'START_DATE' => '2026-01-20',
            'class_session' => '09:00 to 10:00', 'faculty_master' => json_encode([(string) self::FACULTY_PK]),
        ]);
        DB::table('course_group_timetable_mapping')->insert([
            'group_pk' => self::GROUP_PK, 'timetable_pk' => self::SESSION_PK,
        ]);
        DB::table('student_course_group_map')->insert([
            'student_master_pk' => self::STUDENT_PK, 'group_type_master_course_master_map_pk' => self::GROUP_PK,
        ]);
    }

    /** Sign in a fixture actor: session roles, user_id and login category. */
    private function actAs(array $roles, ?int $userId, ?string $category): void
    {
        $user = new User;
        $user->forceFill(['pk' => 90310099, 'user_id' => $userId, 'user_category' => $category]);
        Auth::setUser($user);
        Session::put('user_roles', $roles);
    }

    private function status(callable $call): int
    {
        try {
            return $call()->getStatusCode();
        } catch (HttpException $e) {
            return $e->getStatusCode();
        }
    }

    private function pdf(int $courseId): int
    {
        $request = Request::create('/calendar/weekly-info/pdf', 'GET', ['course_id' => $courseId, 'week_start' => '2026-01-19']);

        return $this->status(fn () => app(CalendarController::class)->weeklyInfoPdf($request));
    }

    private function meta(int $courseId): int
    {
        $request = Request::create('/calendar/weekly-info/meta', 'GET', ['course_id' => $courseId, 'week_start' => '2026-01-19']);

        return $this->status(fn () => app(CalendarController::class)->weeklyInfoMeta($request));
    }

    public function test_meta_answers_an_editor_of_the_course(): void
    {
        $this->actAs(['Training IST'], null, 'E');
        $this->assertSame(200, $this->meta(self::COURSE_IN));
    }

    public function test_meta_refuses_a_user_who_cannot_edit_the_course(): void
    {
        $this->actAs(['FC Reports Viewer'], null, 'E');
        $this->assertSame(403, $this->meta(self::COURSE_IN));

        // A trainee in the course can read its printed sheet, but not the editor's payload.
        $this->actAs(['Student-OT'], self::STUDENT_PK, 'S');
        $this->assertSame(403, $this->meta(self::COURSE_IN));
    }

    public function test_a_trainee_reads_their_own_courses_sheet_only(): void
    {
        $this->actAs(['Student-OT'], self::STUDENT_PK, 'S');
        $this->assertSame(200, $this->pdf(self::COURSE_IN));
        $this->assertSame(403, $this->pdf(self::COURSE_OUT));
    }

    public function test_a_faculty_member_reads_the_sheet_of_a_course_they_teach_only(): void
    {
        $this->actAs(['Internal Faculty'], self::FACULTY_PK, 'F');
        $this->assertSame(200, $this->pdf(self::COURSE_IN));
        $this->assertSame(403, $this->pdf(self::COURSE_OUT));
    }

    public function test_staff_without_a_role_covering_the_course_are_refused(): void
    {
        $this->actAs(['FC Reports Viewer'], null, 'E');
        $this->assertSame(403, $this->pdf(self::COURSE_IN));
    }

    public function test_an_editor_and_super_admin_read_any_courses_sheet(): void
    {
        $this->actAs(['Training IST'], null, 'E');
        $this->assertSame(200, $this->pdf(self::COURSE_OUT));

        $this->actAs(['Super Admin'], null, 'E');
        $this->assertSame(200, $this->pdf(self::COURSE_OUT));
    }
}
