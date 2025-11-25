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
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');
                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->whereHas('studentMaster', function ($q) use ($searchValue) {
                            $q->where('display_name', 'like', "%{$searchValue}%");
                        })
                        ->orWhereHas('courseMaster', function ($q) use ($searchValue) {
                            $q->where('course_name', 'like', "%{$searchValue}%");
                        })
                        ->orWhereHas('mdoDutyTypeMaster', function ($q) use ($searchValue) {
                            $q->where('mdo_duty_type_name', 'like', "%{$searchValue}%");
                        })
                        ->orWhere('Remark', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
->addColumn('actions', function ($row) {
    return '
        <a href="javascript:void(0)"
           class="openMdoExemptionModal"
           data-id="' . $row->pk . '"
           data-bs-toggle="tooltip"
           data-bs-placement="top"
           data-bs-animation="true"
           title="Edit">
            <iconify-icon icon="solar:pen-bold" class="fs-5 text-primary"></iconify-icon>
        </a>
        <a href="javascript:void(0)"
           class="deleteMdoExemption"
           data-id="' . $row->pk . '"
           data-bs-toggle="tooltip"
           data-bs-placement="top"
           data-bs-animation="true"
           title="Delete">
            <iconify-icon icon="solar:trash-bin-trash-bold" class="fs-5 text-danger ms-2"></iconify-icon>
        </a>
    ';
})

->rawColumns(['student_name', 'course_name', 'mdo_name', 'actions']);
    }

    public function query(): QueryBuilder
    {
        $statusFilter = request('status_filter', 'active');
        $courseFilter = request('course_filter');
        $currentDate = Carbon::now()->format('Y-m-d');
        
        $query = MDOEscotDutyMap::with([
            'courseMaster' => fn($q) => $q->select('pk', 'course_name', 'end_date'),
            'mdoDutyTypeMaster' => fn($q) => $q->select('pk', 'mdo_duty_type_name'),
            'studentMaster' => fn($q) => $q->select('pk', 'display_name')
        ]);
        
        // Filter by course status (active/archive) based on course end_date
        if ($statusFilter === 'active' || empty($statusFilter)) {
            $query->whereHas('courseMaster', function ($courseQuery) use ($currentDate) {
                $courseQuery->where(function ($q) use ($currentDate) {
                    $q->whereNull('end_date')
                      ->orWhereDate('end_date', '>=', $currentDate);
                });
            });
        } elseif ($statusFilter === 'archive') {
            $query->whereHas('courseMaster', function ($courseQuery) use ($currentDate) {
                $courseQuery->whereNotNull('end_date')
                    ->whereDate('end_date', '<', $currentDate);
            });
        }
        
        // Filter by specific course
        if (!empty($courseFilter)) {
            $query->where('course_master_pk', $courseFilter);
        }
        
        return $query->orderBy('pk', 'desc')->newQuery();
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('mdoescot-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->responsive(true)
            ->parameters([
    'ordering' => false,
    'paging' => true,
    'info' => true,
    'searching' => true,
    'lengthChange' => true,
    'pageLength' => 10,

    'dom' => '<"row mb-3"
                <"col-sm-12"l>
              >
              <"table-responsive"rt>
              <"row mt-3"
                <"col-sm-6"i>
                <"col-sm-6 text-end"p>
              >',

    'language' => [
        'paginate' => [
            'previous' => '<i class="bi bi-chevron-left"></i>',
            'next'     => '<i class="bi bi-chevron-right"></i>',
        ],
        'lengthMenu' => 'Show _MENU_ entries',
        'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
        'search' => '',
        'searchPlaceholder' => 'Search...',
    ],
])

            ->buttons(['excel', 'csv', 'pdf', 'print', 'reset', 'reload']);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('mdo_date')->title('Date')->orderable(false)->searchable(false),
            Column::make('student_name')->title('Student Name')->addClass('text-center')->orderable(false)->searchable(true),
            Column::make('Time_from')->title('Time From')->orderable(false)->searchable(false)->addClass('text-center'),
            Column::make('Time_to')->title('Time To')->orderable(false)->searchable(false)->addClass('text-center'),
            Column::make('course_name')->title('Programme Name')->addClass('text-center')->searchable(false)->orderable(false),
            Column::make('mdo_name')->title('MDO Name')->addClass('text-center')->searchable(false)->orderable(false),
            Column::make('Remark')->title('Remarks')->addClass('text-center')->searchable(false)->orderable(false),
            Column::computed('actions')->title('Actions')->addClass('text-center')->orderable(false)->searchable(false),
        ];

    }

    protected function filename(): string
    {
        return 'MDOEscotDuty_' . date('YmdHis');
    }
}
