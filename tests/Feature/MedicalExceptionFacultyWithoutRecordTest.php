<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GET /medical-exception-faculty-view as a Faculty login with no faculty_master row.
 *
 * The faculty pk resolves to null, and where('Coordinator_name', null) compiles
 * to "Coordinator_name IS NULL": such a login saw the medical exemptions of any
 * course whose coordinator was never filled in. It must see nothing.
 */
class MedicalExceptionFacultyWithoutRecordTest extends TestCase
{
    private const URL = '/medical-exception-faculty-view';

    private bool $inTransaction = false;

    private int $obLevel = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->obLevel = ob_get_level();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('No database connection: ' . $e->getMessage());
        }

        DB::beginTransaction();
        $this->inTransaction = true;
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

    public function test_a_faculty_login_without_a_faculty_record_sees_no_rows(): void
    {
        $employeePk = DB::table('employee_master')->value('pk');
        if (! $employeePk) {
            $this->markTestSkipped('no employee_master row to attach the exemption to');
        }

        // A course with a medical exemption and a coordinator row whose
        // Coordinator_name was never filled in.
        $course = DB::table('course_master')->insertGetId([
            'course_name' => 'Fme Null Coordinator Course', 'course_year' => (int) now()->format('Y'),
            'active_inactive' => 1, 'end_date' => now()->addDays(30),
        ]);
        DB::table('course_coordinator_master')->insert([
            'courses_master_pk' => $course, 'Coordinator_name' => null, 'created_date' => now(),
        ]);
        $student = DB::table('student_master')->insertGetId([
            'service_master_pk' => 0, 'user_id' => 'fme-medical-null', 'display_name' => 'Fme Medical Student', 'status' => 1,
        ]);
        DB::table('student_medical_exemption')->insert([
            'course_master_pk' => $course, 'student_master_pk' => $student, 'employee_master_pk' => $employeePk,
            'exemption_category_master_pk' => 0, 'exemption_medical_speciality_pk' => 0,
            'from_date' => now()->toDateString(), 'to_date' => now()->addDay()->toDateString(), 'active_inactive' => 1,
        ]);

        // Faculty login whose user_id matches no faculty_master.employee_master_pk.
        $orphanUserId = (int) DB::table('faculty_master')->max('employee_master_pk') + 900003;
        $userPk = DB::table('user_credentials')->insertGetId(['user_name' => 'fme.medical.' . $orphanUserId, 'user_id' => $orphanUserId]);
        $this->assertFalse(DB::table('faculty_master')->where('employee_master_pk', $orphanUserId)->exists());

        $response = $this->actingAs(User::find($userPk))
            ->withSession(['user_roles' => ['Faculty']])
            ->get(self::URL)
            ->assertOk();

        $this->assertCount(0, $response->viewData('data'));
        $this->assertCount(0, $response->viewData('courses'));
        $response->assertDontSee('Fme Medical Student');
        $response->assertDontSee('Fme Null Coordinator Course');
    }
}
