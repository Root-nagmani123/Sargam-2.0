<?php

namespace Tests\Feature;

use App\Models\COE\EvaluationMark;
use App\Models\ExaminationDrive;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * COE → Result Management, end to end: who may enter which marks (subject faculty
 * vs a component evaluator limited to their codes), validation, draft save,
 * Save & Approve and its lock, the CC-approval gate, Result Compilation counts and
 * average, the participants lists, and Notify for Pending. Development database,
 * in a transaction.
 *
 * Fixture: one Active, CC-approved drive on a course that has Unique Participant Codes.
 *   Subject A (owner faculty F1): component X (max 50), component Y (max 30; evaluator F2
 *                                 with the first 3 codes)
 *   Subject B (owner faculty F3): component Z (max 20)
 */
class CoeResultManagementFlowTest extends TestCase
{
    use DatabaseTransactions;

    private ExaminationDrive $drive;

    private Role $role;

    private User $admin;

    /** @var array<int, User> faculty index (1..3) => login */
    private array $faculty = [];

    /** @var array<int, string> faculty index => trimmed full name */
    private array $names = [];

    private int $subjectA;

    private int $subjectB;

    /** @var array<string, int> component pks: X, Y, Z */
    private array $components = [];

    /** @var list<array{pk:int, code:string}> participants in code order */
    private array $ots = [];

    protected function setUp(): void
    {
        parent::setUp();

        $cohort = DB::table('coe_participant_codes')
            ->groupBy('course_master_pk', 'batch')->havingRaw('count(*) >= 5')
            ->first(['course_master_pk', 'batch']);
        $faculty = DB::table('faculty_master as f')
            ->join('user_credentials as u', 'u.user_id', '=', 'f.employee_master_pk')
            ->select('f.pk', 'f.full_name', 'u.pk as user_pk')
            ->groupBy('f.pk', 'f.full_name', 'u.pk')
            ->limit(3)->get();
        $admin = User::role('Super Admin')->first();
        $subjects = DB::table('subject_master')->orderBy('pk')->limit(2)->pluck('pk');
        $term = DB::table('term_master')->value('pk');
        $examType = DB::table('examination_type_master')->value('pk');

        if (! $cohort || $faculty->count() < 3 || ! $admin || $subjects->count() < 2 || ! $term || ! $examType) {
            $this->markTestSkipped('Needs a course with 5+ participant codes, 3 faculty with logins, a Super Admin, 2 subjects, a term and an exam type.');
        }

        $this->admin = $admin;
        [$this->subjectA, $this->subjectB] = [(int) $subjects[0], (int) $subjects[1]];

        // Through a role, as Roles & Permissions grants it (model_has_permissions on the
        // dev database is keyed on permission_id alone, so a direct grant fits one user).
        $permission = Permission::firstOrCreate(['name' => 'coe_faculty_result_entry', 'guard_name' => 'web']);
        $this->role = Role::create(['name' => 'ZZ Result Entry Test '.uniqid(), 'guard_name' => 'web'])->givePermissionTo($permission);
        foreach ($faculty->values() as $i => $f) {
            $this->faculty[$i + 1] = User::findOrFail($f->user_pk)->assignRole($this->role);
            $this->names[$i + 1] = trim($f->full_name);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['X', 'Y', 'Z'] as $c) {
            $this->components[$c] = DB::table('component_master')->insertGetId(['component_name' => 'ZZ RM Test '.$c.' '.uniqid(), 'active_inactive' => 1], 'pk');
        }

        $this->drive = ExaminationDrive::create([
            'drive_name' => 'ZZ Result Mgmt Test',
            'examination_type_master_pk' => $examType,
            'term_master_pk' => $term,
            'course_master_pk' => $cohort->course_master_pk,
            'phase' => (string) $cohort->batch,
            'academic_session' => $cohort->batch,
            // The exam is over: marks exist only after it.
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'status' => ExaminationDrive::STATUS_ACTIVE,
            'cc_status' => ExaminationDrive::CC_APPROVED,
        ]);

        $this->ots = DB::table('coe_participant_codes')
            ->where('course_master_pk', $cohort->course_master_pk)->where('batch', $cohort->batch)
            ->get(['student_master_pk as pk', 'unique_code as code'])
            ->map(fn ($r) => ['pk' => (int) $r->pk, 'code' => trim($r->code)])
            ->sort(fn ($a, $b) => \App\Services\COE\ResultMarksService::compareOtCodes($a['code'], $b['code']))
            ->values()->all();

        $fpk = $faculty->pluck('pk')->values();
        DB::table('examination_drive_faculty_maps')->insert([
            ['examination_drive_id' => $this->drive->id, 'subject_master_pk' => $this->subjectA, 'faculty_master_pk' => $fpk[0]],
            ['examination_drive_id' => $this->drive->id, 'subject_master_pk' => $this->subjectB, 'faculty_master_pk' => $fpk[2]],
        ]);
        $map = fn (int $subject, string $c, float $max, ?int $facultyPk, ?array $uniqueCodes = null) => [
            'examination_drive_id' => $this->drive->id, 'subject_master_pk' => $subject,
            'component_master_pk' => $this->components[$c], 'faculty_master_pk' => $facultyPk,
            'max_marks' => $max, 'passing_marks' => 0, 'source' => 'Faculty', 'weightage' => 50,
            'faculty_unique_codes' => $uniqueCodes ? json_encode($uniqueCodes) : null,
        ];
        DB::table('examination_drive_component_maps')->insert([
            $map($this->subjectA, 'X', 50, $fpk[0]),
            $map($this->subjectA, 'Y', 30, $fpk[1], [$this->names[2] => array_column(array_slice($this->ots, 0, 3), 'code')]),
            $map($this->subjectB, 'Z', 20, $fpk[2]),
        ]);
    }

