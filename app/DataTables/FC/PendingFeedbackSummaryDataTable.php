<?php

namespace App\DataTables\FC;

use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\Log;

class PendingFeedbackSummaryDataTable extends DataTable
{
    public function dataTable($query)
    {
        try {
            return datatables()
                ->query($query)
                ->addIndexColumn()
                ->editColumn('user_name', function ($row) {
                    return $row->user_name ?? '—';
                })
                ->editColumn('email', function ($row) {
                    return $row->email ?? '—';
                })
                ->editColumn('course_name', function ($row) {
                    return $row->course_name ?? '—';
                })
                ->editColumn('session_info', function ($row) {
                    return $row->session_info ?? 'Multiple Sessions';
                })
                ->editColumn('date_range', function ($row) {
                    return $row->date_range ?? '—';
                })
                ->editColumn('pending_count', function ($row) {
                    $count = $row->pending_count ?? 0;
                    $badgeClass = $count > 10 ? 'bg-danger' : ($count > 5 ? 'bg-warning' : 'bg-info');
                    return '<span class="badge ' . $badgeClass . ' fs-6">' . $count . '</span>';
                })
                ->rawColumns(['pending_count'])
                ->filter(function ($query) {
                    $request = request();

                    // Apply course filter
                    if ($request->filled('filter_course_pk') && $request->filter_course_pk != '') {
                        $query->where('t.course_master_pk', $request->filter_course_pk);
                    }

                    // Apply session filter
                    if ($request->filled('filter_session_id') && $request->filter_session_id != '') {
                        $query->where('t.pk', $request->filter_session_id);
                    }

                    // Apply user name filter
                    if ($request->filled('filter_user_name') && $request->filter_user_name != '') {
                        $query->where(function ($q) use ($request) {
                            $q->where('sm.display_name', 'LIKE', '%' . $request->filter_user_name . '%')
                                ->orWhere('sm.first_name', 'LIKE', '%' . $request->filter_user_name . '%')
                                ->orWhere('sm.last_name', 'LIKE', '%' . $request->filter_user_name . '%');
                        });
                    }

                    // Apply email filter
                    if ($request->filled('filter_email') && $request->filter_email != '') {
                        $query->where('sm.email', 'LIKE', '%' . $request->filter_email . '%');
                    }

                    // Apply date filters
                    if ($request->filled('filter_from_date') && $request->filter_from_date != '') {
                        $query->whereDate('t.START_DATE', '>=', $request->filter_from_date);
                    }

                    if ($request->filled('filter_to_date') && $request->filter_to_date != '') {
                        $query->whereDate('t.START_DATE', '<=', $request->filter_to_date);
                    }

                    // Apply global search - FIXED: Only search in actual table columns
                    $searchValue = $request->input('search.value');
                    if (!empty($searchValue)) {
                        $query->where(function ($q) use ($searchValue) {
                            // Search in actual database columns only
                            $q->where(DB::raw("CONCAT(
                                COALESCE(sm.first_name, ''),
                                ' ',
                                COALESCE(sm.middle_name, ''),
                                ' ',
                                COALESCE(sm.last_name, '')
                            )"), 'LIKE', "%{$searchValue}%")
                                ->orWhere('sm.email', 'LIKE', "%{$searchValue}%")
                                ->orWhere('c.course_name', 'LIKE', "%{$searchValue}%")
                                ->orWhere('sm.first_name', 'LIKE', "%{$searchValue}%")
                                ->orWhere('sm.last_name', 'LIKE', "%{$searchValue}%");
                        });
                    }
                }, true);
        } catch (\Exception $e) {
            Log::error('Summary DataTable Error: ' . $e->getMessage());
            throw $e;
        }
    }
   

    public function query()
{
    try {
        $request = request();

        // =========================
        // 🔹 COMMON: PRE-AGGREGATED FEEDBACK SUBQUERY
        // =========================
        $feedbackSub = DB::raw("
            (
                SELECT 
                    timetable_pk,
                    student_master_pk,
                    COUNT(*) as submitted_count
                FROM topic_feedback
                WHERE is_submitted = 1
                GROUP BY timetable_pk, student_master_pk
            ) as tf
        ");

        // =========================
        // 🔹 CASE 1: SESSION FILTER
        // =========================
        if ($request->filled('filter_session_id')) {

            return DB::table('timetable as t')

                ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')

                ->join('course_student_attendance as csa', function ($join) {
                    $join->on('csa.timetable_pk', '=', 't.pk')
                        ->where('csa.status', '1');
                })

                ->join('student_master as sm', 'sm.pk', '=', 'csa.Student_master_pk')

                ->join('student_master_course__map as smcm', function ($join) {
                    $join->on('smcm.student_master_pk', '=', 'sm.pk')
                        ->on('smcm.course_master_pk', '=', 't.course_master_pk')
                        ->where('smcm.active_inactive', 1);
                })

                // ✅ FAST FEEDBACK JOIN
                ->leftJoin($feedbackSub, function ($join) {
                    $join->on('tf.timetable_pk', '=', 't.pk')
                        ->on('tf.student_master_pk', '=', 'sm.pk');
                })

                ->select([
                    DB::raw("CONCAT(
                        COALESCE(sm.first_name, ''),
                        ' ',
                        COALESCE(sm.middle_name, ''),
                        ' ',
                        COALESCE(sm.last_name, '')
                    ) as user_name"),

                    'sm.email',
                    'sm.contact_no',
                    'c.course_name',

                    DB::raw("
                        CONCAT(
                            COALESCE(t.subject_topic, 'Session'),
                            ' (', DATE_FORMAT(t.START_DATE, '%d-%m-%Y'), ')'
                        ) as session_info
                    "),

                    DB::raw("DATE_FORMAT(t.START_DATE, '%d-%m-%Y') as date_range"),

                    DB::raw("
                        (
                            CASE 
                                WHEN JSON_VALID(t.faculty_master) 
                                THEN JSON_LENGTH(t.faculty_master)
                                ELSE 1
                            END
                        )
                        -
                        COALESCE(tf.submitted_count, 0)
                        as pending_count
                    ")
                ])

                ->where('t.pk', $request->filter_session_id)
                ->where('t.feedback_checkbox', 1)

                ->whereRaw("
                    TIMESTAMP(
                        t.END_DATE,
                        STR_TO_DATE(
                            TRIM(SUBSTRING_INDEX(t.class_session, '-', -1)),
                            '%h:%i %p'
                        )
                    ) <= NOW()
                ")

                ->havingRaw('pending_count > 0');
        }

        // =========================
        // 🔹 CASE 2: SUMMARY MODE
        // =========================

        return DB::table('timetable as t')

            ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')

            ->join('student_master_course__map as smcm', function ($join) {
                $join->on('smcm.course_master_pk', '=', 't.course_master_pk')
                    ->where('smcm.active_inactive', 1);
            })

            ->join('student_master as sm', 'sm.pk', '=', 'smcm.student_master_pk')

            ->join('course_student_attendance as csa', function ($join) {
                $join->on('csa.timetable_pk', '=', 't.pk')
                    ->on('csa.Student_master_pk', '=', 'sm.pk')
                    ->where('csa.status', '1');
            })

            // ✅ FAST FEEDBACK JOIN
            ->leftJoin($feedbackSub, function ($join) {
                $join->on('tf.timetable_pk', '=', 't.pk')
                    ->on('tf.student_master_pk', '=', 'sm.pk');
            })

            ->select([
                DB::raw("CONCAT(
                    COALESCE(sm.first_name, ''),
                    ' ',
                    COALESCE(sm.middle_name, ''),
                    ' ',
                    COALESCE(sm.last_name, '')
                ) as user_name"),

                'sm.email',
                'sm.contact_no',
                'c.course_name',

                DB::raw("'Multiple Sessions' as session_info"),

                DB::raw("CONCAT(
                    DATE_FORMAT(MIN(t.START_DATE), '%d-%m-%Y'),
                    ' to ',
                    DATE_FORMAT(MAX(t.START_DATE), '%d-%m-%Y')
                ) as date_range"),

                //  aggregation
                DB::raw("
                    SUM(
                        (
                            CASE 
                                WHEN JSON_VALID(t.faculty_master) 
                                THEN JSON_LENGTH(t.faculty_master)
                                ELSE 1
                            END
                        )
                        -
                        COALESCE(tf.submitted_count, 0)
                    ) as pending_count
                ")
            ])

            ->where('t.feedback_checkbox', 1)

            ->whereRaw("
                TIMESTAMP(
                    t.END_DATE,
                    STR_TO_DATE(
                        TRIM(SUBSTRING_INDEX(t.class_session, '-', -1)),
                        '%h:%i %p'
                    )
                ) <= NOW()
            ")

            ->groupBy(
                'sm.pk',
                'sm.first_name',
                'sm.middle_name',
                'sm.last_name',
                'sm.email',
                'sm.contact_no',
                'c.course_name'
            )

            ->havingRaw('pending_count > 0');

    } catch (\Exception $e) {
        \Log::error('Summary Query Build Error: ' . $e->getMessage());
        throw $e;
    }
}

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('pendingFeedbackSummaryTable')
            ->addTableClass('table table-bordered table-striped table-hover align-middle')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->ajax([
                'url' => route('admin.feedback.summary.datatable'),
                'type' => 'GET',
                'data' => 'function(d) {
                    d.filter_course_pk = $("#filter_course_pk").val();
                    d.filter_session_id = $("#filter_session_id").val();
                    d.filter_user_name = $("#filter_user_name").val();
                    d.filter_email = $("#filter_email").val();
                    d.filter_from_date = $("#filter_from_date").val();
                    d.filter_to_date = $("#filter_to_date").val();
                }'
            ])
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'scrollX' => true,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 20,
                'order' => [[6, 'desc']],
                'lengthMenu' => [[10, 20, 50, 100, -1], [10, 20, 50, 100, 'All']],
                'language' => [
                    'search' => 'Search:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'infoEmpty' => 'Showing 0 to 0 of 0 entries',
                    'infoFiltered' => '(filtered from _MAX_ total entries)',
                    'paginate' => [
                        'first' => 'First',
                        'last' => 'Last',
                        'next' => 'Next',
                        'previous' => 'Previous',
                    ],
                    'processing' => '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                ],
                'dom' => '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' .
                    '<"row"<"col-sm-12"tr>>' .
                    '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                'processing' => true,
                'serverSide' => true,
                // CRITICAL: Disable DataTables from adding its own global search WHERE clauses
                'search' => [
                    'regex' => false,
                    'caseInsensitive' => true,
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')
                ->title('S.No.')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(false)  // Keep this false
                ->width('50px'),

            Column::make('user_name')
                ->title('User Name')
                ->orderable(true)
                ->searchable(false),  // CRITICAL: Set to false to prevent DataTables from adding WHERE clauses

            Column::make('email')
                ->title('Email')
                ->orderable(true)
                ->searchable(false),  // CRITICAL: Set to false

            Column::make('course_name')
                ->title('Course')
                ->orderable(true)
                ->searchable(false),  // CRITICAL: Set to false

            Column::make('session_info')
                ->title('Session')
                ->orderable(false)
                ->searchable(false),  // Keep false

            Column::make('date_range')
                ->title('Date Range')
                ->orderable(true)
                ->searchable(false),  // CRITICAL: Set to false

            Column::make('pending_count')
                ->title('Pending Feedback Count')
                ->orderable(true)
                ->searchable(false)  // Keep false
                ->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'PendingFeedbackSummary_' . date('Ymd_His');
    }
}