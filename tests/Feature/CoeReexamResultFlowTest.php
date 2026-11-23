<?php

namespace Tests\Feature;

use App\Models\COE\Reexam;
use App\Models\COE\ReexamMarksheet;
use App\Models\ExaminationDrive;
use App\Models\User;
use App\Services\COE\MarksheetService;
use App\Services\COE\ReexamMarksheetService;
use App\Services\COE\ReexamService;
use App\Services\COE\ResultMarksService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * COE Re-exam, steps 5–7: Result Entry on a re-exam drive lists only its re-exam OTs
 * (main drives unchanged), the re-exam result (Pass / Fail on the re-exam's own approved
 * marks, no moderation; rank unchanged) and the re-exam marksheet (Pass / Fail only,
 * signed UDC → AAO → COE). Development database, in a transaction.
 *
 * Fixture (as CoeReexamFlowTest): a main drive, subject A (component X, max 50) and
 * subject B (component Y, max 20), every mark approved and moderated: OT 1 fails A
 * (10/50), OT 2 is AB in B. Re-exam 1 for both, CC-approved, its exam over.
 */
class CoeReexamResultFlowTest extends TestCase
{
    use DatabaseTransactions;

    private ExaminationDrive $drive;

    private ExaminationDrive $reexam;

    private User $admin;

    private int $subjectA;

    private int $subjectB;

    private int $compX;

    private int $compY;