    private function key(int $subject, string $c): string
    {
        return $this->drive->id.':'.$subject.':'.$this->components[$c];
    }

    private function save(int $who, array $marks, bool $approve = false, ?int $subject = null)
    {
        return $this->actingAs($this->faculty[$who])->postJson(route('admin.coe.faculty-result-entry.save'), [
            'course' => $this->drive->course_master_pk,
            'subject' => $subject,
            'marks' => $marks,
            'approve' => $approve,
        ]);
    }

    /** Every OT of the course in one column, all with the same value. */
    private function fill(string $key, string $value, ?array $only = null): array
    {
        return [$key => collect($only ?? $this->ots)->mapWithKeys(fn ($o) => [$o['pk'] => $value])->all()];
    }

    /** One approved mark straight in the table, so a subject reaches Result Compilation. */
    private function approveOne(int $subject, string $c, float $marks = 10): void
    {
        DB::table('coe_evaluation_marks')->insert([
            'examination_drive_id' => $this->drive->id, 'subject_master_pk' => $subject,
            'component_master_pk' => $this->components[$c], 'student_master_pk' => $this->ots[0]['pk'],
            'marks' => $marks, 'status' => EvaluationMark::STATUS_APPROVED,
        ]);
    }

    private function compilationRow(int $subject): ?array
    {
        $rows = $this->actingAs($this->admin)->get(route('admin.coe.result-compilation.index'))
            ->assertOk()->viewData('rows');

        return collect($rows)->first(fn ($r) => $r['course_pk'] === (int) $this->drive->course_master_pk && $r['subject_pk'] === $subject);
    }

    public function test_subject_faculty_sees_every_participant_and_evaluator_only_their_codes(): void
    {
        $owner = $this->actingAs($this->faculty[1])
            ->get(route('admin.coe.faculty-result-entry.index', ['course' => $this->drive->course_master_pk]))->assertOk();
        $this->assertSame([$this->key($this->subjectA, 'X'), $this->key($this->subjectA, 'Y')], array_column($owner->viewData('columns'), 'key'));
        $this->assertCount(count($this->ots), $owner->viewData('rows'));

        $evaluator = $this->actingAs($this->faculty[2])
            ->get(route('admin.coe.faculty-result-entry.index', ['course' => $this->drive->course_master_pk]))->assertOk();
        $this->assertSame([$this->key($this->subjectA, 'Y')], array_column($evaluator->viewData('columns'), 'key'));
        $this->assertSame(array_column(array_slice($this->ots, 0, 3), 'pk'), array_keys($evaluator->viewData('rows')));
        // Tutor Name is the evaluator assigned to the OT.
        $this->assertSame([$this->names[2]], $evaluator->viewData('rows')[$this->ots[0]['pk']]['tutors']);
    }

