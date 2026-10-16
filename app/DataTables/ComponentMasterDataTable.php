<?php

namespace App\DataTables;

use App\Models\ComponentMaster;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * COE → Master Data → Component Master, server-side via Yajra.
 * Mirrors ExaminationTermMasterDataTable exactly but for `component_master`.
 */
class ComponentMasterDataTable extends DataTable
{
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('component_name', fn ($row) => e($row->component_name))
            ->addColumn('status', fn ($row) => $this->statusCell((bool) $row->active_inactive))
            ->addColumn('actions', fn ($row) => $this->actionsCell((bool) $row->active_inactive))
            ->setRowAttr([
                'data-coe-id' => fn ($row) => encrypt($row->pk),
                'data-coe-active' => fn ($row) => $row->active_inactive ? '1' : '0',
            ])
            ->rawColumns(['status', 'actions']);
    }

    public function query(ComponentMaster $model): Builder
    {
        $query = $model->newQuery();

        $status = request()->query('status_filter');
        if ($status !== null && $status !== '') {
            $query->where('active_inactive', $status);
        }

        return $query->orderBy('component_name');
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('coe-col-sno'),
            Column::make('component_name')->title('Component'),
            Column::computed('status')->title('Status')->addClass('coe-col-center coe-cell-status'),
            Column::computed('actions')->title('Action')->addClass('coe-cell-actions'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('cmpTable')
            ->columns($this->getColumns());
    }

    protected function filename(): string
    {
        return 'ComponentMaster_' . date('YmdHis');
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
            . '<button type="button" class="coe-act coe-act--del">'
            . '<span class="coe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
            . '<span class="coe-act__label">Delete</span></button>'
            . '</div>';
    }
}
