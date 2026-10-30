<?php
namespace App\DataTables;

use App\Models\ExamFloorMaster;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Column;

class ExamFloorMasterDataTable extends DataTable
{
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()

            ->editColumn('floor_name', fn($row) => $row->floor_name ?? 'N/A')
            ->editColumn('display_order', fn($row) => $row->display_order ?? '-')

            ->addColumn('status', function ($row) {
                return '<div class="form-check form-switch d-inline-block">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                data-table="exam_floor_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . ($row->active_inactive == 1 ? 'checked' : '') . '>
                        </div>';
            })

            ->addColumn('actions', function ($row) {
                $edit   = route('master.exam_floor.edit', encrypt($row->pk));
                $delete = route('master.exam_floor.delete', encrypt($row->pk));

                if ($row->active_inactive == 1) {
                    return '
                <a href="'.$edit.'" title="Edit">
                    <i class="material-icons">edit</i>
                </a>

                    <button style="border:none;background:none " disabled title="Delete">
                        <i class="material-icons text-danger">delete</i>
                    </button>';
                }

                return '
                <a href="'.$edit.'" title="Edit">
                    <i class="material-icons">edit</i>
                </a>

                <form action="'.$delete.'" method="POST" style="display:inline">
                    '.csrf_field().method_field('DELETE').'
                    <button onclick="return confirm(\'Delete?\')" style="border:none;background:none">
                        <i class="material-icons text-danger">delete</i>
                    </button>
                </form>';
            })

            ->rawColumns(['status','actions']);
    }

    public function query(ExamFloorMaster $model): Builder
    {
        return $model->newQuery()
            ->orderBy('exam_floor_master.pk', 'desc');
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No'),
            Column::make('floor_name')->title('Floor Name'),
            Column::make('display_order')->title('Display Order'),
            Column::computed('status')->title('Status'),
            Column::computed('actions')->title('Actions'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('exam-floor-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->pageLength(10);
    }
}
