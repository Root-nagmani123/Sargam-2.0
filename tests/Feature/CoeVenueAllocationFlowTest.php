<?php

namespace Tests\Feature;

use App\Models\COE\RollNumberAllocation;
use App\Models\COE\SeatAllocation;
use App\Models\COE\SeatPlan;
use App\Models\COE\SeatPlanVenue;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * COE → Venue Allocation: map a room, refuse a duplicate, swap two seats, freeze (publish
 * + notify), and the freeze lock on swap / remove. Development database, in a transaction.
 *
 * Needs an examination drive component already scheduled for a course. Roll numbers are
 * generated here for a batch of that course that has none, and the room is a throwaway
 * venue big enough for the whole batch, so the plan seats everyone.
 */
class CoeVenueAllocationFlowTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private int $course;

    private int $batch;

    private int $componentMapId;

    private int $venueId;

    private int $participants;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::where('user_category', '!=', 'S')->first();
        $map = DB::table('examination_drive_component_maps as m')
            ->join('examination_drives as d', 'd.id', '=', 'm.examination_drive_id')
            ->first(['m.id', 'd.course_master_pk']);
        $room = DB::table('venue_master')->whereNotNull('building_master_pk')->whereNotNull('floor_master_pk')
            ->first(['building_master_pk', 'floor_master_pk']);

        $cohort = $map === null ? null : DB::table('student_master as sm')
            ->leftJoin('coe_roll_number_allocations as rna', fn ($j) => $j
                ->on('rna.course_master_pk', '=', 'sm.course_master_pk')->on('rna.batch', '=', 'sm.batch'))
            ->whereNull('rna.pk')
            ->where('sm.course_master_pk', $map->course_master_pk)
            ->where('sm.batch', '>', 0)
            ->groupBy('sm.batch')
            ->havingRaw('count(*) between 3 and 500')
            ->orderByDesc(DB::raw('count(*)'))
            ->first(['sm.batch', DB::raw('count(*) as n')]);

        if ($admin === null || $map === null || $room === null || $cohort === null) {
            $this->markTestSkipped('Needs a non-OT user, a scheduled drive component, an exam building/floor and an unnumbered batch.');
        }

        $this->admin = $admin;
        $this->course = (int) $map->course_master_pk;
        $this->batch = (int) $cohort->batch;
        $this->participants = (int) $cohort->n;
        $this->componentMapId = (int) $map->id;

        $this->venueId = (int) DB::table('venue_master')->insertGetId([
            'venue_name' => 'PHPUnit Exam Hall',
            'venue_short_name' => 'PHPUNIT',
            'building_master_pk' => $room->building_master_pk,
            'floor_master_pk' => $room->floor_master_pk,
            'room_number' => 'T-1',
            'capacity' => $this->participants + 10,
            'laptop_capacity' => 0,
            'seating_capacity' => $this->participants + 10,
            'active_inactive' => 1,
            'created_date' => now()->toDateString(),
        ], 'venue_id');

        $this->actingAs($this->admin)->postJson(route('admin.coe.roll-number.generate'), [
            'course' => $this->course,
            'batch' => $this->batch,
            'prefix' => 'VA',
            'start_number' => '001',
            'strategy' => RollNumberAllocation::STRATEGY_SEQUENTIAL_BY_NAME,
        ])->assertOk();
    }

    private function map(array $overrides = [])
    {
        return $this->actingAs($this->admin)->postJson(route('admin.coe.venue-allocation.store'), $overrides + [
            'course_batch' => $this->course . ':' . $this->batch,
            'component_map_id' => $this->componentMapId,
            'candidate_category' => SeatPlan::CATEGORY_ALL,
            'laptop_requirement' => SeatPlan::LAPTOP_REQ_NONE,
            'venue_id' => $this->venueId,
            'rule_odd_even' => 0,
            'seat_gap' => 0,
        ]);
    }

    private function planVenue(): SeatPlanVenue
    {
        return SeatPlanVenue::where('venue_id', $this->venueId)->firstOrFail();
    }

    public function test_mapping_a_room_seats_the_whole_batch(): void
    {
        $this->map()->assertOk()->assertJsonPath('status', 'success');

        $planVenue = $this->planVenue();
        $seats = SeatAllocation::where('seat_plan_pk', $planVenue->seat_plan_pk)->get();

        $this->assertCount($this->participants, $seats);
        $this->assertSame($this->participants, $seats->pluck('seat_number')->unique()->count());
        $this->assertSame($this->participants, $seats->pluck('student_master_pk')->unique()->count());
        $this->assertTrue($seats->every(fn ($s) => $s->roll_number_pk !== null && (int) $s->venue_id === $this->venueId));
        $this->assertSame(0, (int) SeatPlan::find($planVenue->seat_plan_pk)->unallocated_count);
    }

    public function test_the_same_room_cannot_be_mapped_twice(): void
    {
        $this->map()->assertOk();
        $this->map()->assertStatus(422)->assertJsonValidationErrors('venue_id');
    }

    public function test_a_course_without_roll_numbers_is_refused(): void
    {
        $this->map(['course_batch' => $this->course . ':1'])->assertStatus(422)->assertJsonValidationErrors('course_batch');
    }

    public function test_swap_exchanges_two_seats_and_marks_them_manual(): void
    {
        $this->map()->assertOk();
        $planVenue = $this->planVenue();
        $seatOf = fn (int $n) => SeatAllocation::where('seat_plan_pk', $planVenue->seat_plan_pk)->where('seat_number', $n)->first();

        [$first, $second] = [$seatOf(1), $seatOf(2)];

        $this->actingAs($this->admin)
            ->postJson(route('admin.coe.venue-allocation.swap', $planVenue->pk), ['from_seat' => 1, 'to_seat' => 2])
            ->assertOk()->assertJsonPath('status', 'success');

        $this->assertSame((int) $first->student_master_pk, (int) $seatOf(2)->student_master_pk);
        $this->assertSame((int) $second->student_master_pk, (int) $seatOf(1)->student_master_pk);
        $this->assertTrue((bool) $seatOf(1)->is_manual && (bool) $seatOf(2)->is_manual);

        // Into an empty seat: the OT simply moves.
        $empty = $this->participants + 5;
        $this->actingAs($this->admin)
            ->postJson(route('admin.coe.venue-allocation.swap', $planVenue->pk), ['from_seat' => 1, 'to_seat' => $empty])
            ->assertOk();
        $this->assertNull($seatOf(1));
        $this->assertSame((int) $second->student_master_pk, (int) $seatOf($empty)->student_master_pk);

        // From an empty seat: refused.
        $this->actingAs($this->admin)
            ->postJson(route('admin.coe.venue-allocation.swap', $planVenue->pk), ['from_seat' => 1, 'to_seat' => 2])
            ->assertStatus(422)->assertJsonValidationErrors('from_seat');
    }

    public function test_freeze_publishes_notifies_and_locks_the_plan(): void
    {
        $this->map()->assertOk();
        $planVenue = $this->planVenue();
        $seated = SeatAllocation::where('seat_plan_pk', $planVenue->seat_plan_pk)->pluck('student_master_pk');
        // One notification per OT, even where an OT has two login rows.
        $withLogin = DB::table('user_credentials')->where('user_category', 'S')->whereIn('user_id', $seated)
            ->distinct()->count('user_id');
        $before = DB::table('notifications')->count();

        $this->actingAs($this->admin)->postJson(route('admin.coe.venue-allocation.freeze', $planVenue->pk))
            ->assertOk()->assertJsonPath('status', 'success');

        $this->assertTrue(SeatPlan::find($planVenue->seat_plan_pk)->isPublished());
        $this->assertSame($before + $withLogin, DB::table('notifications')->count());

        $this->actingAs($this->admin)->postJson(route('admin.coe.venue-allocation.freeze', $planVenue->pk))->assertStatus(422);
        $this->actingAs($this->admin)
            ->postJson(route('admin.coe.venue-allocation.swap', $planVenue->pk), ['from_seat' => 1, 'to_seat' => 2])
            ->assertStatus(422);
        $this->actingAs($this->admin)->deleteJson(route('admin.coe.venue-allocation.destroy', $planVenue->pk))->assertStatus(422);
        $this->assertNotNull(SeatPlanVenue::find($planVenue->pk));
    }

    public function test_removing_the_only_room_deletes_the_plan(): void
    {
        $this->map()->assertOk();
        $planVenue = $this->planVenue();

        $this->actingAs($this->admin)->deleteJson(route('admin.coe.venue-allocation.destroy', $planVenue->pk))->assertOk();

        $this->assertNull(SeatPlan::find($planVenue->seat_plan_pk));
        $this->assertFalse(SeatAllocation::where('seat_plan_pk', $planVenue->seat_plan_pk)->exists());
    }

    public function test_index_show_and_edit_render(): void
    {
        $this->map()->assertOk();
        $planVenue = $this->planVenue();

        $this->actingAs($this->admin)->get(route('admin.coe.venue-allocation.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.coe.venue-allocation.show', $planVenue->pk))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.coe.venue-allocation.edit', $planVenue->pk))->assertOk();
    }
}
