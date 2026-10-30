<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Models\COE\SeatAllocation;
use App\Models\COE\SeatPlan;
use App\Models\COE\SeatPlanVenue;
use App\Models\COE\VenueSeatLayout;
use App\Services\COE\SeatAllocator;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * COE → Examination Management → Venue Allocation.
 *
 * Built on the seat-plan tables (coe_seat_plans / coe_seat_plan_venues /
 * coe_seat_allocations) and SeatAllocator. One grid row is one ROOM of one plan;
 * a plan is a course + batch + examination component + candidate category, and
 * "Map Seating Plan" adds a room to that plan (creating the plan the first time)
 * and re-runs the allocator over all of its rooms.
 *
 *   index           — allocation list (Active / Archived = course still running / finished)
 *   edit/{id}       — seat map of one room, with seat swap
 *
 * Status on screen: Pending = plan not yet published, Frozen = published (OTs
 * have been told their seat). A frozen plan cannot be changed.
 */
class VenueAllocationController extends Controller
{
    public const SCOPES = ['active', 'archived'];

    /** Must match public/js/coe-seat-map.js — desks per row, per block. */
    private const SEAT_BLOCKS = ['A' => 4, 'B' => 13, 'C' => 6];

    public function index(Request $request)
    {
        $scope = in_array($request->query('scope'), self::SCOPES, true)
            ? $request->query('scope') : 'active';

        $rows = $this->allocationRows($scope);
        $rooms = $this->examRooms();

        return view('admin.coe.venue_allocation.index', [
            'scope' => $scope,
            'rows' => $rows,
            'filterOptions' => [
                'course' => $rows->pluck('course')->unique()->sort()->values(),
                'batch' => $rows->pluck('batch')->unique()->sortDesc()->values(),
                'category' => $rows->pluck('category')->unique()->sort()->values(),
                'subject' => $rows->pluck('subject')->unique()->sort()->values(),
                'component' => $rows->pluck('component')->unique()->sort()->values(),
            ],
            'courseBatches' => $this->courseBatches(),
            'categories' => SeatPlan::categories(),
            'laptopRequirements' => SeatPlan::laptopRequirements(),
            'buildings' => $rooms->unique('building_pk')->sortBy('building')
                ->map(fn ($r) => ['id' => $r->building_pk, 'name' => $r->building])->values(),
            'roomsByBuildingFloor' => $this->roomTree($rooms),
        ]);
    }

    /**
     * Everything the Map dialog needs once a course + batch is picked: the components
     * the course's examination drives schedule, and how many OTs each candidate
     * category holds (with their laptop / scriber answers, so the dialog can show
     * the laptop count for whichever seating policy is chosen).
     */
    public function components(Request $request): JsonResponse
    {
        [$courseId, $batch] = $this->splitCourseBatch((string) $request->query('course_batch', ''));
        if (! $this->courseBatches()->contains('key', $courseId . ':' . $batch)) {
            return response()->json(['status' => 'error', 'message' => 'Generate roll numbers for this course first.'], 422);
        }

        $components = DB::table('examination_drive_component_maps as dcm')
            ->join('examination_drives as ed', 'ed.id', '=', 'dcm.examination_drive_id')
            ->join('component_master as comp', 'comp.pk', '=', 'dcm.component_master_pk')
            ->leftJoin('subject_master as sub', 'sub.pk', '=', 'dcm.subject_master_pk')
            ->where('ed.course_master_pk', $courseId)
            ->orderBy('sub.subject_name')
            ->orderBy('comp.component_name')
            ->get(['dcm.id', 'dcm.exam_date', 'ed.drive_name', 'sub.subject_name', 'comp.component_name'])
            ->map(fn ($c) => [
                'id' => (int) $c->id,
                'subject' => $c->subject_name ?: 'General',
                'component' => $c->component_name,
                'label' => $c->component_name
                    . ($c->drive_name ? ' — ' . $c->drive_name : '')
                    . ($c->exam_date ? ' (' . date('d M Y', strtotime($c->exam_date)) . ')' : ''),
            ]);

        $counts = [];
        foreach (array_keys(SeatPlan::categories()) as $category) {
            $row = $this->participantQuery($courseId, $batch, $category)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN lr.laptop_required = 1 THEN 1 ELSE 0 END) as declared_yes')
                ->selectRaw('SUM(CASE WHEN lr.laptop_required = 0 THEN 1 ELSE 0 END) as declared_no')
                ->selectRaw('SUM(CASE WHEN lr.scriber_required = 1 THEN 1 ELSE 0 END) as scriber')
                ->first();
            $counts[$category] = [
                'total' => (int) $row->total,
                'declared_yes' => (int) $row->declared_yes,
                'declared_no' => (int) $row->declared_no,
                'undeclared' => (int) $row->total - (int) $row->declared_yes - (int) $row->declared_no,
                'scriber' => (int) $row->scriber,
            ];
        }

