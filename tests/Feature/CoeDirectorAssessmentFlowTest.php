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
 * COE → Evaluation & Marks: the Director Assessment, end to end. Faculty approve
 * their component → the Training Section reviews the OT and enters the Director's
 * overall marks and remarks → Send to COE locks them → they count as part B of
 * Merit & Ranking. Development database, in a transaction.
 *
 * Fixture: one Active, CC-approved "Director Assessment" drive on a course with
 * Unique Participant Codes; one subject with
 *   D  "Discipline"         max 50, faculty F (subject owner)
 *   O  "Director's Overall" max 50, the Training Section's
 */
class CoeDirectorAssessmentFlowTest extends TestCase
{
    use DatabaseTransactions;

    private ExaminationDrive $drive;

    private User $admin;

    private User $faculty;

    private User $ti;

    private int $subject;

    /** @var array<string, int> component pks: D, O */
    private array $components = [];

    /** @var list<array{pk:int, code:string}> */
    private array $ots = [];

    protected function setUp(): void
    {
        parent::setUp();

        $cohort = DB::table('coe_participant_codes')
            ->groupBy('course_master_pk', 'batch')->havingRaw('count(*) >= 3')
            ->first(['course_master_pk', 'batch']);
        $faculty = DB::table('faculty_master as f')
            ->join('user_credentials as u', 'u.user_id', '=', 'f.employee_master_pk')
            ->first(['f.pk', 'u.pk as user_pk']);
        $admin = User::role('Super Admin')->first();
        // The Training Section user: any login that is not a faculty row and not the admin.
        $ti = User::whereNotIn('user_id', DB::table('faculty_master')->whereNotNull('employee_master_pk')->select('employee_master_pk'))
            ->when($admin, fn ($q) => $q->where('pk', '<>', $admin->pk))
            ->where('user_category', '<>', 'S')->first();
        $subject = DB::table('subject_master')->orderBy('pk')->value('pk');
        $term = DB::table('term_master')->value('pk');

        if (! $cohort || ! $faculty || ! $admin || ! $ti || ! $subject || ! $term) {
            $this->markTestSkipped('Needs a course with 3+ participant codes, a faculty with a login, a Super Admin, another staff login, a subject and a term.');
        }

        $this->admin = $admin;
        $this->subject = (int) $subject;

        $make = fn (string $permission) => Role::create(['name' => 'ZZ DA Test '.uniqid(), 'guard_name' => 'web'])
            ->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        $this->faculty = User::findOrFail($faculty->user_pk)->assignRole($make('coe_faculty_result_entry'));
        $this->ti = $ti->assignRole($make('coe_evaluation_marks'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $type = DB::table('examination_type_master')->insertGetId(['exam_type_name' => 'ZZ Director Assessment '.uniqid(), 'active_inactive' => 1], 'pk');
        $this->components['D'] = DB::table('component_master')->insertGetId(['component_name' => 'ZZ DA Discipline '.uniqid(), 'active_inactive' => 1], 'pk');
        $this->components['O'] = DB::table('component_master')->insertGetId(['component_name' => "ZZ Director's Overall ".uniqid(), 'active_inactive' => 1], 'pk');

        $this->drive = ExaminationDrive::create([
            'drive_name' => 'ZZ Director Assessment Test',
            'examination_type_master_pk' => $type,
            'term_master_pk' => $term,
            'course_master_pk' => $cohort->course_master_pk,
            'phase' => (string) $cohort->batch,
            'academic_session' => $cohort->batch,
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

        DB::table('examination_drive_faculty_maps')->insert([
            'examination_drive_id' => $this->drive->id, 'subject_master_pk' => $this->subject, 'faculty_master_pk' => $faculty->pk,
        ]);
        $map = fn (string $c, string $source, ?int $facultyPk) => [
            'examination_drive_id' => $this->drive->id, 'subject_master_pk' => $this->subject,
            'component_master_pk' => $this->components[$c], 'faculty_master_pk' => $facultyPk,
            'max_marks' => 50, 'passing_marks' => 0, 'source' => $source, 'weightage' => 50,
        ];
        DB::table('examination_drive_component_maps')->insert([$map('D', 'Faculty', $faculty->pk), $map('O', 'Sargam 2.0', null)]);
    }

    private function key(string $c): string
    {
        return $this->drive->id.':'.$this->subject.':'.$this->components[$c];
    }

    private function sheet(array $query = [])
    {
        return $this->actingAs($this->ti)
            ->get(route('admin.coe.evaluation-marks.index', ['course' => $this->drive->course_master_pk, 'drive' => $this->drive->id] + $query))
            ->assertOk();
    }

    private function saveAs(User $user, array $body)
    {
        return $this->actingAs($user)->postJson(route('admin.coe.evaluation-marks.save'), $body);
    }

    /** One OT's Director cell: [key => [student => value]]. */
    private function cell(int $ot, string $value): array
    {
        return [$this->key('O') => [$this->ots[$ot]['pk'] => $value]];
    }

    /** The faculty enter and approve Discipline for every OT. */
    private function facultyApproves(string $value = '40'): void
    {
        $this->actingAs($this->faculty)->postJson(route('admin.coe.faculty-result-entry.save'), [
            'course' => $this->drive->course_master_pk,
            'marks' => [$this->key('D') => collect($this->ots)->mapWithKeys(fn ($o) => [$o['pk'] => $value])->all()],
            'approve' => true,
        ])->assertOk();
    }

    private function mark(int $ot): ?EvaluationMark
    {
        return EvaluationMark::where('examination_drive_id', $this->drive->id)
            ->where('component_master_pk', $this->components['O'])
            ->where('student_master_pk', $this->ots[$ot]['pk'])->first();
    }

    public function test_the_director_component_is_the_training_sections_not_the_facultys(): void
    {
        // Faculty Result Entry offers only Discipline.
        $entry = $this->actingAs($this->faculty)
            ->get(route('admin.coe.faculty-result-entry.index', ['course' => $this->drive->course_master_pk]))->assertOk();
        $this->assertSame([$this->key('D')], array_column($entry->viewData('columns'), 'key'));

        // Evaluation & Marks: one row per OT on the Director component, Discipline beside it.
        $page = $this->sheet();
        $rows = $page->viewData('rows');
        $this->assertSame(array_column($this->ots, 'pk'), array_column($rows, 'student'));
        $this->assertSame([$this->key('O')], array_values(array_unique(array_column($rows, 'key'))));
        $this->assertCount(1, $rows[0]['faculty']);
        $this->assertFalse($rows[0]['eligible']);
        $this->assertSame(count($this->ots), $page->viewData('pendingCount'));
        $page->assertSee('Director assessment · Reviewer')->assertSee('Review profile');
    }

    public function test_draft_then_send_to_coe_needs_marks_remarks_and_the_faculty_marks(): void
    {
        $cell = $this->key('O').'|'.$this->ots[0]['pk'];

        // Quick entry saves a draft.
        $this->saveAs($this->ti, ['marks' => $this->cell(0, '42.5')])->assertOk()->assertJson(['saved' => 1]);
        $this->assertSame([42.5, EvaluationMark::STATUS_DRAFT], [$this->mark(0)->marks, $this->mark(0)->status]);
        $this->assertNull($this->mark(0)->evaluated_by_faculty_pk);

        // Out of range / not a number: nothing saved.
        $this->saveAs($this->ti, ['marks' => $this->cell(0, '51')])->assertStatus(422)->assertJsonPath('errors.'.$cell, fn ($m) => str_contains($m, '0 to 50'));
        $this->saveAs($this->ti, ['marks' => $this->cell(0, '4.555')])->assertStatus(422);
        $this->assertSame(42.5, $this->mark(0)->fresh()->marks);

        // Faculty marks not received yet: Send to COE refused.
        $this->saveAs($this->ti, ['remarks' => $this->cell(0, 'Consistent and well prepared.'), 'send' => [$cell]])
            ->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'faculty marks'));
        $this->assertNull($this->mark(0)->remarks, 'all or nothing: the remarks were not saved either');

        $this->facultyApproves();

        // Remarks are required to send.
        $this->saveAs($this->ti, ['send' => [$cell]])->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'remarks'));
        $this->assertSame(EvaluationMark::STATUS_DRAFT, $this->mark(0)->status);

        $this->saveAs($this->ti, ['remarks' => $this->cell(0, 'Consistent and well prepared.'), 'send' => [$cell]])
            ->assertOk()->assertJson(['sent' => 1]);
        $m = $this->mark(0);
        $this->assertSame([EvaluationMark::STATUS_APPROVED, 'Consistent and well prepared.', (int) $this->ti->user_id],
            [$m->status, $m->remarks, (int) $m->approved_by]);

        // Locked once sent.
        $this->saveAs($this->ti, ['marks' => $this->cell(0, '10')])->assertStatus(422);
        $this->assertSame(42.5, $this->mark(0)->fresh()->marks);

        $page = $this->sheet(['tab' => 'completed']);
        $this->assertSame(1, $page->viewData('completedCount'));
        $page->assertSee('Sent to COE');
    }

