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
use Modules\Protocol\Http\Requests\StoreCombinedRequest;
use App\Models\CourseMaster;
use Yajra\DataTables\Facades\DataTables;

class ProtocolRequestController extends Controller
{
    /**
     * Dashboard — quick counts + recent activity. Shared lan  ding page.
     */
    public function dashboard()
    {
        $counts = [
            'guesthouse' => ProtocolRequest::where('request_type', 'guesthouse')->count(),
            'vehicle' => ProtocolRequest::where('request_type', 'vehicle')->count(),
            'ticket' => ProtocolRequest::where('request_type', 'ticket')->count(),
            'pending_mine' => ProtocolRequest::forEmployee(Auth::id())->pending()->count(),
        ];

        $recent = ProtocolRequest::with('requestable', 'batch')
            ->latest()
            ->limit(10)
            ->get();

        return view('protocol::dashboard', compact('counts', 'recent'));
    }

    /**
     * Combined "Request for Accommodation / Vehicle / Tickets" form.
     */
    public function create()
    {
        $courses = CourseMaster::where('active_inactive', 1)->orderBy('course_name', 'asc')->pluck('course_name', 'pk');
        $vehicles = Vehicle::orderBy('vehicle_name', 'asc')->pluck('vehicle_name', 'pk');
        return view('protocol::requests.create', compact('courses', 'vehicles'));
    }

    /**
     * List of the logged-in employee's own requests, with status tracking.
     * Supports ?type=guesthouse|vehicle|ticket to filter the 3 tabs on
     * the My Requests screen. No param / "all" shows everything.
     */


    public function myRequests(Request $request)
    {
        $type = $request->query('type', 'all');
        $query = ProtocolRequest::with(['requestable', 'batch', 'logs'])->forUser(Auth::id());

        if (in_array($type, ['guesthouse', 'vehicle', 'ticket'], true)) {
            $query->where('request_type', $type);
        }

        if ($request->ajax()) {
            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('request_id', function ($row) {
                    return $row->request_number ?? '-';
                })
                ->addColumn('type', function ($row) {
                    return view('protocol::components.type-pill', [
                        'type' => $row->request_type,
                    ])->render();
                })
                ->addColumn('details', function ($row) {
                    return $row->current_stage ?? '-';
                })
                ->addColumn('raised_on', function ($row) {
                    return $row->created_at->format('d M Y');
                })
                ->addColumn('status', function ($row) {
                    return view('protocol::components.status-badge', [
                        'status' => $row->status,
                    ])->render();
                })
                ->addColumn('action', function ($row) {
                    return '
                        <a href="' . route('protocol.requests.show', $row->id) . '" class="btn btn-sm btn-primary">
                            View <i class="bi bi-chevron-right"></i>
                        </a>';
                })
                ->rawColumns(['action', 'type', 'status'])
                ->make(true);
        }

        $requests = $query->latest()
            ->paginate(config('protocol.per_page'))
            ->withQueryString();

        $counts = [
            'all' => ProtocolRequest::forUser(Auth::id())->count(),
            'guesthouse' => ProtocolRequest::forUser(Auth::id())->where('request_type', 'guesthouse')->count(),
            'vehicle' => ProtocolRequest::forUser(Auth::id())->where('request_type', 'vehicle')->count(),
            'ticket' => ProtocolRequest::forUser(Auth::id())->where('request_type', 'ticket')->count(),
        ];

        return view('protocol::requests.my-requests', compact('requests', 'counts', 'type'));
    }

    /**
     * Full detail + timeline for a single request (employee, protocol staff,
     * or manager — all share this read view).
     */
    public function show(ProtocolRequest $protocolRequest)
    {
        $protocolRequest->loadFullRequestable();
        $protocolRequest->load('batch.guests', 'logs.actionBy');
        return view('protocol::requests.show', compact('protocolRequest'));
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
    public function storeCombined(StoreCombinedRequest $request)
    {
        $data = $request->validated();
        $types = $data['types'] ?? [];
        $employeeID = Auth::user()->employeeDetail()->pk;
        $employeeDept = Auth::user()->employeeDetail()->department_master_pk;


        try {
            $created = DB::transaction(function () use ($data, $types, $employeeID, $employeeDept) {
                // 1. Create the shared batch
                $batch = ProtocolRequestBatch::create([
                    'user_id' => Auth::id(),
                    'employee_id' => $employeeID,
                    'employee_department' => $employeeDept,
                    'course_team_to_notify' => $data['course_team_to_notify'] ?? null,
                    'purpose' => $data['purpose'] ?? null,
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
            'employee_id' => $batch->employee_id,
            'employee_department' => $batch->employee_department,
            'status' => ProtocolRequest::STATUS_PENDING,
            'current_stage' => 'Protocol Staff Review',
        ]);

        $protocolRequest->logRaised();

        return $protocolRequest;
    }
}
