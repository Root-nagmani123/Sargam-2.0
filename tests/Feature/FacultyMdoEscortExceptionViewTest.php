<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
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
 */
class FacultyMdoEscortExceptionViewTest extends TestCase
{
    use DatabaseTransactions;

    private const URL = '/faculty-mdo-escort-exception-view';

    private array $f = [];

    private array $c = [];

    private array $d = [];

    private ?User $admin = null;

    private ?User $facultyUser = null;

    protected function setUp(): void
    {
        parent::setUp();

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

        $rows = [];
        foreach ($archive->viewData('studentData') as $student) {
            foreach ($student['exemptions'] as $e) {
                $rows[$student['student_name']][] = $e['faculty'];
            }
        }
        $this->assertSame([
            'Fme Student One' => [['Fme Alpha', 'Fme Bravo', 'Fme Charlie']],
            'Fme Student Two' => [['Fme Alpha']],
        ], $rows);
        $this->assertSame(2, $archive->viewData('totalExceptions'));
    }

    public function test_faculty_login_cannot_reach_a_course_it_does_not_coordinate(): void
    {
        foreach ([[], ['course_status' => 'archive']] as $tab) {
            $response = $this->asFaculty($tab + ['course_filter' => $this->c['B']]);
            $response->assertOk();
            $this->assertSame([], $response->viewData('studentData'));
            $this->assertArrayNotHasKey($this->c['B'], $response->viewData('courseMaster'));
        }
    }

    public function test_array_filter_input_is_ignored_not_a_500(): void
    {
        $this->asAdmin(['faculty_filter' => ['x'], 'course_filter' => ['1'], 'course_status' => ['archive']])->assertOk();
        $this->asFaculty(['course_filter' => ['1']])->assertOk();
    }
}
