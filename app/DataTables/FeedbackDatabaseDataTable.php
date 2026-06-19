<?php

namespace App\DataTables;

use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;

class FeedbackDatabaseDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->query($query)
            ->addIndexColumn()
            ->editColumn('faculty_name', function ($row) {
                $encId = encrypt($row->faculty_id);
                return '<a href="/faculty/show/' . $encId . '" target="_blank" class="link-primary fw-semibold" style="text-decoration:none;">'
                    . e($row->faculty_name) . '</a>';
            })
            ->editColumn('course_name', fn($row) => e($row->course_name))
            ->editColumn('faculty_address', function ($row) {
                $addr = e($row->faculty_address ?? 'N/A');
                $email = $row->faculty_email
                    ? '<br><a href="mailto:' . e($row->faculty_email) . '" class="text-muted small">' . e($row->faculty_email) . '</a>'
                    : '';
                return '<small class="text-body-secondary">' . $addr . $email . '</small>';
            })
            ->editColumn('subject_topic', function ($row) {
                $topic = e($row->subject_topic ?? '—');
                return '<small class="text-truncate d-block" style="max-width:200px;" title="' . $topic . '">' . $topic . '</small>';
            })
            ->editColumn('avg_content_percent', function ($row) {
                $val = floatval($row->avg_content_percent);
                $class = $val >= 90 ? 'percentage-excellent' : ($val >= 80 ? 'percentage-good' : 'percentage-average');
                return '<span class="percentage-badge ' . $class . '">' . number_format($val, 2) . '%</span>';
            })
            ->editColumn('avg_presentation_percent', function ($row) {
                $val = floatval($row->avg_presentation_percent);
                $class = $val >= 90 ? 'percentage-excellent' : ($val >= 80 ? 'percentage-good' : 'percentage-average');
                return '<span class="percentage-badge ' . $class . '">' . number_format($val, 2) . '%</span>';
            })
            ->editColumn('participant_count', function ($row) {
                return '<span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">' . intval($row->participant_count) . '</span>';
            })
            ->editColumn('session_date', function ($row) {
                return $row->session_date
                    ? '<small>' . \Carbon\Carbon::parse($row->session_date)->format('d M Y') . '</small>'
                    : '<small>N/A</small>';
            })
            ->addColumn('comments', function ($row) {
                if (!empty($row->all_comments)) {
                    $escaped = e($row->all_comments);
                    return '<button class="btn btn-sm btn-outline-primary view-comments-btn" data-comments="' . $escaped . '" style="border-radius:20px;font-size:0.75rem;">'
                        . '<i class="fas fa-comment-dots"></i> View</button>';
                }
                return '<span class="text-muted" style="font-size:0.8rem;">—</span>';
            })
            ->rawColumns(['faculty_name', 'faculty_address', 'subject_topic', 'avg_content_percent', 'avg_presentation_percent', 'participant_count', 'session_date', 'comments'])
            ->filter(function ($query) {
                $request = request();

                if ($request->filled('course_id')) {
                    $query->where('t.course_master_pk', $request->course_id);
                }

                if ($request->filled('faculty_id')) {
                    $query->where('f.pk', $request->faculty_id);
                }

                if ($request->filled('topic_value')) {
                    $query->where('t.subject_topic', 'like', '%' . $request->topic_value . '%');
                }

                // Global search
                $searchValue = $request->input('search.value');
                if (!empty($searchValue)) {
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('f.full_name', 'LIKE', "%{$searchValue}%")
                            ->orWhere('c.course_name', 'LIKE', "%{$searchValue}%")
                            ->orWhere('t.subject_topic', 'LIKE', "%{$searchValue}%")
                            ->orWhere('f.email_id', 'LIKE', "%{$searchValue}%");
                    });
                }
            }, true);
    }

    public function query()
    {
        DB::statement("SET SESSION group_concat_max_len = 1000000;");

        $query = DB::table('topic_feedback as tf')
            ->select([
                'f.pk as faculty_id',
                'f.full_name as faculty_name',
                'f.email_id as faculty_email',
                DB::raw('IFNULL(f.Permanent_Address, "N/A") as faculty_address'),
                'c.course_name',
                't.subject_topic',
                DB::raw('ROUND(AVG(tf.content) * 20, 2) as avg_content_percent'),
                DB::raw('ROUND(AVG(tf.presentation) * 20, 2) as avg_presentation_percent'),
                DB::raw('COUNT(DISTINCT tf.student_master_pk) as participant_count'),
                DB::raw('DATE(t.START_DATE) as session_date'),
                DB::raw('GROUP_CONCAT(DISTINCT CASE WHEN tf.remark IS NOT NULL AND TRIM(tf.remark) != "" THEN tf.remark ELSE NULL END SEPARATOR " | ") as all_comments'),
                't.pk as timetable_pk'
            ])
            ->join('timetable as t', 'tf.timetable_pk', '=', 't.pk')
            ->join('faculty_master as f', 'tf.faculty_pk', '=', 'f.pk')
            ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
            ->where('tf.is_submitted', 1)
            ->whereNotNull('tf.content')
            ->whereNotNull('tf.presentation')
            ->where('tf.content', '!=', '')
            ->where('tf.presentation', '!=', '')
            ->groupBy(
                'f.pk',
                'f.full_name',
                'f.email_id',
                'f.Permanent_Address',
                'c.course_name',
                't.subject_topic',
                't.START_DATE',
                't.pk'
            );

        // Conditional HAVING filter
        $request = request();
        if ($request->filled('cond_field') && $request->filled('cond_operator') && $request->filled('cond_value')) {
            $allowedFields = ['content', 'presentation', 'average'];
            $allowedOperators = ['>=', '<=', '>', '<', '='];
            $field = $request->cond_field;
            $operator = $request->cond_operator;
            $value = (float) $request->cond_value;

            if (in_array($field, $allowedFields) && in_array($operator, $allowedOperators)) {
                if ($field === 'average') {
                    $query->havingRaw("ROUND((AVG(tf.content) * 20 + AVG(tf.presentation) * 20) / 2, 2) {$operator} ?", [$value]);
                } else {
                    $query->havingRaw("ROUND(AVG(tf.{$field}) * 20, 2) {$operator} ?", [$value]);
                }
            }
        }

        $query->orderBy('t.START_DATE', 'ASC')
            ->orderByRaw("
                COALESCE(
                    TIME_TO_SEC(STR_TO_DATE(TRIM(SUBSTRING_INDEX(t.class_session, ' - ', 1)), '%h:%i %p')),
                    86400
                ) ASC
            ");

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('feedbackDatabaseTable')
            ->addTableClass('table table-hover align-middle mb-0')
            ->columns($this->getColumns())
            ->ajax([
                'url' => route('admin.feedback.database'),
                'type' => 'GET',
                'data' => 'function(d) {
                    d.course_id = $("#courseSelect").val();
                    d.faculty_id = $("#facultyFilter").val();
                    d.topic_value = $("#topicFilter").val();
                    d.cond_field = $("#conditionalField").val();
                    d.cond_operator = $("#conditionalOperator").val();
                    d.cond_value = $("#conditionalValue").val();
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
                'pageLength' => 10,
                'order' => [],
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'language' => [
                    'search' => '<i class="fas fa-search"></i>',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'infoEmpty' => 'No entries found',
                    'infoFiltered' => '(filtered from _MAX_ total)',
                    'zeroRecords' => '<div class="text-center text-muted py-4"><i class="fas fa-search fa-2x mb-2 opacity-25 d-block"></i>No feedback data found for the selected criteria</div>',
                    'paginate' => [
                        'first' => '«',
                        'last' => '»',
                        'next' => '›',
                        'previous' => '‹',
                    ],
                    'processing' => '<div class="spinner-border text-primary spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Loading...',
                ],
                'dom' => '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' .
                    '<"row"<"col-sm-12"tr>>' .
                    '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                'processing' => true,
                'serverSide' => true,
                'stateSave' => false,
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')
                ->title('S.No.')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(false)
                ->width('50px'),

            Column::make('faculty_name')
                ->title('Faculty Name')
                ->orderable(true)
                ->searchable(false),

            Column::make('course_name')
                ->title('Course Name')
                ->orderable(true)
                ->searchable(false),

            Column::make('faculty_address')
                ->title('Faculty Address')
                ->orderable(false)
                ->searchable(false),

            Column::make('subject_topic')
                ->title('Topic')
                ->orderable(true)
                ->searchable(false),

            Column::make('avg_content_percent')
                ->title('Content (%)')
                ->addClass('text-center')
                ->orderable(true)
                ->searchable(false),

            Column::make('avg_presentation_percent')
                ->title('Presentation (%)')
                ->addClass('text-center')
                ->orderable(true)
                ->searchable(false),

            Column::make('participant_count')
                ->title('Participants')
                ->addClass('text-center')
                ->orderable(true)
                ->searchable(false),

            Column::make('session_date')
                ->title('Session Date')
                ->addClass('text-center')
                ->orderable(true)
                ->searchable(false),

            Column::computed('comments')
                ->title('Comments')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'FeedbackDatabase_' . date('YmdHis');
    }
}
