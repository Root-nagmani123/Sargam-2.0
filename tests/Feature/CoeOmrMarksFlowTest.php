<?php

namespace Tests\Feature;

use App\Models\COE\EvaluationMark;
use App\Models\ExaminationDrive;
use App\Models\ExaminationDriveComponentMap;
use App\Models\User;
use App\Services\COE\OmrMarks;
use App\Services\COE\ResultMarksService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * COE → Evaluation Through = OMR: Result Entry pre-fills an OMR component's marks from
 * coe_exam_omr (exam_id = the component row id, matched on roll number, verified sheets
 * only, latest wins, negative → 0), a saved mark wins over the sheet, and saving the
 * component mapping keeps the row id so the sheets stay linked. Development database,
 * in a transaction.
 */
class CoeOmrMarksFlowTest extends TestCase
{
    use DatabaseTransactions;

    private ExaminationDrive $drive;

    private User $admin;

    private int $subject;

    /** @var array<string, int> component pks: OMR, FAC */
    private array $components = [];

    /** @var array<string, ExaminationDriveComponentMap> */
    private array $maps = [];

    /** @var list<array{pk:int, roll:string}> the course's OTs with roll numbers */
    private array $ots = [];

    protected function setUp(): void
    {
        parent::setUp();

        $allocation = DB::table('coe_roll_number_allocations as a')
            ->join('coe_roll_numbers as r', 'r.roll_number_allocation_pk', '=', 'a.pk')
            ->groupBy('a.pk', 'a.course_master_pk', 'a.batch')->havingRaw('count(*) >= 4')
            ->first(['a.pk', 'a.course_master_pk', 'a.batch']);
        $admin = User::role('Super Admin')->first();
        $subject = DB::table('subject_master')->orderBy('pk')->value('pk');
        $service = DB::table('service_master')->value('pk');
        $term = DB::table('term_master')->value('pk');
        $examType = DB::table('examination_type_master')->value('pk');

        if (! OmrMarks::enabled() || ! $allocation || ! $admin || ! $subject || ! $service || ! $term || ! $examType) {
            $this->markTestSkipped('Needs coe_exam_omr, a roll allocation with 4+ roll numbers, a Super Admin, a subject, a service, a term and an exam type.');
        }

        $this->admin = $admin;
        $this->subject = (int) $subject;

        // Masters the component save checks: passing marks for the service, max marks for the subject.
        DB::table('service_passing_marks_master')->updateOrInsert(['service_master_pk' => $service], ['passing_marks' => 40, 'active_inactive' => 1]);
        DB::table('subject_max_marks_master')->where('subject_master_pk', $this->subject)->update(['active_inactive' => 2]);
        DB::table('subject_max_marks_master')->insert(['subject_master_pk' => $this->subject, 'max_marks' => 200, 'active_inactive' => 1]);

        foreach (['OMR', 'FAC', 'NEW'] as $c) {
            $this->components[$c] = DB::table('component_master')->insertGetId(['component_name' => 'ZZ OMR Test '.$c.' '.uniqid(), 'active_inactive' => 1], 'pk');
        }

        $this->drive = ExaminationDrive::create([
            'drive_name' => 'ZZ OMR Test',
            'examination_type_master_pk' => $examType,
            'term_master_pk' => $term,
            'course_master_pk' => $allocation->course_master_pk,
            'service_master_pk' => $service,
            'phase' => (string) $allocation->batch,
            'academic_session' => $allocation->batch,
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'status' => ExaminationDrive::STATUS_ACTIVE,
            'cc_status' => ExaminationDrive::CC_APPROVED,
        ]);
        DB::table('examination_drive_faculty_maps')->insert([
            'examination_drive_id' => $this->drive->id, 'subject_master_pk' => $this->subject,
            'faculty_master_pk' => DB::table('faculty_master')->value('pk'),
        ]);

        foreach (['OMR' => OmrMarks::SOURCE, 'FAC' => 'Faculty'] as $c => $source) {
            $this->maps[$c] = ExaminationDriveComponentMap::create([
                'examination_drive_id' => $this->drive->id, 'subject_master_pk' => $this->subject,
                'component_master_pk' => $this->components[$c], 'max_marks' => 50, 'passing_marks' => 40,
                'source' => $source, 'weightage' => 50,
                'exam_date' => now()->subDays(3)->toDateString(), 'exam_end_date' => now()->subDays(2)->toDateString(),
                'exam_mode' => 'Offline', 'exam_type' => 'Offline Objective', 'created_date' => now(),
            ]);
        }

        $this->ots = DB::table('coe_roll_numbers')->where('roll_number_allocation_pk', $allocation->pk)
            ->orderBy('serial_number')->limit(4)->get(['student_master_pk', 'roll_number'])
            ->map(fn ($r) => ['pk' => (int) $r->student_master_pk, 'roll' => trim($r->roll_number)])->all();
    }

