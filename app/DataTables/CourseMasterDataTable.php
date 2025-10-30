<?php

namespace App\DataTables;

use App\Models\CourseMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class CourseMasterDataTable extends DataTable
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
            ->addColumn('course_name', fn($row) => $row->course_name)
            ->addColumn('couse_short_name', fn($row) => $row->couse_short_name)
            ->addColumn('course_year', fn($row) => $row->course_year)
            ->addColumn('start_year', function ($row) {
                return $row->start_year ? Carbon::parse($row->start_year)->format('Y-m-d') : '';
            })
            ->addColumn('end_date', function ($row) {
                return $row->end_date ? Carbon::parse($row->end_date)->format('Y-m-d') : '';
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('programme.edit', ['id' => encrypt($row->pk)]);
                $viewUrl = route('programme.show', ['id' => encrypt($row->pk)]);
                $encryptedId = encrypt($row->pk);
                return '<a href="'.$editUrl.'" class="btn btn-primary btn-sm me-1">Edit</a>
                        <a href="'.$viewUrl.'" class="btn btn-info btn-sm me-1" target="_blank">View</a>
                        <!-- <button type="button" class="btn btn-secondary btn-sm view-course-btn" data-id="'.$encryptedId.'" data-bs-toggle="modal" data-bs-target="#viewCourseModal">Quick View</button> -->';
            })
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '
                <div class="form-check form-switch d-inline-block">
                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                        data-table="course_master" data-column="active_inactive" data-id="'.$row->pk.'" '.$checked.'>
                </div>';
            })
            ->filterColumn('course_name', function ($query, $keyword) {
                $query->where('course_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('couse_short_name', function ($query, $keyword) {
                $query->where('couse_short_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('course_year', function ($query, $keyword) {
                $query->where('course_year', 'like', "%{$keyword}%");
            })
            ->filterColumn('start_year', function ($query, $keyword) {
                $query->where('start_year', 'like', "%{$keyword}%");
            })
            ->filterColumn('end_date', function ($query, $keyword) {
                $query->where('end_date', 'like', "%{$keyword}%");
            })
            ->rawColumns(['action', 'status'])
            ->setRowId('pk');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\CourseMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(CourseMaster $model): QueryBuilder
    {
        $query = $model->orderBy('pk', 'desc')->newQuery();
        
        // Apply status filter if provided
        $statusFilter = request('status_filter');
        $currentDate = Carbon::now()->format('Y-m-d');
        
        if ($statusFilter === 'active' || !$statusFilter) {
            // Active courses: current date is between start and end date (default)
            $query->where(function($q) use ($currentDate) {
                $q->where('start_year', '<=', $currentDate)
                  ->where('end_date', '>=', $currentDate);
            });
        } elseif ($statusFilter === 'archive') {
            // Archived courses: current date is before start date or after end date
            $query->where(function($q) use ($currentDate) {
                $q->where('start_year', '>', $currentDate)
                  ->orWhere('end_date', '<', $currentDate);
            });
        }
        
        return $query;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('coursemaster-table')
            ->columns($this->getColumns())
            ->minifiedAjax() // This will use the current route for ajax
            // ->orderBy(1)
            ->selectStyleSingle()
            ->responsive(true)
            ->parameters([
                'responsive' => true,
                'scrollX' => true,
                'autoWidth' => false,
                'order' => [],
            ])
            ->buttons([
                Button::make('excel'),
                Button::make('csv'),
                Button::make('pdf'),
                Button::make('print'),
                Button::make('reset'),
                Button::make('reload'),
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
            Column::computed('DT_RowIndex')->title('S.No.')->searchable(false)->orderable(false),
            Column::make('course_name')->title('Course Name')->orderable(false),
            Column::make('couse_short_name')->title('Short Name')->orderable(false),
            Column::make('course_year')->title('Course Year')->orderable(false),
            Column::make('start_year')->title('Start Date')->orderable(false),
            Column::make('end_date')->title('End Date')->orderable(false),
            Column::computed('action')->addClass('text-center'),
            Column::computed('status')->addClass('text-center'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'CourseMaster_' . date('YmdHis');
    }
}
