<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Models\COE\VenueSeatLayout;
use App\Models\VenueMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * COE → Master Data → Laptop & Scriber Seats Master.
 *
 * One row per examination room (venue_master with a building, a floor and a
 * capacity) that has a seat layout in coe_venue_seat_layouts. The admin picks the
 * laptop desks and the scriber desks on a seat map; seat numbers run
 * 1..venue_master.capacity and use the numbering of public/js/coe-seat-map.js.
 *
 * Every save keeps venue_master.laptop_capacity equal to the number of laptop
 * seats; deleting a layout sets it back to NULL.
 */
class LaptopScriberSeatsController extends Controller
{
    public function index()
    {
        $layouts = VenueSeatLayout::with(['venue.building', 'venue.floor'])->get()
            ->filter(fn (VenueSeatLayout $l) => $l->venue !== null);

        $rows = $layouts->map(function (VenueSeatLayout $l) {
            $venue = $l->venue;
            $laptop = $l->laptopSeatNumbers();
            $scriber = $l->scriberSeatNumbers();

            return [
                'venue_id'      => (int) $venue->venue_id,
                'room'          => (string) $venue->venue_name,
                'building'      => (string) optional($venue->building)->building_name,
                'floor'         => (string) optional($venue->floor)->floor_name,
                'capacity'      => (int) $venue->capacity,
                'laptop'        => $laptop,
                'scriber'       => $scriber,
                'laptop_seats'  => count($laptop),
                'scriber_seats' => count($scriber),
            ];
        })->sortBy([['building', 'asc'], ['room', 'asc']])->values();

        $rooms = $this->examRooms($layouts->pluck('venue_id')->all());

        return view('admin.coe.laptop_scriber_seats.index', [
            'rows'            => $rows,
            'rooms'           => $rooms,
            'buildingFilters' => $rows->pluck('building')->filter()->unique()->sort()->values(),
            'floorFilters'    => $rows->pluck('floor')->filter()->unique()->sort()->values(),
        ]);
    }

    /**
     * Create or replace the seat layout of one room (keyed by venue_id).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'venue_id'        => ['required', 'integer'],
            'laptop_seats'    => ['required', 'array', 'min:1'],
            'laptop_seats.*'  => ['required', 'integer', 'distinct'],
            'scriber_seats'   => ['nullable', 'array'],
            'scriber_seats.*' => ['required', 'integer', 'distinct'],
        ], [
            'laptop_seats.required' => 'Select at least one laptop seat.',
            'laptop_seats.min'      => 'Select at least one laptop seat.',
        ]);

        $venueId = (int) $data['venue_id'];
        // Range is checked on the submitted ints; normalise() would silently drop 0 / negatives.
        $laptop = array_values(array_unique(array_map('intval', $data['laptop_seats'])));
        $scriber = array_values(array_unique(array_map('intval', $data['scriber_seats'] ?? [])));

        $created = DB::transaction(function () use ($venueId, $laptop, $scriber) {
            // Lock the room so two saves for the same venue cannot race on the
            // unique venue_id row or on laptop_capacity.
            $venue = VenueMaster::whereKey($venueId)->lockForUpdate()->first();
            $layout = VenueSeatLayout::where('venue_id', $venueId)->first();

            $this->assertSeatsFit($venue, $layout, $laptop, $scriber);
            $laptop = VenueSeatLayout::normalise($laptop);
            $scriber = VenueSeatLayout::normalise($scriber);

            $userId = auth()->id();
            if ($layout) {
                $layout->fill([
                    'laptop_seats'  => $laptop,
                    'scriber_seats' => $scriber,
                    'modified_by'   => $userId,
                    'modified_date' => now(),
                ])->save();
            } else {
                $layout = VenueSeatLayout::create([
                    'venue_id'      => $venueId,
                    'laptop_seats'  => $laptop,
                    'scriber_seats' => $scriber,
                    'created_by'    => $userId,
                    'created_date'  => now(),
                ]);
            }

            $venue->laptop_capacity = count($laptop);
            $venue->save();

            return $layout->wasRecentlyCreated;
        });

        return response()->json([
            'success' => true,
            'message' => $created ? 'Laptop & scriber seats added.' : 'Laptop & scriber seats updated.',
        ]);
    }

    /**
     * Remove the seat layout of one room (venue = venue_master.venue_id).
     */
    public function destroy(int $venue): JsonResponse
    {
        $deleted = DB::transaction(function () use ($venue) {
            $room = VenueMaster::whereKey($venue)->lockForUpdate()->first();
            $count = VenueSeatLayout::where('venue_id', $venue)->delete();

            if ($count && $room) {
                $room->laptop_capacity = null;
                $room->save();
            }

            return $count > 0;
        });

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Seat layout not found.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Laptop & scriber seats deleted.']);
    }

