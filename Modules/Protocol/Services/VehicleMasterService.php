<?php
######################################
// DEVELOPER INFO 
// => MANJEET CHNAD (शैतान 💀)
// => manjeetchand01@gmail.com
// => +919997294527
// => 23 July 2026
// => veragyam param sukham : 👹
######################################
namespace Modules\Protocol\Services;

use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Modules\Protocol\Entities\ProtocolVehicleMaster;

class VehicleMasterService
{
    public function pageData(): array
    {
        return [
            'columns' => $this->columns(),
            'filters' => [],
            'categories' => [],
            'groups' => [],
            'parent_menus' => [],
        ];
    }

    public function columns(): array
    {
        return [
            ['title' => 'Sr No.', 'data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
            ['title' => 'Vehicle Name', 'data' => 'vehicle_name'],
            ['title' => 'Vehicle Type', 'data' => 'vehicle_type'],
            ['title' => 'Vehicle Number', 'data' => 'vehicle_number'],
            ['title' => 'Model', 'data' => 'model'],
            ['title' => 'Action', 'data' => 'action', 'orderable' => false, 'searchable' => false],
        ];
    }

    # @ Base Query
    protected function baseQuery(Request $request)
    {
        return ProtocolVehicleMaster::query();
    }

    public function getDatatable(Request $request)
    {
        return DataTables::of($this->baseQuery($request))
            ->addColumn('vehicle_name', fn ($e) => $e->vehicle_name ?: '-')
            ->addColumn('vehicle_type', fn ($e) => $e->vehicle_type ?: '-')
            ->addColumn('vehicle_number', fn ($e) => $e->vehicle_number ?: '-')
            ->addColumn('model', fn ($e) => $e->model ?: '-')
            ->addColumn('action', fn ($e) => $this->actionButtons($e))
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->make(true);
    }

    private function actionButtons($data)
    {
        $editUrl   = route('protocol.vehicle-master.edit', $data->id);
        $deleteUrl = route('protocol.vehicle-master.destroy', $data->id);

        return '
        <div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Menu actions">
            <a href="'.$editUrl.'" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" aria-label="Edit menu">
                <i class="material-icons material-symbols-rounded fs-6" aria-hidden="true">edit</i> <span class="d-none d-md-inline">Edit</span>
            </a>
            <form action="'.$deleteUrl.'" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this record?\');">
                '.csrf_field().'
                '.method_field('DELETE').'
                <button type="submit" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" aria-label="Delete category">
                    <span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>
                    <span class="d-none d-md-inline">Delete</span>
                </button>
            </form>
        </div>';
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