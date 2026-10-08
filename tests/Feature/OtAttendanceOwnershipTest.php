<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * /getstudentmarks and the OT attendance page read student_pk from the request
 * and never compared it with the caller, so any signed-in account could read any
 * trainee's per-session attendance, medical-exemption document and description
 * included (PR #334 F-044). The Excel export of the same data already refused
 * another trainee's pk; the page and the data endpoint now do too, and a staff
 * login needs a Training Section role.
 */
class OtAttendanceOwnershipTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const TRAINING_ROLES = [
        'Super Admin', 'Training Induction Admin', 'Training-Induction', 'Training MCTP Admin',
        'Training-MCTP', 'Training IST', 'IST', 'Training', 'Officer Trainee', 'Student-OT',
    ];

    private function anotherStudent(User $ot): int
    {
        $pk = DB::table('student_master')->where('pk', '!=', (int) $ot->user_id)->orderBy('pk')->value('pk');
        if (! $pk) {
            $this->markTestSkipped('needs a second student');
        }

        return (int) $pk;
    }

    private function dataUrl(int $studentPk): string
    {
        return route('ot.student.attendance.data', ['group_pk' => 1, 'course_pk' => 1, 'student_pk' => $studentPk]);
    }

    /** A staff login that holds roles, none of them Training Section or OT. */
    private function staffWithoutTrainingRole(): User
    {
        $excluded = DB::table('roles')->whereIn('name', self::TRAINING_ROLES)->pluck('id')->all();
        $pk = DB::table('user_credentials as u')
            ->where('u.user_category', '!=', 'S')
            ->whereExists(fn ($q) => $q->from('model_has_roles as m')->whereColumn('m.model_id', 'u.pk')
                ->where('m.model_type', User::class))
            ->whereNotExists(fn ($q) => $q->from('model_has_roles as m')->whereColumn('m.model_id', 'u.pk')
                ->where('m.model_type', User::class)->whereIn('m.role_id', $excluded ?: [-1]))
            ->orderBy('u.pk')
            ->value('u.pk');

        if (! $pk) {
            $this->markTestSkipped('no staff login without a Training Section role');
        }

        return User::findOrFail($pk);
    }

    public function test_an_ot_cannot_read_another_trainees_attendance_data(): void
    {
        $ot = $this->officerTrainee();

        $this->as($ot, ['Student-OT'])
            ->getJson($this->dataUrl($this->anotherStudent($ot)))
            ->assertForbidden();
    }

    public function test_an_ot_cannot_open_another_trainees_attendance_page(): void
    {
        $ot = $this->officerTrainee();

        $this->as($ot, ['Student-OT'])
            ->get(route('attendance.OT.student_mark.student', [
                'group_pk' => 1, 'course_pk' => 1, 'timetable_pk' => 0,
                'student_pk' => $this->anotherStudent($ot),
            ]))
            ->assertForbidden();
    }

    public function test_a_staff_login_without_a_training_role_cannot_read_a_trainees_attendance_data(): void
    {
        $student = (int) DB::table('student_master')->orderBy('pk')->value('pk');
        if (! $student) {
            $this->markTestSkipped('no student');
        }

        $this->as($this->staffWithoutTrainingRole(), [])
            ->getJson($this->dataUrl($student))
            ->assertForbidden();
    }

    public function test_an_ot_still_reads_their_own_attendance_data(): void
    {
        $ot = $this->officerTrainee();

        $status = $this->as($ot, ['Student-OT'])->getJson($this->dataUrl((int) $ot->user_id))->getStatusCode();

        $this->assertNotSame(403, $status, 'the owner must not be refused');
    }

    public function test_training_section_staff_still_read_a_trainees_attendance_data(): void
    {
        $student = (int) DB::table('student_master')->orderBy('pk')->value('pk');
        // A staff Super Admin: a user_category 'S' login is a trainee whatever
        // roles it holds, and is limited to its own record.
        $pk = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
            ->join('user_credentials as u', 'u.pk', '=', 'm.model_id')
            ->where('r.name', 'Super Admin')->where('m.model_type', User::class)
            ->where('u.user_category', '!=', 'S')
            ->orderBy('m.model_id')->value('m.model_id');
        if (! $pk) {
            $this->markTestSkipped('no non-trainee Super Admin');
        }
        $admin = User::findOrFail($pk);

        $status = $this->as($admin, ['Super Admin'])->getJson($this->dataUrl($student))->getStatusCode();

        $this->assertNotSame(403, $status, 'a Training Section role must not be refused');
    }
}
