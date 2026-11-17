<?php

namespace Modules\Protocol\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Protocol\Entities\GuestHouseRequest;
use Modules\Protocol\Entities\ProtocolRequest;
use Modules\Protocol\Entities\ProtocolRequestBatch;
use Modules\Protocol\Entities\TicketJourney;
use Modules\Protocol\Entities\TicketRequest;
use Modules\Protocol\Entities\VehicleLeg;
use Modules\Protocol\Entities\Vehicle;
use Modules\Protocol\Entities\VehiclePassRequest;
use Modules\Protocol\Http\Requests\{StoreCombinedRequest,UpdateCombinedRequest};
use Modules\Protocol\Authorization\ProtocolRequestAccess;
use App\Models\CourseMaster;
use Modules\Protocol\Services\{RequestService,ApprovalService,ProtocolDirectory};

class ProtocolRequestController extends Controller
{
    private $service;
    public function __construct(RequestService $service) {
        $this->service = $service;
    }

    /**
    * Dashboard — quick counts + recent activity. Shared lan  ding page.
    */
    public function dashboard(ApprovalService $approvalService)
    {
        // Protocol Staff see the All Requests scope; employees see only their own requests
        $isStaff = Auth::user()->can('protocol.protocol_requests');

        $scoped = fn () => $isStaff
            ? $approvalService->scopedQuery()
            : ProtocolRequest::forUser(Auth::id());

        $types = $isStaff
            ? $approvalService->allowedRequestTypes()
            : array_keys(config('protocol.request_types'));

        // Employees get one "My Pending" card per type; staff get type totals plus their approval queue
        $cards = [];
        foreach ($types as $type) {
            $typeLabel = config('protocol.request_types.' . $type . '.label');
            $cards[] = [
                'type' => $type,
                'label' => $isStaff ? $typeLabel . ' Requests' : 'My Pending ' . $typeLabel . ' Requests',
                'count' => $isStaff
                    ? $scoped()->where('request_type', $type)->count()
                    : $scoped()->pending()->where('request_type', $type)->count(),
                'url' => $isStaff
                    ? route('protocol.requests.all', ['type' => $type])
                    : route('protocol.requests.my', ['type' => $type]),
            ];
        }

        $pending = $isStaff
            ? ['count' => ProtocolRequest::awaitingProtocolStaff(Auth::id())->count(), 'url' => route('protocol.approval.queue')]
            : null;

        $recent = $scoped()->with('requestable', 'batch')
            ->latest()
            ->limit(10)
            ->get();

        return view('protocol::dashboard', compact('cards', 'pending', 'recent'));
    }

    /**
     * Combined "Request for Accommodation / Vehicle / Tickets" form.
     */
    public function create()
    {
        $courses = CourseMaster::activeRunning()->orderBy('course_name', 'asc')->pluck('course_name', 'pk');
        $vehicles = $this->vehicleOptions();
        return view('protocol::requests.create', compact('courses', 'vehicles'));
    }

    /**
     * List of the logged-in employee's own requests, with status tracking.
     * Supports ?type=guesthouse|vehicle|ticket to filter the 3 tabs on
     * the My Requests screen. No param / "all" shows everything.
     */


    public function myRequests(Request $request)
    {
        if($request->ajax()){
            return $this->service->getDatatable($request);
        }
        $pageData = $this->service->pageData();
        return view('protocol::requests.my-requests', compact('pageData'));
    }

    /**
     * Full detail + timeline for a single request (employee, protocol staff,
     * or manager — all share this read view).
     */
    public function show(ProtocolRequest $protocolRequest)
    {
        abort_unless(ProtocolRequestAccess::canView(Auth::user(), $protocolRequest), 403);

        $protocolRequest->loadFullRequestable();
        $protocolRequest->load('batch.guests', 'logs.actionBy');
        return view('protocol::requests.show', compact('protocolRequest'));
    }

    public function edit(Request $request, ProtocolRequest $protocolRequest)
    {
        abort_unless(ProtocolRequestAccess::canEdit(Auth::user(), $protocolRequest), 403);

        $protocolRequest->loadFullRequestable();
        $protocolRequest->load('batch.guests', 'logs.actionBy');
        // Running courses only, but keep the one already saved even if it has since ended
        $courses = CourseMaster::where(function ($q) use ($protocolRequest) {
                $q->activeRunning()->orWhere('pk', $protocolRequest->course_team_to_notify);
            })
            ->orderBy('course_name', 'asc')
            ->pluck('course_name', 'pk');
        // Active vehicles, but keep the one already saved even if it has since been retired
        $vehicles = $this->vehicleOptions($protocolRequest->request_type === 'vehicle' ? $protocolRequest->requestable?->vehicle_type : null);
        return view('protocol::requests.edit', compact('protocolRequest', 'courses', 'vehicles'));
    }