    public function test_marks_entry_is_closed_until_the_cc_approves_the_drive(): void
    {
        $this->drive->update(['cc_status' => ExaminationDrive::CC_PENDING]);

        $page = $this->actingAs($this->faculty[1])->get(route('admin.coe.faculty-result-entry.index'))->assertOk();
        $this->assertSame([], $page->viewData('columns'));
        $this->save(1, $this->fill($this->key($this->subjectA, 'X'), '10'))->assertStatus(403);
    }

    public function test_a_component_shows_only_once_its_exam_is_over(): void
    {
        // Z's exam is tomorrow: not in Result Entry, not in Result Compilation.
        DB::table('examination_drive_component_maps')->where('examination_drive_id', $this->drive->id)
            ->where('component_master_pk', $this->components['Z'])
            ->update(['exam_date' => now()->addDay()->toDateString()]);

        $this->approveOne($this->subjectA, 'X');
        $this->approveOne($this->subjectB, 'Z');

        $page = $this->actingAs($this->faculty[3])
            ->get(route('admin.coe.faculty-result-entry.index', ['course' => $this->drive->course_master_pk]))->assertOk();
        $this->assertSame([], $page->viewData('columns'));
        $this->assertNull($this->compilationRow($this->subjectB));
        $this->assertNotNull($this->compilationRow($this->subjectA));

        // The day it ends, it opens.
        DB::table('examination_drive_component_maps')->where('examination_drive_id', $this->drive->id)
            ->where('component_master_pk', $this->components['Z'])
            ->update(['exam_date' => now()->toDateString()]);
        $this->assertNotNull($this->compilationRow($this->subjectB));
    }

    public function test_compilation_says_why_nobody_can_enter_the_marks(): void
    {
        $this->approveOne($this->subjectA, 'X');
        $this->approveOne($this->subjectB, 'Z');
        $this->assertNull($this->compilationRow($this->subjectA)['blocker']);

        DB::table('examination_drive_faculty_maps')->where('examination_drive_id', $this->drive->id)
            ->where('subject_master_pk', $this->subjectB)->delete();
        DB::table('examination_drive_component_maps')->where('examination_drive_id', $this->drive->id)
            ->where('subject_master_pk', $this->subjectB)->update(['faculty_master_pk' => null]);
        $this->assertSame('No faculty mapped to this subject', $this->compilationRow($this->subjectB)['blocker']);

        $this->drive->update(['cc_status' => ExaminationDrive::CC_PENDING]);
        $this->assertSame('Waiting for Course Coordinator approval', $this->compilationRow($this->subjectA)['blocker']);
    }

    public function test_save_validates_and_refuses_cells_that_are_not_yours(): void
    {
        $x = $this->key($this->subjectA, 'X');
        $y = $this->key($this->subjectA, 'Y');
        $first = $this->ots[0]['pk'];

        $this->save(1, [$x => [$first => '50.5']])->assertStatus(422)->assertJsonStructure(['errors' => ["{$x}|{$first}"]]);
        $this->save(1, [$x => [$first => '1.234']])->assertStatus(422);
        $this->save(1, [$x => [$first => 'abc']])->assertStatus(422);
        // F2 evaluates Y for the first 3 only; X and the 4th OT are not theirs.
        $this->save(2, [$x => [$first => '10']])->assertStatus(422);
        $this->save(2, [$y => [$this->ots[3]['pk'] => '10']])->assertStatus(422);
        // A subject that is not theirs on the course.
        $this->save(2, [], false, $this->subjectB)->assertStatus(403);

        $this->assertSame(0, EvaluationMark::where('examination_drive_id', $this->drive->id)->count());
    }

