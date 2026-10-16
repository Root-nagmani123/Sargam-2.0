<?php

namespace App\DataTables;

use App\Models\ExaminationTypeMaster;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * COE → Master Data → Examination Type Master, server-side via Yajra.
 *
 * The grid markup/CSS (coe-admin.css, coe-grid.js) stays the same as the
 * other COE "simple master" screens; only the row source changes from a
 * fully server-rendered <tbody> to Yajra's ajax() JSON.
 */
class ExaminationTypeMasterDataTable extends DataTable
{
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('exam_type_name', fn ($row) => e($row->exam_type_name))
            ->addColumn('status', fn ($row) => $this->statusCell((bool) $row->active_inactive))
            ->addColumn('actions', fn ($row) => $this->actionsCell((bool) $row->active_inactive))
            ->setRowAttr([
                'data-coe-id' => fn ($row) => encrypt($row->pk),
                'data-coe-active' => fn ($row) => $row->active_inactive ? '1' : '0',
            ])
            ->rawColumns(['status', 'actions']);
    }

    public function query(ExaminationTypeMaster $model): Builder
    {
        $query = $model->newQuery();

        $status = request()->query('status_filter');
        if ($status !== null && $status !== '') {
            $query->where('active_inactive', $status);
        }

        return $query->orderBy('exam_type_name');
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('coe-col-sno'),
            Column::make('exam_type_name')->title('Examination Type'),
            Column::computed('status')->title('Status')->addClass('coe-col-center coe-cell-status'),
            Column::computed('actions')->title('Action')->addClass('coe-cell-actions'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('extTable')
            ->columns($this->getColumns());
    }

    protected function filename(): string
    {
        return 'ExaminationTypeMaster_' . date('YmdHis');
    }

    /** Same markup as coe-grid.js's paintState(), so toggling matches an un-reloaded row. */
    private function statusCell(bool $active): string
    {
        return '<span class="coe-state coe-state--' . ($active ? 'active' : 'inactive') . '">'
            . ($active ? 'Active' : 'Inactive') . '</span>';
    }

    private function actionsCell(bool $active): string
    {
        return '<div class="coe-act-group" role="group" aria-label="Row actions">'
            . '<button type="button" class="coe-act coe-act--edit">'
            . '<span class="coe-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>'
            . '<span class="coe-act__label">Edit</span></button>'
            . '<label class="coe-act coe-act--toggle ' . ($active ? 'is-on' : 'is-off') . '">'
            . '<span class="coe-act__icon"><input type="checkbox" role="switch" '
            . 'class="form-check-input plain-status-toggle coe-switch"' . ($active ? ' checked' : '')
            . ' aria-label="' . ($active ? 'Deactivate' : 'Activate') . '"></span>'
            . '<span class="coe-act__label">' . ($active ? 'Deactivate' : 'Activate') . '</span></label>'
            . '<button type="button" class="coe-act coe-act--del">'
            . '<span class="coe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
            . '<span class="coe-act__label">Delete</span></button>'
            . '</div>';
    }
}
