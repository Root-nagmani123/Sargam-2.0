<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * GET /faculty-mdo-escort-exception-view
 *
 * An escort duty can have several faculty: the full list is the CSV in
 * mdo_escot_duty_map.faculty_master_pks, faculty_master_pk holds only the first.
 * The admin view used to reach duties through FacultyMaster::mdoEscotDutyMaps()
 * (faculty_master_pk), so the second and later faculty never saw the duty and
 * the Faculty filter could not find it. Faculty logins could not reach ended
 * (archived) courses at all.
 *
 * Fixtures (all rolled back):
 *   archived course A  — D1: faculty F1,F2,F3 (CSV), student S1
 *                        D3: faculty F1 only, student S2
 *   active course B    — D2: legacy row, faculty_master_pk = F2, no CSV, student S2
 *   F3 is coordinator of A only, and is the faculty login.
 *
 * A faculty login's access is decided per course: the CC or ACC of a course
 * sees every escort duty on it; on any other course a faculty sees only the
 * duties it is assigned to (any CSV position, or a legacy faculty_master_pk);
 * with neither, nothing. The Course filter offers exactly the courses that
 * leaves a visible escort duty on, in the selected tab.
 *
 * The admin branch lists every trainee's exceptions, so it is gated on the
 * screen's menu permission: an Officer Trainee or Employee-only account gets
 * 403 on both tabs, a permission holder (Training-Induction) and Super Admin
 * get the rows.
 */
class FacultyMdoEscortExceptionViewTest extends TestCase
{
    private const URL = '/faculty-mdo-escort-exception-view';

    private bool $inTransaction = false;

    private array $f = [];

    private array $c = [];

    private array $d = [];

    private ?User $admin = null;

    private ?User $facultyUser = null;

    private int $employeePk = 0;

    /** Next suffix for logins and trainees made by the per-test fixtures. */
    private int $loginSeq = 0;

