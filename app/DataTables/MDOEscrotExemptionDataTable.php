<?php

namespace App\DataTables;

use App\Models\MDOEscotDutyMap;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class MDOEscrotExemptionDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('mdo_date', fn($row) => $row->mdo_date ?? 'N/A')
            ->editColumn('student_name', fn($row) => $row->studentMaster->display_name ?? 'N/A')
            ->editColumn('Time_from', fn($row) => $row->Time_from ?? 'N/A')
            ->editColumn('Time_to', fn($row) => $row->Time_to ?? 'N/A')
            ->editColumn('course_name', fn($row) => optional($row->courseMaster)->course_name ?? 'N/A')
            ->editColumn('mdo_name', fn($row) => optional($row->mdoDutyTypeMaster)->mdo_duty_type_name ?? 'N/A')
            ->editColumn('Remark', fn($row) => $row->Remark ?? 'N/A')
            ->filterColumn('student_name', function ($query, $keyword) {
                $query->whereHas('studentMaster', function ($q) use ($keyword) {
                    $q->where('display_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('course_name', function ($query, $keyword) {
                $query->whereHas('courseMaster', function ($q) use ($keyword) {
                    $q->where('course_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('mdo_name', function ($query, $keyword) {
                $query->whereHas('mdoDutyTypeMaster', function ($q) use ($keyword) {
                    $q->where('mdo_duty_type_name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['student_name', 'course_name', 'mdo_name']);
    }

    public function query(): QueryBuilder
    {
        return MDOEscotDutyMap::with(['courseMaster', 'mdoDutyTypeMaster', 'studentMaster'])->newQuery();
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('mdoescot-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1)
            ->responsive(true)
            ->parameters([
                'responsive' => true,
                'scrollX' => true,
                'autoWidth' => false,
            ])
            ->buttons(['excel', 'csv', 'pdf', 'print', 'reset', 'reload']);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('mdo_date')->title('Date')->orderable(false)->searchable(false),
            Column::make('student_name')->title('Student Name')->addClass('text-center'),
            Column::make('Time_from')->title('Time From')->orderable(false)->searchable(false)->addClass('text-center'),
            Column::make('Time_to')->title('Time To')->orderable(false)->searchable(false)->addClass('text-center'),
            Column::make('course_name')->title('Programme Name')->addClass('text-center'),
            Column::make('mdo_name')->title('MDO Name')->addClass('text-center'),
            Column::make('Remark')->title('Remarks')->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'MDOEscotDuty_' . date('YmdHis');
    }
}
