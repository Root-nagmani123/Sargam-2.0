<?php

namespace App\DataTables;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\CollectionDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Send Memo / Notice listing (/admin/memo-notice-management). Merges two raw-query
 * sources (student_notice_status + student_memo_status, with status=2 notices
 * reconciled against their memo row) exactly like the pre-DataTable controller did,
 * so it is served via Yajra's Collection engine rather than a single Eloquent query.
 */
class CourseAttendanceNoticeMapDataTable extends DataTable
{
    public function query(Request $request): Collection
    {
        return $this->buildMergedCollection($request);
    }

    /**
     * Same merge/filter logic as the pre-DataTable CourseAttendanceNoticeMapController::index().
     * The only change: the free-text search box is read from `search_filter` (not `search`),
     * to avoid colliding with DataTables' own reserved `search[value]` ajax parameter.
     */
    private function buildMergedCollection(Request $request): Collection
    {
        $programNameFilter = $request->get('program_name', '');
        $typeFilter = $request->get('type', '');
        $statusFilter = $request->get('status', '');
        $searchFilter = $request->get('search_filter', '');
        $fromDateFilter = $request->get('from_date', '');
        $toDateFilter = $request->get('to_date', '');

        if (empty($fromDateFilter) && empty($toDateFilter)) {
            $fromDateFilter = Carbon::today()->toDateString();
            $toDateFilter = Carbon::today()->toDateString();
        }

        $isOfficerTrainee = isOfficerTraineeUser();
        $ownStudentPk = $isOfficerTrainee ? Auth::user()->user_id : null;

        $noticesQuery = DB::table('student_notice_status as sns')
            ->leftJoin('course_student_attendance as csa', 'csa.pk', '=', 'sns.course_student_attendance_pk')
            ->leftJoin('student_master as sm', 'sm.pk', '=', 'sns.student_pk')
            ->leftJoin('timetable as t', 't.pk', '=', 'sns.subject_topic')
            ->leftJoin('course_master as cm', 'cm.pk', '=', 'sns.course_master_pk')
            // A Notice closed directly (End Chat) records its conclusion on sns itself —
            // it never becomes a student_memo_status row, so that conclusion must be
            // joined in here or a closed Notice always shows "N/A".
            ->leftJoin('memo_conclusion_master as ncm', 'ncm.pk', '=', 'sns.conclusion_type_pk')
            ->select(
                'sns.pk as notice_id',
                'sns.pk as memo_notice_id',
                'sns.student_pk',
                'sns.course_master_pk',
                'sns.date_',
                'sns.subject_master_pk',
                'sns.subject_topic',
                'sns.venue_id',
                'sns.class_session_master_pk',
                'sns.faculty_master_pk',
                'sns.message',
                'sns.notice_memo',
                'sns.status',
                'sns.conclusion_remark',
                'ncm.discussion_name',
                'sm.display_name as student_name',
                'sm.pk as student_id',
                't.subject_topic as topic_name',
                DB::raw('COALESCE(t.START_DATE, sns.date_) as session_date'),
                'cm.course_name',
                'sns.created_date',
                DB::raw('"Notice" as type_notice_memo')
            );

        if ($isOfficerTrainee) {
            $noticesQuery->where('sns.student_pk', $ownStudentPk);
        }

        if ($programNameFilter) {
            $noticesQuery->where('sns.course_master_pk', $programNameFilter);
        }

        if ($typeFilter !== null && $typeFilter !== '') {
            if ($typeFilter == '1') {
                // Notice: get notices that haven't been converted to memos
                $noticesQuery->where('sns.notice_memo', 1)->where('sns.status', '!=', 2);
            }
            // if $typeFilter == '0' (memo), we'll fetch memos separately later
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            if ($statusFilter == '1') {
                $noticesQuery->where('sns.status', 1);
            } elseif ($statusFilter == '0') {
                $noticesQuery->where('sns.status', 2);
            }
        }

        // Apply date range filter — use session date for attendance-based, notice date for direct
        if ($fromDateFilter) {
            $noticesQuery->where(function ($q) use ($fromDateFilter) {
                $q->whereDate('t.START_DATE', '>=', $fromDateFilter)
                  ->orWhere(function ($q2) use ($fromDateFilter) {
                      $q2->whereNull('t.START_DATE')
                         ->whereDate('sns.date_', '>=', $fromDateFilter);
                  });
            });
        }
        if ($toDateFilter) {
            $noticesQuery->where(function ($q) use ($toDateFilter) {
                $q->whereDate('t.START_DATE', '<=', $toDateFilter)
                  ->orWhere(function ($q2) use ($toDateFilter) {
                      $q2->whereNull('t.START_DATE')
                         ->whereDate('sns.date_', '<=', $toDateFilter);
                  });
            });
        }

        $notices = $noticesQuery->get();

        $memos = collect(); // final result collection

        // If filtering for Memo type, query student_memo_status directly
        if ($typeFilter == '0') {
            $memoQuery = DB::table('student_memo_status')
                ->leftJoin('student_master as sm', 'student_memo_status.student_pk', '=', 'sm.pk')
                ->leftJoin('student_notice_status as sns', 'student_memo_status.student_notice_status_pk', '=', 'sns.pk')
                ->leftJoin('timetable as t', 'sns.subject_topic', '=', 't.pk')
                ->leftJoin('memo_conclusion_master as mcm', 'student_memo_status.memo_conclusion_master_pk', '=', 'mcm.pk')
                ->leftJoin('course_master as cm', 'student_memo_status.course_master_pk', '=', 'cm.pk')
                ->select(
                    'student_memo_status.pk as memo_id',
                    'student_memo_status.pk as memo_notice_id',
                    'student_memo_status.student_notice_status_pk as notice_id',
                    'student_memo_status.student_pk',
                    'student_memo_status.communication_status',
                    'student_memo_status.course_master_pk',
                    'student_memo_status.date as date_',
                    'student_memo_status.conclusion_remark',
                    DB::raw('NULL as subject_master_pk'),
                    DB::raw('NULL as subject_topic'),
                    DB::raw('NULL as venue_id'),
                    DB::raw('NULL as class_session_master_pk'),
                    DB::raw('NULL as faculty_master_pk'),
                    DB::raw('"Memo" as type_notice_memo'),
                    'student_memo_status.message',
                    DB::raw('2 as notice_memo'),
                    'student_memo_status.status',
                    'sm.display_name as student_name',
                    'sm.pk as student_id',
                    't.subject_topic as topic_name',
                    't.START_DATE as session_date',
                    'mcm.discussion_name',
                    'cm.course_name',
                    'student_memo_status.created_date'
                );

            if ($isOfficerTrainee) {
                $memoQuery->where('student_memo_status.student_pk', $ownStudentPk);
            }

            if ($programNameFilter) {
                $memoQuery->where('student_memo_status.course_master_pk', $programNameFilter);
            }
            if ($statusFilter !== null && $statusFilter !== '') {
                if ($statusFilter == '1') {
                    $memoQuery->where('student_memo_status.status', 1);
                } elseif ($statusFilter == '0') {
                    $memoQuery->where('student_memo_status.status', 2);
                }
            }

            // Apply date range filter by session date
            if ($fromDateFilter) {
                $memoQuery->whereDate('t.START_DATE', '>=', $fromDateFilter);
            }
            if ($toDateFilter) {
                $memoQuery->whereDate('t.START_DATE', '<=', $toDateFilter);
            }

            $memos = $memoQuery->get();
        } else {
            // For Notice or no type filter, process notices normally
            // Fix N+1: fetch all memo data for status==2 notices in ONE query
            $statusTwoNoticeIds = $notices->where('status', 2)->pluck('notice_id')->toArray();

            $memoDataMap = collect();
            if (!empty($statusTwoNoticeIds)) {
                $memoDataMap = DB::table('student_memo_status')
                    ->leftJoin('student_master as sm', 'student_memo_status.student_pk', '=', 'sm.pk')
                    ->leftJoin('student_notice_status as sns', 'student_memo_status.student_notice_status_pk', '=', 'sns.pk')
                    ->leftJoin('timetable as t', 'sns.subject_topic', '=', 't.pk')
                    ->leftJoin('memo_conclusion_master as mcm', 'student_memo_status.memo_conclusion_master_pk', '=', 'mcm.pk')
                    ->leftJoin('course_master as cm', 'student_memo_status.course_master_pk', '=', 'cm.pk')
                    ->whereIn('student_memo_status.student_notice_status_pk', $statusTwoNoticeIds)
                    ->select(
                        'student_memo_status.pk as memo_id',
                        'student_memo_status.pk as memo_notice_id',
                        'student_memo_status.student_notice_status_pk as notice_id',
                        'student_memo_status.student_pk',
                        'student_memo_status.communication_status',
                        'student_memo_status.course_master_pk',
                        'student_memo_status.date as date_',
                        'student_memo_status.conclusion_remark',
                        DB::raw('NULL as subject_master_pk'),
                        DB::raw('NULL as subject_topic'),
                        DB::raw('NULL as venue_id'),
                        DB::raw('NULL as class_session_master_pk'),
                        DB::raw('NULL as faculty_master_pk'),
                        DB::raw('"Memo" as type_notice_memo'),
                        'student_memo_status.message',
                        DB::raw('2 as notice_memo'),
                        'student_memo_status.status',
                        'sm.display_name as student_name',
                        'sm.pk as student_id',
                        't.subject_topic as topic_name',
                        't.START_DATE as session_date',
                        'mcm.discussion_name',
                        'cm.course_name'
                    )
                    ->get()
                    ->keyBy('notice_id');
            }

            foreach ($notices as $notice) {
                if ($notice->status == 2 && isset($memoDataMap[$notice->notice_id])) {
                    $memos->push($memoDataMap[$notice->notice_id]);
                } else {
                    $memos->push($notice);
                }
            }
        }

        // Apply additional filters to final collection (only if not fetching pure memo type)
        if ($typeFilter != '0') {
            if ($programNameFilter) {
                $memos = $memos->filter(function ($item) use ($programNameFilter) {
                    return isset($item->course_master_pk) && $item->course_master_pk == $programNameFilter;
                });
            }

            if ($typeFilter !== null && $typeFilter !== '') {
                if ($typeFilter == '1') {
                    $memos = $memos->filter(function ($item) {
                        return isset($item->notice_memo) && $item->notice_memo == 1;
                    });
                }
            }

            if ($statusFilter !== null && $statusFilter !== '') {
                if ($statusFilter == '1') {
                    $memos = $memos->filter(function ($item) {
                        return isset($item->status) && $item->status == 1;
                    });
                } elseif ($statusFilter == '0') {
                    $memos = $memos->filter(function ($item) {
                        return isset($item->status) && $item->status == 2;
                    });
                }
            }

            if ($searchFilter !== null && $searchFilter !== '') {
                $memos = $memos->filter(function ($item) use ($searchFilter) {
                    return (isset($item->student_name) && stripos($item->student_name, $searchFilter) !== false)
                        || (isset($item->course_name) && stripos($item->course_name, $searchFilter) !== false)
                        || (isset($item->topic_name) && stripos($item->topic_name, $searchFilter) !== false)
                        || (isset($item->type_notice_memo) && stripos($item->type_notice_memo, $searchFilter) !== false)
                        || (isset($item->discussion_name) && stripos($item->discussion_name, $searchFilter) !== false)
                        || (isset($item->conclusion_remark) && stripos($item->conclusion_remark, $searchFilter) !== false)
                        || (isset($item->session_date) && stripos($item->session_date, $searchFilter) !== false);
                });
            }

            // Apply date range filter to collection (prefer session date)
            if ($fromDateFilter || $toDateFilter) {
                $memos = $memos->filter(function ($item) use ($fromDateFilter, $toDateFilter) {
                    $itemDate = $item->session_date ?? $item->date_ ?? null;
                    if (!$itemDate) {
                        return false;
                    }
                    if ($fromDateFilter && $itemDate < $fromDateFilter) {
                        return false;
                    }
                    if ($toDateFilter && $itemDate > $toDateFilter) {
                        return false;
                    }
                    return true;
                });
            }
        }

        return $memos->values();
    }