    /**
     * The transaction is opened by hand, not by DatabaseTransactions: that trait
     * connects from parent::setUp(), so with it a database-less host errors
     * before the skip below can run (same shape as StreamDeleteGuardTest).
     */
    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('No database connection: '.$e->getMessage());
        }

        DB::beginTransaction();
        $this->inTransaction = true;

        $adminId = DB::table('model_has_roles as mr')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->where('r.name', 'Super Admin')->value('mr.model_id');
        $this->admin = $adminId ? User::find($adminId) : null;

        $today = now()->startOfDay();
        $this->c['A'] = DB::table('course_master')->insertGetId([
            'course_name' => 'FME Test Archived Course', 'course_year' => (int) $today->format('Y'),
            'active_inactive' => 1, 'end_date' => $today->copy()->subDays(30),
        ]);
        $this->c['B'] = DB::table('course_master')->insertGetId([
            'course_name' => 'FME Test Active Course', 'course_year' => (int) $today->format('Y'),
            'active_inactive' => 1, 'end_date' => $today->copy()->addDays(30),
        ]);

        $employeePk = (int) DB::table('faculty_master')->max('employee_master_pk') + 900001;
        foreach (['F1' => 'Fme Alpha', 'F2' => 'Fme Bravo', 'F3' => 'Fme Charlie'] as $key => $name) {
            $this->f[$key] = DB::table('faculty_master')->insertGetId([
                'faculty_type' => '1', 'first_name' => $name, 'full_name' => $name,
                'country_master_pk' => 0, 'state_master_pk' => 0, 'state_district_mapping_pk' => 0, 'city_master_pk' => 0,
                'employee_master_pk' => $key === 'F3' ? $employeePk : null,
            ]);
        }

        $s1 = DB::table('student_master')->insertGetId(['service_master_pk' => 0, 'user_id' => 'fme-s1-'.$employeePk, 'display_name' => 'Fme Student One', 'generated_OT_code' => 'FME-OT-1', 'status' => 1]);
        $s2 = DB::table('student_master')->insertGetId(['service_master_pk' => 0, 'user_id' => 'fme-s2-'.$employeePk, 'display_name' => 'Fme Student Two', 'generated_OT_code' => 'FME-OT-2', 'status' => 1]);

        $duty = fn (array $row) => DB::table('mdo_escot_duty_map')->insertGetId($row + [
            'mdo_duty_type_master_pk' => 2, 'mdo_date' => $today->copy()->subDays(40), 'Time_from' => '09:00', 'Time_to' => '10:00',
        ]);
        $this->d['D1'] = $duty(['course_master_pk' => $this->c['A'], 'selected_student_list' => $s1,
            'faculty_master_pk' => $this->f['F1'], 'faculty_master_pks' => implode(',', [$this->f['F1'], $this->f['F2'], $this->f['F3']])]);
        $this->d['D2'] = $duty(['course_master_pk' => $this->c['B'], 'selected_student_list' => $s2,
            'faculty_master_pk' => $this->f['F2'], 'faculty_master_pks' => null]);
        $this->d['D3'] = $duty(['course_master_pk' => $this->c['A'], 'selected_student_list' => $s2,
            'faculty_master_pk' => $this->f['F1'], 'faculty_master_pks' => (string) $this->f['F1']]);

        DB::table('course_coordinator_master')->insert([
            'courses_master_pk' => $this->c['A'], 'Coordinator_name' => $this->f['F3'], 'created_date' => now(),
        ]);

        $userPk = DB::table('user_credentials')->insertGetId(['user_name' => 'fme.test.'.$employeePk, 'user_id' => $employeePk]);
        $this->facultyUser = User::find($userPk);
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

    /**
     * A fresh account holding one Spatie role, with the session roles login
     * would give it (students get the Student-OT pseudo-role instead).
     */
    private function userWithRole(string $role, string $category, array $sessionRoles): array
    {
        if (! DB::table('roles')->where('name', $role)->exists()) {
            $this->markTestSkipped("no '$role' role in this database");
        }

        $pk = DB::table('user_credentials')->insertGetId([
            'user_name' => 'fme.'.Str::slug($role).'.'.$this->employeePk,
            'user_id' => $this->employeePk + 1,
            'user_category' => $category,
        ]);
        $user = User::find($pk);
        $user->assignRole($role);

        return [$user->fresh(), $sessionRoles];
    }

    private function asUser(array $actor, array $query = [])
    {
        [$user, $sessionRoles] = $actor;

        return $this->actingAs($user)
            ->withSession(['user_roles' => $sessionRoles])
            ->get(self::URL.($query ? '?'.http_build_query($query) : ''));
    }

    private function asAdmin(array $query = [])
    {
        if (! $this->admin) {
            $this->markTestSkipped('no Super Admin in this database');
        }

        return $this->actingAs($this->admin)->get(self::URL.($query ? '?'.http_build_query($query) : ''));
    }

    private function asFaculty(array $query = [])
    {
        return $this->actingAs($this->facultyUser)
            ->withSession(['user_roles' => ['Internal Faculty']])
            ->get(self::URL.($query ? '?'.http_build_query($query) : ''));
    }

    /** faculty pk => [duty pks] from the admin view's cards. */
    private function cards($response): array
    {
        $cards = [];
        foreach ($response->viewData('facultyData') as $faculty) {
            foreach ($faculty['courses'] as $course) {
                foreach ($course['student_duties'] as $duty) {
                    $cards[$faculty['faculty_id']][] = $duty['duty_pk'];
                }
            }
        }
        ksort($cards);

        return $cards;
    }

    public function test_officer_trainee_and_employee_only_users_get_403_on_both_tabs(): void
    {
        $actors = [
            'Officer Trainee' => $this->userWithRole('Officer Trainee', 'S', ['Student-OT']),
            'Employee-only' => $this->userWithRole('Employee', 'E', ['Employee']),
        ];

        foreach ($actors as $label => $actor) {
            foreach ([[], ['course_status' => 'archive']] as $tab) {
                $response = $this->asUser($actor, $tab);
                $response->assertForbidden();
                $response->assertDontSee('Fme Student One');
                $response->assertDontSee('Fme Student Two');
            }
        }
    }

    public function test_super_admin_and_permission_holder_get_rows_on_both_tabs(): void
    {
        if (! $this->admin) {
            $this->markTestSkipped('no Super Admin in this database');
        }

        $actors = [
            'Super Admin' => [$this->admin, ['Super Admin']],
            'Training-Induction' => $this->userWithRole('Training-Induction', 'E', ['Training-Induction']),
        ];

        foreach ($actors as $label => $actor) {
            $active = $this->asUser($actor, ['course_filter' => $this->c['B']]);
            $active->assertOk();
            $this->assertSame([$this->f['F2'] => [$this->d['D2']]], $this->cards($active), "$label, Active tab");

            $archive = $this->asUser($actor, ['course_status' => 'archive', 'course_filter' => $this->c['A']]);
            $archive->assertOk();
            $this->assertSame([
                $this->f['F1'] => [$this->d['D1'], $this->d['D3']],
                $this->f['F2'] => [$this->d['D1']],
                $this->f['F3'] => [$this->d['D1']],
            ], $this->cards($archive), "$label, Archived tab");
        }
    }

    public function test_admin_faculty_filter_matches_a_faculty_in_any_position(): void
    {
        foreach (['F2', 'F3'] as $key) {
            $response = $this->asAdmin(['course_status' => 'archive', 'faculty_filter' => $this->f[$key]]);
            $response->assertOk();

            $this->assertSame([$this->f[$key] => [$this->d['D1']]], $this->cards($response), "$key must find the duty it is second/third on");
            $row = $response->viewData('facultyData')[0]['courses'][0]['student_duties'][0];
            $this->assertSame(['Fme Alpha', 'Fme Bravo', 'Fme Charlie'], $row['faculty'], 'every associated faculty is listed');
        }
    }

    public function test_admin_course_only_lists_the_duty_under_each_of_its_faculty_and_counts_it_once(): void
    {
        $response = $this->asAdmin(['course_status' => 'archive', 'course_filter' => $this->c['A']]);
        $response->assertOk();

        $this->assertSame([
            $this->f['F1'] => [$this->d['D1'], $this->d['D3']],
            $this->f['F2'] => [$this->d['D1']],
            $this->f['F3'] => [$this->d['D1']],
        ], $this->cards($response));

        // Total Exceptions KPI: 2 distinct duties, not 4 card rows.
        $this->assertMatchesRegularExpression('/Total Exceptions<\/p>\s*<div class="ds-stat-value">2<\/div>/', $response->getContent());
        $response->assertSee('Fme Charlie');
    }

    public function test_admin_faculty_plus_course_combines_both_and_matches_legacy_rows(): void
    {
        // Active course B, legacy row with no CSV: still found through faculty_master_pk.
        $response = $this->asAdmin(['course_filter' => $this->c['B'], 'faculty_filter' => $this->f['F2']]);
        $this->assertSame([$this->f['F2'] => [$this->d['D2']]], $this->cards($response));

        // F1 has nothing on course B.
        $response = $this->asAdmin(['course_filter' => $this->c['B'], 'faculty_filter' => $this->f['F1']]);
        $this->assertSame([], $this->cards($response));
        $response->assertSee('No faculty data found matching the selected filters.');

        // Archived course A + F3 (third in the CSV).
        $response = $this->asAdmin(['course_status' => 'archive', 'course_filter' => $this->c['A'], 'faculty_filter' => $this->f['F3']]);
        $this->assertSame([$this->f['F3'] => [$this->d['D1']]], $this->cards($response));
    }

    public function test_admin_course_options_follow_the_tab_and_faculty_options_include_every_position(): void
    {
        $active = $this->asAdmin();
        $this->assertArrayHasKey($this->c['B'], $active->viewData('allCourses'));
        $this->assertArrayNotHasKey($this->c['A'], $active->viewData('allCourses'));
        // An archived course id on the Active tab returns nothing.
        $this->assertSame([], $this->cards($this->asAdmin(['course_filter' => $this->c['A']])));

        $archive = $this->asAdmin(['course_status' => 'archive']);
        $this->assertArrayHasKey($this->c['A'], $archive->viewData('allCourses'));
        $this->assertArrayNotHasKey($this->c['B'], $archive->viewData('allCourses'));

        foreach (['F1', 'F2', 'F3'] as $key) {
            $this->assertArrayHasKey($this->f[$key], $archive->viewData('allFaculties'), "$key is selectable");
        }
    }

    public function test_faculty_login_gets_course_filter_only_and_can_reach_archived_courses(): void
    {
        $active = $this->asFaculty();
        $active->assertOk();
        $this->assertTrue($active->viewData('isFacultyView'));
        $this->assertArrayNotHasKey($this->c['A'], $active->viewData('courseMaster'));
        $active->assertDontSee('name="faculty_filter"', false);
        $active->assertSee('name="course_filter"', false);

        $archive = $this->asFaculty(['course_status' => 'archive', 'course_filter' => $this->c['A']]);
        $archive->assertOk();
        $this->assertSame([$this->c['A'] => 'FME Test Archived Course'], $archive->viewData('courseMaster'));
        $archive->assertDontSee('name="faculty_filter"', false);

        // F3 is CC of A, so it gets the whole course: D1 (it is third in the
        // CSV) and D3, which is assigned to F1 alone.
        $this->assertSame([
            'Fme Student One' => [['Fme Alpha', 'Fme Bravo', 'Fme Charlie']],
            'Fme Student Two' => [['Fme Alpha']],
        ], $this->facultyRows($archive));
        $this->assertSame(2, $archive->viewData('totalExceptions'));
    }

    public function test_faculty_login_cannot_reach_a_course_it_neither_coordinates_nor_is_assigned_to(): void
    {
        foreach ([[], ['course_status' => 'archive']] as $tab) {
            $response = $this->asFaculty($tab + ['course_filter' => $this->c['B']]);
            $response->assertOk();
            $this->assertSame([], $response->viewData('studentData'));
            $this->assertArrayNotHasKey($this->c['B'], $response->viewData('courseMaster'));
        }
    }

    public function test_faculty_login_sees_its_own_duties_even_on_courses_it_does_not_coordinate(): void
    {
        // F2 coordinates nothing; it is second on D1 (course A) and the legacy
        // faculty_master_pk on D2 (course B).
        $employeePk = $this->employeePk + 2;
        DB::table('faculty_master')->where('pk', $this->f['F2'])->update(['employee_master_pk' => $employeePk]);
        $userPk = DB::table('user_credentials')->insertGetId(['user_name' => 'fme.test.'.$employeePk, 'user_id' => $employeePk]);
        $actor = [User::find($userPk), ['Internal Faculty']];

        $active = $this->asUser($actor);
        $active->assertOk();
        $this->assertSame([$this->c['B'] => 'FME Test Active Course'], $active->viewData('courseMaster'));
        $this->assertSame(['Fme Student Two' => [['Fme Bravo']]], $this->facultyRows($active));

        $archive = $this->asUser($actor, ['course_status' => 'archive']);
        $archive->assertOk();
        $this->assertSame([$this->c['A'] => 'FME Test Archived Course'], $archive->viewData('courseMaster'));
        $this->assertSame([
            'Fme Student One' => [['Fme Alpha', 'Fme Bravo', 'Fme Charlie']],
        ], $this->facultyRows($archive), 'D3 (F1 only) must not be listed');
    }

    public function test_cc_and_acc_see_every_duty_of_their_course_on_both_tabs(): void
    {
        foreach (['active', 'archive'] as $tab) {
            $ex = $this->exampleCourse($tab);

            // A second course the CC coordinates, with no escort duty (only an
            // MDO one): it is not offered in the Course filter.
            $idle = $this->newCourse("FME Example {$ex['label']} Idle Course", $tab);
            DB::table('course_coordinator_master')->insert([
                'courses_master_pk' => $idle, 'Coordinator_name' => $ex['faculty']['A']['pk'], 'created_date' => now(),
            ]);
            $this->newDuty($idle, $this->newStudent("Fme Ex {$ex['label']} Idle MDO"), [$ex['faculty']['A']['pk']], 1);

            foreach (['A' => 'CC', 'B' => 'ACC'] as $key => $role) {
                foreach ([[], ['course_filter' => $ex['course']]] as $filter) {
                    $response = $this->asUser($ex['faculty'][$key]['actor'], $this->tabQuery($tab) + $filter);
                    $response->assertOk();

                    // Duty 2 (C only) and Duty 3 (D + E) included, with the full Faculty column.
                    $this->assertSame([
                        $ex['duties'][1] => [[$ex['names']['A']]],
                        $ex['duties'][2] => [[$ex['names']['C']]],
                        $ex['duties'][3] => [[$ex['names']['D'], $ex['names']['E']]],
                    ], $this->facultyRows($response), "$role, $tab tab");
                    $this->assertSame(3, $response->viewData('totalExceptions'), "$role, $tab tab");
                    $this->assertSame([$ex['course'] => $ex['course_name']], $response->viewData('courseMaster'), "$role, $tab tab: Course filter");
                }

                // The other tab has nothing for them.
                $other = $this->asUser($ex['faculty'][$key]['actor'], $this->tabQuery($tab === 'archive' ? 'active' : 'archive'));
                $this->assertSame([], $other->viewData('studentData'), "$role, other tab than $tab");
                $this->assertSame([], $other->viewData('courseMaster'), "$role, other tab than $tab");
            }
        }
    }

    public function test_assigned_faculty_who_is_not_cc_or_acc_sees_only_own_duties_on_both_tabs(): void
    {
        foreach (['active', 'archive'] as $tab) {
            $ex = $this->exampleCourse($tab);

            $expected = [
                'C' => [$ex['duties'][2] => [[$ex['names']['C']]]],
                'D' => [$ex['duties'][3] => [[$ex['names']['D'], $ex['names']['E']]]],
                'E' => [$ex['duties'][3] => [[$ex['names']['D'], $ex['names']['E']]]],
            ];

            foreach ($expected as $key => $rows) {
                // Selecting the course in the filter does not widen the rows.
                foreach ([[], ['course_filter' => $ex['course']]] as $filter) {
                    $response = $this->asUser($ex['faculty'][$key]['actor'], $this->tabQuery($tab) + $filter);
                    $response->assertOk();
                    $this->assertSame($rows, $this->facultyRows($response), "Faculty $key, $tab tab");
                    $this->assertSame([$ex['course'] => $ex['course_name']], $response->viewData('courseMaster'), "Faculty $key, $tab tab: Course filter");
                    $response->assertDontSee($ex['duties'][1]);
                }
            }

            $other = $this->asUser($ex['faculty']['C']['actor'], $this->tabQuery($tab === 'archive' ? 'active' : 'archive'));
            $this->assertSame([], $other->viewData('studentData'), "Faculty C, other tab than $tab");
            $this->assertSame([], $other->viewData('courseMaster'), "Faculty C, other tab than $tab");
        }
    }

    public function test_faculty_with_no_cc_acc_role_and_no_duty_sees_nothing_on_both_tabs(): void
    {
        foreach (['active', 'archive'] as $tab) {
            $ex = $this->exampleCourse($tab);

            // An MDO (not escort) duty on the course does not count as an assignment.
            $this->newDuty($ex['course'], $this->newStudent("Fme Ex {$ex['label']} F MDO"), [$ex['faculty']['F']['pk']], 1);

            foreach (['active', 'archive'] as $seen) {
                foreach ([[], ['course_filter' => $ex['course']]] as $filter) {
                    $response = $this->asUser($ex['faculty']['F']['actor'], $this->tabQuery($seen) + $filter);
                    $response->assertOk();
                    $this->assertSame([], $response->viewData('studentData'), "Faculty F, course on $tab, viewing $seen");
                    $this->assertSame(0, $response->viewData('totalExceptions'));
                    $this->assertSame([], $response->viewData('courseMaster'), "Faculty F, course on $tab, viewing $seen");
                }
            }
        }
    }

    public function test_assigned_faculty_is_found_in_first_second_and_later_positions_and_on_legacy_rows(): void
    {
        foreach (['active', 'archive'] as $tab) {
            $label = $tab === 'archive' ? 'Archived' : 'Active';
            $course = $this->newCourse("FME Position {$label} Course", $tab);
            $p = [];
            foreach (['P1', 'P2', 'P3', 'P4', 'P5', 'P6'] as $key) {
                $p[$key] = $this->newFaculty("Fme Pos {$label} {$key}");
            }

            $this->newDuty($course, $this->newStudent("Fme Pos {$label} Csv"), [$p['P1']['pk'], $p['P2']['pk'], $p['P3']['pk'], $p['P4']['pk']]);
            // Legacy rows: faculty_master_pk only, faculty_master_pks NULL or ''.
            $this->newDuty($course, $this->newStudent("Fme Pos {$label} Legacy Null"), [$p['P5']['pk']], 2, null);
            $this->newDuty($course, $this->newStudent("Fme Pos {$label} Legacy Empty"), [$p['P6']['pk']], 2, '');

            $all = ["Fme Pos {$label} P1", "Fme Pos {$label} P2", "Fme Pos {$label} P3", "Fme Pos {$label} P4"];
            $expected = [
                'P1' => ["Fme Pos {$label} Csv" => [$all]],
                'P2' => ["Fme Pos {$label} Csv" => [$all]],
                'P3' => ["Fme Pos {$label} Csv" => [$all]],
                'P4' => ["Fme Pos {$label} Csv" => [$all]],
                'P5' => ["Fme Pos {$label} Legacy Null" => [["Fme Pos {$label} P5"]]],
                'P6' => ["Fme Pos {$label} Legacy Empty" => [["Fme Pos {$label} P6"]]],
            ];

            foreach ($expected as $key => $rows) {
                $response = $this->asUser($p[$key]['actor'], $this->tabQuery($tab));
                $response->assertOk();
                $this->assertSame($rows, $this->facultyRows($response), "$key, $tab tab");
                $this->assertSame([$course => "FME Position {$label} Course"], $response->viewData('courseMaster'), "$key, $tab tab: Course filter");
            }
        }
    }

    public function test_coordinator_rule_applies_only_to_the_courses_they_coordinate(): void
    {
        // F3 is CC of archived course A, not of active course B. On B it is on
        // D4 only; D2 (F2) and D5 (F1) stay hidden.
        $d4 = $this->newDuty($this->c['B'], $this->newStudent('Fme Student Four'), [$this->f['F1'], $this->f['F3']]);
        $this->newDuty($this->c['B'], $this->newStudent('Fme Student Five'), [$this->f['F1']]);

        $active = $this->asFaculty();
        $active->assertOk();
        $this->assertSame(['Fme Student Four' => [['Fme Alpha', 'Fme Charlie']]], $this->facultyRows($active));
        $this->assertSame([$this->c['B'] => 'FME Test Active Course'], $active->viewData('courseMaster'));

        $archive = $this->asFaculty(['course_status' => 'archive']);
        $this->assertSame([
            'Fme Student One' => [['Fme Alpha', 'Fme Bravo', 'Fme Charlie']],
            'Fme Student Two' => [['Fme Alpha']],
        ], $this->facultyRows($archive));
        $this->assertSame([$this->c['A'] => 'FME Test Archived Course'], $archive->viewData('courseMaster'));

        // The admin view still lists every duty on B, D4 under both its faculty.
        // (Drop the faculty session roles the requests above left behind.)
        $this->flushSession();
        $admin = $this->asAdmin(['course_filter' => $this->c['B']]);
        $admin->assertOk();
        $cards = $this->cards($admin);
        $this->assertContains($d4, $cards[$this->f['F1']]);
        $this->assertContains($d4, $cards[$this->f['F3']]);
        $this->assertSame([$this->d['D2']], $cards[$this->f['F2']]);
    }

    /** student name => [faculty names of each exception] from the faculty view. */
    private function facultyRows($response): array
    {
        $rows = [];
        foreach ($response->viewData('studentData') as $student) {
            foreach ($student['exemptions'] as $e) {
                $rows[$student['student_name']][] = $e['faculty'];
            }
        }
        ksort($rows);

        return $rows;
    }

    private function tabQuery(string $tab): array
    {
        return $tab === 'archive' ? ['course_status' => 'archive'] : [];
    }

    private function newCourse(string $name, string $tab): int
    {
        $today = now()->startOfDay();

        return DB::table('course_master')->insertGetId([
            'course_name' => $name, 'course_year' => (int) $today->format('Y'), 'active_inactive' => 1,
            'end_date' => $tab === 'archive' ? $today->copy()->subDays(30) : $today->copy()->addDays(30),
        ]);
    }

    /** A faculty_master row with an employee login that has the Internal Faculty session role. */
    private function newFaculty(string $name): array
    {
        $employeePk = $this->employeePk + 100 + $this->loginSeq++;
        $pk = DB::table('faculty_master')->insertGetId([
            'faculty_type' => '1', 'first_name' => $name, 'full_name' => $name,
            'country_master_pk' => 0, 'state_master_pk' => 0, 'state_district_mapping_pk' => 0, 'city_master_pk' => 0,
            'employee_master_pk' => $employeePk,
        ]);
        $userPk = DB::table('user_credentials')->insertGetId([
            'user_name' => 'fme.login.'.$employeePk, 'user_id' => $employeePk, 'user_category' => 'E',
        ]);

        return ['pk' => $pk, 'actor' => [User::find($userPk), ['Internal Faculty']]];
    }

    private function newStudent(string $name): int
    {
        $seq = $this->loginSeq++;

        return DB::table('student_master')->insertGetId([
            'service_master_pk' => 0, 'user_id' => 'fme-x'.$seq.'-'.$this->employeePk,
            'display_name' => $name, 'generated_OT_code' => 'FME-X-'.$seq, 'status' => 1,
        ]);
    }

    /**
     * One duty. faculty_master_pk is the first faculty, as the form stores it;
     * $csv 'list' (default) stores the full list, null or '' make a legacy row.
     */
    private function newDuty(int $course, int $student, array $facultyPks, int $type = 2, ?string $csv = 'list'): int
    {
        return DB::table('mdo_escot_duty_map')->insertGetId([
            'course_master_pk' => $course, 'selected_student_list' => $student, 'mdo_duty_type_master_pk' => $type,
            'faculty_master_pk' => $facultyPks[0], 'faculty_master_pks' => $csv === 'list' ? implode(',', $facultyPks) : $csv,
            'mdo_date' => now()->startOfDay()->subDays(40), 'Time_from' => '09:00', 'Time_to' => '10:00',
        ]);
    }

    /**
     * The worked example on one course in the given tab: CC = A, ACC = B;
     * Duty 1 = A, Duty 2 = C, Duty 3 = D + E; F has no role on it. One trainee
     * per duty, so the trainee's name identifies the duty in the faculty table.
     */
    private function exampleCourse(string $tab): array
    {
        $label = $tab === 'archive' ? 'Archived' : 'Active';
        $courseName = "FME Example {$label} Course";
        $course = $this->newCourse($courseName, $tab);

        $faculty = $names = [];
        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $key) {
            $names[$key] = "Fme Ex {$label} {$key}";
            $faculty[$key] = $this->newFaculty($names[$key]);
        }

        DB::table('course_coordinator_master')->insert([
            'courses_master_pk' => $course, 'Coordinator_name' => $faculty['A']['pk'],
            'Assistant_Coordinator_name' => $faculty['B']['pk'], 'created_date' => now(),
        ]);

        $duties = [];
        foreach ([1 => ['A'], 2 => ['C'], 3 => ['D', 'E']] as $n => $keys) {
            $duties[$n] = "Fme Ex {$label} Duty {$n}";
            $this->newDuty($course, $this->newStudent($duties[$n]), array_map(fn ($k) => $faculty[$k]['pk'], $keys));
        }

        return ['label' => $label, 'course' => $course, 'course_name' => $courseName, 'faculty' => $faculty, 'names' => $names, 'duties' => $duties];
    }

    public function test_faculty_role_login_gets_the_faculty_view_not_the_admin_view(): void
    {
        // Faculty accounts hold the Spatie role "Faculty", which also holds this
        // screen's menu permission; login copies Spatie role names into the
        // session. Such a login must get the faculty view, never the admin
        // view's every-faculty listing.
        if (! DB::table('roles')->where('name', 'Faculty')->exists()) {
            $this->markTestSkipped("no 'Faculty' role in this database");
        }

        foreach (['active', 'archive'] as $tab) {
            $ex = $this->exampleCourse($tab);

            $expected = [
                'A' => [
                    $ex['duties'][1] => [[$ex['names']['A']]],
                    $ex['duties'][2] => [[$ex['names']['C']]],
                    $ex['duties'][3] => [[$ex['names']['D'], $ex['names']['E']]],
                ],
                'C' => [$ex['duties'][2] => [[$ex['names']['C']]]],
                'F' => [],
            ];

            foreach ($expected as $key => $rows) {
                [$user] = $ex['faculty'][$key]['actor'];
                $user->assignRole('Faculty');

                $response = $this->asUser([$user->fresh(), ['Faculty', 'Employee']], $this->tabQuery($tab));
                $response->assertOk();
                $response->assertViewHas('isFacultyView', true);
                $response->assertViewMissing('facultyData');
                $response->assertDontSee('name="faculty_filter"', false);
                $this->assertSame($rows, $this->facultyRows($response), "Faculty-role $key, $tab tab");
            }
        }
    }

    public function test_faculty_category_login_resolves_its_faculty_by_pk_on_both_tabs(): void
    {
        // A faculty login (user_category F) stores faculty_master.pk in user_id;
        // it has no employee link. Same per-course rule as any faculty login.
        foreach (['active', 'archive'] as $tab) {
            $ex = $this->exampleCourse($tab);

            $expected = [
                'A' => [
                    $ex['duties'][1] => [[$ex['names']['A']]],
                    $ex['duties'][2] => [[$ex['names']['C']]],
                    $ex['duties'][3] => [[$ex['names']['D'], $ex['names']['E']]],
                ],
                'C' => [$ex['duties'][2] => [[$ex['names']['C']]]],
            ];

            foreach ($expected as $key => $rows) {
                $facultyPk = $ex['faculty'][$key]['pk'];
                $userPk = DB::table('user_credentials')->insertGetId([
                    'user_name' => 'fme.f.'.$facultyPk, 'user_id' => $facultyPk, 'user_category' => 'F',
                ]);

                $response = $this->asUser([User::find($userPk), ['Faculty']], $this->tabQuery($tab));
                $response->assertOk();
                $response->assertViewHas('isFacultyView', true);
                $this->assertSame($rows, $this->facultyRows($response), "F login $key, $tab tab");
                $this->assertSame([$ex['course'] => $ex['course_name']], $response->viewData('courseMaster'));
            }
        }
    }

    public function test_login_category_decides_which_link_is_read(): void
    {
        $ex = $this->exampleCourse('active');
        $c = $ex['faculty']['C']['pk'];
        $cEmployee = (int) DB::table('faculty_master')->where('pk', $c)->value('employee_master_pk');

        // An F login whose user_id is C's employee pk is not C: F reads faculty pk only.
        // A non-F login whose user_id is C's faculty pk is not C: it reads the employee link only.
        $cases = [
            'F login, employee pk' => ['F', $cEmployee],
            'E login, faculty pk' => ['E', $c],
        ];
        foreach ($cases as $label => [$category, $userId]) {
            $this->assertFalse(DB::table('faculty_master')->where($category === 'F' ? 'pk' : 'employee_master_pk', $userId)->exists(), "$label: fixture must not collide");

            $userPk = DB::table('user_credentials')->insertGetId([
                'user_name' => 'fme.cat.'.$category.'.'.$userId, 'user_id' => $userId, 'user_category' => $category,
            ]);
            $this->flushSession();
            $response = $this->from('/dashboard')->asUser([User::find($userPk), ['Faculty']]);

            $response->assertRedirect('/dashboard');
            $response->assertSessionHas('error', 'Faculty record not found.');
        }
    }

    public function test_array_filter_input_is_ignored_not_a_500(): void
    {
        $this->asAdmin(['faculty_filter' => ['x'], 'course_filter' => ['1'], 'course_status' => ['archive']])->assertOk();
        $this->asFaculty(['course_filter' => ['1']])->assertOk();
    }
}
