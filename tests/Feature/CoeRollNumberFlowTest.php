<?php

namespace Tests\Feature;

use App\Models\COE\RollNumber;
use App\Models\COE\RollNumberAllocation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * COE → Roll Numbers: generate, regenerate, freeze, the freeze lock on edit / delete /
 * regenerate, and notify. Runs against the development database (see phpunit.xml) inside
 * a transaction, on the first course + batch that has participants and no allocation yet.
 */
class CoeRollNumberFlowTest extends TestCase
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
            ->leftJoin('coe_roll_number_allocations as rna', fn ($j) => $j
                ->on('rna.course_master_pk', '=', 'sm.course_master_pk')->on('rna.batch', '=', 'sm.batch'))
            ->whereNull('rna.pk')
            ->whereNotNull('sm.course_master_pk')
            ->whereNotNull('sm.batch')
            ->where('sm.batch', '>', 0)
            ->groupBy('sm.course_master_pk', 'sm.batch')
            ->havingRaw('count(*) between 5 and 2000')
            ->orderByDesc(DB::raw('count(*)'))
            ->first(['sm.course_master_pk', 'sm.batch', DB::raw('count(*) as n')]);

        if ($admin === null || $cohort === null) {
            $this->markTestSkipped('No non-OT user, or no course + batch without roll numbers.');
        }

        $this->admin = $admin;
        $this->course = (int) $cohort->course_master_pk;
        $this->batch = (int) $cohort->batch;
        $this->participants = (int) $cohort->n;
    }

    private function generate(array $overrides = [])
    {
        return $this->actingAs($this->admin)->postJson(route('admin.coe.roll-number.generate'), $overrides + [
            'course' => $this->course,
            'batch' => $this->batch,
            'prefix' => 'TST',
            'start_number' => '001',
            'strategy' => RollNumberAllocation::STRATEGY_ALLOCATION_RULES,
            'rules' => ['avoid_same_service'],
        ]);
    }

    private function rows()
    {
        $allocation = RollNumberAllocation::where('course_master_pk', $this->course)->where('batch', $this->batch)->firstOrFail();

        return RollNumber::where('roll_number_allocation_pk', $allocation->pk)->orderBy('serial_number')->get();
    }

    public function test_generate_numbers_every_participant_once(): void
    {
        $this->generate()->assertOk()->assertJsonPath('written', $this->participants);

        $rows = $this->rows();
        $this->assertCount($this->participants, $rows);
        $this->assertSame($this->participants, $rows->pluck('roll_number')->unique()->count());
        $this->assertSame($this->participants, $rows->pluck('student_master_pk')->unique()->count());
        $this->assertSame('TST-001', $rows->first()->roll_number);
        $this->assertTrue($rows->every(fn ($r) => (int) $r->status === RollNumber::STATUS_GENERATED));
    }

    public function test_generate_rejects_a_bad_prefix_and_writes_nothing(): void
    {
        $this->generate(['prefix' => 'T S#'])->assertStatus(422);

        $this->assertFalse(RollNumberAllocation::where('course_master_pk', $this->course)->where('batch', $this->batch)->exists());
    }

    public function test_regenerate_replaces_the_run_before_any_freeze(): void
    {
        $this->generate()->assertOk();
        $this->generate(['prefix' => 'NEW', 'start_number' => '0101'])->assertOk();

        $rows = $this->rows();
        $this->assertCount($this->participants, $rows);
        $this->assertSame('NEW-0101', $rows->first()->roll_number);
    }

    public function test_freeze_locks_edit_delete_and_regenerate(): void
    {
        $this->generate()->assertOk();
        $rows = $this->rows();
        $frozenIds = $rows->take(2)->pluck('pk')->all();

        $this->actingAs($this->admin)->postJson(route('admin.coe.roll-number.freeze'), ['ids' => $frozenIds])
            ->assertOk()->assertJsonPath('frozen', 2);

        // Idempotent: freezing again changes nothing.
        $this->actingAs($this->admin)->postJson(route('admin.coe.roll-number.freeze'), ['ids' => $frozenIds])
            ->assertOk()->assertJsonPath('frozen', 0);

        $this->actingAs($this->admin)
            ->putJson(route('admin.coe.roll-number.update', $frozenIds[0]), ['prefix' => 'X', 'number' => '9'])
            ->assertStatus(422);
        $this->actingAs($this->admin)->deleteJson(route('admin.coe.roll-number.destroy', $frozenIds[0]))->assertStatus(422);
        $this->generate()->assertStatus(422);

        $this->assertSame($rows->first()->roll_number, RollNumber::find($frozenIds[0])->roll_number);

        // An unfrozen row can still be edited and deleted.
        $open = $rows->last();
        $this->actingAs($this->admin)
            ->putJson(route('admin.coe.roll-number.update', $open->pk), ['prefix' => 'EDIT', 'number' => '999'])
            ->assertOk()->assertJsonPath('roll_number', 'EDIT-999');
        $this->actingAs($this->admin)->deleteJson(route('admin.coe.roll-number.destroy', $open->pk))->assertOk();
        $this->assertNull(RollNumber::find($open->pk));
    }

    public function test_notify_sends_only_frozen_numbers(): void
    {
        $this->generate()->assertOk();
        $rows = $this->rows();

        $this->actingAs($this->admin)->postJson(route('admin.coe.roll-number.notify'), ['ids' => [$rows->first()->pk]])
            ->assertStatus(422);

        // Freeze a handful that have an OT login, so notifications have somewhere to go.
        $withLogin = DB::table('user_credentials')->where('user_category', 'S')
            ->whereIn('user_id', $rows->pluck('student_master_pk'))->pluck('user_id')->map(fn ($id) => (int) $id);
        if ($withLogin->isEmpty()) {
            $this->markTestSkipped('No participant in the cohort has an OT login.');
        }
        $ids = $rows->whereIn('student_master_pk', $withLogin->take(3)->all())->pluck('pk')->all();

        $this->actingAs($this->admin)->postJson(route('admin.coe.roll-number.freeze'), ['ids' => $ids])->assertOk();

        $before = DB::table('notifications')->where('type', 'coe_roll_number')->count();
        $this->actingAs($this->admin)->postJson(route('admin.coe.roll-number.notify'), ['ids' => $ids])->assertOk();

        $this->assertSame($before + count($ids), DB::table('notifications')->where('type', 'coe_roll_number')->count());
        $sample = RollNumber::find($ids[0]);
        $this->assertTrue(DB::table('notifications')->where('type', 'coe_roll_number')
            ->where('reference_pk', $sample->pk)->where('message', 'like', '%' . $sample->roll_number . '%')->exists());
    }

    public function test_index_and_participants_render(): void
    {
        $this->generate()->assertOk();

        $this->actingAs($this->admin)->get(route('admin.coe.roll-number.index'))->assertOk();
        $this->actingAs($this->admin)
            ->getJson(route('admin.coe.roll-number.participants', ['course' => $this->course, 'batch' => $this->batch]))
            ->assertOk();
    }
}
