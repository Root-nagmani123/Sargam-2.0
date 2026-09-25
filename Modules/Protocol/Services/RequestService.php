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

class RequestService
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
            ['title' => 'Details', 'data' => 'details'],
            ['title' => 'Raised On', 'data' => 'created_at'],
            ['title' => 'Status', 'data' => 'status'],
            ['title' => 'Action', 'data' => 'action', 'orderable' => false, 'searchable' => false],
        ];
    }

    # @ Base Query
    protected function baseQuery(Request $request)
    {
        $query = ProtocolRequest::with(['requestable', 'batch', 'logs'])->forUser(Auth::id());
        return $query;
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
        return DataTables::eloquent($this->applyTabFilters($request))
            ->addColumn('request_id', fn($e) => $e->request_number ?: '-')
            ->addColumn('request_type', fn($e) => view('protocol::components.type-pill', ['type' => $e->request_type])->render())
            ->addColumn('details', fn($e) => $e->current_stage)
            ->addColumn('created_at', fn($e) => $e->created_at->format('d-m-Y') ?: '-')
            ->addColumn('status', fn($e) => view('protocol::components.status-badge', ['status' => $e->status])->render())
            ->addColumn('action', function ($row) {
                return '
                    <a href="' . route('protocol.requests.show', $row->id) . '" class="btn btn-sm btn-primary">
                        View <i class="bi bi-chevron-right"></i>
                    </a>';
            })
            ->rawColumns(['request_type', 'details', 'status', 'action'])
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