    /**
     * Vehicle dropdown: active vehicles that have not been deleted (PR #348 F-024).
     * $keepId keeps a request's saved vehicle selectable on edit.
     */
    protected function vehicleOptions($keepId = null)
    {
        return Vehicle::where(function ($q) use ($keepId) {
                $q->where(fn ($active) => $active->whereNull('deleted_at')->where('status', 1));
                if ($keepId !== null && $keepId !== '') {
                    $q->orWhere('id', $keepId);
                }
            })
            ->orderBy('vehicle_name', 'asc')
            ->pluck('vehicle_name', 'id');
    }

    /**
     * Store the combined form submission. One batch row is created to
     * hold the shared fields (Purpose, Course Team To Notify, Escort
     * Required, Faculty) and the Guest Details rows — entered once,
     * reused across every checked type. Then one independent
     * ProtocolRequest is created per checked type (Guest House /
     * Vehicle / Ticket), each with its own request number, status, and
     * approval timeline, all pointing back at the same batch.
     */
    public function storeCombined(StoreCombinedRequest $request, ProtocolDirectory $directory)
    {
        $data = $request->validated();
        $types = $data['types'] ?? [];
        // user_id may be the employee's pk or pk_old; resolved by the codebase's rule
        $employee = $directory->employeeFor(Auth::user());
        if (! $employee) {
            $message = 'Your login is not linked to a single employee record, so the request cannot be raised. Please contact IT support.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->back()->withInput()->with('error', $message);
        }
        $employeeID = $employee->pk;
        $employeeDept = $employee->department_master_pk;

        try {
            $created = DB::transaction(function () use ($data, $types, $employeeID, $employeeDept) {
                // 1. Create the shared batch
                $batch = ProtocolRequestBatch::create([
                    'user_id' => Auth::id(),
                    'employee_id' => $employeeID,
                    'employee_department' => $employeeDept,
                    'course_team_to_notify' => $data['course_team_to_notify'] ?? null,
                    'purpose' => $data['purpose'] ?? null,
                    'other_purpose' => $data['other_purpose'] ?? null,
                    'escort_required' => ($data['escort_required'] ?? 'no') === 'yes',
                    'is_faculty' => (bool) ($data['is_faculty'] ?? false),
                ]);

                // 2. Create the shared guest rows (entered once, reused across types)
                foreach (($data['guests'] ?? []) as $index => $guestData) {
                    $batch->guests()->create([
                        'guest_name' => $guestData['guest_name'],
                        'age' => $guestData['age'] ?? null,
                        'sex' => $guestData['sex'],
                        'designation' => $guestData['designation'] ?? null,
                        'mobile_no' => $guestData['mobile_no'],
                        'email_id' => $guestData['email_id'] ?? null,
                        'guest_id_proof' => $guestData['guest_id_proof'] ?? null,
                        'is_main_guest' => (bool) ($guestData['is_main_guest'] ?? false),
                        'sort_order' => $index,
                    ]);
                }

                $results = [];

                // 3. Create one independent ProtocolRequest per checked type
                if (in_array('guesthouse', $types, true)) {
                    $results[] = $this->createGuestHouseRequest($batch, $data['guest_house'] ?? []);
                }

                if (in_array('vehicle', $types, true)) {
                    $results[] = $this->createVehicleRequest($batch, $data['vehicle'] ?? []);
                }

                if (in_array('ticket', $types, true)) {
                    $results[] = $this->createTicketRequest($batch, $data['ticket'] ?? []);
                }

                return $results;
            });
        } catch (\Throwable $e) {
            report($e);

            $message = 'Something went wrong while submitting your request. Please try again, or contact IT support if the problem continues.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => $message,
                ], 500);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $message);
        }

        $labels = collect($created)->map(fn(ProtocolRequest $r) => $r->request_number)->implode(', ');
        $count = count($created);
        $successMessage = $count > 1
            ? "{$count} requests submitted ({$labels}) and sent to Protocol Staff for review."
            : "Request {$labels} submitted and sent to Protocol Staff for review.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $successMessage,
                'redirect' => route('protocol.requests.my'),
            ]);
        }

        return redirect()
            ->route('protocol.requests.my')
            ->with('success', $successMessage);
    }

    protected function createGuestHouseRequest(ProtocolRequestBatch $batch, array $data): ProtocolRequest
    {
        $requestable = GuestHouseRequest::create([
            'date_from' => $data['date_from'] ?? null,
            'date_to' => $data['date_to'] ?? null,
            'no_of_guests' => $data['no_of_guests'] ?? null,
            'no_of_rooms' => $data['no_of_rooms'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'payment_done_by' => $data['payment_done_by'] ?? 'Myself',
        ]);

        return $this->linkProtocolRequest($batch, 'guesthouse', $requestable, GuestHouseRequest::class);
    }

    protected function createVehicleRequest(ProtocolRequestBatch $batch, array $data): ProtocolRequest
    {
        $requestable = VehiclePassRequest::create([
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'one_way_booking' => (bool) ($data['one_way_booking'] ?? false),
            'payment_will_be_done_by' => $data['payment_will_be_done_by'] ?? 'Myself',
        ]);

        foreach (($data['legs'] ?? []) as $index => $legData) {
            VehicleLeg::create([
                'vehicle_pass_id' => $requestable->id,
                'date_time_from' => $legData['date_time_from'] ?? null,
                'date_time_to' => $legData['date_time_to'] ?? null,
                'pickup_type' => $legData['pickup_type'] ?? 'location',
                'pickup_value' => $legData['pickup_value'] ?? null,
                'pickup_time' => $legData['pickup_time'] ?? null,
                'drop_type' => $legData['drop_type'] ?? 'location',
                'drop_value' => $legData['drop_value'] ?? null,
                'drop_time' => $legData['drop_time'] ?? null,
                'no_of_persons' => $legData['no_of_persons'] ?? 1,
                'remarks' => $legData['remarks'] ?? null,
                'sort_order' => $index,
            ]);
        }

        return $this->linkProtocolRequest($batch, 'vehicle', $requestable, VehiclePassRequest::class);
    }

    protected function createTicketRequest(ProtocolRequestBatch $batch, array $data): ProtocolRequest
    {
        $requestable = TicketRequest::create([]);

        foreach (($data['journeys'] ?? []) as $index => $journeyData) {
            TicketJourney::create([
                'ticket_id' => $requestable->id,
                'origin' => $journeyData['origin'] ?? null,
                'destination' => $journeyData['destination'] ?? null,
                'ticket_type' => $journeyData['ticket_type'] ?? 'Air',
                'class' => $journeyData['class'] ?? null,
                'date_of_journey' => $journeyData['date_of_journey'] ?? null,
                'train_flight_bus_name' => $journeyData['train_flight_bus_name'] ?? null,
                'train_flight_bus_no' => $journeyData['train_flight_bus_no'] ?? null,
                'quota' => $journeyData['quota'] ?? 'General',
                'book_if_waiting' => (bool) ($journeyData['book_if_waiting'] ?? false),
                'payment_will_be_done_by' => $journeyData['payment_will_be_done_by'] ?? 'Myself',
                'remarks' => $journeyData['remarks'] ?? null,
                'sort_order' => $index,
            ]);
        }

        return $this->linkProtocolRequest($batch, 'ticket', $requestable, TicketRequest::class);
    }

    /**
     * Shared final step: create the master protocol_requests row pointing
     * at the batch and the type-specific row, then log the "Raised" entry.
     */
    protected function linkProtocolRequest(ProtocolRequestBatch $batch, string $type, $requestable, string $modelClass): ProtocolRequest
    {
        $protocolRequest = ProtocolRequest::create([
            'request_number' => ProtocolRequest::generateRequestNumber(),
            'batch_id' => $batch->id,
            'request_type' => $type,
            'requestable_id' => $requestable->id,
            'requestable_type' => $modelClass,
            'user_id'=>Auth::id(),
            'employee_id' => $batch->employee_id,
            'employee_department' => $batch->employee_department,
            'status' => ProtocolRequest::STATUS_PENDING,
            'current_stage' => 'Protocol Staff Review',
        ]);

        $protocolRequest->logRaised();

        return $protocolRequest;
    }







    /**
     * Update a single ProtocolRequest (and its type-specific data).
     *
     * - The request TYPE is locked: each type is an independent ProtocolRequest
     *   with its own status/approval timeline, so editing never creates or
     *   removes sibling requests.
     * - Shared batch fields and guest rows are updated on the batch, so they
     *   also change for the sibling requests raised in the same submission —
     *   unless a sibling has already been decided, in which case this request
     *   first moves to its own copy of the batch (see moveToOwnBatch()).
     * - Child rows (guests / legs / journeys) are synced by id:
     *   existing id => update, no id => create, missing from payload => delete.
     *
     * Remember: import UpdateCombinedRequest, VehicleLeg etc. at the top of the controller.
     */
    public function update(UpdateCombinedRequest $request, ProtocolRequest $protocolRequest)
    {
        abort_unless(ProtocolRequestAccess::canEdit(Auth::user(), $protocolRequest), 403);

        // Only editable while still pending (adjust if "returned" requests should be editable too)
        if ($protocolRequest->status !== ProtocolRequest::STATUS_PENDING) {
            return $this->notPendingResponse($request);
        }

        $data = $request->validated();

        try {
            $stillPending = DB::transaction(function () use ($data, $protocolRequest) {
                // Re-read under a row lock: Protocol Staff may have decided it since the check above
                $status = ProtocolRequest::whereKey($protocolRequest->getKey())->lockForUpdate()->value('status');
                if ($status !== ProtocolRequest::STATUS_PENDING) {
                    return false;
                }

                $batch = $protocolRequest->batch;

                // Batch fields and guests are shared with the sibling requests of the same
                // submission. Once any sibling has been decided they must not change under it,
                // so this request moves to its own copy of the batch and the edit applies there.
                $siblingStatuses = ProtocolRequest::where('batch_id', $batch->id)
                    ->whereKeyNot($protocolRequest->getKey())
                    ->lockForUpdate()
                    ->pluck('status', 'request_number');
                $decidedSiblings = $siblingStatuses->reject(fn ($s) => $s === ProtocolRequest::STATUS_PENDING)->keys();
                if ($decidedSiblings->isNotEmpty()) {
                    $batch = $this->moveToOwnBatch($protocolRequest, $batch);
                }

                // 1. Shared batch fields
                $batch->update([
                    'course_team_to_notify' => $data['course_team_to_notify'] ?? null,
                    'purpose' => $data['purpose'] ?? null,
                    'other_purpose' => $data['other_purpose'] ?? null,
                    'escort_required' => ($data['escort_required'] ?? 'no') === 'yes',
                    'is_faculty' => (bool) ($data['is_faculty'] ?? $batch->is_faculty),
                ]);

                // 2. Shared guest rows
                $this->syncChildren($batch->guests(), $data['guests'] ?? [], fn($g, $index) => [
                    'guest_name' => $g['guest_name'],
                    'age' => $g['age'] ?? null,
                    'sex' => $g['sex'],
                    'designation' => $g['designation'] ?? null,
                    'mobile_no' => $g['mobile_no'],
                    'email_id' => $g['email_id'] ?? null,
                    'guest_id_proof' => $g['guest_id_proof'] ?? null,
                    'is_main_guest' => (bool) ($g['is_main_guest'] ?? false),
                    'sort_order' => $index,
                ]);

                // 3. Type-specific data (type is locked to the existing request_type)
                $requestable = $protocolRequest->requestable;

                switch ($protocolRequest->request_type) {
                    case 'guesthouse':
                        $this->updateGuestHouseRequest($requestable, $data['guest_house'] ?? []);
                        break;
                    case 'vehicle':
                        $this->updateVehicleRequest($requestable, $data['vehicle'] ?? []);
                        break;
                    case 'ticket':
                        $this->updateTicketRequest($requestable, $data['ticket'] ?? []);
                        break;
                }

                // 4. Touch the master row and log the edit
                $protocolRequest->touch();
                $protocolRequest->logUpdated($decidedSiblings->isNotEmpty()
                    ? 'Guest and purpose details separated from ' . $decidedSiblings->implode(', ') . ', already processed.'
                    : null);

                return true;
            });
        } catch (\Throwable $e) {
            report($e);

            $message = 'Something went wrong while updating your request. Please try again, or contact IT support if the problem continues.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $message], 500);
            }

            return redirect()->back()->withInput()->with('error', $message);
        }

        if (! $stillPending) {
            return $this->notPendingResponse($request);
        }

        $successMessage = "Request {$protocolRequest->request_number} updated successfully.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $successMessage,
                'redirect' => route('protocol.requests.my'),
            ]);
        }

        return redirect()->route('protocol.requests.my')->with('success', $successMessage);
    }

    /**
     * Give this request its own copy of the batch's shared fields. Guests are not
     * copied: the edit's sync creates them on the new batch from the submitted rows.
     */
    protected function moveToOwnBatch(ProtocolRequest $protocolRequest, ProtocolRequestBatch $batch): ProtocolRequestBatch
    {
        $own = $batch->replicate();
        $own->created_at = $batch->created_at;
        $own->save();

        $protocolRequest->update(['batch_id' => $own->id]);
        $protocolRequest->setRelation('batch', $own);

        return $own;
    }

    protected function notPendingResponse(Request $request)
    {
        $message = 'Only pending requests can be edited.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()->route('protocol.requests.my')->with('error', $message);
    }

    protected function updateGuestHouseRequest($requestable, array $data): void
    {
        $requestable->update([
            'date_from' => $data['date_from'] ?? null,
            'date_to' => $data['date_to'] ?? null,
            'no_of_guests' => $data['no_of_guests'] ?? null,
            'no_of_rooms' => $data['no_of_rooms'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'payment_done_by' => $data['payment_done_by'] ?? 'Myself',
        ]);
    }

    protected function updateVehicleRequest($requestable, array $data): void
    {
        $requestable->update([
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'one_way_booking' => (bool) ($data['one_way_booking'] ?? false),
            'payment_will_be_done_by' => $data['payment_will_be_done_by'] ?? 'Myself',
        ]);

        // Assumes VehiclePassRequest::legs() => hasMany(VehicleLeg::class, 'vehicle_pass_id')
        $this->syncChildren($requestable->legs(), $data['legs'] ?? [], fn($leg, $index) => [
            'date_time_from' => $leg['date_time_from'] ?? null,
            'date_time_to' => $leg['date_time_to'] ?? null,
            'pickup_type' => $leg['pickup_type'] ?? 'location',
            'pickup_value' => $leg['pickup_value'] ?? null,
            'pickup_time' => $leg['pickup_time'] ?? null,
            'drop_type' => $leg['drop_type'] ?? 'location',
            'drop_value' => $leg['drop_value'] ?? null,
            'drop_time' => $leg['drop_time'] ?? null,
            'no_of_persons' => $leg['no_of_persons'] ?? 1,
            'remarks' => $leg['remarks'] ?? null,
            'sort_order' => $index,
        ]);
    }

    protected function updateTicketRequest($requestable, array $data): void
    {
        // Assumes TicketRequest::journeys() => hasMany(TicketJourney::class, 'ticket_id')
        $this->syncChildren($requestable->journeys(), $data['journeys'] ?? [], fn($j, $index) => [
            'origin' => $j['origin'] ?? null,
            'destination' => $j['destination'] ?? null,
            'ticket_type' => $j['ticket_type'] ?? 'Air',
            'class' => $j['class'] ?? null,
            'date_of_journey' => $j['date_of_journey'] ?? null,
            'train_flight_bus_name' => $j['train_flight_bus_name'] ?? null,
            'train_flight_bus_no' => $j['train_flight_bus_no'] ?? null,
            'quota' => $j['quota'] ?? 'General',
            'book_if_waiting' => (bool) ($j['book_if_waiting'] ?? false),
            'payment_will_be_done_by' => $j['payment_will_be_done_by'] ?? 'Myself',
            'remarks' => $j['remarks'] ?? null,
            'sort_order' => $index,
        ]);
    }

    /**
     * Sync a hasMany relation from submitted rows.
     * Rows with an id that belongs to this relation are updated, rows without
     * (or with a foreign) id are created, and existing rows not submitted are deleted.
     */
    protected function syncChildren($relation, array $rows, callable $mapper): void
    {
        $existing = $relation->get()->keyBy('id');
        $keepIds = [];

        foreach (array_values($rows) as $index => $row) {
            $attributes = $mapper($row, $index);
            $id = $row['id'] ?? null;

            if ($id && $existing->has((int) $id)) {
                $existing[(int) $id]->update($attributes);
                $keepIds[] = (int) $id;
            } else {
                $keepIds[] = $relation->create($attributes)->id;
            }
        }

        $relation->whereNotIn('id', $keepIds)->delete();
    }
}
