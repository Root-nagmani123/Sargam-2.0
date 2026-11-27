<?php

namespace Tests\Feature;

use App\Models\COE\EvaluationMark;
use App\Models\ExaminationDrive;
use App\Models\User;
use App\Services\COE\ResultMarksService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Merit & Ranking on a drive with several subjects, one of them with several components
 * (tester, 27 Nov 2026: "no data when a drive has multiple subjects / a subject has
 * multiple components; it shows for one subject without sub-components").
 *
 * Fixture: one Active, CC-approved drive on a course with roll numbers, per-subject
 * Unique Participant Codes, subjects A (components X, Y) and B (component Z). Marks are
 * entered and approved on Result Entry and moderated on Marks Moderation, as a user would.
 */
class CoeMeritMultiSubjectFlowTest extends TestCase
{
    use DatabaseTransactions;

    private ExaminationDrive $drive;

    private User $admin;

    /** @var array<string, int> */
    private array $subjects = [];

    /** @var array<string, int> */
    private array $components = [];

    /** @var int[] */
    private array $ots = [];

    protected function setUp(): void
    {
        parent::setUp();

        $cohort = DB::table('coe_roll_number_allocations as a')
            ->join('coe_roll_numbers as r', 'r.roll_number_allocation_pk', '=', 'a.pk')
            ->groupBy('a.pk', 'a.course_master_pk', 'a.batch')->havingRaw('count(*) >= 3')
            ->first(['a.pk', 'a.course_master_pk', 'a.batch']);
        $admin = User::role('Super Admin')->first();
        $subjects = DB::table('subject_master')->orderBy('pk')->limit(2)->pluck('pk');
        $term = DB::table('term_master')->value('pk');
        $type = DB::table('examination_type_master')->where('exam_type_name', 'not like', '%director%')->value('pk');
        if (! $cohort || ! $admin || $subjects->count() < 2 || ! $term || ! $type) {
            $this->markTestSkipped('Needs a course with 3+ roll numbers, a Super Admin, 2 subjects, a term and a non-Director exam type.');
        }
        $this->admin = $admin;
        [$this->subjects['A'], $this->subjects['B']] = [(int) $subjects[0], (int) $subjects[1]];

        // Only this drive on the course, so other drives' marks do not decide the outcome.
        ExaminationDrive::where('course_master_pk', $cohort->course_master_pk)->update(['status' => ExaminationDrive::STATUS_CLOSED]);

        foreach (['X', 'Y', 'Z'] as $c) {
            $this->components[$c] = DB::table('component_master')->insertGetId(['component_name' => 'ZZ Merit '.$c.' '.uniqid(), 'active_inactive' => 1], 'pk');
        }
        $this->drive = ExaminationDrive::create([
            'drive_name' => 'ZZ Merit Multi Subject',
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
        $map = fn (string $s, string $c, float $max) => [
            'examination_drive_id' => $this->drive->id, 'subject_master_pk' => $this->subjects[$s],
            'component_master_pk' => $this->components[$c], 'max_marks' => $max, 'passing_marks' => 0,
            'source' => 'Sargam 2.0', 'weightage' => 30,
            'exam_date' => now()->subDays(3)->toDateString(), 'exam_end_date' => now()->subDays(2)->toDateString(),
        ];
        DB::table('examination_drive_component_maps')->insert([$map('A', 'X', 50), $map('A', 'Y', 30), $map('B', 'Z', 20)]);

        $this->ots = DB::table('coe_roll_numbers')->where('roll_number_allocation_pk', $cohort->pk)
            ->pluck('student_master_pk')->map(fn ($pk) => (int) $pk)->all();

        // Per-subject Unique Participant Codes, as the Unique Participant Code screen makes them now.
        DB::table('coe_participant_codes')->where('course_master_pk', $cohort->course_master_pk)->delete();
        $rows = [];
        foreach (['A' => 'ZA', 'B' => 'ZB'] as $s => $prefix) {
            foreach ($this->ots as $i => $pk) {
                $rows[] = ['student_master_pk' => $pk, 'course_master_pk' => $cohort->course_master_pk, 'subject_master_pk' => $this->subjects[$s],
                    'batch' => $cohort->batch, 'unique_code' => $prefix.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT), 'status' => 1];
            }
        }
        DB::table('coe_participant_codes')->insert($rows);
    }

    private function key(string $s, string $c): string
    {
        return $this->drive->id.':'.$this->subjects[$s].':'.$this->components[$c];
    }

    public function test_every_ot_is_ranked_once_all_subjects_and_components_are_approved_and_moderated(): void
    {
        $fill = fn (string $s, string $c, string $v) => [$this->key($s, $c) => array_fill_keys($this->ots, $v)];

        // Result Entry: the whole course sheet filled and approved (Super Admin enters all).
        $this->actingAs($this->admin)->postJson(route('admin.coe.faculty-result-entry.save'), [
            'course' => $this->drive->course_master_pk,
            'marks' => $fill('A', 'X', '40') + $fill('A', 'Y', '20') + $fill('B', 'Z', '15'),
            'approve' => true,
        ])->assertOk();
        $this->assertSame(3 * count($this->ots), EvaluationMark::where('examination_drive_id', $this->drive->id)->where('status', 2)->count());

        $results = app(ResultMarksService::class);

        // Not moderated yet: nobody ranked, and the page says which components hold each OT back.
        $before = $results->merit('active', (int) $this->drive->course_master_pk);
        $this->assertSame([], $before['rows']);
        $this->assertCount(count($this->ots), $before['blocked']);
        $this->assertSame(['not moderated'], array_values(array_unique(array_column($before['blocked'][0]['missing'], 'reason'))));
        $this->assertCount(3, $before['blocked'][0]['missing']);
        $this->actingAs($this->admin)->get(route('admin.coe.merit-ranking.index', ['course' => $this->drive->course_master_pk]))
            ->assertOk()->assertSee('Why not ranked: 3 component(s) still need a final mark')
            ->assertSee('Approved, not moderated (Marks Moderation)');

        // Marks Moderation: moderate every row of the drive.
        $rows = collect($results->moderationSheet('active'))->filter(fn ($r) => str_starts_with($r['key'], $this->drive->id.':'));
        $this->assertSame(3 * count($this->ots), $rows->count(), 'every OT × component is on Marks Moderation');
        $this->assertSame([], $rows->where('status', '!=', 'not-moderated')->pluck('status', 'key')->all());
        $done = $results->moderate($this->admin, $rows->pluck('key')->all());
        $this->assertNull($done['error']);
        $this->assertSame(3 * count($this->ots), EvaluationMark::where('examination_drive_id', $this->drive->id)->whereNotNull('moderated_marks')->count());

        // Merit & Ranking: everyone ranked.
        $merit = $results->merit('active', (int) $this->drive->course_master_pk);
        $this->assertSame(0, $merit['pending'], 'nobody left unranked');
        $this->assertCount(count($this->ots), $merit['rows']);
        $this->assertSame(75.0, $merit['rows'][0]['grand']);
    }
}
