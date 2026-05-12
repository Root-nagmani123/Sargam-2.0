<?php

namespace App\DataTables;

use App\Models\MDODutyTypeMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class MDODutyTypeMasterDataTable extends DataTable
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
            ->setRowId('pk')
            ->editColumn('mdo_duty_type_name', fn($row) => '<label class="text-dark">' . $row->mdo_duty_type_name . '</label>')
            ->filterColumn('mdo_duty_type_name', function ($query, $keyword) {
                $query->where('mdo_duty_type_name', 'like', "%{$keyword}%");
            })
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->where('mdo_duty_type_name', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            ->addColumn('status', function ($row) {
                if ($row->active_inactive == 1) {
                    return '<span class="mdt-badge-active">Active</span>';
                }
                return '<span class="mdt-badge-inactive">Inactive</span>';
            })

            ->addColumn('actions', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';

                return '
                    <div class="d-inline-flex align-items-center gap-2">

                        <!-- Edit -->
                        <a href="javascript:void(0)"
                            data-id="' . $row->pk . '"
                            data-mdo_duty_type_name="' . htmlspecialchars($row->mdo_duty_type_name, ENT_QUOTES, 'UTF-8') . '"
                            data-active_inactive="' . $row->active_inactive . '"
                            class="mdt-action-btn edit-btn text-primary"
                            title="Edit">
                            <span class="material-icons material-symbols-rounded" style="font-size:20px;">edit</span>
                        </a>

                        <!-- Status Toggle -->
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input plain-status-toggle"
                                   type="checkbox"
                                   role="switch"
                                   data-id="' . $row->pk . '"
                                   style="cursor:pointer;width:2.2em;height:1.2em;"
                                   ' . $checked . '>
                        </div>

                        <!-- Delete -->
                        <a href="javascript:void(0)"
                            data-id="' . $row->pk . '"
                            class="mdt-action-btn delete-btn text-danger"
                            title="Delete">
                            <span class="material-icons material-symbols-rounded" style="font-size:20px;">delete</span>
                        </a>

                    </div>
                ';
            })
            ->rawColumns(['mdo_duty_type_name', 'status', 'actions']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\MDODutyTypeMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(MDODutyTypeMaster $model): QueryBuilder
    {
        // Show all records (both active and inactive)
        return $model->newQuery()->orderBy('pk', 'desc');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('mdodutytypemaster-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'order' => [],
                'dom' => 'rt',
                'paging' => false,
                'info' => false,
                'searching' => true,
                'responsive' => true,
                'autoWidth' => false,
                'scrollX' => false,
                'columnDefs' => [
                    ['orderable' => false, 'targets' => '_all'],
                ],
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
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('mdo_duty_type_name')->title('Duty Type Name')->addClass('text-center')->orderable(false)->searchable(true),
            Column::computed('status')->title('Status')->addClass('text-center')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Actions')->addClass('text-center')->orderable(false)->searchable(false),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'MDODutyTypeMaster_' . date('YmdHis');
    }
}
