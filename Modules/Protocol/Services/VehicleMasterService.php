<?php
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
    $editUrl = route('protocol.vehicle-master.edit', $data->id);

    $buttons = '
        <div class="pr-act-group" role="group" aria-label="Row actions">
            <a href="'.$editUrl.'" class="pr-act pr-act--edit">
                <span class="pr-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                <span class="pr-act__label">Edit</span>
            </a>';

    // Show Delete button only when status is not 1
    if ($data->status != 1) {
        $deleteUrl = route('protocol.vehicle-master.destroy', $data->id);

        $buttons .= '
            <form action="'.$deleteUrl.'" method="POST" class="pr-delete-form"
                onsubmit="return confirm(\'Are you sure you want to delete this record?\');">
                '.csrf_field().'
                '.method_field('DELETE').'
                <button type="submit" class="pr-act pr-act--del">
                    <span class="pr-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                    <span class="pr-act__label">Delete</span>
                </button>
            </form>';
    } else {
        $buttons .= '
            <span class="pr-act pr-act--del is-disabled" title="Active vehicles cannot be deleted">
                <span class="pr-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                <span class="pr-act__label">Delete</span>
            </span>';
    }

    $buttons .= '</div>';

    return $buttons;
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