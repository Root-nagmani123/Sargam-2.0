<?php

namespace App\Services\COE;

use App\Models\COE\SeatPlan;
use App\Models\COE\SeatPlanVenue;
use Illuminate\Support\Collection;

/**
 * Places participants into examination rooms — the Examination module doc's "AUTOMATIC
 * Exam ROOM ALLOCATION".
 *
 * The doc names the inputs: Roll Number, Laptop requirement, Room capacity, Examination,
 * Group, and other configurable rules; and it gives the worked example
 *
 *     Laptop Required     → Room 101, Room 102
 *     Laptop Not Required → Room 103
 *
 * ── Why this is deterministic ──
 * A seating chart is posted on a door and argued about afterwards. Two runs over the same
 * participants, the same rooms and the same rules must produce the same chart, or nobody
 * can answer "why is this candidate in Room 103". So there is no randomness anywhere:
 * participants are consumed in roll-number order and rooms in the order the administrator
 * arranged them, with venue_id breaking any remaining tie.
 *
 * ── Why it returns a shortfall instead of overflowing ──
 * When the chosen rooms cannot hold everybody the allocator seats who it can and RETURNS
 * the rest, rather than squeezing candidates into a room past its capacity. An over-full
 * room is discovered on the morning of the examination; a shortfall reported on screen is
 * discovered while there is still time to add a room.
 *
 * ── The service-separation rule is NOT applied here ──
 * The doc's "participants from the same service are not seated together" is satisfied at
 * roll-number allocation time by RollNumberAllocator, and seats are handed out in
 * roll-number order, so the property carries through to the desks. Re-deriving it here
 * could only disagree with the allocation the roll numbers already encode.
 *
 * ── Group ──
 * The doc lists Group among the inputs. This schema has no examination group mapping for
 * a participant — course_group_type_master carries Language / Tutor / House groups, none
 * of which is an examination grouping — so there is nothing to split on. The room
 * `purpose` column is the configurable hook the day such a mapping exists: a group would
 * become another purpose, and nothing else here has to change.
 */
class SeatAllocator
{
    /**
     * Work out a seating chart.
     *
     * Pure: it reads no database and writes none. The controller loads the participants
     * and the rooms, hands them over, and persists whatever comes back — which is what
     * makes the algorithm testable and makes a Preview cost exactly the same as a run.
     *
     * @param  Collection<int, object>  $participants  in the order they should be seated;
     *                                                 each needs pk, roll_number_pk,
     *                                                 roll_number, requires_laptop
     * @param  Collection<int, SeatPlanVenue>  $planVenues  the plan's rooms, in fill order
     * @param  array<int, object>  $venuesById  venue_master rows keyed by venue_id
     * @param  array<int, array{venue_id: int, seat_number: int}>  $manualSeats  keyed by
     *         student_master_pk — seats an administrator placed by hand, which this run
     *         honours rather than overwrites
     * @return array{
     *     seats: array<int, array{student_master_pk:int, roll_number_pk:int|null, venue_id:int, seat_number:int, requires_laptop:bool}>,
     *     unplaced: array<int, object>,
     *     rooms: array<int, array<string, mixed>>,
     *     summary: array<string, mixed>
     * }
     */
    public function allocate(
        SeatPlan $plan,
        Collection $participants,
        Collection $planVenues,
        array $venuesById,
        array $manualSeats = []
    ): array {
        $rooms = $this->buildRooms($plan, $planVenues, $venuesById, $manualSeats);

        if ($plan->rule_odd_even) {
            // Odd serials first, then even, each half still in roll-number order: two
            // neighbouring desks then never hold consecutive roll numbers.
            [$odd, $even] = $participants->partition(fn (object $p) => ((int) ($p->serial_number ?? 0)) % 2 === 1);
            $participants = $odd->concat($even)->values();
        }

        // Participants an administrator has already placed keep their desk and are not
        // re-seated — that is the whole point of an override (doc: "Allow manual override
        // by authorized administrator"). Their seats are already reserved in $rooms.
        [$manual, $automatic] = $participants->partition(
            fn (object $participant) => isset($manualSeats[(int) $participant->pk])
        );

        $seats = [];
        foreach ($manual as $participant) {
            $placed = $manualSeats[(int) $participant->pk];
            $seats[] = [
                'student_master_pk' => (int) $participant->pk,
                'roll_number_pk' => $participant->roll_number_pk === null ? null : (int) $participant->roll_number_pk,
                'venue_id' => (int) $placed['venue_id'],
                'seat_number' => (int) $placed['seat_number'],
                'requires_laptop' => (bool) $participant->requires_laptop,
                'requires_scriber' => (bool) ($participant->requires_scriber ?? false),
            ];
        }

        $unplaced = [];

        /*
         * The doc's worked example, in two passes.
         *
         * Laptop-needing participants are placed FIRST, because the rooms that can take
         * them are the scarce resource: a room with laptop capacity will also take
         * somebody who does not need one, but not the reverse. Filling the non-laptop half
         * first would hand those rooms away and strand the candidates who had no
         * alternative.
         */
        if ($plan->rule_split_by_laptop) {
            [$needLaptop, $rest] = $automatic->partition(fn (object $p) => (bool) $p->requires_laptop);

            foreach ([[$needLaptop, true], [$rest, false]] as [$pool, $requiresLaptop]) {
                $result = $this->place($plan, $pool, $rooms, $requiresLaptop);
                $rooms = $result['rooms'];
                $seats = array_merge($seats, $result['seats']);
                $unplaced = array_merge($unplaced, $result['unplaced']);
            }
        } else {
            // One pool: every room takes whoever is next, and requires_laptop is recorded
            // against the participant but does not steer the room.
            $result = $this->place($plan, $automatic, $rooms, null);
            $rooms = $result['rooms'];
            $seats = array_merge($seats, $result['seats']);
            $unplaced = $result['unplaced'];
        }

        return [
            'seats' => $seats,
            'unplaced' => $unplaced,
            'rooms' => $this->roomReport($rooms),
            'summary' => $this->summary($plan, $participants, $seats, $unplaced, $rooms, count($manualSeats)),
        ];
    }

