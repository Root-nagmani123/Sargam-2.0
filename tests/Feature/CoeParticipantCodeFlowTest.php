<?php

namespace Tests\Feature;

use App\Models\COE\ParticipantCode;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * COE → Unique Participant Code: generate, regenerate, edit, freeze, delete, the freeze
 * lock, cross-course uniqueness, and the grid's column mapping (Course, OT name from
 * student_master, DT name from the OT's login). Development database, in a transaction.
 */
class CoeParticipantCodeFlowTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private int $course;

    private int $batch;

    private int $participants;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::where('user_category', '!=', 'S')->first();
        $cohort = DB::table('student_master as sm')
            ->join('course_master as cm', 'cm.pk', '=', 'sm.course_master_pk')
            ->leftJoin('coe_participant_codes as pc', 'pc.student_master_pk', '=', 'sm.pk')
            ->whereNull('pc.pk')
            ->where('sm.batch', '>', 0)
            ->where('cm.course_name', '!=', '')
            ->groupBy('sm.course_master_pk', 'sm.batch')
            ->havingRaw('count(*) between 5 and 2000')
            ->orderByDesc(DB::raw('count(*)'))
            ->first(['sm.course_master_pk', 'sm.batch', DB::raw('count(*) as n')]);

        if ($admin === null || $cohort === null) {
            $this->markTestSkipped('No non-OT user, or no course + batch without codes.');
        }

        $this->admin = $admin;
        $this->course = (int) $cohort->course_master_pk;
        $this->batch = (int) $cohort->batch;
        $this->participants = (int) $cohort->n;
    }

    private function generate(array $overrides = [])
    {
        return $this->actingAs($this->admin)->postJson(route('admin.coe.unique-participant-code.generate'), $overrides + [
            'course_batch' => $this->course . ':' . $this->batch,
            'prefix' => 'ZZT-',
            'count_start' => '0324',
        ]);
    }

    private function codes()
    {
        return ParticipantCode::where('course_master_pk', $this->course)->where('batch', $this->batch)
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

        $first = DB::table('student_master')->where('course_master_pk', $this->course)->where('batch', $this->batch)
            ->orderBy('display_name')->orderBy('first_name')->orderBy('pk')->value('pk');
        $this->assertSame((int) $first, $codes->first()->student_master_pk);
    }

    public function test_generate_validates_input_and_writes_nothing(): void
    {
        $this->generate(['prefix' => 'U C#'])->assertStatus(422);
        $this->generate(['count_start' => '12a'])->assertStatus(422);
        $this->generate(['course_batch' => ''])->assertStatus(422);

        $this->assertCount(0, $this->codes());
    }

    public function test_generating_again_keeps_every_code_and_codes_only_new_ots(): void
    {
        $this->generate()->assertOk();
        $before = $this->codes()->pluck('unique_code', 'student_master_pk')->all();

        // Everyone has a code: nothing to do, nothing changes.
        $this->generate(['prefix' => 'ZZN-', 'count_start' => '1'])->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'already have a Unique Participant Code'));
        $this->assertSame($before, $this->codes()->pluck('unique_code', 'student_master_pk')->all());

        // One code frozen, two OTs without a code (one deleted, as if added to the course later).
        $codes = $this->codes();
        $codes[0]->update(['status' => ParticipantCode::STATUS_FROZEN]);
        $gone = $codes->slice(1, 2)->pluck('student_master_pk')->all();
        ParticipantCode::whereIn('student_master_pk', $gone)->delete();

        $this->generate()->assertOk()->assertJsonPath('written', 2)
            ->assertJsonPath('message', fn ($m) => str_contains($m, ($this->participants - 2).' OT(s) already had a code and kept it'));
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

    public function test_a_code_owned_by_another_course_is_refused(): void
    {
        $other = DB::table('student_master')
            ->where(fn ($q) => $q->where('course_master_pk', '!=', $this->course)->orWhere('batch', '!=', $this->batch))
            ->whereNotNull('course_master_pk')
            ->first(['pk', 'course_master_pk', 'batch']);
        if ($other === null) {
            $this->markTestSkipped('Needs a participant outside the cohort.');
        }
        ParticipantCode::insert([
            'student_master_pk' => $other->pk,
            'course_master_pk' => $other->course_master_pk,
            'batch' => (int) $other->batch,
            'unique_code' => 'ZZT-0325',
            'status' => ParticipantCode::STATUS_PENDING,
        ]);

        $this->generate()->assertStatus(422)->assertJsonFragment(['message' =>
            'Code ZZT-0325 already belongs to a participant of another course. Choose a different prefix or start number.']);
        $this->assertCount(0, $this->codes());
    }

    public function test_freeze_and_delete_and_no_hand_edit(): void
    {
        $this->generate()->assertOk();
        [$a, $b, $c] = $this->codes()->take(3)->all();

        // No hand edit: it broke the course's sequence. The route is gone.
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

    public function test_grid_shows_course_batch_ot_name_and_display_name(): void
    {
        $this->generate()->assertOk();

        $code = DB::table('coe_participant_codes as pc')
            ->join('student_master as sm', 'sm.pk', '=', 'pc.student_master_pk')
            ->join('course_master as cm', 'cm.pk', '=', 'pc.course_master_pk')
            ->where('pc.course_master_pk', $this->course)->where('pc.batch', $this->batch)
            ->whereNotNull('sm.first_name')->where('sm.first_name', '!=', '')
            ->first(['pc.unique_code', 'sm.display_name', 'sm.first_name', 'sm.middle_name', 'sm.last_name',
                'cm.course_name', 'cm.active_inactive', 'cm.end_date']);
        if ($code === null) {
            $this->markTestSkipped('No participant in the cohort has a first name.');
        }

        $active = (int) $code->active_inactive === 1 && ($code->end_date === null || $code->end_date >= now()->toDateString());
        $page = $this->actingAs($this->admin)
            ->get(route('admin.coe.unique-participant-code.index', $active ? [] : ['scope' => 'archived']))
            ->assertOk();

        $row = collect($page->viewData('rows'))->firstWhere('unique_code', $code->unique_code);
        $clean = fn ($v) => trim(preg_replace('/\s+/u', ' ', html_entity_decode((string) $v, ENT_QUOTES | ENT_HTML5)));
        $this->assertSame($this->batch, $row['batch']);
        $this->assertSame($clean(implode(' ', array_filter([$clean($code->first_name), $clean($code->middle_name), $clean($code->last_name)]))), $row['ot_name']);
        $this->assertSame($clean($code->display_name) ?: '—', $row['display_name']);
        $this->assertContains($this->batch, $page->viewData('batches')[$code->course_name]->all());

        $page->assertSee('<th scope="col">Batch</th>', false)
             ->assertSee('<th scope="col">Display Name</th>', false)
             ->assertDontSee('DT Name')
             ->assertDontSee('coe-act--edit', false)
             ->assertSee('id="upcFilterBatch"', false);
    }
}
