<?php
namespace App\DataTables;

use App\Models\GroupTypeMasterCourseMasterMap;
use App\Support\DataTableRedisCache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class GroupMappingDataTable extends DataTable
{
    public const LISTING_CACHE_EPOCH_KEY = 'group_mapping_dt_list_epoch';

    public static function bumpListingCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'GroupMappingDataTable');
    }

    /**
     * Server-side JSON for /group-mapping. .env: GROUP_MAPPING_DATATABLE_CACHE_*.
     */
    public function ajax(): JsonResponse
    {
        return DataTableRedisCache::serveCachedAjax(
            $this->request(),
            'group_mapping_dt:v1:',
            self::LISTING_CACHE_EPOCH_KEY,
            [
                'enabled' => 'GROUP_MAPPING_DATATABLE_CACHE_ENABLED',
                'seconds' => 'GROUP_MAPPING_DATATABLE_CACHE_SECONDS',
            ],
            'GroupMappingDataTable',
            fn () => parent::ajax(),
            [
                'status_filter' => (string) $this->request()->input('status_filter', ''),
                'course_filter' => (string) $this->request()->input('course_filter', ''),
                'group_type_filter' => (string) $this->request()->input('group_type_filter', ''),
                'faculty_filter' => (string) $this->request()->input('faculty_filter', ''),
                'role_course_ids' => get_Role_by_course(),
            ]
        );
    }

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
            ->addColumn('student_count', fn ($row) => $row->student_course_group_map_count ?? '0')
            ->addColumn('status', function ($row) {
                if ((int) $row->active_inactive === 1) {
                    return '<span class="badge rounded-1 programme-status-badge programme-status-badge--active">Active</span>';
                }

                return '<span class="badge rounded-1 programme-status-badge programme-status-badge--inactive">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                $id = encrypt($row->pk);
                $deleteUrl = route('group.mapping.delete', ['id' => $id]);
                $courseLabel = $row->courseGroup->course_name ?? '';
                $typeLabel = $row->courseGroupType->type_name ?? '';
                $facultyLabel = $row->Faculty->full_name ?? '';
                $isActive = (int) $row->active_inactive === 1;
                $checked = $isActive ? 'checked' : '';
                $csrf = csrf_token();

                $viewHtml = '<a href="javascript:void(0)" class="programme-action-btn gm-action-btn view-student" data-id="' . e($id) . '" aria-label="View students"><i class="bi bi-eye" aria-hidden="true"></i></a>';

                $downloadHtml = '<a href="' . e(route('group.mapping.export.student.list', $id)) . '" class="programme-action-btn gm-action-btn" aria-label="Download student list"><i class="bi bi-download" aria-hidden="true"></i></a>';

                $deleteHtml = $isActive
                    ? '<button type="button" class="programme-action-btn gm-action-btn programme-action-btn--danger" disabled aria-disabled="true" title="Cannot delete active group mapping"><i class="bi bi-trash" aria-hidden="true"></i></button>'
                    : '<form action="' . e($deleteUrl) . '" method="POST" class="d-inline-flex m-0 gm-delete-form">'
                        . '<input type="hidden" name="_token" value="' . e($csrf) . '">'
                        . '<input type="hidden" name="_method" value="DELETE">'
                        . '<button type="submit" class="programme-action-btn gm-action-btn programme-action-btn--danger delete-btn" aria-label="Delete group mapping" onclick="return confirm(\'Are you sure you want to delete this group name mapping?\');">'
                        . '<i class="bi bi-trash" aria-hidden="true"></i>'
                        . '</button></form>';

                $toggleHtml = '<label class="programme-action-toggle-icon mb-0" aria-label="Toggle group mapping status">'
                    . '<input class="status-toggle plain-status-toggle gm-status-toggle-input" type="checkbox" role="switch"'
                    . ' data-table="group_type_master_course_master_map" data-column="active_inactive" data-id="' . e($row->pk) . '" ' . $checked . '>'
                    . '<i class="bi bi-toggle-off gm-toggle-icon gm-toggle-icon--off" aria-hidden="true"></i>'
                    . '<i class="bi bi-toggle-on gm-toggle-icon gm-toggle-icon--on" aria-hidden="true"></i>'
                    . '</label>';

                return '<div class="d-inline-flex align-items-center justify-content-center programme-action-group gm-action-group" role="group" aria-label="Row actions">'
                    . $viewHtml
                    . '<button type="button" class="programme-action-btn gm-action-btn edit-btn gm-edit-btn" aria-label="Edit group mapping"'
                    . ' data-id="' . e($id) . '"'
                    . ' data-course-id="' . e($row->course_name) . '"'
                    . ' data-course-name="' . e($courseLabel) . '"'
                    . ' data-type-id="' . e($row->type_name) . '"'
                    . ' data-type-name="' . e($typeLabel) . '"'
                    . ' data-group-name="' . e($row->group_name ?? '') . '"'
                    . ' data-facility-id="' . e($row->facility_id ?? '') . '"'
                    . ' data-faculty-name="' . e($facultyLabel) . '">'
                    . '<i class="bi bi-pencil" aria-hidden="true"></i></button>'
                    . $toggleHtml
                    . $downloadHtml
                    . $deleteHtml
                    . '</div>';
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
$searchValue = request()->input('search.value');

if (!empty($searchValue)) {
$query->where(function ($subQuery) use ($searchValue) {

$subQuery->where('group_name', 'like', "%{$searchValue}%")

->orWhereHas('courseGroup', function ($courseQuery) use ($searchValue) {
$courseQuery->where('course_name', 'like', "%{$searchValue}%");
})

->orWhereHas('courseGroupType', function ($typeQuery) use ($searchValue) {
$typeQuery->where('type_name', 'like', "%{$searchValue}%");
})

->orWhereHas('Faculty', function ($facultyQuery) use ($searchValue) {
$facultyQuery->where('full_name', 'like', "%{$searchValue}%");
});
});
}
})

