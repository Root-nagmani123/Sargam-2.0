<?php

namespace Tests\Feature;

use App\Models\COE\Reexam;
use App\Models\COE\ReexamCandidate;
use App\Models\ExaminationDrive;
use App\Models\ExaminationDriveFacultyMap;
use App\Models\FacultyMaster;
use App\Models\QuestionPaper;
use App\Models\User;
use App\Services\COE\QuestionPaperService;
use App\Services\COE\RajbhashaPaperService;
use App\Services\COE\ResultMarksService;
use App\Services\COE\ReexamService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * COE Re-exam, step 1 (COE side): who is listed (Failed / Absent per subject, score %),
 * the "results final" gate, creating the re-exam drive (copy of the main drive, dates
 * empty, candidates saved), Re-exam 2 from the latest results, and the guards (Edit /
 * Delete, re-exam drives kept out of the result screens). Development database, in a
 * transaction.
 *
 * Fixture: one Active, CC-approved main drive whose exams are over, on a course with
 * Unique Participant Codes. Subject A: component X (max 50). Subject B: component Y
 * (max 20). Every participant has approved (and moderated) marks: OT 1 scores 10/50 in A
 * (Failed), OT 2 is AB in B (Absent), everyone else passes.
 */
class CoeReexamFlowTest extends TestCase
{
    use DatabaseTransactions;

    private ExaminationDrive $drive;

    private User $admin;

    private int $subjectA;

    private int $subjectB;

    private int $compX;

    private int $compY;

    private int $facultyPk;

    /** The faculty's login (Faculty role), when the dev database has one. */
    private ?User $facultyUser = null;

    /** @var list<int> participant student pks, in code order */
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
        // A faculty with a login where there is one (the faculty-side tests need it).
        $this->facultyUser = User::role('Faculty')->get()
            ->first(fn (User $u) => FacultyMaster::where('employee_master_pk', $u->user_id)->exists());
        $faculty = $this->facultyUser
            ? FacultyMaster::where('employee_master_pk', $this->facultyUser->user_id)->value('pk')
            : DB::table('faculty_master')->value('pk');
        if (! $cohort || ! $admin || $subjects->count() < 2 || ! $term || ! $examType || ! $faculty) {
            $this->markTestSkipped('Needs a course with 3+ participant codes, a Super Admin, 2 subjects, a term, an exam type and a faculty.');
        }

        $this->admin = $admin;
        $this->facultyPk = (int) $faculty;
        [$this->subjectA, $this->subjectB] = [(int) $subjects[0], (int) $subjects[1]];
        $this->compX = DB::table('component_master')->insertGetId(['component_name' => 'ZZ RX Test X '.uniqid(), 'active_inactive' => 1], 'pk');
        $this->compY = DB::table('component_master')->insertGetId(['component_name' => 'ZZ RX Test Y '.uniqid(), 'active_inactive' => 1], 'pk');

        $this->drive = ExaminationDrive::create([
            'drive_name' => 'ZZ Reexam Test Drive',
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
            ['examination_drive_id' => $this->drive->id, 'subject_master_pk' => $this->subjectA, 'faculty_master_pk' => $this->facultyPk],
            ['examination_drive_id' => $this->drive->id, 'subject_master_pk' => $this->subjectB, 'faculty_master_pk' => $this->facultyPk],
        ]);
        $map = fn (int $subject, int $component, float $max) => [
            'examination_drive_id' => $this->drive->id, 'subject_master_pk' => $subject, 'component_master_pk' => $component,
            'faculty_master_pk' => $this->facultyPk, 'max_marks' => $max, 'passing_marks' => $max / 2, 'source' => 'Faculty',
            'weightage' => 50, 'exam_date' => now()->subDays(3)->toDateString(), 'exam_end_date' => now()->subDays(3)->toDateString(),
            'exam_mode' => 'Offline',
        ];
        DB::table('examination_drive_component_maps')->insert([
            $map($this->subjectA, $this->compX, 50),
            $map($this->subjectB, $this->compY, 20),
        ]);
        // Faculty setup checks a subject's total against Subject Max Marks.
        foreach ([$this->subjectA, $this->subjectB] as $subject) {
            DB::table('subject_max_marks_master')->updateOrInsert(['subject_master_pk' => $subject], ['max_marks' => 100, 'active_inactive' => 1]);
        }