    public function test_draft_save_then_approve_locks_the_marks(): void
    {
        $x = $this->key($this->subjectA, 'X');
        $y = $this->key($this->subjectA, 'Y');
        [$first, $second] = [$this->ots[0]['pk'], $this->ots[1]['pk']];

        $this->save(1, [$x => [$first => '40', $second => 'ab']])->assertOk();
        $marks = EvaluationMark::where('examination_drive_id', $this->drive->id)->get()->keyBy('student_master_pk');
        $this->assertSame(40.0, $marks[$first]->marks);
        $this->assertTrue($marks[$second]->is_absent);
        $this->assertNull($marks[$second]->marks);
        $this->assertSame(EvaluationMark::STATUS_DRAFT, $marks[$first]->status);

        // Clearing a draft cell deletes it.
        $this->save(1, [$x => [$first => '']])->assertOk();
        $this->assertFalse(EvaluationMark::where('examination_drive_id', $this->drive->id)->where('student_master_pk', $first)->exists());

        // Approval needs every cell of the sheet filled, and writes nothing otherwise.
        $this->save(1, $this->fill($x, '30'), true)->assertStatus(422);
        $this->assertSame(0, EvaluationMark::where('examination_drive_id', $this->drive->id)->where('status', EvaluationMark::STATUS_APPROVED)->count());
        $this->assertSame(1, EvaluationMark::where('examination_drive_id', $this->drive->id)->count());

        $this->save(1, $this->fill($x, '30') + $this->fill($y, '20'), true)->assertOk();
        $this->assertSame(2 * count($this->ots), EvaluationMark::where('examination_drive_id', $this->drive->id)
            ->where('status', EvaluationMark::STATUS_APPROVED)->whereNotNull('approved_at')->count());

        // Approved marks are locked: a later save leaves them alone.
        $this->save(1, [$x => [$first => '1']])->assertOk();
        $this->assertSame(30.0, EvaluationMark::where('examination_drive_id', $this->drive->id)
            ->where('component_master_pk', $this->components['X'])->where('student_master_pk', $first)->value('marks') + 0.0);
    }

    public function test_compilation_counts_average_and_participant_lists(): void
    {
        $x = $this->key($this->subjectA, 'X');
        $y = $this->key($this->subjectA, 'Y');
        $n = count($this->ots);

        // No row until faculty approve something; a draft is not enough.
        $this->assertNull($this->compilationRow($this->subjectA));
        $this->save(1, [$x => [$this->ots[0]['pk'] => '10']])->assertOk();
        $this->assertNull($this->compilationRow($this->subjectA));

        // F2 approves Y for their 3: the row appears, nobody fully evaluated yet (X is a draft).
        $three = array_slice($this->ots, 0, 3);
        $this->save(2, $this->fill($y, '15', $three), true)->assertOk();
        $row = $this->compilationRow($this->subjectA);
        $this->assertSame([0, $n, 80.0, null], [$row['approved'], $row['pending'], $row['max'], $row['average']]);

        // Only approved marks are shown: the first OT's draft X (10) is blank and not in the total.
        $pending = $this->actingAs($this->admin)->get(route('admin.coe.result-compilation.participants', [
            'course' => $this->drive->course_master_pk, 'subject' => $this->subjectA, 'status' => 'pending',
        ]))->assertOk();
        $first = $pending->viewData('rows')[0];
        $this->assertSame([null, 15.0], [$first['marks'][$x], $first['subjects'][$this->subjectA]]);

        // F1 owns subject A, so approving needs Y for the other OTs too; F2's 3 are locked.
        $this->save(1, $this->fill($x, '25'), true)->assertStatus(422);
        $this->save(1, $this->fill($x, '25') + $this->fill($y, '5', array_slice($this->ots, 3)), true)->assertOk();
        $row = $this->compilationRow($this->subjectA);
        $this->assertSame([$n, 0], [$row['approved'], $row['pending']]);
        // First 3: 25 + 15 = 40 each; the rest: 25 + 5 = 30 each.
        $this->assertSame(round((3 * 40 + ($n - 3) * 30) / $n / 80 * 100, 1), $row['average']);

        // Subject B: nothing approved, so no row.
        $this->assertNull($this->compilationRow($this->subjectB));

        $evaluated = $this->actingAs($this->admin)->get(route('admin.coe.result-compilation.participants', [
            'course' => $this->drive->course_master_pk, 'subject' => $this->subjectA, 'status' => 'evaluated',
        ]))->assertOk();
        $this->assertCount($n, $evaluated->viewData('rows'));
        $this->assertSame(40.0, $evaluated->viewData('rows')[0]['subjects'][$this->subjectA]);
        $this->assertSame(30.0, $evaluated->viewData('rows')[3]['subjects'][$this->subjectA]);

        $pendingWholeCourse = $this->actingAs($this->admin)->get(route('admin.coe.result-compilation.participants', [
            'course' => $this->drive->course_master_pk, 'status' => 'pending',
        ]))->assertOk();
        // Subject B is not compiled yet, so with A complete nobody is pending.
        $this->assertCount(0, $pendingWholeCourse->viewData('rows'));
    }

