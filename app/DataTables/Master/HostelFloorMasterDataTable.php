<?php

namespace App\DataTables\Master;

use App\Models\FloorMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class HostelFloorMasterDataTable extends DataTable
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
            ->addColumn('floor_name', fn($row) => $row->floor_name ?? '-')
            ->addColumn('action', function ($row) {
                $editUrl = route('master.hostel.floor.edit', ['id' => encrypt($row->pk)]);
                $deleteUrl = route('master.hostel.floor.destroy', ['id' => encrypt($row->pk)]);

                // Presentation only: inline edit / status-toggle / delete icons.
                // The .status-toggle AJAX hook is preserved; delete is status-gated
                // (enabled only when inactive), mirroring the Building module.
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                $deleteDisabled = $row->active_inactive == 0 ? '' : 'disabled';

                return '
                    <div class="hf-row-actions d-inline-flex align-items-center justify-content-center gap-2">
                        <a href="' . $editUrl . '" class="hf-icon-btn hf-icon-edit hf-edit-trigger" title="Edit" aria-label="Edit"
                            data-pk="' . encrypt($row->pk) . '"
                            data-name="' . e($row->floor_name) . '"
                            data-status="' . (int) $row->active_inactive . '">
                            <i class="material-icons material-symbols-rounded">edit</i>
                        </a>
                        <div class="form-check form-switch m-0 hf-row-switch">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                data-table="floor_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
                        </div>
                        <form action="' . $deleteUrl . '" method="POST" class="d-inline m-0" onsubmit="return confirm(\'Are you sure you want to delete this floor?\')">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                            <button type="submit" class="hf-icon-btn hf-icon-delete" title="Delete" aria-label="Delete" ' . $deleteDisabled . '>
                                <i class="material-icons material-symbols-rounded">delete</i>
                            </button>
                        </form>
                    </div>
                ';
            })
            ->addColumn('status', function ($row) {
                // Presentation only: render the active/inactive flag as a pill badge.
                return $row->active_inactive == 1
                    ? '<span class="badge rounded-1 hf-badge hf-badge-active">Active</span>'
                    : '<span class="badge rounded-1 hf-badge hf-badge-inactive">Inactive</span>';
            })

            ->setRowId('pk')
            ->setRowClass('text-center')
            ->filterColumn('floor_name', function ($query, $keyword) {
                $query->where('floor_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['floor_name', 'action', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\HostelFloorMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(FloorMaster $model): QueryBuilder
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
                    ->setTableId('hostelfloormaster-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    //->dom('Bfrtip')
                    // ->orderBy(1)
                    ->parameters([
                        'order' => [],
                    ])
                    ->selectStyleSingle()
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
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('floor_name')->title('Floor Name')->orderable(false)->addClass('text-center'),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center')
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'HostelFloorMaster_' . date('YmdHis');
    }
}