        $this->ots = array_keys(app(ResultMarksService::class)->participants($this->drive));
        foreach ($this->ots as $i => $pk) {
            $this->mark($this->drive->id, $this->subjectA, $this->compX, $pk, $i === 0 ? 10 : 40);
            $this->mark($this->drive->id, $this->subjectB, $this->compY, $pk, $i === 1 ? null : 18);
        }
        // Agreed flow: a re-exam opens only after the marksheets are published.
        $this->publishMarksheets($this->ots);
    }

    /** Published (COE signed) marksheets for these OTs on the drive's course. */
    private function publishMarksheets(array $students, int $status = 2): void
    {
        foreach ($students as $i => $pk) {
            DB::table('coe_marksheets')->updateOrInsert(
                ['course_master_pk' => $this->drive->course_master_pk, 'student_master_pk' => $pk],
                ['serial' => 9000 + $i, 'marksheet_number' => 'ZZ-RX/'.$pk.'/'.uniqid(), 'status' => $status]
            );
        }
    }

    /** An approved (and, unless $moderate is false, moderated) mark; null = absent. */
    private function mark(int $driveId, int $subject, int $component, int $student, ?float $marks, int $status = 2, bool $moderate = true): void
    {
        $row = [
            'examination_drive_id' => $driveId, 'subject_master_pk' => $subject, 'component_master_pk' => $component,
            'student_master_pk' => $student, 'marks' => $marks, 'is_absent' => $marks === null ? 1 : 0, 'status' => $status,
        ];
        if ($moderate && ResultMarksService::moderationEnabled() && $marks !== null && $status === 2) {
            $row['moderated_marks'] = $marks;
        }
        DB::table('coe_evaluation_marks')->updateOrInsert(
            array_intersect_key($row, array_flip(['examination_drive_id', 'subject_master_pk', 'component_master_pk', 'student_master_pk'])),
            $row
        );
    }

    private function sheet(?int $driveId = null): array
    {
        return $this->actingAs($this->admin)->getJson(route('admin.coe.examination-drive.reexam.show', $driveId ?? $this->drive->id))
            ->assertOk()->json();
    }

    private function create(array $keys, ?string $name = null)
    {
        return $this->actingAs($this->admin)->postJson(route('admin.coe.examination-drive.reexam.store', $this->drive->id), [
            'keys' => $keys,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'name' => $name,
        ]);
    }

    public function test_the_list_shows_failed_and_absent_ots_per_subject(): void
    {
        $sheet = $this->sheet();

        $this->assertTrue($sheet['ready']);
        $this->assertSame(1, $sheet['next_no']);
        $this->assertSame('ZZ Reexam Test Drive — Re-exam 1', $sheet['default_name']);
        $rows = collect($sheet['rows'])->keyBy('key');
        $this->assertCount(2, $rows);

        $failed = $rows[$this->subjectA.':'.$this->ots[0]];
        $this->assertSame(ReexamCandidate::REASON_FAILED, $failed['reason']);
        $this->assertEquals(20.0, $failed['score']);   // 10 / 50

        $absent = $rows[$this->subjectB.':'.$this->ots[1]];
        $this->assertSame(ReexamCandidate::REASON_ABSENT, $absent['reason']);
        $this->assertEquals(0.0, $absent['score']);
    }

    public function test_no_list_until_every_mark_is_final(): void
    {
        $this->mark($this->drive->id, $this->subjectA, $this->compX, $this->ots[2], 40, 1);   // a draft

        $sheet = $this->sheet();

        $this->assertFalse($sheet['ready']);
        $this->assertSame([], $sheet['rows']);
        $this->assertStringContainsString('not final', $sheet['message']);
        $this->actingAs($this->admin)->getJson(route('admin.coe.examination-drive.reexam.ready', ['ids' => [$this->drive->id]]))
            ->assertOk()->assertJson(['ready' => []]);
    }

    public function test_no_reexam_until_the_marksheets_are_published(): void
    {
        // One OT's marksheet only Generated (not signed by COE yet).
        $this->publishMarksheets([$this->ots[2]], 1);

        $sheet = $this->sheet();

        $this->assertFalse($sheet['ready']);
        $this->assertSame([], $sheet['rows']);
        $this->assertStringContainsString('marksheets are not published yet', $sheet['message']);
        $this->actingAs($this->admin)->getJson(route('admin.coe.examination-drive.reexam.ready', ['ids' => [$this->drive->id]]))
            ->assertOk()->assertJson(['ready' => []]);
        $this->create([$this->subjectA.':'.$this->ots[0]])->assertStatus(422);
    }

    public function test_the_drive_list_shows_the_button_only_when_ready(): void
    {
        $this->actingAs($this->admin)->getJson(route('admin.coe.examination-drive.reexam.ready', ['ids' => [$this->drive->id]]))
            ->assertOk()->assertJson(['ready' => [$this->drive->id]]);
    }

    public function test_creating_a_reexam_copies_the_main_drive_for_the_ticked_ots(): void
    {
        $key = $this->subjectA.':'.$this->ots[0];

        $this->create([$key])->assertOk();

        $link = Reexam::where('main_drive_id', $this->drive->id)->firstOrFail();
        $this->assertSame(1, $link->reexam_no);
        $reexam = ExaminationDrive::findOrFail($link->reexam_drive_id);
        $this->assertSame('ZZ Reexam Test Drive — Re-exam 1', $reexam->drive_name);
        $this->assertSame(ExaminationDrive::STATUS_ACTIVE, $reexam->status);
        $this->assertNull($reexam->cc_status);
        $this->assertSame((int) $this->drive->course_master_pk, (int) $reexam->course_master_pk);
        $this->assertSame(now()->addDays(10)->toDateString(), $reexam->start_date->toDateString());

        // Only subject A (the ticked one) is copied, faculty and component, dates empty.
        $this->assertSame([$this->subjectA], DB::table('examination_drive_faculty_maps')->where('examination_drive_id', $reexam->id)->pluck('subject_master_pk')->map(fn ($v) => (int) $v)->all());
        $components = DB::table('examination_drive_component_maps')->where('examination_drive_id', $reexam->id)->get();
        $this->assertCount(1, $components);
        $this->assertSame($this->compX, (int) $components[0]->component_master_pk);
        $this->assertEquals(50, $components[0]->max_marks);
        $this->assertSame('Offline', $components[0]->exam_mode);
        $this->assertNull($components[0]->exam_date);
        $this->assertNull($components[0]->exam_end_date);

        $candidates = $link->candidates()->get();
        $this->assertCount(1, $candidates);
        $this->assertSame($this->ots[0], $candidates[0]->student_master_pk);
        $this->assertSame(ReexamCandidate::REASON_FAILED, $candidates[0]->reason);
        $this->assertEquals(20.0, $candidates[0]->score_percent);

        // The main drive is untouched.
        $this->assertSame(2, DB::table('examination_drive_component_maps')->where('examination_drive_id', $this->drive->id)->count());
        $this->assertSame(ExaminationDrive::CC_APPROVED, $this->drive->fresh()->cc_status);
    }

    public function test_a_custom_name_is_kept(): void
    {
        $this->create([$this->subjectA.':'.$this->ots[0]], 'Law Supplementary')->assertOk();

        $this->assertSame('Law Supplementary', Reexam::where('main_drive_id', $this->drive->id)->firstOrFail()->reexamDrive->drive_name);
    }

    public function test_an_ot_not_on_the_list_is_refused(): void
    {
        $passed = $this->subjectA.':'.$this->ots[2];

        $this->create([$passed])->assertStatus(422);
        $this->create([])->assertStatus(422);
        $this->assertFalse(Reexam::where('main_drive_id', $this->drive->id)->exists());
    }

    public function test_reexam_two_uses_the_latest_results(): void
    {
        $this->create([$this->subjectA.':'.$this->ots[0], $this->subjectB.':'.$this->ots[1]])->assertOk();
        $reexamId = Reexam::where('main_drive_id', $this->drive->id)->value('reexam_drive_id');

        // Re-exam 1 marks not in yet: no Re-exam 2.
        $sheet = $this->sheet();
        $this->assertFalse($sheet['ready']);
        $this->assertStringContainsString('Re-exam 1', $sheet['message']);

        // OT 1 passes A on the re-exam (replaces 10/50); OT 2 still fails B. Re-exams have
        // no moderation: approved marks count as they are.
        $this->mark($reexamId, $this->subjectA, $this->compX, $this->ots[0], 30, 2, false);
        $this->mark($reexamId, $this->subjectB, $this->compY, $this->ots[1], 5, 2, false);

        $sheet = $this->sheet();
        $this->assertTrue($sheet['ready']);
        $this->assertSame(2, $sheet['next_no']);
        $this->assertSame([$this->subjectB.':'.$this->ots[1]], array_column($sheet['rows'], 'key'));
        $this->assertSame(ReexamCandidate::REASON_FAILED, $sheet['rows'][0]['reason']);
        $this->assertEquals(25.0, $sheet['rows'][0]['score']);   // 5 / 20

        $this->create([$this->subjectB.':'.$this->ots[1]])->assertOk();
        $this->assertSame([1, 2], Reexam::where('main_drive_id', $this->drive->id)->orderBy('reexam_no')->pluck('reexam_no')->all());
    }

    public function test_a_reexam_drive_has_no_reexam_and_cannot_be_edited(): void
    {
        $this->create([$this->subjectA.':'.$this->ots[0]])->assertOk();
        $reexamId = (int) Reexam::where('main_drive_id', $this->drive->id)->value('reexam_drive_id');

        $this->assertFalse($this->sheet($reexamId)['ready']);
        $this->actingAs($this->admin)->putJson(route('admin.coe.examination-drive.update', $reexamId), [])->assertStatus(422);
    }

    public function test_the_main_drive_cannot_be_deleted_while_it_has_reexams_but_a_reexam_can(): void
    {
        $this->create([$this->subjectA.':'.$this->ots[0]])->assertOk();
        $reexamId = (int) Reexam::where('main_drive_id', $this->drive->id)->value('reexam_drive_id');
        $this->drive->update(['cc_status' => null]);   // so only the re-exam rule refuses

        $this->actingAs($this->admin)->deleteJson(route('admin.coe.examination-drive.destroy', $this->drive->id))->assertStatus(422);

        $this->actingAs($this->admin)->deleteJson(route('admin.coe.examination-drive.destroy', $reexamId))->assertOk();
        $this->assertFalse(Reexam::where('reexam_drive_id', $reexamId)->exists());
        $this->assertSame(0, DB::table('coe_reexam_candidates')->whereNotIn('reexam_id', DB::table('coe_reexams')->select('id'))->count());
    }

    /* ───────────── Step 2: faculty setup and CC approval ───────────── */

    /** Re-exam 1 for OT 1 in subject A, far in the future so no real exam slot clashes. */
    private function reexamDrive(): ExaminationDrive
    {
        $this->actingAs($this->admin)->postJson(route('admin.coe.examination-drive.reexam.store', $this->drive->id), [
            'keys' => [$this->subjectA.':'.$this->ots[0]],
            'start_date' => now()->addDays(400)->toDateString(),
            'end_date' => now()->addDays(410)->toDateString(),
        ])->assertOk();

        return ExaminationDrive::findOrFail(Reexam::where('main_drive_id', $this->drive->id)->value('reexam_drive_id'));
    }

    private function needsFacultyLogin(): void
    {
        if (! $this->facultyUser) {
            $this->markTestSkipped('Needs a Faculty-role user linked to faculty_master.');
        }
        if (! Schema::hasColumn('examination_drive_component_maps', 'exam_start_at')) {
            $this->markTestSkipped('Run the COE migrations first (exam_start_at).');
        }
    }

    private function setupRequest(ExaminationDrive $reexam, array $row)
    {
        return $this->actingAs($this->facultyUser)->putJson(route('admin.coe.examination-drive.components.save', $reexam->id), [
            'subject_master_pk' => $this->subjectA,
            'rows' => [$row],
        ]);
    }

    public function test_faculty_sets_only_the_exam_range_on_a_reexam(): void
    {
        $this->needsFacultyLogin();
        $reexam = $this->reexamDrive();
        $component = DB::table('component_master')->where('pk', $this->compX)->value('component_name');
        $day = now()->addDays(402)->toDateString();

        $facultyName = trim((string) FacultyMaster::where('pk', $this->facultyPk)->value('full_name'));

        // Tries to change marks and mode too: only the range is taken.
        $this->setupRequest($reexam, [
            'component' => $component, 'max_marks' => 5, 'source' => 'Faculty', 'faculty' => $facultyName,
            'exam_start_at' => $day.' 10:00', 'exam_end_at' => $day.' 13:00',
            'exam_mode' => 'Online', 'exam_type' => 'Online Objective',
        ])->assertOk()->assertJsonFragment(['message' => 'Component mapping saved. Sent to the Course Coordinator for approval.']);

        $map = DB::table('examination_drive_component_maps')->where('examination_drive_id', $reexam->id)->first();
        $this->assertEquals(50, $map->max_marks);
        $this->assertSame('Offline', $map->exam_mode);
        $this->assertSame($this->facultyPk, (int) $map->faculty_master_pk);
        $this->assertSame($day.' 10:00:00', $map->exam_start_at);
        $this->assertSame($day, $map->exam_date);
        // Every subject of the re-exam dated: off to the CC.
        $this->assertSame(ExaminationDrive::CC_PENDING, $reexam->fresh()->cc_status);
    }

    public function test_a_reexam_component_needs_its_range_inside_the_reexam_dates(): void
    {
        $this->needsFacultyLogin();
        $reexam = $this->reexamDrive();
        $component = DB::table('component_master')->where('pk', $this->compX)->value('component_name');
        $base = ['component' => $component, 'max_marks' => 50, 'source' => 'Faculty', 'exam_mode' => 'Offline', 'exam_type' => 'Offline Objective'];

        $this->setupRequest($reexam, $base)->assertStatus(422);
        $outside = now()->addDays(420)->toDateString();
        $this->setupRequest($reexam, $base + ['exam_start_at' => $outside.' 10:00', 'exam_end_at' => $outside.' 12:00'])->assertStatus(422);
        $this->assertNull($reexam->fresh()->cc_status);
    }

    public function test_assign_faculty_is_refused_on_a_reexam(): void
    {
        $reexam = $this->reexamDrive();
        $component = DB::table('component_master')->where('pk', $this->compX)->value('component_name');

        $this->actingAs($this->admin)->putJson(route('admin.coe.examination-drive.components.assign-faculty', $reexam->id), [
            'subject_master_pk' => $this->subjectA, 'component' => $component, 'faculty' => null,
        ])->assertStatus(422);
    }

    public function test_the_cc_confirms_reexam_slots_inside_the_reexam_dates(): void
    {
        if (! Schema::hasColumn('examination_drive_component_maps', 'proposed_start_at')) {
            $this->markTestSkipped('Run the COE migrations first (proposed_start_at).');
        }
        $reexam = $this->reexamDrive();
        $reexam->update(['cc_status' => ExaminationDrive::CC_PENDING]);
        $mapId = (int) DB::table('examination_drive_component_maps')->where('examination_drive_id', $reexam->id)->value('id');
        $slot = fn (int $days) => ['slots' => [$mapId => [
            'start' => now()->addDays($days)->format('Y-m-d').'T10:00', 'end' => now()->addDays($days)->format('Y-m-d').'T13:00',
        ]]];

        $this->actingAs($this->admin)->putJson(route('admin.coe.cc-examination-drive.approve', $reexam->id), $slot(430))
            ->assertStatus(422)->assertJsonFragment(['message' => 'The proposed exam dates must be within the re-exam dates ('
                .now()->addDays(400)->format('d-m-Y').' to '.now()->addDays(410)->format('d-m-Y').').']);

        // After the course has ended is fine for a re-exam.
        $this->actingAs($this->admin)->putJson(route('admin.coe.cc-examination-drive.approve', $reexam->id), $slot(405))->assertOk();
        $this->assertSame(ExaminationDrive::CC_APPROVED, $reexam->fresh()->cc_status);
    }

    public function test_the_faculty_list_marks_a_reexam(): void
    {
        $this->reexamDrive();

        $json = $this->actingAs($this->admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.coe.faculty-examination-drive.index', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertOk()->json('data');

        $row = collect($json)->first(fn ($r) => str_contains($r['name'], 'ZZ Reexam Test Drive — Re-exam 1'));
        $this->assertNotNull($row);
        $this->assertStringContainsString('edc-reexam-sub">Re-exam 1</span>', $row['name']);
        $this->assertStringContainsString('Complete<br>Setup', $row['actions']);
    }

    /* ───────────── Step 3: question paper and Rajbhasha ───────────── */

    public function test_a_reexam_gets_its_own_question_paper_through_rajbhasha(): void
    {
        Storage::fake(QuestionPaper::DISK);
        $reexam = $this->reexamDrive();
        $faculty = app(QuestionPaperService::class);

        // Not before the CC approves the re-exam.
        $mapId = (int) ExaminationDriveFacultyMap::where('examination_drive_id', $reexam->id)->value('id');
        $this->assertFalse($faculty->papersForFaculty($this->facultyPk)->firstWhere('id', $mapId)['editable']);

        $reexam->update(['cc_status' => ExaminationDrive::CC_APPROVED]);
        $row = $faculty->papersForFaculty($this->facultyPk)->firstWhere('id', $mapId);
        $this->assertTrue($row['editable']);
        $this->assertSame('ZZ Reexam Test Drive — Re-exam 1', $row['drive']);

        $this->actingAs($this->facultyUser ?? $this->admin);
        $paper = $faculty->saveOriginalPaper($this->facultyPk, $mapId, '<p>Re-exam question 1</p>');

        $this->assertSame($reexam->id, (int) $paper->examination_drive_id);
        $this->assertSame(QuestionPaper::STATUS_PENDING_RAJBHASHA, $paper->status);
        $this->assertSame(0, QuestionPaper::where('examination_drive_id', $this->drive->id)->count());
        $this->assertTrue(app(RajbhashaPaperService::class)->papers()->contains(fn ($p) => $p['drive'] === 'ZZ Reexam Test Drive — Re-exam 1'));
    }

    public function test_reexam_drives_stay_out_of_the_result_screens(): void
    {
        $this->create([$this->subjectA.':'.$this->ots[0]])->assertOk();
        $reexamId = (int) Reexam::where('main_drive_id', $this->drive->id)->value('reexam_drive_id');
        ExaminationDrive::whereKey($reexamId)->update(['cc_status' => ExaminationDrive::CC_APPROVED]);

        $ids = ReexamService::excludeFrom(ExaminationDrive::where('course_master_pk', $this->drive->course_master_pk))->pluck('id')->all();
        $this->assertContains($this->drive->id, $ids);
        $this->assertNotContains($reexamId, $ids);

        $columns = collect(app(ResultMarksService::class)->entryColumns($this->admin, (int) $this->drive->course_master_pk));
        $this->assertTrue($columns->contains('drive_id', $this->drive->id));
        $this->assertFalse($columns->contains('drive_id', $reexamId));
    }
}