    public function test_notify_for_pending_reaches_the_responsible_faculty(): void
    {
        $before = DB::table('notifications')->where('type', 'coe_result_entry')->count();

        $this->actingAs($this->admin)->postJson(route('admin.coe.result-compilation.notify'), [
            'course' => $this->drive->course_master_pk, 'subject' => $this->subjectB,
        ])->assertOk();

        $sent = DB::table('notifications')->where('type', 'coe_result_entry')->orderByDesc('pk')
            ->limit(DB::table('notifications')->where('type', 'coe_result_entry')->count() - $before)
            ->pluck('receiver_user_id')->map(fn ($id) => (int) $id)->all();
        $this->assertSame([(int) $this->faculty[3]->user_id], $sent, 'Only subject B\'s faculty');

        // Everything approved on subject B -> nothing pending to notify.
        $this->save(3, $this->fill($this->key($this->subjectB, 'Z'), '18'), true)->assertOk();
        $this->actingAs($this->admin)->postJson(route('admin.coe.result-compilation.notify'), [
            'course' => $this->drive->course_master_pk, 'subject' => $this->subjectB,
        ])->assertStatus(422);
    }

    public function test_sheet_lists_one_row_per_ot_and_component_split_into_pending_and_completed(): void
    {
        $y = $this->key($this->subjectA, 'Y');
        $three = array_slice($this->ots, 0, 3);
        $page = fn () => $this->actingAs($this->faculty[2])
            ->get(route('admin.coe.faculty-result-entry.index', ['course' => $this->drive->course_master_pk]))->assertOk();

        $entries = $page()->viewData('entries');
        $this->assertSame(array_column($three, 'code'), array_column($entries, 'code'));
        $this->assertSame([$y], array_values(array_unique(array_column($entries, 'key'))));
        $this->assertSame([3, 0], [$page()->viewData('pendingCount'), $page()->viewData('completedCount')]);

        $this->save(2, $this->fill($y, '15', $three), true)->assertOk();
        $this->assertSame([0, 3], [$page()->viewData('pendingCount'), $page()->viewData('completedCount')]);
        $this->actingAs($this->faculty[2])->get(route('admin.coe.faculty-result-entry.index', [
            'course' => $this->drive->course_master_pk, 'tab' => 'completed',
        ]))->assertOk()->assertSee('Completed: 3')->assertSee('Evaluation &amp; Marks Entry', false);
    }

    public function test_remarks_are_saved_only_once_the_column_exists(): void
    {
        $y = $this->key($this->subjectA, 'Y');
        $first = $this->ots[0]['pk'];

        $this->actingAs($this->faculty[2])->postJson(route('admin.coe.faculty-result-entry.save'), [
            'course' => $this->drive->course_master_pk,
            'marks' => [$y => [$first => '12']],
            'remarks' => [$y => [$first => 'Strong analytical response']],
        ])->assertOk();

        $mark = EvaluationMark::where('examination_drive_id', $this->drive->id)->where('student_master_pk', $first)->firstOrFail();
        $this->assertSame(12.0, $mark->marks);
        if (\App\Services\COE\ResultMarksService::remarksEnabled()) {
            $this->assertSame('Strong analytical response', $mark->remarks);
        } else {
            $this->assertNull($mark->remarks);
        }
    }

