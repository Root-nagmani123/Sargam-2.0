<?php
######################################
// DEVELOPER INFO 
// => MANJEET CHNAD (शैतान 💀)
// => manjeetchand01@gmail.com
// => +919997294527
// => 14 September 2026
// => veragyam param sukham : 👹
######################################
namespace Modules\Protocol\Services;

use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Modules\Protocol\Entities\ProtocolRequest;
use Auth;

class ApprovalService
{
    public function pageData(): array
    {
        return [
            'columns' => $this->columns(),
            'filters' => [],
            'categories' => [],
            'groups' => [],
            'parent_menus' => [],
            'tabs' => $this->activateRequestedTab($this->tabs()),
        ];
    }

    # @ Open the tab named in ?type= (dashboard cards link here with it)
    private function activateRequestedTab(array $tabs): array
    {
        $requested = request('type');
        if (! collect($tabs)->contains('type', $requested)) {
            return $tabs;
        }

        return array_map(fn ($tab) => array_merge($tab, ['active' => $tab['type'] === $requested]), $tabs);
    }

    # @ Direct + role permissions of the logged-in user
    private function userPermissions(): array
    {
        return Auth::user()->getAllPermissions()->pluck('name')->toArray();
    }

    # @ Full access, recommended bookings and Protocol Approvers (requests of any type
    # @ can be recommended to them) see every request type
    private function seesAllRequestTypes(array $userPermissions): bool
    {
        return in_array('protocol.all_booking', $userPermissions)
            || in_array('protocol.recommended_booking', $userPermissions)
            || Auth::user()->hasRole('Protocol Approver');
    }

    # @ Request types this user can see in All Requests
    public function allowedRequestTypes(): array
    {
        $userPermissions = $this->userPermissions();

        if ($this->seesAllRequestTypes($userPermissions)) {
            return array_keys(config('protocol.request_types'));
        }

        $map = [
            'guesthouse' => 'protocol.guest_house_booking',
            'vehicle' => 'protocol.vehicle_booking',
            'ticket' => 'protocol.ticket_booking',
        ];

        return array_keys(array_filter($map, fn ($permission) => in_array($permission, $userPermissions)));
    }

    # @ Same scope as the All Requests list, for dashboard counts
    public function scopedQuery()
    {
        return $this->baseQuery(request());
    }

    private function tabs(): array
    {
        $userPermissions = $this->userPermissions();
        $labels = ['guesthouse' => 'Guest House', 'vehicle' => 'Vehicle', 'ticket' => 'Ticket'];

        $tabs = [];

        // Users who see every type get an "All" tab
        if ($this->seesAllRequestTypes($userPermissions)) {
            $tabs[] = [
                'type' => 'all',
                'label' => 'All',
                'count' => $this->getCounts('all'),
                'active' => true,
            ];
        }

        foreach ($this->allowedRequestTypes() as $type) {
            $tabs[] = [
                'type' => $type,
                'label' => $labels[$type],
                'count' => $this->getCounts($type),
                'active' => empty($tabs),
            ];
        }

        return $tabs;
    }

    private function getCounts(string $type): int
    {
        $query = $this->scopedQuery();

        switch ($type) {
            case 'guesthouse':
                $query->where('request_type', 'guesthouse');
                break;
            case 'vehicle':
                $query->where('request_type', 'vehicle');
                break;
            case 'ticket':
                $query->where('request_type', 'ticket');
                break;
            case 'all':
            default:
                // no filter
                break;
        }

        return $this->applyStatusFilter($query)->count();
    }

    # @ Narrow to one status when the page is opened with ?status= (dashboard cards link here with it)
    private function applyStatusFilter($query)
    {
        $statuses = [
            ProtocolRequest::STATUS_PENDING,
            ProtocolRequest::STATUS_RECOMMENDED,
            ProtocolRequest::STATUS_APPROVED,
            ProtocolRequest::STATUS_REJECTED,
        ];

        if (in_array(request('status'), $statuses, true)) {
            $query->where('status', request('status'));
        }

        return $query;
    }

