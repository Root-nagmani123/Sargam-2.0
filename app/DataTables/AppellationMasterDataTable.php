<?php

namespace App\DataTables;

use App\Models\AppellationMaster;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Column;

class AppellationMasterDataTable extends DataTable
{
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()

            ->editColumn('appettation_name', fn($row) => $row->appettation_name ?? 'N/A')

            ->addColumn('status', function ($row) {
                return '<div class="form-check form-switch d-inline-block">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                data-table="appellation_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . ($row->active_inactive == 1 ? 'checked' : '') . '>
                        </div>';
            })

            ->addColumn('actions', function ($row) {
                $edit   = route('master.appellation.edit', encrypt($row->pk));
                $delete = route('master.appellation.delete', encrypt($row->pk));

                if ($row->active_inactive == 1) {
                    return '
                        <a href="' . $edit . '" title="Edit">
                            <i class="material-icons">edit</i>
                        </a>
                        <button style="border:none;background:none" disabled title="Cannot delete active record">
                            <i class="material-icons text-danger">delete</i>
                        </button>';
                }

                return '
                    <a href="' . $edit . '" title="Edit">
                        <i class="material-icons">edit</i>
                    </a>
                    <form action="' . $delete . '" method="POST" style="display:inline">
                        ' . csrf_field() . method_field('DELETE') . '
                        <button onclick="return confirm(\'Are you sure you want to delete this record?\')" style="border:none;background:none">
                            <i class="material-icons text-danger">delete</i>
                        </button>
                    </form>';
            })

            ->rawColumns(['status', 'actions']);
    }

    public function query(AppellationMaster $model): Builder
    {
        return $model->newQuery()->orderBy('pk', 'desc');
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No'),
            Column::make('appettation_name')->title('Appellation Name'),
            Column::computed('status')->title('Status'),
            Column::computed('actions')->title('Actions'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('appellation-master-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->pageLength(10);
    }

    protected function filename(): string
    {
        return 'AppellationMaster_' . date('YmdHis');
    }
}