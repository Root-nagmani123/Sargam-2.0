<?php

namespace App\DataTables\FC;

use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;

class PendingFeedbackDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
   public function dataTable($query)
{
    try {
        return datatables()
            ->query($query)
            ->addIndexColumn()
            ->editColumn('student_name', function ($row) {
                return $row->student_name ?? '—';
            })
            ->editColumn('course_name', function ($row) {
                return $row->course_name ?? '—';
            })
            ->editColumn('subject_topic', function ($row) {
                return $row->subject_topic ?? '—';
            })
            ->editColumn('email', function ($row) {
                return $row->email ?? '—';
            })
            ->editColumn('contact_no', function ($row) {
                return $row->contact_no ?? '—';
            })
            ->editColumn('generated_OT_code', function ($row) {
                return '<code class="bg-light p-1 rounded">' . ($row->generated_OT_code ?? '—') . '</code>';
            })
            ->editColumn('from_date', function ($row) {
                return $row->from_date ? date('d-m-Y', strtotime($row->from_date)) : '—';
            })
            ->editColumn('to_date', function ($row) {
                return $row->to_date ? date('d-m-Y', strtotime($row->to_date)) : '—';
            })
            ->editColumn('class_session', function ($row) {
                return $row->class_session ?? '—';
            })
            ->editColumn('venue_name', function ($row) {
                return $row->venue_name ?? '—';
            })
            ->editColumn('faculty_name', function ($row) {
                return $row->faculty_name ?? '—';
            })
            ->rawColumns(['generated_OT_code'])
            
            // IMPORTANT: Add custom filter handlers for each column
            ->filterColumn('student_name', function($query, $keyword) {
                $query->whereRaw("TRIM(CONCAT(
                    COALESCE(sm.first_name, ''),
                    ' ',
                    COALESCE(sm.middle_name, ''),
                    ' ',
                    COALESCE(sm.last_name, '')
                )) LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('course_name', function($query, $keyword) {
                $query->where('c.course_name', 'LIKE', "%{$keyword}%");
            })
            ->filterColumn('subject_topic', function($query, $keyword) {
                $query->where('t.subject_topic', 'LIKE', "%{$keyword}%");
            })
            ->filterColumn('email', function($query, $keyword) {
                $query->where('sm.email', 'LIKE', "%{$keyword}%");
            })
            ->filterColumn('contact_no', function($query, $keyword) {
                $query->where('sm.contact_no', 'LIKE', "%{$keyword}%");
            })
            ->filterColumn('generated_OT_code', function($query, $keyword) {
                $query->where('sm.generated_OT_code', 'LIKE', "%{$keyword}%");
            })
            ->filterColumn('from_date', function($query, $keyword) {
                $query->whereDate('t.START_DATE', 'LIKE', "%{$keyword}%");
            })
            ->filterColumn('to_date', function($query, $keyword) {
                $query->whereDate('t.END_DATE', 'LIKE', "%{$keyword}%");
            })
            ->filterColumn('class_session', function($query, $keyword) {
                $query->where('t.class_session', 'LIKE', "%{$keyword}%");
            })
            
            // Global search filter (for the main search box)
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
                
                // Apply date filters
                if ($request->filled('filter_from_date') && $request->filter_from_date != '') {
                    $query->whereDate('t.START_DATE', '>=', $request->filter_from_date);
                }
                
                if ($request->filled('filter_to_date') && $request->filter_to_date != '') {
                    $query->whereDate('t.START_DATE', '<=', $request->filter_to_date);
                }
                
                // Apply global search (the main search box)
                $searchValue = $request->input('search.value');
                if (!empty($searchValue)) {
                    $query->where(function ($q) use ($searchValue) {
                        $q->whereRaw("TRIM(CONCAT(
                            COALESCE(sm.first_name, ''),
                            ' ',
                            COALESCE(sm.middle_name, ''),
                            ' ',
                            COALESCE(sm.last_name, '')
                        )) LIKE ?", ["%{$searchValue}%"])
                        ->orWhere('sm.email', 'LIKE', "%{$searchValue}%")
                        ->orWhere('sm.contact_no', 'LIKE', "%{$searchValue}%")
                        ->orWhere('sm.generated_OT_code', 'LIKE', "%{$searchValue}%")
                        ->orWhere('t.subject_topic', 'LIKE', "%{$searchValue}%")
                        ->orWhere('c.course_name', 'LIKE', "%{$searchValue}%");
                    });
                }
            }, true);
            
    } catch (\Exception $e) {
        \Log::error('DataTable Error: ' . $e->getMessage());
        throw $e;
    }
}

    /**
     * Get the query source of dataTable.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        try {
            $query = DB::table('timetable as t')
                ->select([
                    't.pk as timetable_pk',
                    't.subject_topic',
                    'c.course_name',
                    'v.venue_name',
                    DB::raw('f.full_name as faculty_name'),
                    DB::raw("TRIM(CONCAT(
                        COALESCE(sm.first_name, ''),
                        ' ',
                        COALESCE(sm.middle_name, ''),
                        ' ',
                        COALESCE(sm.last_name, '')
                    )) as student_name"),
                    'sm.email',
                    'sm.contact_no',
                    'sm.generated_OT_code',
                    't.START_DATE as from_date',
                    't.END_DATE as to_date',
                    't.class_session'
                ])
                ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
                ->join('venue_master as v', 't.venue_id', '=', 'v.venue_id')
                ->leftJoin('faculty_master as f', function ($join) {
                    $join->on('f.pk', '=', DB::raw("
                        CAST(
                            CASE 
                                WHEN JSON_VALID(t.faculty_master) 
                                THEN JSON_UNQUOTE(JSON_EXTRACT(t.faculty_master, '$[0]'))
                                ELSE t.faculty_master
                            END AS UNSIGNED
                        )
                    "));
                })
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
                ->leftJoin('topic_feedback as tf', function ($join) {
                    $join->on('tf.timetable_pk', '=', 't.pk')
                        ->on('tf.student_master_pk', '=', 'sm.pk')
                        ->where('tf.is_submitted', 1);
                })
                ->where('t.feedback_checkbox', 1)
                ->whereNull('tf.pk')
                ->where('t.START_DATE', '<=', now())
                ->whereRaw("
                    ADDTIME(t.END_DATE, 
                        STR_TO_DATE(
                            TRIM(SUBSTRING_INDEX(t.class_session, '-', -1)),
                            '%h:%i %p'
                        )
                    ) <= NOW()
                ")
                ->distinct();

            return $query;
        } catch (\Exception $e) {
            \Log::error('Query Build Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Optional method if you want to use html builder.
     */
   public function html(): HtmlBuilder
{
    return $this->builder()
        ->setTableId('pendingFeedbackTable')
        ->addTableClass('table table-bordered table-striped table-hover align-middle')
        ->columns($this->getColumns())
        ->minifiedAjax()
        ->ajax([
            'url' => route('admin.feedback.pending.datatable'),
            'type' => 'GET',
            'data' => 'function(d) {
                d.filter_course_pk = $("#filter_course_pk").val();
                d.filter_session_id = $("#filter_session_id").val();
                d.filter_from_date = $("#filter_from_date").val();
                d.filter_to_date = $("#filter_to_date").val();
            }'
        ])
        ->parameters([
            'responsive' => false,
            'autoWidth' => false,
            'scrollX' => true,
            'scrollCollapse' => true,
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
        ]);
}

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')
                ->title('S.No.')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(false)
                ->width('50px'),

            Column::make('student_name')
                ->title('Student Name')
                ->orderable(true)
                ->searchable(true),

            Column::make('course_name')
                ->title('Course')
                ->orderable(true)
                ->searchable(true),

            Column::make('subject_topic')
                ->title('Session Topic')
                ->orderable(true)
                ->searchable(true),

            Column::make('email')
                ->title('Email')
                ->orderable(true)
                ->searchable(true),

            Column::make('contact_no')
                ->title('Phone')
                ->orderable(true)
                ->searchable(true),

            Column::make('generated_OT_code')
                ->title('OT Code')
                ->orderable(true)
                ->searchable(true),

            Column::make('from_date')
                ->title('From Date')
                ->orderable(true)
                ->searchable(true),

            Column::make('to_date')
                ->title('To Date')
                ->orderable(true)
                ->searchable(true),

            Column::make('class_session')
                ->title('Session Time')
                ->orderable(true)
                ->searchable(true),

            // Column::make('venue_name')
            //     ->title('Venue')
            //     ->orderable(true)
            //     ->searchable(true),

            // Column::make('faculty_name')
            //     ->title('Faculty')
            //     ->orderable(true)
            //     ->searchable(true),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'PendingFeedback_' . date('Ymd_His');
    }
}
