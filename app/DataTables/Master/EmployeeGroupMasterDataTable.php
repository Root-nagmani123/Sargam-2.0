<?php

namespace App\DataTables\Master;

use App\Models\EmployeeGroupMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
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
            ->addColumn('emp_group_name', fn($row) => e($row->emp_group_name ?? '-'))
            ->addColumn('status', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                return '<span class="badge rounded-1 programme-status-badge egm-status-badge '
                    . ($isActive ? 'programme-status-badge--active">Active' : 'programme-status-badge--inactive">Inactive')
                    . '</span>';
            })
            ->addColumn('action', function ($row) {
                $isActive = (int) $row->active_inactive === 1;
                $name = e($row->emp_group_name ?? '');
                $encryptedPk = encrypt($row->pk);

                $editBtn = '<button type="button" class="egm-action-btn egm-action-edit egm-edit-btn"'
                    . ' aria-label="Edit employee group"'
                    . ' data-pk="' . e($encryptedPk) . '" data-name="' . $name . '">'
                    . '<i class="bi bi-pencil" aria-hidden="true"></i>'
                    . '</button>';

                $toggle = '<div class="form-check form-switch egm-action-switch-wrap mb-0">'
                    . '<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                    . ' data-table="employee_group_master" data-column="active_inactive"'
                    . ' data-id="' . $row->pk . '" ' . ($isActive ? 'checked' : '') . '>'
                    . '</div>';

                // Active rows are delete-guarded, matching the other master screens.
                $deleteBtn = '<button type="button" class="egm-action-btn egm-action-delete egm-delete-btn"'
                    . ' aria-label="Delete employee group"'
                    . ' data-url="' . e(route('master.employee.group.delete', ['id' => $encryptedPk])) . '"'
                    . ' data-name="' . $name . '"'
                    . ($isActive ? ' disabled aria-disabled="true" title="Deactivate this employee group before deleting it"' : '')
                    . '>'
                    . '<i class="bi bi-trash3" aria-hidden="true"></i>'
                    . '</button>';

                return '<div class="egm-group-actions" role="group" aria-label="Employee group actions">'
                    . $editBtn . $toggle . $deleteBtn
                    . '</div>';
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
        return $model->newQuery();
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
            // No ->dom(): the global datatable-global-ui.js default renders the
            // length element the footer needs for "Showing [N] of M items".
            ->selectStyleSingle()
            ->parameters([
                'responsive' => true,
                'scrollX' => false,
                'autoWidth' => false,
                'order' => [],
                'paging' => true,
                'pagingType' => 'full_numbers',
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
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
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false)->addClass('egm-col-sno'),
            Column::make('emp_group_name')->title('Employee Group Name')->orderable(false)->addClass('egm-col-name'),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('egm-col-status'),
            Column::computed('action')->title('Action')->searchable(false)->orderable(false)->addClass('egm-col-action'),
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