    /**
     * The rooms this run may fill, with the seats a manual override has already spoken for
     * removed from what is available.
     *
     * @param  Collection<int, SeatPlanVenue>  $planVenues
     * @param  array<int, object>  $venuesById
     * @param  array<int, array{venue_id: int, seat_number: int}>  $manualSeats
     * @return array<int, array<string, mixed>>  keyed by venue_id
     */
    private function buildRooms(SeatPlan $plan, Collection $planVenues, array $venuesById, array $manualSeats): array
    {
        $step = $plan->seatStep();
        $start = max(1, (int) $plan->seat_start_number);

        $rooms = [];

        foreach ($this->sortVenues($planVenues) as $planVenue) {
            $venueId = (int) $planVenue->venue_id;
            $venue = $venuesById[$venueId] ?? null;

            /*
             * Two different capacities, and conflating them is how a room ends up
             * double-booked.
             *
             * `physical` is how many desks the room has — it sets how far the seat
             * NUMBERS may run, because seat 31 does not exist in a 30-desk room.
             * `capacity` is how many candidates may sit in it, which is half that (rounded
             * up) when every other desk is left empty. A 30-desk room takes 15 candidates
             * at seats 1, 3 … 29.
             */
            $physical = $planVenue->effectiveCapacity($venue);
            $usable = $step === 1 ? $physical : intdiv($physical + $step - 1, $step);
            $lastSeat = $start + $physical - 1;

            // Desks the Laptop & Scriber Seats master marked on the seat map. Empty for a
            // room nobody has configured, in which case seats are handed out in plain
            // number order exactly as before.
            $inRoom = fn ($seats) => array_values(array_filter(
                array_map('intval', (array) $seats),
                fn (int $n) => $n >= $start && $n <= $lastSeat
            ));
            $laptopSeats = $inRoom($venue->laptop_seat_numbers ?? []);
            $scriberSeats = array_values(array_diff($inRoom($venue->scriber_seat_numbers ?? []), $laptopSeats));

            $rooms[$venueId] = [
                'venue_id' => $venueId,
                'name' => (string) ($venue->venue_name ?? ('Venue #' . $venueId)),
                'purpose' => (int) $planVenue->purpose,
                'capacity' => $usable,
                'physical_capacity' => $physical,
                'model' => $planVenue,
                // Seat numbers already spoken for. A manual override may sit on a number
                // this run would otherwise hand out, and the unique key on
                // (plan, venue, seat) means a collision is a failed INSERT, not a warning.
                'taken' => [],
                'placed' => 0,
                'manual' => 0,
                'cursor' => $start,
                'step' => $step,
                'start' => $start,
                'laptop_seats' => $laptopSeats,
                'scriber_seats' => $scriberSeats,
                'reserved' => array_fill_keys(array_merge($laptopSeats, $scriberSeats), true),
            ];
        }

        foreach ($manualSeats as $placed) {
            $venueId = (int) $placed['venue_id'];
            if (! isset($rooms[$venueId])) {
                // A hand-placed participant in a room that has since been removed from the
                // plan. The seat is left alone — the screen lists it as being outside the
                // plan's rooms rather than this run silently moving somebody.
                continue;
            }

            $rooms[$venueId]['taken'][(int) $placed['seat_number']] = true;
            $rooms[$venueId]['manual']++;
            // A manual seat consumes capacity: the desk is occupied whoever chose it.
            $rooms[$venueId]['placed']++;
        }

        return $rooms;
    }

