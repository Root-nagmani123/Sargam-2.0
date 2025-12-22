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
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->whereHas('studentMaster', function ($studentQuery) use ($searchValue) {
                            $studentQuery->where('display_name', 'like', "%{$searchValue}%");
                        })
                        ->orWhereHas('courseMaster', function ($courseQuery) use ($searchValue) {
                            $courseQuery->where('course_name', 'like', "%{$searchValue}%");
                        })
                        ->orWhereHas('mdoDutyTypeMaster', function ($mdoQuery) use ($searchValue) {
                            $mdoQuery->where('mdo_duty_type_name', 'like', "%{$searchValue}%");
                        })
                        ->orWhere('Remark', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            ->addColumn('actions', function ($row) {
                $editUrl = route('mdo-escrot-exemption.edit', $row->pk);
                $deleteUrl = route('mdo-escrot-exemption.destroy', $row->pk);
                $csrf = csrf_token();
                $formId = 'delete-form-' . $row->pk;

                return <<<HTML
<div class="dropdown text-center">
    <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions">
        <span class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">more_horiz</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        <li>
            <a class="dropdown-item d-flex align-items-center" href="{$editUrl}">
                <span class="material-icons menu-icon material-symbols-rounded me-2" style="font-size: 20px;">edit</span>
                Edit
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <form id="{$formId}" action="{$deleteUrl}" method="POST" class="d-inline">
                <input type="hidden" name="_token" value="{$csrf}">
                <input type="hidden" name="_method" value="DELETE">
                <a href="#" class="dropdown-item d-flex align-items-center text-danger" onclick="event.preventDefault(); if(confirm('Are you sure you want to delete this record?')) document.getElementById('{$formId}').submit();">
                    <span class="material-icons menu-icon material-symbols-rounded me-2" style="font-size: 20px;">delete</span>
                    Delete
                </a>
            </form>
        </li>
    </ul>
</div>
HTML;
            })
            ->rawColumns(['student_name', 'course_name', 'mdo_name', 'actions']);
    }

    public function query(): QueryBuilder
    {
        $query = MDOEscotDutyMap::with([
            'courseMaster' => fn($q) => $q->select('pk', 'course_name', 'end_date'),
            'mdoDutyTypeMaster' => fn($q) => $q->select('pk', 'mdo_duty_type_name'),
            'studentMaster' => fn($q) => $q->select('pk', 'display_name')
        ])->orderBy('pk', 'desc')->newQuery();

        // Filter by course status (Active/Archive)
        $filter = request('filter', 'active'); // Default to 'active'
        $currentDate = now()->format('Y-m-d');
        
        if ($filter === 'active') {
            // Active Courses: end_date > current date
            $query->whereHas('courseMaster', function($q) use ($currentDate) {
                $q->where('end_date', '>', $currentDate);
            });
        } elseif ($filter === 'archive') {
            // Archive Courses: end_date < current date
            $query->whereHas('courseMaster', function($q) use ($currentDate) {
                $q->where('end_date', '<', $currentDate);
            });
        }

        // Apply course filter if provided
        if ($courseFilter = request('course_filter')) {
            $query->where('course_master_pk', $courseFilter);
        }

        // Apply year filter if provided
        if ($yearFilter = request('year_filter')) {
            $query->whereYear('mdo_date', $yearFilter);
        }

        // Apply time from filter if provided
        if ($timeFromFilter = request('time_from_filter')) {
            $query->where('Time_from', '>=', $timeFromFilter);
        }

        // Apply time to filter if provided
        if ($timeToFilter = request('time_to_filter')) {
            $query->where('Time_to', '<=', $timeToFilter);
        }

        // Apply duty type filter if provided
        if ($dutyTypeFilter = request('duty_type_filter')) {
            $query->where('mdo_duty_type_master_pk', $dutyTypeFilter);
        }

        // Apply date filter if provided
        $dateFilter = request('date_filter');
        if ($dateFilter === 'today') {
            // Show records where mdo_date is today
            $query->whereDate('mdo_date', $currentDate);
        }

        return $query;
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
            'searching' => true,
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
            Column::make('student_name')->title('Student Name')->addClass('text-center')->orderable(false)->searchable(true),
            Column::make('Time_from')->title('Time From')->orderable(false)->searchable(false)->addClass('text-center'),
            Column::make('Time_to')->title('Time To')->orderable(false)->searchable(false)->addClass('text-center'),
            Column::make('course_name')->title('Programme Name')->addClass('text-center')->searchable(true)->orderable(false),
            Column::make('mdo_name')->title('Duty type')->addClass('text-center')->searchable(true)->orderable(false),
            Column::make('Remark')->title('Remarks')->addClass('text-center')->searchable(true)->orderable(false),
            Column::computed('actions')->title('Actions')->addClass('text-center')->orderable(false),
        ];

    }

    protected function filename(): string
    {
        return 'MDOEscotDuty_' . date('YmdHis');
    }
}
