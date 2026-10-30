<?php
namespace App\DataTables;

use App\Models\ExamBuildingMaster;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Column;

class ExamBuildingMasterDataTable extends DataTable
{
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()

            ->editColumn('building_name', fn($row) => $row->building_name ?? 'N/A')
            ->editColumn('building_short_name', fn($row) => $row->building_short_name ?? 'N/A')
            ->editColumn('description', fn($row) => $row->description ?? '-')

            ->addColumn('status', function ($row) {
                return '<div class="form-check form-switch d-inline-block">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                data-table="exam_building_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . ($row->active_inactive == 1 ? 'checked' : '') . '>
                        </div>';
            })

            ->addColumn('actions', function ($row) {
                $edit   = route('master.exam_building.edit', encrypt($row->pk));
                $delete = route('master.exam_building.delete', encrypt($row->pk));

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

    public function query(ExamBuildingMaster $model): Builder
    {
        return $model->newQuery()
            ->orderBy('exam_building_master.pk', 'desc');
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No'),
            Column::make('building_name')->title('Building Name'),
            Column::make('building_short_name')->title('Short Name'),
            Column::make('description')->title('Description'),
            Column::computed('status')->title('Status'),
            Column::computed('actions')->title('Actions'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('exam-building-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->pageLength(10);
    }
}
