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
            ->editColumn('ot_code', fn($row) => $row->studentMaster->generated_OT_code ?? 'N/A')
            ->editColumn('Time_from', fn($row) => $row->Time_from ?? 'N/A')
            ->editColumn('Time_to', fn($row) => $row->Time_to ?? 'N/A')
            ->editColumn('course_name', fn($row) => optional($row->courseMaster)->course_name ?? 'N/A')
            ->editColumn('mdo_name', fn($row) => optional($row->mdoDutyTypeMaster)->mdo_duty_type_name ?? 'N/A')
            ->editColumn('faculty_name', fn($row) => optional($row->facultyMaster)->full_name ?? 'N/A')
            ->editColumn('Remark', fn($row) => $row->Remark ?? 'N/A')
            ->filterColumn('student_name', function ($query, $keyword) {
                $query->whereHas('studentMaster', function ($q) use ($keyword) {
                    $q->where('display_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('ot_code', function ($query, $keyword) {
                $query->whereHas('studentMaster', function ($q) use ($keyword) {
                    $q->where('generated_OT_code', 'like', "%{$keyword}%");
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
            ->filterColumn('faculty_name', function ($query, $keyword) {
                $query->whereHas('facultyMaster', function ($q) use ($keyword) {
                    $q->where('full_name', 'like', "%{$keyword}%");
                });
            })
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->whereHas('studentMaster', function ($studentQuery) use ($searchValue) {
                            $studentQuery->where('display_name', 'like', "%{$searchValue}%")
                                         ->orWhere('generated_OT_code', 'like', "%{$searchValue}%");
                        })
                        ->orWhereHas('courseMaster', function ($courseQuery) use ($searchValue) {
                            $courseQuery->where('course_name', 'like', "%{$searchValue}%");
                        })
                        ->orWhereHas('mdoDutyTypeMaster', function ($mdoQuery) use ($searchValue) {
                            $mdoQuery->where('mdo_duty_type_name', 'like', "%{$searchValue}%");
                        })
                        ->orWhereHas('facultyMaster', function ($facultyQuery) use ($searchValue) {
                            $facultyQuery->where('full_name', 'like', "%{$searchValue}%");
                        })
                        ->orWhere('Remark', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            ->addColumn('actions', function ($row) {
                $encryptedPk = encrypt($row->pk);
                $dutyTypePk  = $row->mdo_duty_type_master_pk ?? '';
                $facultyPk   = $row->faculty_master_pk ?? '';
                $mdoDate     = $row->mdo_date ? \Carbon\Carbon::parse($row->mdo_date)->format('Y-m-d') : '';
                $timeFrom    = $row->Time_from ? substr($row->Time_from, 0, 5) : '';
                $timeTo      = $row->Time_to   ? substr($row->Time_to, 0, 5)   : '';
                $studentName = htmlspecialchars($row->studentMaster->display_name ?? '', ENT_QUOTES, 'UTF-8');
                $deleteUrl   = route('mdo-escrot-exemption.destroy', $row->pk);
                $csrf        = csrf_token();
                $formId      = 'delete-form-' . $row->pk;

                return <<<HTML
<div class="d-flex justify-content-start align-items-start gap-2"
     role="group"
     aria-label="Row actions">

    <!-- Edit (opens modal) -->
    <button type="button"
            class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 px-2 border-0 p-0 bg-transparent text-primary"
            data-bs-toggle="modal"
            data-bs-target="#editMdoModal"
            data-pk="{$encryptedPk}"
            data-duty-type="{$dutyTypePk}"
            data-faculty="{$facultyPk}"
            data-date="{$mdoDate}"
            data-time-from="{$timeFrom}"
            data-time-to="{$timeTo}"
            data-student="{$studentName}"
            aria-label="Edit record">
        <span class="material-icons material-symbols-rounded"
              style="font-size:20px;"
              aria-hidden="true">edit</span>
    </button>

    <!-- Delete -->
    <form id="{$formId}" action="{$deleteUrl}" method="POST" class="d-inline">
        <input type="hidden" name="_token" value="{$csrf}">
        <input type="hidden" name="_method" value="DELETE">

        <button type="submit"
                class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1 px-2 border-0 p-0 bg-transparent text-primary"
                aria-label="Delete record"
                onclick="return confirm('Are you sure you want to delete this record?');">
            <span class="material-icons material-symbols-rounded"
                  style="font-size:20px;"
                  aria-hidden="true">delete</span>
        </button>
    </form>

</div>

HTML;
            })
            ->rawColumns(['student_name', 'ot_code', 'course_name', 'mdo_name', 'faculty_name', 'actions']);
    }

    public function query(): QueryBuilder
    {
        $query = MDOEscotDutyMap::with([
            'courseMaster' => fn($q) => $q->select('pk', 'course_name', 'end_date'),
            'mdoDutyTypeMaster' => fn($q) => $q->select('pk', 'mdo_duty_type_name'),
            'studentMaster' => fn($q) => $q->select('pk', 'display_name', 'generated_OT_code'),
            'facultyMaster' => fn($q) => $q->select('pk', 'full_name')
        ])->orderBy('created_date', 'desc')->newQuery();

        // Filter by course status (Active/Archive)
        $filter = request('filter', 'active'); // Default to 'active'
        $currentDate = now()->format('Y-m-d');
        
        $data_course_id =  get_Role_by_course();
            if(!empty($data_course_id))
            {
                $query->whereIn('course_master_pk',$data_course_id);
            }
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

        // Apply date range filter if provided
        $fromDateFilter = request('from_date_filter');
        $toDateFilter = request('to_date_filter');
        
        if ($fromDateFilter) {
            $query->whereDate('mdo_date', '>=', $fromDateFilter);
        }
        
        if ($toDateFilter) {
            $query->whereDate('mdo_date', '<=', $toDateFilter);
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
            'pagingType' => 'full_numbers',
            'ordering' => false,
            'searching' => true,
            'lengthChange' => true,
            'pageLength' => 10,
            'language' => [
                'paginate' => ['first' => '', 'last' => '', 'next' => '', 'previous' => '']
            ],
        ]);
}

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->orderable(false)->searchable(false),
            Column::make('mdo_date')->title('Date')->orderable(false)->searchable(false),
            Column::make('student_name')->title('Student Name')->orderable(false)->searchable(true),
            Column::make('ot_code')->title('OT Code')->orderable(false)->searchable(true),
            Column::make('Time_from')->title('Time From')->orderable(false)->searchable(false),
            Column::make('Time_to')->title('Time To')->orderable(false)->searchable(false),
            Column::make('course_name')->title('Programme Name')->searchable(true)->orderable(false),
            Column::make('mdo_name')->title('Duty type')->searchable(true)->orderable(false),
            Column::make('faculty_name')->title('Faculty Name')->searchable(true)->orderable(false),
            Column::make('Remark')->title('Remarks')->searchable(true)->orderable(false),
            Column::computed('actions')->title('Actions')->orderable(false),
        ];

    }

    protected function filename(): string
    {
        return 'MDOEscotDuty_' . date('YmdHis');
    }
}