    public function test_template_is_the_pending_table_and_import_saves_drafts_all_or_nothing(): void
    {
        $course = ['course' => $this->drive->course_master_pk];
        $entries = $this->actingAs($this->faculty[2])
            ->get(route('admin.coe.faculty-result-entry.index', $course))->assertOk()->viewData('entries');
        [$a, $b, $c] = array_map(fn ($e) => [$e['code'], $e['course'], $e['subject'].' — '.$e['component']], $entries);
        $csv = fn (array $rows) => \Illuminate\Http\UploadedFile::fake()->createWithContent('marks.csv',
            "OT Unique Code,Course,Subject / Component,Attendance,Marks / 30\n"
            .implode("\n", array_map(fn ($r) => implode(',', array_map(fn ($v) => '"'.$v.'"', $r)), $rows))."\n");
        $import = fn (array $rows) => $this->actingAs($this->faculty[2])
            ->post(route('admin.coe.faculty-result-entry.import'), $course + ['file' => $csv($rows)], ['Accept' => 'application/json']);

        // The template is the table on screen: same columns, every pending row.
        $download = $this->actingAs($this->faculty[2])
            ->get(route('admin.coe.faculty-result-entry.template', $course))->assertOk()->assertDownload();
        $sheet = \Maatwebsite\Excel\Facades\Excel::toArray(new class {}, $download->baseResponse->getFile()->getPathname(), null, \Maatwebsite\Excel\Excel::XLSX)[0];
        $this->assertSame(['OT Unique Code', 'Course', 'Subject / Component', 'Attendance', 'Marks / 30'], array_slice($sheet[0], 0, 5));
        $this->assertSame(array_column($entries, 'code'), array_column(array_slice($sheet, 1), 0));

        // A row that is not on the sheet: the whole file is refused.
        $import([[...$a, 'Present', 20], [$this->ots[3]['code'], $a[1], $a[2], 'Present', 20]])
            ->assertStatus(422)->assertJsonStructure(['failures']);
        // Over the max: refused by the same rules as the sheet, reported by row.
        $import([[...$a, 'Present', 31]])->assertStatus(422)
            ->assertJsonFragment(['failures' => ['Row 2: Enter marks from 0 to 30 (up to 2 decimals), or AB for absent.']]);
        $this->assertSame(0, EvaluationMark::where('examination_drive_id', $this->drive->id)->count());

        $import([[...$a, 'Present', 20], [...$b, 'Absent', ''], [...$c, 'Present', '']])->assertOk();
        $marks = EvaluationMark::where('examination_drive_id', $this->drive->id)->get()->keyBy('student_master_pk');
        [$pa, $pb, $pc] = array_column($entries, 'student');
        $this->assertSame([20.0, true, false], [$marks[$pa]->marks, $marks[$pb]->is_absent, isset($marks[$pc])]);
        $this->assertSame(EvaluationMark::STATUS_DRAFT, $marks[$pa]->status);
    }

    public function test_merit_ranks_fully_evaluated_ots_with_shared_ranks_and_service_pass_marks(): void
    {
        $x = $this->key($this->subjectA, 'X');
        $y = $this->key($this->subjectA, 'Y');
        $z = $this->key($this->subjectB, 'Z');
        [$o0, $o1, $o2] = array_column(array_slice($this->ots, 0, 3), 'pk');
        // Pass needs Grand Total >= 20 on every OT's service.
        $services = DB::table('student_master')->whereIn('pk', array_column($this->ots, 'pk'))
            ->whereNotNull('service_master_pk')->distinct()->pluck('service_master_pk');
        foreach ($services as $service) {
            DB::table('service_passing_marks_master')->updateOrInsert(
                ['service_master_pk' => $service], ['passing_marks' => 20, 'active_inactive' => 1]);
        }
        $merit = fn () => $this->actingAs($this->admin)
            ->get(route('admin.coe.merit-ranking.index', ['course' => $this->drive->course_master_pk]))->assertOk();

        $this->save(1, [$x => [$o0 => '40', $o1 => '40'] + $this->fill($x, '10')[$x]] + $this->fill($y, '5'), true)->assertOk();
        // Subject B is not evaluated yet: nobody is ranked.
        $this->assertSame([], $merit()->viewData('rows'));
        $this->assertSame(count($this->ots), $merit()->viewData('pending'));

        $this->save(3, [$z => [$o2 => 'AB'] + $this->fill($z, '10')[$z]], true)->assertOk();
        $page = $merit();
        $rows = collect($page->viewData('rows'))->keyBy('student');
        $this->assertSame(0, $page->viewData('pending'));
        $this->assertCount(count($this->ots), $rows);

        // The Figma's six subject columns are always there; the two held carry their drive max.
        $subjects = $page->viewData('subjects');
        $this->assertSame(array_map(fn ($l) => 'f:'.$l, array_keys(\App\Services\COE\ResultMarksService::MERIT_SUBJECTS)), array_slice(array_keys($subjects), 0, 6));
        $this->assertSame(100.0, array_sum(array_column(array_filter($subjects, fn ($s) => $s['columns'] !== []), 'max')));
        $this->assertSame($page->viewData('maxA') + $page->viewData('maxB'), $page->viewData('max'));
        // No Director Assessment drive and no ICT / Hindi subject: '-' there, Figma max in the header.
        $this->assertSame(\App\Services\COE\ResultMarksService::DIRECTOR_MAX, $page->viewData('maxB'));
        $this->assertSame([55.0, null, ['ICT' => null, 'Hindi' => null]], [$rows[$o0]['total_a'], $rows[$o0]['director'], $rows[$o0]['qualifying']]);
        $this->assertCount(count($subjects), $rows[$o0]['subjects']);

        // 40 + 5 + 10 = 55 twice: both rank 1, the next is 3.
        $this->assertSame([1, 55.0, 'Pass'], [$rows[$o0]['rank'], $rows[$o0]['grand'], $rows[$o0]['result']]);
        $this->assertSame([1, 55.0, 'Pass'], [$rows[$o1]['rank'], $rows[$o1]['grand'], $rows[$o1]['result']]);
        // Absent on Z counts 0: 10 + 5 = 15, under the service's 20.
        $this->assertSame([15.0, 'Fail'], [$rows[$o2]['grand'], $rows[$o2]['result']]);
        if (count($this->ots) > 3) {
            $this->assertSame([3, 25.0, 'Pass'], [$rows[$this->ots[3]['pk']]['rank'], $rows[$this->ots[3]['pk']]['grand'], $rows[$this->ots[3]['pk']]['result']]);
        }
        $this->assertSame(count($this->ots), $rows[$o2]['rank']);
    }

