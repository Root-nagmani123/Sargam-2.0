<?php
namespace App\DataTables;

use App\Models\GroupTypeMasterCourseMasterMap;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

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
            ->addColumn('student_count', fn($row) => $row->student_course_group_map_count ?? '-')
            ->addColumn('view_download', function ($row) {
                $id = encrypt($row->pk);
                    if (!empty($row->student_course_group_map_count) && $row->student_course_group_map_count > 0) {
                        $exportUrl = route('group.mapping.export.student.list', $id);
                        $html = <<<HTML
    <a href="javascript:void(0)" class="view-student" data-id="{$id}" data-bs-toggle="tooltip" data-bs-placement="top" title="View Students">
        <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 30px;">person</i>
    </a>
    <a href="{$exportUrl}" data-bs-toggle="tooltip" data-bs-placement="top" title="Download Student List">
        <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 30px;">download</i>
    </a>
    HTML;
                    return $html;
                }
                return "<span class='text-muted'>No Students</span>";
            })
            ->addColumn('action', function ($row) {
                $id = encrypt($row->pk);
                $csrf = csrf_token();
                $editUrl = route('group.mapping.edit', ['id' => $id]);
                $deleteUrl = route('group.mapping.delete', ['id' => $id]);
                $html = <<<HTML
    <a href="{$editUrl}" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Mapping">
        <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">edit</i>
    </a>
    <form action="{$deleteUrl}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this record?')">
        <input type="hidden" name="_token" value="{$csrf}">
        <input type="hidden" name="_method" value="DELETE">
        <a href="javascript:void(0)" onclick="event.preventDefault(); this.closest('form').submit();" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete Mapping">
            <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">delete</i>
        </a>
    </form>
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
            ->rawColumns(['course_name', 'group_name', 'view_download', 'action', 'status']);
    }

    public function query(GroupTypeMasterCourseMasterMap $model): QueryBuilder
    {
        $statusFilter = request('status_filter', 'active');
        $currentDate = Carbon::now()->format('d-m-Y');

        $query = $model->newQuery()
                ->withCount('studentCourseGroupMap')
                ->with(['courseGroup', 'courseGroupType'])
                ->orderBy('pk', 'desc');
                // <- check karo ki data aa raha hai ya nahi

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
        ->responsive(true)
        ->selectStyleSingle()
        ->addTableClass('table table-bordered table-hover align-middle custom-mapping-table')
        ->parameters([
            'responsive' => true,
            'scrollX' => true,
            'autoWidth' => false,
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
                ->searchable(false)
                ->orderable(false),
            Column::make('type_name')
                ->title('Group Type')
                ->addClass('text-center')
                ->searchable(false)
                ->orderable(false),
            Column::make('group_name')
                ->title('Group Name')
                ->addClass('text-center')
                ->searchable(true),
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
            Column::computed('action')
                ->addClass('text-center')
                ->exportable(false)
                ->printable(false),
            Column::computed('status')
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
