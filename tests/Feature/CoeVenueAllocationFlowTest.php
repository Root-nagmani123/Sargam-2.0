<?php

namespace Tests\Feature;

use App\Models\BuildingFloorRoomMapping;
use App\Models\BuildingMaster;
use App\Models\COE\LaptopResponse;
use App\Models\COE\RollNumberAllocation;
use App\Models\COE\SeatAllocation;
use App\Models\COE\SeatPlan;
use App\Models\COE\SeatPlanVenue;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * COE → Venue Allocation: map rooms (System generated and Manual), refuse a duplicate,
 * swap two seats, freeze (publish + notify), and the freeze lock on swap / remove.
 * Development database, in a transaction.
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

    private int $building;

    private int $floor;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::where('user_category', '!=', 'S')->first();
        $map = DB::table('examination_drive_component_maps as m')
            ->join('examination_drives as d', 'd.id', '=', 'm.examination_drive_id')
            ->first(['m.id', 'd.course_master_pk']);
        // Rooms come from COE Room Master: an active Classroom building and an active floor.
        // None on record is fine: a throwaway one, rolled back with the rest.
        $building = DB::table('building_master')->where('building_type', BuildingMaster::TYPE_CLASSROOM)
            ->where('active_inactive', 1)->value('pk')
            ?? DB::table('building_master')->insertGetId([
                'building_name' => 'PHPUnit Exam Block',
                'building_type' => BuildingMaster::TYPE_CLASSROOM,
                'active_inactive' => 1,
            ], 'pk');
        $floor = DB::table('floor_master')->where('active_inactive', 1)->value('pk');

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

        if ($admin === null || $map === null || $building === null || $floor === null || $cohort === null) {
            $this->markTestSkipped('Needs a non-OT user, a scheduled drive component, a Classroom building, a floor and an unnumbered batch.');
        }

        $this->admin = $admin;
        $this->course = (int) $map->course_master_pk;
        $this->batch = (int) $cohort->batch;
        $this->participants = (int) $cohort->n;
        $this->componentMapId = (int) $map->id;
        // Seating is mapped only for drives the Course Coordinator approved.
        DB::table('examination_drives')
            ->where('id', DB::table('examination_drive_component_maps')->where('id', $map->id)->value('examination_drive_id'))
            ->update(['cc_status' => \App\Models\ExaminationDrive::CC_APPROVED]);
        $this->building = (int) $building;
        $this->floor = (int) $floor;

        $this->venueId = $this->addRoom('PHPUNIT', $this->participants + 10);

        // Roll numbers are generated only for a running course.
        DB::table('course_master')->where('pk', $this->course)
            ->update(['active_inactive' => 1, 'end_date' => now()->addYear()->toDateString()]);

        $this->actingAs($this->admin)->postJson(route('admin.coe.roll-number.generate'), [
            'course' => $this->course,
            'batch' => $this->batch,
            'prefix' => 'VA',
            'start_number' => '001',
            'strategy' => RollNumberAllocation::STRATEGY_SEQUENTIAL_BY_NAME,
        ])->assertOk();
    }

    /** A throwaway examination room: a venue and its Room Master row, joined by the short name. */
    private function addRoom(string $code, int $capacity): int
    {
        $venueId = (int) DB::table('venue_master')->insertGetId([
            'venue_name' => 'PHPUnit Exam Hall ' . $code,
            'venue_short_name' => $code,
            'room_number' => $code,
            'capacity' => $capacity,
            'laptop_capacity' => 0,
            'seating_capacity' => $capacity,
            'active_inactive' => 1,
            'created_date' => now()->toDateString(),
        ], 'venue_id');

        DB::table('building_floor_room_mapping')->insert([
            'building_master_pk' => $this->building,
            'floor_master_pk' => $this->floor,
            'room_name' => $code,
            'room_type' => array_key_first(BuildingFloorRoomMapping::$roomTypes),
            'capacity' => $capacity,
            'active_inactive' => 1,
            'created_date' => now(),
            'updated_date' => now(),
        ]);

        return $venueId;
    }

    private function map(array $overrides = [])
    {
        // A null override leaves the field out of the request.
        return $this->actingAs($this->admin)->postJson(route('admin.coe.venue-allocation.store'), array_filter($overrides + [
            'course_batch' => $this->course . ':' . $this->batch,
            'component_map_id' => $this->componentMapId,
            'candidate_category' => SeatPlan::CATEGORY_ALL,
            'laptop_requirement' => SeatPlan::LAPTOP_REQ_NONE,
            'seating_mode' => 'system',
            'venue_ids' => [$this->venueId],
            'rule_odd_even' => 0,
            'seat_gap' => 0,
        ], fn ($value) => $value !== null));
    }

    /** @param array<int, array{venue_id: int, ots: array<int, int>}> $rooms */
    private function mapManual(array $rooms, array $overrides = [])
    {
        return $this->map($overrides + ['seating_mode' => 'manual', 'rooms' => $rooms, 'venue_ids' => null]);
    }

    /** OT pks with a roll number, in roll order — what Manual Seating's picker offers. */
    private function ots(): array
    {
        return collect($this->actingAs($this->admin)
            ->getJson(route('admin.coe.venue-allocation.components', ['course_batch' => $this->course . ':' . $this->batch]))
            ->assertOk()
            ->json('ots'))->pluck('pk')->all();
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
        $this->map()->assertStatus(422)->assertJsonValidationErrors('venue_ids');
    }

    public function test_system_generated_maps_several_rooms_at_once(): void
    {
        $second = $this->addRoom('PHPUNIT2', 2);

        $this->map(['venue_ids' => [$this->venueId, $second]])->assertOk()->assertJsonPath('status', 'success');

        $plan = $this->planVenue()->seat_plan_pk;
        $this->assertSame(2, SeatPlanVenue::where('seat_plan_pk', $plan)->count());
        $this->assertSame($this->participants, SeatAllocation::where('seat_plan_pk', $plan)->count());
    }

    // Every OT of the paper needs a seat: a manual room that leaves anyone out is refused, nothing saved.
    public function test_a_manual_room_must_seat_every_ot_of_the_paper(): void
    {
        $all = $this->ots();

        $this->mapManual([['venue_id' => $this->venueId, 'ots' => array_slice($all, 0, 2)]])
            ->assertStatus(422)->assertJsonValidationErrors('rooms.0.ots');
        $this->assertFalse(SeatPlanVenue::where('venue_id', $this->venueId)->exists());

        $this->mapManual([['venue_id' => $this->venueId, 'ots' => $all]])->assertOk()->assertJsonPath('status', 'success');
        $planVenue = $this->planVenue();
        $this->assertTrue($planVenue->isManual());
        $this->assertSame(0, (int) SeatPlan::find($planVenue->seat_plan_pk)->unallocated_count);
    }

    public function test_manual_refuses_more_ots_than_the_room_seats(): void
    {
        $small = $this->addRoom('PHPUNIT2', 1);

        $this->mapManual([['venue_id' => $small, 'ots' => array_slice($this->ots(), 0, 2)]])
            ->assertStatus(422)->assertJsonValidationErrors('rooms.0.ots');
    }

    public function test_manual_refuses_one_ot_in_two_rooms(): void
    {
        $second = $this->addRoom('PHPUNIT2', 5);
        $ot = $this->ots()[0];

        $this->mapManual([['venue_id' => $this->venueId, 'ots' => [$ot]], ['venue_id' => $second, 'ots' => [$ot]]])
            ->assertStatus(422)->assertJsonValidationErrors('rooms.1.ots');
    }

    public function test_manual_refuses_more_laptop_ots_than_the_room_has_laptops(): void
    {
        // Every OT needs a laptop; the room has none.
        $this->mapManual([['venue_id' => $this->venueId, 'ots' => array_slice($this->ots(), 0, 1)]],
            ['laptop_requirement' => SeatPlan::LAPTOP_REQ_ALL])
            ->assertStatus(422)->assertJsonValidationErrors('rooms.0.ots');
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

    public function test_change_room_moves_ots_to_another_room_of_the_paper(): void
    {
        $other = $this->addRoom('PHPUNIT2', $this->participants + 10);
        $this->map(['venue_ids' => [$this->venueId, $other]])->assertOk();
        $planVenue = $this->planVenue();
        $plan = (int) $planVenue->seat_plan_pk;
        $from = SeatAllocation::where('seat_plan_pk', $plan)->where('venue_id', $planVenue->venue_id)->orderBy('seat_number')->first();
        $to = (int) $planVenue->venue_id === $this->venueId ? $other : $this->venueId;
        $before = SeatAllocation::where('seat_plan_pk', $plan)->count();

        $this->actingAs($this->admin)->postJson(route('admin.coe.venue-allocation.move', $planVenue->pk),
            ['seats' => [$from->seat_number], 'venue_id' => $to])->assertOk()->assertJsonPath('status', 'success');

        $moved = SeatAllocation::where('seat_plan_pk', $plan)->where('student_master_pk', $from->student_master_pk)->first();
        $this->assertSame($to, (int) $moved->venue_id);
        $this->assertTrue((bool) $moved->is_manual);
        $this->assertSame($before, SeatAllocation::where('seat_plan_pk', $plan)->count());

        // Same room again: refused.
        $this->actingAs($this->admin)->postJson(route('admin.coe.venue-allocation.move', $planVenue->pk),
            ['seats' => [1], 'venue_id' => $planVenue->venue_id])->assertStatus(422)->assertJsonValidationErrors('venue_id');
    }

    public function test_change_room_to_a_free_room_adds_it_to_the_plan(): void
    {
        $this->map()->assertOk();
        $planVenue = $this->planVenue();
        $free = $this->addRoom('PHPUNIT3', 5);
        $from = SeatAllocation::where('seat_plan_pk', $planVenue->seat_plan_pk)->orderBy('seat_number')->first();

        $this->actingAs($this->admin)->get(route('admin.coe.venue-allocation.edit', $planVenue->pk))->assertOk()
            ->assertSee('PHPUnit Exam Hall PHPUNIT3');

        $this->actingAs($this->admin)->postJson(route('admin.coe.venue-allocation.move', $planVenue->pk),
            ['seats' => [$from->seat_number], 'venue_id' => $free])->assertOk();

        $room = SeatPlanVenue::where('seat_plan_pk', $planVenue->seat_plan_pk)->where('venue_id', $free)->first();
        $this->assertNotNull($room);
        $this->assertTrue($room->isManual());
        $this->assertTrue(DB::table('coe_seat_plan_venue_ots')->where('seat_plan_venue_pk', $room->pk)
            ->where('student_master_pk', $from->student_master_pk)->exists());
        $this->assertSame(1, SeatAllocation::where('seat_plan_pk', $planVenue->seat_plan_pk)->where('venue_id', $free)->count());
    }

    public function test_change_room_is_refused_on_a_frozen_plan(): void
    {
        $this->map()->assertOk();
        $planVenue = $this->planVenue();
        $free = $this->addRoom('PHPUNIT4', 5);
        $this->actingAs($this->admin)->postJson(route('admin.coe.venue-allocation.freeze', $planVenue->pk))->assertOk();

        $this->actingAs($this->admin)->postJson(route('admin.coe.venue-allocation.move', $planVenue->pk),
            ['seats' => [1], 'venue_id' => $free])->assertStatus(422);
    }

    public function test_print_renders_the_branded_seating_plan(): void
    {
        $this->map()->assertOk();
        $planVenue = $this->planVenue();
        $roll = DB::table('coe_seat_allocations as sa')->join('coe_roll_numbers as rn', 'rn.pk', '=', 'sa.roll_number_pk')
            ->where('sa.seat_plan_pk', $planVenue->seat_plan_pk)->value('rn.roll_number');

        $this->actingAs($this->admin)->get(route('admin.coe.venue-allocation.print', $planVenue->pk))->assertOk()
            ->assertSee('Examination Seating Plan')
            ->assertSee('PHPUnit Exam Hall PHPUNIT')
            ->assertSee($roll)
            ->assertDontSee('setup_content');
    }

    public function test_seat_list_exports_as_excel_pdf_and_csv(): void
    {
        $this->map()->assertOk();
        $planVenue = $this->planVenue();
        $roll = DB::table('coe_seat_allocations as sa')->join('coe_roll_numbers as rn', 'rn.pk', '=', 'sa.roll_number_pk')
            ->where('sa.seat_plan_pk', $planVenue->seat_plan_pk)->value('rn.roll_number');
        $url = fn (string $f) => route('admin.coe.venue-allocation.export', ['id' => $planVenue->pk, 'format' => $f]);

        $excel = $this->actingAs($this->admin)->get($url('excel'))->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $excel->headers->get('content-disposition'));

        $pdf = $this->actingAs($this->admin)->get($url('pdf'))->assertOk();
        $this->assertStringContainsString('.pdf', (string) $pdf->headers->get('content-disposition'));

        $this->assertStringContainsString($roll, $this->actingAs($this->admin)->get($url('csv'))->assertOk()->streamedContent());
        $this->actingAs($this->admin)->get($url('docx'))->assertNotFound();
    }

    public function test_view_lists_every_seated_ot_with_the_plan_details(): void
    {
        $this->map()->assertOk();
        $planVenue = $this->planVenue();
        $rolls = DB::table('coe_seat_allocations as sa')->join('coe_roll_numbers as rn', 'rn.pk', '=', 'sa.roll_number_pk')
            ->where('sa.seat_plan_pk', $planVenue->seat_plan_pk)->pluck('rn.roll_number');

        $page = $this->actingAs($this->admin)->get(route('admin.coe.venue-allocation.show', $planVenue->pk))->assertOk()
            ->assertSee('Seat Details')->assertSee('Examination Drive');
        foreach ($rolls as $roll) {
            $page->assertSee($roll);
        }
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

    // ── Map Seating Plan by examination drive ───────────────────────────────

    /** The fixture component's drive, open, CC-approved and pointed at the batch numbered in setUp(). */
    private function driveId(): int
    {
        $id = (int) DB::table('examination_drive_component_maps')->where('id', $this->componentMapId)->value('examination_drive_id');
        $update = ['status' => \App\Models\ExaminationDrive::STATUS_ACTIVE, 'cc_status' => \App\Models\ExaminationDrive::CC_APPROVED];
        if ($this->batch >= 1901 && $this->batch <= 2155) {
            $update['academic_session'] = $this->batch;
        }
        DB::table('examination_drives')->where('id', $id)->update($update);

        return $id;
    }

    private function driveData(): array
    {
        return $this->actingAs($this->admin)
            ->getJson(route('admin.coe.venue-allocation.drive', $this->driveId()))
            ->assertOk()->json();
    }

    private function mapDrive(array $rows)
    {
        return $this->actingAs($this->admin)->postJson(route('admin.coe.venue-allocation.store-drive'), [
            'drive_id' => $this->driveId(),
            'rows' => $rows,
            'rule_odd_even' => 0,
            'seat_gap' => 0,
        ]);
    }

    public function test_drive_lists_its_subjects_components_and_ots(): void
    {
        $data = $this->driveData();

        $ids = collect($data['subjects'])->flatMap(fn ($s) => collect($s['components'])->pluck('id'))->all();
        $this->assertContains($this->componentMapId, $ids);
        $this->assertSame(count($data['ots']), $data['ot_count']);
        $this->assertNotEmpty($data['ots']);
    }

    public function test_drive_shows_the_component_exam_date_not_the_range(): void
    {
        DB::table('examination_drive_component_maps')->where('id', $this->componentMapId)
            ->update(['exam_date' => '2026-11-11', 'exam_end_date' => '2026-11-14']);

        $component = collect($this->driveData()['subjects'])->flatMap(fn ($s) => $s['components'])
            ->firstWhere('id', $this->componentMapId);
        $this->assertSame('11/11/2026', $component['exam_date']);
    }

    public function test_the_list_shows_the_examination_drive(): void
    {
        $this->mapDrive([['component_map_id' => $this->componentMapId, 'venue_id' => $this->venueId,
            'ots' => collect($this->driveData()['ots'])->pluck('pk')->all()]])->assertOk();
        $name = DB::table('examination_drives')->where('id', $this->driveId())->value('drive_name');

        $rows = $this->actingAs($this->admin)->get(route('admin.coe.venue-allocation.index'))
            ->assertOk()->viewData('rows');
        $this->assertContains(trim((string) $name) ?: '—', $rows->pluck('drive')->all());
    }

    public function test_a_drive_pending_at_cc_is_not_offered_or_accepted(): void
    {
        $id = $this->driveId();
        DB::table('examination_drives')->where('id', $id)->update(['cc_status' => \App\Models\ExaminationDrive::CC_PENDING]);

        $drives = $this->actingAs($this->admin)->get(route('admin.coe.venue-allocation.index'))
            ->assertOk()->viewData('drives');
        $this->assertNotContains($id, collect($drives)->pluck('id')->all());

        $this->actingAs($this->admin)->getJson(route('admin.coe.venue-allocation.drive', $id))->assertStatus(422);
    }

    public function test_drive_row_seats_the_ots_picked_for_its_room(): void
    {
        // Every OT of the paper, as Map Seating only saves a fully seated paper.
        $picked = collect($this->driveData()['ots'])->pluck('pk')->all();

        $this->mapDrive([['component_map_id' => $this->componentMapId, 'venue_id' => $this->venueId, 'ots' => $picked]])
            ->assertOk()->assertJsonCount(1, 'results');

        $planVenue = $this->planVenue();
        $plan = SeatPlan::find($planVenue->seat_plan_pk);
        $this->assertTrue($planVenue->isManual());
        $this->assertSame($this->componentMapId, (int) $plan->examination_drive_component_map_id);
        $this->assertSame(SeatPlan::CATEGORY_ALL, (int) $plan->candidate_category);
        $seated = SeatAllocation::where('seat_plan_pk', $plan->pk)->pluck('student_master_pk')->map(fn ($pk) => (int) $pk)->sort()->values()->all();
        $this->assertSame(collect($picked)->sort()->values()->all(), $seated);

        // Listed OTs come back as taken for that component, and cannot be listed again.
        $taken = collect($this->driveData()['subjects'])->flatMap(fn ($s) => $s['components'])
            ->firstWhere('id', $this->componentMapId)['taken'];
        $this->assertEqualsCanonicalizing($picked, $taken);
        $other = $this->addRoom('PHPUNIT2', 5);
        $this->mapDrive([['component_map_id' => $this->componentMapId, 'venue_id' => $other, 'ots' => [$picked[0]]]])
            ->assertStatus(422)->assertJsonValidationErrors('rows.0.ots');
    }

    public function test_drive_refuses_a_paper_that_leaves_ots_without_a_seat(): void
    {
        $ots = collect($this->driveData()['ots'])->pluck('pk')->all();
        if (count($ots) < 2) {
            $this->markTestSkipped('Needs at least two OTs with a roll number.');
        }

        $this->mapDrive([['component_map_id' => $this->componentMapId, 'venue_id' => $this->venueId, 'ots' => [$ots[0]]]])
            ->assertStatus(422)->assertJsonValidationErrors('rows.0.ots');
        // Rolled back: no room, no plan, no seat.
        $this->assertFalse(SeatPlanVenue::where('venue_id', $this->venueId)->exists());
        $this->assertFalse(SeatPlan::where('examination_drive_component_map_id', $this->componentMapId)
            ->where('batch', $this->batch)->exists());
    }

    public function test_drive_row_errors_are_keyed_to_the_row(): void
    {
        $ot = $this->driveData()['ots'][0]['pk'];
        $small = $this->addRoom('PHPUNIT2', 1);

        // Missing room → that row's room; too many OTs for the room → that row's OTs.
        $this->mapDrive([['component_map_id' => $this->componentMapId, 'ots' => [$ot]]])
            ->assertStatus(422)->assertJsonValidationErrors('rows.0.venue_id');
        $two = collect($this->driveData()['ots'])->take(2)->pluck('pk')->all();
        $this->mapDrive([['component_map_id' => $this->componentMapId, 'venue_id' => $small, 'ots' => $two]])
            ->assertStatus(422)->assertJsonValidationErrors('rows.0.ots');
        $this->assertFalse(SeatPlanVenue::where('venue_id', $small)->exists());
    }

    public function test_one_component_maps_to_several_rooms_in_one_save(): void
    {
        $ots = collect($this->driveData()['ots'])->pluck('pk')->all();
        if (count($ots) < 2) {
            $this->markTestSkipped('Needs at least two OTs with a roll number.');
        }
        $second = $this->addRoom('PHPUNIT2', count($ots));
        [$first, $rest] = [[$ots[0]], array_slice($ots, 1)];

        $this->mapDrive([
            ['component_map_id' => $this->componentMapId, 'venue_id' => $this->venueId, 'ots' => $first],
            ['component_map_id' => $this->componentMapId, 'venue_id' => $second, 'ots' => $rest],
        ])->assertOk()->assertJsonCount(1, 'results');

        $plan = (int) SeatPlanVenue::where('venue_id', $this->venueId)->value('seat_plan_pk');
        $this->assertSame($plan, (int) SeatPlanVenue::where('venue_id', $second)->value('seat_plan_pk'));
        $seatedIn = fn (int $venue) => SeatAllocation::where('seat_plan_pk', $plan)->where('venue_id', $venue)
            ->pluck('student_master_pk')->map(fn ($pk) => (int) $pk)->sort()->values()->all();
        $this->assertSame($first, $seatedIn($this->venueId));
        $this->assertSame(collect($rest)->sort()->values()->all(), $seatedIn($second));
    }

    public function test_one_component_refuses_a_room_or_an_ot_twice(): void
    {
        $ots = collect($this->driveData()['ots'])->pluck('pk')->all();
        $second = $this->addRoom('PHPUNIT2', 5);

        $this->mapDrive([
            ['component_map_id' => $this->componentMapId, 'venue_id' => $this->venueId, 'ots' => [$ots[0]]],
            ['component_map_id' => $this->componentMapId, 'venue_id' => $this->venueId, 'ots' => [$ots[1] ?? $ots[0]]],
        ])->assertStatus(422)->assertJsonValidationErrors('rows.1.venue_id');

        $this->mapDrive([
            ['component_map_id' => $this->componentMapId, 'venue_id' => $this->venueId, 'ots' => [$ots[0]]],
            ['component_map_id' => $this->componentMapId, 'venue_id' => $second, 'ots' => [$ots[0]]],
        ])->assertStatus(422)->assertJsonValidationErrors('rows.1.ots');
        $this->assertFalse(SeatPlanVenue::whereIn('venue_id', [$this->venueId, $second])->exists());
    }

    public function test_drive_refuses_a_component_of_another_drive(): void
    {
        $foreign = (int) DB::table('examination_drive_component_maps')
            ->where('examination_drive_id', '!=', $this->driveId())->value('id');
        if ($foreign === 0) {
            $this->markTestSkipped('Needs a second examination drive.');
        }

        $this->mapDrive([['component_map_id' => $foreign, 'venue_id' => $this->venueId, 'ots' => [$this->driveData()['ots'][0]['pk']]]])
            ->assertStatus(422)->assertJsonValidationErrors('rows.0.component_map_id');
    }

    // ── Add Invigilators ────────────────────────────────────────────────────

    /** @return array{0: array<int, int>, 1: int} two active faculty pks and one active MTS employee pk */
    private function people(): array
    {
        $faculty = DB::table('faculty_master')->where('active_inactive', 1)->limit(2)->pluck('pk')->map(fn ($pk) => (int) $pk)->all();
        $mts = DB::table('employee_master as em')->join('designation_master as dm', 'dm.pk', '=', 'em.designation_master_pk')
            ->where('dm.designation_name', 'MULTI TASKING STAFF')->where('em.status', 1)->value('em.pk');
        if (count($faculty) < 2 || $mts === null) {
            $this->markTestSkipped('Needs two active faculty and an active Multi Tasking Staff employee.');
        }

        return [$faculty, (int) $mts];
    }

    private function saveInvigilators(array $rows)
    {
        return $this->actingAs($this->admin)->postJson(route('admin.coe.venue-allocation.invigilators.store'), ['rows' => $rows]);
    }

    public function test_invigilators_are_saved_and_shown_again(): void
    {
        [[$chief, $invigilator], $mts] = $this->people();
        DB::table('examination_drive_component_maps')->where('id', $this->componentMapId)->update(['exam_type' => 'Online Objective']);
        $this->map()->assertOk();
        $room = $this->planVenue()->pk;

        $this->saveInvigilators([['id' => $room, 'chief' => $chief, 'invigilator' => $invigilator, 'mts' => $mts]])
            ->assertOk()->assertJsonPath('status', 'success');

        $rooms = collect($this->actingAs($this->admin)->getJson(route('admin.coe.venue-allocation.invigilators', $room))
            ->assertOk()->json('subjects'))->flatMap(fn ($s) => $s['rooms']);
        $mine = $rooms->firstWhere('id', $room);
        $this->assertSame([$chief, $invigilator, null, $mts], [$mine['chief'], $mine['invigilator'], $mine['assistant'], $mine['mts']]);
        $this->assertNotSame('—', $mine['ot']);

        // View shows who invigilates; the listing offers Edit (not Add) Invigilators.
        $name = fn (int $pk) => trim((string) DB::table('faculty_master')->where('pk', $pk)->value('full_name'));
        $view = $this->actingAs($this->admin)->get(route('admin.coe.venue-allocation.show', $room))->assertOk()
            ->assertSee('Invigilators')->assertSee('Chief Invigilator');
        if ($name($chief) !== '') {
            $view->assertSee($name($chief));
        }
        $listing = $this->actingAs($this->admin)->get(route('admin.coe.venue-allocation.index'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/data-coe-id="' . $room . '"[^>]*data-va-invig-has="1"/s', $listing);

        // Clearing every role removes the row.
        $this->saveInvigilators([['id' => $room]])->assertOk();
        $this->assertFalse(DB::table('coe_seat_plan_venue_invigilators')->where('seat_plan_venue_pk', $room)->exists());
    }

    public function test_one_person_cannot_hold_two_roles_or_two_rooms_of_a_paper(): void
    {
        [[$chief], ] = $this->people();
        DB::table('examination_drive_component_maps')->where('id', $this->componentMapId)->update(['exam_type' => 'Online Objective']);
        $second = $this->addRoom('PHPUNIT2', 5);
        $this->map(['venue_ids' => [$this->venueId, $second]])->assertOk();
        [$a, $b] = SeatPlanVenue::whereIn('venue_id', [$this->venueId, $second])->orderBy('venue_id')->pluck('pk')->all();

        $this->saveInvigilators([['id' => $a, 'chief' => $chief, 'assistant' => $chief]])
            ->assertStatus(422)->assertJsonValidationErrors('rows.0.assistant');
        $this->saveInvigilators([['id' => $a, 'chief' => $chief], ['id' => $b, 'invigilator' => $chief]])
            ->assertStatus(422)->assertJsonValidationErrors('rows.1.invigilator');

        // Also against a room of the same paper saved earlier.
        $this->saveInvigilators([['id' => $a, 'chief' => $chief]])->assertOk();
        $this->saveInvigilators([['id' => $b, 'chief' => $chief]])
            ->assertStatus(422)->assertJsonValidationErrors('rows.0.chief');
    }

    public function test_one_person_invigilates_one_room_a_day_across_papers(): void
    {
        [[$chief], ] = $this->people();
        DB::table('examination_drive_component_maps')->where('id', $this->componentMapId)
            ->update(['exam_type' => 'Online Objective', 'exam_date' => '2026-11-11']);
        $this->map()->assertOk();
        $a = $this->planVenue();
        $plan = SeatPlan::find($a->seat_plan_pk);
        $plan->forceFill(['exam_date' => '2026-11-11'])->save();

        // Another paper (a plan of its own) in another room, the same day.
        $other = $plan->replicate();
        $other->forceFill(['examination_drive_component_map_id' => null, 'plan_name' => 'PHPUnit other paper', 'exam_name' => 'Other Paper'])->save();
        $b = SeatPlanVenue::create(['seat_plan_pk' => $other->pk, 'venue_id' => $this->addRoom('PHPUNIT5', 5),
            'purpose' => SeatPlanVenue::PURPOSE_ANY, 'seating_mode' => SeatPlanVenue::MODE_MANUAL, 'display_order' => 1]);

        $this->saveInvigilators([['id' => $a->pk, 'chief' => $chief]])->assertOk();

        // The dialog of the other paper lists them as busy that day...
        $busy = $this->actingAs($this->admin)->getJson(route('admin.coe.venue-allocation.invigilators', $b->pk))->assertOk()->json('busy');
        $this->assertContains($chief, $busy['d2026-11-11']['f'] ?? []);
        // ...and the server refuses them, in any role.
        $this->saveInvigilators([['id' => $b->pk, 'assistant' => $chief]])
            ->assertStatus(422)->assertJsonValidationErrors('rows.0.assistant');

        // Another day is fine.
        $other->forceFill(['exam_date' => '2026-11-12'])->save();
        $this->saveInvigilators([['id' => $b->pk, 'assistant' => $chief]])->assertOk();
    }

    public function test_a_frozen_plan_locks_its_invigilators(): void
    {
        [[$chief, $other], ] = $this->people();
        DB::table('examination_drive_component_maps')->where('id', $this->componentMapId)->update(['exam_type' => 'Online Objective']);
        $this->map()->assertOk();
        $room = $this->planVenue()->pk;
        $this->saveInvigilators([['id' => $room, 'chief' => $chief]])->assertOk();

        $this->actingAs($this->admin)->postJson(route('admin.coe.venue-allocation.freeze', $room))->assertOk();

        $this->actingAs($this->admin)->getJson(route('admin.coe.venue-allocation.invigilators', $room))->assertStatus(422);
        $this->saveInvigilators([['id' => $room, 'chief' => $other]])->assertStatus(422)->assertJsonValidationErrors('rows.0.id');
        $this->assertSame($chief, (int) DB::table('coe_seat_plan_venue_invigilators')->where('seat_plan_venue_pk', $room)->value('chief_faculty_pk'));
        $this->actingAs($this->admin)->get(route('admin.coe.venue-allocation.index'))->assertOk()
            ->assertDontSee(route('admin.coe.venue-allocation.invigilators', $room), false);
    }

    public function test_freeze_notifies_invigilators_and_lists_their_duty(): void
    {
        // An active faculty member with a login (faculty_master.employee_master_pk = user_id).
        $faculty = DB::table('faculty_master as f')->join('user_credentials as uc', 'uc.user_id', '=', 'f.employee_master_pk')
            ->where('f.active_inactive', 1)->first(['f.pk', 'uc.pk as login', 'uc.user_id']);
        if ($faculty === null) {
            $this->markTestSkipped('Needs an active faculty member with a login.');
        }
        DB::table('examination_drive_component_maps')->where('id', $this->componentMapId)->update(['exam_type' => 'Online Objective']);
        $this->map()->assertOk();
        $room = $this->planVenue()->pk;
        $this->saveInvigilators([['id' => $room, 'invigilator' => (int) $faculty->pk]])->assertOk();

        // Not frozen yet: not on their page.
        $me = User::find($faculty->login);
        $this->actingAs($me)->get(route('admin.coe.my-invigilation.index'))->assertOk()
            ->assertDontSee(route('admin.coe.venue-allocation.show', $room), false);

        $this->actingAs($this->admin)->postJson(route('admin.coe.venue-allocation.freeze', $room))->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertTrue(DB::table('notifications')->where('receiver_user_id', $faculty->user_id)
            ->where('type', 'coe_invigilation')->where('reference_pk', $room)->exists());
        $this->actingAs($me)->get(route('admin.coe.my-invigilation.index'))->assertOk()
            ->assertSee('PHPUnit Exam Hall PHPUNIT')
            ->assertSee(route('admin.coe.venue-allocation.show', $room), false);
    }

    public function test_an_ot_sees_their_seat_once_the_plan_is_frozen(): void
    {
        $this->map()->assertOk();
        $room = $this->planVenue();
        $login = DB::table('coe_seat_allocations as sa')
            ->join('user_credentials as uc', fn ($j) => $j->on('uc.user_id', '=', 'sa.student_master_pk')->where('uc.user_category', 'S'))
            ->where('sa.seat_plan_pk', $room->seat_plan_pk)
            ->first(['uc.pk', 'sa.seat_number']);
        if ($login === null) {
            $this->markTestSkipped('Needs a seated OT with a login.');
        }
        $ot = User::find($login->pk);
        $desk = explode('-', \App\Http\Controllers\Admin\COE\VenueAllocationController::seatLabel((int) $login->seat_number), 2)[1];

        $this->actingAs($ot)->get(route('admin.coe.my-exam-seat.index'))->assertOk()
            ->assertSee('No seat allotted yet')->assertDontSee('PHPUnit Exam Hall PHPUNIT');

        $this->actingAs($this->admin)->postJson(route('admin.coe.venue-allocation.freeze', $room->pk))->assertOk();

        $this->actingAs($ot)->get(route('admin.coe.my-exam-seat.index'))->assertOk()
            ->assertSee('PHPUnit Exam Hall PHPUNIT')->assertSee($desk);
        // Not an OT: sent back.
        $this->actingAs($this->admin)->get(route('admin.coe.my-exam-seat.index'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_an_offline_objective_paper_takes_no_invigilators(): void
    {
        [[$chief], ] = $this->people();
        DB::table('examination_drive_component_maps')->where('id', $this->componentMapId)->update(['exam_type' => 'Offline Objective']);
        $this->map()->assertOk();
        $room = $this->planVenue()->pk;

        $this->actingAs($this->admin)->getJson(route('admin.coe.venue-allocation.invigilators', $room))->assertStatus(422);
        $this->saveInvigilators([['id' => $room, 'chief' => $chief]])->assertStatus(422)->assertJsonValidationErrors('rows.0.id');
        $this->actingAs($this->admin)->get(route('admin.coe.venue-allocation.index'))->assertOk()
            ->assertDontSee(route('admin.coe.venue-allocation.invigilators', $room), false);
    }

    public function test_an_online_objective_paper_takes_no_assistant_invigilator(): void
    {
        [[$chief, $other], $mts] = $this->people();
        DB::table('examination_drive_component_maps')->where('id', $this->componentMapId)->update(['exam_type' => 'Online Descriptive']);
        $this->map()->assertOk();
        $room = $this->planVenue()->pk;
        $dialog = fn () => collect($this->actingAs($this->admin)->getJson(route('admin.coe.venue-allocation.invigilators', $room))
            ->assertOk()->json('subjects'))->flatMap(fn ($s) => $s['rooms'])->firstWhere('id', $room);

        // Online Descriptive: all four roles.
        $this->assertSame([], $dialog()['locked']);
        $this->saveInvigilators([['id' => $room, 'chief' => $chief, 'assistant' => $other, 'mts' => $mts]])->assertOk();

        // Online Objective: no Assistant Invigilator; one saved earlier shows empty.
        DB::table('examination_drive_component_maps')->where('id', $this->componentMapId)->update(['exam_type' => 'Online Objective']);
        $mine = $dialog();
        $this->assertSame(['assistant'], $mine['locked']);
        $this->assertSame([$chief, null, $mts], [$mine['chief'], $mine['assistant'], $mine['mts']]);

        $this->saveInvigilators([['id' => $room, 'chief' => $chief, 'assistant' => $other]])
            ->assertStatus(422)->assertJsonValidationErrors('rows.0.assistant');
        $this->saveInvigilators([['id' => $room, 'chief' => $chief, 'invigilator' => $other, 'mts' => $mts]])->assertOk();
        $this->assertNull(DB::table('coe_seat_plan_venue_invigilators')->where('seat_plan_venue_pk', $room)->value('assistant_faculty_pk'));
    }

    public function test_laptop_required_is_set_per_ot_from_map_seating_plan(): void
    {
        $driveId = (int) DB::table('examination_drive_component_maps')->where('id', $this->componentMapId)->value('examination_drive_id');
        $ot = (int) DB::table('coe_roll_numbers as rn')
            ->join('coe_roll_number_allocations as rna', 'rna.pk', '=', 'rn.roll_number_allocation_pk')
            ->where('rna.course_master_pk', $this->course)->where('rna.batch', $this->batch)
            ->orderBy('rn.serial_number')->value('rn.student_master_pk');
        DB::table('coe_laptop_responses')->where('student_master_pk', $ot)->delete();
        $url = route('admin.coe.venue-allocation.drive.laptops', $driveId);
        $before = $this->actingAs($this->admin)->getJson(route('admin.coe.venue-allocation.drive', $driveId))->assertOk()->json('laptop_required');

        $this->actingAs($this->admin)->postJson($url, ['yes' => [$ot]])->assertOk()
            ->assertJsonPath('laptop_required', $before + 1);
        $row = DB::table('coe_laptop_responses')->where('student_master_pk', $ot)->first();
        $this->assertSame([1, LaptopResponse::SOURCE_ADMIN], [(int) $row->laptop_required, (int) $row->source]);
        $this->assertNotEmpty($row->reason);
        $ots = collect($this->actingAs($this->admin)->getJson(route('admin.coe.venue-allocation.drive', $driveId))->json('ots'));
        $this->assertTrue($ots->firstWhere('pk', $ot)['laptop']);

        $this->actingAs($this->admin)->postJson($url, ['no' => [$ot]])->assertOk()->assertJsonPath('laptop_required', $before);
        // Not on the drive, or nothing sent: refused.
        $this->actingAs($this->admin)->postJson($url, ['yes' => [0]])->assertStatus(422);
        $this->actingAs($this->admin)->postJson($url, [])->assertStatus(422);
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
