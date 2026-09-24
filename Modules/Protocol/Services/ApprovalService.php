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
            'tabs' => $this->tabs(),
        ];
    }

    private function tabs(): array
    {
        $userPermissions = Auth::user()->getDirectPermissions()->pluck('name')->toArray();

        // Full access
        if (in_array('protocol.all_booking', $userPermissions)) {
            return [
                [
                    'type' => 'all',
                    'label' => 'All',
                    'count' => $this->getCounts('all'),
                    'active' => true,
                ],
                [
                    'type' => 'guesthouse',
                    'label' => 'Guest House',
                    'count' => $this->getCounts('guesthouse'),
                    'active' => false,
                ],
                [
                    'type' => 'vehicle',
                    'label' => 'Vehicle',
                    'count' => $this->getCounts('vehicle'),
                    'active' => false,
                ],
                [
                    'type' => 'ticket',
                    'label' => 'Ticket',
                    'count' => $this->getCounts('ticket'),
                    'active' => false,
                ],
            ];
        }

        $tabs = [];

        if (in_array('protocol.guest_house_booking', $userPermissions)) {
            $tabs[] = [
                'type' => 'guesthouse',
                'label' => 'Guest House',
                'count' => $this->getCounts('guesthouse'),
                'active' => empty($tabs),
            ];
        }

        if (in_array('protocol.vehicle_booking', $userPermissions)) {
            $tabs[] = [
                'type' => 'vehicle',
                'label' => 'Vehicle',
                'count' => $this->getCounts('vehicle'),
                'active' => empty($tabs),
            ];
        }

        if (in_array('protocol.ticket_booking', $userPermissions)) {
            $tabs[] = [
                'type' => 'ticket',
                'label' => 'Ticket',
                'count' => $this->getCounts('ticket'),
                'active' => empty($tabs),
            ];
        }

        return $tabs;
    }

    private function getCounts(string $type): int
    {
        $query = ProtocolRequest::query();

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

        return $query->count();
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
        $user = Auth::user();

        // Direct user permissions only
        $userPermissions = $user->permissions()->pluck('name')->toArray();

        // Full access
        if (in_array('protocol.all_booking', $userPermissions)) {
            return ProtocolRequest::query();
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
        if (empty($requestTypes)) {
            return ProtocolRequest::whereRaw('1 = 0');
        }

        return ProtocolRequest::whereIn('request_type', $requestTypes);
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

        return $query;
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
        $editUrl = route('protocol.approval.review', $data->id);
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