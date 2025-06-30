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
            ->rawColumns(['student_name', 'hostel_room_name']);
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
            Column::make('user_name')->title('User Name')->addClass('text-center')->orderable(false),
            Column::make('hostel_room_name')->title('Hostel Room Name')->addClass('text-center')->orderable(false),
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