    public function test_merit_splits_director_assessment_and_qualifying_papers_out_of_total_a(): void
    {
        $x = $this->key($this->subjectA, 'X');
        $y = $this->key($this->subjectA, 'Y');
        $z = $this->key($this->subjectB, 'Z');
        [$o0] = array_column($this->ots, 'pk');
        // Subject B is ICT: shown on its own, not added up.
        DB::table('subject_master')->where('pk', $this->subjectB)->update(['sub_short_name' => 'ICT']);

        $this->save(1, [$x => [$o0 => '40'] + $this->fill($x, '10')[$x]] + $this->fill($y, '5'), true)->assertOk();
        $this->save(3, $this->fill($z, '12'), true)->assertOk();

        $merit = fn () => $this->actingAs($this->admin)
            ->get(route('admin.coe.merit-ranking.index', ['course' => $this->drive->course_master_pk]))->assertOk();
        $row = collect($merit()->viewData('rows'))->keyBy('student')[$o0];
        $this->assertSame([45.0, null, 45.0, 12.0], [$row['total_a'], $row['director'], $row['grand'], $row['qualifying']['ICT']]);
        $this->assertSame(20.0, $merit()->viewData('qualifying')['ICT']);

        // The same drive as a Director Assessment: everything is B.
        $type = DB::table('examination_type_master')->insertGetId(['exam_type_name' => 'Director Assessment', 'active_inactive' => 1], 'pk');
        $this->drive->update(['examination_type_master_pk' => $type]);
        $page = $merit();
        $row = collect($page->viewData('rows'))->keyBy('student')[$o0];
        $this->assertSame([0.0, 57.0, 57.0], [$row['total_a'], $row['director'], $row['grand']]);
        $this->assertSame(100.0, $page->viewData('maxB'));
    }

    public function test_merit_all_courses_ranks_each_course_within_itself(): void
    {
        $x = $this->key($this->subjectA, 'X');
        $y = $this->key($this->subjectA, 'Y');
        $z = $this->key($this->subjectB, 'Z');
        $this->save(1, $this->fill($x, '10') + $this->fill($y, '5'), true)->assertOk();
        $this->save(3, $this->fill($z, '10'), true)->assertOk();

        $page = $this->actingAs($this->admin)->get(route('admin.coe.merit-ranking.index'))->assertOk()->assertSee('>All</option>', false);
        $this->assertNull($page->viewData('coursePk'));
        $mine = collect($page->viewData('rows'))->where('student', $this->ots[0]['pk'])->first();
        // Everyone on the test drive has 25: all share rank 1 within their course.
        $this->assertSame([1, 25.0], [$mine['rank'], $mine['grand']]);
    }

    public function test_faculty_without_the_permission_is_refused(): void
    {
        // Off every role (the Faculty role holds it too); rolled back with the test.
        DB::table('role_has_permissions')
            ->where('permission_id', Permission::findByName('coe_faculty_result_entry', 'web')->id)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($this->faculty[1]->fresh())->get(route('admin.coe.faculty-result-entry.index'))->assertForbidden();
    }
}
