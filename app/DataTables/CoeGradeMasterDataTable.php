<?php

namespace App\DataTables;

use App\Models\COE\GradeMaster;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * COE → Master Data → Grade Master, server-side via Yajra.
 * Same shape as SubjectMaxMarksDataTable; data-coe-val-result carries the
 * result status for the Edit dialog's select.
 */
class CoeGradeMasterDataTable extends DataTable
{
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('min_percent', fn ($row) => SubjectMaxMarksDataTable::marks($row->min_percent))
            ->editColumn('max_percent', fn ($row) => SubjectMaxMarksDataTable::marks($row->max_percent))
            ->addColumn('status', fn ($row) => $this->statusCell((bool) $row->active_inactive))
            ->addColumn('actions', fn ($row) => $this->actionsCell((bool) $row->active_inactive))
            ->setRowAttr([
                'data-coe-id' => fn ($row) => encrypt($row->pk),
                'data-coe-active' => fn ($row) => $row->active_inactive ? '1' : '0',
                'data-coe-val-result' => fn ($row) => $row->result_status,
            ])
            ->rawColumns(['status', 'actions']);
    }

    public function query(GradeMaster $model): Builder
    {
        $query = $model->newQuery();

        $status = request()->query('status_filter');
        if ($status !== null && $status !== '') {
            $query->where('active_inactive', $status);
        }

        return $query->orderByDesc('max_percent');
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('coe-col-sno'),
            Column::make('grade')->title('Grade'),
            Column::make('classification')->title('Classification'),
            Column::make('min_percent')->title('Minimum %'),
            Column::make('max_percent')->title('Maximum %'),
            Column::make('result_status')->title('Result Status'),
            Column::computed('status')->title('Status')->addClass('coe-col-center coe-cell-status'),
            Column::computed('actions')->title('Action')->addClass('coe-cell-actions'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('cgmTable')
            ->columns($this->getColumns());
    }

    protected function filename(): string
    {
        return 'GradeMaster_' . date('YmdHis');
    }

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
            // An active grade cannot be deleted (GradeMasterController::destroy); deactivate first.
            . '<button type="button" class="coe-act coe-act--del"'
            . ($active ? ' disabled title="Deactivate the grade before deleting it"' : '') . '>'
            . '<span class="coe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
            . '<span class="coe-act__label">Delete</span></button>'
            . '</div>';
    }
}
