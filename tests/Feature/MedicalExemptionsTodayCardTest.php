<?php

namespace Tests\Feature;

use App\Exports\StudentMedicalExemptionExport;
use App\Models\StudentMedicalExemption;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Doctor's "Medical Exemptions Today" card: its count, the exemption listing it opens
 * (?from_date=today&to_date=today) and that listing's export must hold the same rows.
 * Rows are written inside a transaction that is rolled back.
 */
class MedicalExemptionsTodayCardTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const TAG = 'today-card probe';

    public function test_card_listing_and_export_agree_on_exemptions_covering_the_day(): void
    {
        config(['cache.default' => 'array']);

        $course = DB::table('course_master')->whereNotNull('end_date')->orderByDesc('end_date')->first(['pk', 'end_date']);
        $doctor = User::find(DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')->where('r.name', 'Doctor')->value('m.model_id'));
        if (! $course || ! $doctor) {
            $this->markTestSkipped('needs a course and a Doctor login');
        }

        // A day the course is still running on.
        $day = Carbon::parse($course->end_date)->subDays(2)->startOfDay();
        Carbon::setTestNow($day->copy()->setTime(12, 0));
        $d = $day->toDateString();
        $prev = $day->copy()->subDay()->toDateString();

        $row = [
            'course_master_pk' => $course->pk, 'student_master_pk' => 1, 'employee_master_pk' => 1,
            'exemption_category_master_pk' => 1, 'exemption_medical_speciality_pk' => 1, 'opd_category' => 'OPD',
            'days' => 1, 'Description' => self::TAG, 'pt_outdoor_advise' => '', 'created_by' => 1,
            'active_inactive' => 1, 'created_date' => now(), 'modified_date' => now(),
        ];
        StudentMedicalExemption::insert([
            $row + ['from_date' => "$d 10:00:00", 'to_date' => "$d 18:00:00"],   // later the same day: counts
            $row + ['from_date' => "$prev 09:00:00", 'to_date' => null],         // open-ended: counts
            $row + ['from_date' => "$prev 09:00:00", 'to_date' => "$prev 17:00:00"], // ended yesterday: not
        ]);

        $card = StudentMedicalExemption::onRunningCourse()->overlapping($d, $d);
        $this->assertSame(2, (clone $card)->where('Description', self::TAG)->count());

        $grid = $this->actingAs($doctor)->getJson(
            route('student.medical.exemption.index', ['from_date' => $d, 'to_date' => $d, 'status' => 'active', 'draw' => 1, 'start' => 0, 'length' => 10]),
            ['X-Requested-With' => 'XMLHttpRequest']
        )->assertOk()->json('recordsTotal');

        $export = (new StudentMedicalExemptionExport('active', null, null, $d, $d))->pdfRows()->count();

        $this->assertSame($card->count(), $grid);
        $this->assertSame($card->count(), $export);

        Carbon::setTestNow();
    }
}
