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
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block">
                    <input class="form-check-input plain-status-toggle" type="checkbox" role="switch"
                        data-table="course_group_type_master"
                        data-column="active_inactive"
                        data-id="' . $row->pk . '"
                        ' . $checked . '>
                </div>';
            })


            ->addColumn('actions', function ($row) {
                $disabled = $row->active_inactive == 1 ? 'disabled' : '';

                return '
                    <div class="d-inline-flex align-items-center gap-2"
                        role="group"
                        aria-label="Row actions">

                        <!-- Edit Action -->
                        <a href="javascript:void(0)"
                        data-id="' . $row->pk . '"
                        data-mdo_duty_type_name="' . $row->mdo_duty_type_name . '"
                         data-id="' . $row->pk . '"
                        data-active_inactive="' . $row->active_inactive . '"
                        class="btn btn-sm edit-btn btn-outline-primary d-inline-flex align-items-center gap-1"
                        aria-label="Edit course group type">

                            <i class="material-icons material-symbols-rounded"
                            style="font-size:18px;">edit</i>

                            <span class="d-none d-md-inline">Edit</span>
                        </a>

                        <!-- Delete Action -->
                        <a href="javascript:void(0)"
                        data-id="' . $row->pk . '"
                        class="btn btn-sm btn-outline-danger delete-btn d-inline-flex align-items-center gap-1 ' . $disabled . '"
                        aria-disabled="' . ($row->active_inactive == 1 ? 'true' : 'false') . '">
                            <i class="material-icons material-symbols-rounded"
                            style="font-size:18px;">delete</i>
                            <span class="d-none d-md-inline">Delete</span>
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
            // ->orderBy(1)
            ->parameters([
                'order' => [],
                'responsive' => true,
                'autoWidth' => false,
                'scrollX' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'lengthMenu' => [[10, 25, 50, 100], [10, 25, 50, 100]],
                'buttons' => ['excel', 'csv', 'pdf', 'print', 'reset', 'reload'],
                'columnDefs' => [
                    ['orderable' => false, 'targets' => 0],
                    ['orderable' => false, 'targets' => 1],
                    ['orderable' => false, 'targets' => 2],
                    ['orderable' => false, 'targets' => 3],
                ],
                'language' => [
                    'paginate' => [
                        'previous' => ' <i class="material-icons menu-icon material-symbols-rounded"
                                                    style="font-size: 24px;">chevron_left</i>',
                        'next' => '<i class="material-icons menu-icon material-symbols-rounded"
                                                    style="font-size: 24px;">chevron_right</i>'
                    ]
                ],

            ])
            ->selectStyleSingle()
            ->buttons([
                Button::make('excel'),
                Button::make('csv'),
                Button::make('pdf'),
                Button::make('print'),
                Button::make('reset'),
                Button::make('reload'),
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
            Column::make('mdo_duty_type_name')->title('Duty type')->addClass('text-center')->orderable(false)->searchable(true),
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
