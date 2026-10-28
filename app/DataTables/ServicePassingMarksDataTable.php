<?php

namespace App\DataTables;

use App\Models\ServiceMaster;
use App\Models\ServicePassingMarksMaster;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * COE → Master Data → Service Passing Marks, server-side via Yajra.
 * Same shape as ComponentMasterDataTable; the Service column shows the short
 * name (full name on hover), and data-coe-val-service carries the pk for the
 * Edit dialog's select.
 */
class ServicePassingMarksDataTable extends DataTable
{
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('service_master_pk', fn ($row) => $row->service
                ? '<span title="' . e($row->service->service_name) . '">' . e($row->service->service_short_name) . '</span>'
                : '-')
            ->editColumn('passing_marks', fn ($row) => SubjectMaxMarksDataTable::marks($row->passing_marks))
            ->addColumn('status', fn ($row) => $this->statusCell((bool) $row->active_inactive))
            ->addColumn('actions', fn ($row) => $this->actionsCell((bool) $row->active_inactive))
            ->setRowAttr([
                'data-coe-id' => fn ($row) => encrypt($row->pk),
                'data-coe-active' => fn ($row) => $row->active_inactive ? '1' : '0',
                'data-coe-val-service' => fn ($row) => $row->service_master_pk,
            ])
            ->filterColumn('service_master_pk', fn ($q, $kw) => $q->whereHas('service', fn ($qq) => $qq
                ->where('service_name', 'like', '%' . $kw . '%')
                ->orWhere('service_short_name', 'like', '%' . $kw . '%')))
            ->orderColumn('service_master_pk', fn ($q, $dir) => $q->orderBy(
                ServiceMaster::select('service_short_name')->whereColumn('service_master.pk', 'service_passing_marks_master.service_master_pk'),
                $dir
            ))
            ->rawColumns(['service_master_pk', 'status', 'actions']);
    }

    public function query(ServicePassingMarksMaster $model): Builder
    {
        $query = $model->newQuery()->with('service');

        $status = request()->query('status_filter');
        if ($status !== null && $status !== '') {
            $query->where('active_inactive', $status);
        }

        return $query->orderByDesc('pk');
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('coe-col-sno'),
            Column::make('service_master_pk')->title('Service'),
            Column::make('passing_marks')->title('Passing Marks'),
            Column::computed('status')->title('Status')->addClass('coe-col-center coe-cell-status'),
            Column::computed('actions')->title('Action')->addClass('coe-cell-actions'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('spmTable')
            ->columns($this->getColumns());
    }

    protected function filename(): string
    {
        return 'ServicePassingMarks_' . date('YmdHis');
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
