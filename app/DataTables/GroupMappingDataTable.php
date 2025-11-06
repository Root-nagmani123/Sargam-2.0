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
            ->addColumn('facility', function ($row) {
                return $row->facility->venue_name ?? '-';
            })
            ->addColumn('student_count', fn($row) => $row->student_course_group_map_count ?? '-')
            ->addColumn('view_download', function ($row) {
                $id = encrypt($row->pk);

                if( !empty($row->student_course_group_map_count) && $row->student_course_group_map_count > 0) {
                    return "
                    <a 
                        href='javascript:void(0)'
                        class='btn btn-info btn-sm view-student'
                        data-id='{$id}'
                    >View Student</a>
                    <a href='" . route('group.mapping.export.student.list', $id) . "' class='btn btn-sm btn-primary'>
                        <i class='fa fa-download'></i> Download
                    </a>
                    ";
                }
                return "
                <span class='text-muted'>No Students</span>
                ";
            })
            ->addColumn('action', function ($row) {
                $id = encrypt($row->pk);
                $csrf = csrf_token();

                $editUrl = route('group.mapping.edit', ['id' => $id]);
                $deleteUrl = route('group.mapping.delete', ['id' => $id]);

                return '
                    <a href="'.$editUrl.'" class="btn btn-primary btn-sm">Edit</a>
                    <form action="'.$deleteUrl.'" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this record?\')">
                        <input type="hidden" name="_token" value="'.$csrf.'">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>    
                ';
                
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
            ->rawColumns(['course_name', 'group_name', 'counsellor_group_name', 'view_download', 'action', 'status']);
    }

    public function query(GroupTypeMasterCourseMasterMap $model): QueryBuilder
    {
        $statusFilter = request('status_filter', 'active');
        $currentDate = Carbon::now()->format('Y-m-d');

        $query = $model->newQuery()
                ->withCount('studentCourseGroupMap')
                ->with(['courseGroup', 'courseGroupType', 'facility'])
                // ->when($statusFilter === 'active' || empty($statusFilter), function ($query) use ($currentDate) {
                //     $query->whereHas('courseGroup', function ($courseQuery) use ($currentDate) {
                //         $courseQuery->where(function ($dateQuery) use ($currentDate) {
                //             $dateQuery->whereNull('start_year')
                //                 ->orWhereDate('start_year', '<=', $currentDate);
                //         })->where(function ($dateQuery) use ($currentDate) {
                //             $dateQuery->whereNull('end_date')
                //                 ->orWhereDate('end_date', '>=', $currentDate);
                //         });
                //     });
                // })
                ->when($statusFilter === 'active' || empty($statusFilter), function ($query) use ($currentDate) {
                    $query->whereHas('courseGroup', function ($courseQuery) use ($currentDate) {
                        $courseQuery->where(function ($q) use ($currentDate) {
                            $q->whereNull('end_date')              // end date NULL ho (kabhi khatam nahi)
                              ->orWhereDate('end_date', '>=', $currentDate); // ya abhi ya future me active
                        });
                    });
                })
                
                ->when($statusFilter === 'archive', function ($query) use ($currentDate) {
                    $query->whereHas('courseGroup', function ($courseQuery) use ($currentDate) {
                        $courseQuery->whereNotNull('end_date')
                            ->whereDate('end_date', '<', $currentDate);
                    });
                })
                ->orderBy('pk', 'desc');
                // <- check karo ki data aa raha hai ya nahi

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('group-mapping-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1)
            ->responsive(true)
            ->selectStyleSingle()
            ->parameters([
                'responsive' => true,
                'scrollX' => true,
                'autoWidth' => false,
                'order' => [],
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
            Column::make('facility')
                ->title('Facility')
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
