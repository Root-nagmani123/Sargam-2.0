<?php

namespace App\DataTables\FC;

use App\Http\Controllers\Admin\FeedbackController;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SessionTimetableReportDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->query($query)
            ->addIndexColumn()
            ->editColumn('start_date', function ($row) {
                $v = $row->start_date ?? null;

                return $v ? date('d M Y, g:i A', strtotime((string) $v)) : '—';
            })
            ->editColumn('end_date', function ($row) {
                $v = $row->end_date ?? null;

                return $v ? date('d M Y, g:i A', strtotime((string) $v)) : '—';
            })
            ->editColumn('subject_topic', fn ($row) => $row->subject_topic ?? '—')
            ->editColumn('faculty_name', fn ($row) => $row->faculty_name ?? '—')
            ->editColumn('faculty_code', fn ($row) => $row->faculty_code ?? '—')
            ->editColumn('faculty_type', fn ($row) => $row->faculty_type ?? '—')
            ->editColumn('course_name', fn ($row) => $row->course_name ?? '—')
            ->editColumn('couse_short_name', fn ($row) => $row->couse_short_name ?? '—')
            ->editColumn('course_group_type_master', fn ($row) => $row->course_group_type_master ?? '—')
            ->editColumn('group_name', fn ($row) => $row->group_name ?? '—')
            ->editColumn('class_session', fn ($row) => $row->class_session ?? '—')
            ->editColumn('venue_name', fn ($row) => $row->venue_name ?? '—')
            ->editColumn('subject_master_name', fn ($row) => $row->subject_master_name ?? '—')
            ->editColumn('subject_module_name', fn ($row) => $row->subject_module_name ?? '—')
            ->orderColumn('start_date', 't.START_DATE $1')
            ->orderColumn('end_date', 't.END_DATE $1')
            ->orderColumn('subject_topic', 't.subject_topic $1')
            ->orderColumn('course_name', 'c.course_name $1')
            ->orderColumn('couse_short_name', 'c.couse_short_name $1')
            ->orderColumn('course_group_type_master', 'cgtm.type_name $1')
            ->orderColumn('class_session', 't.class_session $1')
            ->orderColumn('venue_name', 'vm.venue_name $1')
            ->orderColumn('subject_master_name', 'sm.subject_name $1')
            ->orderColumn('subject_module_name', 'smm.module_name $1')
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');
                if ($searchValue === null || trim((string) $searchValue) === '') {
                    return;
                }
                $k = '%'.addcslashes(trim((string) $searchValue), '%_\\').'%';
                $query->where(function ($w) use ($k) {
                    $w->where('t.subject_topic', 'like', $k)
                        ->orWhere('c.course_name', 'like', $k)
                        ->orWhere('c.couse_short_name', 'like', $k)
                        ->orWhere('cgtm.type_name', 'like', $k)
                        ->orWhere('t.class_session', 'like', $k)
                        ->orWhere('vm.venue_name', 'like', $k)
                        ->orWhere('sm.subject_name', 'like', $k)
                        ->orWhere('smm.module_name', 'like', $k);
                });
            }, true);
    }

    public function query()
    {
        /** @var FeedbackController $controller */
        $controller = app(FeedbackController::class);

        return $controller->makeSessionTimetableReportQueryBuilder(request());
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('sessionTimetableReportTable')
            ->addTableClass('table table-hover align-middle text-nowrap mb-0 w-100')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->ajax([
                'url' => route('admin.feedback.session_timetable_report.datatable'),
                'type' => 'GET',
                'data' => 'function(d) {
                    var ct = document.querySelector("input[name=str_course_type]:checked");
                    d.filter_course_type = ct ? ct.value : "current";
                    d.filter_course_id = $("#filter_course").val() || "";
                    d.filter_faculty_id = $("#filter_faculty").val() || "";
                    d.filter_faculty_type = $("#filter_faculty_type").val() || "";
                    d.filter_venue_id = $("#filter_venue").val() || "";
                    d.filter_subject_topic = $("#filter_subject_topic").val() || "";
                    d.filter_subject_module = $("#filter_subject_module").val() || "";
                    d.filter_date_from = $("#filter_date_from").val() || "";
                    d.filter_date_to = $("#filter_date_to").val() || "";
                }',
            ])
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'scrollX' => true,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'order' => [[2, 'desc']],
                'lengthMenu' => [[10, 25, 50, 100], [10, 25, 50, 100]],
                'language' => [
                    'search' => 'Search table:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'infoEmpty' => 'Showing 0 to 0 of 0 entries',
                    'infoFiltered' => '(filtered from _MAX_ total entries)',
                    'processing' => '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading…</span></div>',
                ],
                'dom' => '<"row mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
                'processing' => true,
                'serverSide' => true,
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')
                ->title('S.No.')
                ->width(20)
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(false),

            Column::make('pk')
                ->title('pk')
                ->orderable(false)
                ->searchable(false)
                ->visible(false)
                ->exportable(false)
                ->printable(false),

            Column::make('subject_topic')
                ->title('Topic')
                ->searchable(false),

            Column::make('faculty_name')
                ->title('Faculty')
                ->orderable(false)
                ->searchable(false),

            Column::make('faculty_code')
                ->title('Code')
                ->orderable(false)
                ->searchable(false),

            Column::make('faculty_type')
                ->title('Faculty type')
                ->orderable(false)
                ->searchable(false),

            Column::make('course_name')
                ->title('Course')
                ->searchable(false),

            Column::make('couse_short_name')
                ->title('Short')
                ->searchable(false),

            Column::make('course_group_type_master')
                ->title('Prog. type')
                ->searchable(false),

            Column::make('group_name')
                ->title('Groups')
                ->orderable(false)
                ->searchable(false),

            Column::make('class_session')
                ->title('Session')
                ->searchable(false),
                Column::make('start_date')
                ->title('Start')
                ->searchable(false),

            Column::make('end_date')
                ->title('End')
                ->searchable(false),

            Column::make('venue_name')
                ->title('Venue')
                ->searchable(false),

            Column::make('subject_master_name')
                ->title('Subject')
                ->searchable(false),

            Column::make('subject_module_name')
                ->title('Module')
                ->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'Session_Timetable_Report_'.date('YmdHis');
    }
}