        return response()->json(['status' => 'success', 'components' => $components, 'counts' => $counts]);
    }

    /** Map Seating Plan: add a room to the plan for this course/batch/component/category, then seat everyone. */
    public function store(Request $request, SeatAllocator $allocator): JsonResponse
    {
        $mapping = $this->resolveMapping($request);

        try {
            $summary = DB::transaction(fn () => $this->applyMapping($mapping, $allocator)['summary']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('COE venue allocation failed', ['exception' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => 'The seating could not be saved. Nothing was changed.'], 500);
        }

        return response()->json([
            'status' => $summary['unallocated'] > 0 ? 'warning' : 'success',
            'message' => $this->allocationMessage($summary),
        ]);
    }

    /**
     * Validate a Map Seating Plan request and look up everything it names, refusing it
     * before anything is written.
     *
     * @return array{validated: array, courseId: int, batch: int, component: object, room: object, category: int, plan: ?SeatPlan}
     *
     * @throws ValidationException
     */
    private function resolveMapping(Request $request): array
    {
        $validated = $request->validate([
            'course_batch' => ['required', 'regex:/^\d+:\d+$/'],
            'component_map_id' => ['required', 'integer'],
            'candidate_category' => ['required', Rule::in(array_keys(SeatPlan::categories()))],
            'laptop_requirement' => ['required', Rule::in(array_keys(SeatPlan::laptopRequirements()))],
            'venue_id' => ['required', 'integer'],
            'rule_odd_even' => ['nullable', 'boolean'],
            'seat_gap' => ['nullable', 'integer', 'min:0', 'max:10'],
        ], [
            'course_batch.required' => 'Select a course.',
            'component_map_id.required' => 'Select a component.',
            'venue_id.required' => 'Select a room.',
        ]);

        [$courseId, $batch] = $this->splitCourseBatch($validated['course_batch']);
        if (! $this->courseBatches()->contains('key', $courseId . ':' . $batch)) {
            throw ValidationException::withMessages(['course_batch' => 'This course has no roll numbers yet. Generate roll numbers first.']);
        }

        $component = DB::table('examination_drive_component_maps as dcm')
            ->join('examination_drives as ed', 'ed.id', '=', 'dcm.examination_drive_id')
            ->join('component_master as comp', 'comp.pk', '=', 'dcm.component_master_pk')
            ->leftJoin('subject_master as sub', 'sub.pk', '=', 'dcm.subject_master_pk')
            ->where('dcm.id', $validated['component_map_id'])
            ->where('ed.course_master_pk', $courseId)
            ->first(['dcm.id', 'dcm.exam_date', 'sub.subject_name', 'comp.component_name']);
        if ($component === null) {
            throw ValidationException::withMessages(['component_map_id' => 'That component is not scheduled for this course.']);
        }

        $room = $this->examRooms()->firstWhere('venue_id', (int) $validated['venue_id']);
        if ($room === null) {
            throw ValidationException::withMessages(['venue_id' => 'That room is not an examination room with a capacity.']);
        }

        $category = (int) $validated['candidate_category'];
        $existing = SeatPlan::where('course_master_pk', $courseId)
            ->where('batch', $batch)
            ->where('examination_drive_component_map_id', $component->id)
            ->get();

        // General + PH split the cohort; "All" overlaps both. Two overlapping plans for the
        // same paper would give one OT two desks.
        $clash = $existing->first(fn (SeatPlan $p) => (int) $p->candidate_category !== $category
            && ((int) $p->candidate_category === SeatPlan::CATEGORY_ALL || $category === SeatPlan::CATEGORY_ALL));
        if ($clash) {
            throw ValidationException::withMessages(['candidate_category' => 'This component is already mapped for '
                . SeatPlan::categoryLabel((int) $clash->candidate_category) . '. Use that category, or delete its rooms first.']);
        }

        $plan = $existing->firstWhere('candidate_category', $category);
        if ($plan && $plan->isPublished()) {
            throw ValidationException::withMessages(['component_map_id' => 'This seating plan is frozen and cannot take another room.']);
        }
        if ($plan && SeatPlanVenue::where('seat_plan_pk', $plan->pk)->where('venue_id', $room->venue_id)->exists()) {
            throw ValidationException::withMessages(['venue_id' => 'This room is already part of this seating plan.']);
        }

        // One room, one paper at a time: refuse a room another plan already holds on the same date.
        if ($component->exam_date) {
            $busy = DB::table('coe_seat_plan_venues as spv')
                ->join('coe_seat_plans as sp', 'sp.pk', '=', 'spv.seat_plan_pk')
                ->where('spv.venue_id', $room->venue_id)
                ->where('sp.exam_date', $component->exam_date)
                ->when($plan, fn ($q) => $q->where('sp.pk', '!=', $plan->pk))
                ->exists();
            if ($busy) {
                throw ValidationException::withMessages(['venue_id' => 'This room is already allocated to another examination on '
                    . date('d M Y', strtotime($component->exam_date)) . '.']);
            }
        }

        return compact('validated', 'courseId', 'batch', 'component', 'room', 'category', 'plan');
    }

    /**
     * Add the room to the plan (creating the plan if needed) and re-seat everyone.
     * Caller owns the transaction.
     *
     * @param  array<string, mixed>  $mapping  from resolveMapping()
     * @return array{plan: SeatPlan, summary: array<string, mixed>}
     */
    private function applyMapping(array $mapping, SeatAllocator $allocator): array
    {
        ['validated' => $validated, 'courseId' => $courseId, 'batch' => $batch, 'component' => $component,
            'room' => $room, 'category' => $category, 'plan' => $plan] = $mapping;

        $now = now();
        $plan ??= new SeatPlan([
            'course_master_pk' => $courseId,
            'batch' => $batch,
            'examination_drive_component_map_id' => $component->id,
            'candidate_category' => $category,
            'plan_name' => mb_substr(($component->subject_name ?: 'General') . ' / ' . $component->component_name
                . ' · ' . SeatPlan::categoryLabel($category) . ' #' . $component->id, 0, 150),
            'exam_name' => $component->component_name,
            'subject_name' => $component->subject_name,
            'exam_date' => $component->exam_date,
            'rule_split_by_laptop' => true,
            'rule_balance_rooms' => false,
            'rule_skip_alternate_seats' => false,
            'seat_start_number' => 1,
            'created_by' => Auth::id(),
            'created_date' => $now,
        ]);
        // The latest dialog's rules apply to the whole plan — it is re-seated below.
        $plan->fill([
            'laptop_requirement' => (int) $validated['laptop_requirement'],
            'rule_odd_even' => (bool) ($validated['rule_odd_even'] ?? false),
            'seat_gap' => (int) ($validated['seat_gap'] ?? 0),
            'modified_by' => Auth::id(),
            'modified_date' => $now,
        ])->save();

        SeatPlanVenue::create([
            'seat_plan_pk' => $plan->pk,
            'venue_id' => $room->venue_id,
            'purpose' => SeatPlanVenue::PURPOSE_ANY,
            'display_order' => (int) SeatPlanVenue::where('seat_plan_pk', $plan->pk)->max('display_order') + 1,
            'created_by' => Auth::id(),
            'created_date' => $now,
        ]);

        return ['plan' => $plan, 'summary' => $this->reseat($plan, $allocator)];
    }

    /** Freeze = publish the whole plan and tell every seated OT where they sit. */
    public function freeze(int $id, NotificationService $notifications): JsonResponse
    {
        [$planVenue, $plan] = $this->findPlanVenue($id);

        if ($plan->isPublished()) {
            return response()->json(['status' => 'error', 'message' => 'This seating plan is already frozen.'], 422);
        }
        if ((int) $plan->unallocated_count > 0) {
            return response()->json(['status' => 'error', 'message' => $plan->unallocated_count
                . ' OT(s) in this plan have no seat yet. Map another room before freezing.'], 422);
        }
        if (! SeatAllocation::where('seat_plan_pk', $plan->pk)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Nobody is seated in this plan yet.'], 422);
        }

        $plan->forceFill([
            'status' => SeatPlan::STATUS_PUBLISHED,
            'published_at' => now(),
            'published_by' => Auth::id(),
            'modified_by' => Auth::id(),
            'modified_date' => now(),
        ])->save();

        [$sent, $noAccount] = $this->notifySeats($plan, $notifications);

        $message = "Seating plan frozen. {$sent} OT(s) notified of their seat.";
        if ($noAccount > 0) {
            $message .= " {$noAccount} OT(s) have no login account and were not notified.";
        }

        return response()->json(['status' => 'success', 'message' => $message]);
    }

    /** Remove one room from its plan; the plan's OTs are re-seated in the rooms that remain. */
    public function destroy(int $id, SeatAllocator $allocator): JsonResponse
    {
        [$planVenue, $plan] = $this->findPlanVenue($id);

        if ($plan->isPublished()) {
            return response()->json(['status' => 'error', 'message' => 'A frozen seating plan cannot be changed.'], 422);
        }

        try {
            $message = DB::transaction(function () use ($planVenue, $plan, $allocator) {
                SeatAllocation::where('seat_plan_pk', $plan->pk)->where('venue_id', $planVenue->venue_id)->delete();
                $planVenue->delete();

                if (! SeatPlanVenue::where('seat_plan_pk', $plan->pk)->exists()) {
                    $plan->delete();

                    return 'Room removed. The seating plan had no other room and was deleted.';
                }

                return 'Room removed. ' . $this->allocationMessage($this->reseat($plan, $allocator));
            });
        } catch (\Throwable $e) {
            Log::error('COE venue allocation delete failed', ['id' => $id, 'exception' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => 'The room could not be removed. Nothing was changed.'], 500);
        }

        return response()->json(['status' => 'success', 'message' => $message]);
    }

    public function edit(int $id)
    {
        return view('admin.coe.venue_allocation.edit', ['allocation' => $this->allocationDetail($id)]);
    }

    /** View Seating Plan — the room read-only, from the listing's View action. */
    public function show(int $id)
    {
        return view('admin.coe.venue_allocation.show', ['allocation' => $this->allocationDetail($id)]);
    }

    /**
     * One room of a plan with everyone seated in it — Edit and View Seating Plan.
     *
     * @return array<string, mixed>
     */
    private function allocationDetail(int $id): array
    {
        [$planVenue, $plan] = $this->findPlanVenue($id);
        $room = $this->roomDetails((int) $planVenue->venue_id);
        abort_if($room === null, 404);

        $seats = $this->roomSeats((int) $plan->pk, (int) $planVenue->venue_id);

        return [
            'id' => (int) $planVenue->pk,
            'room' => $room->venue_name,
            'building' => $room->building,
            'floor' => $room->floor,
            'course' => $plan->course?->course_name,
            'batch' => $plan->batch,
            'subject' => $plan->subject_name ?: 'General',
            'component' => $plan->exam_name,
            'category' => SeatPlan::categoryLabel((int) $plan->candidate_category),
            'exam_date' => $plan->exam_date?->format('d M Y'),
            'frozen' => $plan->isPublished(),
            // "System Generated" until an administrator moves someone by hand.
            'source' => $seats->contains('manual', true) ? 'Manually Adjusted' : 'System Generated',
            'capacity' => (int) $room->capacity,
            'laptop_seats' => $room->laptop_seats,
            'scriber_seats' => $room->scriber_seats,
            'seats' => $seats->values(),
        ];
    }

    /**
     * Swap two desks in one room (or move an OT to an empty desk). Both rows become
     * manual, so a later re-run of the allocator keeps them where the admin put them.
     */
    public function swap(Request $request, int $id): JsonResponse
    {
        [$planVenue, $plan] = $this->findPlanVenue($id);
        if ($plan->isPublished()) {
            return response()->json(['status' => 'error', 'message' => 'A frozen seating plan cannot be changed.'], 422);
        }

        $room = $this->roomDetails((int) $planVenue->venue_id);
        $validated = $request->validate([
            'from_seat' => ['required', 'integer', 'min:1', 'max:' . max(1, (int) $room?->capacity)],
            'to_seat' => ['required', 'integer', 'min:1', 'max:' . max(1, (int) $room?->capacity), 'different:from_seat'],
        ]);
        $from = (int) $validated['from_seat'];
        $to = (int) $validated['to_seat'];
        $laptopSeats = array_flip($room->laptop_seats);

        try {
            DB::transaction(function () use ($plan, $planVenue, $from, $to, $laptopSeats) {
                $inRoom = fn () => SeatAllocation::where('seat_plan_pk', $plan->pk)->where('venue_id', $planVenue->venue_id);
                $a = $inRoom()->where('seat_number', $from)->lockForUpdate()->first();
                $b = $inRoom()->where('seat_number', $to)->lockForUpdate()->first();

                if ($a === null) {
                    throw ValidationException::withMessages(['from_seat' => 'Nobody sits at seat ' . self::seatLabel($from) . '.']);
                }
                // The seat map says where the laptops are; a laptop candidate cannot leave one.
                if ($laptopSeats !== []) {
                    if ($a->requires_laptop && ! isset($laptopSeats[$to])) {
                        throw ValidationException::withMessages(['to_seat' => 'Seat ' . self::seatLabel($to) . ' is not a laptop desk.']);
                    }
                    if ($b && $b->requires_laptop && ! isset($laptopSeats[$from])) {
                        throw ValidationException::withMessages(['to_seat' => 'Seat ' . self::seatLabel($from) . ' is not a laptop desk.']);
                    }
                }

                $stamp = ['is_manual' => true, 'modified_by' => Auth::id(), 'modified_date' => now()];
                // Seat 0 is never a real desk; it parks the first row so the unique
                // (plan, venue, seat) key holds at every step of the swap.
                $a->forceFill(['seat_number' => 0] + $stamp)->save();
                $b?->forceFill(['seat_number' => $from] + $stamp)->save();
                $a->forceFill(['seat_number' => $to])->save();
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('COE seat swap failed', ['id' => $id, 'exception' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => 'The swap could not be saved. Nothing was changed.'], 500);
        }

        return response()->json(['status' => 'success', 'message' => 'Seat ' . self::seatLabel($from) . ' and seat '
            . self::seatLabel($to) . ' swapped.']);
    }

    /**
     * Who sits where in one room of a plan — for Edit Seating Plan and View Seating Plan.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function roomSeats(int $planPk, int $venueId): Collection
    {
        return DB::table('coe_seat_allocations as sa')
            ->join('student_master as sm', 'sm.pk', '=', 'sa.student_master_pk')
            ->leftJoin('coe_roll_numbers as rn', 'rn.pk', '=', 'sa.roll_number_pk')
            ->where('sa.seat_plan_pk', $planPk)
            ->where('sa.venue_id', $venueId)
            ->orderBy('sa.seat_number')
            ->get(['sa.seat_number', 'sa.requires_laptop', 'sa.requires_scriber', 'sa.is_manual', 'rn.roll_number',
                'sm.display_name', 'sm.first_name', 'sm.middle_name', 'sm.last_name'])
            ->map(fn ($s) => [
                'seat' => (int) $s->seat_number,
                'label' => self::seatLabel((int) $s->seat_number),
                'roll' => $s->roll_number ?: '—',
                'name' => $this->studentName($s),
                'laptop' => (bool) $s->requires_laptop,
                'scriber' => (bool) $s->requires_scriber,
                'manual' => (bool) $s->is_manual,
            ]);
    }

    /** Desk label shared with public/js/coe-seat-map.js: block letter, row letter, desk in block. */
    public static function seatLabel(int $number): string
    {
        $perRow = array_sum(self::SEAT_BLOCKS);
        $index = $number - 1;
        if ($index < 0) {
            return (string) $number;
        }
        $within = $index % $perRow;
        $row = intdiv($index, $perRow) + 1;
        $rowLetter = '';
        while ($row > 0) {
            $rowLetter = chr(65 + ($row - 1) % 26) . $rowLetter;
            $row = intdiv($row - 1, 26);
        }
        foreach (self::SEAT_BLOCKS as $block => $cols) {
            if ($within < $cols) {
                return $block . '-' . $rowLetter . str_pad((string) ($within + 1), 2, '0', STR_PAD_LEFT);
            }
            $within -= $cols;
        }

        return (string) $number;
    }

    // ── Allocation ──────────────────────────────────────────────────────────

    /**
     * Run the allocator over every room of the plan and replace the automatic seats.
     * Manual seats (swaps) are kept: the allocator is told they are taken. Caller owns the
     * transaction.
     *
     * @return array<string, mixed> the allocator's summary
     */
    private function reseat(SeatPlan $plan, SeatAllocator $allocator): array
    {
        $planVenues = SeatPlanVenue::where('seat_plan_pk', $plan->pk)->get();
        $participants = $this->allocationInput($plan);
        $venues = $this->venuesById($planVenues->pluck('venue_id')->all());
        $manualSeats = SeatAllocation::where('seat_plan_pk', $plan->pk)->where('is_manual', true)
            ->get(['student_master_pk', 'venue_id', 'seat_number'])
            ->mapWithKeys(fn ($s) => [(int) $s->student_master_pk => ['venue_id' => (int) $s->venue_id, 'seat_number' => (int) $s->seat_number]])
            ->all();

        $result = $allocator->allocate($plan, $participants, $planVenues, $venues, $manualSeats);

        SeatAllocation::where('seat_plan_pk', $plan->pk)->where('is_manual', false)->delete();

        $now = now();
        $rows = [];
        foreach ($result['seats'] as $seat) {
            if (isset($manualSeats[$seat['student_master_pk']])) {
                continue;
            }
            $rows[] = [
                'seat_plan_pk' => (int) $plan->pk,
                'student_master_pk' => $seat['student_master_pk'],
                'roll_number_pk' => $seat['roll_number_pk'],
                'venue_id' => $seat['venue_id'],
                'seat_number' => $seat['seat_number'],
                'requires_laptop' => $seat['requires_laptop'],
                'requires_scriber' => $seat['requires_scriber'],
                'is_manual' => false,
                'allocated_at' => $now,
                'created_by' => Auth::id(),
                'created_date' => $now,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            SeatAllocation::insert($chunk);
        }

        // Counted from the rows that landed: a manual seat whose OT has since lost their
        // roll number is not in the allocator's input but is still a real desk.
        $seated = (int) SeatAllocation::where('seat_plan_pk', $plan->pk)->count();
        $plan->forceFill([
            'allocated_count' => $seated,
            'unallocated_count' => max(0, $participants->count() - $seated),
            'status' => SeatPlan::STATUS_ALLOCATED,
            'allocated_at' => $now,
            'allocated_by' => Auth::id(),
        ])->save();

        return $result['summary'];
    }

    /**
     * The OTs this plan seats, in roll-number order — only OTs with a roll number, as
     * the seat is addressed by it.
     *
     * @return Collection<int, object>
     */
    private function allocationInput(SeatPlan $plan): Collection
    {
        return $this->participantQuery((int) $plan->course_master_pk, (int) $plan->batch, (int) $plan->candidate_category)
            ->select(['sm.pk', 'rn.pk as roll_number_pk', 'rn.roll_number', 'rn.serial_number', 'lr.laptop_required', 'lr.scriber_required'])
            ->orderBy('rn.serial_number')
            ->orderBy('sm.pk')
            ->get()
            ->map(fn ($row) => (object) [
                'pk' => (int) $row->pk,
                'roll_number_pk' => (int) $row->roll_number_pk,
                'roll_number' => (string) $row->roll_number,
                'serial_number' => (int) $row->serial_number,
                'requires_laptop' => $this->requiresLaptop($plan, $row->laptop_required),
                'requires_scriber' => (int) $row->scriber_required === 1,
            ]);
    }

    /** OTs of a course + batch that have a roll number, narrowed to a candidate category. */
    private function participantQuery(int $courseId, int $batch, int $category)
    {
        return DB::table('student_master as sm')
            ->join('coe_roll_numbers as rn', 'rn.student_master_pk', '=', 'sm.pk')
            ->leftJoin('coe_laptop_responses as lr', 'lr.student_master_pk', '=', 'sm.pk')
            ->where('sm.course_master_pk', $courseId)
            ->where('sm.batch', $batch)
            ->when($category === SeatPlan::CATEGORY_GENERAL, fn ($q) => $q->where(fn ($w) => $w
                ->whereNull('lr.has_disability')->orWhere('lr.has_disability', 0)))
            ->when($category === SeatPlan::CATEGORY_PH, fn ($q) => $q->where('lr.has_disability', 1));
    }

    /**
     * Whether a participant needs a laptop desk, by the plan's policy. An OT with no
     * answer is read cautiously each way — see SeatPlan::laptopRequirements().
     */
    private function requiresLaptop(SeatPlan $plan, $laptopRequired): bool
    {
        return match ((int) $plan->laptop_requirement) {
            SeatPlan::LAPTOP_REQ_ALL => true,
            SeatPlan::LAPTOP_REQ_NONE => false,
            SeatPlan::LAPTOP_REQ_DECLARED_YES => $laptopRequired !== null && (int) $laptopRequired === 1,
            default => $laptopRequired === null || (int) $laptopRequired === 0,
        };
    }

    /**
     * venue_master rows keyed by id, carrying the seat map the allocator honours.
     *
     * @param  array<int, int>  $venueIds
     * @return array<int, object>
     */
    private function venuesById(array $venueIds): array
    {
        if ($venueIds === []) {
            return [];
        }

        return DB::table('venue_master as vm')
            ->leftJoin('coe_venue_seat_layouts as vsl', 'vsl.venue_id', '=', 'vm.venue_id')
            ->whereIn('vm.venue_id', $venueIds)
            ->get(['vm.venue_id', 'vm.venue_name', 'vm.capacity', 'vm.laptop_capacity', 'vm.seating_capacity',
                'vsl.laptop_seats', 'vsl.scriber_seats'])
            ->map(function ($v) {
                $v->laptop_seat_numbers = VenueSeatLayout::normalise(json_decode((string) $v->laptop_seats, true));
                $v->scriber_seat_numbers = VenueSeatLayout::normalise(json_decode((string) $v->scriber_seats, true));

                return $v;
            })
            ->keyBy('venue_id')
            ->all();
    }

    /** @param array<string, mixed> $summary */
    private function allocationMessage(array $summary): string
    {
        if ((int) $summary['unallocated'] === 0) {
            return sprintf('Seating mapped — %d OT(s) seated across %d room(s).', $summary['allocated'], $summary['rooms']);
        }

        return sprintf(
            '%d OT(s) seated, but %d could not be placed (the rooms hold %d).%s Map another room for them.',
            $summary['allocated'],
            $summary['unallocated'],
            $summary['capacity'],
            (int) $summary['unallocated_laptop'] > 0
                ? ' ' . $summary['unallocated_laptop'] . ' of them need a laptop desk.'
                : ''
        );
    }

    /** @return array{0: int, 1: int} */
    private function notifySeats(SeatPlan $plan, NotificationService $notifications): array
    {
        $seats = DB::table('coe_seat_allocations as sa')
            ->join('venue_master as vm', 'vm.venue_id', '=', 'sa.venue_id')
            ->leftJoin('exam_building_master as eb', 'eb.pk', '=', 'vm.building_master_pk')
            ->leftJoin('exam_floor_master as ef', 'ef.pk', '=', 'vm.floor_master_pk')
            ->where('sa.seat_plan_pk', $plan->pk)
            ->get(['sa.student_master_pk', 'sa.seat_number', 'vm.venue_name', 'eb.building_name', 'ef.floor_name']);

        // Same lookup as NotificationReceiverService::getStudentUserId, once for the plan.
        $accounts = DB::table('user_credentials')
            ->whereIn('user_id', $seats->pluck('student_master_pk')->all())
            ->where('user_category', 'S')
            ->pluck('user_id')
            ->flip();

        $paper = trim(($plan->subject_name ? $plan->subject_name . ' – ' : '') . $plan->exam_name);
        $date = $plan->exam_date ? ' on ' . $plan->exam_date->format('d M Y') : '';
        $sent = 0;

        foreach ($seats as $seat) {
            if (! isset($accounts[$seat->student_master_pk])) {
                continue;
            }
            $where = implode(', ', array_filter([$seat->building_name, $seat->floor_name, $seat->venue_name]));
            try {
                $notifications->create(
                    (int) $seat->student_master_pk,
                    'coe_seat_allocation',
                    'COE',
                    (int) $plan->pk,
                    'Examination seat allotted',
                    "Your seat for {$paper}{$date}: {$where}, Seat " . self::seatLabel((int) $seat->seat_number) . '.'
                );
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('COE seat notification failed', ['plan' => $plan->pk, 'student' => $seat->student_master_pk, 'exception' => $e->getMessage()]);
            }
        }

        return [$sent, $seats->count() - $sent];
    }

    // ── Lookups ─────────────────────────────────────────────────────────────

    /** @return array{0: SeatPlanVenue, 1: SeatPlan} */
    private function findPlanVenue(int $id): array
    {
        $planVenue = SeatPlanVenue::findOrFail($id);
        $plan = SeatPlan::with('course')->findOrFail($planVenue->seat_plan_pk);

        return [$planVenue, $plan];
    }

    private function allocationRows(string $scope): Collection
    {
        $today = now()->toDateString();

        $rows = DB::table('coe_seat_plan_venues as spv')
            ->join('coe_seat_plans as sp', 'sp.pk', '=', 'spv.seat_plan_pk')
            ->join('course_master as cm', 'cm.pk', '=', 'sp.course_master_pk')
            ->join('venue_master as vm', 'vm.venue_id', '=', 'spv.venue_id')
            ->leftJoin('exam_building_master as eb', 'eb.pk', '=', 'vm.building_master_pk')
            ->leftJoin('exam_floor_master as ef', 'ef.pk', '=', 'vm.floor_master_pk')
            ->leftJoin('coe_venue_seat_layouts as vsl', 'vsl.venue_id', '=', 'spv.venue_id')
            ->when($scope === 'active', fn ($q) => $q->where('cm.active_inactive', 1)->where('cm.end_date', '>=', $today))
            ->when($scope === 'archived', fn ($q) => $q->where(fn ($w) => $w->where('cm.active_inactive', '!=', 1)
                ->orWhereNull('cm.active_inactive')->orWhere('cm.end_date', '<', $today)))
            ->orderBy('cm.course_name')
            ->orderBy('sp.pk')
            ->orderBy('spv.display_order')
            ->get(['spv.pk', 'spv.seat_plan_pk', 'spv.venue_id', 'sp.batch', 'sp.candidate_category', 'sp.subject_name',
                'sp.exam_name', 'sp.status', 'sp.unallocated_count', 'cm.course_name', 'vm.venue_name', 'vm.capacity',
                'vm.laptop_capacity', 'eb.building_name', 'ef.floor_name', 'vsl.laptop_seats', 'vsl.scriber_seats']);

        $counts = DB::table('coe_seat_allocations')
            ->whereIn('seat_plan_pk', $rows->pluck('seat_plan_pk')->unique()->all())
            ->groupBy('seat_plan_pk', 'venue_id')
            ->get([
                'seat_plan_pk', 'venue_id',
                DB::raw('COUNT(*) as seated'),
                DB::raw('SUM(requires_laptop) as laptop'),
                DB::raw('SUM(requires_scriber) as scriber'),
            ])
            ->keyBy(fn ($c) => $c->seat_plan_pk . ':' . $c->venue_id);

        return $rows->map(function ($r) use ($counts) {
            $c = $counts[$r->seat_plan_pk . ':' . $r->venue_id] ?? null;
            $laptopSeats = VenueSeatLayout::normalise(json_decode((string) $r->laptop_seats, true));
            $availLaptop = $laptopSeats !== [] ? count($laptopSeats) : (int) $r->laptop_capacity;
            $laptopReq = (int) ($c->laptop ?? 0);

            return [
                'id' => (int) $r->pk,
                'course' => $r->course_name,
                'batch' => (int) $r->batch,
                'category' => SeatPlan::categoryLabel((int) $r->candidate_category),
                'subject' => $r->subject_name ?: 'General',
                'component' => $r->exam_name ?: '—',
                'ot_count' => (int) ($c->seated ?? 0),
                'laptop_req' => $laptopReq,
                'scriber_req' => (int) ($c->scriber ?? 0),
                'building' => $r->building_name ?: '—',
                'floor' => $r->floor_name ?: '—',
                'room' => $r->venue_name,
                'capacity' => (int) $r->capacity,
                'avail_laptop' => $availLaptop,
                'scriber_seats' => count(VenueSeatLayout::normalise(json_decode((string) $r->scriber_seats, true))),
                'empty_laptop' => max(0, $availLaptop - $laptopReq),
                'status' => (int) $r->status === SeatPlan::STATUS_PUBLISHED ? 'Frozen' : 'Pending',
                'unallocated' => (int) $r->unallocated_count,
            ];
        });
    }

    /** Course + batch pairs that have roll numbers — the only ones a plan can seat. */
    private function courseBatches(): Collection
    {
        return DB::table('coe_roll_number_allocations as rna')
            ->join('course_master as cm', 'cm.pk', '=', 'rna.course_master_pk')
            ->orderBy('cm.course_name')
            ->orderByDesc('rna.batch')
            ->get(['rna.course_master_pk', 'rna.batch', 'cm.course_name'])
            ->map(fn ($r) => [
                'key' => $r->course_master_pk . ':' . $r->batch,
                'label' => $r->course_name . ' — ' . $r->batch,
            ]);
    }

    /** Examination rooms: venues with a building, a floor and a capacity. */
    private function examRooms(): Collection
    {
        return DB::table('venue_master as vm')
            ->join('exam_building_master as eb', 'eb.pk', '=', 'vm.building_master_pk')
            ->join('exam_floor_master as ef', 'ef.pk', '=', 'vm.floor_master_pk')
            ->leftJoin('coe_venue_seat_layouts as vsl', 'vsl.venue_id', '=', 'vm.venue_id')
            ->where('vm.active_inactive', 1)
            ->where('vm.capacity', '>', 0)
            ->orderBy('eb.building_name')
            ->orderBy('ef.display_order')
            ->orderBy('vm.venue_name')
            ->get(['vm.venue_id', 'vm.venue_name', 'vm.capacity', 'vm.laptop_capacity', 'eb.pk as building_pk',
                'eb.building_name as building', 'ef.pk as floor_pk', 'ef.floor_name as floor', 'vsl.laptop_seats', 'vsl.scriber_seats']);
    }

    /** building id → floor id → { name, rooms: [{ id, name, capacity, laptop, scriber }] } for the dependent selects. */
    private function roomTree(Collection $rooms): array
    {
        $tree = [];
        foreach ($rooms as $r) {
            $laptopSeats = VenueSeatLayout::normalise(json_decode((string) $r->laptop_seats, true));
            $tree[$r->building_pk][$r->floor_pk]['name'] = $r->floor;
            $tree[$r->building_pk][$r->floor_pk]['rooms'][] = [
                'id' => (int) $r->venue_id,
                'name' => $r->venue_name,
                'capacity' => (int) $r->capacity,
                'laptop' => $laptopSeats !== [] ? count($laptopSeats) : (int) $r->laptop_capacity,
                'scriber' => count(VenueSeatLayout::normalise(json_decode((string) $r->scriber_seats, true))),
            ];
        }

        return $tree;
    }

    private function roomDetails(int $venueId): ?object
    {
        $room = DB::table('venue_master as vm')
            ->leftJoin('exam_building_master as eb', 'eb.pk', '=', 'vm.building_master_pk')
            ->leftJoin('exam_floor_master as ef', 'ef.pk', '=', 'vm.floor_master_pk')
            ->leftJoin('coe_venue_seat_layouts as vsl', 'vsl.venue_id', '=', 'vm.venue_id')
            ->where('vm.venue_id', $venueId)
            ->first(['vm.venue_id', 'vm.venue_name', 'vm.capacity', 'eb.building_name as building', 'ef.floor_name as floor',
                'vsl.laptop_seats', 'vsl.scriber_seats']);

        if ($room) {
            $room->laptop_seats = VenueSeatLayout::normalise(json_decode((string) $room->laptop_seats, true));
            $room->scriber_seats = VenueSeatLayout::normalise(json_decode((string) $room->scriber_seats, true));
        }

        return $room;
    }

    private function studentName(object $s): string
    {
        $name = trim((string) ($s->display_name ?? ''));
        if ($name === '') {
            $name = trim(implode(' ', array_filter([$s->first_name ?? null, $s->middle_name ?? null, $s->last_name ?? null])));
        }

        return $name !== '' ? $name : '—';
    }

    /** @return array{0: int, 1: int} */
    private function splitCourseBatch(string $value): array
    {
        [$courseId, $batch] = array_pad(explode(':', $value, 2), 2, null);

        return [(int) $courseId, (int) $batch];
    }
}
