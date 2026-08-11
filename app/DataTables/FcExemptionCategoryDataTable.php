<?php

namespace App\DataTables;

use App\Models\ExemptionCategory;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class FcExemptionCategoryDataTable extends DataTable
{
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()

            ->editColumn('created_at', fn ($row) => $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('Y-m-d') : 'N/A')
            ->addColumn('created_by_name', fn ($row) => $row->creator->name ?? 'N/A')
            ->addColumn('modified_by_name', fn ($row) => $row->updater->name ?? 'N/A')

            ->addColumn('actions', function ($row) {
                $edit = route('exemptionEdit', $row->pk);

                return '<a href="' . $edit . '" class="btn btn-sm btn-info">Edit</a>';
            })

            ->addColumn('status', function ($row) {
                return '<div class="form-check form-switch d-inline-block">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                data-table="fc_exemption_master" data-column="visible" data-id="' . $row->pk . '" ' . ($row->visible == 1 ? 'checked' : '') . '>
                        </div>';
            })

            ->rawColumns(['actions', 'status']);
    }

    public function query(ExemptionCategory $model): Builder
    {
        return $model->newQuery()
            ->with(['creator', 'updater'])
            ->where('is_notice', 0)
            ->orderBy('pk', 'desc');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('exemption-master-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->pageLength(10);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No'),
            Column::make('Exemption_name')->title('Exemption Name'),
            Column::make('description')->title('Description'),
            Column::make('created_at')->title('Created Date'),
            Column::computed('created_by_name')->title('Created By'),
            Column::computed('modified_by_name')->title('Modified By'),
            Column::computed('actions')->title('Action'),
            Column::computed('status')->title('Status'),
        ];
    }

    protected function filename(): string
    {
        return 'ExemptionMaster_' . date('YmdHis');
    }
}
