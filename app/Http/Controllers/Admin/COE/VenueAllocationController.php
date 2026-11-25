<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Concerns\ExportsMasterGrid;
use App\Http\Controllers\Controller;
use App\Models\COE\SeatAllocation;
use App\Models\COE\SeatPlan;
use App\Models\COE\SeatPlanVenue;
use App\Models\COE\VenueSeatLayout;
use App\Models\ExaminationDrive;
use App\Services\COE\CourseParticipants;
use App\Services\COE\ExamRooms;
use App\Services\COE\LaptopDeclarationService;
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
 * "Map Seating Plan" adds rooms to that plan (creating the plan the first time)
 * and re-runs the allocator over all of its rooms.
 *
 *   System generated — any number of rooms; the allocator decides who sits where.
 *   Manual           — one row per room with the OTs the administrator picked for it;
 *                      that room seats exactly those OTs.
 *
 *   index           — allocation list (Active / Archived = course still running / finished)
 *   edit/{id}       — seat map of one room, with seat swap
 *
 * Status on screen: Pending = plan not yet published, Frozen = published (OTs
 * have been told their seat). A frozen plan cannot be changed.
 */
class VenueAllocationController extends Controller
{
    use ExportsMasterGrid;

    public const SCOPES = ['active', 'archived'];

    /** Map Seating Plan's seating_mode => coe_seat_plan_venues.seating_mode. */
    private const SEATING_MODES = ['system' => SeatPlanVenue::MODE_SYSTEM, 'manual' => SeatPlanVenue::MODE_MANUAL];

    /** Must match public/js/coe-seat-map.js — desks per row, per block. */
    private const SEAT_BLOCKS = ['A' => 4, 'B' => 13, 'C' => 6];

    /** examination_drive_component_maps.exam_type that takes no invigilators. */
    private const OFFLINE_OBJECTIVE = 'Offline Objective';

    /** Component exam type => the invigilator roles its rooms do not take (disabled in Add Invigilators). */
    private const ROLES_NOT_TAKEN = [
        'Online Objective' => ['assistant'],
    ];

    /** Add Invigilators: request field => coe_seat_plan_venue_invigilators column, and whether it holds a faculty. */
    private const INVIGILATOR_ROLES = [
        'chief' => ['column' => 'chief_faculty_pk', 'faculty' => true, 'label' => 'Chief Invigilator'],
        'invigilator' => ['column' => 'invigilator_faculty_pk', 'faculty' => true, 'label' => 'Invigilator'],
        'assistant' => ['column' => 'assistant_faculty_pk', 'faculty' => true, 'label' => 'Assistant Invigilator'],
        'mts' => ['column' => 'mts_employee_pk', 'faculty' => false, 'label' => 'MTS'],
    ];

