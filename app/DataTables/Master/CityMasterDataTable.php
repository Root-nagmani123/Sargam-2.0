<?php

namespace App\DataTables\Master;

use App\Models\City;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CityMasterDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('city_name', fn($row) => $row->city_name ?? '-')
            ->addColumn('district_name', fn($row) => optional($row->district)->district_name ?? 'N/A')
            ->addColumn('state_name', fn($row) => optional($row->state)->state_name ?? 'N/A')
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block">
                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                    data-table="city_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
            </div>';
            })
            ->addColumn('action', function ($row) {
                $edit = route('master.city.edit', $row->pk);
                $delete = route('master.city.delete', $row->pk);
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
            ->filterColumn('city_name', function ($query, $keyword) {
                $query->where('city_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('district_name', function ($query, $keyword) {
                $query->whereHas('district', function ($q) use ($keyword) {
                    $q->where('district_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('state_name', function ($query, $keyword) {
                $query->whereHas('state', function ($q) use ($keyword) {
                    $q->where('state_name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['city_name', 'district_name', 'state_name', 'status', 'action']);
    }

    public function query(City $model): QueryBuilder
    {
        return $model->newQuery()->with(['state', 'district']);
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('citymaster-table')
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
            Column::computed('DT_RowIndex')->title('S.No')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('city_name')->title('City Name')->orderable(false)->addClass('text-center'),
            Column::computed('district_name')->title('District')->orderable(false)->addClass('text-center'),
            Column::computed('state_name')->title('State')->orderable(false)->addClass('text-center'),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-start'),
        ];
    }

    protected function filename(): string
    {
        return 'CityMaster_' . date('YmdHis');
    }
}
