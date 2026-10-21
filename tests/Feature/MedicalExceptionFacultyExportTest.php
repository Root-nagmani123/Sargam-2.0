<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GET /medical-exception-faculty-view/export/{format} (PR #341).
 *
 * The export reuses the screen's viewData(), so it must keep the screen's access
 * rule (F-002), its faculty scoping, and the admin scope across every faculty
 * (F-008); it must not 500 on a malformed date (F-003); and its rows must follow
 * the grid's search and ordering (F-005).
 */
class MedicalExceptionFacultyExportTest extends TestCase
{
    private const SCREEN = '/medical-exception-faculty-view';

    private const EXPORT = '/medical-exception-faculty-view/export';

    private bool $inTransaction = false;

    private int $obLevel = 0;

    private int $seq = 0;

    private int $idBase = 0;

    private int $employeePk = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->obLevel = ob_get_level();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('No database connection: '.$e->getMessage());
        }

        DB::beginTransaction();
        $this->inTransaction = true;

        $this->idBase = (int) DB::table('faculty_master')->max('employee_master_pk') + 900400;
        $this->employeePk = (int) DB::table('employee_master')->value('pk');
        if (! $this->employeePk) {
            $this->markTestSkipped('no employee_master row to attach exemptions to');
        }
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > $this->obLevel) {
            ob_end_clean();
        }

        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }

        parent::tearDown();
    }

    /** A login with the given Spatie role (or none) and the session roles login would set. */
    private function account(?string $role, array $sessionRoles, ?int $userId = null): array
    {
        if ($role !== null && ! DB::table('roles')->where('name', $role)->exists()) {
            $this->markTestSkipped("no '$role' role in this database");
        }

        $userId ??= $this->idBase + 50 + $this->seq++;
        $user = User::find(DB::table('user_credentials')->insertGetId([
            'user_name' => 'mefx.'.$userId, 'user_id' => $userId, 'user_category' => 'E',
        ]));
        if ($role !== null) {
            $user->assignRole($role);
        }

        return [$user->fresh(), $sessionRoles];
    }

    /** A faculty who coordinates one running course (and teaches it, per the timetable). Returns [login, course pk]. */
    private function facultyWithCourse(string $courseName): array
    {
        $employeeKey = $this->idBase + $this->seq++;
        $facultyPk = DB::table('faculty_master')->insertGetId([
            'faculty_type' => '1', 'first_name' => 'Mefx', 'full_name' => 'Mefx '.$courseName, 'employee_master_pk' => $employeeKey,
            'country_master_pk' => 0, 'state_master_pk' => 0, 'state_district_mapping_pk' => 0, 'city_master_pk' => 0,
        ]);
        $course = DB::table('course_master')->insertGetId([
            'course_name' => $courseName, 'course_year' => (int) now()->format('Y'),
            'active_inactive' => 1, 'end_date' => now()->addDays(30),
        ]);
        DB::table('course_coordinator_master')->insert([
            'courses_master_pk' => $course, 'Coordinator_name' => (string) $facultyPk, 'created_date' => now(),
        ]);
        DB::table('timetable')->insert([
            'course_master_pk' => $course, 'faculty_master' => $facultyPk, 'active_inactive' => 1,
            'subject_master_pk' => 0, 'subject_module_master_pk' => 0, 'course_group_type_master' => 0,
            'group_name' => '[]', 'venue_id' => 0,
        ]);

        return [$this->account(null, ['Faculty'], $employeeKey), $course];
    }

    private function exemption(int $course, string $student, string $topic = 'Fever', ?string $fromDate = null): void
    {
        $studentPk = DB::table('student_master')->insertGetId([
            'service_master_pk' => 0, 'user_id' => 'mefx-'.$this->seq++, 'display_name' => $student, 'status' => 1,
        ]);
        DB::table('student_medical_exemption')->insert([
            'course_master_pk' => $course, 'student_master_pk' => $studentPk, 'employee_master_pk' => $this->employeePk,
            'exemption_category_master_pk' => 0, 'exemption_medical_speciality_pk' => 0, 'description' => $topic,
            'from_date' => $fromDate ?? now()->toDateString(), 'to_date' => now()->addDay()->toDateString(), 'active_inactive' => 1,
        ]);
    }

    private function export(array $actor, string $query = '', string $format = 'csv')
    {
        [$user, $sessionRoles] = $actor;

        return $this->actingAs($user)->withSession(['user_roles' => $sessionRoles])
            ->get(self::EXPORT.'/'.$format.($query !== '' ? '?'.$query : ''));
    }

    /** [column header, data rows] of a CSV export; the header band above them is skipped. */
    private function csvRows($response, string $firstHeading = 'S.No.'): array
    {
        $this->assertSame(200, $response->getStatusCode());
        $lines = array_map('str_getcsv', preg_split('/\r?\n/', trim(ltrim($response->streamedContent(), "\xEF\xBB\xBF"))));
        foreach ($lines as $i => $line) {
            if (($line[0] ?? null) === $firstHeading) {
                return [$line, array_slice($lines, $i + 1)];
            }
        }
        $this->fail('no column header row in the CSV');
    }

    private function column(array $rows, array $header, string $heading): array
    {
        $at = array_search($heading, $header, true);

        return array_map(fn ($row) => $row[$at], $rows);
    }

    private function students(array $actor, string $query = ''): array
    {
        [$header, $rows] = $this->csvRows($this->export($actor, $query));

        return $this->column($rows, $header, 'Student Name');
    }

    // ---------- F-002: who may export ----------

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get(self::EXPORT.'/csv')->assertRedirect();
        $this->assertGuest();
    }

    public function test_accounts_without_the_screen_permission_get_403_on_every_format(): void
    {
        [, $course] = $this->facultyWithCourse('Mefx Hidden Course');
        $this->exemption($course, 'Mefx Hidden Student');

        $actors = [
            'Employee-only' => $this->account('Employee', ['Employee']),
            'no role' => $this->account(null, []),
        ];

        foreach ($actors as $label => $actor) {
            foreach (['csv', 'excel', 'pdf', 'print'] as $format) {
                $this->assertSame(403, $this->export($actor, '', $format)->getStatusCode(), "$label / $format");
            }
        }
    }

    public function test_a_faculty_exports_only_the_courses_they_coordinate(): void
    {
        [$facultyA, $courseA] = $this->facultyWithCourse('Mefx Course A');
        [, $courseB] = $this->facultyWithCourse('Mefx Course B');
        $this->exemption($courseA, 'Mefx Student A');
        $this->exemption($courseB, 'Mefx Student B');

        $this->assertSame(['Mefx Student A'], $this->students($facultyA));
    }

    public function test_cols_limits_the_exported_columns(): void
    {
        [$faculty, $course] = $this->facultyWithCourse('Mefx Cols Course');
        $this->exemption($course, 'Mefx Cols Student');

        [$header, $rows] = $this->csvRows($this->export($faculty, 'cols=student,course'), 'Course Name');

        $this->assertSame(['Course Name', 'Student Name'], $header);
        $this->assertSame([['Mefx Cols Course', 'Mefx Cols Student']], $rows);
    }

    // ---------- F-008: the admin scope covers every faculty's courses ----------

    public function test_super_admin_sees_every_facultys_courses_on_screen_and_in_the_export(): void
    {
        [, $courseA] = $this->facultyWithCourse('Mefx Admin Course A');
        [, $courseB] = $this->facultyWithCourse('Mefx Admin Course B');
        $this->exemption($courseA, 'Mefx Admin Student A');
        $this->exemption($courseB, 'Mefx Admin Student B');
        $admin = $this->account('Super Admin', ['Super Admin']);

        $screen = $this->actingAs($admin[0])->withSession(['user_roles' => $admin[1]])->get(self::SCREEN)->assertOk();
        $onScreen = collect($screen->viewData('data'))->pluck('student_name');
        $this->assertContains('Mefx Admin Student A', $onScreen);
        $this->assertContains('Mefx Admin Student B', $onScreen);

        $exported = $this->students($admin);
        $this->assertContains('Mefx Admin Student A', $exported);
        $this->assertContains('Mefx Admin Student B', $exported);
    }

    // ---------- F-009: the header names only the faculty's own course ----------

    public function test_the_header_never_names_another_facultys_course(): void
    {
        [$facultyA, $courseA] = $this->facultyWithCourse('Mefx Header Course A');
        [, $courseB] = $this->facultyWithCourse('Mefx Header Course B');
        $this->exemption($courseA, 'Mefx Header Student A');

        $foreign = $this->export($facultyA, 'course='.$courseB)->streamedContent();
        $this->assertStringNotContainsString('Mefx Header Course B', $foreign);
        $this->assertStringNotContainsString('Course: ', $foreign);

        $own = $this->export($facultyA, 'course='.$courseA)->streamedContent();
        $this->assertStringContainsString('Course: Mefx Header Course A', $own);
    }

    // ---------- F-003: a malformed date never 500s ----------

    public function test_a_malformed_date_is_ignored_and_a_valid_one_still_filters(): void
    {
        [$faculty, $course] = $this->facultyWithCourse('Mefx Date Course');
        $this->exemption($course, 'Mefx Old Student', 'Fever', now()->subDays(10)->toDateString());
        $this->exemption($course, 'Mefx New Student', 'Fever', now()->toDateString());

        foreach (['date_from=garbage', 'date_from=2026-02-30', 'date_from[]=x', 'course[]=999999'] as $query) {
            foreach (['csv', 'pdf'] as $format) {
                $this->assertSame(200, $this->export($faculty, $query, $format)->getStatusCode(), "$query / $format");
            }
            $this->assertCount(2, $this->students($faculty, $query), "$query is ignored, not applied");
        }

        $this->assertSame(['Mefx New Student'], $this->students($faculty, 'date_from='.now()->subDays(2)->toDateString()));
    }

    // ---------- F-005: search and ordering follow the grid ----------

    public function test_search_treats_a_quoted_phrase_as_one_word_like_the_grid(): void
    {
        [$faculty, $course] = $this->facultyWithCourse('Mefx Search Course');
        $this->exemption($course, 'Mefx Phrase Student', 'Severe ear ache');
        $this->exemption($course, 'Mefx Words Student', 'ear infection and head ache');
        $this->exemption($course, 'Mefx Other Student', 'Fever');

        $search = fn (string $q) => $this->students($faculty, 'q='.rawurlencode($q));

        $this->assertSame(['Mefx Phrase Student'], $search('"ear ache"'));
        $this->assertEqualsCanonicalizing(['Mefx Phrase Student', 'Mefx Words Student'], $search('ear ache'));
        $this->assertSame(['Mefx Phrase Student'], $search('"ear ache" mefx'));
        $this->assertSame(['Mefx Other Student'], $search('FEVER'));
    }

    public function test_the_export_follows_the_grids_ordering(): void
    {
        [$faculty, $course] = $this->facultyWithCourse('Mefx Order Course');
        $this->exemption($course, 'Mefx Bravo', 'Fever', now()->subDays(1)->toDateString());
        $this->exemption($course, 'mefx alpha', 'Fever', now()->subDays(3)->toDateString());
        $this->exemption($course, 'Mefx Charlie', 'Fever', now()->subDays(2)->toDateString());

        // No ?order= = the server order the grid starts with (from_date desc within the course).
        $this->assertSame(['Mefx Bravo', 'Mefx Charlie', 'mefx alpha'], $this->students($faculty));

        // Case-insensitive string sort, and S.No. renumbered from 1 as the grid does.
        [$header, $rows] = $this->csvRows($this->export($faculty, 'order=student:asc'));
        $this->assertSame(['mefx alpha', 'Mefx Bravo', 'Mefx Charlie'], $this->column($rows, $header, 'Student Name'));
        $this->assertSame(['1', '2', '3'], $this->column($rows, $header, 'S.No.'));

        $this->assertSame(['Mefx Charlie', 'Mefx Bravo', 'mefx alpha'], $this->students($faculty, 'order=student:desc'));
        // The date column sorts on its timestamp (data-order), not the d-m-Y text.
        $this->assertSame(['mefx alpha', 'Mefx Charlie', 'Mefx Bravo'], $this->students($faculty, 'order=date:asc'));
        // Multi-column; unknown keys are ignored.
        $this->assertSame(['Mefx Bravo', 'Mefx Charlie', 'mefx alpha'], $this->students($faculty, 'order=course:asc,bogus:asc,date:desc'));
        $this->assertSame(200, $this->export($faculty, 'order[]=x')->getStatusCode());
    }
}
