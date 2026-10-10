<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Faculty accounts hold the Spatie role "Faculty" (login copies it into the
 * session); no account holds "Internal Faculty" or "Guest Faculty". Screens
 * that narrowed data only for those two names handed a Faculty login the
 * unscoped, every-faculty listing. Each test logs in as a Faculty-role user
 * linked to a faculty_master row that is CC of course X and of nothing else.
 *
 * Fixtures (all rolled back):
 *   course X — CC = the logged-in faculty; course Y — CC = another faculty.
 */
class FacultyRoleScopingTest extends TestCase
{
    private bool $inTransaction = false;

    private int $employeePk = 0;

    private int $me = 0;

    private int $other = 0;

    private int $courseX = 0;

    private int $courseY = 0;

    /** Manual transaction so a database-less host skips instead of erroring in setUp(). */
    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('No database connection: '.$e->getMessage());
        }

        if (! DB::table('roles')->where('name', 'Faculty')->exists()) {
            $this->markTestSkipped("no 'Faculty' role in this database");
        }

        DB::beginTransaction();
        $this->inTransaction = true;

        $employeePk = (int) DB::table('faculty_master')->max('employee_master_pk') + 910001;

        $this->me = $this->faculty('Frs Mine', $employeePk);
        $this->other = $this->faculty('Frs Other', null);

        $today = now()->startOfDay();
        foreach (['courseX' => 'FRS Course X', 'courseY' => 'FRS Course Y'] as $prop => $name) {
            $this->{$prop} = DB::table('course_master')->insertGetId([
                'course_name' => $name, 'couse_short_name' => $name, 'course_year' => (int) $today->format('Y'),
                'active_inactive' => 1, 'end_date' => $today->copy()->addDays(30),
            ]);
        }
        DB::table('course_coordinator_master')->insert([
            ['courses_master_pk' => $this->courseX, 'Coordinator_name' => $this->me, 'created_date' => now()],
            ['courses_master_pk' => $this->courseY, 'Coordinator_name' => $this->other, 'created_date' => now()],
        ]);

        $this->employeePk = $employeePk;
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }

        parent::tearDown();
    }

    private function faculty(string $name, ?int $employeePk): int
    {
        return DB::table('faculty_master')->insertGetId([
            'faculty_type' => '1', 'first_name' => $name, 'full_name' => $name,
            'country_master_pk' => 0, 'state_master_pk' => 0, 'state_district_mapping_pk' => 0, 'city_master_pk' => 0,
            'employee_master_pk' => $employeePk,
        ]);
    }

    private function student(string $name): int
    {
        return DB::table('student_master')->insertGetId([
            'service_master_pk' => 0, 'user_id' => 'frs-'.$name.'-'.$this->me,
            'display_name' => $name, 'first_name' => $name, 'generated_OT_code' => 'FRS-'.$name, 'status' => 1,
        ]);
    }

    /**
     * As a Faculty-role login of $category, with the session roles login gives it.
     * An employee login (E) stores the employee pk in user_id; a faculty login (F)
     * stores faculty_master.pk itself. $userId overrides that, for the denial cases.
     */
    private function asFacultyRole(string $category = 'E', ?int $userId = null)
    {
        $userId ??= $category === 'F' ? $this->me : $this->employeePk;
        $user = User::where('user_name', "frs.login.{$category}.{$userId}")->first();
        if (! $user) {
            $user = User::find(DB::table('user_credentials')->insertGetId([
                'user_name' => "frs.login.{$category}.{$userId}", 'user_id' => $userId, 'user_category' => $category,
            ]));
            $user->assignRole('Faculty');
        }

        return $this->actingAs($user->fresh())->withSession(['user_roles' => $user->roles()->pluck('name')->all()]);
    }

    /** PR #335 review F-008: an employee login and a faculty login (user_category F) resolve to the same faculty. */
    public static function logins(): array
    {
        return ['employee login (E)' => ['E'], 'faculty login (F)' => ['F']];
    }

    /**
     * A timetable session on $course cloned from an existing mapped session,
     * so the attendance grid's relations (group, venue, class session) resolve
     * the way they do for real rows. Returns [timetable pk, mapping pk].
     */
    private function timetableSession(int $course, int $facultyPk): array
    {
        $mapping = DB::table('course_group_timetable_mapping as m')
            ->join('timetable as t', 't.pk', '=', 'm.timetable_pk')
            ->orderByDesc('m.pk')
            ->first(['m.pk as mapping_pk', 't.pk as timetable_pk']);
        if (! $mapping) {
            $this->markTestSkipped('no mapped timetable session to clone');
        }

        $timetable = (array) DB::table('timetable')->where('pk', $mapping->timetable_pk)->first();
        unset($timetable['pk']);
        $timetable['course_master_pk'] = $course;
        $timetable['faculty_master'] = (string) $facultyPk;
        $timetablePk = DB::table('timetable')->insertGetId($timetable);

        $row = (array) DB::table('course_group_timetable_mapping')->where('pk', $mapping->mapping_pk)->first();
        unset($row['pk']);
        $row['Programme_pk'] = $course;
        $row['timetable_pk'] = $timetablePk;

        return [$timetablePk, DB::table('course_group_timetable_mapping')->insertGetId($row)];
    }

    /** @dataProvider logins */
    public function test_medical_exception_view_gives_a_faculty_role_login_its_cc_courses_not_the_admin_view(string $category): void
    {
        $employee = DB::table('employee_master')->insertGetId(['first_name' => 'Frs Doctor']);
        $exemption = fn (int $course, string $student) => DB::table('student_medical_exemption')->insert([
            'course_master_pk' => $course, 'student_master_pk' => $this->student($student),
            'employee_master_pk' => $employee,
            'exemption_category_master_pk' => (int) DB::table('exemption_category_master')->value('pk'),
            'exemption_medical_speciality_pk' => (int) DB::table('exemption_medical_speciality_master')->value('pk'),
            'from_date' => now()->subDay(), 'to_date' => now()->addDay(), 'active_inactive' => 1,
        ]);
        $exemption($this->courseX, 'FrsMedMine');
        $exemption($this->courseY, 'FrsMedOther');

        $response = $this->asFacultyRole($category)->get('/medical-exception-faculty-view');

        $response->assertOk();
        $response->assertViewMissing('facultyData');
        $this->assertSame(['FrsMedMine'], collect($response->viewData('data'))->pluck('student_name')->all());
        $this->assertSame([$this->courseX], collect($response->viewData('courses'))->pluck('pk')->map(fn ($pk) => (int) $pk)->all());
        $response->assertDontSee('FrsMedOther');
    }

    /** @dataProvider logins */
    public function test_attendance_list_and_course_filter_scope_a_faculty_role_login_to_its_cc_courses(string $category): void
    {
        [, $mine] = $this->timetableSession($this->courseX, $this->other);
        $this->timetableSession($this->courseY, $this->me);

        $list = $this->asFacultyRole($category)->postJson('/attendance/get-attendance-list', [
            'page_context' => 'attendance', 'draw' => 1, 'start' => 0, 'length' => 100,
        ]);
        $list->assertOk();
        $this->assertSame(1, $list->json('recordsTotal'), 'only the CC course X session, not every course');
        $this->assertSame([$this->courseX], array_map('intval', array_column($list->json('data'), 'Programme_pk')));
        $this->assertSame([$mine], array_map('intval', array_column($list->json('data'), 'pk')));

        $index = $this->asFacultyRole($category)->get('/attendance');
        $index->assertOk();
        $offered = collect($index->viewData('courseMasters'))->merge($index->viewData('archivedCourseMasters'))
            ->pluck('pk')->map(fn ($pk) => (int) $pk)->all();
        $this->assertSame([$this->courseX], $offered);
    }

    /** @dataProvider logins */
    public function test_feedback_details_shows_a_faculty_role_login_only_its_own_feedback(string $category): void
    {
        // Tag both courses with the Faculty role so get_Role_by_course() lets
        // them through; the own-feedback filter is then the only narrowing.
        $facultyRoleId = (int) DB::table('roles')->where('name', 'Faculty')->value('id');
        DB::table('course_master')->whereIn('pk', [$this->courseX, $this->courseY])->update(['user_role_master_pk' => $facultyRoleId]);

        $feedback = function (int $course, int $facultyPk, string $student) {
            [$timetablePk] = $this->timetableSession($course, $facultyPk);
            DB::table('timetable')->where('pk', $timetablePk)->update([
                'START_DATE' => now()->subDay(), 'END_DATE' => now()->subDay(), 'subject_topic' => 'Frs topic',
            ]);
            DB::table('topic_feedback')->insert([
                'timetable_pk' => $timetablePk, 'student_master_pk' => $this->student($student), 'topic_name' => 'Frs topic',
                'faculty_pk' => $facultyPk, 'is_submitted' => 1, 'presentation' => '4', 'content' => '4', 'remark' => 'ok',
                'created_date' => now(),
            ]);
        };
        $feedback($this->courseX, $this->me, 'FrsFbMine');
        $feedback($this->courseX, $this->other, 'FrsFbOther');
        $feedback($this->courseY, $this->other, 'FrsFbOtherY');

        $response = $this->asFacultyRole($category)->getJson('/feedback_details?course_type=current');
        $response->assertOk();

        $grouped = $response->json('groupedData') ?? [];
        $this->assertNotEmpty($grouped, 'the fixture feedback must be listed at all');

        $names = [];
        array_walk_recursive($grouped, function ($value, $key) use (&$names) {
            if ($key === 'faculty_name') {
                $names[] = $value;
            }
        });
        $this->assertSame(['Frs Mine'], array_values(array_unique($names)));
    }

    /**
     * Neither identity falls back to the other: a faculty login (F) whose
     * user_id is an employee pk, and an employee login (E) whose user_id is a
     * faculty pk, resolve to no faculty and see nobody's courses or feedback.
     */
    public function test_an_unrelated_login_sees_no_faculty_rows_on_any_of_the_three_screens(): void
    {
        $facultyRoleId = (int) DB::table('roles')->where('name', 'Faculty')->value('id');
        DB::table('course_master')->whereIn('pk', [$this->courseX, $this->courseY])->update(['user_role_master_pk' => $facultyRoleId]);
        [$timetablePk] = $this->timetableSession($this->courseX, $this->me);
        DB::table('timetable')->where('pk', $timetablePk)->update(['START_DATE' => now()->subDay(), 'END_DATE' => now()->subDay()]);
        DB::table('topic_feedback')->insert([
            'timetable_pk' => $timetablePk, 'student_master_pk' => $this->student('FrsFbDenied'), 'topic_name' => 'Frs topic',
            'faculty_pk' => $this->me, 'is_submitted' => 1, 'presentation' => '4', 'content' => '4', 'remark' => 'ok', 'created_date' => now(),
        ]);

        foreach (['F' => $this->employeePk, 'E' => $this->me] as $category => $userId) {
            $list = $this->asFacultyRole($category, $userId)->postJson('/attendance/get-attendance-list', [
                'page_context' => 'attendance', 'draw' => 1, 'start' => 0, 'length' => 100,
            ])->assertOk();
            $this->assertSame(0, $list->json('recordsTotal'), "$category login holding the other kind of id: attendance");

            $medical = $this->asFacultyRole($category, $userId)->get('/medical-exception-faculty-view')->assertOk();
            $this->assertSame([], collect($medical->viewData('courses'))->pluck('pk')->all(), "$category login: medical exception courses");

            $feedback = $this->asFacultyRole($category, $userId)->getJson('/feedback_details?course_type=current')->assertOk();
            $this->assertStringNotContainsString('Frs Mine', (string) $feedback->getContent(), "$category login: feedback");
        }
    }
}
