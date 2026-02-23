<?php

namespace App\DataTables\Master;

use App\Models\EmployeeGroupMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class EmployeeGroupMasterDataTable extends DataTable
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
            ->addColumn('emp_group_name', fn($row) => $row->emp_group_name ?? '-')
            ->addColumn('action', function ($row) {
                $editUrl = route('master.employee.group.edit', ['id' => encrypt($row->pk)]);
                $deleteUrl = route('master.employee.group.delete', ['id' => encrypt($row->pk)]);
                $groupName = htmlspecialchars($row->emp_group_name ?? 'N/A', ENT_QUOTES, 'UTF-8');

                return '<div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Employee group actions">
                    <a href="' . $editUrl . '" class="text-primary btn-sm edit-employee-group" title="Edit employee group">
                        <i class="material-icons material-symbols-rounded" style="font-size: 18px; vertical-align: middle;">edit</i>
                    </a>
                    <a href="' . $deleteUrl . '"
                       class="text-primary btn-sm delete-employee-group"
                       data-name="' . $groupName . '">
                        <i class="material-icons material-symbols-rounded" style="font-size: 18px; vertical-align: middle;">delete</i>
                    </a>
                </div>';
            })
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block ms-2">
                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                    data-table="employee_group_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
            </div>';
            })

            ->setRowId('pk')
            ->setRowClass('text-center')
            ->filterColumn('emp_group_name', function ($query, $keyword) {
                $query->where('emp_group_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['emp_group_name', 'action', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\EmployeeGroupMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(EmployeeGroupMaster $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('created_date', 'desc');;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('employeegroupmaster-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            //->dom('Bfrtip')
            // ->orderBy(1)
            ->selectStyleSingle()
            ->parameters([
                'responsive' => false,
                'scrollX' => true,
                'autoWidth' => false,
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
            Column::computed('DT_RowIndex')->title('S.No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('emp_group_name')->title('Employee Group Name')->orderable(false)->addClass('text-center'),

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
        return 'EmployeeGroupMaster_' . date('YmdHis');
    }
}