    private const MTS_DESIGNATION = 'MULTI TASKING STAFF';

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
                'drive' => $rows->pluck('drive')->unique()->sort()->values(),
                'course' => $rows->pluck('course')->unique()->sort()->values(),
                'batch' => $rows->pluck('batch')->unique()->sortDesc()->values(),
                'category' => $rows->pluck('category')->unique()->sort()->values(),
                'subject' => $rows->pluck('subject')->unique()->sort()->values(),
                'component' => $rows->pluck('component')->unique()->sort()->values(),
            ],
            'drives' => $this->mappableDrives()->map(fn ($d) => ['id' => $d['id'], 'label' => $d['label']])->values(),
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
            ->where('ed.cc_status', ExaminationDrive::CC_APPROVED)
            ->orderBy('sub.subject_name')
            ->orderBy('comp.component_name')
            // Exam day: the CC's confirmed slot, else the Tentative Exam Range (its time, else its date).
            ->get(['dcm.id', DB::raw('COALESCE(DATE(dcm.proposed_start_at), DATE(dcm.exam_start_at), dcm.exam_date) as exam_date'), 'ed.drive_name', 'sub.subject_name', 'comp.component_name'])
            ->map(fn ($c) => [
                'id' => (int) $c->id,
                'subject' => $c->subject_name ?: 'General',
                'component' => $c->component_name,
                'label' => $c->component_name
                    . ($c->drive_name ? ' — ' . $c->drive_name : '')
                    . ($c->exam_date ? ' (' . date('d M Y', strtotime($c->exam_date)) . ')' : ''),
            ]);

        // Manual Seating's OT Roll No. picker; the dialog narrows it to the chosen category.
        $ots = $this->participantQuery($courseId, $batch, SeatPlan::CATEGORY_ALL)
            ->orderBy('rn.serial_number')
            ->orderBy('sm.pk')
            ->get(['sm.pk', 'rn.roll_number', 'lr.has_disability', 'sm.display_name', 'sm.first_name', 'sm.middle_name', 'sm.last_name'])
            ->map(fn ($o) => [
                'pk' => (int) $o->pk,
                'roll' => (string) $o->roll_number,
                'name' => $this->studentName($o),
                'ph' => (int) $o->has_disability === 1,
            ]);

        $counts = [];
        foreach (array_keys(SeatPlan::categories()) as $category) {
            $row = $this->participantQuery($courseId, $batch, $category)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN lr.laptop_required = 1 THEN 1 ELSE 0 END) as declared_yes')
                ->selectRaw('SUM(CASE WHEN lr.laptop_required = 0 THEN 1 ELSE 0 END) as declared_no')
                // A scriber only for a PwBD OT, whatever an older answer recorded.
                ->selectRaw('SUM(CASE WHEN lr.scriber_required = 1 AND ' . LaptopDeclarationService::pwbdSql('sm.ph') . ' THEN 1 ELSE 0 END) as scriber')
                ->first();
            $counts[$category] = [
                'total' => (int) $row->total,
                'declared_yes' => (int) $row->declared_yes,
                'declared_no' => (int) $row->declared_no,
                'undeclared' => (int) $row->total - (int) $row->declared_yes - (int) $row->declared_no,
                'scriber' => (int) $row->scriber,
            ];
        }

        return response()->json(['status' => 'success', 'components' => $components, 'counts' => $counts, 'ots' => $ots]);
    }

    /** Map Seating Plan: add the rooms to the plan for this course/batch/component/category, then seat everyone. */
    public function store(Request $request, SeatAllocator $allocator): JsonResponse
    {
        $mapping = $this->resolveMapping($request);

        try {
            $summary = DB::transaction(function () use ($mapping, $allocator) {
                $summary = $this->applyMapping($mapping, $allocator)['summary'];
                // Every OT of the category needs a desk; one left over rolls the whole mapping back.
                if ($summary['unallocated'] > 0) {
                    $rooms = $mapping['rooms'];
                    $field = $mapping['manual'] ? $rooms[array_key_last($rooms)]['field'] . '.ots' : 'venue_ids';
                    throw ValidationException::withMessages([$field => $this->unseatedMessage($summary, $mapping['manual'])]);
                }

                return $summary;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('COE venue allocation failed', ['exception' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => 'The seating could not be saved. Nothing was changed.'], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => $this->allocationMessage($summary),
        ]);
    }

    /**
     * Map Seating Plan, drive first: everything the dialog needs once a drive is picked —
     * the drive's subjects and their components (each one row of the dialog), the OT count
     * and laptop count of the drive's cohort, and the OTs with a roll number for the
     * OT Roll No. picker.
     *
     * Per component: `taken` is the OTs already listed in a room of that component's plan
     * (they cannot be listed again), `frozen` whether that plan is published.
     */
    public function drive(int $id): JsonResponse
    {
        $drive = $this->mappableDrives()->firstWhere('id', $id);
        if ($drive === null) {
            return response()->json(['status' => 'error', 'message' => 'This examination drive is closed, not yet approved by the Course Coordinator, or its course has no roll numbers yet.'], 422);
        }
        [$courseId, $batch] = [$drive['course_id'], $drive['batch']];

        $plans = DB::table('coe_seat_plans')
            ->where('course_master_pk', $courseId)
            ->where('batch', $batch)
            ->whereNotNull('examination_drive_component_map_id')
            ->get(['pk', 'examination_drive_component_map_id as map_id', 'status']);
        $taken = DB::table('coe_seat_plan_venue_ots')
            ->whereIn('seat_plan_pk', $plans->pluck('pk')->all())
            ->get(['seat_plan_pk', 'student_master_pk'])
            ->groupBy('seat_plan_pk');

        $subjects = DB::table('examination_drive_component_maps as dcm')
            ->join('component_master as comp', 'comp.pk', '=', 'dcm.component_master_pk')
            ->leftJoin('subject_master as sub', 'sub.pk', '=', 'dcm.subject_master_pk')
            ->where('dcm.examination_drive_id', $id)
            ->orderBy('sub.subject_name')
            ->orderBy('dcm.exam_date')
            ->orderBy('comp.component_name')
            ->get(['dcm.id', 'dcm.exam_date', 'sub.subject_name', 'comp.component_name'])
            ->map(function ($c) use ($plans, $taken) {
                $own = $plans->where('map_id', $c->id);

                return [
                    'id' => (int) $c->id,
                    'subject' => trim((string) $c->subject_name) ?: 'General',
                    'component' => $c->component_name,
                    'exam_date' => $this->examDateLabel($c->exam_date),
                    'frozen' => $own->contains(fn ($p) => (int) $p->status === SeatPlan::STATUS_PUBLISHED),
                    'taken' => $own->flatMap(fn ($p) => ($taken[$p->pk] ?? collect())->pluck('student_master_pk'))
                        ->map(fn ($pk) => (int) $pk)->values(),
                ];
            })
            ->groupBy('subject')
            ->map(fn ($components, $subject) => ['subject' => $subject, 'components' => $components->values()])
            ->values();

        $ots = $this->participantQuery($courseId, $batch, SeatPlan::CATEGORY_ALL)
            ->orderBy('rn.serial_number')
            ->orderBy('sm.pk')
            ->get(['sm.pk', 'rn.roll_number', 'rn.serial_number', 'lr.laptop_required', 'sm.display_name', 'sm.first_name', 'sm.middle_name', 'sm.last_name'])
            ->map(fn ($o) => [
                'pk' => (int) $o->pk,
                'roll' => (string) $o->roll_number,
                'serial' => (int) $o->serial_number,
                'name' => $this->studentName($o),
                // Needs a laptop desk under the drive's policy (Laptop Required = Yes) — for the dialog's live room check.
                'laptop' => $this->requiresLaptop(SeatPlan::LAPTOP_REQ_DECLARED_YES, $o->laptop_required),
            ]);

        $laptops = (int) $this->participantQuery($courseId, $batch, SeatPlan::CATEGORY_ALL)
            ->where('lr.laptop_required', 1)
            ->count();

        return response()->json([
            'status' => 'success',
            'ot_count' => $ots->count(),
            'laptop_required' => $laptops,
            'subjects' => $subjects,
            'ots' => $ots,
        ]);
    }

    /**
     * Map Seating Plan, drive first. One row per component of the drive: the room it sits
     * in and the OTs that room seats (Manual seating, every candidate category). Rows the
     * administrator left blank are skipped; the rest are saved together or not at all.
     */
    public function storeDrive(Request $request, SeatAllocator $allocator): JsonResponse
    {
        $validated = $request->validate([
            'drive_id' => ['required', 'integer'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.component_map_id' => ['required', 'integer'],
            'rows.*.venue_id' => ['required', 'integer'],
            'rows.*.ots' => ['required', 'array', 'min:1'],
            'rows.*.ots.*' => ['integer'],
            'rule_avoid_same_service' => ['nullable', 'boolean'],
            'rule_avoid_same_cadre' => ['nullable', 'boolean'],
            'rule_alternate_gender' => ['nullable', 'boolean'],
            'rule_odd_even' => ['nullable', 'boolean'],
            'seat_gap' => ['nullable', 'integer', 'min:0', 'max:10'],
        ], [
            'drive_id.required' => 'Select a drive.',
            'rows.required' => 'Map at least one component to a room.',
            'rows.*.venue_id.required' => 'Select a room.',
            'rows.*.ots.required' => 'Select the OTs for this room.',
            'rows.*.ots.min' => 'Select the OTs for this room.',
        ]);

        $drive = $this->mappableDrives()->firstWhere('id', (int) $validated['drive_id']);
        if ($drive === null) {
            throw ValidationException::withMessages(['drive_id' => 'This examination drive is closed, not yet approved by the Course Coordinator, or its course has no roll numbers yet.']);
        }
        $components = DB::table('examination_drive_component_maps as dcm')
            ->join('component_master as comp', 'comp.pk', '=', 'dcm.component_master_pk')
            ->leftJoin('subject_master as sub', 'sub.pk', '=', 'dcm.subject_master_pk')
            ->where('dcm.examination_drive_id', $drive['id'])
            ->get(['dcm.id', 'sub.subject_name', 'comp.component_name'])
            ->keyBy(fn ($c) => (int) $c->id);

        // Rows of one component share its seating plan: grouped, in the dialog's order.
        $groups = [];
        foreach ($validated['rows'] as $i => $row) {
            if (! $components->has((int) $row['component_map_id'])) {
                throw ValidationException::withMessages(["rows.{$i}.component_map_id" => 'That component is not part of this drive.']);
            }
            $groups[(int) $row['component_map_id']][] = $i;
        }

        $common = array_intersect_key($validated, array_flip(['rule_avoid_same_service', 'rule_avoid_same_cadre',
            'rule_alternate_gender', 'rule_odd_even', 'seat_gap'])) + [
                'course_batch' => $drive['course_id'] . ':' . $drive['batch'],
                'candidate_category' => SeatPlan::CATEGORY_ALL,
                // The design has no laptop choice: a laptop desk goes to every OT who
                // answered "Laptop Required = Yes" on OT Laptop Status.
                'laptop_requirement' => SeatPlan::LAPTOP_REQ_DECLARED_YES,
                'seating_mode' => 'manual',
            ];

        try {
            $messages = DB::transaction(function () use ($groups, $validated, $common, $components, $allocator) {
                $messages = [];
                foreach ($groups as $mapId => $rowIndexes) {
                    $rooms = array_map(fn (int $i) => [
                        'venue_id' => (int) $validated['rows'][$i]['venue_id'],
                        'ots' => $validated['rows'][$i]['ots'],
                    ], $rowIndexes);

                    try {
                        $mapping = $this->resolveValidated(['component_map_id' => $mapId, 'rooms' => $rooms] + $common, true);
                    } catch (ValidationException $e) {
                        throw $this->driveRowErrors($e, $rowIndexes);
                    }
                    $summary = $this->applyMapping($mapping, $allocator)['summary'];
                    // Every OT of the paper needs a desk: one left over rolls the whole save back,
                    // under that component's last room.
                    if ($summary['unallocated'] > 0) {
                        throw ValidationException::withMessages([
                            'rows.' . end($rowIndexes) . '.ots' => $this->unseatedMessage($summary, true),
                        ]);
                    }

                    $c = $components[$mapId];
                    $messages[] = [
                        'component' => (trim((string) $c->subject_name) ?: 'General') . ' – ' . $c->component_name,
                        'status' => $summary['unallocated'] > 0 ? 'warning' : 'success',
                        'text' => $this->allocationMessage($summary),
                    ];
                }

                return $messages;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('COE venue allocation by drive failed', ['drive' => $drive['id'], 'exception' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => 'The seating could not be saved. Nothing was changed.'], 500);
        }

        return response()->json([
            'status' => collect($messages)->contains('status', 'warning') ? 'warning' : 'success',
            'message' => collect($messages)->map(fn ($m) => $m['component'] . ': ' . $m['text'])->implode("\n"),
            'results' => $messages,
        ]);
    }

    /**
     * A per-component validation error, re-keyed to the dialog row it is about:
     * rooms.{n}.x → rows.{row}.x, and a component-level error to the component's first row.
     *
     * @param  array<int, int>  $rowIndexes  rooms.{n} came from rows.{$rowIndexes[n]}
     */
    private function driveRowErrors(ValidationException $e, array $rowIndexes): ValidationException
    {
        $errors = [];
        foreach ($e->errors() as $key => $messages) {
            if (preg_match('/^rooms\.(\d+)\.(\w+)/', $key, $m) && isset($rowIndexes[(int) $m[1]])) {
                $field = $m[2] === 'ots' ? 'ots' : 'venue_id';
                $errors["rows.{$rowIndexes[(int) $m[1]]}.{$field}"] = $messages;
            } else {
                $errors["rows.{$rowIndexes[0]}.component_map_id"] = $messages;
            }
        }

        return ValidationException::withMessages($errors);
    }

    /**
     * Examination drives a seating plan can be mapped for: not closed, approved by the Course
     * Coordinator (a drive pending / rejected at CC is still being set up), and their course has
     * roll numbers. The cohort is the roll-number batch matching the drive's academic
     * session, else the course's latest — the rule Examination Drive uses for roll numbers.
     *
     * @return Collection<int, array{id: int, label: string, course_id: int, batch: int}>
     */
    private function mappableDrives(): Collection
    {
        $batches = DB::table('coe_roll_number_allocations')
            ->get(['course_master_pk', 'batch'])
            ->groupBy('course_master_pk');

        return DB::table('examination_drives as ed')
            ->join('course_master as cm', 'cm.pk', '=', 'ed.course_master_pk')
            ->where('ed.status', '!=', ExaminationDrive::STATUS_CLOSED)
            ->where('ed.cc_status', ExaminationDrive::CC_APPROVED)
            ->whereIn('ed.course_master_pk', $batches->keys()->all())
            ->orderByDesc('ed.start_date')
            ->orderByDesc('ed.id')
            ->get(['ed.id', 'ed.drive_name', 'ed.phase', 'ed.academic_session', 'ed.course_master_pk', 'cm.course_name'])
            ->map(function ($d) use ($batches) {
                $courseBatches = $batches[$d->course_master_pk]->pluck('batch')->map(fn ($b) => (int) $b);
                $batch = $courseBatches->contains((int) $d->academic_session) ? (int) $d->academic_session : $courseBatches->max();
                $name = trim((string) $d->drive_name);

                return [
                    'id' => (int) $d->id,
                    'label' => $name !== '' ? $name . ' — ' . $d->course_name : trim($d->course_name . ' ' . $d->phase),
                    'course_id' => (int) $d->course_master_pk,
                    'batch' => (int) $batch,
                ];
            });
    }

    /**
     * "01/11/2026": the component's exam date, as Faculty Coordinates & Components shows it.
     * The faculty's Exam Range end (`exam_end_date`) is not the exam date and is not shown.
     */
    private function examDateLabel(?string $date): string
    {
        return $date ? date('d/m/Y', strtotime($date)) : '—';
    }

    /**
     * Validate a Map Seating Plan request and look up everything it names, refusing it
     * before anything is written.
     *
     * Rooms come back in the order they are filled: building → floor → room for System
     * generated, the rows' order for Manual.
     *
     * @return array{validated: array, courseId: int, batch: int, component: object, category: int, plan: ?SeatPlan,
     *     manual: bool, rooms: array<int, array{field: string, room: object, ots: ?array<int, int>}>}
     *
     * @throws ValidationException
     */
    private function resolveMapping(Request $request): array
    {
        $manual = $request->input('seating_mode') === 'manual';
        $validated = $request->validate([
            'course_batch' => ['required', 'regex:/^\d+:\d+$/'],
            'component_map_id' => ['required', 'integer'],
            'candidate_category' => ['required', Rule::in(array_keys(SeatPlan::categories()))],
            'laptop_requirement' => ['required', Rule::in(array_keys(SeatPlan::laptopRequirements()))],
            'seating_mode' => ['required', Rule::in(array_keys(self::SEATING_MODES))],
            'venue_ids' => [Rule::requiredIf(! $manual), 'nullable', 'array'],
            'venue_ids.*' => ['integer'],
            'rooms' => [Rule::requiredIf($manual), 'nullable', 'array'],
            'rooms.*.venue_id' => ['required', 'integer'],
            'rooms.*.ots' => ['required', 'array'],
            'rooms.*.ots.*' => ['integer'],
            'rule_avoid_same_service' => ['nullable', 'boolean'],
            'rule_avoid_same_cadre' => ['nullable', 'boolean'],
            'rule_alternate_gender' => ['nullable', 'boolean'],
            'rule_odd_even' => ['nullable', 'boolean'],
            'seat_gap' => ['nullable', 'integer', 'min:0', 'max:10'],
        ], [
            'course_batch.required' => 'Select a course.',
            'component_map_id.required' => 'Select a component.',
            'venue_ids.required' => 'Select at least one room.',
            'rooms.required' => 'Add at least one room.',
            'rooms.*.venue_id.required' => 'Select a room.',
            'rooms.*.ots.required' => 'Select the OTs for this room.',
        ]);

        return $this->resolveValidated($validated, $manual);
    }

    /**
     * resolveMapping() after the request is validated: look up the course, component,
     * rooms and plan, and refuse anything that clashes. Map Seating Plan by drive calls
     * this once per component, inside its transaction, so a room taken by an earlier
     * component of the same submission is already visible here.
     *
     * @throws ValidationException
     */
    private function resolveValidated(array $validated, bool $manual): array
    {
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
            ->where('ed.cc_status', ExaminationDrive::CC_APPROVED)
            ->first(['dcm.id', DB::raw('COALESCE(DATE(dcm.proposed_start_at), DATE(dcm.exam_start_at), dcm.exam_date) as exam_date'), 'sub.subject_name', 'comp.component_name']);
        if ($component === null) {
            throw ValidationException::withMessages(['component_map_id' => 'That component is not scheduled for this course, or its examination drive is not approved by the Course Coordinator yet.']);
        }

        $rooms = $this->resolveRooms($validated, $manual);

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
        foreach ($rooms as $r) {
            $field = $manual ? $r['field'] . '.venue_id' : 'venue_ids';
            $name = $r['room']->venue_name;

            if ($plan && SeatPlanVenue::where('seat_plan_pk', $plan->pk)->where('venue_id', $r['room']->venue_id)->exists()) {
                throw ValidationException::withMessages([$field => "{$name} is already part of this seating plan."]);
            }

            // One room, one paper at a time: refuse a room another plan already holds on the same date.
            if ($component->exam_date) {
                $busy = DB::table('coe_seat_plan_venues as spv')
                    ->join('coe_seat_plans as sp', 'sp.pk', '=', 'spv.seat_plan_pk')
                    ->where('spv.venue_id', $r['room']->venue_id)
                    ->where('sp.exam_date', $component->exam_date)
                    ->when($plan, fn ($q) => $q->where('sp.pk', '!=', $plan->pk))
                    ->exists();
                if ($busy) {
                    throw ValidationException::withMessages([$field => "{$name} is already allocated to another examination on "
                        . date('d M Y', strtotime($component->exam_date)) . '.']);
                }
            }
        }

        if ($manual) {
            $this->checkManualRooms($rooms, $validated, $courseId, $batch, $category, $plan);
        }

        return compact('validated', 'courseId', 'batch', 'component', 'rooms', 'category', 'plan', 'manual');
    }

    /**
     * The examination rooms a Map Seating Plan request names.
     *
     * @return array<int, array{field: string, room: object, ots: ?array<int, int>}>
     *
     * @throws ValidationException
     */
    private function resolveRooms(array $validated, bool $manual): array
    {
        $examRooms = $this->examRooms();

        if (! $manual) {
            $ids = array_unique(array_map('intval', $validated['venue_ids']));
            // examRooms() is already building → floor → room, which is the fill order.
            $picked = $examRooms->filter(fn ($r) => in_array((int) $r->venue_id, $ids, true))->values();
            if ($picked->count() !== count($ids)) {
                throw ValidationException::withMessages(['venue_ids' => 'A selected room is not an examination room with a capacity.']);
            }

            return $picked->map(fn ($r) => ['field' => 'venue_ids', 'room' => $r, 'ots' => null])->all();
        }

        $rooms = [];
        foreach ($validated['rooms'] as $i => $row) {
            $room = $examRooms->firstWhere('venue_id', (int) $row['venue_id']);
            if ($room === null) {
                throw ValidationException::withMessages(["rooms.{$i}.venue_id" => 'That room is not an examination room with a capacity.']);
            }
            if (collect($rooms)->contains(fn ($r) => (int) $r['room']->venue_id === (int) $room->venue_id)) {
                throw ValidationException::withMessages(["rooms.{$i}.venue_id" => "{$room->venue_name} is already chosen in another row."]);
            }
            $rooms[] = ['field' => "rooms.{$i}", 'room' => $room, 'ots' => array_values(array_unique(array_map('intval', $row['ots'])))];
        }

        return $rooms;
    }

    /**
     * Manual Seating: every OT picked belongs to this course + category and has a roll
     * number, sits in one room only, and each room can hold its OTs — desks (after the
     * gap) and laptops both.
     *
     * @param  array<int, array{field: string, room: object, ots: array<int, int>}>  $rooms
     *
     * @throws ValidationException
     */
    private function checkManualRooms(array $rooms, array $validated, int $courseId, int $batch, int $category, ?SeatPlan $plan): void
    {
        $eligible = $this->participantQuery($courseId, $batch, $category)
            ->get(['sm.pk', 'rn.roll_number', 'lr.laptop_required'])
            ->keyBy(fn ($o) => (int) $o->pk);
        $listedBefore = $plan === null ? collect() : DB::table('coe_seat_plan_venue_ots as o')
            ->join('coe_seat_plan_venues as spv', 'spv.pk', '=', 'o.seat_plan_venue_pk')
            ->join('venue_master as vm', 'vm.venue_id', '=', 'spv.venue_id')
            ->where('o.seat_plan_pk', $plan->pk)
            ->pluck('vm.venue_name', 'o.student_master_pk');

        $policy = (int) $validated['laptop_requirement'];
        $step = max($plan?->rule_skip_alternate_seats ? 2 : 1, (int) ($validated['seat_gap'] ?? 0) + 1);
        $rolls = fn (array $pks) => $this->rollList(array_map(fn (int $pk) => $eligible[$pk]->roll_number ?? ('#' . $pk), $pks));
        $seen = [];

        foreach ($rooms as $r) {
            $field = $r['field'] . '.ots';
            $name = $r['room']->venue_name;

            if ($unknown = array_values(array_diff($r['ots'], $eligible->keys()->all()))) {
                throw ValidationException::withMessages([$field => count($unknown)
                    . ' selected OT(s) are not in this course and candidate category, or have no roll number.']);
            }
            if ($twice = array_values(array_intersect($r['ots'], array_keys($seen)))) {
                throw ValidationException::withMessages([$field => 'Roll No. ' . $rolls($twice) . ' is also selected in another room.']);
            }
            if ($before = array_values(array_filter($r['ots'], fn (int $pk) => isset($listedBefore[$pk])))) {
                throw ValidationException::withMessages([$field => 'Roll No. ' . $rolls($before) . ' is already seated in '
                    . $listedBefore[$before[0]] . ' of this seating plan.']);
            }
            $seen += array_flip($r['ots']);

            $capacity = intdiv((int) $r['room']->capacity + $step - 1, $step);
            if (count($r['ots']) > $capacity) {
                throw ValidationException::withMessages([$field => sprintf('%s seats %d OT(s)%s, but %d are selected.',
                    $name, $capacity, $step > 1 ? ' with the gap in seating' : '', count($r['ots']))]);
            }

            $laptops = $this->roomLaptops($r['room']);
            $needLaptop = count(array_filter($r['ots'], fn (int $pk) => $this->requiresLaptop($policy, $eligible[$pk]->laptop_required)));
            if ($needLaptop > $laptops) {
                throw ValidationException::withMessages([$field => sprintf('%s has %d laptop(s), but %d of the selected OTs need one.',
                    $name, $laptops, $needLaptop)]);
            }
        }
    }

    /** "VA001, VA002, VA003 and 4 more" */
    private function rollList(array $rolls): string
    {
        $shown = implode(', ', array_slice($rolls, 0, 5));

        return count($rolls) > 5 ? $shown . ' and ' . (count($rolls) - 5) . ' more' : $shown;
    }

    /**
     * Add the rooms to the plan (creating the plan if needed) and re-seat everyone.
     * Caller owns the transaction.
     *
     * @param  array<string, mixed>  $mapping  from resolveMapping()
     * @return array{plan: SeatPlan, summary: array<string, mixed>}
     */
    private function applyMapping(array $mapping, SeatAllocator $allocator): array
    {
        ['validated' => $validated, 'courseId' => $courseId, 'batch' => $batch, 'component' => $component,
            'rooms' => $rooms, 'category' => $category, 'plan' => $plan, 'manual' => $manual] = $mapping;

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
            'rule_avoid_same_service' => (bool) ($validated['rule_avoid_same_service'] ?? false),
            'rule_avoid_same_cadre' => (bool) ($validated['rule_avoid_same_cadre'] ?? false),
            'rule_alternate_gender' => (bool) ($validated['rule_alternate_gender'] ?? false),
            'rule_odd_even' => (bool) ($validated['rule_odd_even'] ?? false),
            'seat_gap' => (int) ($validated['seat_gap'] ?? 0),
            'modified_by' => Auth::id(),
            'modified_date' => $now,
        ])->save();

        $order = (int) SeatPlanVenue::where('seat_plan_pk', $plan->pk)->max('display_order');
        $filledBy = $this->filledByRole();
        $listed = [];
        foreach ($rooms as $r) {
            $planVenue = SeatPlanVenue::create([
                'seat_plan_pk' => $plan->pk,
                'venue_id' => $r['room']->venue_id,
                'purpose' => SeatPlanVenue::PURPOSE_ANY,
                'seating_mode' => self::SEATING_MODES[$manual ? 'manual' : 'system'],
                'display_order' => ++$order,
                'created_by' => Auth::id(),
                'created_date' => $now,
                'filled_by' => $filledBy,
            ]);
            foreach ($r['ots'] ?? [] as $studentPk) {
                $listed[] = [
                    'seat_plan_venue_pk' => (int) $planVenue->pk,
                    'seat_plan_pk' => (int) $plan->pk,
                    'student_master_pk' => $studentPk,
                    'created_by' => Auth::id(),
                    'created_date' => $now,
                ];
            }
        }

        if ($listed !== []) {
            foreach (array_chunk($listed, 500) as $chunk) {
                DB::table('coe_seat_plan_venue_ots')->insert($chunk);
            }
            // A seat swapped by hand in another room would keep the OT there; the room they
            // were just listed for wins.
            SeatAllocation::where('seat_plan_pk', $plan->pk)->where('is_manual', true)
                ->whereIn('student_master_pk', array_column($listed, 'student_master_pk'))
                ->delete();
        }

        return ['plan' => $plan, 'summary' => $this->reseat($plan, $allocator)];
    }

    /** The role a room was mapped under: COE, else Super Admin, else the user's first role. */
    private function filledByRole(): ?string
    {
        $roles = Auth::user()?->getRoleNames() ?? collect();

        foreach (['COE', 'Super Admin'] as $role) {
            if ($roles->contains($role)) {
                return $role;
            }
        }

        return $roles->first();
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
        [$invSent, $invNoAccount] = $this->notifyInvigilators($plan, $notifications);

        $message = "Seating plan frozen. {$sent} OT(s) notified of their seat.";
        if ($noAccount > 0) {
            $message .= " {$noAccount} OT(s) have no login account and were not notified.";
        }
        if ($invSent + $invNoAccount > 0) {
            $message .= " {$invSent} invigilator(s) notified of their duty."
                . ($invNoAccount > 0 ? " {$invNoAccount} have no login account and were not notified." : '');
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

    // ── Add Invigilators ────────────────────────────────────────────────────

    /**
     * Add Invigilators, opened from one listing row: every room mapped for the same
     * examination drive (same course + batch), grouped by subject then component, with who
     * invigilates each. A plan made before drives existed shows its own rooms only.
     * Offline Objective components are left out — they take no invigilators.
     */
    public function invigilators(int $id): JsonResponse
    {
        [, $plan] = $this->findPlanVenue($id);

        $component = $plan->examination_drive_component_map_id === null ? null
            : DB::table('examination_drive_component_maps')->where('id', $plan->examination_drive_component_map_id)
                ->first(['examination_drive_id', 'exam_type']);
        if ($component && $component->exam_type === self::OFFLINE_OBJECTIVE) {
            return response()->json(['status' => 'error', 'message' => 'An Offline Objective paper takes no invigilators.'], 422);
        }
        if ($plan->isPublished()) {
            return response()->json(['status' => 'error', 'message' => 'This seating plan is frozen; its invigilators can no longer be changed.'], 422);
        }

        $rooms = $this->invigilatorRooms()
            ->when($component, fn ($q) => $q->where('dcm.examination_drive_id', $component->examination_drive_id)
                ->where('sp.course_master_pk', $plan->course_master_pk)
                ->where('sp.batch', $plan->batch),
                fn ($q) => $q->where('sp.pk', $plan->pk))
            ->where(fn ($w) => $w->whereNull('dcm.exam_type')->orWhere('dcm.exam_type', '!=', self::OFFLINE_OBJECTIVE))
            ->orderBy('sp.subject_name')
            ->orderBy('dcm.exam_date')
            ->orderBy('sp.exam_name')
            ->orderBy('sp.pk')
            ->orderBy('spv.display_order')
            ->get(['spv.pk', 'spv.seat_plan_pk', 'spv.venue_id', 'sp.examination_drive_component_map_id as map_id', 'sp.subject_name', 'sp.exam_name', 'sp.exam_date as plan_date', 'sp.status as plan_status',
                'dcm.exam_date', 'dcm.exam_type', 'vm.venue_name',
                DB::raw('COALESCE(rp.building_name, eb.building_name) as building_name'),
                DB::raw('COALESCE(rp.floor_name, ef.floor_name) as floor_name'),
                'inv.chief_faculty_pk', 'inv.invigilator_faculty_pk', 'inv.assistant_faculty_pk', 'inv.mts_employee_pk']);
        // A role the paper does not take shows empty, whatever was saved before its exam type changed.
        foreach ($rooms as $r) {
            foreach (self::ROLES_NOT_TAKEN[$r->exam_type] ?? [] as $role) {
                $r->{self::INVIGILATOR_ROLES[$role]['column']} = null;
            }
        }

        // Roll-number serials seated in each room, for the OT column ("1-252").
        $serials = DB::table('coe_seat_allocations as sa')
            ->join('coe_roll_numbers as rn', 'rn.pk', '=', 'sa.roll_number_pk')
            ->whereIn('sa.seat_plan_pk', $rooms->pluck('seat_plan_pk')->unique()->all())
            ->get(['sa.seat_plan_pk', 'sa.venue_id', 'rn.serial_number'])
            ->groupBy(fn ($s) => $s->seat_plan_pk . ':' . $s->venue_id);

        $subjects = $rooms->map(fn ($r) => [
            'id' => (int) $r->pk,
            // One person invigilates one room a day: rooms of the same exam date share a key.
            'paper' => $this->invigilationDay($r->exam_date ?? $r->plan_date, (int) $r->seat_plan_pk),
            'subject' => trim((string) $r->subject_name) ?: 'General',
            'component' => $r->exam_name ?: '—',
            'exam_date' => $this->examDateLabel($r->exam_date ?? $r->plan_date),
            'building' => $r->building_name ?: '—',
            'floor' => $r->floor_name ?: '—',
            'room' => $r->venue_name,
            'ot' => $this->serialRuns(($serials[$r->seat_plan_pk . ':' . $r->venue_id] ?? collect())->pluck('serial_number')->all()),
            'chief' => $r->chief_faculty_pk ? (int) $r->chief_faculty_pk : null,
            'invigilator' => $r->invigilator_faculty_pk ? (int) $r->invigilator_faculty_pk : null,
            'assistant' => $r->assistant_faculty_pk ? (int) $r->assistant_faculty_pk : null,
            'mts' => $r->mts_employee_pk ? (int) $r->mts_employee_pk : null,
            // Roles this paper's exam type does not take: shown disabled.
            'locked' => self::ROLES_NOT_TAKEN[$r->exam_type] ?? [],
            // A frozen plan's room is shown read-only and is not saved.
            'frozen' => (int) $r->plan_status === SeatPlan::STATUS_PUBLISHED,
        ])
            ->groupBy('subject')
            ->map(fn ($rows, $subject) => ['subject' => $subject, 'rooms' => $rows->values()])
            ->values();

        // Someone already assigned stays in the list even after leaving the master's active set.
        $assignedFaculty = $rooms->flatMap(fn ($r) => [$r->chief_faculty_pk, $r->invigilator_faculty_pk, $r->assistant_faculty_pk])->filter()->unique()->values()->all();
        $assignedMts = $rooms->pluck('mts_employee_pk')->filter()->unique()->values()->all();

        // Who is already invigilating, on these days, in rooms this dialog does not show
        // (another drive's or course's exam): day key -> { f: [faculty pks], m: [MTS pks] }.
        $busy = [];
        foreach ($this->dayRoomsOutside($rooms->map(fn ($r) => $r->exam_date ?? $r->plan_date)->filter()->all(), $rooms->pluck('pk')->all()) as $o) {
            $day = $this->invigilationDay($o->day, 0);
            foreach ([$o->chief_faculty_pk, $o->invigilator_faculty_pk, $o->assistant_faculty_pk] as $pk) {
                if ($pk) {
                    $busy[$day]['f'][] = (int) $pk;
                }
            }
            if ($o->mts_employee_pk) {
                $busy[$day]['m'][] = (int) $o->mts_employee_pk;
            }
        }

        return response()->json([
            'status' => 'success',
            'subjects' => $subjects,
            'busy' => (object) $busy,
            'faculty' => $this->facultyOptions($assignedFaculty),
            'mts' => $this->mtsOptions($assignedMts),
        ]);
    }

    /**
     * Map Invigilator: save who invigilates each room shown. A role left empty is cleared.
     * One person cannot hold two roles in one room, nor invigilate two rooms on the same exam
     * date — any paper, any drive (a room with no date: its own plan only).
     */
    public function storeInvigilators(Request $request): JsonResponse
    {
        $rules = ['rows' => ['required', 'array', 'min:1'], 'rows.*.id' => ['required', 'integer', 'distinct']];
        foreach (array_keys(self::INVIGILATOR_ROLES) as $role) {
            $rules["rows.*.{$role}"] = ['nullable', 'integer'];
        }
        $validated = $request->validate($rules, ['rows.required' => 'There is no room to map.']);
        $rows = $validated['rows'];

        $rooms = $this->invigilatorRooms()
            ->whereIn('spv.pk', array_map(fn ($r) => (int) $r['id'], $rows))
            ->get(['spv.pk', 'sp.pk as plan_pk', 'sp.status as plan_status', 'sp.examination_drive_component_map_id as map_id', 'dcm.exam_type', 'vm.venue_name',
                DB::raw('DATE(COALESCE(dcm.exam_date, sp.exam_date)) as day'),
                'inv.chief_faculty_pk', 'inv.invigilator_faculty_pk', 'inv.assistant_faculty_pk', 'inv.mts_employee_pk'])
            ->keyBy(fn ($r) => (int) $r->pk);

        // Who may be picked: the active masters, plus whoever a submitted room already holds.
        $currentFaculty = $rooms->flatMap(fn ($r) => [$r->chief_faculty_pk, $r->invigilator_faculty_pk, $r->assistant_faculty_pk])->filter()->all();
        $faculty = collect($this->facultyOptions($currentFaculty))->keyBy('id');
        $mts = collect($this->mtsOptions($rooms->pluck('mts_employee_pk')->filter()->all()))->keyBy('id');

        $errors = [];
        $paper = [];   // "<component>|f|<person>" or "<component>|m|<person>" => [row index, field]
        foreach ($rows as $i => $row) {
            $room = $rooms[(int) $row['id']] ?? null;
            if ($room === null) {
                $errors["rows.{$i}.id"] = 'That room is no longer part of a seating plan.';
                continue;
            }
            if ($room->exam_type === self::OFFLINE_OBJECTIVE) {
                $errors["rows.{$i}.id"] = 'An Offline Objective paper takes no invigilators.';
                continue;
            }
            if ((int) $room->plan_status === SeatPlan::STATUS_PUBLISHED) {
                $errors["rows.{$i}.id"] = "The seating plan of {$room->venue_name} is frozen; its invigilators can no longer be changed.";
                continue;
            }
            $inRoom = [];
            foreach (self::INVIGILATOR_ROLES as $role => $def) {
                $pk = $row[$role] ?? null;
                if ($pk === null) {
                    continue;
                }
                if (in_array($role, self::ROLES_NOT_TAKEN[$room->exam_type] ?? [], true)) {
                    $errors["rows.{$i}.{$role}"] = "An {$room->exam_type} paper takes no {$def['label']}.";
                    continue;
                }
                $pk = (int) $pk;
                $people = $def['faculty'] ? $faculty : $mts;
                if (! $people->has($pk)) {
                    $errors["rows.{$i}.{$role}"] = $def['faculty'] ? 'Select an active faculty member.' : 'Select an active Multi Tasking Staff member.';
                    continue;
                }
                if ($def['faculty'] && isset($inRoom[$pk])) {
                    $errors["rows.{$i}.{$role}"] = $people[$pk]['text'] . ' is already ' . self::INVIGILATOR_ROLES[$inRoom[$pk]]['label'] . ' in this room.';
                    continue;
                }
                $inRoom[$pk] = $role;
                // One person, one room a day.
                $key = $this->invigilationDay($room->day, (int) $room->plan_pk) . '|' . ($def['faculty'] ? 'f' : 'm') . '|' . $pk;
                if (isset($paper[$key])) {
                    $errors["rows.{$i}.{$role}"] = $people[$pk]['text'] . ' already invigilates ' . $paper[$key]
                        . ($room->day ? ' on ' . date('d/m/Y', strtotime($room->day)) : '') . '.';
                    continue;
                }
                $paper[$key] = $room->venue_name;
            }
        }

        // Rooms on the same dates that this request does not include (any drive) still hold their people.
        $days = $rooms->pluck('day')->filter()->unique()->all();
        if ($days !== [] && $errors === []) {
            $others = $this->dayRoomsOutside($days, $rooms->keys()->all());
            foreach ($rows as $i => $row) {
                $room = $rooms[(int) $row['id']];
                if (! $room->day) {
                    continue;
                }
                foreach (self::INVIGILATOR_ROLES as $role => $def) {
                    $pk = isset($row[$role]) ? (int) $row[$role] : null;
                    $clash = $pk === null ? null : $others->first(fn ($o) => $o->day === $room->day && ($def['faculty']
                        ? in_array($pk, [(int) $o->chief_faculty_pk, (int) $o->invigilator_faculty_pk, (int) $o->assistant_faculty_pk], true)
                        : (int) $o->mts_employee_pk === $pk));
                    if ($clash) {
                        $name = ($def['faculty'] ? $faculty : $mts)[$pk]['text'];
                        $errors["rows.{$i}.{$role}"] = "{$name} already invigilates {$clash->venue_name}"
                            . ($clash->exam_name ? " ({$clash->exam_name})" : '') . ' on ' . date('d/m/Y', strtotime($room->day)) . '.';
                    }
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $now = now();
        DB::transaction(function () use ($rows, $now) {
            foreach ($rows as $row) {
                $values = [];
                foreach (self::INVIGILATOR_ROLES as $role => $def) {
                    $values[$def['column']] = isset($row[$role]) ? (int) $row[$role] : null;
                }
                $where = ['seat_plan_venue_pk' => (int) $row['id']];
                $table = DB::table('coe_seat_plan_venue_invigilators');

                if (array_filter($values) === []) {
                    $table->where($where)->delete();
                } elseif ((clone $table)->where($where)->exists()) {
                    $table->where($where)->update($values + ['modified_by' => Auth::id(), 'modified_date' => $now]);
                } else {
                    $table->insert($where + $values + ['created_by' => Auth::id(), 'created_date' => $now]);
                }
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Invigilators saved for ' . count($rows) . ' room(s).']);
    }

    /** Key of an invigilation day: "d2026-11-11"; a room with no exam date is its own plan's day. */
    private function invigilationDay($date, int $planPk): string
    {
        return $date ? 'd' . date('Y-m-d', strtotime((string) $date)) : 'p' . $planPk;
    }

    /**
     * Rooms with invigilators on the given exam dates, other than $exceptRooms — any drive.
     *
     * @param  array<int, string>  $dates
     * @param  array<int, int>  $exceptRooms  coe_seat_plan_venues.pk
     */
    private function dayRoomsOutside(array $dates, array $exceptRooms): Collection
    {
        $dates = array_values(array_unique(array_map(fn ($d) => date('Y-m-d', strtotime((string) $d)), $dates)));
        if ($dates === []) {
            return collect();
        }

        return $this->invigilatorRooms()
            ->whereNotNull('inv.seat_plan_venue_pk')
            ->whereIn(DB::raw('DATE(COALESCE(dcm.exam_date, sp.exam_date))'), $dates)
            ->when($exceptRooms !== [], fn ($q) => $q->whereNotIn('spv.pk', $exceptRooms))
            ->get(['spv.pk', 'vm.venue_name', 'sp.exam_name', DB::raw('DATE(COALESCE(dcm.exam_date, sp.exam_date)) as day'),
                'inv.chief_faculty_pk', 'inv.invigilator_faculty_pk', 'inv.assistant_faculty_pk', 'inv.mts_employee_pk']);
    }

    /** Rooms of seating plans with their building, floor and invigilators — for Add Invigilators. */
    private function invigilatorRooms()
    {
        return DB::table('coe_seat_plan_venues as spv')
            ->join('coe_seat_plans as sp', 'sp.pk', '=', 'spv.seat_plan_pk')
            ->join('venue_master as vm', 'vm.venue_id', '=', 'spv.venue_id')
            ->leftJoin('examination_drive_component_maps as dcm', 'dcm.id', '=', 'sp.examination_drive_component_map_id')
            ->leftJoinSub(ExamRooms::placements(), 'rp', 'rp.venue_id', '=', 'vm.venue_id')
            ->leftJoin('exam_building_master as eb', 'eb.pk', '=', 'vm.building_master_pk')
            ->leftJoin('exam_floor_master as ef', 'ef.pk', '=', 'vm.floor_master_pk')
            ->leftJoin('coe_seat_plan_venue_invigilators as inv', 'inv.seat_plan_venue_pk', '=', 'spv.pk');
    }

    /**
     * Active faculty for the three invigilator roles, plus $keep (already assigned).
     *
     * @param  array<int, int|string>  $keep
     * @return array<int, array{id: int, text: string}>
     */
    private function facultyOptions(array $keep = []): array
    {
        return DB::table('faculty_master')
            ->where(fn ($w) => $w->where('active_inactive', 1)->orWhereIn('pk', $keep))
            ->get(['pk', 'full_name', 'first_name', 'middle_name', 'last_name', 'faculty_code'])
            ->map(function ($f) {
                $name = trim((string) $f->full_name) ?: trim(implode(' ', array_filter([$f->first_name, $f->middle_name, $f->last_name])));

                return ['id' => (int) $f->pk, 'text' => ($name ?: 'Faculty #' . $f->pk) . ($f->faculty_code ? ' (' . trim($f->faculty_code) . ')' : '')];
            })
            ->sortBy('text', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * Active Multi Tasking Staff for the MTS role, plus $keep (already assigned).
     *
     * @param  array<int, int|string>  $keep
     * @return array<int, array{id: int, text: string}>
     */
    private function mtsOptions(array $keep = []): array
    {
        return DB::table('employee_master as em')
            ->leftJoin('designation_master as dm', 'dm.pk', '=', 'em.designation_master_pk')
            ->where(fn ($w) => $w->where(fn ($a) => $a->where('dm.designation_name', self::MTS_DESIGNATION)->where('em.status', 1))
                ->orWhereIn('em.pk', $keep))
            ->get(['em.pk', 'em.first_name', 'em.middle_name', 'em.last_name', 'em.emp_id'])
            ->map(function ($e) {
                $name = trim(implode(' ', array_filter([$e->first_name, $e->middle_name, $e->last_name])));

                return ['id' => (int) $e->pk, 'text' => ($name ?: 'Employee #' . $e->pk) . ($e->emp_id ? ' (' . trim($e->emp_id) . ')' : '')];
            })
            ->sortBy('text', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * Roll-number serials as runs: "1-252", "1-50, 61-80"; more than three runs is a count.
     *
     * @param  array<int, int|string>  $serials
     */
    private function serialRuns(array $serials): string
    {
        $serials = array_values(array_unique(array_map('intval', $serials)));
        sort($serials);
        if ($serials === []) {
            return '—';
        }
        $runs = [];
        foreach ($serials as $n) {
            $last = array_key_last($runs);
            if ($last !== null && $runs[$last][1] === $n - 1) {
                $runs[$last][1] = $n;
            } else {
                $runs[] = [$n, $n];
            }
        }
        if (count($runs) > 3) {
            return count($serials) . ' OTs';
        }

        return implode(', ', array_map(fn ($r) => $r[0] === $r[1] ? (string) $r[0] : $r[0] . '-' . $r[1], $runs));
    }

    public function edit(int $id)
    {
        [$planVenue, $plan] = $this->findPlanVenue($id);

        return view('admin.coe.venue_allocation.edit', [
            'allocation' => $this->allocationDetail($id),
            'moveRooms' => $plan->isPublished() ? [] : $this->moveRooms($plan, $planVenue),
        ]);
    }

    /** View Seating Plan — the room read-only, from the listing's View action. */
    public function show(int $id)
    {
        return view('admin.coe.venue_allocation.show', ['allocation' => $this->allocationDetail($id)]);
    }

    /**
     * Print: the room's seating plan as a standalone, LBSNAA-branded A4 sheet (no app
     * chrome) — details, the hall with each desk's roll number, the seat list and the
     * invigilators. Opened in a new tab from Edit / View Seating Plan; it prints itself.
     */
    public function printSheet(int $id)
    {
        $allocation = $this->allocationDetail($id);

        return view('admin.coe.venue_allocation.print', [
            'allocation' => $allocation,
            'drive' => $allocation['drive'],
            'blocks' => self::SEAT_BLOCKS,
            'printedAt' => now()->format('d-m-Y h:i A'),
        ]);
    }

    /**
     * Download (CSV / Excel / PDF) of one room's seat list — Edit and View Seating Plan.
     * The shared COE/master export: LBSNAA-branded, the exam and room in the header band,
     * audit-logged. Print is printSheet() (it carries the hall drawing).
     */
    public function seatsExport(int $id, string $format)
    {
        $allocation = $this->allocationDetail($id);
        $drive = $allocation['drive'];
        $text = fn ($v) => html_entity_decode((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $blockNames = ['A' => 'Block A', 'B' => 'Block B (Centre)', 'C' => 'Block C'];
        $part = fn (array $s, int $i) => explode('-', $s['label'], 2)[$i] ?? $s['label'];

        $columns = [
            'sno' => ['heading' => 'S. No.', 'width' => '6%', 'align' => 'center', 'value' => fn ($s, int $i) => $i + 1],
            'roll' => ['heading' => 'Roll No.', 'width' => '14%', 'value' => fn ($s) => $s['roll']],
            'name' => ['heading' => 'Name', 'width' => '32%', 'value' => fn ($s) => $s['name']],
            'seat' => ['heading' => 'Seat', 'width' => '9%', 'align' => 'center', 'value' => fn ($s) => $part($s, 1)],
            'block' => ['heading' => 'Block', 'width' => '17%', 'value' => fn ($s) => $blockNames[$part($s, 0)] ?? $part($s, 0)],
            'remarks' => ['heading' => 'Remarks', 'width' => '22%', 'value' => fn ($s) => collect([
                $s['laptop'] ? 'Laptop' : null, $s['scriber'] ? 'Scriber' : null, $s['manual'] ? 'Moved' : null,
            ])->filter()->implode(', ') ?: '-'],
        ];

        $filters = implode('  |  ', array_filter([
            $drive ? 'Drive: ' . $text($drive) : null,
            'Course: ' . $text($allocation['course']) . ' (' . $allocation['batch'] . ')',
            'Subject: ' . $text($allocation['subject']) . ' / ' . $text($allocation['component']),
            $allocation['exam_date'] ? 'Exam Date: ' . $allocation['exam_date'] : null,
            'Room: ' . $allocation['room'] . ' (' . collect([$allocation['building'], $allocation['floor']])->filter()->implode(' / ') . ')',
            'Candidates: ' . $allocation['category'],
            'Status: ' . ($allocation['frozen'] ? 'Frozen' : 'Not frozen'),
        ]));

        return $this->renderMasterExport(
            $format,
            $allocation['seats']->values(),
            $columns,
            'Seating Plan — ' . $allocation['room'],
            'SeatingPlan_' . preg_replace('/[^A-Za-z0-9]+/', '-', (string) $allocation['room']) . '_' . $id,
            $filters,
            'Nobody is seated in this room.'
        );
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
        // Some names are stored HTML-encoded ("&#039;"); decode so the page shows the text.
        $text = fn ($v) => $v === null ? null : html_entity_decode((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return [
            'id' => (int) $planVenue->pk,
            'drive' => $plan->examination_drive_component_map_id === null ? null : $text(DB::table('examination_drive_component_maps as dcm')
                ->join('examination_drives as ed', 'ed.id', '=', 'dcm.examination_drive_id')
                ->where('dcm.id', $plan->examination_drive_component_map_id)
                ->value('ed.drive_name')),
            'room' => $room->venue_name,
            'building' => $room->building,
            'floor' => $room->floor,
            'course' => $text($plan->course?->course_name),
            'batch' => $plan->batch,
            'subject' => $text($plan->subject_name) ?: 'General',
            'component' => $text($plan->exam_name),
            'category' => SeatPlan::categoryLabel((int) $plan->candidate_category),
            'exam_date' => $plan->exam_date?->format('d M Y'),
            'frozen' => $plan->isPublished(),
            // "System Generated" until an administrator moves someone by hand.
            'source' => $planVenue->isManual() ? 'Manual Seating'
                : ($seats->contains('manual', true) ? 'Manually Adjusted' : 'System Generated'),
            'capacity' => (int) $room->capacity,
            'laptop_seats' => $room->laptop_seats,
            'scriber_seats' => $room->scriber_seats,
            'seats' => $seats->values(),
            // An Offline Objective paper takes no invigilators; otherwise who is assigned, by role.
            'takes_invigilators' => $plan->examination_drive_component_map_id === null
                || DB::table('examination_drive_component_maps')->where('id', $plan->examination_drive_component_map_id)
                    ->value('exam_type') !== self::OFFLINE_OBJECTIVE,
            'invigilators' => $this->roomInvigilators((int) $planVenue->pk),
        ];
    }

    /**
     * Who invigilates one room of a plan, in role order; an unfilled role has a null name.
     *
     * @return array<int, array{role: string, name: ?string}>
     */
    private function roomInvigilators(int $seatPlanVenuePk): array
    {
        $inv = DB::table('coe_seat_plan_venue_invigilators as inv')
            ->leftJoin('faculty_master as f1', 'f1.pk', '=', 'inv.chief_faculty_pk')
            ->leftJoin('faculty_master as f2', 'f2.pk', '=', 'inv.invigilator_faculty_pk')
            ->leftJoin('faculty_master as f3', 'f3.pk', '=', 'inv.assistant_faculty_pk')
            ->leftJoin('employee_master as em', 'em.pk', '=', 'inv.mts_employee_pk')
            ->where('inv.seat_plan_venue_pk', $seatPlanVenuePk)
            // Faculty: full_name, else first/middle/last — as facultyOptions() names them.
            ->first([
                DB::raw("COALESCE(NULLIF(TRIM(f1.full_name), ''), TRIM(CONCAT_WS(' ', f1.first_name, f1.middle_name, f1.last_name))) as chief_name"), 'f1.faculty_code as chief_code',
                DB::raw("COALESCE(NULLIF(TRIM(f2.full_name), ''), TRIM(CONCAT_WS(' ', f2.first_name, f2.middle_name, f2.last_name))) as invigilator_name"), 'f2.faculty_code as invigilator_code',
                DB::raw("COALESCE(NULLIF(TRIM(f3.full_name), ''), TRIM(CONCAT_WS(' ', f3.first_name, f3.middle_name, f3.last_name))) as assistant_name"), 'f3.faculty_code as assistant_code',
                DB::raw("TRIM(CONCAT_WS(' ', em.first_name, em.middle_name, em.last_name)) as mts_name"), 'em.emp_id as mts_code',
            ]);

        $out = [];
        foreach (self::INVIGILATOR_ROLES as $role => $def) {
            $name = trim((string) ($inv->{$role . '_name'} ?? ''));
            $code = trim((string) ($inv->{$role . '_code'} ?? ''));
            $out[] = ['role' => $def['label'], 'name' => $name === '' ? null : $name . ($code !== '' ? " ({$code})" : '')];
        }

        return $out;
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
     * Change Room: move OTs seated in this room to another room — one the paper already
     * uses, or a free examination room, which joins the plan as a Manual room. Each OT gets
     * the first free desk that suits them there (a laptop desk if they need one); the seat
     * is marked manual so a later re-run of the allocator keeps it. The other OTs stay put.
     */
    public function move(Request $request, int $id): JsonResponse
    {
        [$planVenue, $plan] = $this->findPlanVenue($id);
        if ($plan->isPublished()) {
            return response()->json(['status' => 'error', 'message' => 'A frozen seating plan cannot be changed.'], 422);
        }

        $validated = $request->validate([
            'seats' => ['required', 'array', 'min:1'],
            'seats.*' => ['integer', 'min:1'],
            'venue_id' => ['required', 'integer'],
        ], [
            'seats.required' => 'Select the OTs to move.',
            'seats.min' => 'Select the OTs to move.',
            'venue_id.required' => 'Select the room to move them to.',
        ]);
        $venueId = (int) $validated['venue_id'];
        $seats = array_values(array_unique(array_map('intval', $validated['seats'])));
        if ($venueId === (int) $planVenue->venue_id) {
            throw ValidationException::withMessages(['venue_id' => 'They already sit in this room. Pick another room.']);
        }
        $target = collect($this->moveRooms($plan, $planVenue))->firstWhere('id', $venueId);
        if ($target === null) {
            throw ValidationException::withMessages(['venue_id' => 'That room is not an examination room free for this paper on its exam date.']);
        }

        try {
            $moved = DB::transaction(function () use ($plan, $planVenue, $venueId, $seats, $target) {
                $moving = SeatAllocation::where('seat_plan_pk', $plan->pk)->where('venue_id', $planVenue->venue_id)
                    ->whereIn('seat_number', $seats)->orderBy('seat_number')->lockForUpdate()->get();
                if ($moving->count() !== count($seats)) {
                    throw ValidationException::withMessages(['seats' => 'This room was changed meanwhile. Reload the page and try again.']);
                }

                $targetVenue = SeatPlanVenue::where('seat_plan_pk', $plan->pk)->where('venue_id', $venueId)->lockForUpdate()->first()
                    ?? SeatPlanVenue::create([
                        'seat_plan_pk' => $plan->pk,
                        'venue_id' => $venueId,
                        'purpose' => SeatPlanVenue::PURPOSE_ANY,
                        'seating_mode' => SeatPlanVenue::MODE_MANUAL,
                        'display_order' => (int) SeatPlanVenue::where('seat_plan_pk', $plan->pk)->max('display_order') + 1,
                        'created_by' => Auth::id(),
                        'created_date' => now(),
                        'filled_by' => $this->filledByRole(),
                    ]);

                $room = $this->roomDetails($venueId);
                $occupants = SeatAllocation::where('seat_plan_pk', $plan->pk)->where('venue_id', $venueId)
                    ->lockForUpdate()->get(['seat_number', 'requires_laptop']);
                $taken = $occupants->pluck('seat_number')->mapWithKeys(fn ($n) => [(int) $n => true])->all();
                // No laptop desks marked on the room's seat map: Room Master's laptop count caps it.
                $laptopsLeft = $room->laptop_seats === []
                    ? (int) $target['laptop'] - $occupants->where('requires_laptop', true)->count() : PHP_INT_MAX;
                $step = max(1, (int) $plan->seat_gap + 1);

                foreach ($moving as $a) {
                    if ($a->requires_laptop && $laptopsLeft <= 0) {
                        throw ValidationException::withMessages(['venue_id' => "{$room->venue_name} has no laptop left for Roll No. "
                            . $this->rollOf($a) . '.']);
                    }
                    $seat = $this->freeDesk($room, $taken, (bool) $a->requires_laptop, (bool) $a->requires_scriber, $step);
                    if ($seat === null) {
                        throw ValidationException::withMessages(['venue_id' => "{$room->venue_name} has no free "
                            . ($a->requires_laptop && $room->laptop_seats !== [] ? 'laptop ' : '') . 'desk for Roll No. ' . $this->rollOf($a) . '.']);
                    }
                    $taken[$seat] = true;
                    if ($a->requires_laptop) {
                        $laptopsLeft--;
                    }

                    // A Manual room seats exactly its listed OTs: the OT leaves the old list and,
                    // if the new room is Manual, joins its list.
                    DB::table('coe_seat_plan_venue_ots')->where('seat_plan_pk', $plan->pk)
                        ->where('student_master_pk', $a->student_master_pk)->delete();
                    if ($targetVenue->isManual()) {
                        DB::table('coe_seat_plan_venue_ots')->insert([
                            'seat_plan_venue_pk' => (int) $targetVenue->pk,
                            'seat_plan_pk' => (int) $plan->pk,
                            'student_master_pk' => (int) $a->student_master_pk,
                            'created_by' => Auth::id(),
                            'created_date' => now(),
                        ]);
                    }

                    $a->forceFill(['venue_id' => $venueId, 'seat_number' => $seat, 'is_manual' => true,
                        'modified_by' => Auth::id(), 'modified_date' => now()])->save();
                }

                return [$room->venue_name, $moving->count()];
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('COE seat room change failed', ['id' => $id, 'exception' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => 'The room could not be changed. Nothing was changed.'], 500);
        }

        [$name, $count] = $moved;

        return response()->json(['status' => 'success', 'message' => "{$count} OT(s) moved to {$name}."]);
    }

    /**
     * Rooms an OT of this room can move to: the plan's other rooms with a free desk, then
     * examination rooms no plan holds on the paper's exam date.
     *
     * @return array<int, array{id: int, name: string, group: string, free: int, laptop: int}>
     */
    private function moveRooms(SeatPlan $plan, SeatPlanVenue $planVenue): array
    {
        $inPlan = SeatPlanVenue::where('seat_plan_pk', $plan->pk)->pluck('venue_id')->map(fn ($v) => (int) $v)->all();
        $seated = SeatAllocation::where('seat_plan_pk', $plan->pk)->groupBy('venue_id')
            ->selectRaw('venue_id, COUNT(*) as n')->pluck('n', 'venue_id');
        $busy = $plan->exam_date === null ? [] : DB::table('coe_seat_plan_venues as spv')
            ->join('coe_seat_plans as sp', 'sp.pk', '=', 'spv.seat_plan_pk')
            ->whereDate('sp.exam_date', $plan->exam_date)
            ->where('sp.pk', '!=', $plan->pk)
            ->pluck('spv.venue_id')->map(fn ($v) => (int) $v)->all();

        return $this->examRooms()
            ->reject(fn ($r) => (int) $r->venue_id === (int) $planVenue->venue_id || in_array((int) $r->venue_id, $busy, true))
            ->map(function ($r) use ($inPlan, $seated) {
                $own = in_array((int) $r->venue_id, $inPlan, true);

                return [
                    'id' => (int) $r->venue_id,
                    'name' => trim($r->venue_name . ' · ' . $r->building . ' / ' . $r->floor),
                    'group' => $own ? 'Rooms of this paper' : 'Other free rooms',
                    'free' => max(0, (int) $r->capacity - (int) ($seated[$r->venue_id] ?? 0)),
                    'laptop' => $this->roomLaptops($r),
                ];
            })
            ->filter(fn ($r) => $r['free'] > 0)
            ->sortBy(fn ($r) => $r['group'] === 'Rooms of this paper' ? 0 : 1, SORT_REGULAR)
            ->values()
            ->all();
    }

    /**
     * The first free desk of a room for one OT: a laptop desk if they need one (when the
     * seat map marks them), else a desk that is not a laptop desk; a scriber desk for a
     * scriber OT; desks on the plan's seat gap before the rest.
     *
     * @param  array<int, true>  $taken
     */
    private function freeDesk(object $room, array $taken, bool $laptop, bool $scriber, int $step): ?int
    {
        $laptopDesks = array_flip($room->laptop_seats);
        $scriberDesks = array_flip($room->scriber_seats);
        $best = null;
        $bestScore = null;
        for ($n = 1; $n <= (int) $room->capacity; $n++) {
            if (isset($taken[$n])) {
                continue;
            }
            if ($laptop && $laptopDesks !== [] && ! isset($laptopDesks[$n])) {
                continue;
            }
            $score = [
                ! $laptop && isset($laptopDesks[$n]) ? 1 : 0,
                $scriberDesks !== [] && ($scriber xor isset($scriberDesks[$n])) ? 1 : 0,
                ($n - 1) % $step === 0 ? 0 : 1,
            ];
            if ($bestScore === null || $score < $bestScore) {
                [$best, $bestScore] = [$n, $score];
            }
        }

        return $best;
    }

    private function rollOf(SeatAllocation $a): string
    {
        return (string) (DB::table('coe_roll_numbers')->where('pk', $a->roll_number_pk)->value('roll_number') ?: '#' . $a->student_master_pk);
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
        // Manual Seating rooms and the OTs listed for each.
        $assignedRooms = DB::table('coe_seat_plan_venue_ots as o')
            ->join('coe_seat_plan_venues as spv', 'spv.pk', '=', 'o.seat_plan_venue_pk')
            ->where('o.seat_plan_pk', $plan->pk)
            ->pluck('spv.venue_id', 'o.student_master_pk')
            ->map(fn ($venueId) => (int) $venueId)
            ->all();

        $result = $allocator->allocate($plan, $participants, $planVenues, $venues, $manualSeats, $assignedRooms);

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

        return $result['summary'] + ['unplaced_rolls' => array_map(fn (object $p) => $p->roll_number, $result['unplaced'])];
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
            ->select(['sm.pk', 'sm.ph', 'sm.service_master_pk', 'sm.cadre_master_pk', 'sm.gender',
                'rn.pk as roll_number_pk', 'rn.roll_number', 'rn.serial_number', 'lr.laptop_required', 'lr.scriber_required'])
            ->orderBy('rn.serial_number')
            ->orderBy('sm.pk')
            ->get()
            ->map(fn ($row) => (object) [
                'pk' => (int) $row->pk,
                'roll_number_pk' => (int) $row->roll_number_pk,
                'roll_number' => (string) $row->roll_number,
                'serial_number' => (int) $row->serial_number,
                // Read by the seating rules: same service / same cadre / female-male.
                'service_master_pk' => $row->service_master_pk,
                'cadre_master_pk' => $row->cadre_master_pk,
                'gender' => $row->gender,
                'requires_laptop' => $this->requiresLaptop((int) $plan->laptop_requirement, $row->laptop_required),
                'requires_scriber' => (int) $row->scriber_required === 1 && LaptopDeclarationService::isPwbd($row->ph),
            ]);
    }

    /** OTs of a course + batch that have a roll number, narrowed to a candidate category. */
    private function participantQuery(int $courseId, int $batch, int $category)
    {
        return DB::table('student_master as sm')
            ->join('coe_roll_numbers as rn', 'rn.student_master_pk', '=', 'sm.pk')
            ->leftJoin('coe_laptop_responses as lr', 'lr.student_master_pk', '=', 'sm.pk')
            // On the course by student_master or by student_master_course__map — see CourseParticipants.
            ->whereIn('sm.pk', CourseParticipants::of($courseId, $batch))
            ->when($category === SeatPlan::CATEGORY_GENERAL, fn ($q) => $q->where(fn ($w) => $w
                ->whereNull('lr.has_disability')->orWhere('lr.has_disability', 0)))
            ->when($category === SeatPlan::CATEGORY_PH, fn ($q) => $q->where('lr.has_disability', 1));
    }

    /**
     * Whether a participant needs a laptop desk, by the plan's policy. An OT with no
     * answer is read cautiously each way — see SeatPlan::laptopRequirements().
     */
    private function requiresLaptop(int $policy, $laptopRequired): bool
    {
        return match ($policy) {
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
            ->leftJoinSub(ExamRooms::placements(), 'rp', 'rp.venue_id', '=', 'vm.venue_id')
            ->leftJoin('coe_venue_seat_layouts as vsl', 'vsl.venue_id', '=', 'vm.venue_id')
            ->whereIn('vm.venue_id', $venueIds)
            ->get(['vm.venue_id', 'vm.venue_name', DB::raw('COALESCE(rp.room_capacity, vm.capacity) as capacity'),
                'vm.laptop_capacity', 'vm.seating_capacity',
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

        // Manual rooms leave OTs out by choice, so the capacity is only the reason when it is short.
        return sprintf(
            '%d OT(s) seated, but %d have no seat yet%s.%s Map another room for them.',
            $summary['allocated'],
            $summary['unallocated'],
            (int) $summary['capacity'] < (int) $summary['participants'] ? ' (the rooms hold ' . $summary['capacity'] . ')' : '',
            (int) $summary['unallocated_laptop'] > 0
                ? ' ' . $summary['unallocated_laptop'] . ' of them need a laptop desk.'
                : ''
        );
    }

    /**
     * Map Seating Plan refused: who has no seat and what to do about it.
     *
     * @param  array<string, mixed>  $summary  from reseat()
     */
    private function unseatedMessage(array $summary, bool $manual): string
    {
        $rolls = $summary['unplaced_rolls'] ?? [];

        return sprintf(
            'Seating not saved — %s %s no seat%s.%s %s',
            $rolls ? 'Roll No. ' . $this->rollList($rolls) : $summary['unallocated'] . ' OT(s)',
            count($rolls) === 1 ? 'has' : 'have',
            (int) $summary['capacity'] < (int) $summary['participants'] ? ' (the rooms hold ' . $summary['capacity'] . ')' : '',
            (int) $summary['unallocated_laptop'] > 0 ? ' ' . $summary['unallocated_laptop'] . ' of them need a laptop desk.' : '',
            $manual ? 'Select every OT in a room, or add another room.' : 'Select another room.'
        );
    }

    /** @return array{0: int, 1: int} */
    /**
     * Freeze locks the invigilators (they can no longer change), so each one is told of their
     * duty: room, role, paper, date. Bell only (`coe_invigilation`, opens My Invigilation Duty).
     * Faculty reach a login through faculty_master.employee_master_pk, MTS through their own
     * employee_master.pk; someone without one is counted, not notified. A failure never
     * blocks the freeze.
     *
     * @return array{0: int, 1: int} [notified, without a login]
     */
    private function notifyInvigilators(SeatPlan $plan, NotificationService $notifications): array
    {
        $rooms = $this->invigilatorRooms()
            ->where('sp.pk', $plan->pk)
            ->whereNotNull('inv.seat_plan_venue_pk')
            ->get(['spv.pk', 'vm.venue_name', DB::raw('COALESCE(rp.building_name, eb.building_name) as building_name'),
                DB::raw('COALESCE(rp.floor_name, ef.floor_name) as floor_name'),
                'inv.chief_faculty_pk', 'inv.invigilator_faculty_pk', 'inv.assistant_faculty_pk', 'inv.mts_employee_pk']);
        if ($rooms->isEmpty()) {
            return [0, 0];
        }

        $facultyPks = $rooms->flatMap(fn ($r) => [$r->chief_faculty_pk, $r->invigilator_faculty_pk, $r->assistant_faculty_pk])->filter()->unique()->all();
        $logins = DB::table('user_credentials')->select('user_id');
        $facultyUser = DB::table('faculty_master')->whereIn('pk', $facultyPks)
            ->whereIn('employee_master_pk', $logins)->pluck('employee_master_pk', 'pk');
        $mtsUser = DB::table('user_credentials')->whereIn('user_id', $rooms->pluck('mts_employee_pk')->filter()->all())
            ->distinct()->pluck('user_id')->flip();

        $paper = trim(($plan->subject_name ? $plan->subject_name . ' – ' : '') . $plan->exam_name);
        $date = $plan->exam_date ? ' on ' . $plan->exam_date->format('d M Y') : '';
        $sent = $missing = 0;

        foreach ($rooms as $room) {
            $where = implode(', ', array_filter([$room->building_name, $room->floor_name, $room->venue_name]));
            foreach (self::INVIGILATOR_ROLES as $role => $def) {
                $pk = $room->{$def['column']};
                if (! $pk) {
                    continue;
                }
                $userId = $def['faculty'] ? ($facultyUser[$pk] ?? null) : (isset($mtsUser[$pk]) ? (int) $pk : null);
                if ($userId === null) {
                    $missing++;
                    continue;
                }
                try {
                    $notifications->create((int) $userId, 'coe_invigilation', 'Invigilation', (int) $room->pk,
                        'Invigilation duty assigned',
                        "You are {$def['label']} for {$paper}{$date}: {$where}.");
                    $sent++;
                } catch (\Throwable $e) {
                    Log::warning('COE invigilator notification failed', ['plan' => $plan->pk, 'room' => $room->pk, 'role' => $role, 'exception' => $e->getMessage()]);
                }
            }
        }

        return [$sent, $missing];
    }

    /**
     * My Invigilation Duty: the logged-in faculty / MTS member's rooms on frozen seating
     * plans (frozen = final), soonest exam first. Only their own duties; no permission key.
     */
    public function myDuties()
    {
        $userId = (int) Auth::user()?->user_id;
        $facultyPks = DB::table('faculty_master')->where('employee_master_pk', $userId)->pluck('pk')->map(fn ($pk) => (int) $pk)->all();

        $rows = $this->invigilatorRooms()
            ->join('course_master as cm', 'cm.pk', '=', 'sp.course_master_pk')
            ->leftJoin('examination_drives as ed', 'ed.id', '=', 'dcm.examination_drive_id')
            ->where('sp.status', SeatPlan::STATUS_PUBLISHED)
            ->where(function ($w) use ($facultyPks, $userId) {
                $w->where('inv.mts_employee_pk', $userId);
                if ($facultyPks !== []) {
                    $w->orWhereIn('inv.chief_faculty_pk', $facultyPks)
                        ->orWhereIn('inv.invigilator_faculty_pk', $facultyPks)
                        ->orWhereIn('inv.assistant_faculty_pk', $facultyPks);
                }
            })
            ->orderByRaw('COALESCE(dcm.exam_date, sp.exam_date) IS NULL, COALESCE(dcm.exam_date, sp.exam_date)')
            ->orderBy('vm.venue_name')
            ->get(['spv.pk', 'spv.seat_plan_pk', 'spv.venue_id', 'sp.subject_name', 'sp.exam_name', 'sp.batch', 'cm.course_name',
                'ed.drive_name', 'dcm.exam_mode', DB::raw('DATE(COALESCE(dcm.exam_date, sp.exam_date)) as day'), 'vm.venue_name',
                DB::raw('COALESCE(rp.building_name, eb.building_name) as building_name'),
                DB::raw('COALESCE(rp.floor_name, ef.floor_name) as floor_name'),
                'inv.chief_faculty_pk', 'inv.invigilator_faculty_pk', 'inv.assistant_faculty_pk', 'inv.mts_employee_pk']);

        $seated = DB::table('coe_seat_allocations')
            ->whereIn('seat_plan_pk', $rows->pluck('seat_plan_pk')->unique()->all())
            ->groupBy('seat_plan_pk', 'venue_id')
            ->selectRaw('seat_plan_pk, venue_id, COUNT(*) as n')
            ->get()->keyBy(fn ($c) => $c->seat_plan_pk . ':' . $c->venue_id);
        $today = now()->toDateString();
        $text = fn ($v) => html_entity_decode((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $duties = $rows->map(function ($r) use ($facultyPks, $userId, $seated, $today, $text) {
            $roles = [];
            foreach (self::INVIGILATOR_ROLES as $def) {
                $pk = (int) $r->{$def['column']};
                if ($pk && ($def['faculty'] ? in_array($pk, $facultyPks, true) : $pk === $userId)) {
                    $roles[] = $def['label'];
                }
            }

            return [
                'id' => (int) $r->pk,
                'date' => $this->examDateLabel($r->day),
                'when' => $r->day === null ? 'upcoming' : ($r->day === $today ? 'today' : ($r->day > $today ? 'upcoming' : 'completed')),
                'drive' => $text($r->drive_name) ?: '—',
                'course' => $text($r->course_name) . ' (' . $r->batch . ')',
                'paper' => trim($text($r->subject_name) . ' / ' . $text($r->exam_name), ' /') ?: '—',
                'mode' => $r->exam_mode ?: '—',
                'building' => $r->building_name ?: '—',
                'floor' => $r->floor_name ?: '—',
                'room' => $r->venue_name,
                'role' => implode(', ', $roles),
                'ots' => (int) ($seated[$r->seat_plan_pk . ':' . $r->venue_id]->n ?? 0),
            ];
        });

        return view('admin.coe.venue_allocation.my_duties', ['duties' => $duties]);
    }

    /**
     * My Exam Seat (OT portal): the logged-in Officer Trainee's seats on frozen seating plans,
     * soonest exam first — paper, date, room, seat, with a hall map marking only their desk
     * (no other OT is shown). The trainee comes from the session (user_category 'S').
     */
    public function mySeats()
    {
        $student = DB::table('user_credentials as uc')
            ->join('student_master as sm', 'sm.pk', '=', 'uc.user_id')
            ->leftJoin('coe_roll_numbers as rn', 'rn.student_master_pk', '=', 'sm.pk')
            ->where('uc.pk', Auth::id())
            ->where('uc.user_category', 'S')
            ->first(['sm.pk', 'sm.display_name', 'sm.first_name', 'sm.middle_name', 'sm.last_name', 'sm.generated_OT_code', 'rn.roll_number']);
        if ($student === null) {
            return redirect()->route('admin.dashboard')->with('error', 'This page is only for Officer Trainees.');
        }

        $rows = DB::table('coe_seat_allocations as sa')
            ->join('coe_seat_plans as sp', 'sp.pk', '=', 'sa.seat_plan_pk')
            ->join('course_master as cm', 'cm.pk', '=', 'sp.course_master_pk')
            ->join('venue_master as vm', 'vm.venue_id', '=', 'sa.venue_id')
            ->leftJoinSub(ExamRooms::placements(), 'rp', 'rp.venue_id', '=', 'vm.venue_id')
            ->leftJoin('exam_building_master as eb', 'eb.pk', '=', 'vm.building_master_pk')
            ->leftJoin('exam_floor_master as ef', 'ef.pk', '=', 'vm.floor_master_pk')
            ->leftJoin('coe_venue_seat_layouts as vsl', 'vsl.venue_id', '=', 'vm.venue_id')
            ->leftJoin('examination_drive_component_maps as dcm', 'dcm.id', '=', 'sp.examination_drive_component_map_id')
            ->leftJoin('examination_drives as ed', 'ed.id', '=', 'dcm.examination_drive_id')
            ->where('sa.student_master_pk', $student->pk)
            ->where('sp.status', SeatPlan::STATUS_PUBLISHED)
            ->orderByRaw('COALESCE(dcm.exam_date, sp.exam_date) IS NULL, COALESCE(dcm.exam_date, sp.exam_date)')
            ->get(['sa.seat_plan_pk', 'sa.seat_number', 'sa.requires_laptop', 'sa.requires_scriber', 'sp.subject_name', 'sp.exam_name',
                'cm.course_name', 'ed.drive_name', 'dcm.exam_mode', DB::raw('DATE(COALESCE(dcm.exam_date, sp.exam_date)) as day'),
                'vm.venue_name', DB::raw('COALESCE(rp.room_capacity, vm.capacity) as capacity'),
                DB::raw('COALESCE(rp.building_name, eb.building_name) as building_name'),
                DB::raw('COALESCE(rp.floor_name, ef.floor_name) as floor_name'),
                'vsl.laptop_seats', 'vsl.scriber_seats']);

        $today = now()->toDateString();
        $text = fn ($v) => html_entity_decode((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $blockNames = ['A' => 'Block A', 'B' => 'Block B (Centre)', 'C' => 'Block C'];
        $seats = $rows->map(function ($r) use ($today, $text, $blockNames) {
            $label = self::seatLabel((int) $r->seat_number);
            [$block, $desk] = array_pad(explode('-', $label, 2), 2, $label);

            return [
                'key' => $r->seat_plan_pk . '-' . $r->seat_number,
                'date' => $this->examDateLabel($r->day),
                'when' => $r->day === null ? 'upcoming' : ($r->day === $today ? 'today' : ($r->day > $today ? 'upcoming' : 'completed')),
                'drive' => $text($r->drive_name) ?: '—',
                'course' => $text($r->course_name),
                'subject' => $text($r->subject_name) ?: 'General',
                'component' => $text($r->exam_name) ?: '—',
                'mode' => $r->exam_mode ?: '—',
                'building' => $r->building_name ?: '—',
                'floor' => $r->floor_name ?: '—',
                'room' => $r->venue_name,
                'seat' => (int) $r->seat_number,
                'desk' => $desk,
                'block' => $blockNames[$block] ?? $block,
                'laptop' => (bool) $r->requires_laptop,
                'scriber' => (bool) $r->requires_scriber,
                // For the hall map: the room's shape and its marked desks; only this OT is placed.
                'capacity' => (int) $r->capacity,
                'laptop_seats' => VenueSeatLayout::normalise(json_decode((string) $r->laptop_seats, true)),
                'scriber_seats' => VenueSeatLayout::normalise(json_decode((string) $r->scriber_seats, true)),
            ];
        });

        return view('admin.coe.venue_allocation.my_seats', [
            'student' => $student,
            'name' => $this->studentName($student),
            'seats' => $seats,
        ]);
    }

    private function notifySeats(SeatPlan $plan, NotificationService $notifications): array
    {
        $seats = DB::table('coe_seat_allocations as sa')
            ->join('venue_master as vm', 'vm.venue_id', '=', 'sa.venue_id')
            ->leftJoinSub(ExamRooms::placements(), 'rp', 'rp.venue_id', '=', 'vm.venue_id')
            ->leftJoin('exam_building_master as eb', 'eb.pk', '=', 'vm.building_master_pk')
            ->leftJoin('exam_floor_master as ef', 'ef.pk', '=', 'vm.floor_master_pk')
            ->where('sa.seat_plan_pk', $plan->pk)
            ->get(['sa.student_master_pk', 'sa.seat_number', 'vm.venue_name',
                DB::raw('COALESCE(rp.building_name, eb.building_name) as building_name'),
                DB::raw('COALESCE(rp.floor_name, ef.floor_name) as floor_name')]);

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
            ->leftJoinSub(ExamRooms::placements(), 'rp', 'rp.venue_id', '=', 'vm.venue_id')
            ->leftJoin('exam_building_master as eb', 'eb.pk', '=', 'vm.building_master_pk')
            ->leftJoin('exam_floor_master as ef', 'ef.pk', '=', 'vm.floor_master_pk')
            ->leftJoin('coe_venue_seat_layouts as vsl', 'vsl.venue_id', '=', 'spv.venue_id')
            ->leftJoin('examination_drive_component_maps as dcm', 'dcm.id', '=', 'sp.examination_drive_component_map_id')
            ->leftJoin('examination_drives as ed', 'ed.id', '=', 'dcm.examination_drive_id')
            ->leftJoin('coe_seat_plan_venue_invigilators as inv', 'inv.seat_plan_venue_pk', '=', 'spv.pk')
            ->when($scope === 'active', fn ($q) => $q->where('cm.active_inactive', 1)->where('cm.end_date', '>=', $today))
            ->when($scope === 'archived', fn ($q) => $q->where(fn ($w) => $w->where('cm.active_inactive', '!=', 1)
                ->orWhereNull('cm.active_inactive')->orWhere('cm.end_date', '<', $today)))
            ->orderBy('cm.course_name')
            ->orderBy('sp.pk')
            ->orderBy('spv.display_order')
            ->get(['spv.pk', 'spv.seat_plan_pk', 'spv.venue_id', 'sp.batch', 'sp.candidate_category', 'sp.subject_name',
                'sp.exam_name', 'sp.status', 'sp.unallocated_count', 'cm.course_name', 'vm.venue_name',
                DB::raw('COALESCE(rp.room_capacity, vm.capacity) as capacity'),
                DB::raw('COALESCE(rp.room_laptops, vm.laptop_capacity) as laptop_capacity'),
                DB::raw('COALESCE(rp.building_name, eb.building_name) as building_name'),
                DB::raw('COALESCE(rp.floor_name, ef.floor_name) as floor_name'),
                'vsl.laptop_seats', 'vsl.scriber_seats', 'dcm.exam_type', 'ed.drive_name',
                DB::raw('(inv.chief_faculty_pk IS NOT NULL OR inv.invigilator_faculty_pk IS NOT NULL OR inv.assistant_faculty_pk IS NOT NULL OR inv.mts_employee_pk IS NOT NULL) as has_invigilators')]);

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
                // A plan made before drives existed has no component map, so no drive.
                'drive' => trim((string) $r->drive_name) ?: '—',
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
                // An Offline Objective paper takes no invigilators.
                // Not for an Offline Objective paper, and locked once the seating plan is frozen.
                'invigilators' => $r->exam_type !== self::OFFLINE_OBJECTIVE && (int) $r->status !== SeatPlan::STATUS_PUBLISHED,
                // Add Invigilators becomes Edit Invigilators once someone is assigned.
                'has_invigilators' => (bool) $r->has_invigilators,
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

    /** Examination rooms: the active rooms of Room Master, with a capacity. */
    private function examRooms(): Collection
    {
        return ExamRooms::active()
            ->join('venue_master as vm', 'vm.venue_id', '=', 'rp.venue_id')
            ->leftJoin('coe_venue_seat_layouts as vsl', 'vsl.venue_id', '=', 'rp.venue_id')
            ->orderBy('rp.building_name')
            ->orderBy('rp.floor_name')
            ->orderBy('vm.venue_name')
            ->get(['vm.venue_id', 'vm.venue_name', 'rp.room_capacity as capacity', 'rp.room_laptops as laptop_capacity',
                'rp.building_pk', 'rp.building_name as building', 'rp.floor_pk', 'rp.floor_name as floor',
                'vsl.laptop_seats', 'vsl.scriber_seats']);
    }

    /** building id → floor id → { name, rooms: [{ id, name, capacity, laptop, scriber }] } for the dependent selects. */
    private function roomTree(Collection $rooms): array
    {
        $tree = [];
        foreach ($rooms as $r) {
            $tree[$r->building_pk][$r->floor_pk]['name'] = $r->floor;
            $tree[$r->building_pk][$r->floor_pk]['rooms'][] = [
                'id' => (int) $r->venue_id,
                'name' => $r->venue_name,
                'capacity' => (int) $r->capacity,
                'laptop' => $this->roomLaptops($r),
                'scriber' => count(VenueSeatLayout::normalise(json_decode((string) $r->scriber_seats, true))),
            ];
        }

        return $tree;
    }

    /** Laptops an examRooms() row offers: the marked laptop desks, else Room Master's laptop count. */
    private function roomLaptops(object $room): int
    {
        $laptopSeats = VenueSeatLayout::normalise(json_decode((string) $room->laptop_seats, true));

        return $laptopSeats !== [] ? count($laptopSeats) : (int) $room->laptop_capacity;
    }

    private function roomDetails(int $venueId): ?object
    {
        $room = DB::table('venue_master as vm')
            ->leftJoinSub(ExamRooms::placements(), 'rp', 'rp.venue_id', '=', 'vm.venue_id')
            ->leftJoin('exam_building_master as eb', 'eb.pk', '=', 'vm.building_master_pk')
            ->leftJoin('exam_floor_master as ef', 'ef.pk', '=', 'vm.floor_master_pk')
            ->leftJoin('coe_venue_seat_layouts as vsl', 'vsl.venue_id', '=', 'vm.venue_id')
            ->where('vm.venue_id', $venueId)
            ->first(['vm.venue_id', 'vm.venue_name', DB::raw('COALESCE(rp.room_capacity, vm.capacity) as capacity'),
                DB::raw('COALESCE(rp.building_name, eb.building_name) as building'),
                DB::raw('COALESCE(rp.floor_name, ef.floor_name) as floor'),
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
