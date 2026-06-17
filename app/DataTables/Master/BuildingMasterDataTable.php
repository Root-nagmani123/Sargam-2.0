<?php

namespace App\DataTables\Master;

use App\Models\BuildingMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class BuildingMasterDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     * @return \Yajra\DataTables\EloquentDataTable
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('building_name', fn($row) => $row->building_name ?? '-')
            ->addColumn('no_of_floors', fn($row) => $row->no_of_floors ?? '-')
            ->addColumn('no_of_rooms', fn($row) => $row->no_of_rooms ?? '-')
            ->addColumn('building_type', fn($row) => $row->building_type ?? '-')
            ->addColumn('action', function ($row) {
                $editUrl = route('master.hostel.building.edit', ['id' => encrypt($row->pk)]);
                $deleteUrl = route('master.hostel.building.destroy', ['id' => encrypt($row->pk)]);

                // Presentation only: inline edit / status-toggle / delete icons.
                // Routes, the .status-toggle AJAX hook and the status-gated delete
                // (enabled only when inactive) are all preserved exactly as before.
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                $deleteDisabled = $row->active_inactive == 0 ? '' : 'disabled';

                return '
                    <div class="hb-row-actions d-inline-flex align-items-center justify-content-center gap-2">
                        <a href="' . $editUrl . '" class="hb-icon-btn hb-icon-edit hb-edit-trigger" title="Edit" aria-label="Edit"
                            data-pk="' . encrypt($row->pk) . '"
                            data-name="' . e($row->building_name) . '"
                            data-floors="' . e($row->no_of_floors) . '"
                            data-rooms="' . e($row->no_of_rooms) . '"
                            data-type="' . e($row->building_type) . '"
                            data-status="' . (int) $row->active_inactive . '">
                            <i class="material-icons material-symbols-rounded">edit</i>
                        </a>
                        <div class="form-check form-switch m-0 hb-row-switch">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                data-table="building_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
                        </div>
                        <form action="' . $deleteUrl . '" method="POST" class="d-inline m-0" onsubmit="return confirm(\'Are you sure you want to delete this building?\')">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                            <button type="submit" class="hb-icon-btn hb-icon-delete" title="Delete" data-bs-toggle="tooltip" aria-label="Delete" ' . $deleteDisabled . '>
                                <i class="material-icons material-symbols-rounded">delete</i>
                            </button>
                        </form>
                    </div>
                ';
            })
            ->addColumn('status', function ($row) {
                // Presentation only: render the active/inactive flag as a pill badge.
                return $row->active_inactive == 1
                    ? '<span class="badge rounded-1 hb-badge hb-badge-active">Active</span>'
                    : '<span class="badge rounded-1 hb-badge hb-badge-inactive">Inactive</span>';
            })

            ->setRowId('pk')
            ->filterColumn('building_name', function ($query, $keyword) {
                $query->where('building_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['building_name', 'action', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\BuildingMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(BuildingMaster $model): QueryBuilder
    {
        return $model->newQuery()->latest('pk');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('hostelbuildingmaster-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    //->dom('Bfrtip')
                    // ->orderBy(1)
                    ->selectStyleSingle()
                    ->parameters([
                        'order' => [],
                    ])
                    ->buttons([
                        Button::make('excel'),
                        Button::make('csv'),
                        Button::make('pdf'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload')
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     *
     * @return array
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false),
            Column::make('building_name')->title('Building Name')->orderable(false),
            Column::make('no_of_floors')->title('Number of Floors')->orderable(false),
            Column::make('no_of_rooms')->title('Number of Rooms')->orderable(false),
            Column::make('building_type')->title('Building Type')->orderable(false),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false),
            Column::make('action')->title('Action')->searchable(false)->orderable(false)
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'BuildingMaster_' . date('YmdHis');
    }
}
