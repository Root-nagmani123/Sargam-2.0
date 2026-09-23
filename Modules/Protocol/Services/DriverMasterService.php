<?php

namespace Modules\Protocol\Services;

use Illuminate\Http\Request;
use Modules\Protocol\Entities\ProtocolDriverMaster;
use Yajra\DataTables\Facades\DataTables;

class DriverMasterService
{
    /**
     * Page configuration data.
     */
    public function pageData(): array
    {
        return [
            'columns' => $this->columns(),
            'filters' => [],
        ];
    }

    /**
     * DataTable columns.
     */
    public function columns(): array
    {
        return [
            [
                'title' => 'Sr No.',
                'data' => 'DT_RowIndex',
                'orderable' => false,
                'searchable' => false,
            ],
            [
                'title' => 'Driver Name',
                'data' => 'name',
            ],
            [
                'title' => 'Driver Type',
                'data' => 'type',
            ],
            // [
            //     'title' => 'Driver DOB',
            //     'data' => 'dob',
            // ],
            [
                'title' => "Driver's No.",
                'data' => 'driver_no',
            ],
            // [
            //     'title' => 'Driving License No.',
            //     'data' => 'driving_licence_no',
            // ],
            // [
            //     'title' => 'Licence Expiry Date',
            //     'data' => 'licence_expiry_date',
            // ],
            // [
            //     'title' => 'Hiring Date',
            //     'data' => 'hiring_date',
            // ],
            // [
            //     'title' => 'Helper One',
            //     'data' => 'helper_one',
            // ],
            // [
            //     'title' => 'Helper Two',
            //     'data' => 'helper_two',
            // ],
            // [
            //     'title' => 'Note About Driver',
            //     'data' => 'note_about_driver',
            // ],
            // [
            //     'title' => 'Country',
            //     'data' => 'country_id',
            // ],
            // [
            //     'title' => 'State',
            //     'data' => 'state_id',
            // ],
            // [
            //     'title' => 'City',
            //     'data' => 'city_id',
            // ],
            // [
            //     'title' => 'Postal Code',
            //     'data' => 'postal_code',
            // ],
            [
                'title' => 'Email',
                'data' => 'email',
            ],
            // [
            //     'title' => 'Address',
            //     'data' => 'address',
            // ],
            [
                'title' => 'Action',
                'data' => 'action',
                'orderable' => false,
                'searchable' => false,
            ],
        ];
    }

    /**
     * Base query for Driver Master.
     */
    protected function baseQuery(Request $request)
    {
        return ProtocolDriverMaster::query();
    }

    /**
     * Get Driver Master data for DataTable.
     */
    public function getDatatable(Request $request)
    {
        return DataTables::of($this->baseQuery($request))

            ->editColumn('hiring_date', function ($driver) {
                return $driver->hiring_date
                    ? $driver->hiring_date->format('d-m-Y')
                    : '-';
            })
            

            ->addColumn('action', function ($driver) {
                return $this->actionButtons($driver);
            })

            ->rawColumns(['action'])

            ->addIndexColumn()

            ->make(true);
    }

    /**
     * Get all drivers.
     */
    public function getAll()
    {
        return ProtocolDriverMaster::query()->get();
    }

    /**
     * Store a new driver.
     */
    public function store(array $data)
    {
        return ProtocolDriverMaster::create($data);
    }

    /**
     * Update an existing driver.
     */
    public function update($id, array $data)
    {
        $driver = ProtocolDriverMaster::findOrFail($id);

        $driver->update($data);

        return $driver;
    }

    /**
     * Delete a driver.
     */
    public function delete($id)
    {
        $driver = ProtocolDriverMaster::findOrFail($id);

        return $driver->delete();
    }

    /**
     * Generate action buttons for DataTable.
     */
   private function actionButtons($data)
    {
        $deleteUrl = route('protocol.driver-master.destroy', $data->id);
        $editUrl = route('protocol.driver-master.edit', $data->id);
      // dd($editUrl);
        $buttons = '
        <div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Menu actions">
            <!-- Edit -->
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
        return $buttons;
    }
}