    public function dataTable(Collection $query): CollectionDataTable
    {
        // Notice count per student+course, over the FULL filtered set (not just the
        // current ajax page) — used to show the "multiple notices" dot on the Chats
        // button. The pre-DataTable controller computed this only over the current
        // 10-row page (a side effect of pagination happening before the count); doing
        // it over the full set here is the correct reading and no longer has that
        // pagination-dependent quirk.
        $noticeCount = $query
            ->groupBy(fn ($item) => $item->student_pk . '_' . $item->course_master_pk)
            ->map(fn ($group) => $group->where('type_notice_memo', 'Notice')->count());

        $canManageMemoNotice = hasRole('Internal Faculty') || hasRole('Guest Faculty')
            || hasRole('Super Admin') || hasRole('Training Induction Admin') || hasRole('Training-Induction');

        return (new CollectionDataTable($query))
            ->addIndexColumn()
            ->editColumn('course_name', fn ($row) => $row->course_name ?? 'N/A')
            ->editColumn('student_name', fn ($row) => $row->student_name ?? 'N/A')
            ->editColumn('topic_name', fn ($row) => $row->topic_name ?? 'N/A')
            ->editColumn('discussion_name', fn ($row) => ($row->discussion_name ?? '') !== '' ? $row->discussion_name : 'N/A')
            ->editColumn('conclusion_remark', fn ($row) => ($row->conclusion_remark ?? '') !== '' ? $row->conclusion_remark : 'N/A')
            ->editColumn('session_date', function ($row) {
                $sessionDate = $row->session_date ?? $row->date_ ?? null;

                return $sessionDate ? date('d-m-Y', strtotime($sessionDate)) : 'N/A';
            })
            ->editColumn('created_date', function ($row) {
                return !empty($row->created_date) ? date('d-m-Y', strtotime($row->created_date)) : 'N/A';
            })
            ->addColumn('type', function ($row) {
                $isNotice = ($row->type_notice_memo ?? null) == 'Notice';

                return $isNotice
                    ? '<span class="badge bg-primary-subtle text-primary"><i class="bi bi-file-earmark-text me-1"></i> Notice</span>'
                    : '<span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-file-earmark me-1"></i> Memo</span>';
            })
            ->addColumn('status', function ($row) {
                [$stLabel, $stClass] = $this->resolveStatus($row);

                return '<span class="mnm-status ' . $stClass . '">' . e($stLabel) . '</span>';
            })
            ->addColumn('action', function ($row) use ($noticeCount, $canManageMemoNotice) {
                return $this->renderActionsColumn($row, $noticeCount, $canManageMemoNotice);
            })
            ->rawColumns(['type', 'status', 'action']);
    }

