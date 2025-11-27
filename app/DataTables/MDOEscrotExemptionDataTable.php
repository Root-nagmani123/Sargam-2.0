<?php

namespace App\DataTables;

use App\Models\MDOEscotDutyMap;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;
class MDOEscrotExemptionDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('mdo_date', fn($row) => format_date($row->mdo_date) ?? 'N/A')
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
            ->addColumn('actions', function ($row) {
                $editUrl = route('mdo-escrot-exemption.edit', $row->pk);
                $deleteUrl = route('mdo-escrot-exemption.destroy', $row->pk);
                return '
                    <a href="' . $editUrl . '" title="Edit">
                         <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">edit</i>
                    </a>
                    <form action="' . $deleteUrl . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this record?\')">
                        ' . csrf_field() . '
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-link p-0 m-0" title="Delete" style="vertical-align: baseline;">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">delete</i>
                        </button>
                    </form>
                ';
            })
            ->rawColumns(['student_name', 'course_name', 'mdo_name', 'actions']);
    }

    public function query(): QueryBuilder
    {
        return MDOEscotDutyMap::with([
            'courseMaster' => fn($q) => $q->select('pk', 'course_name'),
            'mdoDutyTypeMaster' => fn($q) => $q->select('pk', 'mdo_duty_type_name'),
            'studentMaster' => fn($q) => $q->select('pk', 'display_name')
        ])->orderBy('pk', 'desc')->newQuery();

    }

public function html(): HtmlBuilder
{
    return $this->builder()
        ->setTableId('mdoescot-table')
        ->addTableClass('table')
        ->columns($this->getColumns())
        ->minifiedAjax()
        ->parameters([
            'responsive' => false,
            'autoWidth' => false,
            'ordering' => false,
            'searching' => false,
            'lengthChange' => true,
            'pageLength' => 10,
            'language' => [
                'paginate' => [
                    'previous' => ' <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">chevron_left</i>',
                    'next' => '<i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">chevron_right</i>'
                ]
            ],
        ]);
}

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('mdo_date')->title('Date')->orderable(false)->searchable(false),
            Column::make('student_name')->title('Student Name')->addClass('text-center')->orderable(false),
            Column::make('Time_from')->title('Time From')->orderable(false)->searchable(false)->addClass('text-center'),
            Column::make('Time_to')->title('Time To')->orderable(false)->searchable(false)->addClass('text-center'),
            Column::make('course_name')->title('Programme Name')->addClass('text-center')->searchable(false)->orderable(false),
            Column::make('mdo_name')->title('MDO Name')->addClass('text-center')->searchable(false)->orderable(false),
            Column::make('Remark')->title('Remarks')->addClass('text-center')->searchable(false)->orderable(false),
            Column::computed('actions')->title('Actions')->addClass('text-center')->orderable(false),
        ];

    }

    protected function filename(): string
    {
        return 'MDOEscotDuty_' . date('YmdHis');
    }
}