    public function test_approve_sends_only_complete_rows_all_or_nothing(): void
    {
        $this->facultyApproves();
        $cells = fn (array $i) => array_map(fn ($n) => $this->key('O').'|'.$this->ots[$n]['pk'], $i);
        $this->saveAs($this->ti, ['marks' => $this->cell(0, '30') + [], 'remarks' => $this->cell(0, 'Good.')])->assertOk();
        $this->saveAs($this->ti, ['marks' => $this->cell(1, '35')])->assertOk();

        // OT 1 has no remarks: neither is sent.
        $this->saveAs($this->ti, ['send' => $cells([0, 1])])->assertStatus(422)
            ->assertJsonStructure(['errors' => [$cells([1])[0]]]);
        $this->assertSame(EvaluationMark::STATUS_DRAFT, $this->mark(0)->status);

        $this->saveAs($this->ti, ['remarks' => $this->cell(1, 'Steady.'), 'send' => $cells([0, 1])])->assertOk()->assertJson(['sent' => 2]);
    }

    public function test_sent_director_marks_count_as_part_b_of_merit(): void
    {
        $this->facultyApproves('40');
        foreach (array_keys($this->ots) as $i) {
            $this->saveAs($this->ti, [
                'marks' => $this->cell($i, '45'), 'remarks' => $this->cell($i, 'Fine.'),
                'send' => [$this->key('O').'|'.$this->ots[$i]['pk']],
            ])->assertOk();
        }
        // Marks Moderation: the Director component's evaluator is the Training Section.
        $moderation = collect(app(\App\Services\COE\ResultMarksService::class)->moderationSheet('active'))
            ->firstWhere('key', $this->key('O').'|'.$this->ots[0]['pk']);
        $this->assertSame('Training Section', $moderation['evaluator']);

        DB::table('coe_evaluation_marks')->where('examination_drive_id', $this->drive->id)
            ->update(['moderated_marks' => DB::raw('marks'), 'moderated_by' => $this->admin->user_id, 'moderated_at' => now()]);

        $rows = collect($this->actingAs($this->admin)
            ->get(route('admin.coe.merit-ranking.index', ['course' => $this->drive->course_master_pk]))->assertOk()
            ->viewData('rows'))->keyBy('student');
        $this->assertSame([85.0, 85.0], [$rows[$this->ots[0]['pk']]['director'], $rows[$this->ots[0]['pk']]['grand']]);
    }