    /**
     * @param  list<int>  $laptop
     * @param  list<int>  $scriber
     */
    private function assertSeatsFit(?VenueMaster $venue, ?VenueSeatLayout $layout, array $laptop, array $scriber): void
    {
        if (!$venue || !$this->isExamRoom($venue)) {
            throw ValidationException::withMessages([
                'venue_id' => 'Select an examination room (a room with a building, a floor and a capacity).',
            ]);
        }
        // A new layout only for an active room; an existing one stays editable.
        if (!$layout && (int) $venue->active_inactive !== 1) {
            throw ValidationException::withMessages(['venue_id' => 'This room is inactive.']);
        }

        $capacity = (int) $venue->capacity;
        $errors = [];

        $outside = fn (array $seats) => array_values(array_filter($seats, fn (int $n) => $n < 1 || $n > $capacity));
        if ($bad = $outside($laptop)) {
            $errors['laptop_seats'] = 'Laptop seat(s) ' . implode(', ', $bad) . " are outside this room (1-{$capacity}).";
        }
        if ($bad = $outside($scriber)) {
            $errors['scriber_seats'] = 'Scriber seat(s) ' . implode(', ', $bad) . " are outside this room (1-{$capacity}).";
        }
        if ($both = array_values(array_intersect($laptop, $scriber))) {
            $errors['scriber_seats'] = 'Seat(s) ' . implode(', ', $both) . ' cannot be both a laptop and a scriber seat.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function isExamRoom(VenueMaster $venue): bool
    {
        return $venue->building_master_pk && $venue->floor_master_pk && (int) $venue->capacity > 0;
    }

    /**
     * Rooms the Add / Edit modal can offer: every active examination room in an
     * active building and on an active floor, plus any room that already has a
     * layout (so it can still be edited after the room was deactivated).
     *
     * @param  list<int>  $withLayout
     * @return Collection<int, array{id:int, name:string, building_pk:int, building:string,
     *     floor_pk:int, floor:string, floor_order:int, capacity:int}>
     */
    private function examRooms(array $withLayout): Collection
    {
        return VenueMaster::with(['building', 'floor'])
            ->whereNotNull('building_master_pk')
            ->whereNotNull('floor_master_pk')
            ->where('capacity', '>', 0)
            ->where(function ($q) use ($withLayout) {
                $q->where(function ($q) {
                    $q->where('active_inactive', 1)
                        ->whereHas('building', fn ($b) => $b->where('active_inactive', 1))
                        ->whereHas('floor', fn ($f) => $f->where('active_inactive', 1));
                });
                if ($withLayout) {
                    $q->orWhereIn('venue_id', $withLayout);
                }
            })
            ->get()
            ->filter(fn (VenueMaster $v) => $v->building && $v->floor)
            ->map(fn (VenueMaster $v) => [
                'id'          => (int) $v->venue_id,
                'name'        => (string) $v->venue_name,
                'building_pk' => (int) $v->building->pk,
                'building'    => (string) $v->building->building_name,
                'floor_pk'    => (int) $v->floor->pk,
                'floor'       => (string) $v->floor->floor_name,
                'floor_order' => (int) $v->floor->display_order,
                'capacity'    => (int) $v->capacity,
            ])
            ->sortBy([['building', 'asc'], ['floor_order', 'asc'], ['name', 'asc']])
            ->values();
    }
}