    /**
     * Rooms in the order they are filled.
     *
     * display_order is what the administrator arranged; venue_id breaks the tie so the
     * order never depends on how the rows came back from the database.
     *
     * @param  Collection<int, SeatPlanVenue>  $planVenues
     * @return array<int, SeatPlanVenue>
     */
    private function sortVenues(Collection $planVenues): array
    {
        return $planVenues
            ->sortBy([
                fn ($a, $b) => (int) $a->display_order <=> (int) $b->display_order,
                fn ($a, $b) => (int) $a->venue_id <=> (int) $b->venue_id,
            ])
            ->values()
            ->all();
    }

    /**
     * Seat one pool of participants.
     *
     * @param  Collection<int, object>  $pool
     * @param  array<int, array<string, mixed>>  $rooms
     * @param  bool|null  $requiresLaptop  null when the plan is not splitting by laptop,
     *                                     in which case every room is eligible
     * @return array{seats: array<int, array<string, mixed>>, unplaced: array<int, object>, rooms: array<int, array<string, mixed>>}
     */
    private function place(SeatPlan $plan, Collection $pool, array $rooms, ?bool $requiresLaptop): array
    {
        $seats = [];
        $unplaced = [];

        if ($pool->isEmpty()) {
            return ['seats' => $seats, 'unplaced' => $unplaced, 'rooms' => $rooms];
        }

        $eligible = $this->eligibleRoomOrder($rooms, $requiresLaptop);

        if ($eligible === []) {
            // No room accepts this pool at all — every participant in it is a shortfall,
            // and the report says which kind so the administrator knows what to add.
            return ['seats' => $seats, 'unplaced' => $pool->values()->all(), 'rooms' => $rooms];
        }

        $cursor = 0;
        // Rooms with no free desk of the kind this pool needs. Kept per pass, not written
        // into the room: a room out of laptop desks still has plain desks for the next pass.
        $exhausted = [];

        foreach ($pool as $participant) {
            $venueId = null;
            $seatNumber = null;
            $needsScriber = (bool) ($participant->requires_scriber ?? false);

            /*
             * Bounded retry, not a single attempt.
             *
             * A room can report free capacity and still have no free seat NUMBER — a
             * manual override sitting on a high number inside a small room does exactly
             * that. Dropping the participant there would report a shortfall while other
             * rooms stood empty, so the room is marked full and the next one is tried. The
             * loop can only run once per eligible room, because every failed attempt
             * fills one.
             */
            for ($attempt = 0, $limit = count($eligible); $attempt < $limit; $attempt++) {
                $open = array_values(array_diff($eligible, $exhausted));
                $candidate = $plan->rule_balance_rooms
                    ? $this->nextBalancedRoom($rooms, $open, $cursor)
                    : $this->nextSequentialRoom($rooms, $open);

                if ($candidate === null) {
                    break;
                }

                $seatNumber = $this->takeSeat($rooms[$candidate], (bool) $participant->requires_laptop, $needsScriber);

                if ($seatNumber !== null) {
                    $venueId = $candidate;
                    break;
                }

                $exhausted[] = $candidate;
            }

            if ($venueId === null || $seatNumber === null) {
                $unplaced[] = $participant;
                continue;
            }

            $rooms[$venueId]['taken'][$seatNumber] = true;
            $rooms[$venueId]['placed']++;

            $seats[] = [
                'student_master_pk' => (int) $participant->pk,
                'roll_number_pk' => $participant->roll_number_pk === null ? null : (int) $participant->roll_number_pk,
                'venue_id' => $venueId,
                'seat_number' => $seatNumber,
                'requires_laptop' => (bool) $participant->requires_laptop,
                'requires_scriber' => $needsScriber,
            ];
        }

        return ['seats' => $seats, 'unplaced' => $unplaced, 'rooms' => $rooms];
    }