    public function test_ot_profile_shows_the_review_with_previous_and_next(): void
    {
        $url = fn (int $i) => route('admin.coe.evaluation-marks.review', [
            'key' => $this->key('O'), 'student' => $this->ots[$i]['pk'],
            'course' => $this->drive->course_master_pk, 'drive' => $this->drive->id,
        ]);
        $page = $this->actingAs($this->ti)->get($url(1))->assertOk()
            ->assertSee('Director’s overall assessment', false)->assertSee('Notices &amp; memos', false)
            ->assertSee('Faculty marks pending')->assertSee('Send to COE');
        $this->assertSame($this->ots[0]['pk'], $page->viewData('prev')['student']);
        $this->assertSame($this->ots[2]['pk'], $page->viewData('next')['student']);
        $this->assertSame(2, $page->viewData('position'));

        // Sent to COE, it leaves Pending, yet reopened from there Previous / Next still move on.
        $this->facultyApproves();
        $this->saveAs($this->ti, ['marks' => $this->cell(1, '30'), 'remarks' => $this->cell(1, 'Fine.'),
            'send' => [$this->key('O').'|'.$this->ots[1]['pk']]])->assertOk();
        $page = $this->actingAs($this->ti)->get($url(1))->assertOk()->assertSee('Sent to COE');
        $this->assertSame([$this->ots[0]['pk'], $this->ots[2]['pk']],
            [$page->viewData('prev')['student'], $page->viewData('next')['student']]);

        $this->actingAs($this->ti)->get(route('admin.coe.evaluation-marks.review', ['key' => '1:2:3', 'student' => 1]))->assertNotFound();
        $this->actingAs($this->ti)->get(route('admin.coe.evaluation-marks.review', ['key' => $this->key('D'), 'student' => $this->ots[0]['pk']]))->assertNotFound();
    }

    public function test_only_holders_of_the_permission_may_open_it(): void
    {
        $this->actingAs($this->faculty)->get(route('admin.coe.evaluation-marks.index'))->assertForbidden();
        $this->saveAs($this->faculty, ['marks' => $this->cell(0, '10')])->assertForbidden();
        $this->assertNull($this->mark(0));

        // A faculty column cannot be written through this screen.
        $this->saveAs($this->ti, ['marks' => [$this->key('D') => [$this->ots[0]['pk'] => '10']]])->assertStatus(422);
    }
}
