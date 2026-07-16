<?php

namespace App\DataTables\Master;

use App\Models\Country;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CountryMasterDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('country_name', fn($row) => $row->country_name ?? '-')
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block">
                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                    data-table="country_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
            </div>';
            })
            ->addColumn('action', function ($row) {
                $edit = route('master.country.edit', $row->pk);

                if ($row->active_inactive == 1) {
                    return '
                        <a href="' . $edit . '" class="btn btn-sm btn-outline-primary" title="Edit">
                            <i class="material-icons" style="font-size:16px;">edit</i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete active country">
                            <i class="material-icons" style="font-size:16px;">delete</i>
                        </button>';
                }

                $delete = route('master.country.delete', $row->pk);

                return '
                    <a href="' . $edit . '" class="btn btn-sm btn-outline-primary" title="Edit">
                        <i class="material-icons" style="font-size:16px;">edit</i>
                    </a>
                    <form action="' . $delete . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this?\');">
                        ' . csrf_field() . method_field('DELETE') . '
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                            <i class="material-icons" style="font-size:16px;">delete</i>
                        </button>
                    </form>';
            })
            ->setRowId('pk')
            ->filterColumn('country_name', function ($query, $keyword) {
                $query->where('country_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['country_name', 'status', 'action']);
    }

    public function query(Country $model): QueryBuilder
    {
        return $model->newQuery();
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('countrymaster-table')
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
            Column::computed('DT_RowIndex')->title('#')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('country_name')->title('Country Name')->orderable(false)->addClass('text-center'),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('action')->title('Actions')->searchable(false)->orderable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'CountryMaster_' . date('YmdHis');
    }
}