    /** @var list<int> */
    private array $ots = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('coe_reexams')) {
            $this->markTestSkipped('Run the migrations first (coe_reexams).');
        }
        $cohort = DB::table('coe_participant_codes')
            ->groupBy('course_master_pk', 'batch')->havingRaw('count(*) >= 3')
            ->first(['course_master_pk', 'batch']);
        $admin = User::role('Super Admin')->first();
        $subjects = DB::table('subject_master')->orderBy('pk')->limit(2)->pluck('pk');
        $term = DB::table('term_master')->value('pk');
        $examType = DB::table('examination_type_master')->where('exam_type_name', 'not like', '%director%')->value('pk');
        $faculty = DB::table('faculty_master')->value('pk');
        if (! $cohort || ! $admin || $subjects->count() < 2 || ! $term || ! $examType || ! $faculty) {
            $this->markTestSkipped('Needs a course with 3+ participant codes, a Super Admin, 2 subjects, a term, an exam type and a faculty.');
        }
        $this->admin = $admin;
        [$this->subjectA, $this->subjectB] = [(int) $subjects[0], (int) $subjects[1]];
        $this->compX = DB::table('component_master')->insertGetId(['component_name' => 'ZZ RXR Test X '.uniqid(), 'active_inactive' => 1], 'pk');
        $this->compY = DB::table('component_master')->insertGetId(['component_name' => 'ZZ RXR Test Y '.uniqid(), 'active_inactive' => 1], 'pk');

        $this->drive = ExaminationDrive::create([
            'drive_name' => 'ZZ Reexam Result Drive',
            'examination_type_master_pk' => $examType,
            'term_master_pk' => $term,
            'course_master_pk' => $cohort->course_master_pk,
            'phase' => (string) $cohort->batch,
            'academic_session' => $cohort->batch,
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'status' => ExaminationDrive::STATUS_ACTIVE,
            'cc_status' => ExaminationDrive::CC_APPROVED,
        ]);
        DB::table('examination_drive_faculty_maps')->insert([
            ['examination_drive_id' => $this->drive->id, 'subject_master_pk' => $this->subjectA, 'faculty_master_pk' => $faculty],
            ['examination_drive_id' => $this->drive->id, 'subject_master_pk' => $this->subjectB, 'faculty_master_pk' => $faculty],
        ]);
        $map = fn (int $subject, int $component, float $max) => [
            'examination_drive_id' => $this->drive->id, 'subject_master_pk' => $subject, 'component_master_pk' => $component,
            'faculty_master_pk' => $faculty, 'max_marks' => $max, 'passing_marks' => $max / 2, 'source' => 'Faculty',
            'weightage' => 50, 'exam_date' => now()->subDays(3)->toDateString(), 'exam_end_date' => now()->subDays(3)->toDateString(),
            'exam_mode' => 'Offline',
        ];
        DB::table('examination_drive_component_maps')->insert([$map($this->subjectA, $this->compX, 50), $map($this->subjectB, $this->compY, 20)]);

        $this->ots = array_keys(app(ResultMarksService::class)->participants($this->drive));
        foreach ($this->ots as $i => $pk) {
            $this->mark($this->drive->id, $this->subjectA, $this->compX, $pk, $i === 0 ? 10 : 40);
            $this->mark($this->drive->id, $this->subjectB, $this->compY, $pk, $i === 1 ? null : 18);
        }
        ExaminationDrive::where('course_master_pk', $this->drive->course_master_pk)
            ->where('id', '!=', $this->drive->id)->update(['status' => ExaminationDrive::STATUS_DRAFT]);

        // Re-exam 1 for OT 1 (A) and OT 2 (B); CC-approved and its exam over, so marks can be entered.
        $this->actingAs($this->admin)->postJson(route('admin.coe.examination-drive.reexam.store', $this->drive->id), [
            'keys' => [$this->subjectA.':'.$this->ots[0], $this->subjectB.':'.$this->ots[1]],
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ])->assertOk();
        $this->reexam = ExaminationDrive::findOrFail(Reexam::where('main_drive_id', $this->drive->id)->value('reexam_drive_id'));
        $this->reexam->update(['cc_status' => ExaminationDrive::CC_APPROVED]);
        DB::table('examination_drive_component_maps')->where('examination_drive_id', $this->reexam->id)
            ->update(['exam_date' => now()->subDay()->toDateString(), 'exam_end_date' => now()->subDay()->toDateString()]);
    }

    /** An approved mark (moderated on the main drive only, as Marks Moderation would); null = absent. */
    private function mark(int $driveId, int $subject, int $component, int $student, ?float $marks, int $status = 2): void
    {
        $row = [
            'examination_drive_id' => $driveId, 'subject_master_pk' => $subject, 'component_master_pk' => $component,
            'student_master_pk' => $student, 'marks' => $marks, 'is_absent' => $marks === null ? 1 : 0, 'status' => $status,
        ];
        if ($driveId === $this->drive->id && ResultMarksService::moderationEnabled() && $marks !== null && $status === 2) {
            $row['moderated_marks'] = $marks;
        }
        DB::table('coe_evaluation_marks')->updateOrInsert(
            array_intersect_key($row, array_flip(['examination_drive_id', 'subject_master_pk', 'component_master_pk', 'student_master_pk'])),
            $row
        );
    }

    private function key(int $driveId, int $subject, int $component): string
    {
        return $driveId.':'.$subject.':'.$component;
    }

    private function result(): array
    {
        return $this->actingAs($this->admin)->getJson(route('admin.coe.examination-drive.reexam.result', $this->reexam->id))->assertOk()->json();
    }

    /** Re-exam marks in: OT 1 passes A (30/50), OT 2 is absent again in B. */
    private function reexamMarksIn(): void
    {
        $this->mark($this->reexam->id, $this->subjectA, $this->compX, $this->ots[0], 30);
        $this->mark($this->reexam->id, $this->subjectB, $this->compY, $this->ots[1], null);
    }

    /* ───────────── Step 5: Result Entry ───────────── */

    public function test_result_entry_lists_only_the_reexam_ots_and_the_main_drive_is_unchanged(): void
    {
        $columns = collect(app(ResultMarksService::class)->entryColumns($this->admin, (int) $this->drive->course_master_pk))->keyBy('key');

        $this->assertSame([$this->ots[0]], $columns[$this->key($this->reexam->id, $this->subjectA, $this->compX)]['students']);
        $this->assertSame([$this->ots[1]], $columns[$this->key($this->reexam->id, $this->subjectB, $this->compY)]['students']);
        $this->assertSame($this->ots, $columns[$this->key($this->drive->id, $this->subjectA, $this->compX)]['students']);
        $this->assertSame($this->ots, $columns[$this->key($this->drive->id, $this->subjectB, $this->compY)]['students']);
    }

    public function test_reexam_marks_are_saved_and_approved_only_for_its_ots(): void
    {
        $key = $this->key($this->reexam->id, $this->subjectA, $this->compX);
        $save = fn (array $marks) => $this->actingAs($this->admin)->postJson(route('admin.coe.faculty-result-entry.save'), [
            'course' => (int) $this->drive->course_master_pk, 'subject' => $this->subjectA, 'component' => $key,
            'marks' => [$key => $marks], 'approve' => true,
        ]);

        $save([$this->ots[2] => '45'])->assertStatus(422)->assertJsonFragment(['message' => 'You are not assigned to some of these participants. Reload the page.']);
        $save([$this->ots[0] => '30'])->assertOk();

        $this->assertSame(2, (int) DB::table('coe_evaluation_marks')->where('examination_drive_id', $this->reexam->id)
            ->where('student_master_pk', $this->ots[0])->value('status'));
        $this->assertFalse(DB::table('coe_evaluation_marks')->where('examination_drive_id', $this->reexam->id)
            ->where('student_master_pk', $this->ots[2])->exists());
    }

    /* ───────────── Step 6: Re-exam result ───────────── */

    public function test_the_result_is_pending_until_the_reexam_marks_are_approved(): void
    {
        $this->mark($this->reexam->id, $this->subjectA, $this->compX, $this->ots[0], 30, 1);   // draft

        $result = $this->result();
        $this->assertFalse($result['ready']);
        $this->assertSame(2, $result['counts']['Pending']);
        $this->assertSame(['Pending', 'Pending'], array_column($result['ots'], 'result'));
    }

    public function test_the_reexam_marks_decide_pass_or_fail_without_moderation(): void
    {
        $this->reexamMarksIn();

        $result = $this->result();
        $this->assertTrue($result['ready']);
        $this->assertSame(1, $result['reexam_no']);
        $ots = collect($result['ots'])->keyBy('student_pk');
        $this->assertSame('Pass', $ots[$this->ots[0]]['result']);   // 30/50 replaces 10/50
        $this->assertSame('Fail', $ots[$this->ots[1]]['result']);   // AB again
        $rows = collect($result['rows'])->keyBy('student_pk');
        $this->assertEquals(60.0, $rows[$this->ots[0]]['score']);
        $this->assertSame('Failed', $rows[$this->ots[0]]['previous']);
        $this->assertTrue($rows[$this->ots[1]]['absent']);
        $this->assertSame('Absent', $rows[$this->ots[1]]['previous']);
    }

    public function test_the_main_merit_result_and_rank_are_not_touched(): void
    {
        $before = collect(app(ResultMarksService::class)->merit('active', (int) $this->drive->course_master_pk)['rows'])->keyBy('student');
        $this->reexamMarksIn();
        $after = collect(app(ResultMarksService::class)->merit('active', (int) $this->drive->course_master_pk)['rows'])->keyBy('student');

        foreach ($this->ots as $pk) {
            $this->assertSame($before[$pk]['result'] ?? null, $after[$pk]['result'] ?? null);
            $this->assertSame($before[$pk]['rank'] ?? null, $after[$pk]['rank'] ?? null);
        }
    }

    public function test_the_drive_list_shows_result_on_a_cc_approved_reexam_only(): void
    {
        $table = app(\App\DataTables\ExaminationDriveDataTable::class);
        $cell = fn (ExaminationDrive $d) => (string) (new \ReflectionMethod($table, 'actionsCell'))->invoke($table, $d->fresh()->load('reexamLink'));

        $this->assertStringContainsString('coe-act--rxresult', $cell($this->reexam));
        $this->assertStringNotContainsString('coe-act--rxresult', $cell($this->drive));
        $this->reexam->update(['cc_status' => ExaminationDrive::CC_PENDING]);
        $this->assertStringNotContainsString('coe-act--rxresult', $cell($this->reexam));
    }

    /* ───────────── Step 7: Re-exam marksheet ───────────── */

    private function needsMarksheetTable(): void
    {
        if (! ReexamMarksheetService::enabled()) {
            $this->markTestSkipped('Run the coe_reexam_marksheets migration first.');
        }
    }

    /** @return list<array<string, mixed>> this re-exam's marksheets as the Re-exam tab lists them now */
    private function marksheets(): array
    {
        return array_values(array_filter(app(ReexamMarksheetService::class)->rows(),
            fn ($m) => $m['reexam_key'] === (string) Reexam::where('reexam_drive_id', $this->reexam->id)->value('id')));
    }

    public function test_a_reexam_marksheet_appears_once_the_result_is_in_with_pass_or_fail_only(): void
    {
        $this->needsMarksheetTable();
        $this->assertSame([], $this->marksheets());

        $this->reexamMarksIn();
        $rows = collect($this->marksheets())->keyBy('code');

        $this->assertCount(2, $rows);
        $this->assertSame('Pass', $rows[(string) $this->ots[0]]['result']);
        $this->assertSame([['name' => $rows[(string) $this->ots[0]]['subjects'][0]['name'], 'result' => 'Pass']], $rows[(string) $this->ots[0]]['subjects']);
        $this->assertSame('Fail', $rows[(string) $this->ots[1]]['result']);
        $this->assertArrayNotHasKey('marks', $rows[(string) $this->ots[0]]);
        $this->assertArrayNotHasKey('grand', $rows[(string) $this->ots[0]]);
        $this->assertMatchesRegularExpression('#^RMS/[A-Z0-9]+/\d{4}/\d{4}$#', $rows[(string) $this->ots[0]]['number']);
        $this->assertSame('Generated', $rows[(string) $this->ots[0]]['status']);
        $this->assertSame(2, ReexamMarksheet::where('reexam_id', (int) $rows[(string) $this->ots[0]]['reexam_key'])->count());
    }

    public function test_the_pages_open_and_the_sheet_has_no_marks(): void
    {
        $this->needsMarksheetTable();
        $this->reexamMarksIn();
        $row = collect($this->marksheets())->firstWhere('code', (string) $this->ots[0]);

        $this->actingAs($this->admin)->get(route('admin.coe.reexam-marksheet.index'))->assertOk()->assertSee($row['number']);
        $this->actingAs($this->admin)->get(route('admin.coe.reexam-marksheet.verified'))->assertOk()->assertDontSee($row['number']);
        $this->actingAs($this->admin)->get(route('admin.coe.reexam-marksheet.show', ['reexam' => $row['reexam_key'], 'ot' => $row['code']]))
            ->assertOk()->assertSee('RE-EXAMINATION RESULT')->assertSee('Pass')->assertDontSee('Marks obtained');
        $this->actingAs($this->admin)->get(route('admin.coe.marksheet-generation.index'))->assertOk()->assertSee(route('admin.coe.reexam-marksheet.index'));
    }

    /** The $nth user outside Super Admin and the three signatory roles, given $role (as CoeMarksheetSigningFlowTest). */
    private function signer(string $role, int $nth): User
    {
        foreach (['UDC', 'AAO', 'COE'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $user = User::whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['Super Admin', 'UDC', 'AAO', 'COE']))
            ->orderBy('pk')->skip($nth)->first()
            ?? $this->markTestSkipped('Needs users outside Super Admin, UDC, AAO and COE.');
        Permission::firstOrCreate(['name' => 'coe_marksheet_generation', 'guard_name' => 'web']);
        $roleModel = Role::findByName($role, 'web');
        if (! $roleModel->hasPermissionTo('coe_marksheet_generation')) {
            $roleModel->givePermissionTo('coe_marksheet_generation');
        }
        $user->assignRole($role);

        return $user->fresh();
    }

    public function test_udc_then_aao_then_coe_sign_and_the_coe_publishes(): void
    {
        $this->needsMarksheetTable();
        if (app(MarksheetService::class)->signatureProblem() !== null) {
            $this->markTestSkipped('Needs one Active UDC, AAO and COE signature with its image.');
        }
        $this->reexamMarksIn();
        $row = collect($this->marksheets())->firstWhere('code', (string) $this->ots[0]);
        $verify = fn (User $u) => $this->actingAs($u)->postJson(route('admin.coe.reexam-marksheet.publish'), ['reexam' => $row['reexam_key'], 'ots' => [$row['code']]]);
        [$udc, $aao, $coe] = [$this->signer('UDC', 0), $this->signer('AAO', 1), $this->signer('COE', 2)];

        $verify($aao)->assertStatus(422)->assertJsonFragment(['message' => '1 marksheet is not verified by the UDC yet. The UDC verifies first, then the AAO.']);
        $verify($udc)->assertOk();
        $verify($aao)->assertOk();
        $verify($coe)->assertOk()->assertJsonFragment(['published' => 1]);

        $record = ReexamMarksheet::where('reexam_id', (int) $row['reexam_key'])->where('student_master_pk', $this->ots[0])->firstOrFail();
        $this->assertTrue($record->isPublished());
        $this->assertEqualsCanonicalizing(['UDC', 'AAO', 'COE'], array_keys($record->snapshot['signatures']));
        $this->assertSame('Pass', $record->snapshot['result']);
        $this->actingAs($this->admin)->get(route('admin.coe.reexam-marksheet.verified'))->assertOk()->assertSee($row['number']);
    }
}
