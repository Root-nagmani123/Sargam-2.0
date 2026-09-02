<?php

namespace App\DataTables;

use App\Models\LeaveApplication;
use App\Models\LeaveNatureMaster;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Column;

class LeaveNatureMasterDataTable extends DataTable
{
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()

            ->editColumn('leave_type', function ($row) {
                return $row->leave_type === LeaveApplication::TYPE_PT_EXEMPTION
                    ? 'PT Exemption'
                    : 'Stationed Leave';
            })

            ->editColumn('nature_name', fn ($row) => $row->nature_name ?? 'N/A')

            ->addColumn('status', function ($row) {
                return '<div class="form-check form-switch d-inline-block">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                data-table="leave_nature_master" data-column="active_inactive" data-id="'.$row->pk.'" '.($row->active_inactive == 1 ? 'checked' : '').'>
                        </div>';
            })

            ->addColumn('actions', function ($row) {
                $edit = route('master.leave-nature.edit', encrypt($row->pk));
                $delete = route('master.leave-nature.delete', encrypt($row->pk));

                if ($row->active_inactive == 1) {
                    return '
                        <a href="'.$edit.'" title="Edit">
                            <i class="material-icons">edit</i>
                        </a>
                        <button style="border:none;background:none" disabled title="Cannot delete active record">
                            <i class="material-icons text-danger">delete</i>
                        </button>';
                }

                return '
                    <a href="'.$edit.'" title="Edit">
                        <i class="material-icons">edit</i>
                    </a>
                    <form action="'.$delete.'" method="POST" style="display:inline">
                        '.csrf_field().method_field('DELETE').'
                        <button onclick="return confirm(\'Are you sure you want to delete this record?\')" style="border:none;background:none">
                            <i class="material-icons text-danger">delete</i>
                        </button>
                    </form>';
            })

            ->rawColumns(['status', 'actions']);
    }

    public function query(LeaveNatureMaster $model): Builder
    {
        return $model->newQuery()->orderBy('leave_type')->orderBy('display_order');
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No'),
            Column::make('leave_type')->title('Leave Type'),
            Column::make('nature_name')->title('Nature Name'),
            Column::computed('status')->title('Status'),
            Column::computed('actions')->title('Actions'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('leave-nature-master-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->pageLength(10);
    }

    protected function filename(): string
    {
        return 'LeaveNatureMaster_'.date('YmdHis');
    }
}