    /**
     * Which rooms this pool may use, in the order it should use them.
     *
     * Rooms dedicated to the pool come before the "Either" rooms. That ordering is what
     * makes the two passes cooperate: the laptop pass exhausts the laptop-only rooms
     * before it starts borrowing shared ones, leaving as much shared space as possible for
     * the pass that has no dedicated rooms of its own.
     *
     * @param  array<int, array<string, mixed>>  $rooms
     * @return array<int, int>  venue ids
     */
    private function eligibleRoomOrder(array $rooms, ?bool $requiresLaptop): array
    {
        $dedicated = [];
        $shared = [];

        $wanted = $requiresLaptop === true
            ? SeatPlanVenue::PURPOSE_LAPTOP
            : SeatPlanVenue::PURPOSE_NON_LAPTOP;

        foreach ($rooms as $venueId => $room) {
            // A room configured for nobody to sit in is not a candidate; it would only
            // produce seats the allocator immediately has to take back.
            if ($room['capacity'] <= 0) {
                continue;
            }

            if ($requiresLaptop === null) {
                $shared[] = $venueId;
                continue;
            }

            if (! $room['model']->accepts($requiresLaptop)) {
                continue;
            }

            if ($room['purpose'] === $wanted) {
                $dedicated[] = $venueId;
            } else {
                $shared[] = $venueId;
            }
        }

        // Both halves are already in fill order, because $rooms was built in it.
        return array_merge($dedicated, $shared);
    }

    /**
     * Fill each room to capacity before opening the next.
     *
     * @param  array<int, array<string, mixed>>  $rooms
     * @param  array<int, int>  $eligible
     */
    private function nextSequentialRoom(array $rooms, array $eligible): ?int
    {
        foreach ($eligible as $venueId) {
            if ($rooms[$venueId]['placed'] < $rooms[$venueId]['capacity']) {
                return $venueId;
            }
        }

        return null;
    }

    /**
     * Spread participants across the rooms instead of packing them.
     *
     * Round-robin from a cursor that keeps moving, so the rooms stay within one of each
     * other until the small ones fill up and the remainder flows into the large ones. The
     * cursor is by reference because the caller's position has to survive between
     * participants — restarting at room 1 every time would pack the first room after all.
     *
     * @param  array<int, array<string, mixed>>  $rooms
     * @param  array<int, int>  $eligible
     */
    private function nextBalancedRoom(array $rooms, array $eligible, int &$cursor): ?int
    {
        $count = count($eligible);

        for ($i = 0; $i < $count; $i++) {
            $venueId = $eligible[($cursor + $i) % $count];

            if ($rooms[$venueId]['placed'] < $rooms[$venueId]['capacity']) {
                $cursor = ($cursor + $i + 1) % $count;

                return $venueId;
            }
        }

        return null;
    }

    /**
     * The next free seat number in a room for this participant.
     *
     * When the room has a seat map (Laptop & Scriber Seats master):
     *   laptop candidate  → only a laptop desk; none left means this room is full for them
     *   scriber candidate → a scriber desk first, then a plain one
     *   anyone else       → a plain desk first, then any free reserved desk, because a
     *                       spare laptop desk seats a pen-and-paper candidate perfectly well
     * Without a seat map every desk is plain and numbers go out in order, stepping over
     * desks left empty by the gap rule. The marked desks are not stepped: they are where
     * the admin said the laptops are.
     *
     * Returns null when no suitable desk is left — see the caller for why that is not the
     * same as the room being full.
     *
     * @param  array<string, mixed>  $room
     */
    private function takeSeat(array &$room, bool $needsLaptop = false, bool $needsScriber = false): ?int
    {
        $free = fn (array $list) => current(array_filter($list, fn (int $n) => ! isset($room['taken'][$n])));

        if ($needsLaptop && $room['laptop_seats'] !== []) {
            return $free($room['laptop_seats']) ?: null;
        }

        if ($needsScriber && $room['scriber_seats'] !== []) {
            $seat = $free($room['scriber_seats']);
            if ($seat) {
                return $seat;
            }
        }

        $plain = $this->nextPlainSeat($room);
        if ($plain !== null || $room['reserved'] === []) {
            return $plain;
        }

        return $free(array_merge($room['scriber_seats'], $room['laptop_seats'])) ?: null;
    }

