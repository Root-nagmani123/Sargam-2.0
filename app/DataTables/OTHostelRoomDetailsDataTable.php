<?php

namespace App\DataTables;

use App\Models\OTHostelRoomDetails;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class OTHostelRoomDetailsDataTable extends DataTable
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
            ->addColumn('student_name', function ($row) {
                return $row->user_name;
            })
            ->addColumn('hostel_room_name', function ($row) {
                return $row->hostel_room_name;
            })
            ->addColumn('course_name', function ($row) {
                return optional($row->course)->course_name;
            })
            ->filterColumn('course_name', function ($query, $keyword) {
                $query->whereHas('course', function ($q) use ($keyword) {
                    $q->where('course_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('user_name', function ($query, $keyword) {
                $query->where('user_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('hostel_room_name', function ($query, $keyword) {
                $query->where('hostel_room_name', 'like', "%{$keyword}%");
            })
            ->addColumn('action', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block ms-2">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                data-table="ot_hostel_room_details" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
                        </div>';
            })
            ->rawColumns(['student_name', 'hostel_room_name', 'course_name', 'action']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\OTHostelRoomDetail $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(OTHostelRoomDetails $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('othostelroomdetails-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            //->dom('Bfrtip')
            ->orderBy(1)
            ->selectStyleSingle()
            ->parameters([
                'order' => [],
                // 'initComplete' => 'function(settings, json) {
                //     var api = this.api();

                //     // Room types from PHP
                //     var roomTypes = ' . $roomTypes . ';

                //     // Filter HTML
                //     var filterHtml = `
                //         <div class="row mb-3">
                //             <div class="col-md-3">
                //                 <label class="form-label fw-bold">Filter by Course</label>
                //                 <select id="courseFilter" class="form-select form-select-sm">
                //                     <option value="">All Courses</option>
                //                 </select>
                //             </div>
                //             <div class="col-md-3">
                //                 <label class="form-label fw-bold">Filter by Hostel Room</label>
                //                 <select id="roomFilter" class="form-select form-select-sm">
                //                     <option value="">All Rooms</option>
                //                 </select>
                //             </div>
                //             <div class="col-md-3 d-flex align-items-end">
                //                 <button class="btn btn-secondary btn-sm" id="resetFilters">Reset Filters</button>
                //             </div>
                //         </div>
                //     `;

                //     $("#othostelroomdetails-table_wrapper").prepend(filterHtml);

                //     // Populate Course Filter dynamically
                //     $.ajax({
                //         url: "' . route('api.get.courses') . '",
                //         type: "GET",
                //         success: function(data) {
                //             $.each(data, function(i, item) {
                //                 $("#courseFilter").append("<option value=\'" + item.pk + "\'>" + item.course_name + "</option>");
                //             });
                //         }
                //     });

                //     // Populate Room Filter dynamically
                //     $.ajax({
                //         url: "' . route('api.get.rooms') . '",
                //         type: "GET",
                //         success: function(data) {
                //             $.each(data, function(i, item) {
                //                 $("#roomFilter").append("<option value=\'" + item.room_name + "\'>" + item.room_name + "</option>");
                //             });
                //         }
                //     });

                //     // Apply filters (reload server-side)
                //     $(document).on("change", "#courseFilter, #roomFilter", function() {
                //         api.ajax.reload();
                //     });

                //     // Reset filters
                //     $(document).on("click", "#resetFilters", function() {
                //         $("#courseFilter").val("");
                //         $("#roomFilter").val("");
                //         api.ajax.reload();
                //     });
                // }',


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
            Column::computed('DT_RowIndex')->title('#')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('course_name')->title('Course Name')->addClass('text-center')->orderable(false),
            Column::make('user_name')->title('Student Name')->addClass('text-center')->orderable(false),
            Column::make('hostel_room_name')->title('Hostel Room Name')->addClass('text-center')->orderable(false),
            Column::make('action')->title('Action')->addClass('text-center')->orderable(false),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'OTHostelRoomDetails_' . date('YmdHis');
    }
}
