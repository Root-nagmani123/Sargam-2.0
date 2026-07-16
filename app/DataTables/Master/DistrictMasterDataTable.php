<?php

namespace App\DataTables\Master;

use App\Models\District;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class DistrictMasterDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('district_name', fn($row) => $row->district_name ?? '-')
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block">
                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                    data-table="state_district_mapping" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
            </div>';
            })
            ->addColumn('action', function ($row) {
                $edit = route('master.district.edit', $row->pk);
                $delete = route('master.district.delete', $row->pk);
                $isActive = $row->active_inactive == 1 ? 1 : 0;

                return '
                    <div class="dropdown">
                        <a href="javascript:void(0)" id="actionMenu' . $row->pk . '" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="material-symbols-rounded fs-5">more_horiz</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="actionMenu' . $row->pk . '">
                            <li>
                                <a href="' . $edit . '" class="dropdown-item d-flex align-items-center gap-2">
                                    <span class="material-symbols-rounded text-primary fs-6">edit</span>
                                    Edit
                                </a>
                            </li>
                            <li>
                                <form action="' . $delete . '" method="POST" class="d-inline">
                                    ' . csrf_field() . method_field('DELETE') . '
                                    <button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                        onclick="event.preventDefault();
                                            if(' . $isActive . ' == 1) return;
                                            if(confirm(\'Are you sure you want to delete this?\')) { this.closest(\'form\').submit(); }"
                                        ' . ($isActive == 1 ? 'disabled' : '') . '>
                                        <span class="material-symbols-rounded fs-6">delete</span>
                                        Delete
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>';
            })
            ->setRowId('pk')
            ->filterColumn('district_name', function ($query, $keyword) {
                $query->where('district_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['district_name', 'status', 'action']);
    }

    public function query(District $model): QueryBuilder
    {
        return $model->newQuery();
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('districtmaster-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('frtip')
            ->selectStyleSingle()
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'order' => [],
                'paging' => true,
                'pagingType' => 'full_numbers',
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('district_name')->title('District')->orderable(false)->addClass('text-center'),
            Column::computed('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-start'),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'DistrictMaster_' . date('YmdHis');
    }
}
