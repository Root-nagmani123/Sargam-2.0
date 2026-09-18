<?php

namespace App\DataTables;

use App\Models\ExaminationDrive;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ExaminationDriveDataTable extends DataTable
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
            ->addColumn('examination_type', fn ($row) => $row->examinationType->exam_type_name ?? '-')
            ->addColumn('term', fn ($row) => $row->term->term_name ?? '-')
            ->addColumn('course', fn ($row) => $row->course->couse_short_name ?? $row->course->course_name ?? '-')
            ->addColumn('phase', fn ($row) => $row->phase)
            ->addColumn('academic_session', fn ($row) => $row->academic_session)
            ->addColumn('start_date', fn ($row) => $row->start_date ? Carbon::parse($row->start_date)->format('d-m-Y') : '')
            ->addColumn('end_date', fn ($row) => $row->end_date ? Carbon::parse($row->end_date)->format('d-m-Y') : '')
            ->addColumn('status', function ($row) {
                $badgeClass = match ((int) $row->status) {
                    ExaminationDrive::STATUS_PUBLISHED => 'programme-status-badge--active',
                    ExaminationDrive::STATUS_CLOSED => 'programme-status-badge--inactive',
                    default => 'programme-status-badge--draft',
                };
                $label = ExaminationDrive::STATUS_LABELS[(int) $row->status] ?? 'Draft';

                return '<span class="badge rounded-1 programme-status-badge ' . $badgeClass . '">' . $label . '</span>';
            })
            ->addColumn('action', function ($row) {
                $deleteUrl = route('master.examination.drive.destroy', ['id' => encrypt($row->id)]);
                $csrf = csrf_token();

                $editBtn = '<button type="button" class="programme-action-btn ed-edit-btn" aria-label="Edit examination drive"'
                        . ' data-id="' . encrypt($row->id) . '"'
                        . ' data-examination_type_master_pk="' . $row->examination_type_master_pk . '"'
                        . ' data-term_master_pk="' . $row->term_master_pk . '"'
                        . ' data-course_master_pk="' . $row->course_master_pk . '"'
                        . ' data-course_name="' . e($row->course->couse_short_name ?? $row->course->course_name ?? '') . '"'
                        . ' data-phase="' . e($row->phase) . '"'
                        . ' data-academic_session="' . $row->academic_session . '"'
                        . ' data-start_date="' . $row->start_date . '"'
                        . ' data-end_date="' . $row->end_date . '"'
                        . ' data-status="' . (int) $row->status . '">'
                        . '<i class="bi bi-pencil" aria-hidden="true"></i>'
                        . '</button>';

                $deleteHtml = '<form action="' . $deleteUrl . '" method="POST" class="d-inline-flex m-0 ed-delete-form">'
                        . '<input type="hidden" name="_token" value="' . $csrf . '">'
                        . '<input type="hidden" name="_method" value="DELETE">'
                        . '<button type="submit" class="programme-action-btn programme-action-btn--danger" aria-label="Delete examination drive">'
                        . '<i class="bi bi-trash3" aria-hidden="true"></i>'
                        . '</button>'
                        . '</form>';

                $mapUrl = route('master.examination.drive.components.show', ['driveId' => encrypt($row->id)]);
                $mapBtn = '<a href="' . $mapUrl . '" class="programme-action-btn" aria-label="Map subjects & components" title="Map Subjects & Components">'
                        . '<i class="bi bi-diagram-3" aria-hidden="true"></i>'
                        . '</a>';

                $facultyUrl = route('master.examination.drive.faculty.show', ['driveId' => encrypt($row->id)]);
                $facultyBtn = '<a href="' . $facultyUrl . '" class="programme-action-btn" aria-label="Faculty mapping" title="Faculty Mapping">'
                        . '<i class="bi bi-person-badge" aria-hidden="true"></i>'
                        . '</a>';

                return '
                <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">
                    ' . $editBtn . $mapBtn . $facultyBtn . $deleteHtml . '
                </div>';
            })
            ->filter(function ($query) {
                $search = request()->input('search.value');
                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('phase', 'like', "%{$search}%")
                          ->orWhere('academic_session', 'like', "%{$search}%")
                          ->orWhereHas('examinationType', fn ($sq) => $sq->where('exam_type_name', 'like', "%{$search}%"))
                          ->orWhereHas('term', fn ($sq) => $sq->where('term_name', 'like', "%{$search}%"))
                          ->orWhereHas('course', fn ($sq) => $sq->where('course_name', 'like', "%{$search}%")
                              ->orWhere('couse_short_name', 'like', "%{$search}%"));
                    });
                }
            }, true)
            ->setRowId('id')
            ->rawColumns(['status', 'action']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\ExaminationDrive $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(ExaminationDrive $model): QueryBuilder
    {
        $query = $model->newQuery()->with(['examinationType', 'term', 'course']);

        // Restrict to the courses this user's role is allowed to see (empty = no
        // restriction, i.e. Admin / Super Admin / PA).
        $data_course_id = get_Role_by_course();
        if (!empty($data_course_id)) {
            $query->whereIn('course_master_pk', $data_course_id);
        }

        // Same Active/Archived split as the Programme page: end_date not yet
        // passed = Active, already passed = Archived.
        $statusFilter = request('status_filter');
        $currentDate = Carbon::now()->format('Y-m-d');

        if ($statusFilter === 'archive') {
            $query->where('end_date', '<', $currentDate);
        } elseif ($statusFilter === 'active' || !$statusFilter) {
            $query->where('end_date', '>=', $currentDate);
        }

        $courseFilter = request('course_filter');
        if (!empty($courseFilter)) {
            $query->where('course_master_pk', $courseFilter);
        }

        return $query->latest('id');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('examinationdrive-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->selectStyleSingle()
                    ->responsive(true)
                    ->parameters([
                        'responsive'   => true,
                        'scrollX'      => false,
                        'autoWidth'    => false,
                        'ordering'     => false,
                        'searching'    => true,
                        'lengthChange' => true,
                        'pageLength'   => 10,
                        'lengthMenu'   => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                        'order'        => [],
                        'language'     => [
                            'search'           => '',
                            'searchPlaceholder' => 'Search',
                            'paginate'         => [
                                'previous' => '‹',
                                'next'     => '›',
                            ],
                            'lengthMenu'   => 'Showing _MENU_',
                            'info'         => 'of _TOTAL_ items',
                            'infoEmpty'    => 'of 0 items',
                            'infoFiltered' => 'of _MAX_ items',
                        ],
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
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('examination_type')->title('Examination Type')->searchable(false)->orderable(false),
            Column::computed('term')->title('Term')->searchable(false)->orderable(false),
            Column::computed('course')->title('Course')->searchable(false)->orderable(false),
            Column::computed('phase')->title('Phase')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('academic_session')->title('Academic Session')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('start_date')->title('Start Date')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('end_date')->title('End Date')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'ExaminationDrive_' . date('YmdHis');
    }
}
