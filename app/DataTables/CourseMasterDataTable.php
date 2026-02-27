<?php

namespace App\DataTables;

use App\Models\CourseMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class CourseMasterDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     * @return \Yajra\DataTables\EloquentDataTable
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('course_name', fn($row) => $row->course_name)
            ->addColumn('couse_short_name', fn($row) => $row->couse_short_name)
            ->addColumn('course_year', fn($row) => $row->course_year)
            ->addColumn('start_year', function ($row) {
                return $row->start_year ? Carbon::parse($row->start_year)->format('d-m-Y') : '';
            })
            ->addColumn('end_date', function ($row) {
                return $row->end_date ? Carbon::parse($row->end_date)->format('d-m-Y') : '';
            })
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '
                <div class="form-check form-switch d-inline-block">
                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                        data-table="course_master" data-column="active_inactive" data-id="'.$row->pk.'" '.$checked.'>
                </div>';
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('programme.edit', ['id' => encrypt($row->pk)]);
                $viewUrl = route('programme.show', ['id' => encrypt($row->pk)]);
                $deleteUrl = route('programme.destroy', ['id' => encrypt($row->pk)]);
                $isActive = $row->active_inactive == 1;
                $csrf = csrf_token();
                $btnId = 'dropdown-btn-' . $row->pk;

                $html = <<<HTML
<td class="text-center">
    <div class="d-inline-flex align-items-center gap-2"
         role="group"
         aria-label="Row actions">

        <!-- View -->
        <a
            href="{$viewUrl}"
            class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
            aria-label="View course"
        >
            <span class="material-icons material-symbols-rounded"
                  style="font-size:18px;"
                  aria-hidden="true">
                visibility
            </span>
            <span class="d-none d-lg-inline">View</span>
        </a>

        <!-- Edit -->
        <a
            href="{$editUrl}"
            class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
            aria-label="Edit course"
        >
            <span class="material-icons material-symbols-rounded"
                  style="font-size:18px;"
                  aria-hidden="true">
                edit
            </span>
            <span class="d-none d-lg-inline">Edit</span>
        </a>

        <!-- Delete -->
        <?php if ($isActive): ?>
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 d-none"
                disabled
                aria-disabled="true"
                title="Cannot delete active course"
            >
                <span class="material-icons material-symbols-rounded"
                      style="font-size:18px;"
                      aria-hidden="true">
                    delete
                </span>
                <span class="d-none d-lg-inline">Delete</span>
            </button>
        <?php else: ?>
            <form action="{$deleteUrl}" method="POST" class="d-inline">
                <input type="hidden" name="_token" value="{$csrf}">
                <input type="hidden" name="_method" value="DELETE">

                <button
                    type="submit"
                    class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1"
                    aria-label="Delete course"
                    onclick="return confirm('Are you sure you want to delete this course?');"
                >
                    <span class="material-icons material-symbols-rounded"
                          style="font-size:18px;"
                          aria-hidden="true">
                        delete
                    </span>
                    <span class="d-none d-lg-inline">Delete</span>
                </button>
            </form>
        <?php endif; ?>

    </div>
</td>

HTML;
                return $html;
            })
            ->filterColumn('course_name', function ($query, $keyword) {
                $query->where('course_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('couse_short_name', function ($query, $keyword) {
                $query->where('couse_short_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('course_year', function ($query, $keyword) {
                $query->where('course_year', 'like', "%{$keyword}%");
            })
            ->filterColumn('start_year', function ($query, $keyword) {
                $query->where('start_year', 'like', "%{$keyword}%");
            })
            ->filterColumn('end_date', function ($query, $keyword) {
                $query->where('end_date', 'like', "%{$keyword}%");
            })
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->where('course_name', 'like', "%{$searchValue}%")
                            ->orWhere('couse_short_name', 'like', "%{$searchValue}%")
                            ->orWhere('course_year', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            ->rawColumns(['action', 'status'])
            ->setRowId('pk');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\CourseMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(CourseMaster $model): QueryBuilder
    {
        $data_course_id =  get_Role_by_course();
         if(!empty($data_course_id))
        {
            $model = $model->whereIn('pk',$data_course_id);
        }
        $query = $model->orderBy('pk', 'desc')->newQuery();
        
        // Apply status filter if provided
        $statusFilter = request('status_filter');
        $courseFilter = request('course_filter');
        $currentDate = Carbon::now()->format('Y-m-d');
        
        if ($statusFilter === 'active' || !$statusFilter) {
            // Active courses: end_date is today or in the future (current and upcoming courses)
            $query->where('end_date', '>=', $currentDate);
        } elseif ($statusFilter === 'archive') {
            // Archived courses: end_date has already passed (expired courses)
            $query->where('end_date', '<', $currentDate);
        }
        
        // Apply course filter if provided
        if (!empty($courseFilter)) {
            $query->where('pk', $courseFilter);
        }
        
        return $query;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('coursemaster-table')
            ->columns($this->getColumns())
            ->minifiedAjax() // This will use the current route for ajax
            // ->orderBy(1)
            ->selectStyleSingle()
            ->responsive(true)
            ->parameters([
                'responsive' => true,
                'scrollX' => true,
                'autoWidth' => false,
                'ordering' => false,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'order' => [],
            ])
            ->buttons([
                Button::make('excel'),
                Button::make('csv'),
                Button::make('pdf'),
                Button::make('print'),
                Button::make('reset'),
                Button::make('reload'),
            ]);
    }

    /**
     * Get the dataTable columns definition.
     *
     * @return array
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->searchable(false)->orderable(false),
            Column::make('course_name')->title('Course Name')->addClass('text-center')->orderable(false)->searchable(true),
            Column::make('couse_short_name')->title('Short Name')->addClass('text-center')->orderable(false)->searchable(true),
            Column::make('course_year')->title('Course Year')->addClass('text-center')->orderable(false)->searchable(true),
            Column::make('start_year')->title('Start Date')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('end_date')->title('End Date')->addClass('text-center')->orderable(false)->searchable(false),
                Column::computed('status')->addClass('text-center')->orderable(false)->searchable(false),
            Column::computed('action')->addClass('text-center')->orderable(false)->searchable(false),
        
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'CourseMaster_' . date('YmdHis');
    }
}