    /**
     * @return array{0: string, 1: string} [label, css class]
     */
    private function resolveStatus(object $row): array
    {
        $isNotice = ($row->type_notice_memo ?? null) == 'Notice';
        $st = $row->status ?? null;
        $cs = $row->communication_status ?? null;

        if ($isNotice) {
            return $st == 1 ? ['Notice Sent', 'mnm-status--notice'] : ['Notice Chat Closed', 'mnm-status--closed'];
        }
        if ($cs == 1) {
            return ['Memo Chat Open', 'mnm-status--memo-open'];
        }
        if ($cs == 2) {
            return ['Memo Chat Closed', 'mnm-status--closed'];
        }

        return ['Memo Sent', 'mnm-status--memo-sent'];
    }

    /**
     * Mirrors resources/views/admin/courseAttendanceNoticeMap/index.blade.php's previous
     * per-row Action cell markup.
     */
    private function renderActionsColumn(object $row, Collection $noticeCount, bool $canManageMemoNotice): string
    {
        $isNotice = ($row->type_notice_memo ?? null) == 'Notice';
        $st = $row->status ?? null;
        $cs = $row->communication_status ?? null;
        [, $stClass] = $this->resolveStatus($row);
        $noticeKey = $row->student_pk . '_' . $row->course_master_pk;
        $hasBell = $isNotice && isset($noticeCount[$noticeKey]) && $noticeCount[$noticeKey] >= 2;

        $html = '<div class="mnm-actions justify-content-center">';

        if (!empty($row->notice_id)) {
            $url = route('memo.notice.management.conversation', ['id' => $row->notice_id, 'type' => 'notice']);
            $html .= '<a class="mnm-action" href="' . e($url) . '" title="View Notice"><i class="bi bi-file-earmark-text"></i><span>Notice</span></a>';
        } else {
            $html .= '<span class="mnm-action disabled" title="No notice"><i class="bi bi-file-earmark-text"></i><span>Notice</span></span>';
        }

        if (!empty($row->memo_id)) {
            $url = route('memo.notice.management.conversation', ['id' => $row->memo_id, 'type' => 'memo']);
            $html .= '<a class="mnm-action" href="' . e($url) . '" title="View Memo Document"><i class="bi bi-file-earmark"></i><span>Memo Doc</span></a>';
        } else {
            $html .= '<span class="mnm-action disabled" title="No memo yet"><i class="bi bi-file-earmark"></i><span>Memo Doc</span></span>';
        }

        // Edit Notice: template only, and only while still open
        if ($isNotice && $canManageMemoNotice && $st == 1) {
            $html .= '<a href="javascript:void(0)" class="mnm-action edit-notice-btn" data-notice-id="' . e((string) $row->notice_id) . '"'
                . ' data-bs-toggle="modal" data-bs-target="#editNoticeModal" title="Edit Notice">'
                . '<i class="bi bi-pencil"></i><span>Edit</span></a>';
        }

        // Chats: open the conversation offcanvas
        if ($isNotice) {
            $html .= '<a class="mnm-action view-conversation" data-bs-toggle="offcanvas" data-bs-target="#chatOffcanvas"'
                . ' data-type="notice" data-id="' . e((string) $row->notice_id) . '" data-topic="' . e((string) $row->topic_name) . '"'
                . ' data-participant="' . e((string) $row->student_name) . '" data-closed="' . ($stClass === 'mnm-status--closed' ? '1' : '0') . '" title="Open chat">'
                . '<i class="bi bi-chat-dots"></i><span>Chats</span>'
                . ($hasBell ? '<span class="mnm-dot"></span>' : '')
                . '</a>';
        } else {
            $html .= '<a class="mnm-action view-conversation" data-bs-toggle="offcanvas" data-bs-target="#chatOffcanvas"'
                . ' data-type="memo" data-id="' . e((string) $row->memo_id) . '" data-topic="' . e((string) $row->topic_name) . '"'
                . ' data-participant="' . e((string) $row->student_name) . '" data-closed="' . ($stClass === 'mnm-status--closed' ? '1' : '0') . '" title="Open chat">'
                . '<i class="bi bi-chat-dots"></i><span>Chats</span>'
                . ($cs == 1 ? '<span class="mnm-dot"></span>' : '')
                . '</a>';
        }

        // Memo: generate (notice→status 2) or preview (memo)
        if ($isNotice && $st == 2) {
            $html .= '<a href="javascript:void(0)" class="mnm-action generate-memo-btn" data-id="' . e((string) $row->memo_notice_id) . '"'
                . ' data-bs-toggle="modal" data-bs-target="#memo_generate" title="Generate Memo">'
                . '<i class="bi bi-file-earmark-plus"></i><span>Memo</span></a>';
        } elseif (!$isNotice) {
            $html .= '<a href="javascript:void(0)" class="mnm-action preview-memo-btn" data-notice-id="' . e((string) $row->notice_id) . '" data-memo-id="' . e((string) $row->memo_id) . '"'
                . ' data-bs-toggle="modal" data-bs-target="#memo_generate" title="View Memo">'
                . '<i class="bi bi-file-earmark-check"></i><span>Memo</span>'
                . ($cs == 1 ? '<span class="mnm-dot"></span>' : '')
                . '</a>';
            if ($canManageMemoNotice && $cs != 2) {
                $html .= '<a href="javascript:void(0)" class="mnm-action edit-memo-btn" data-memo-id="' . e((string) $row->memo_id) . '"'
                    . ' data-bs-toggle="modal" data-bs-target="#memo_generate" title="Edit Memo">'
                    . '<i class="bi bi-pencil"></i><span>Edit</span></a>';
            }
        } else {
            $html .= '<span class="mnm-action disabled" title="Memo not available yet"><i class="bi bi-file-earmark"></i><span>Memo</span></span>';
        }

        // Delete: admins/faculty only, only while still open
        if ($canManageMemoNotice) {
            if ($stClass === 'mnm-status--closed') {
                $label = $isNotice ? 'notice' : 'memo';
                $html .= '<span class="mnm-action disabled" title="Cannot delete a closed ' . e($label) . '">'
                    . '<i class="bi bi-trash3"></i><span>Delete</span></span>';
            } else {
                $deleteId = $isNotice ? $row->notice_id : $row->memo_id;
                $html .= '<a href="javascript:void(0)" class="mnm-action mnm-delete-record" style="color:#d92d20;"'
                    . ' data-id="' . e((string) $deleteId) . '" data-type="' . ($isNotice ? 'notice' : 'memo') . '" title="Delete">'
                    . '<i class="bi bi-trash3"></i><span>Delete</span></a>';
            }
        }

        $html .= '</div>';

        return $html;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('mnmTable')
            ->addTableClass('table align-middle mb-0')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'ordering' => false,
                'searching' => false,
                'pageLength' => 10,
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'language' => [
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ items',
                    'infoEmpty' => 'No records found',
                    'infoFiltered' => '',
                    'zeroRecords' => $this->emptyStateHtml(),
                    'paginate' => [
                        'first' => 'First',
                        'last' => 'Last',
                        'next' => 'Next',
                        'previous' => 'Previous',
                    ],
                ],
                'dom' => '<"row align-items-center mb-2"<"col-12 col-md-4"l>>rt<"row align-items-center mt-2"<"col-12 col-md-5"i><"col-12 col-md-7"p>>',
            ]);
    }

    private function emptyStateHtml(): string
    {
        return '<div class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No records found</div>';
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->orderable(false)->searchable(false),
            Column::make('course_name')->title('Program Name')->orderable(false)->searchable(false)->addClass('fw-medium'),
            Column::make('student_name')->title('Participant Name')->orderable(false)->searchable(false)->addClass('fw-medium'),
            Column::computed('type')->title('Type')->orderable(false)->searchable(false),
            Column::make('session_date')->title('Session Date')->orderable(false)->searchable(false),
            Column::make('topic_name')->title('Topic')->orderable(false)->searchable(false),
            Column::make('discussion_name')->title('Conclusion Type')->orderable(false)->searchable(false),
            Column::make('conclusion_remark')->title('Conclusion Remark')->orderable(false)->searchable(false),
            Column::make('created_date')->title('Created Date')->orderable(false)->searchable(false),
            Column::computed('status')->title('Status')->orderable(false)->searchable(false),
            Column::computed('action')->title('Action')->orderable(false)->searchable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'MemoNoticeManagement_' . date('YmdHis');
    }
}
