<?php

namespace App\DataTables\Master;

use App\Models\SubjectModuleMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SubjectModuleMasterDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('status', function ($row) {
                if ((int) $row->active_inactive === 1) {
                    return '<span class="badge rounded-1 programme-status-badge programme-status-badge--active">Active</span>';
                }

                return '<span class="badge rounded-1 programme-status-badge programme-status-badge--inactive">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                return $this->renderActionsColumn($row);
            })
            ->rawColumns(['status', 'action'])
            ->setRowAttr([
                'class' => 'sm-module-row',
                'data-module-id' => fn ($row) => $row->pk,
            ]);
    }

    /**
     * Mirrors resources/views/admin/subject_module/index.blade.php's previous per-row markup
     * (Edit button + status-toggle switch + Delete form/disabled button).
     */
    private function renderActionsColumn($row): string
    {
        $checked = (int) $row->active_inactive === 1 ? 'checked' : '';

        $editBtn = '<button type="button" class="btn btn-sm sm-action-btn sm-action-edit sm-edit-module-btn"'
            . ' data-id="' . $row->pk . '"'
            . ' data-module-name="' . e($row->module_name) . '"'
            . ' data-active-inactive="' . (int) $row->active_inactive . '"'
            . ' aria-label="Edit subject module">'
            . '<i class="bi bi-pencil" aria-hidden="true"></i>'
            . '</button>';

        $toggle = '<span class="sm-action-switch-wrap">'
            . '<div class="form-check form-switch sm-action-switch mb-0">'
            . '<input class="form-check-input status-toggle" type="checkbox" role="switch"'
            . ' data-table="subject_module_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>'
            . '</div></span>';

        if ((int) $row->active_inactive === 1) {
            $deleteBtn = '<button type="button" class="btn btn-sm sm-action-btn sm-action-delete" disabled aria-disabled="true"'
                . ' title="Cannot delete active subject module" aria-label="Delete subject module (disabled while active)">'
                . '<i class="bi bi-trash" aria-hidden="true"></i></button>';
        } else {
            $deleteUrl = route('subject-module.destroy', $row->pk);
            $token = csrf_token();
            $deleteBtn = '<form action="' . e($deleteUrl) . '" method="POST" class="d-inline m-0 sm-delete-form"'
                . ' onsubmit="return confirm(\'Are you sure you want to delete this Subject Module?\');">'
                . '<input type="hidden" name="_token" value="' . e($token) . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="btn btn-sm sm-action-btn sm-action-delete" aria-label="Delete subject module">'
                . '<i class="bi bi-trash" aria-hidden="true"></i></button>'
                . '</form>';
        }

        return '<div class="sm-module-actions" role="group" aria-label="Subject module actions">'
            . $editBtn . $toggle . $deleteBtn . '</div>';
    }

    public function query(SubjectModuleMaster $model): QueryBuilder
    {
        $query = $model->newQuery();

        // Default newest-first, but only when the user hasn't requested a column sort
        // (clicking a header would otherwise never take visible effect over this).
        if (empty(request('order'))) {
            $query->orderByDesc('created_date');
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('zero_config')
            ->addTableClass('table table-hover align-middle mb-0 w-100 programme-dt-table sm-module-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'language' => [
                    'emptyTable' => $this->emptyStateHtml(),
                    'zeroRecords' => $this->emptyStateHtml(),
                ],
            ]);
    }

    private function emptyStateHtml(): string
    {
        return '<div class="text-center py-5 text-muted">'
            . '<i class="bi bi-inbox fs-2 d-block mb-2 opacity-50" aria-hidden="true"></i>'
            . '<span class="fw-medium">No subject modules found.</span>'
            . '</div>';
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->orderable(false)->searchable(false)->addClass('text-nowrap text-muted fw-medium'),
            Column::make('module_name')->title('Module Name')->orderable(true)->searchable(true)->addClass('sm-col-name'),
            Column::computed('status')->title('Status')->orderable(false)->searchable(false)->addClass('text-nowrap sm-module-status-cell'),
            Column::computed('action')->title('Action')->orderable(false)->searchable(false)->addClass('text-nowrap text-end sm-col-actions'),
        ];
    }

    protected function filename(): string
    {
        return 'SubjectModuleMaster_' . date('YmdHis');
    }
}