    public function columns(): array
    {
        return [
            ['title' => 'Sr No.', 'data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
            ['title' => 'Request ID', 'data' => 'request_id'],
            ['title' => 'Type', 'data' => 'request_type'],
            ['title' => 'Employee', 'data' => 'employee'],
            ['title' => 'Raised On', 'data' => 'created_at'],
            ['title' => 'Current Stage', 'data' => 'current_stage'],
            ['title' => 'Status', 'data' => 'status'],
            ['title' => 'Action', 'data' => 'action', 'orderable' => false, 'searchable' => false],
        ];
    }

    # @ Base Query
    protected function baseQuery(Request $request)
    {
        $userPermissions = $this->userPermissions();

        // Full access
        if (in_array('protocol.all_booking', $userPermissions)) {
            return ProtocolRequest::query();
        }

        // Booking permissions decide the scope; requests recommended to this user
        // (the approver) are always included on top, whatever their permissions
        return ProtocolRequest::where(function ($q) use ($userPermissions) {
            $q->where(function ($scope) use ($userPermissions) {
                if (in_array('protocol.recommended_booking', $userPermissions)) {
                    $scope->where('status', 'recommended');
                    return;
                }

                $requestTypes = [];

                if (in_array('protocol.guest_house_booking', $userPermissions)) {
                    $requestTypes[] = 'guesthouse';
                }

                if (in_array('protocol.ticket_booking', $userPermissions)) {
                    $requestTypes[] = 'ticket';
                }

                if (in_array('protocol.vehicle_booking', $userPermissions)) {
                    $requestTypes[] = 'vehicle';
                }

                // No valid permission
                empty($requestTypes)
                    ? $scope->whereRaw('1 = 0')
                    : $scope->whereIn('request_type', $requestTypes);
            })->orWhere('recommended_to_id', Auth::id());
        });
    }


    protected function applyTabFilters(Request $request)
    {
        $query = $this->baseQuery($request);

        switch ($request->type) {
            case 'guesthouse':
                $query->where('request_type', 'guesthouse');
                break;
            case 'vehicle':
                $query->where('request_type', 'vehicle');
                break;
            case 'ticket':
                $query->where('request_type', 'ticket');
                break;
            case 'all':
            default:
                // no filter
                break;
        }

        return $this->applyStatusFilter($query);
    }

    public function getDatatable(Request $request)
    {
        return DataTables::of($this->applyTabFilters($request))
            ->addColumn('request_id', fn($e) => $e->request_number ?: '-')
            ->addColumn('request_type', fn($e) => view('protocol::components.type-pill', ['type' => $e->request_type])->render())
            ->addColumn('employee', fn($e) => $e->employee->name ?: '-')
            ->addColumn('created_at', fn($e) => $e->created_at->format('d-m-Y') ?: '-')
            ->addColumn('current_stage', fn($e) => $e->current_stage)
            ->addColumn('status', fn($e) => view('protocol::components.status-badge', ['status' => $e->status])->render())
            ->addColumn('action', fn($e) => $this->actionButtons($e))
            ->rawColumns(['request_type', 'current_stage', 'status', 'action'])
            ->addIndexColumn()
            ->make(true);
    }

    private function actionButtons($data)
    {
        // A request awaiting this user's final decision opens the approver screen
        $editUrl = $data->status === ProtocolRequest::STATUS_RECOMMENDED && (int) $data->recommended_to_id === (int) Auth::id()
            ? route('protocol.manager.review', $data->id)
            : route('protocol.approval.review', $data->id);
        return '<a href="' . $editUrl . '" class="btn btn-sm btn-outline-saffron">Review <i class="bi bi-chevron-right"></i></a>';
    }

    public function getAll()
    {
        return [];
    }

    public function store(array $data)
    {
        // handled in controller store()
    }

    public function update($id, array $data)
    {
        // handled in controller update()
    }

    public function delete($id)
    {
        // handled in controller destroy()
    }
}