    private function sheet(string $component, string $roll, float $score, string $status = 'verified'): void
    {
        DB::table(OmrMarks::TABLE)->insert([
            'exam_id' => $this->maps[$component]->id, 'upload_name' => 'zz.jpg', 'path' => 'zz/zz.jpg',
            'roll_number' => $roll, 'status' => $status, 'score' => $score, 'max_questions' => 50,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @return array<int, ?float> student pk => pre-filled OMR score of the component */
    private function omrOf(string $component): array
    {
        $key = $this->drive->id.':'.$this->subject.':'.$this->components[$component];
        $sheet = app(ResultMarksService::class)->entrySheet($this->admin, (int) $this->drive->course_master_pk, $this->subject, $key);

        return collect($sheet['entries'])->mapWithKeys(fn ($e) => [$e['student'] => $e['omr']])->all();
    }

    public function test_omr_is_an_evaluation_through_option(): void
    {
        $this->assertContains('OMR', \App\Http\Controllers\Admin\COE\ExaminationDriveController::SOURCES);
    }

    public function test_setup_page_shows_the_omr_exam_id_of_an_omr_row_only(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.coe.faculty-examination-drive.setup', ['id' => $this->drive->id, 'subject' => $this->subject]))
            ->assertOk()
            ->getContent();

        // assertTrue, not assertSee: a failure must not print the whole page.
        $this->assertTrue((bool) preg_match('/<option value="OMR"\s+selected\s*>OMR<\/option>/', $html), 'OMR is not picked on the OMR row.');
        // Shown on the OMR row; the Faculty row has it too, hidden until OMR is picked.
        $this->assertTrue((bool) preg_match('/fedc-omr-id"\s*>OMR Exam ID: '.$this->maps['OMR']->id.'</', $html), 'OMR Exam ID not shown on the OMR row.');
        $this->assertTrue((bool) preg_match('/fedc-omr-id"\s+hidden\s*>OMR Exam ID: '.$this->maps['FAC']->id.'</', $html), 'OMR Exam ID not hidden on the Faculty row.');
    }

    public function test_an_omr_component_is_prefilled_with_each_ots_verified_score(): void
    {
        [$a, $b, $c, $d] = $this->ots;
        $this->sheet('OMR', $a['roll'], 30);          // older sheet of A
        $this->sheet('OMR', $a['roll'], 42);          // latest sheet of A wins
        $this->sheet('OMR', $b['roll'], 40, 'review'); // not checked yet: not used
        $this->sheet('OMR', $c['roll'], -5);          // negative marking below zero → 0
        $this->sheet('OMR', $d['roll'], 60);          // above the max marks (50): not used

        $omr = $this->omrOf('OMR');

        $this->assertSame(42.0, $omr[$a['pk']]);
        $this->assertNull($omr[$b['pk']]);
        $this->assertSame(0.0, $omr[$c['pk']]);
        $this->assertNull($omr[$d['pk']]);
    }

    public function test_sheets_of_another_paper_or_a_faculty_component_are_not_used(): void
    {
        [$a] = $this->ots;
        // The Faculty component's id as exam_id: it is not OMR, so nothing is pre-filled.
        $this->sheet('FAC', $a['roll'], 25);

        $this->assertNull($this->omrOf('FAC')[$a['pk']]);
        $this->assertNull($this->omrOf('OMR')[$a['pk']]);
    }

    public function test_a_saved_mark_wins_over_the_omr_sheet(): void
    {
        [$a, $b] = $this->ots;
        $this->sheet('OMR', $a['roll'], 42);
        $this->sheet('OMR', $b['roll'], 38);
        EvaluationMark::create([
            'examination_drive_id' => $this->drive->id, 'subject_master_pk' => $this->subject,
            'component_master_pk' => $this->components['OMR'], 'student_master_pk' => $a['pk'],
            'marks' => 20, 'is_absent' => false, 'status' => EvaluationMark::STATUS_DRAFT,
            'created_date' => now(),
        ]);

        $omr = $this->omrOf('OMR');

        $this->assertNull($omr[$a['pk']]);
        $this->assertSame(38.0, $omr[$b['pk']]);
    }

    public function test_result_entry_shows_the_omr_score_in_the_marks_box(): void
    {
        [$a] = $this->ots;
        $this->sheet('OMR', $a['roll'], 42);

        $this->actingAs($this->admin)
            ->get(route('admin.coe.faculty-result-entry.index', [
                'course' => $this->drive->course_master_pk, 'subject' => $this->subject,
                'component' => $this->drive->id.':'.$this->subject.':'.$this->components['OMR'],
            ]))
            ->assertOk()
            ->assertSee('value="42.00"', false)
            ->assertSee('data-omr="42.00"', false);
    }

    public function test_saving_the_components_keeps_each_rows_id(): void
    {
        $name = fn (string $c) => DB::table('component_master')->where('pk', $this->components[$c])->value('component_name');
        $row = fn (string $c, string $source) => [
            'component' => $name($c), 'max_marks' => 50, 'source' => $source, 'weightage' => 30,
            'exam_date' => now()->subDays(3)->toDateString(), 'exam_end_date' => now()->subDays(2)->toDateString(),
            'exam_mode' => 'Offline', 'exam_type' => 'Offline Objective',
        ];
        $save = fn (array $rows) => $this->actingAs($this->admin)
            ->putJson(route('admin.coe.examination-drive.components.save', $this->drive->id), [
                'subject_master_pk' => $this->subject, 'rows' => $rows,
            ]);
        $ids = fn () => ExaminationDriveComponentMap::where('examination_drive_id', $this->drive->id)
            ->pluck('id', 'component_master_pk')->all();

        $omrId = $this->maps['OMR']->id;

        // FAC dropped, NEW added, OMR saved again with new max marks.
        $save([['max_marks' => 40] + $row('OMR', 'OMR'), $row('NEW', 'Sargam')])->assertOk();

        $after = $ids();
        $this->assertSame($omrId, $after[$this->components['OMR']]);
        $this->assertArrayNotHasKey($this->components['FAC'], $after);
        $this->assertArrayHasKey($this->components['NEW'], $after);
        $this->assertEquals(40, ExaminationDriveComponentMap::find($omrId)->max_marks);
        $this->assertSame('OMR', ExaminationDriveComponentMap::find($omrId)->source);

        // The same component twice is refused, nothing changes.
        $save([$row('OMR', 'OMR'), $row('OMR', 'OMR')])->assertStatus(422)->assertJsonFragment(['message' => 'Each component can be added only once: '.$name('OMR').'.']);
        $this->assertSame($after, $ids());
    }
}
