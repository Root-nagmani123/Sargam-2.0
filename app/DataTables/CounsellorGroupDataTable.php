<?php
namespace App\DataTables;

use App\Models\CounsellorGroup;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CounsellorGroupDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('course_name', function ($row) {
                return $row->course->course_name ?? '';
            })
            ->addColumn('counsellor_group_name', function ($row) {
                return $row->counsellor_group_name ?? '';
            })
            ->addColumn('faculty_name', function ($row) {
                return $row->faculty->full_name ?? '-';
            })
            ->addColumn('action', function ($row) {
                $id = encrypt($row->pk);
                $csrf = csrf_token();

                $editUrl = route('counsellor.group.edit', ['id' => $id]);
                $deleteUrl = route('counsellor.group.delete', ['id' => $id]);

                return '
                    <a href="'.$editUrl.'" class="btn btn-primary btn-sm">Edit</a>
                    <form action="'.$deleteUrl.'" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this record?\')">
                        <input type="hidden" name="_token" value="'.$csrf.'">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>    
                ';
            })
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return "
                <div class='form-check form-switch d-inline-block'>
                    <input class='form-check-input status-toggle' type='checkbox' role='switch'
                        data-table='counsellor_group'
                        data-column='active_inactive'
                        data-id='{$row->pk}' {$checked}>
                </div>
                ";
            })
            ->filterColumn('course_name', function ($query, $keyword) {
                $query->whereHas('course', function ($q) use ($keyword) {
                    $q->where('course_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('faculty_name', function ($query, $keyword) {
                $query->whereHas('faculty', function ($q) use ($keyword) {
                    $q->where('full_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('counsellor_group_name', function ($query, $keyword) {
                $query->where('counsellor_group_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['course_name', 'counsellor_group_name', 'faculty_name', 'action', 'status']);
    }

    public function query(CounsellorGroup $model): QueryBuilder
    {
        return $model->newQuery()
                ->with(['course', 'faculty'])
                ->orderBy('pk', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('counsellor-group-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1)
            ->responsive(true)
            ->selectStyleSingle()
            ->parameters([
                'responsive' => true,
                'scrollX' => true,
                'autoWidth' => false,
                'order' => [],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center'),
            Column::make('course_name')
                ->title('Course Name')
                ->addClass('text-center')
                ->searchable(false)
                ->orderable(false),
            Column::make('counsellor_group_name')
                ->title('Counsellor Group Name')
                ->addClass('text-center')
                ->searchable(true),
            Column::make('faculty_name')
                ->title('Faculty Name')
                ->addClass('text-center')
                ->searchable(false)
                ->orderable(false),
            Column::computed('action')
                ->addClass('text-center')
                ->exportable(false)
                ->printable(false),
            Column::computed('status')
                ->addClass('text-center')
                ->exportable(false)
                ->printable(false)
        ];
    }

    protected function filename(): string
    {
        return 'CounsellorGroup_' . date('YmdHis');
    }
}

