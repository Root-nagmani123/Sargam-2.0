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
 * Who may save a course's weekly info sheet (CalendarController::saveWeeklyInfo):
 * Training, Super Admin, Admin, Training MCTP Admin, Training IST and
 * Training-Induction for any course, and the course's own Coordinator and
 * Assistant Coordinators. Everyone else is refused - including a coordinator
 * of a different course, and a trainee login whose user_id happens to equal
 * a coordinator's employee pk.
 *
 * Writes run inside DatabaseTransactions and are rolled back.
 */
class WeeklyInfoEditAccessTest extends TestCase
{
    use DatabaseTransactions;

    private const EDITING_ROLES = ['Training', 'Super Admin', 'Admin', 'Training MCTP Admin', 'Training IST', 'Training-Induction'];

    private const COURSE_OWN = 90300001;   // the fixture faculty coordinates this one

    private const COURSE_ASSIST = 90300002;   // ...assists on this one

    private const COURSE_OTHER = 90300003;   // ...and has nothing to do with this one

    private const FACULTY_PK = 90300011;

    private const EMPLOYEE_PK = 90300021;   // user_credentials.user_id of the coordinator

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('course_week_notes')) {
            $this->markTestSkipped('course_week_notes is not migrated on this database.');
        }

        foreach ([self::COURSE_OWN, self::COURSE_ASSIST, self::COURSE_OTHER] as $pk) {
            DB::table('course_master')->insert([
                'pk' => $pk, 'course_name' => "Fixture course $pk", 'couse_short_name' => "FC$pk",
                'course_year' => 2026, 'start_year' => '2026-01-05', 'end_date' => '2026-01-30',
            ]);
        }

        DB::table('faculty_master')->insert([
            'pk' => self::FACULTY_PK, 'faculty_type' => '1', 'first_name' => 'Owner', 'full_name' => 'Sheet Owner',
            'employee_master_pk' => self::EMPLOYEE_PK,
            'country_master_pk' => 0, 'state_master_pk' => 0, 'state_district_mapping_pk' => 0, 'city_master_pk' => 0,
        ]);

        DB::table('course_coordinator_master')->insert([
            ['courses_master_pk' => self::COURSE_OWN, 'Coordinator_name' => (string) self::FACULTY_PK, 'Assistant_Coordinator_name' => '1', 'created_date' => now()],
            // Assistant coordinators are a comma-separated list.
            ['courses_master_pk' => self::COURSE_ASSIST, 'Coordinator_name' => '1', 'Assistant_Coordinator_name' => '2,'.self::FACULTY_PK, 'created_date' => now()],
            ['courses_master_pk' => self::COURSE_OTHER, 'Coordinator_name' => '1', 'Assistant_Coordinator_name' => '2', 'created_date' => now()],
        ]);
    }

    /** Status of a save by an actor with the given session roles, user_id and login category. */
    private function saveAs(array $roles, ?int $userId, int $courseId, ?string $category = 'E'): int
    {
        $user = new User;
        $user->forceFill(['pk' => 90300099, 'user_id' => $userId, 'user_category' => $category]);
        Auth::setUser($user);
        Session::put('user_roles', $roles);

        $request = Request::create('/calendar/weekly-info/save', 'POST', [
            'course_id' => $courseId, 'week_start' => '2026-01-19', 'mention_of_week' => 'access test',
        ]);

        try {
            return app(CalendarController::class)->saveWeeklyInfo($request)->getStatusCode();
        } catch (HttpException $e) {
            return $e->getStatusCode();
        }
    }

    private function savedNote(int $courseId): ?string
    {
        return DB::table('course_week_notes')->where('course_master_pk', $courseId)->value('mention_of_week');
    }

    public function test_each_editing_role_may_save_any_course(): void
    {
        foreach (self::EDITING_ROLES as $role) {
            DB::table('course_week_notes')->where('course_master_pk', self::COURSE_OTHER)->delete();

            $this->assertSame(200, $this->saveAs([$role], null, self::COURSE_OTHER), $role);
            $this->assertSame('access test', $this->savedNote(self::COURSE_OTHER), $role);
        }
    }

    public function test_the_course_coordinator_may_save(): void
    {
        $this->assertSame(200, $this->saveAs([], self::EMPLOYEE_PK, self::COURSE_OWN));
        $this->assertSame('access test', $this->savedNote(self::COURSE_OWN));
    }

    public function test_an_assistant_coordinator_may_save(): void
    {
        $this->assertSame(200, $this->saveAs([], self::EMPLOYEE_PK, self::COURSE_ASSIST));
    }

    public function test_a_coordinator_of_another_course_is_refused(): void
    {
        $this->assertSame(403, $this->saveAs([], self::EMPLOYEE_PK, self::COURSE_OTHER));
        $this->assertNull($this->savedNote(self::COURSE_OTHER));
    }

    /**
     * user_credentials.user_id is a per-category id: on a trainee login (no
     * user_category) it is not an employee pk, but it can equal one. Such a
     * login must not inherit that employee's coordinator courses.
     */
    public function test_a_trainee_login_whose_user_id_matches_a_coordinator_is_refused(): void
    {
        $this->assertSame(403, $this->saveAs([], self::EMPLOYEE_PK, self::COURSE_OWN, null));
        $this->assertSame(403, $this->saveAs([], self::EMPLOYEE_PK, self::COURSE_ASSIST, 'S'));
        $this->assertNull($this->savedNote(self::COURSE_OWN));
    }

    public function test_a_user_with_no_editing_role_and_no_coordinator_record_is_refused(): void
    {
        $this->assertSame(403, $this->saveAs(['FC Reports Viewer'], null, self::COURSE_OWN));
    }

    /**
     * The save locks the course row before it writes, so a course_id with no
     * course behind it is refused rather than leaving orphan coordinator and
     * week-note rows.
     */
    public function test_a_course_that_does_not_exist_is_refused(): void
    {
        try {
            $this->saveAs(['Training IST'], null, 90300999);
            $this->fail('a save for a missing course was accepted');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('course_id', $e->errors());
        }
        $this->assertSame(0, DB::table('course_coordinator_master')->where('courses_master_pk', 90300999)->count());
        $this->assertNull($this->savedNote(90300999));
    }

    /** created_date has no default; the save must still work for a course with no coordinator row. */
    public function test_a_course_without_a_coordinator_row_can_be_saved(): void
    {
        DB::table('course_coordinator_master')->where('courses_master_pk', self::COURSE_OTHER)->delete();

        $this->assertSame(200, $this->saveAs(['Training IST'], null, self::COURSE_OTHER));
        $this->assertSame(1, DB::table('course_coordinator_master')->where('courses_master_pk', self::COURSE_OTHER)->count());
    }
}
