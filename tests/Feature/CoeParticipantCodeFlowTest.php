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

    public function test_regenerate_replaces_pending_codes(): void
    {
        $this->generate()->assertOk();
        $this->generate(['prefix' => 'ZZN-', 'count_start' => '1'])->assertOk();

        $codes = $this->codes();
        $this->assertCount($this->participants, $codes);
        $this->assertTrue($codes->every(fn ($c) => str_starts_with($c->unique_code, 'ZZN-')));
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

    public function test_edit_freeze_and_delete(): void
    {
        $this->generate()->assertOk();
        [$a, $b, $c] = $this->codes()->take(3)->all();

        // Edit: a free code is taken, a duplicate is refused.
        $this->actingAs($this->admin)->putJson(route('admin.coe.unique-participant-code.update', $a->pk), ['unique_code' => 'ZZE-1'])
            ->assertOk()->assertJsonPath('unique_code', 'ZZE-1');
        $this->actingAs($this->admin)->putJson(route('admin.coe.unique-participant-code.update', $b->pk), ['unique_code' => 'ZZE-1'])
            ->assertStatus(422);

        // Freeze, idempotently.
        $this->actingAs($this->admin)->postJson(route('admin.coe.unique-participant-code.freeze'), ['ids' => [$a->pk, $b->pk]])
            ->assertOk()->assertJsonPath('frozen', 2);
        $this->actingAs($this->admin)->postJson(route('admin.coe.unique-participant-code.freeze'), ['ids' => [$a->pk]])
            ->assertOk()->assertJsonPath('frozen', 0);

        // Frozen: no edit, no delete, no regenerate.
        $this->actingAs($this->admin)->putJson(route('admin.coe.unique-participant-code.update', $a->pk), ['unique_code' => 'ZZE-2'])
            ->assertStatus(422);
        $this->actingAs($this->admin)->postJson(route('admin.coe.unique-participant-code.bulk-destroy'), ['ids' => [$a->pk]])
            ->assertStatus(422);
        $this->generate()->assertStatus(422);

        // Mixed delete: the pending one goes, the frozen one stays.
        $this->actingAs($this->admin)->postJson(route('admin.coe.unique-participant-code.bulk-destroy'), ['ids' => [$b->pk, $c->pk]])
            ->assertOk()->assertJsonPath('deleted_ids', [$c->pk]);
        $this->assertNotNull(ParticipantCode::find($b->pk));
        $this->assertNull(ParticipantCode::find($c->pk));
        $this->assertSame('ZZE-1', ParticipantCode::find($a->pk)->unique_code);
    }

    public function test_grid_maps_course_ot_and_dt_name_from_the_masters(): void
    {
        $this->generate()->assertOk();

        // A participant with a login, to check DT Name against user_credentials.
        $code = DB::table('coe_participant_codes as pc')
            ->join('user_credentials as uc', fn ($j) => $j->on('uc.user_id', '=', 'pc.student_master_pk')->where('uc.user_category', 'S'))
            ->join('student_master as sm', 'sm.pk', '=', 'pc.student_master_pk')
            ->join('course_master as cm', 'cm.pk', '=', 'pc.course_master_pk')
            ->where('pc.course_master_pk', $this->course)->where('pc.batch', $this->batch)
            ->first(['pc.unique_code', 'uc.user_name', 'sm.display_name', 'cm.course_name', 'cm.active_inactive', 'cm.end_date']);
        if ($code === null) {
            $this->markTestSkipped('No participant in the cohort has an OT login.');
        }

        $active = (int) $code->active_inactive === 1 && ($code->end_date === null || $code->end_date >= now()->toDateString());
        $page = $this->actingAs($this->admin)
            ->get(route('admin.coe.unique-participant-code.index', $active ? [] : ['scope' => 'archived']))
            ->assertOk();

        $page->assertSee('data-upc-code="' . e($code->unique_code) . '"', false)
             ->assertSee('data-upc-dt="' . e($code->user_name) . '"', false)
             ->assertSee(e($code->display_name), false)
             ->assertSee(e($code->course_name), false)
             ->assertDontSee('Dana Arnold');
    }
}
