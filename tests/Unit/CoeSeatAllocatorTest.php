<?php

namespace Tests\Unit;

use App\Models\COE\SeatPlan;
use App\Models\COE\SeatPlanVenue;
use App\Services\COE\SeatAllocator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

/** Venue Allocation: the seating rules and System generated / Manual rooms. No database. */
class CoeSeatAllocatorTest extends TestCase
{
    private function plan(array $rules = []): SeatPlan
    {
        return new SeatPlan($rules + [
            'rule_split_by_laptop' => true,
            'rule_balance_rooms' => false,
            'rule_skip_alternate_seats' => false,
            'rule_odd_even' => false,
            'seat_gap' => 0,
            'seat_start_number' => 1,
            'rule_avoid_same_service' => false,
            'rule_avoid_same_cadre' => false,
            'rule_alternate_gender' => false,
        ]);
    }

    /** One OT per spec, in roll order: [service, cadre, gender]. */
    private function cohort(array $specs): Collection
    {
        return collect($specs)->values()->map(fn (array $s, int $i) => (object) [
            'pk' => $i + 1,
            'roll_number_pk' => $i + 1,
            'roll_number' => 'R' . ($i + 1),
            'serial_number' => $i + 1,
            'service_master_pk' => $s[0],
            'cadre_master_pk' => $s[1] ?? null,
            'gender' => $s[2] ?? null,
            'requires_laptop' => false,
            'requires_scriber' => false,
        ]);
    }

    /** @param array<int, array{0: int, 1: int, 2?: int}> $rooms  [venue_id, capacity, mode] */
    private function seat(SeatPlan $plan, Collection $cohort, array $rooms, array $assigned = []): array
    {
        $planVenues = collect($rooms)->map(fn (array $r, int $i) => new SeatPlanVenue([
            'venue_id' => $r[0],
            'purpose' => SeatPlanVenue::PURPOSE_ANY,
            'seating_mode' => $r[2] ?? SeatPlanVenue::MODE_SYSTEM,
            'display_order' => $i + 1,
        ]));
        $venues = collect($rooms)->mapWithKeys(fn (array $r) => [$r[0] => (object) [
            'venue_id' => $r[0], 'venue_name' => 'Room ' . $r[0], 'capacity' => $r[1],
            'laptop_seat_numbers' => [], 'scriber_seat_numbers' => [],
        ]])->all();

        return (new SeatAllocator())->allocate($plan, $cohort, $planVenues, $venues, [], $assigned);
    }

    /** pk of whoever sits at each desk of a room, in desk order. */
    private function seatOrder(array $result, int $venueId): array
    {
        return collect($result['seats'])->where('venue_id', $venueId)->sortBy('seat_number')->pluck('student_master_pk')->values()->all();
    }

    private function neighboursSharing(array $order, Collection $cohort, string $field): int
    {
        $byPk = $cohort->keyBy('pk');
        $clashes = 0;
        for ($i = 1; $i < count($order); $i++) {
            if ($byPk[$order[$i]]->{$field} === $byPk[$order[$i - 1]]->{$field}) {
                $clashes++;
            }
        }

        return $clashes;
    }

    public function test_with_no_rule_the_seats_follow_roll_order(): void
    {
        $cohort = $this->cohort([[1], [1], [2], [2]]);

        $this->assertSame([1, 2, 3, 4], $this->seatOrder($this->seat($this->plan(), $cohort, [[10, 10]]), 10));
    }

    public function test_same_service_never_sits_side_by_side_when_avoidable(): void
    {
        // Six of service 1 in a row of ten: only possible as 1 x 1 x 1 x 1 x 1 x … from the start.
        $cohort = $this->cohort([[1], [1], [1], [1], [1], [1], [2], [2], [3], [3]]);
        $order = $this->seatOrder($this->seat($this->plan(['rule_avoid_same_service' => true]), $cohort, [[10, 20]]), 10);

        $this->assertCount(10, $order);
        $this->assertSame(1, $this->neighboursSharing($order, $cohort, 'service_master_pk'));
    }

    public function test_a_cohort_already_kept_apart_keeps_its_roll_order(): void
    {
        $cohort = $this->cohort([[1], [2], [1], [3], [2], [1]]);
        $order = $this->seatOrder($this->seat($this->plan(['rule_avoid_same_service' => true]), $cohort, [[10, 20]]), 10);

        $this->assertSame([1, 2, 3, 4, 5, 6], $order);
    }

    public function test_same_cadre_never_sits_side_by_side_when_avoidable(): void
    {
        $cohort = $this->cohort([[1, 7], [2, 7], [3, 7], [4, 8], [5, 8], [6, 9]]);
        $order = $this->seatOrder($this->seat($this->plan(['rule_avoid_same_cadre' => true]), $cohort, [[10, 20]]), 10);

        $this->assertSame(0, $this->neighboursSharing($order, $cohort, 'cadre_master_pk'));
    }

    public function test_female_and_male_alternate(): void
    {
        $cohort = $this->cohort([[1, null, 'Male'], [2, null, 'Male'], [3, null, 'Male'], [4, null, 'Female'], [5, null, 'Female'], [6, null, 'Female']]);
        $order = $this->seatOrder($this->seat($this->plan(['rule_alternate_gender' => true]), $cohort, [[10, 20]]), 10);

        $this->assertSame(0, $this->neighboursSharing($order, $cohort, 'gender'));
    }

    public function test_a_manual_room_seats_only_its_listed_ots_and_the_rest_go_to_system_rooms(): void
    {
        $cohort = $this->cohort([[1], [1], [1], [1], [1], [1]]);
        $result = $this->seat($this->plan(), $cohort, [[10, 20, SeatPlanVenue::MODE_MANUAL], [20, 20]], [2 => 10, 5 => 10]);

        $this->assertSame([2, 5], $this->seatOrder($result, 10));
        $this->assertSame([1, 3, 4, 6], $this->seatOrder($result, 20));
        $this->assertSame(0, $result['summary']['unallocated']);
    }

    public function test_nobody_overflows_into_a_manual_room(): void
    {
        $cohort = $this->cohort([[1], [1], [1], [1]]);
        $result = $this->seat($this->plan(), $cohort, [[10, 20, SeatPlanVenue::MODE_MANUAL]], [1 => 10]);

        $this->assertSame([1], $this->seatOrder($result, 10));
        $this->assertSame(3, $result['summary']['unallocated']);
    }
}
