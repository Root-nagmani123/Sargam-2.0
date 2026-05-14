<?php
namespace App\DataTables;

use App\Models\GroupTypeMasterCourseMasterMap;
use App\Support\DataTableRedisCache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

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
            ->addColumn('student_count', fn($row) => $row->student_course_group_map_count ?? '-')
            ->addColumn('action', function ($row) {
                $id = encrypt($row->pk);
                $editUrl = route('group.mapping.edit', ['id' => $id]);
                $deleteUrl = route('group.mapping.delete', ['id' => $id]);
                $exportUrl = route('group.mapping.export.student.list', $id);
                $isActive = $row->active_inactive == 1;
                $checked = $isActive ? 'checked' : '';
                $csrf = csrf_token();
                $hasStudents = !empty($row->student_course_group_map_count) && $row->student_course_group_map_count > 0;

                $s = 'display:inline-flex;align-items:center;justify-content:center;padding:0;text-decoration:none;border:none;background:transparent;cursor:pointer;';

                $viewHtml = $hasStudents
                    ? '<a href="javascript:void(0)" class="view-student" data-id="' . $id . '" title="View Students" style="' . $s . 'color:#1b3a5c;"><i class="material-icons material-symbols-rounded" style="font-size:20px;">visibility</i></a>'
                    : '';

                $editHtml = '<a href="' . $editUrl . '" title="Edit" style="' . $s . 'color:#1b3a5c;"><i class="material-icons material-symbols-rounded" style="font-size:20px;">edit</i></a>';

                $statusHtml = '<div class="form-check form-switch d-inline-flex align-items-center mb-0" style="min-height:auto;padding-left:2.5em;">'
                    . '<input class="form-check-input status-toggle" type="checkbox" role="switch" data-table="group_type_master_course_master_map" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . ' style="cursor:pointer;width:2.2em;height:1.1em;">'
                    . '</div>';

                $downloadHtml = $hasStudents
                    ? '<a href="' . $exportUrl . '" title="Download" style="' . $s . 'color:#1b3a5c;"><i class="material-icons material-symbols-rounded" style="font-size:20px;">download</i></a>'
                    : '';

                $deleteHtml = '<form action="' . $deleteUrl . '" method="POST" class="d-inline"><input type="hidden" name="_token" value="' . $csrf . '"><input type="hidden" name="_method" value="DELETE"><button type="submit" title="Delete" style="' . $s . 'color:#dc3545;" onclick="return confirm(\'Are you sure you want to delete this group mapping?\');"><i class="material-icons material-symbols-rounded" style="font-size:20px;">delete</i></button></form>';

                return '<div class="d-inline-flex align-items-center gap-3 flex-nowrap">' . $viewHtml . $editHtml . $statusHtml . $downloadHtml . $deleteHtml . '</div>';
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

->orWhereExists(function ($existsQuery) use ($searchValue) {
$existsQuery->select(DB::raw(1))
->from('course_group_type_master')
->where('type_name', 'like', "%{$searchValue}%");
});
});
}
})

->rawColumns(['course_name', 'group_name', 'type_name', 'action']);
}

public function query(GroupTypeMasterCourseMasterMap $model): QueryBuilder
{
$statusFilter = request('status_filter');
$courseFilter = request('course_filter');
$groupTypeFilter = request('group_type_filter');
$currentDate = Carbon::now()->format('Y-m-d');

// Check if any filter is explicitly set
$hasAnyFilter = !empty($statusFilter) || !empty($courseFilter) || !empty($groupTypeFilter);

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
    ->addTableClass('table table-hover align-middle custom-mapping-table')
    ->parameters([
    'responsive' => false,
    'scrollX' => false,
    'autoWidth' => false,
    'ordering' => false,
    'searching' => true,
    'lengthChange' => true,
    'pageLength' => 10,
    'lengthMenu' => [[10, 25, 50, 100, 200, 500], [10, 25, 50, 100, 200, 500]],
    'order' => [],
    'pagingType' => 'full_numbers',
    'dom' => '<"table-responsive"rt><"d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3 mt-1 border-top"p<"d-flex align-items-center gap-1 text-muted small"li>>',
    'language' => [
    'paginate' => ['first' => '&laquo;', 'last' => '&raquo;', 'next' => '&rsaquo;', 'previous' => '&lsaquo;'],
    'info' => 'of _MAX_ Items',
    'infoEmpty' => '0 Items',
    'infoFiltered' => '',
    'lengthMenu' => 'Showing _MENU_',
    ]
    ])
    ->buttons([
        Button::make('excel'),
        Button::make('pdf'),
    ]);
    }



    public function getColumns(): array
    {
    return [
    Column::computed('DT_RowIndex')->title('S. No.')->width('5%'),
    Column::make('course_name')
    ->title('Course Name')
    ->width('25%')
    ->searchable(true)
    ->orderable(false),
    Column::make('type_name')
    ->title('Group Type')
    ->width('12%')
    ->searchable(false)
    ->orderable(false),
    Column::make('group_name')
    ->title('Group Name')
    ->width('12%')
    ->searchable(true),
    Column::make('Faculty')
    ->title('Faculty')
    ->width('10%')
    ->searchable(false)
    ->orderable(false),
    Column::computed('student_count')
    ->title('Students')
    ->width('8%')
    ->searchable(false)
    ->orderable(false),
    Column::computed('action')
    ->title('Action')
    ->width('18%')
    ->searchable(false)
    ->orderable(false)
    ->exportable(false)
    ->printable(false)
    ];
    }

    protected function filename(): string
    {
    return 'GroupTypeMaster_' . date('YmdHis');
    }
    }