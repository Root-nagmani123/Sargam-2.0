<?php
namespace App\DataTables;

use App\Models\GroupTypeMasterCourseMasterMap;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class GroupMappingDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('course_name', function ($row) {
                return $row->courseGroup->course_name ?? '';
            })
            ->addColumn('type_name', function ($row) {
                return $row->courseGroupType->type_name ?? '';
            })
            ->addColumn('group_name', function ($row) {
                return $row->group_name ?? '';
            })
            ->addColumn('Faculty', function ($row) {
                return $row->Faculty->full_name ?? '-';
            })
            ->addColumn('student_count', fn($row) => $row->student_course_group_map_count ?? '-')
            ->addColumn('view_download', function ($row) {
                $id = encrypt($row->pk);

                if (!empty($row->student_course_group_map_count) && $row->student_course_group_map_count > 0) {
                    $exportUrl = route('group.mapping.export.student.list', $id);

                    $html = <<<HTML
<div class="d-inline-flex align-items-center gap-2">
    <button
        type="button"
        class="btn btn-sm btn-outline-primary rounded-1 d-inline-flex align-items-center gap-1 view-student"
        data-id="{$id}"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        title="View students in this group"
        aria-label="View students in this group"
    >
        <span class="material-icons material-symbols-rounded fs-6" aria-hidden="true">visibility</span>
    </button>

    <a
        href="{$exportUrl}"
        class="btn btn-sm btn-outline-success rounded-1 d-inline-flex align-items-center gap-1"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        title="Download student list (Excel)"
        aria-label="Download student list (Excel)"
    >
        <span class="material-icons material-symbols-rounded fs-6" aria-hidden="true">download</span>
    </a>
</div>
HTML;

                    return $html;
                }

                return "<span class='badge bg-secondary-subtle text-secondary-emphasis rounded-1 px-3 py-1'>No students</span>";
            })
            ->addColumn('action', function ($row) {
                $id       = encrypt($row->pk);
                $editUrl  = route('group.mapping.edit', ['id' => $id]);
                $deleteUrl = route('group.mapping.delete', ['id' => $id]);
                $isActive = (int) $row->active_inactive === 1;
                $csrf     = csrf_token();

                $html = <<<HTML
<div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Row actions">
    <a
        href="{$editUrl}"
        class="btn btn-sm btn-outline-primary rounded-1 d-inline-flex align-items-center gap-1"
        aria-label="Edit group mapping"
    >
        <span class="material-icons material-symbols-rounded fs-6" aria-hidden="true">edit</span>
    </a>

    <?php if (!\$isActive): ?>
        <form action="{$deleteUrl}" method="POST" class="d-inline m-0 p-0">
            <input type="hidden" name="_token" value="{$csrf}">
            <input type="hidden" name="_method" value="DELETE">

            <button
                type="submit"
                class="btn btn-sm btn-outline-danger rounded-1 d-inline-flex align-items-center gap-1"
                aria-label="Delete group mapping"
                onclick="return confirm('Are you sure you want to delete this group name mapping?');"
            >
                <span class="material-icons material-symbols-rounded fs-6" aria-hidden="true">delete</span>
            </button>
        </form>
    <?php else: ?>
        <button
            type="button"
            class="btn btn-sm btn-outline-secondary rounded-1 d-inline-flex align-items-center gap-1"
            disabled
            aria-disabled="true"
            title="Cannot delete an active group mapping"
        >
            <span class="material-icons material-symbols-rounded fs-6" aria-hidden="true">lock</span>
        </button>
    <?php endif; ?>
</div>
HTML;

                return $html;
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
            ->filterColumn('course_name', function ($query, $keyword) {
                $query->whereHas('courseGroup', function ($q) use ($keyword) {
                    $q->where('course_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('type_name', function ($query, $keyword) {
                $query->whereHas('courseGroupType', function ($q) use ($keyword) {
                    $q->where('type_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('group_name', function ($query, $keyword) {

                $query->where('group_name', 'like', "%{$keyword}%");
            })

           ->filter(function ($query) {

    $searchValue = request('search.value');

    if (!empty($searchValue)) {

        $query->where(function ($q) use ($searchValue) {

            $q->where('group_name', 'like', "%{$searchValue}%")

            ->orWhereHas('courseGroup', function ($cq) use ($searchValue) {
                $cq->where('course_name', 'like', "%{$searchValue}%");
            })

            ->orWhereHas('courseGroupType', function ($tq) use ($searchValue) {
                $tq->where('type_name', 'like', "%{$searchValue}%");
            })

            ->orWhereHas('Faculty', function ($fq) use ($searchValue) {
                $fq->where('full_name', 'like', "%{$searchValue}%");
            });

        });
    }
})

        ->rawColumns(['course_name', 'group_name', 'type_name', 'view_download', 'action', 'status']);
    }

    public function query(GroupTypeMasterCourseMasterMap $model): QueryBuilder
    {
        $statusFilter = request('status_filter', 'active');
        $courseFilter = request('course_filter');
        $groupTypeFilter = request('group_type_filter');
        $currentDate      = now()->toDateString();

        // Check if any filter is explicitly set
        /*$hasAnyFilter = !empty($statusFilter) || !empty($courseFilter) || !empty($groupTypeFilter);

        // If no filters are applied, show active courses by default
        if (!$hasAnyFilter) {
            $statusFilter = 'active'; // Set default to active
        }
            */

        $data_course_id =  get_Role_by_course();

        $query = $model->newQuery()
                ->withCount('studentCourseGroupMap')
                ->with(['courseGroup', 'courseGroupType', 'Faculty'])

         /*
        |--------------------------------------------------------------------------
        | Always exclude inactive master courses
        |--------------------------------------------------------------------------
        */
        ->whereHas('courseGroup', function ($q) {
            $q->where('active_inactive', 1);
        })

        ->when($statusFilter === 'active', function ($query) use ($currentDate) {
            $query->whereHas('courseGroup', function ($q) use ($currentDate) {
                $q->where(function ($sub) use ($currentDate) {
                    $sub->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $currentDate);
                });
            });
            })

            //By Dhananjay

            ->when($statusFilter === 'archive', function ($query) use ($currentDate) {
            $query->whereHas('courseGroup', function ($q) use ($currentDate) {
                $q->whereNotNull('end_date')
                  ->whereDate('end_date', '<', $currentDate);
            });
             })

              /*
        |--------------------------------------------------------------------------
        | Course Dropdown Filter
        |--------------------------------------------------------------------------
        */

        ->when(!empty($data_course_id), function ($query) use ($data_course_id) {
            $query->whereHas('courseGroup', function ($courseQuery) use ($data_course_id) {
                $courseQuery->whereIn('pk', $data_course_id);
            });
        })
            //By Dhananjay
        ->when(!empty($courseFilter), function ($query) use ($courseFilter) {
                $query->whereHas('courseGroup', function ($q) use ($courseFilter) {
                    $q->where('pk', $courseFilter);
                });
            })

           ->when(!empty($groupTypeFilter), function ($query) use ($groupTypeFilter) {
            $query->where('type_name', $groupTypeFilter);
        })
        ->orderBy('pk', 'desc');

        return $query;
    }

public function html(): HtmlBuilder
{
    return $this->builder()
       ->setTableId('group-mapping-table')
        ->setTableAttribute('id', 'group-mapping-table')
        ->columns($this->getColumns())
        ->minifiedAjax()
        ->orderBy(1)
        ->responsive(false)
        ->selectStyleSingle()
        ->addTableClass('table table-bordered table-hover align-middle text-nowrap')
        ->parameters([
            'responsive' => false,
            'scrollX' => true,
            'scrollCollapse' => true,
            'autoWidth' => false,
            'ordering' => false,
            'searching' => true,
            'lengthChange' => true,
            'pageLength' => 10,
            'order' => [],
            'pagingType' => 'simple_numbers', // Bootstrap 5 pagination style
            'language' => [
                'paginate' => [
                    'previous' => '&laquo;',
                    'next' => '&raquo;',
                ]
            ]
        ]);
}



    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center'),
            Column::make('course_name')
                ->title('Course Name')
                ->addClass('text-center')
                ->searchable(true)
                ->orderable(false),
            Column::make('type_name')
                ->title('Course group type name')
                ->addClass('text-center')
                ->searchable(false)
                ->orderable(false),
            Column::make('group_name')
                ->title('Group Name')
                ->addClass('text-center')
                ->searchable(true),
            Column::make('Faculty')
                ->title('Faculty')
                ->addClass('text-center')
                ->searchable(false)
                ->orderable(false),
            Column::computed('student_count')
                ->title('Student Count')
                ->addClass('text-center')
                ->searchable(false)
                ->orderable(false),
            Column::computed('view_download')
                ->title('View/Download')
                ->addClass('text-center')
                ->searchable(false)
                ->orderable(false)
                ->exportable(false)
                ->printable(false),
            Column::computed('status')
                ->addClass('text-center')
                ->exportable(false)
                ->printable(false),
            Column::computed('action')
                ->addClass('text-center')
                ->exportable(false)
                ->printable(false)
        ];
    }

    protected function filename(): string
    {
        return 'GroupTypeMaster_' . date('YmdHis');
    }
}