->rawColumns(['course_name', 'group_name', 'type_name', 'student_count', 'status', 'action']);
}

public function query(GroupTypeMasterCourseMasterMap $model): QueryBuilder
{
$statusFilter = request('status_filter');
$courseFilter = request('course_filter');
$groupTypeFilter = request('group_type_filter');
$facultyFilter = request('faculty_filter');
$currentDate = Carbon::now()->format('Y-m-d');

// Check if any filter is explicitly set
$hasAnyFilter = !empty($statusFilter) || !empty($courseFilter) || !empty($groupTypeFilter) || !empty($facultyFilter);

// If no filters are applied, show active courses by default
if (!$hasAnyFilter) {
$statusFilter = 'active'; // Set default to active
}

$data_course_id = get_Role_by_course();

$query = $model->newQuery()
->withCount('studentCourseGroupMap')
->with(['courseGroup', 'courseGroupType', 'Faculty'])
->when($statusFilter === 'active', function ($query) use ($currentDate) {
$query->whereHas('courseGroup', function ($courseQuery) use ($currentDate) {
$courseQuery->where(function ($q) use ($currentDate) {
$q->whereNull('end_date') // end date NULL ho (kabhi khatam nahi)
->orWhereDate('end_date', '>=', $currentDate); // ya abhi ya future me active
});
});
})

->when($statusFilter === 'archive', function ($query) use ($currentDate) {
$query->whereHas('courseGroup', function ($courseQuery) use ($currentDate) {
$courseQuery->whereNotNull('end_date')
->whereDate('end_date', '<', $currentDate); }); }) ->when(!empty($data_course_id), function ($query) use
    ($data_course_id) {
    $query->whereHas('courseGroup', function ($courseQuery) use ($data_course_id) {
    $courseQuery->whereIn('pk', $data_course_id);
    });
    })
    ->when(!empty($courseFilter), function ($query) use ($courseFilter) {
    $query->where('course_name', $courseFilter);
    })
    ->when(!empty($groupTypeFilter), function ($query) use ($groupTypeFilter) {
    $query->where('type_name', $groupTypeFilter);
    })
    ->when(!empty($facultyFilter), function ($query) use ($facultyFilter) {
    $query->where('facility_id', $facultyFilter);
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
    ->addTableClass('table table-hover align-middle mb-0 w-100 programme-dt-table')
    ->parameters([
    'responsive' => false,
    'scrollX' => false,
    'autoWidth' => false,
    'ordering' => true,
    'searching' => true,
    'lengthChange' => true,
    'pageLength' => 10,
    'lengthMenu' => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
    'order' => [],
    'dom' => 'rt<"row d-none"<"col-sm-12"ilp>>',
    'pagingType' => 'full_numbers',
    'language' => [
    'search' => '',
    'searchPlaceholder' => 'Search',
    'processing' => '<span class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></span>Loading…',
    'emptyTable' => 'No group mappings found.',
    'zeroRecords' => 'No matching group mappings found.',
    'lengthMenu' => 'Showing _MENU_',
    'info' => 'of _TOTAL_ items',
    'infoEmpty' => 'of 0 items',
    'infoFiltered' => 'of _MAX_ items',
    'paginate' => [
    'previous' => '&laquo;',
    'next' => '&raquo;',
    ],
    ],
    ]);
    }



    public function getColumns(): array
    {
    return [
    Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center text-nowrap gm-col-sno'),
    Column::make('course_name')
    ->title('Course Name')
    ->addClass('text-start gm-col-course')
    ->searchable(true)
    ->orderable(false),
    Column::make('type_name')
    ->title('Group Type')
    ->addClass('text-start gm-col-type')
    ->searchable(false)
    ->orderable(false),
    Column::make('group_name')
    ->title('Group Name')
    ->addClass('text-start gm-col-group')
    ->searchable(true),
    Column::make('Faculty')
    ->title('Faculty')
    ->addClass('text-start gm-col-faculty')
    ->searchable(false)
    ->orderable(false),
    Column::computed('student_count')
    ->title('Student Name')
    ->addClass('text-center gm-col-student')
    ->searchable(false)
    ->orderable(false),
    Column::computed('status')
    ->title('Status')
    ->addClass('text-center gm-col-status')
    ->exportable(false)
    ->printable(false),
    Column::computed('action')
    ->title('Action')
    ->addClass('text-center gm-col-action')
    ->exportable(false)
    ->printable(false)
    ];
    }

    protected function filename(): string
    {
    return 'GroupTypeMaster_' . date('YmdHis');
    }
    }