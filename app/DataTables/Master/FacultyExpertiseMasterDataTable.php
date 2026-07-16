<?php

namespace App\DataTables\Master;

use App\Models\FacultyExpertiseMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class FacultyExpertiseMasterDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('expertise_name', fn($row) => $row->expertise_name ?? '-')
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block">
                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                    data-table="faculty_expertise_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
            </div>';
            })
            ->addColumn('action', function ($row) {
                $edit = route('master.faculty.expertise.edit', ['id' => encrypt($row->pk)]);

                if ($row->active_inactive == 1) {
                    return '
                        <a href="' . $edit . '" class="btn btn-sm btn-outline-primary" title="Edit">
                            <i class="material-icons" style="font-size:16px;">edit</i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete active record">
                            <i class="material-icons" style="font-size:16px;">delete</i>
                        </button>';
                }

                $delete = route('master.faculty.expertise.delete', ['id' => encrypt($row->pk)]);

                return '
                    <a href="' . $edit . '" class="btn btn-sm btn-outline-primary" title="Edit">
                        <i class="material-icons" style="font-size:16px;">edit</i>
                    </a>
                    <form action="' . $delete . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this record?\');">
                        ' . csrf_field() . method_field('DELETE') . '
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                            <i class="material-icons" style="font-size:16px;">delete</i>
                        </button>
                    </form>';
            })
            ->setRowId('pk')
            ->filterColumn('expertise_name', function ($query, $keyword) {
                $query->where('expertise_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['expertise_name', 'status', 'action']);
    }

    public function query(FacultyExpertiseMaster $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('pk', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('facultyexpertisemaster-table')
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
            Column::make('expertise_name')->title('Faculty Expertise')->orderable(false)->addClass('text-center'),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'FacultyExpertiseMaster_' . date('YmdHis');
    }
}
