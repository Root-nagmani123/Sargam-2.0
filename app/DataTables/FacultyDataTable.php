<?php

namespace App\DataTables;

use App\Models\Faculty;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use App\Models\FacultyMaster;

class FacultyDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     * @return \Yajra\DataTables\EloquentDataTable
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('faculty_type', function($row) {

                return match((int)$row->faculty_type) {
                    1 => '<span class="badge bg-success">Internal</span>',
                    2 => '<span class="badge bg-warning">Guest</span>',
                    3 => '<span class="badge bg-info">Research</span>',
                    default => null,
                };
            })
            ->addColumn('full_name', function($row) {
                return $row->full_name ?? '';
            })
            ->addColumn('mobile_number', function($row) {
                return $row->mobile_no ?? '';
            })
            ->addColumn('designation', function($row) {
                return $row->designation ?? '';
            })
            ->addColumn('current_sector', function($row) {
                return match((int)$row->current_sector) {
                    1 => '<span class="badge bg-success">Government</span>',
                    default => '<span class="badge bg-danger">Private</span>',
                };
                
            })
            ->addColumn('action', function ($row) {
                $id = encrypt($row->pk);
                $csrf = csrf_token();

                $editUrl = route('faculty.edit', ['id' => $id]);
                $viewUrl = route('faculty.show', ['id' => $id]);
                // $deleteUrl = route('faculty.delete', ['id' => $id]);

                return '
                    <a href="'.$editUrl.'" class="btn btn-primary btn-sm">Edit</a>
                    <a href="'.$viewUrl.'" class="btn btn-info btn-sm">View</a>
                ';
                // <form action="'.$deleteUrl.'" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this record?\')">
                //         <input type="hidden" name="_token" value="'.$csrf.'">
                //         <input type="hidden" name="_method" value="DELETE">
                //         <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                //     </form>    
            })
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return "
                <div class='form-check form-switch d-inline-block'>
                    <input class='form-check-input status-toggle' type='checkbox' role='switch'
                        data-table='group_type_master_course_master_map'
                        data-column='active_inactive'
                        data-id='{$row->pk}' {$checked}>
                </div>
                ";
            })
            ->filterColumn('full_name', function ($query, $keyword) {
                $query->where('full_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('mobile_number', function ($query, $keyword) {
                $query->where('mobile_no', 'like', "%{$keyword}%");
            })
            ->rawColumns(['faculty_type','action', 'status', 'current_sector']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Faculty $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(FacultyMaster $model): QueryBuilder
    {
        // return $model->newQuery();
        return $model->orderBy('pk', 'desc')->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('faculty-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    //->dom('Bfrtip')
                    ->orderBy(1)
                    ->selectStyleSingle()
                    ->parameters([
                        'order' => [],
                    ])
                    ->buttons([
                        Button::make('excel'),
                        Button::make('csv'),
                        Button::make('pdf'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload')
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     *
     * @return array
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center'),
            Column::make('faculty_type')
                ->title('Faculty Type')
                ->addClass('text-center')
                ->searchable(false)
                ->orderable(false),
            Column::make('full_name')
                ->title('Full Name')
                ->addClass('text-center')
                ->searchable(true)
                ->orderable(false),
            Column::make('mobile_number')
                ->title('Mobile Number')
                ->addClass('text-center')
                ->searchable(true)
                ->orderable(false),
            Column::make('designation')
                ->title('Designation')
                ->addClass('text-center')
                ->searchable(false)
                ->orderable(false),
            Column::make('current_sector')
                ->title('Current Sector')
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

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'Faculty_' . date('YmdHis');
    }
}
