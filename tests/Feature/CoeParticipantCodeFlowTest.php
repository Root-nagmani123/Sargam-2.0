<?php

namespace Tests\Feature;

use App\Models\COE\ParticipantCode;
use App\Models\User;
use App\Services\COE\CourseParticipants;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * COE → Unique Participant Code, one code per OT per subject: generate, regenerate,
 * per-subject independence, the in-subject clash check, freeze, delete, no hand edit,
 * the grid's columns and the By OT view. Development database, in a transaction.
 */
class CoeParticipantCodeFlowTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private int $course;

    private int $batch;

    private int $participants;

    private int $subject;

    private int $otherSubject;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::where('user_category', '!=', 'S')->first();
        // A course + batch of 5..2000 OTs on an active course (as Examination Drive offers them), without codes.
        $active = \App\Models\CourseMaster::activeRunning()->pluck('pk')->map(fn ($pk) => (int) $pk)->all();
        $cohort = CourseParticipants::cohorts()
            ->filter(fn ($c) => in_array($c->course_master_pk, $active, true) && $c->n >= 5 && $c->n <= 2000)
            ->first(fn ($c) => ! ParticipantCode::where('course_master_pk', $c->course_master_pk)->where('batch', $c->batch)->exists());
        $subjects = DB::table('subject_master')->where('active_inactive', 1)->orderBy('pk')->limit(2)->pluck('pk');

        if ($admin === null || $cohort === null || $subjects->count() < 2) {
            $this->markTestSkipped('Needs a non-OT user, an active course + batch of 5..2000 OTs without codes, and two active subjects.');
        }

        $this->admin = $admin;
        $this->course = $cohort->course_master_pk;
        $this->batch = $cohort->batch;
        $this->participants = $cohort->n;
        [$this->subject, $this->otherSubject] = $subjects->map(fn ($pk) => (int) $pk)->all();
    }

    private function generate(array $overrides = [])
    {
        return $this->actingAs($this->admin)->postJson(route('admin.coe.unique-participant-code.generate'), $overrides + [
            'course' => $this->course,
            'batch' => $this->batch,
            'subject' => $this->subject,
            'prefix' => 'ZZT-',
            'count_start' => '0324',
        ]);
    }

    private function codes(?int $subject = null)
    {
        return ParticipantCode::where('course_master_pk', $this->course)->where('batch', $this->batch)
            ->where('subject_master_pk', $subject ?? $this->subject)
            ->orderBy('unique_code')->get();
    }

    public function test_generate_gives_every_participant_one_code_in_name_order(): void
    {
        $this->generate()->assertOk()->assertJsonPath('written', $this->participants);

        $codes = $this->codes();
        $this->assertCount($this->participants, $codes);
        $this->assertSame($this->participants, $codes->pluck('student_master_pk')->unique()->count());
        $this->assertSame('ZZT-0324', $codes->first()->unique_code);
        $this->assertTrue($codes->every(fn ($c) => (int) $c->status === ParticipantCode::STATUS_PENDING));

        $first = DB::table('student_master')->whereIn('pk', CourseParticipants::of($this->course, $this->batch))
            ->orderBy('display_name')->orderBy('first_name')->orderBy('pk')->value('pk');
        $this->assertSame((int) $first, $codes->first()->student_master_pk);
        // Only that batch: OTs of the course's other batches get nothing.
        $this->assertSame($this->participants, ParticipantCode::where('course_master_pk', $this->course)->count());
    }

    public function test_generate_validates_input_and_writes_nothing(): void
    {
        $this->generate(['prefix' => 'U C#'])->assertStatus(422);
        $this->generate(['count_start' => '12a'])->assertStatus(422);
        $this->generate(['course' => ''])->assertStatus(422);
        $this->generate(['batch' => ''])->assertStatus(422);
        $inactiveCourse = DB::table('course_master')->whereNotIn('pk', \App\Models\CourseMaster::activeRunning()->select('pk'))->value('pk');
        $this->generate(['course' => $inactiveCourse])->assertStatus(422);
        $this->generate(['subject' => ''])->assertStatus(422);
        $inactive = DB::table('subject_master')->where('active_inactive', '!=', 1)->value('pk');
        $this->generate(['subject' => $inactive ?? 999999999])->assertStatus(422);

        $this->assertSame(0, ParticipantCode::where('course_master_pk', $this->course)->count());
    }

    public function test_each_subject_gets_its_own_codes(): void
    {
        $this->generate()->assertOk();
        // Same prefix and start in another subject: allowed, a code is read with its subject.
        $this->generate(['subject' => $this->otherSubject])->assertOk()->assertJsonPath('written', $this->participants);

        $this->assertCount($this->participants, $this->codes($this->otherSubject));
        $student = $this->codes()->first()->student_master_pk;
        $this->assertSame(2, ParticipantCode::where('student_master_pk', $student)
            ->where('course_master_pk', $this->course)->where('batch', $this->batch)->count());
    }

    public function test_generating_again_keeps_every_code_and_codes_only_new_ots(): void
    {
        $this->generate()->assertOk();
        $before = $this->codes()->pluck('unique_code', 'student_master_pk')->all();

        // Everyone has a code for the subject: nothing to do, nothing changes.
        $this->generate(['prefix' => 'ZZN-', 'count_start' => '1'])->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'already have a code for this subject'));
        $this->assertSame($before, $this->codes()->pluck('unique_code', 'student_master_pk')->all());

        // One code frozen, two OTs without a code (deleted, as if added to the course later).
        $codes = $this->codes();
        $codes[0]->update(['status' => ParticipantCode::STATUS_FROZEN]);
        $gone = $codes->slice(1, 2)->pluck('student_master_pk')->all();
        ParticipantCode::whereIn('pk', $codes->slice(1, 2)->pluck('pk'))->delete();

        $this->generate()->assertOk()->assertJsonPath('written', 2)
            ->assertJsonPath('message', fn ($m) => str_contains($m, ($this->participants - 2).' OT(s) already had a code for this subject and kept it'));
        $after = $this->codes()->pluck('unique_code', 'student_master_pk')->all();
        $this->assertCount($this->participants, $after);
        // Everyone else's code is untouched; the two new ones carry on after the highest.
        $this->assertSame(array_diff_key($before, array_flip($gone)), array_diff_key($after, array_flip($gone)));
        $last = 324 + $this->participants - 1;
        $this->assertEqualsCanonicalizing(
            ['ZZT-'.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT), 'ZZT-'.str_pad((string) ($last + 2), 4, '0', STR_PAD_LEFT)],
            array_values(array_intersect_key($after, array_flip($gone)))
        );
    }

    public function test_numbering_continues_past_a_code_already_used_in_the_subject(): void
    {
        // An OT no longer on the course still holds ZZT-0325 in this subject.
        $outsider = DB::table('student_master')->whereNotIn('pk', CourseParticipants::of($this->course, $this->batch))->value('pk');
        ParticipantCode::insert([
            'student_master_pk' => $outsider, 'course_master_pk' => $this->course, 'subject_master_pk' => $this->subject,
            'batch' => $this->batch, 'unique_code' => 'ZZT-0325', 'status' => ParticipantCode::STATUS_PENDING,
        ]);

        // No clash: the OTs are numbered on from the subject's highest code.
        $this->generate()->assertOk()->assertJsonPath('written', $this->participants)
            ->assertJsonPath('message', fn ($m) => str_starts_with($m, $this->participants.' code(s) generated, ZZT-0326 to'));
        $this->assertSame($outsider, (int) ParticipantCode::where('course_master_pk', $this->course)->where('batch', $this->batch)
            ->where('subject_master_pk', $this->subject)->where('unique_code', 'ZZT-0325')->value('student_master_pk'));
    }

    public function test_freeze_and_delete_and_no_hand_edit(): void
    {
        $this->generate()->assertOk();
        [$a, $b, $c] = $this->codes()->take(3)->all();

        // No hand edit: it broke the sequence. The route is gone.
        $this->actingAs($this->admin)->putJson('/admin/coe/unique-participant-code/'.$a->pk, ['unique_code' => 'ZZE-1'])
            ->assertNotFound();
        $this->assertSame($a->unique_code, $a->fresh()->unique_code);

        // Freeze, idempotently.
        $this->actingAs($this->admin)->postJson(route('admin.coe.unique-participant-code.freeze'), ['ids' => [$a->pk, $b->pk]])
            ->assertOk()->assertJsonPath('frozen', 2);
        $this->actingAs($this->admin)->postJson(route('admin.coe.unique-participant-code.freeze'), ['ids' => [$a->pk]])
            ->assertOk()->assertJsonPath('frozen', 0);

        // Frozen: no delete.
        $this->actingAs($this->admin)->postJson(route('admin.coe.unique-participant-code.bulk-destroy'), ['ids' => [$a->pk]])
            ->assertStatus(422);

        // Mixed delete: the pending one goes, the frozen one stays.
        $this->actingAs($this->admin)->postJson(route('admin.coe.unique-participant-code.bulk-destroy'), ['ids' => [$b->pk, $c->pk]])
            ->assertOk()->assertJsonPath('deleted_ids', [$c->pk]);
        $this->assertNotNull(ParticipantCode::find($b->pk));
        $this->assertNull(ParticipantCode::find($c->pk));

        // The deleted OT gets the next number in the sequence back by Generate.
        $this->generate()->assertOk()->assertJsonPath('written', 1);
    }

    public function test_ot_list_and_one_ots_codes_screen(): void
    {
        $this->generate()->assertOk();
        $this->generate(['subject' => $this->otherSubject])->assertOk();

        $code = $this->codes()->first();
        $page = $this->actingAs($this->admin)->get(route('admin.coe.unique-participant-code.index'))->assertOk();

        // The list: one row per OT, with their code count per course.
        $ot = collect($page->viewData('otRows'))->firstWhere('student_pk', $code->student_master_pk);
        $this->assertCount(2, $ot['courses'][(string) $this->course]);
        $countUrl = route('admin.coe.unique-participant-code.ot', ['student' => $code->student_master_pk, 'course' => $this->course]);
        $page->assertSee('<th scope="col">User Name</th>', false)
             ->assertSee(e($countUrl), false)
             ->assertSee('id="upcDeleteAllBtn"', false)
             ->assertSee('id="upcFreezeAllBtn"', false)
             ->assertDontSee('<th scope="col">Subject Name</th>', false);
        // A ticked OT row carries all their code ids; Freeze Selected freezes every one.
        $this->assertCount(2, $ot['ids']);
        $this->actingAs($this->admin)->postJson(route('admin.coe.unique-participant-code.freeze'), ['ids' => $ot['ids']])
            ->assertOk()->assertJsonPath('frozen', 2);
        $this->assertSame(2, ParticipantCode::where('student_master_pk', $code->student_master_pk)
            ->where('course_master_pk', $this->course)->where('status', ParticipantCode::STATUS_FROZEN)->count());

        // Generate offers every active subject, and exactly Examination Drive's active courses, once each.
        $this->assertSame(DB::table('subject_master')->where('active_inactive', 1)->count(), $page->viewData('activeSubjects')->count());
        $this->assertEqualsCanonicalizing(
            \App\Models\CourseMaster::activeRunning()->whereNotNull('course_name')->where('course_name', '!=', '')->pluck('pk')->map(fn ($pk) => (string) $pk)->all(),
            collect($page->viewData('cohorts'))->pluck('key')->all());
        $batches = collect(collect($page->viewData('cohorts'))->firstWhere('key', (string) $this->course)['batches']);
        $this->assertSame($this->participants, $batches->firstWhere('batch', $this->batch)['count']);

        // A count opens that OT's codes, preset to the course.
        $screen = $this->actingAs($this->admin)->get($countUrl)->assertOk();
        $rows = collect($screen->viewData('rows'));
        $this->assertCount(2, $rows);
        $this->assertTrue($rows->every(fn ($r) => $r['student_pk'] === $code->student_master_pk && $r['course_pk'] === $this->course));
        $this->assertContains($code->unique_code, $rows->pluck('unique_code')->all());
        $this->assertSame($rows->first()['course_name'], $screen->viewData('presetCourse'));
        $screen->assertSee('<th scope="col">Subject Name</th>', false)
               ->assertSee('<th scope="col">Unique Code</th>', false)
               ->assertSee('id="upcFreezeAllBtn"', false)
               ->assertDontSee('coe-act--edit', false);

        $this->actingAs($this->admin)->get(route('admin.coe.unique-participant-code.ot', ['student' => 999999999]))->assertNotFound();
    }

    public function test_mapping_page_filters_by_subject(): void
    {
        $this->generate()->assertOk();
        $this->generate(['subject' => $this->otherSubject, 'prefix' => 'ZZO-'])->assertOk();
        $name = fn (int $pk) => \App\Services\COE\CourseParticipants::plainName(DB::table('subject_master')->where('pk', $pk)->value('subject_name'));

        // The mapping is COE-only (coe_roll_code_mapping); Super Admin always passes.
        $coe = User::role('Super Admin')->first() ?? $this->markTestSkipped('Needs a Super Admin.');
        $active = \App\Models\CourseMaster::activeRunning()->where('pk', $this->course)->exists();
        $page = $this->actingAs($coe)
            ->get(route('admin.coe.roll-code-mapping.index', $active ? [] : ['scope' => 'archived']))->assertOk()
            ->assertSee('id="rcmFilterSubject"', false);
        $subjects = $page->viewData('subjects')->all();
        $this->assertContains($name($this->subject), $subjects);
        $this->assertContains($name($this->otherSubject), $subjects);
        $this->assertNotContains('—', $subjects);

        // The export follows the Subject filter: only that subject's codes.
        $response = $this->actingAs($coe)->get(route('admin.coe.roll-code-mapping.export',
            array_filter(['format' => 'csv', 'subject' => $name($this->otherSubject), 'scope' => $active ? null : 'archived'])))->assertOk();
        $csv = $response->baseResponse instanceof \Symfony\Component\HttpFoundation\StreamedResponse ? $response->streamedContent() : $response->getContent();
        $this->assertStringContainsString('ZZO-0324', $csv);
        $this->assertStringNotContainsString('ZZT-0324', $csv);
    }
}
