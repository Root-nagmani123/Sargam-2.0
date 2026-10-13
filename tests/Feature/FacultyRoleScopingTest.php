<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
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
     * As a login of $category holding $role, with the session roles login gives it.
     * An employee login (E) stores the employee pk in user_id; a faculty login (F)
     * stores faculty_master.pk itself. $userId overrides that, for the denial cases.
     * A role this database lacks is created inside the test's transaction.
     */
    private function asFacultyRole(string $category = 'E', ?int $userId = null, string $role = 'Faculty')
    {
        $userId ??= $category === 'F' ? $this->me : $this->employeePk;
        $userName = 'frs.login.'.Str::slug($role).".{$category}.{$userId}";
        $user = User::where('user_name', $userName)->first();
        if (! $user) {
            $user = User::find(DB::table('user_credentials')->insertGetId([
                'user_name' => $userName, 'user_id' => $userId, 'user_category' => $category,
            ]));
            $user->assignRole(Role::findOrCreate($role, 'web'));
        }

        return $this->actingAs($user->fresh())->withSession(['user_roles' => $user->roles()->pluck('name')->all()]);
    }

    /** PR #335 review F-008: an employee login and a faculty login (user_category F) resolve to the same faculty. */
    public static function logins(): array
    {
        return ['employee login (E)' => ['E'], 'faculty login (F)' => ['F']];
    }

    /** PR #335 F-008 decision: the four rules cover a login holding any of these roles. */
    public static function facultyRoles(): array
    {
        return ['Faculty' => ['Faculty'], 'Internal Faculty' => ['Internal Faculty'], 'Guest Faculty' => ['Guest Faculty']];
    }

    private function medicalExemption(int $course, string $student): void
    {
        DB::table('student_medical_exemption')->insert([
            'course_master_pk' => $course, 'student_master_pk' => $this->student($student),
            'employee_master_pk' => DB::table('employee_master')->insertGetId(['first_name' => 'Frs Doctor']),
            'exemption_category_master_pk' => (int) DB::table('exemption_category_master')->value('pk'),
            'exemption_medical_speciality_pk' => (int) DB::table('exemption_medical_speciality_master')->value('pk'),
            'from_date' => now()->subDay(), 'to_date' => now()->addDay(), 'active_inactive' => 1,
        ]);
    }

    /**
     * A submitted feedback row for $facultyPk on a session of $course held yesterday.
     * The course is tagged with $role so get_Role_by_course() lets it through and
     * the own-feedback filter is the only narrowing.
     */
    private function feedback(int $course, int $facultyPk, string $student, string $role = 'Faculty'): void
    {
        DB::table('course_master')->where('pk', $course)->update(['user_role_master_pk' => Role::findOrCreate($role, 'web')->id]);
        [$timetablePk] = $this->timetableSession($course, $facultyPk);
        DB::table('timetable')->where('pk', $timetablePk)->update([
            'START_DATE' => now()->subDay(), 'END_DATE' => now()->subDay(), 'subject_topic' => 'Frs topic',
        ]);
        DB::table('topic_feedback')->insert([
            'timetable_pk' => $timetablePk, 'student_master_pk' => $this->student($student), 'topic_name' => 'Frs topic',
            'faculty_pk' => $facultyPk, 'is_submitted' => 1, 'presentation' => '4', 'content' => '4', 'remark' => 'ok',
            'created_date' => now(),
        ]);
    }

    /** faculty_name values in a /feedback_details JSON response. */
    private function feedbackFacultyNames($response): array
    {
        $names = [];
        $grouped = $response->json('groupedData') ?? [];
        array_walk_recursive($grouped, function ($value, $key) use (&$names) {
            if ($key === 'faculty_name') {
                $names[] = $value;
            }
        });

        return array_values(array_unique($names));
    }

    /** Course pks on the attendance grid, and offered by its course filter (active + archived). */
    private function attendanceCourses($login): array
    {
        $list = $login->postJson('/attendance/get-attendance-list', [
            'page_context' => 'attendance', 'draw' => 1, 'start' => 0, 'length' => 100,
        ])->assertOk();
        $index = $login->get('/attendance')->assertOk();

        $sorted = fn ($pks) => collect($pks)->map(fn ($pk) => (int) $pk)->unique()->sort()->values()->all();

        return [
            'grid' => $sorted(array_column($list->json('data'), 'Programme_pk')),
            'filter' => $sorted(collect($index->viewData('courseMasters'))->merge($index->viewData('archivedCourseMasters'))->pluck('pk')),
        ];
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
        $this->medicalExemption($this->courseX, 'FrsMedMine');
        $this->medicalExemption($this->courseY, 'FrsMedOther');

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
        $this->feedback($this->courseX, $this->me, 'FrsFbMine');
        $this->feedback($this->courseX, $this->other, 'FrsFbOther');
        $this->feedback($this->courseY, $this->other, 'FrsFbOtherY');

        $response = $this->asFacultyRole($category)->getJson('/feedback_details?course_type=current');
        $response->assertOk();

        $this->assertNotEmpty($response->json('groupedData') ?? [], 'the fixture feedback must be listed at all');
        $this->assertSame(['Frs Mine'], $this->feedbackFacultyNames($response));
    }

    /**
     * PR #335 F-008 rules 1-3 hold for each of the three faculty roles: Attendance
     * and Medical Exception show only courses the faculty is CC or ACC of (an ACC
     * given alone or, on legacy rows, in a comma list), Feedback only its own rows.
     * Before the fix a Guest Faculty login got every course on the attendance grid,
     * and Attendance left out a course whose ACC was a comma list.
     *
     * @dataProvider facultyRoles
     */
    public function test_each_faculty_role_sees_its_cc_and_acc_courses_and_its_own_feedback_only(string $role): void
    {
        $today = now()->startOfDay();
        $course = fn (string $name) => DB::table('course_master')->insertGetId([
            'course_name' => $name, 'couse_short_name' => $name, 'course_year' => (int) $today->format('Y'),
            'active_inactive' => 1, 'end_date' => $today->copy()->addDays(30),
        ]);
        $acc = $course('FRS Course ACC');
        $accList = $course('FRS Course ACC list');
        DB::table('course_coordinator_master')->insert([
            ['courses_master_pk' => $acc, 'Coordinator_name' => $this->other, 'Assistant_Coordinator_name' => (string) $this->me, 'created_date' => now()],
            ['courses_master_pk' => $accList, 'Coordinator_name' => $this->other, 'Assistant_Coordinator_name' => "{$this->other},{$this->me}", 'created_date' => now()],
        ]);
        $mine = [$this->courseX, $acc, $accList];
        sort($mine);

        foreach ([...$mine, $this->courseY] as $c) {
            $this->timetableSession($c, $this->other);
            $this->medicalExemption($c, "FrsMed{$c}");
        }
        $this->feedback($this->courseX, $this->me, 'FrsFbMine', $role);
        $this->feedback($this->courseX, $this->other, 'FrsFbOther', $role);
        $this->feedback($this->courseY, $this->other, 'FrsFbOtherY', $role);

        $this->assertSame(['grid' => $mine, 'filter' => $mine], $this->attendanceCourses($this->asFacultyRole('E', null, $role)), "$role: attendance");

        $medical = $this->asFacultyRole('E', null, $role)->get('/medical-exception-faculty-view')->assertOk();
        $medical->assertViewMissing('facultyData');
        $this->assertSame($mine, collect($medical->viewData('courses'))->pluck('pk')->map(fn ($pk) => (int) $pk)->sort()->values()->all(), "$role: medical exception courses");
        $medical->assertDontSee("FrsMed{$this->courseY}");

        $feedback = $this->asFacultyRole('E', null, $role)->getJson('/feedback_details?course_type=current')->assertOk();
        $this->assertSame(['Frs Mine'], $this->feedbackFacultyNames($feedback), "$role: feedback");
    }

    /**
     * PR #335 F-008: a faculty with a faculty_master row but no CC/ACC mapping
     * sees no course on Attendance or Medical Exception (rules 1, 3) and still
     * sees its own feedback (rule 2).
     *
     * @dataProvider logins
     */
    public function test_a_faculty_with_no_cc_or_acc_mapping_sees_no_courses_but_still_its_own_feedback(string $category): void
    {
        $lone = $this->faculty('Frs Lone', $this->employeePk + 1);

        $this->timetableSession($this->courseX, $lone);
        $this->medicalExemption($this->courseX, 'FrsMedLone');
        $this->feedback($this->courseX, $lone, 'FrsFbLone');
        $this->feedback($this->courseX, $this->me, 'FrsFbMine');

        $userId = $category === 'F' ? $lone : $this->employeePk + 1;

        $this->assertSame(['grid' => [], 'filter' => []], $this->attendanceCourses($this->asFacultyRole($category, $userId)));

        $medical = $this->asFacultyRole($category, $userId)->get('/medical-exception-faculty-view')->assertOk();
        $this->assertSame([], collect($medical->viewData('courses'))->all());
        $this->assertSame([], collect($medical->viewData('data'))->all());

        $feedback = $this->asFacultyRole($category, $userId)->getJson('/feedback_details?course_type=current')->assertOk();
        $this->assertSame(['Frs Lone'], $this->feedbackFacultyNames($feedback));
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