    /**
     * The next free unreserved desk, walking from the cursor so a 200-desk room does not
     * cost 200 checks per candidate. Bounded by the DESKS, not by how many candidates may
     * use them: numbering stops at the last desk that physically exists.
     *
     * @param  array<string, mixed>  $room
     */
    private function nextPlainSeat(array &$room): ?int
    {
        $step = (int) $room['step'];
        $limit = (int) $room['start'] + (int) $room['physical_capacity'] - 1;

        for ($seat = (int) $room['cursor']; $seat <= $limit; $seat += $step) {
            if (! isset($room['taken'][$seat]) && ! isset($room['reserved'][$seat])) {
                $room['cursor'] = $seat + $step;

                return $seat;
            }
        }

        return null;
    }

    /**
     * Per-room outcome, for the screen and the print-out.
     *
     * @param  array<int, array<string, mixed>>  $rooms
     * @return array<int, array<string, mixed>>
     */
    private function roomReport(array $rooms): array
    {
        $report = [];

        foreach ($rooms as $venueId => $room) {
            $report[] = [
                'venue_id' => $venueId,
                'name' => $room['name'],
                'purpose' => $room['purpose'],
                'purpose_label' => SeatPlanVenue::purposeLabel($room['purpose']),
                // The seatable figure, which is what the administrator is planning
                // against; the desk count is carried alongside so the screen can explain
                // the difference when alternate seats are being skipped.
                'capacity' => $room['capacity'],
                'physical_capacity' => $room['physical_capacity'],
                'allocated' => $room['placed'],
                'manual' => $room['manual'],
                'free' => max(0, $room['capacity'] - $room['placed']),
            ];
        }

        return $report;
    }

    /**
     * What the run achieved, in the words the screen uses.
     *
     * The shortfall is split by kind because the two have different fixes: too few laptop
     * desks is solved by adding a room with laptop capacity, too few plain desks by any
     * room at all, and "no eligible room" by changing a room's purpose.
     *
     * @param  Collection<int, object>  $participants
     * @param  array<int, array<string, mixed>>  $seats
     * @param  array<int, object>  $unplaced
     * @param  array<int, array<string, mixed>>  $rooms
     * @return array<string, mixed>
     */
    private function summary(
        SeatPlan $plan,
        Collection $participants,
        array $seats,
        array $unplaced,
        array $rooms,
        int $manualCount
    ): array {
        $unplacedLaptop = 0;
        foreach ($unplaced as $participant) {
            if ((bool) $participant->requires_laptop) {
                $unplacedLaptop++;
            }
        }

        $capacity = 0;
        $laptopCapacity = 0;
        foreach ($rooms as $room) {
            $capacity += $room['capacity'];
            if ($room['model']->accepts(true)) {
                $laptopCapacity += $room['capacity'];
            }
        }

        return [
            'participants' => $participants->count(),
            'allocated' => count($seats),
            'unallocated' => count($unplaced),
            'unallocated_laptop' => $unplacedLaptop,
            'unallocated_other' => count($unplaced) - $unplacedLaptop,
            'manual' => $manualCount,
            'rooms' => count($rooms),
            'capacity' => $capacity,
            'laptop_capacity' => $laptopCapacity,
            'requires_laptop' => $participants->filter(fn (object $p) => (bool) $p->requires_laptop)->count(),
            'rules' => [
                'split_by_laptop' => (bool) $plan->rule_split_by_laptop,
                'balance_rooms' => (bool) $plan->rule_balance_rooms,
                'skip_alternate_seats' => (bool) $plan->rule_skip_alternate_seats,
                'odd_even' => (bool) $plan->rule_odd_even,
                'seat_gap' => (int) $plan->seat_gap,
            ],
        ];
    }
}
