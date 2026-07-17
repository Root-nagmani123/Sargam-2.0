<?php
 
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{ClassSessionMaster, CourseGroupTimetableMapping,CourseMaster, FacultyMaster, VenueMaster, SubjectMaster, SubjectModuleMaster, CalendarEvent, MemoTypeMaster,Timetable, CourseAttendanceNoticeMap};

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\MemoNoticeTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\NotificationService;
use App\Services\NotificationReceiverService;
 
class CourseAttendanceNoticeMapController extends Controller
{
    //
   public function index(Request $request)
{
    // Get filter parameters
    $programNameFilter = $request->get('program_name', '');
    $typeFilter = $request->get('type', '');
    $statusFilter = $request->get('status', '');
    $searchFilter = $request->get('search', '');
    $fromDateFilter = $request->get('from_date', '');
    $toDateFilter = $request->get('to_date', '');
    
    // Set default to today's date if no date filters are provided
    if (empty($fromDateFilter) && empty($toDateFilter)) {
        $fromDateFilter = Carbon::today()->toDateString();
        $toDateFilter = Carbon::today()->toDateString();
    }

    // An Officer Trainee must only ever see their own notices/memos.
    $isOfficerTrainee = isOfficerTraineeUser();
    $ownStudentPk = $isOfficerTrainee ? Auth::user()->user_id : null;

    // Get initial notice records with course name
    // Start from student_notice_status so direct notices (course_student_attendance_pk=0) are included
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

    // Apply filters on notices query
    if ($isOfficerTrainee) {
        $noticesQuery->where('sns.student_pk', $ownStudentPk);
    }

    if ($programNameFilter) {
        $noticesQuery->where('sns.course_master_pk', $programNameFilter);
    }

    if ($typeFilter !== null && $typeFilter !== '') {
        if ($typeFilter == '1') {
            // Notice: get notices that haven't been converted to a Memo. A closed
            // Notice (status == 2) can mean either "closed directly as a Notice" or
            // "converted to a Memo" — those are indistinguishable from status alone,
            // so check for the absence of a student_memo_status row instead.
            $noticesQuery->where('sns.notice_memo', 1)
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('student_memo_status')
                        ->whereColumn('student_memo_status.student_notice_status_pk', 'sns.pk');
                });
        }
        // if $typeFilter == '0' (memo), we'll fetch memos separately later
    }

    // Status filter is intentionally NOT applied here. A notice with sns.status == 2
    // may either be a Notice closed directly, or a Notice converted to a Memo whose
    // own open/closed state lives in student_memo_status — the two are indistinguishable
    // from sns.status alone. Filtering here would also conflict with the "not converted
    // to memo" condition above (sns.status != 2) when Type=Notice + Status=Close is
    // selected together, always returning zero rows. The collection-level filter below
    // (after memo substitution) applies status correctly against the resolved item.

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
            $memos = $memos->filter(function($item) use ($programNameFilter) {
                return isset($item->course_master_pk) && $item->course_master_pk == $programNameFilter;
            });
        }

        if ($typeFilter !== null && $typeFilter !== '') {
            if ($typeFilter == '1') {
                $memos = $memos->filter(function($item) {
                    return isset($item->notice_memo) && $item->notice_memo == 1;
                });
            }
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            if ($statusFilter == '1') {
                $memos = $memos->filter(function($item) {
                    return isset($item->status) && $item->status == 1;
                });
            } elseif ($statusFilter == '0') {
                $memos = $memos->filter(function($item) {
                    return isset($item->status) && $item->status == 2;
                });
            }
        }

        if ($searchFilter !== null && $searchFilter !== '') {
            $memos = $memos->filter(function($item) use ($searchFilter) {
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
            $memos = $memos->filter(function($item) use ($fromDateFilter, $toDateFilter) {
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

   
   

    // Get memo type and venues if needed
    $venue = VenueMaster::where('active_inactive', 1)->get();
    $memo_master = MemoTypeMaster::where('active_inactive', 1)->get();
    // Conclusion types for the chat panel's "End Chat" action.
    $conclusions = \App\Models\MemoConclusionMaster::where('active_inactive', 1)->get();
    
    // Get courses for Program Name filter - only active courses (active_inactive = 1 and end_date > now)
    $courses = CourseMaster::where('active_inactive', 1)
        ->where('end_date', '>', now())
        ->orderBy('course_name', 'asc')
        ->get();

    // Paginate the collection
    $perPage = 10;
    $currentPage = request()->get('page', 1);
    $pagedData = $memos->slice(($currentPage - 1) * $perPage, $perPage)->values();
    $memos = new \Illuminate\Pagination\LengthAwarePaginator(
        $pagedData,
        $memos->count(),
        $perPage,
        $currentPage,
        ['path' => request()->url(), 'query' => request()->query()]
    );
$noticeCount = $memos->groupBy(function($item) {
    return $item->student_pk . '_' . $item->course_master_pk;
})->map(function ($group) {
    return $group->where('type_notice_memo', 'Notice')->count();
});
    $canManageMemoNotice = $this->userCanManageMemoNotice();
    return view('admin.courseAttendanceNoticeMap.index', compact('memos', 'venue', 'memo_master', 'conclusions', 'courses', 'programNameFilter', 'typeFilter', 'statusFilter', 'searchFilter', 'fromDateFilter', 'toDateFilter','noticeCount', 'canManageMemoNotice'));
}

    public function exportPdf(Request $request)
    {
        $data = $this->noticeMemoExportData($request);

        // Generate PDF
        $pdf = Pdf::loadView('admin.courseAttendanceNoticeMap.export_pdf', $data)
            ->setPaper('a4', 'landscape');

        $fileName = 'Notice_Memo_Report_' . date('Y-m-d_His') . '.pdf';
        return $pdf->download($fileName);
    }

    /**
     * Download the Send Memo / Notice listing as a CSV, using the same
     * filtered dataset as the PDF export, in the mess-style layout:
     * a title block (report name + applied filters), the column-header row, then data rows.
     */
    public function exportCsv(Request $request)
    {
        $data = $this->noticeMemoExportData($request);
        $memos = $data['memos'];

        $courseName = optional($data['selectedCourse'])->course_name ?? 'All';
        $typeText = $data['typeFilter'] === '1' ? 'Notice' : ($data['typeFilter'] === '0' ? 'Memo' : 'All');
        $statusText = $data['statusFilter'] === '1' ? 'Open' : ($data['statusFilter'] === '0' ? 'Close' : 'All');
        $dateRange = ($data['fromDateFilter'] || $data['toDateFilter'])
            ? (($data['fromDateFilter'] ? Carbon::parse($data['fromDateFilter'])->format('d-m-Y') : '—') . ' to ' . ($data['toDateFilter'] ? Carbon::parse($data['toDateFilter'])->format('d-m-Y') : '—'))
            : 'All Dates';

        $headers = ['Name', 'OT/Participant Code', 'Cadre', 'Infraction', 'Date of Infraction', 'Remarks'];

        $rows = [];
        foreach ($memos as $memo) {
            $sessionDate = $memo->session_date ?? $memo->date_ ?? null;

            $rows[] = [
                $memo->student_name ?? 'N/A',
                $memo->generated_OT_code ?? 'N/A',
                $memo->cadre_name ?? 'N/A',
                $memo->topic_name ?? 'N/A',
                $sessionDate ? Carbon::parse($sessionDate)->format('d M Y') : 'N/A',
                $memo->message ?? '',
            ];
        }

        $titleBlock = [
            ['Send Memo / Notice'],
            ['Date Range', $dateRange, 'Program', $courseName, 'Type', $typeText, 'Status', $statusText],
            ['Generated On', now()->format('d-m-Y H:i:s')],
            [],
        ];

        $fileName = 'send-memo-notice-' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($titleBlock, $headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            foreach ($titleBlock as $line) {
                fputcsv($out, $line);
            }
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Build the filtered Notice/Memo collection + filter context shared by the
     * PDF and CSV exports. Mirrors the filter logic of exportPdf/index.
     */
    private function noticeMemoExportData(Request $request): array
    {
        // Get filter parameters (same as index method)
        $programNameFilter = $request->get('program_name', '');
        $typeFilter = $request->get('type', '');
        $statusFilter = $request->get('status', '');
        $searchFilter = $request->get('search', '');
        $fromDateFilter = $request->get('from_date', '');
        $toDateFilter = $request->get('to_date', '');

        // Set default to today's date if no date filters are provided
        if (empty($fromDateFilter) && empty($toDateFilter)) {
            $fromDateFilter = Carbon::today()->toDateString();
            $toDateFilter = Carbon::today()->toDateString();
        }

        // An Officer Trainee must only ever export their own notices/memos.
        $isOfficerTrainee = isOfficerTraineeUser();
        $ownStudentPk = $isOfficerTrainee ? Auth::user()->user_id : null;

        // Get initial notice records with course name
        $noticesQuery = DB::table('course_student_attendance as csa')
            ->join('student_notice_status as sns', 'sns.course_student_attendance_pk', '=', 'csa.pk')
            ->leftJoin('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')
            ->leftJoin('cadre_master as crd', 'sm.cadre_master_pk', '=', 'crd.pk')
            ->leftJoin('timetable as t', 'sns.subject_topic', '=', 't.pk')
            ->leftJoin('course_master as cm', 'sns.course_master_pk', '=', 'cm.pk')
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
                'sm.generated_OT_code',
                'crd.cadre_name',
                't.subject_topic as topic_name',
                't.START_DATE as session_date',
                'cm.course_name',
                'sns.created_date',
                DB::raw('"Notice" as type_notice_memo')
            );

        // Apply filters on notices query
        if ($isOfficerTrainee) {
            $noticesQuery->where('sns.student_pk', $ownStudentPk);
        }

        if ($programNameFilter) {
            $noticesQuery->where('sns.course_master_pk', $programNameFilter);
        }

        if ($typeFilter !== null && $typeFilter !== '') {
            if ($typeFilter == '1') {
                // Notice: get notices that haven't been converted to a Memo. A closed
                // Notice (status == 2) can mean either "closed directly as a Notice" or
                // "converted to a Memo" — those are indistinguishable from status alone,
                // so check for the absence of a student_memo_status row instead.
                $noticesQuery->where('sns.notice_memo', 1)
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))
                            ->from('student_memo_status')
                            ->whereColumn('student_memo_status.student_notice_status_pk', 'sns.pk');
                    });
            }
        }

        // Status filter is intentionally NOT applied here. A notice with sns.status == 2
        // may either be a Notice closed directly, or a Notice converted to a Memo whose
        // own open/closed state lives in student_memo_status — the two are indistinguishable
        // from sns.status alone. Filtering here would also conflict with the "not converted
        // to memo" condition above (sns.status != 2) when Type=Notice + Status=Close is
        // selected together, always returning zero rows. The collection-level filter below
        // (after memo substitution) applies status correctly against the resolved item.

        // Apply date range filter by session date
        if ($fromDateFilter) {
            $noticesQuery->whereDate('t.START_DATE', '>=', $fromDateFilter);
        }
        if ($toDateFilter) {
            $noticesQuery->whereDate('t.START_DATE', '<=', $toDateFilter);
        }

        $notices = $noticesQuery->get();

        $memos = collect();

        // If filtering for Memo type, query student_memo_status directly
        if ($typeFilter == '0') {
            $memoQuery = DB::table('student_memo_status')
                ->leftJoin('student_master as sm', 'student_memo_status.student_pk', '=', 'sm.pk')
                ->leftJoin('cadre_master as crd', 'sm.cadre_master_pk', '=', 'crd.pk')
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
                    'sm.generated_OT_code',
                    'crd.cadre_name',
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
            foreach ($notices as $notice) {
                if ($notice->status == 2) {
                    $memoDataQuery = DB::table('student_memo_status')
                        ->leftJoin('student_master as sm', 'student_memo_status.student_pk', '=', 'sm.pk')
                        ->leftJoin('cadre_master as crd', 'sm.cadre_master_pk', '=', 'crd.pk')
                        ->leftJoin('student_notice_status as sns', 'student_memo_status.student_notice_status_pk', '=', 'sns.pk')
                        ->leftJoin('timetable as t', 'sns.subject_topic', '=', 't.pk')
                        ->leftJoin('memo_conclusion_master as mcm', 'student_memo_status.memo_conclusion_master_pk', '=', 'mcm.pk')
                        ->leftJoin('course_master as cm', 'student_memo_status.course_master_pk', '=', 'cm.pk')
                        ->where('student_memo_status.student_notice_status_pk', $notice->notice_id);

                    if ($fromDateFilter) {
                        $memoDataQuery->whereDate('t.START_DATE', '>=', $fromDateFilter);
                    }
                    if ($toDateFilter) {
                        $memoDataQuery->whereDate('t.START_DATE', '<=', $toDateFilter);
                    }

                    $memoData = $memoDataQuery->select(
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
                            'sm.generated_OT_code',
                            'crd.cadre_name',
                            't.subject_topic as topic_name',
                            't.START_DATE as session_date',
                            'mcm.discussion_name',
                            'cm.course_name'
                        )
                        ->first();

                    if ($memoData) {
                        $memos->push($memoData);
                    } else {
                        $memos->push($notice);
                    }
                } else {
                    $memos->push($notice);
                }
            }
        }

        // Apply additional filters to final collection
        if ($typeFilter != '0') {
            if ($programNameFilter) {
                $memos = $memos->filter(function($item) use ($programNameFilter) {
                    return isset($item->course_master_pk) && $item->course_master_pk == $programNameFilter;
                });
            }

            if ($typeFilter !== null && $typeFilter !== '') {
                if ($typeFilter == '1') {
                    $memos = $memos->filter(function($item) {
                        return isset($item->notice_memo) && $item->notice_memo == 1;
                    });
                }
            }

            if ($statusFilter !== null && $statusFilter !== '') {
                if ($statusFilter == '1') {
                    $memos = $memos->filter(function($item) {
                        return isset($item->status) && $item->status == 1;
                    });
                } elseif ($statusFilter == '0') {
                    $memos = $memos->filter(function($item) {
                        return isset($item->status) && $item->status == 2;
                    });
                }
            }

            if ($searchFilter !== null && $searchFilter !== '') {
                $memos = $memos->filter(function($item) use ($searchFilter) {
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
                $memos = $memos->filter(function($item) use ($fromDateFilter, $toDateFilter) {
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

        // Get course name for filter display
        $selectedCourse = null;
        if ($programNameFilter) {
            $selectedCourse = CourseMaster::find($programNameFilter);
        }

        return [
            'memos' => $memos,
            'programNameFilter' => $programNameFilter,
            'typeFilter' => $typeFilter,
            'statusFilter' => $statusFilter,
            'searchFilter' => $searchFilter,
            'fromDateFilter' => $fromDateFilter,
            'toDateFilter' => $toDateFilter,
            'selectedCourse' => $selectedCourse,
        ];
    }

    public function index_bkp()
    {
     $memos = DB::table('student_notice_status')
    ->join('course_student_attendance as csa', 'student_notice_status.course_student_attendance_pk', '=', 'csa.pk')
    ->join('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')  
    ->join('timetable as t', 'student_notice_status.subject_topic', '=', 't.pk')          
    ->select(
        'student_notice_status.pk as memo_notice_id',
        'student_notice_status.course_master_pk', 'student_notice_status.date_',
        'student_notice_status.subject_master_pk','student_notice_status.subject_topic',
        'student_notice_status.venue_id', 'student_notice_status.class_session_master_pk',
        'student_notice_status.faculty_master_pk','student_notice_status.message','student_notice_status.notice_memo',
        'student_notice_status.status',
        'sm.display_name as student_name','sm.pk as student_id','t.subject_topic as topic_name'
    )
    ->get();
    $venue = [];
    $memo_master = [];
if($memos[0]->status == 2){
    $venue = VenueMaster::where('active_inactive', 1)->get();
    // print_r($venue);die;
    $memo_master = MemoTypeMaster::where('active_inactive', 1)->get();
}

                    
                    // print_r($memos);die;
         return view('admin.courseAttendanceNoticeMap.index', compact('memos', 'venue', 'memo_master'));
    }
public function create(Request $request)
{
    $activeCourses = CourseMaster::where('active_inactive', '1')
        ->where('end_date', '>', now())
        ->get();
// print_r($activeCourses);die;
    return view('admin.courseAttendanceNoticeMap.create', compact('activeCourses'));
}
public function getTemplateByCourse(Request $request)
{
    $courseId = $request->course_id;
    $type     = $request->type; // 'Notice' or 'Memo'


    $template = DB::table('memo_notice_templates')
        ->where('course_master_pk', $courseId)
        ->where('memo_notice_type', $type)
        ->where('active_inactive', 1)
        ->whereNull('deleted_at')
        ->first();

    return response()->json($template);
}

/**
 * List active templates for a course + type ('Notice' or 'Memo') so the sender can
 * pick one at send time. Returns [{pk, title}].
 */
public function getTemplatesByType(Request $request)
{
    $courseId         = $request->course_id;
    $type             = $request->type === 'Memo' ? 'Memo' : 'Notice';
    $memoTypeMasterPk = $request->memo_type_master_pk;

    if (! $courseId) {
        return response()->json([]);
    }

    $query = DB::table('memo_notice_templates')
        ->where('course_master_pk', $courseId)
        ->where('memo_notice_type', $type)
        ->where('active_inactive', 1)
        ->whereNull('deleted_at');

    // A Memo template may be pinned to a specific Memo Type, or left
    // type-agnostic (memo_type_master_pk null) as a fallback when no
    // type-specific one exists — mirrors the Discipline Memo precedence.
    if ($type === 'Memo' && $memoTypeMasterPk) {
        $query->where(function ($q) use ($memoTypeMasterPk) {
            $q->whereNull('memo_type_master_pk')
              ->orWhere('memo_type_master_pk', $memoTypeMasterPk);
        })->orderByRaw('memo_type_master_pk IS NULL'); // type-specific first, type-agnostic fallback last
    }

    $templates = $query->orderBy('title')
        ->get(['pk', 'title', 'content', 'director_name', 'director_designation', 'signature_image', 'memo_type_master_pk']);

    return response()->json($templates);
}

public function getStudentsByCourse(Request $request)
{
    $courseId = (int) $request->course_id;
    if (!$courseId) {
        return response()->json([]);
    }

    $students = DB::table('student_master_course__map as a')
        ->join('student_master as s', 'a.student_master_pk', '=', 's.pk')
        ->where('a.course_master_pk', $courseId)
        ->where('a.active_inactive', 1)
        ->whereNotNull('s.display_name')
        ->where('s.display_name', '!=', '')
        ->select('s.pk', 's.display_name', 's.generated_OT_code')
        ->orderBy('s.display_name')
        ->get();

    return response()->json($students);
}

public function getSubjectByCourse(Request $request)
{
    $courseId = $request->course_id;
    $date     = $request->date; // optional — filter by timetable date

    $query = DB::table('timetable as t')
        ->join('subject_master as s', 't.subject_master_pk', '=', 's.pk')
        ->select('s.pk as subject_id', 's.subject_name')
        ->where('t.course_master_pk', $courseId)
        ->where('s.active_inactive', 1);

    if ($date) {
        $query->whereDate('t.START_DATE', $date);
    }

    $subjects = $query->groupBy('s.pk', 's.subject_name')->get();

    if ($subjects->isEmpty()) {
        return '<option value="">No subjects found for selected date</option>';
    }

    $html = '<option value="">Select Subject</option>';
    foreach ($subjects as $subject) {
        $html .= '<option value="' . $subject->subject_id . '">' . e($subject->subject_name) . '</option>';
    }
    return $html;
}
function getTopicBysubject(Request $request)
{
    $courseId  = $request->course_id;
    $subjectId = $request->subject_master_id;
    $date      = $request->date; // optional — filter by timetable date

    $query = DB::table('timetable as t')
        ->where('t.course_master_pk', $courseId)
        ->where('t.subject_master_pk', $subjectId)
        ->select('t.pk', 't.subject_topic');

    if ($date) {
        $query->whereDate('t.START_DATE', $date);
    }

    $topics = $query->get();

    if ($topics->isEmpty()) {
        return '<option value="">No topics found for selected date</option>';
    }
    $html = '<option value="">Select Topic</option>';
    foreach ($topics as $topic) {
        $html .= '<option value="' . $topic->pk . '">' . e($topic->subject_topic) . '</option>';
    }
    return $html;
}
public function gettimetableDetailsBytopic(Request $request)
{
    $topicId = $request->topic_id;
    $timetable = DB::table('timetable as t')
        ->leftJoin('venue_master as v', 't.venue_id', '=', 'v.venue_id')
        ->where('t.pk', $topicId)
        ->select(
            't.*',
            'v.venue_name',
            't.class_session as shift_name'
        )
        ->first();

    if ($timetable) {
        $facultyIds = get_timetable_faculty_ids($timetable);
        $timetable->faculty_name = get_timetable_faculty_names($timetable, 'N/A');

        if (empty($timetable->faculty_master) && !empty($facultyIds)) {
            $timetable->faculty_master = json_encode($facultyIds);
        }
    }

    return response()->json($timetable);
}

/**
 * Sessions that have a timetable entry for the given course on the given date.
 */
public function getSessionsByCourse(Request $request)
{
    $courseId = $request->course_id;
    $date     = $request->date;

    // timetable.class_session stores the raw shift text (e.g. "10:35 to 11:30"),
    // matching class_session_master.shift_time — it is NOT a class_session_master.pk FK.
    $query = DB::table('timetable as t')
        ->select('t.class_session')
        ->where('t.course_master_pk', $courseId)
        ->whereNotNull('t.class_session')
        ->where('t.class_session', '!=', '');

    if ($date) {
        $query->whereDate('t.START_DATE', $date);
    }

    $sessions = $query->distinct()->orderBy('t.class_session')->get();

    if ($sessions->isEmpty()) {
        return '<option value="">No sessions found for selected date</option>';
    }

    $labelsByShiftTime = DB::table('class_session_master')->pluck('shift_name', 'shift_time');

    $html = '<option value="">Select Session</option>';
    foreach ($sessions as $session) {
        $value = $session->class_session;
        // Shift-based sessions store just the shift name; show its time range
        // alongside the name so it's identifiable the same way raw time-range
        // sessions (e.g. "01:53 PM - 03:53 PM") already are.
        $label = isset($labelsByShiftTime[$value]) ? $labelsByShiftTime[$value] . ' (' . $value . ')' : $value;
        $html .= '<option value="' . e($value) . '">' . e($label) . '</option>';
    }
    return $html;
}

/**
 * Venues that have a timetable entry for the given course/date/session.
 */
public function getVenuesBySession(Request $request)
{
    $courseId  = $request->course_id;
    $date      = $request->date;
    $sessionPk = $request->session_pk;

    $query = DB::table('timetable as t')
        ->join('venue_master as v', 't.venue_id', '=', 'v.venue_id')
        ->select('v.venue_id', 'v.venue_name')
        ->where('t.course_master_pk', $courseId)
        ->where('t.class_session', $sessionPk);

    if ($date) {
        $query->whereDate('t.START_DATE', $date);
    }

    $venues = $query->groupBy('v.venue_id', 'v.venue_name')->get();

    if ($venues->isEmpty()) {
        return '<option value="">No venues found for selected session</option>';
    }

    $html = '<option value="">Select Venue</option>';
    foreach ($venues as $venue) {
        $html .= '<option value="' . $venue->venue_id . '">' . e($venue->venue_name) . '</option>';
    }
    return $html;
}

/**
 * Resolve the single timetable row for course/date/session/venue and return
 * its subject/topic/faculty so the Add Notice form can auto-fill them.
 */
public function getTimetableDetailsBySessionVenue(Request $request)
{
    $courseId  = $request->course_id;
    $date      = $request->date;
    $sessionPk = $request->session_pk;
    $venueId   = $request->venue_id;

    $timetable = DB::table('timetable as t')
        ->leftJoin('subject_master as s', 't.subject_master_pk', '=', 's.pk')
        ->where('t.course_master_pk', $courseId)
        ->where('t.class_session', $sessionPk)
        ->where('t.venue_id', $venueId)
        ->when($date, function ($q) use ($date) {
            $q->whereDate('t.START_DATE', $date);
        })
        ->select('t.*', 's.subject_name')
        ->first();

    if (!$timetable) {
        return response()->json(null);
    }

    $timetable->topic_id = $timetable->pk;
    $timetable->faculty_name = get_timetable_faculty_names($timetable, 'N/A');
    $facultyIds = get_timetable_faculty_ids($timetable);
    if (empty($timetable->faculty_master) && !empty($facultyIds)) {
        $timetable->faculty_master = json_encode($facultyIds);
    }

    return response()->json($timetable);
}

public function conversation($id, $type)
{
    if (!$id || !is_numeric($id)) {
        return redirect()->back()->with('error', 'Invalid Memo/Notice ID.');
    }

    $memoNotice = collect(); // default empty collection
$memo_conclusion_master = collect(); // default empty collection
    if ($type == 'notice') {
        // $memoNotice = DB::table('notice_message_student_decip_incharge as nmsdi')
        //     ->leftjoin('student_notice_status as sns', 'nmsdi.student_notice_status_pk', '=', 'sns.pk')
        //     ->leftjoin('course_student_attendance as csa', 'sns.course_student_attendance_pk', '=', 'csa.pk')
        //     ->leftjoin('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')
        //     ->where('nmsdi.student_notice_status_pk', $id)
        //     ->orderBy('nmsdi.created_date', 'asc')
        //     ->select(
        //         'nmsdi.*',
        //         'sns.pk as notice_id',
        //         'sns.status as notice_status',
        //         'sm.pk as student_id',
        //         'sm.display_name as student_name'
        //     )
        //     ->get();
        $memoNotice = DB::table('notice_message_student_decip_incharge as n')
    ->leftJoin('student_notice_status as sns', 'n.student_notice_status_pk', '=', 'sns.pk')
    ->leftJoin('course_student_attendance as csa', 'sns.course_student_attendance_pk', '=', 'csa.pk')
    ->leftJoin('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')
    ->where('n.student_notice_status_pk', $id)
    ->orderBy('n.created_date', 'asc')
    ->select(
        'n.*',
        'sns.pk as notice_id',
        'sns.status as notice_status',
        'sm.pk as student_id',
        'sm.display_name as student_name'
    )
    ->get();

         $template_details = DB::table('student_notice_status as sns')
    ->leftJoin('timetable as t', 'sns.subject_topic', '=', 't.pk')
    ->leftJoin('venue_master as v', 't.venue_id', '=', 'v.venue_id')
    ->leftJoin('student_master as sm', 'sns.student_pk', '=', 'sm.pk')
    ->leftJoin('course_master as cm', 'sns.course_master_pk', '=', 'cm.pk')
    ->leftJoin('faculty_master as fm', DB::raw('fm.pk'), '=', DB::raw("CASE WHEN sns.faculty_master_pk LIKE '[%' THEN JSON_UNQUOTE(JSON_EXTRACT(sns.faculty_master_pk, '$[0]')) ELSE sns.faculty_master_pk END"))
    ->leftJoin('memo_notice_templates as mnt', function($join) {
        // Prefer the template pinned at send time; else the latest active Notice template for the course.
        $join->on(DB::raw('mnt.pk'), '=', DB::raw("COALESCE(sns.memo_notice_template_pk, (SELECT t2.pk FROM memo_notice_templates t2 WHERE t2.course_master_pk = sns.course_master_pk AND t2.memo_notice_type = 'Notice' AND t2.active_inactive = 1 AND t2.deleted_at IS NULL ORDER BY t2.pk DESC LIMIT 1))"));
    })
    ->where('sns.pk', $id)
    ->select(
        'sns.student_pk',
        'sns.course_master_pk',
        't.subject_topic',
        'v.venue_name',
        't.class_session as session_time',
        'sns.date_ as session_date',
        'sm.display_name',
        'sm.generated_OT_code',
        'cm.course_name',
        'fm.full_name as faculty_name',
        'mnt.content',
        'mnt.director_name',
        'mnt.director_designation',
        'mnt.signature_image',
        'sns.template_snapshot',
        'sns.conclusion_type_pk',
        'sns.conclusion_remark',
        'sns.mark_of_deduction',
        'sns.status as notice_current_status'
    )
    ->first();
    // Content frozen at send time wins over the live-joined (current) template.
    $template_details = apply_memo_notice_template_snapshot($template_details);
    $memo_conclusion_master = DB::table('memo_conclusion_master')->where('active_inactive', 1)->get();

    } elseif ($type == 'memo') {
        $memoNotice = DB::table('memo_message_student_decip_incharge as mmsdi')
            ->leftjoin('student_memo_status as sms', 'mmsdi.student_memo_status_pk', '=', 'sms.pk')
            ->leftjoin('student_master as sm', 'sms.student_pk', '=', 'sm.pk')
            ->where('mmsdi.student_memo_status_pk', $id)
            ->orderBy('mmsdi.created_date', 'asc')
            ->select(
                'mmsdi.*',
                'sms.pk as notice_id',
                'sms.communication_status as notice_status',
                'sms.communication_status',
                
                'sm.pk as student_id',
                'sm.display_name as student_name'
            )
            ->get();
            $memo_conclusion_master = DB::table('memo_conclusion_master')->where('active_inactive', 1)->get();

             $template_details = DB::table('student_memo_status as sms')
    ->leftJoin('student_notice_status as sns', 'sms.student_notice_status_pk', '=', 'sns.pk')
    ->leftJoin('timetable as t', 'sns.subject_topic', '=', 't.pk')
    ->leftJoin('venue_master as v', 't.venue_id', '=', 'v.venue_id')
    ->leftJoin('student_master as sm', 'sms.student_pk', '=', 'sm.pk')
    ->leftJoin('course_master as cm', 'sms.course_master_pk', '=', 'cm.pk')
    ->leftJoin('faculty_master as fm', DB::raw('fm.pk'), '=', DB::raw("CASE WHEN sns.faculty_master_pk LIKE '[%' THEN JSON_UNQUOTE(JSON_EXTRACT(sns.faculty_master_pk, '$[0]')) ELSE sns.faculty_master_pk END"))
    ->leftJoin('memo_notice_templates as mnt', function($join) {
        // Prefer the template pinned at send time; else the latest active Memo template for the course.
        $join->on(DB::raw('mnt.pk'), '=', DB::raw("COALESCE(sms.memo_notice_template_pk, (SELECT t2.pk FROM memo_notice_templates t2 WHERE t2.course_master_pk = sms.course_master_pk AND t2.memo_notice_type = 'Memo' AND t2.active_inactive = 1 AND t2.deleted_at IS NULL ORDER BY t2.pk DESC LIMIT 1))"));
    })
    ->where('sms.pk', $id)
    ->select(
        'sms.student_pk',
        'sms.course_master_pk',
        't.subject_topic',
        'v.venue_name',
        't.class_session as session_time',
        'sns.date_ as session_date',
        'sm.display_name',
        'sm.generated_OT_code',
        'cm.course_name',
        'fm.full_name as faculty_name',
        'mnt.content',
        'mnt.director_name',
        'mnt.director_designation',
        'mnt.signature_image',
        'sms.template_snapshot',
        'sms.memo_conclusion_master_pk as conclusion_type_pk',
        'sms.conclusion_remark',
        'sms.mark_of_deduction',
        'sms.communication_status as notice_current_status'
    )
    ->first();
    // Content frozen at send time wins over the live-joined (current) template.
    $template_details = apply_memo_notice_template_snapshot($template_details);

    }

    // Build the full list of sessions this student has been noticed for on this course,
    // grouped by date, so the notice's session table reflects all of them (not just this one).
    $sessionRows = collect();
    if (!empty($template_details) && !empty($template_details->student_pk) && !empty($template_details->course_master_pk)) {
        $sessionRows = DB::table('student_notice_status as sns2')
            ->leftJoin('timetable as t2', 'sns2.subject_topic', '=', 't2.pk')
            ->leftJoin('venue_master as v2', 't2.venue_id', '=', 'v2.venue_id')
            ->where('sns2.student_pk', $template_details->student_pk)
            ->where('sns2.course_master_pk', $template_details->course_master_pk)
            ->orderBy('sns2.date_')
            ->select(
                'sns2.date_ as session_date',
                't2.subject_topic',
                'v2.venue_name',
                't2.class_session as session_time'
            )
            ->get()
            ->groupBy(function ($row) {
                return \Carbon\Carbon::parse($row->session_date)->format('Y-m-d');
            })
            ->map(function ($rows) {
                return (object) [
                    'session_date'  => $rows->first()->session_date,
                    'session_count' => $rows->count(),
                    'topics'        => $rows->pluck('subject_topic')->filter()->unique()->implode(', '),
                    'venues'        => $rows->pluck('venue_name')->filter()->unique()->implode(', '),
                    'sessions'      => $rows->pluck('session_time')->filter()->unique()->implode(', '),
                ];
            })
            ->values();
    }

    // Multiple distinct admins/faculty can post in the same conversation, so
    // resolve each sender's real name + role instead of collapsing them all
    // to a generic "Admin" label (same helper the Discipline memo chat uses).
    $memoNotice->transform(function ($item) {
        $identity = resolve_chat_sender_identity($item->created_by, $item->role_type);
        $item->display_name = $identity['display_name'];
        $item->role_name = $identity['role_name'];
        return $item;
    });

// print_r($memoNotice);die;
    return view('admin.courseAttendanceNoticeMap.conversation', compact('id', 'memoNotice', 'type', 'memo_conclusion_master','template_details', 'sessionRows'));
}

function conversation_bkp($id,$type){

    // Validate the ID
    if (!$id || !is_numeric($id)) {
        return redirect()->back()->with('error', 'Invalid Memo/Notice ID.');
    }
    // Fetch the memo/notice details
    if($type == 'notice'){
 $memoNotice = DB::table('notice_message_student_decip_incharge as nmsdi')
    ->join('student_notice_status as sns', 'nmsdi.student_notice_status_pk', '=', 'sns.pk')
    ->join('course_student_attendance as csa', 'sns.course_student_attendance_pk', '=', 'csa.pk')
    ->join('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')
    ->where('nmsdi.student_notice_status_pk', $id)
    ->orderBy('nmsdi.created_date', 'asc')
    ->select(
        'nmsdi.*',
        'sns.pk as notice_id',
        'sns.status as notice_status',
        'sm.pk as student_id',
        'sm.display_name as student_name'
    )
    ->get();

$memoNotice->transform(function ($item) {
    if ($item->role_type == 'f') {
        // Admin (From users table)
        $creator = DB::table('users')
            ->where('id', $item->created_by)
            ->first();
        $item->display_name = 'Admin';
    } elseif ($item->role_type == 's') {
        // Student (From student_master table)
        $student = DB::table('student_master')
            ->where('pk', $item->created_by)
            ->first();
        $item->display_name = $student ? $student->display_name : 'Student';
    } else {
        $item->display_name = 'Unknown';
    }

    return $item;
});
    }else{

    }
// print_r($memoNotice);die;
     return view('admin.courseAttendanceNoticeMap.conversation', compact('id','memoNotice'));
}

//memo conversation


public function getStudentAttendanceBytopic(Request $request)
{
    try {
        $topicId = $request->topic_id;

        if (!$topicId) {
            return response()->json([
                'status' => false,
                'message' => 'Topic ID is required.'
            ]);
        }

        // Cast topicId to integer to ensure proper comparison
        $topicId = (int) $topicId;

        // Students who already have a notice for this topic (any status)
        $alreadyNoticedStudentPks = DB::table('student_notice_status')
            ->where('subject_topic', $topicId)
            ->pluck('student_pk')
            ->toArray();

        // Students marked Late (label '2') or Absent (label '3') for this session.
        // NOTE: course_student_attendance.status is ENUM('0','1',...,'7'). Because the
        // labels are numeric strings, a plain `= 3` / `IN (2,3)` compares against the
        // ENUM *index* (off-by-one) and silently drops Absent. CAST(... AS CHAR) forces
        // a label comparison so both Late and Absent are matched reliably.
        //
        // The attendance row itself is the source of truth for who was late/absent in
        // this timetable session, so we no longer inner-join student_course_group_map
        // (that group-PK-only join could drop absent defaulters whose group row was
        // inactive/mismatched, which is what hid them from this list).
        $attendance = DB::table('course_student_attendance as a')
                ->join('student_master as s', 'a.Student_master_pk', '=', 's.pk')
                ->where('a.timetable_pk', $topicId)
                ->whereRaw("CAST(a.status AS CHAR) IN ('2', '3')")
                ->whereNotNull('s.pk')
                ->whereNotNull('s.display_name')
                ->where('s.display_name', '!=', '')
                ->when(!empty($alreadyNoticedStudentPks), function ($q) use ($alreadyNoticedStudentPks) {
                    $q->whereNotIn('s.pk', $alreadyNoticedStudentPks);
                })
                ->select(
                    'a.pk as studnet_pk',
                    's.pk as pk',
                    's.display_name as display_name',
                    's.generated_OT_code as generated_OT_code',
                    'a.status as attendance_status'
                )
                ->distinct()
                ->get();


        // If no students found, return empty array instead of error
        // This allows the UI to handle empty state gracefully
        if ($attendance->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No defaulter students found for this topic.',
                'students' => []
            ]);
        }

        // Format the attendance data
        $students = $attendance->map(function ($student) {
            $statusLabel = ((int) $student->attendance_status === 2) ? 'Late' : 'Absent';
            return [
                'pk'               => (int) $student->pk,
                'display_name'     => $student->display_name,
                'generated_OT_code'=> $student->generated_OT_code,
                'attendance_label' => $statusLabel,
            ];
        })->values(); // Reset array keys

        return response()->json([
            'status' => true,
            'message' => 'Student list fetched successfully.',
            'students' => $students
        ]);

    } catch (\Exception $e) {
        \Log::error('Error in getStudentAttendanceBytopic: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'status' => false,
            'message' => 'Error occurred while fetching student list.',
            'error' => $e->getMessage() // optional for debugging
        ]);
    }
}
public function store_memo_notice(Request $request)
{
   
    $validated = $request->validate([
        'course_master_pk' => 'required|exists:course_master,pk',
        'date_memo_notice' => 'required|date',
        'subject_master_id' => 'required|exists:subject_master,pk',
        'topic_id' => 'required|exists:timetable,pk',
        'venue_id' => 'nullable',
        'class_session_master_pk' => 'nullable',
        'faculty_master_pk' => 'nullable',
        'selected_student_list' => 'required|array|min:1',
        'Remark' => 'nullable|string|max:500',
        'submission_type' => 'required|in:1,2',
        'memo_notice_template_pk' => 'required|exists:memo_notice_templates,pk',
    ]);

    // Guard: only include memo_notice_template_pk if the column exists (migration may not have run)
    $hasTplCol = \Illuminate\Support\Facades\Schema::hasColumn('student_notice_status', 'memo_notice_template_pk');
    $hasSnapshotCol = \Illuminate\Support\Facades\Schema::hasColumn('student_notice_status', 'template_snapshot');
    // Freeze the chosen template's content now, so editing it later doesn't change what's already sent.
    $templateSnapshot = $hasSnapshotCol ? build_memo_notice_template_snapshot($validated['memo_notice_template_pk'] ?? null) : null;

    // ✅ Fetch all required student info in one query
    $students = DB::table('course_student_attendance as a')
        ->join('student_master as s', 'a.Student_master_pk', '=', 's.pk')
        ->whereIn('a.Student_master_pk', $validated['selected_student_list'])
        ->where('a.timetable_pk', $validated['topic_id'])
        ->select('a.pk as course_attendance_pk', 's.pk as student_pk')
        ->get()
        ->keyBy('student_pk'); // keyBy student_pk to avoid duplicates

    $data = [];
    // print_r($students);
    // print_r($validated['selected_student_list']);die;

    // Get already existing notices for these students on same topic to prevent duplicates
    $existingNotices = DB::table('student_notice_status')
        ->where('subject_topic', $validated['topic_id'])
        ->whereIn('student_pk', $students->pluck('student_pk')->toArray())
        ->pluck('student_pk')
        ->toArray();

    foreach ($students as $studentId) {
    // Skip if notice already exists for this student on this topic
    if (in_array($studentId->student_pk, $existingNotices)) {
        continue;
    }


            $row = [
                'course_master_pk'             => $validated['course_master_pk'],
                'student_pk'                   => $studentId->student_pk,
                'date_'                        => $validated['date_memo_notice'],
                'subject_master_pk'            => $validated['subject_master_id'],
                'subject_topic'                => $validated['topic_id'],
                'venue_id'                     => $validated['venue_id'] ?? null,
                'class_session_master_pk'      => $validated['class_session_master_pk'] ?? null,
                'faculty_master_pk'            => $validated['faculty_master_pk'] ?? null,
                'course_student_attendance_pk' => $studentId->course_attendance_pk,
                'message'                      => $validated['Remark'] ?? null,
                'notice_memo'                  => $validated['submission_type'],
            ];
            if ($hasTplCol) {
                $row['memo_notice_template_pk'] = $validated['memo_notice_template_pk'] ?? null;
            }
            if ($hasSnapshotCol) {
                $row['template_snapshot'] = $templateSnapshot;
            }
            $data[] = $row;
          
        }
    // }
    // print_r($data);die;

    // If no new notices to create (all duplicates)
    if (empty($data)) {
        return redirect()->route('memo.notice.management.index')->with('warning', 'Notice already exists for the selected students on this topic.');
    }

    // ✅ Bulk insert
    $inserted = DB::table('student_notice_status')->insert($data);

    if ($inserted) {
        $this->sendIssuedNoticeNotifications(
            $data,
            (int) $validated['submission_type'],
            $validated['Remark'] ?? null
        );

        return redirect()->route('memo.notice.management.index')->with('success', 'Memo/Notice created successfully.');
    } else {
        return redirect()->back()->with('error', 'Failed to create Memo/Notice. Please try again.');
    }
}

function store_memo_notice_bkp(Request $request){
  
     $validated = $request->validate([
        'course_master_pk' => 'required|exists:course_master,pk',
        'date_memo_notice' => 'required|date',
        'subject_master_id' => 'required|exists:subject_master,pk',
        'topic_id' => 'required|exists:timetable,pk',
        'venue_id' => 'required',
        'class_session_master_pk' => 'required',
        'faculty_master_pk' => 'required',
        'selected_student_list' => 'required|array|min:1',
        'Remark' => 'nullable|string|max:500',
        'submission_type' => 'required|in:1,2', // Assuming 1 for Memo and 2 for Notice
    ]);
   

    $data = [];
    foreach ($validated['selected_student_list'] as $studentId) {
         $student_id = DB::table('course_student_attendance as a')
            ->Join('student_master as s', 'a.Student_master_pk', '=', 's.pk')
            ->where('a.pk', $studentId)
            ->select(
                'a.pk as studnet_pk',
            )
            ->get();
        $data[] = [
            'course_master_pk' => $validated['course_master_pk'],
            'student_pk' => $student_id->studnet_pk,
            'date_' => $validated['date_memo_notice'],
            'subject_master_pk' => $validated['subject_master_id'],
            'subject_topic' => $validated['topic_id'],
            'venue_id' => $validated['venue_id'],
            'class_session_master_pk' => $validated['class_session_master_pk'],
            'faculty_master_pk' => $validated['faculty_master_pk'],
            'course_student_attendance_pk' => $studentId,
            'message' => $validated['Remark'],
            'notice_memo' => $validated['submission_type'],
        ];

         

        // Insert into student_notice_status table

    }

    $courseAttendanceNotice = DB::table('student_notice_status')->insert($data);

    if ($courseAttendanceNotice) {
        return redirect()->route('memo.notice.management.index')->with('success', 'Memo/Notice created successfully.');
    } else {
        return redirect()->back()->with('error', 'Failed to create Memo/Notice. Please try again.');
    }
}
public function getNewMessages(Request $request, $id, $type)
{
    $lastPk = (int) $request->query('last_pk', 0);

    if ($type === 'memo') {
        $table = 'memo_message_student_decip_incharge';
        $fk    = 'student_memo_status_pk';
    } else {
        $table = 'notice_message_student_decip_incharge';
        $fk    = 'student_notice_status_pk';
    }

    $messages = DB::table($table)
        ->where($fk, $id)
        ->where('pk', '>', $lastPk)
        ->orderBy('pk', 'asc')
        ->get();

    // Multiple distinct admins/faculty can post in the same conversation, so
    // resolve each sender's real name + role instead of collapsing them all
    // to a generic "Admin" label (same helper the Discipline memo chat uses).
    $messages = $messages->map(function ($msg) {
        $identity = resolve_chat_sender_identity($msg->created_by, $msg->role_type);
        $msg->display_name = $identity['display_name'];
        $msg->role_name = $identity['role_name'];
        // Format date for display
        $msg->formatted_date = $msg->created_date
            ? \Carbon\Carbon::parse($msg->created_date, 'UTC')->timezone('Asia/Kolkata')->format('d-m-Y h:i A')
            : '';
        return $msg;
    });

    return response()->json($messages);
}

public function deleteMemoNotice($id)
{
    try {
        $memoNotice = DB::table('student_notice_status')->where('pk', $id)->first();

        if (!$memoNotice) {
            return redirect()->back()->with('error', 'Memo/Notice not found.');
        }

        DB::table('student_notice_status')->where('pk', $id)->delete();

        return redirect()->route('memo.notice.management.index')->with('success', 'Memo/Notice deleted successfully.');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Failed to delete Memo/Notice. Please try again.');
    }
}

/**
 * Hard-delete a notice or memo row (with its conversation messages + uploaded files).
 * Deleting a notice also removes any memos that were generated from it.
 */
public function destroyRecord($id, $type)
{
    if (! $this->userCanManageMemoNotice()) {
        return response()->json(['success' => false, 'message' => 'You are not authorized to delete this record.'], 403);
    }

    $type = $type === 'memo' ? 'memo' : 'notice';

    // Capture record before hard-delete so the OT can still be notified.
    $noticeForNotify = null;
    $memoForNotify = null;
    if ($type === 'notice') {
        $noticeForNotify = DB::table('student_notice_status')->where('pk', $id)->first();
        if ($noticeForNotify && empty($noticeForNotify->student_pk)) {
            $noticeForNotify->student_pk = $this->resolveNoticeStudentPk((int) $id);
        }
    } else {
        $memoForNotify = DB::table('student_memo_status')->where('pk', $id)->first();
    }

    try {
        DB::transaction(function () use ($id, $type) {
            if ($type === 'memo') {
                $this->deleteMemoWithConversation((int) $id);
            } else {
                // A notice can spawn memos; remove those (and their chats) first to avoid orphans.
                $memoIds = DB::table('student_memo_status')
                    ->where('student_notice_status_pk', $id)
                    ->pluck('pk');
                foreach ($memoIds as $memoId) {
                    $this->deleteMemoWithConversation((int) $memoId);
                }

                $this->deleteConversationFiles('notice_message_student_decip_incharge', 'student_notice_status_pk', (int) $id);
                DB::table('notice_message_student_decip_incharge')->where('student_notice_status_pk', $id)->delete();
                DB::table('student_notice_status')->where('pk', $id)->delete();
            }
        });

        if ($noticeForNotify) {
            $this->notifyStudentAboutNotice(
                $noticeForNotify,
                'Notice Deleted',
                'has been deleted'
            );
        }

        if ($memoForNotify) {
            $this->notifyStudentAboutMemo(
                (int) $memoForNotify->pk,
                (int) $memoForNotify->student_pk,
                (int) $memoForNotify->course_master_pk,
                (int) $memoForNotify->student_notice_status_pk,
                (string) ($memoForNotify->date ?? now()->format('Y-m-d')),
                'Memo Deleted',
                'has been deleted'
            );
        }

        return response()->json(['success' => true, 'message' => ucfirst($type) . ' deleted successfully.']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Failed to delete the record. Please try again.'], 500);
    }
}

protected function deleteMemoWithConversation(int $memoId): void
{
    $this->deleteConversationFiles('memo_message_student_decip_incharge', 'student_memo_status_pk', $memoId);
    DB::table('memo_message_student_decip_incharge')->where('student_memo_status_pk', $memoId)->delete();
    DB::table('student_memo_status')->where('pk', $memoId)->delete();
}

protected function deleteConversationFiles(string $table, string $fk, int $id): void
{
    $files = DB::table($table)->where($fk, $id)->pluck('doc_upload')->filter();
    foreach ($files as $file) {
        if ($file && Storage::disk('public')->exists($file)) {
            Storage::disk('public')->delete($file);
        }
    }
}

protected function userCanManageMemoNotice(): bool
{
    return hasRole('Internal Faculty') || hasRole('Guest Faculty')
        || hasRole('Super Admin') || hasRole('Training Induction Admin') || hasRole('Training-Induction');
}

/**
 * Notice detail for the "Edit Notice" modal — the only editable field is the
 * template; everything else shown is read-only context for the live preview.
 */
public function editNotice($id)
{
    if (! $this->userCanManageMemoNotice()) {
        return response()->json(['message' => 'You are not authorized to edit this notice.'], 403);
    }

    $notice = DB::table('student_notice_status as sns')
        ->leftJoin('course_master as cm', 'cm.pk', '=', 'sns.course_master_pk')
        ->leftJoin('timetable as t', 't.pk', '=', 'sns.subject_topic')
        ->leftJoin('venue_master as v', 'v.venue_id', '=', 'sns.venue_id')
        ->leftJoin('student_master as sm', 'sm.pk', '=', 'sns.student_pk')
        ->where('sns.pk', $id)
        ->select(
            'sns.pk',
            'sns.course_master_pk',
            'sns.date_',
            'sns.class_session_master_pk',
            'sns.memo_notice_template_pk',
            'sns.message',
            'sns.status',
            'cm.course_name',
            't.subject_topic as topic_name',
            'v.venue_name',
            'sm.display_name as student_name',
            'sm.generated_OT_code'
        )
        ->first();

    if (!$notice) {
        return response()->json(['message' => 'Notice not found.'], 404);
    }

    return response()->json([
        'pk' => $notice->pk,
        'course_master_pk' => $notice->course_master_pk,
        'course_name' => $notice->course_name ?? 'N/A',
        'date_' => $notice->date_,
        'topic_name' => $notice->topic_name ?? 'N/A',
        'venue_name' => $notice->venue_name ?? 'N/A',
        'session_name' => $notice->class_session_master_pk ?? 'N/A',
        'memo_notice_template_pk' => $notice->memo_notice_template_pk,
        'student_name' => $notice->student_name ?? 'N/A',
        'generated_OT_code' => $notice->generated_OT_code,
        'message' => $notice->message,
    ]);
}

public function updateNoticeTemplate(Request $request, $id)
{
    if (! $this->userCanManageMemoNotice()) {
        return response()->json(['success' => false, 'message' => 'You are not authorized to edit this notice.'], 403);
    }

    $notice = DB::table('student_notice_status')->where('pk', $id)->first();
    if (!$notice) {
        return response()->json(['success' => false, 'message' => 'Notice not found.'], 404);
    }
    if ($notice->status == 2) {
        return response()->json(['success' => false, 'message' => 'Closed notices cannot be edited.'], 422);
    }

    $validated = $request->validate([
        'memo_notice_template_pk' => 'required|exists:memo_notice_templates,pk',
    ]);

    DB::table('student_notice_status')->where('pk', $id)->update([
        'memo_notice_template_pk' => $validated['memo_notice_template_pk'],
        // Re-pinning the template also re-freezes its content as of now.
        'template_snapshot'       => build_memo_notice_template_snapshot($validated['memo_notice_template_pk']),
    ]);

    $this->notifyStudentAboutNotice(
        $notice,
        'Notice Updated',
        'has been updated. Please review'
    );

    return response()->json(['success' => true, 'message' => 'Notice updated successfully.']);
}
public function memo_notice_conversation(Request $request)
{
    $type = $request->input('type'); // 'memo' or 'notice'
// print_r($request->all());die;
    $validator = Validator::make($request->all(), [
        'memo_notice_id' => [
            'required',
            function ($attribute, $value, $fail) use ($type) {
                $table = $type === 'memo' ? 'student_memo_status' : 'student_notice_status';
                if (!DB::table($table)->where('pk', $value)->exists()) {
                    $fail("The selected ID does not exist in $table.");
                }
            }
        ],
        'date' => 'required|date',
        'time' => 'required',
        'message' => 'required|string|max:500',
        'document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        'status' => 'required|in:1,2',
        'mark_of_deduction' => 'nullable|numeric|min:0',
        'conclusion_type' => 'nullable|exists:memo_conclusion_master,pk',
        'conclusion_remark' => 'nullable|string|max:500',
    ]);
    if($type === 'memo') {
    $validator->sometimes('conclusion_type', 'required_if:status,2', function ($input) {
        return $input->type === 'memo';
    });

    $validator->sometimes('conclusion_remark', 'required_if:status,2|max:500', function ($input) {
        return $input->type === 'memo';
    });
}

    // "Marks Deduction" is looked up by name, not a hardcoded pk, since master data
    // can be re-seeded — matches the client-side check in conversation.blade.php's
    // toggleDeduction() (selectedText === 'Marks Deduction').
    $validator->sometimes('mark_of_deduction', 'required|numeric|min:0', function ($input) {
        if (empty($input->conclusion_type)) {
            return false;
        }
        return DB::table('memo_conclusion_master')
            ->where('pk', $input->conclusion_type)
            ->value('discussion_name') === 'Marks Deduction';
    });

    if ($validator->fails()) {

    //    print_r($validator->errors());die;

        return redirect()->back()->withErrors($validator)->withInput();
    }

    // ✅ Fixed: Get validated data
    $validated = $validator->validated();

    // // File upload
    // print_r($request->all());
    // print_r($validated);die;

    $filePath = null;
    if ($request->hasFile('document')) {
        $file = $request->file('document');
        $filePath = $type == 'memo'
            ? $file->store('memo_conversation_documents', 'public')
            : $file->store('notice_documents', 'public');
    }

    // Define insert table and foreign key field
    $table = $type === 'memo' ? 'memo_message_student_decip_incharge' : 'notice_message_student_decip_incharge';
    $statusTable = $type === 'memo' ? 'student_memo_status' : 'student_notice_status';
    $foreignKey = $type === 'memo' ? 'student_memo_status_pk' : 'student_notice_status_pk';

    // Insert message (store created_date in UTC for correct chat order and display in Asia/Kolkata)
    $inserted = DB::table($table)->insert([
        $foreignKey => $validated['memo_notice_id'],
        'student_decip_incharge_msg' => $validated['message'],
        'doc_upload' => $filePath,
        'role_type' => 'f',
        'created_by' => auth()->user()->id ?? 1,
        'created_date' => now('UTC'),
    ]);

   if ($inserted) {
    // Update status if needed
    if ($validated['status'] == 2) {
        $query = DB::table($statusTable)
            ->where('pk', $validated['memo_notice_id']);

        if ($type === 'memo') {
            $query->update([
                'communication_status' => 2,
                'status' => 2
            ]);
        } else {
            $query->update([
                'status' => 2
            ]);
        }

        // Notify the OT that the memo/notice conversation has been closed.
        if ($type === 'memo') {
            $memoForNotify = DB::table('student_memo_status')->where('pk', $validated['memo_notice_id'])->first();
            if ($memoForNotify) {
                $this->notifyStudentAboutMemo(
                    (int) $memoForNotify->pk,
                    (int) $memoForNotify->student_pk,
                    (int) $memoForNotify->course_master_pk,
                    (int) $memoForNotify->student_notice_status_pk,
                    (string) ($memoForNotify->date ?? now()->format('Y-m-d')),
                    'Memo Closed',
                    'has been closed'
                );
            }
        } else {
            $noticeForNotify = DB::table('student_notice_status')->where('pk', $validated['memo_notice_id'])->first();
            if ($noticeForNotify) {
                if (empty($noticeForNotify->student_pk)) {
                    $noticeForNotify->student_pk = $this->resolveNoticeStudentPk((int) $validated['memo_notice_id']);
                }
                $this->notifyStudentAboutNotice(
                    $noticeForNotify,
                    'Notice Closed',
                    'has been closed'
                );
            }
        }
    }


        // Optional: Memo conclusion update (if applicable)
        if ($type === 'memo' && isset($validated['conclusion_type'])) {
            DB::table('student_memo_status')
                ->where('pk', $validated['memo_notice_id'])
                ->update([
                    'memo_conclusion_master_pk' => $validated['conclusion_type'],
                    'conclusion_remark' => $validated['conclusion_remark'] ?? null,
                    'mark_of_deduction' => $validated['mark_of_deduction'] ?? null,
                    'decicion_taken_by' => auth()->user()->id ?? 1,
                    'decision_date' => now(),
                    'modified_date' => now(),
                ]);
        }else if ($type === 'notice' && isset($validated['conclusion_type'])) {
            // If status is not 2, still update modified_date
            DB::table('student_notice_status')
                ->where('pk', $validated['memo_notice_id'])
                ->update([
                    'conclusion_type_pk' => $validated['conclusion_type'],
                    'conclusion_remark' => $validated['conclusion_remark'] ?? null,
                    'mark_of_deduction' => $validated['mark_of_deduction'] ?? null,
                ]);
        }

        // Notify the other party about the new chat message.
        $this->sendMemoNoticeChatNotification(
            (int) $validated['memo_notice_id'],
            $type,
            'f',
            $validated['message'] ?? null
        );

        return redirect()->back()->with('success', ucfirst($type) . ' message created successfully.');
    }

    return redirect()->back()->with('error', 'Failed to create ' . ucfirst($type) . '. Please try again.');
}


public function memo_notice_conversation_bkp(Request $request)
{
    // print_r($request->all());die;
    $validated = $request->validate([
        'memo_notice_id' => 'required|exists:student_notice_status,pk',
        'date' => 'required|date',
        'time' => 'required',
        'message' => 'required|string|max:500',
        'document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        'status' => 'required|in:1,2',
    ]);

    // Handle file upload
     $filePath = null;
   
    if ($request->hasFile('document')) {
        $file = $request->file('document');
        $filePath = $file->store('notice_documents', 'public'); // stored in /storage/app/public/notice_documents
    }

    // Insert into your table
   $data = DB::table('notice_message_student_decip_incharge')->insert([
        'student_notice_status_pk' => $validated['memo_notice_id'],
        'student_decip_incharge_msg' => $validated['message'],
        'doc_upload' => $filePath,
        'role_type' => 'f',
        'created_by' => auth()->user()->id ?? 1, // Replace with correct user ID
         ]);

   if ($data) {
             if (isset($validated['status']) && $validated['status'] == 2) {
                DB::table('student_notice_status')
                ->where('pk', $validated['memo_notice_id'])
                ->update(['status' => $validated['status']]);
        }
        return redirect()->back()->with('success', 'Memo msg created successfully.');
   


    if ($request->hasFile('document')) {
        $file = $request->file('document');
        $filePath = $file->store('notice_documents', 'public'); // stored in /storage/app/public/notice_documents
    }

    // Insert into your table
   $data = DB::table('memo_message_student_decip_incharge')->insert([
        'student_memo_status_pk' => $validated['memo_notice_id'],
        'student_decip_incharge_msg' => $validated['message'],
        'doc_upload' => $filePath,
        'role_type' => 'f',
        'created_by' => auth()->user()->id ?? 1, // Replace with correct user ID
         ]);

   if ($data) {
             if (isset($validated['status']) && $validated['status'] == 2) {
                DB::table('student_notice_status')
                ->where('pk', $validated['memo_notice_id'])
                ->update(['status' => $validated['status']]);
        }
        return redirect()->back()->with('success', 'Notice msg created successfully.');
    }
        } else {
        return redirect()->back()->with('error', 'Failed to create Memo/Notice. Please try again.');
    }
}
public function noticedeleteMessage($id,$type)
{
    if($type == 'memo'){
        $table = 'memo_message_student_decip_incharge';
    }else{
        $table = 'notice_message_student_decip_incharge';
    }

       $message = DB::table($table)
        ->where('pk', $id)
        ->first();

    if ($message && !empty($message->file_name)) {
        // Delete the file from the 'public' disk
        if (Storage::disk('public')->exists($message->file_name)) {
            Storage::disk('public')->delete($message->file_name);
        }
    }


    // Now delete the DB record
    DB::table($table)
        ->where('pk', $id)
        ->delete();

    return redirect()->back()->with('success', 'Message and associated file deleted successfully.');

}
  public function user_bkp()
    {
       $memos =  DB::table('student_notice_status')
                    ->join('course_student_attendance as csa', 'student_notice_status.course_student_attendance_pk', '=', 'csa.pk')
                    ->join('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')  
                    ->join('timetable as t', 'student_notice_status.subject_topic', '=', 't.pk')          
       ->select(
            'student_notice_status.pk as memo_notice_id',
            'student_notice_status.course_master_pk', 'student_notice_status.date_',
            'student_notice_status.subject_master_pk','student_notice_status.subject_topic',
            'student_notice_status.venue_id', 'student_notice_status.class_session_master_pk','student_notice_status.faculty_master_pk','student_notice_status.message','student_notice_status.notice_memo',
            'student_notice_status.status',
            'sm.display_name as student_name','sm.pk as student_id','t.subject_topic as topic_name',)
                    ->get();
         return view('admin.courseAttendanceNoticeMap.uers_notice_list', compact('memos'));
    }
    public function user()
{
     
    $notices = DB::table('student_notice_status');
      if(hasRole('Student-OT')){
        $notices->where('student_notice_status.student_pk', auth()->user()->user_id);
    }
    $notices->leftJoin('course_student_attendance as csa', 'student_notice_status.course_student_attendance_pk', '=', 'csa.pk');
    // For direct notices course_student_attendance_pk=0, so csa is NULL; fall back to student_notice_status.student_pk
    $notices->leftJoin('student_master as sm', function ($join) {
        $join->whereRaw('sm.pk = COALESCE(csa.Student_master_pk, student_notice_status.student_pk)');
    });
    $notices->leftJoin('timetable as t', 'student_notice_status.subject_topic', '=', 't.pk');
    $notices->select(
        'student_notice_status.pk as notice_id',
        'student_notice_status.student_pk',
        'student_notice_status.course_master_pk',
        'student_notice_status.date_',
        'student_notice_status.subject_master_pk',
        'student_notice_status.subject_topic',
        'student_notice_status.venue_id',
        'student_notice_status.class_session_master_pk',
        'student_notice_status.faculty_master_pk',
        'student_notice_status.message',
        'student_notice_status.notice_memo',
        'student_notice_status.status',
        'sm.display_name as student_name',
        'sm.pk as student_id',
        't.subject_topic as topic_name'
    );
    $notices = $notices->get();

    // Batch-fetch all memo data for status-2 notices in one query (fixes N+1)
    $status2Ids = $notices->where('status', 2)->pluck('notice_id')->filter()->values()->all();

    $memosByNoticeId = collect();
    if (!empty($status2Ids)) {
        $memosByNoticeId = DB::table('student_memo_status')
            ->leftJoin('student_master as sm', 'student_memo_status.student_pk', '=', 'sm.pk')
            ->leftJoin('student_notice_status as sns', 'student_memo_status.student_notice_status_pk', '=', 'sns.pk')
            ->leftJoin('timetable as t', 'sns.subject_topic', '=', 't.pk')
            ->whereIn('student_memo_status.student_notice_status_pk', $status2Ids)
            ->select(
                'student_memo_status.pk as memo_id',
                'student_memo_status.student_notice_status_pk as notice_id',
                'student_memo_status.student_pk',
                'student_memo_status.course_master_pk',
                'student_memo_status.date as date_',
                DB::raw('NULL as subject_master_pk'),
                DB::raw('NULL as subject_topic'),
                DB::raw('NULL as venue_id'),
                DB::raw('NULL as class_session_master_pk'),
                DB::raw('NULL as faculty_master_pk'),
                'student_memo_status.message',
                DB::raw('2 as notice_memo'),
                'student_memo_status.status',
                'sm.display_name as student_name',
                'sm.pk as student_id',
                't.subject_topic as topic_name',
                DB::raw('"Memo" as type_notice_memo')
            )
            ->get()
            ->keyBy('notice_id');
    }

    $memos = collect();

    foreach ($notices as $notice) {
        if ($notice->status == 2 && isset($memosByNoticeId[$notice->notice_id])) {
            $memos->push($memosByNoticeId[$notice->notice_id]);
        } else {
            $notice->type_notice_memo = 'Notice';
            $memos->push($notice);
        }
    }

    // Paginate the collection
    $perPage = 10;
    $currentPage = request()->get('page', 1);
    $pagedData = $memos->slice(($currentPage - 1) * $perPage, $perPage)->values();

    // Attach the unread student-reply count (for the current viewer) used by the reply-icon badge.
    // Counts come from the same notifications table that feeds the header bell, so badge + bell stay in sync.
    $this->attachChatUnreadCounts($pagedData);

    $memos = new \Illuminate\Pagination\LengthAwarePaginator(
        $pagedData,
        $memos->count(),
        $perPage,
        $currentPage,
        ['path' => request()->url(), 'query' => request()->query()]
    );

    return view('admin.courseAttendanceNoticeMap.uers_notice_list', compact('memos'));
}

/**
 * Populate $row->chat_unread on each listing row with the number of unread student-reply
 * notifications for the current viewer. Notice rows are keyed by notice id (module "Notice"),
 * memo rows by memo id (module "Memo"), matching how the bell notifications are created.
 *
 * @param  \Illuminate\Support\Collection  $rows
 */
private function attachChatUnreadCounts($rows): void
{
    foreach ($rows as $row) {
        $row->chat_unread = 0;
    }

    $viewerId = auth()->user()->user_id ?? null;
    if (!$viewerId || $rows->isEmpty()) {
        return;
    }

    $noticeIds = [];
    $memoIds   = [];
    foreach ($rows as $row) {
        if (($row->type_notice_memo ?? '') === 'Memo') {
            if (!empty($row->memo_id)) {
                $memoIds[] = $row->memo_id;
            }
        } elseif (!empty($row->notice_id)) {
            $noticeIds[] = $row->notice_id;
        }
    }

    if (empty($noticeIds) && empty($memoIds)) {
        return;
    }

    $counts = DB::table('notifications')
        ->where('receiver_user_id', $viewerId)
        ->where('type', 'memo_notice')
        ->where('is_read', 0)
        ->where(function ($q) use ($noticeIds, $memoIds) {
            if (!empty($noticeIds)) {
                $q->orWhere(function ($w) use ($noticeIds) {
                    $w->where('module_name', 'Notice')->whereIn('reference_pk', $noticeIds);
                });
            }
            if (!empty($memoIds)) {
                $q->orWhere(function ($w) use ($memoIds) {
                    $w->where('module_name', 'Memo')->whereIn('reference_pk', $memoIds);
                });
            }
        })
        ->select('module_name', 'reference_pk', DB::raw('COUNT(*) as cnt'))
        ->groupBy('module_name', 'reference_pk')
        ->get();

    $lookup = ['Notice' => [], 'Memo' => []];
    foreach ($counts as $c) {
        $lookup[$c->module_name][(string) $c->reference_pk] = (int) $c->cnt;
    }

    foreach ($rows as $row) {
        if (($row->type_notice_memo ?? '') === 'Memo') {
            $row->chat_unread = $lookup['Memo'][(string) ($row->memo_id ?? '')] ?? 0;
        } else {
            $row->chat_unread = $lookup['Notice'][(string) ($row->notice_id ?? '')] ?? 0;
        }
    }
}

public function conversation_student($id ,$type, Request $request){

if (!$id || !is_numeric($id)) {
        return redirect()->back()->with('error', 'Invalid Memo/Notice ID.');
    }

    // Viewing the conversation clears the viewer's unread badge + bell for this thread.
    $this->markMemoNoticeChatRead($id, in_array($type, ['memo', 'notice']) ? $type : 'notice');

    $memoNotice = collect(); // default empty collection

    if ($type == 'notice') {
        $memoNotice = DB::table('notice_message_student_decip_incharge as nmsdi')
            ->leftjoin('student_notice_status as sns', 'nmsdi.student_notice_status_pk', '=', 'sns.pk')
            ->leftjoin('course_student_attendance as csa', 'sns.course_student_attendance_pk', '=', 'csa.pk')
            ->leftjoin('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')
            ->where('nmsdi.student_notice_status_pk', $id)
            ->orderBy('nmsdi.created_date', 'asc')
            ->select(
                'nmsdi.*',
                'sns.pk as notice_id',
                'sns.status as notice_status',
                'sm.pk as student_id',
                'sm.display_name as student_name'
            )
            ->get();
  $template_details = DB::table('student_notice_status as sns')
            ->leftJoin('timetable as t', 'sns.subject_topic', '=', 't.pk')
            ->leftJoin('venue_master as v', 't.venue_id', '=', 'v.venue_id')
            ->leftJoin('student_master as sm', 'sns.student_pk', '=', 'sm.pk')
            ->leftJoin('course_master as cm', 'sns.course_master_pk', '=', 'cm.pk')
            ->leftJoin('faculty_master as fm', DB::raw('fm.pk'), '=', DB::raw("CASE WHEN sns.faculty_master_pk LIKE '[%' THEN JSON_UNQUOTE(JSON_EXTRACT(sns.faculty_master_pk, '$[0]')) ELSE sns.faculty_master_pk END"))
            ->leftJoin('memo_notice_templates as mnt', function($join) {
                // Prefer the template pinned at send time; else the latest active Notice template for the course.
                $join->on(DB::raw('mnt.pk'), '=', DB::raw("COALESCE(sns.memo_notice_template_pk, (SELECT t2.pk FROM memo_notice_templates t2 WHERE t2.course_master_pk = sns.course_master_pk AND t2.memo_notice_type = 'Notice' AND t2.active_inactive = 1 AND t2.deleted_at IS NULL ORDER BY t2.pk DESC LIMIT 1))"));
            })
            ->where('sns.pk', $id)
            ->select(
                't.subject_topic',
                'v.venue_name',
                't.class_session as session_time',
                'sns.date_ as session_date',
                'sm.display_name',
                'sm.generated_OT_code',
                'cm.course_name',
                'fm.full_name as faculty_name',
                'mnt.content',
                'mnt.director_name',
                'mnt.director_designation',
                'mnt.signature_image',
                'sns.template_snapshot',
                'sns.conclusion_type_pk',
                'sns.conclusion_remark',
                'sns.mark_of_deduction',
                'sns.status as notice_current_status'
            )
            ->first();
            // Content frozen at send time wins over the live-joined (current) template.
            $template_details = apply_memo_notice_template_snapshot($template_details);

    } elseif ($type == 'memo') {

        $memoNotice = DB::table('memo_message_student_decip_incharge as mmsdi')
            ->leftjoin('student_memo_status as sms', 'mmsdi.student_memo_status_pk', '=', 'sms.pk')
            ->leftjoin('student_master as sm', 'sms.student_pk', '=', 'sm.pk')
            ->where('mmsdi.student_memo_status_pk', $id)
            ->orderBy('mmsdi.created_date', 'asc')
            ->select(
                'mmsdi.*',
                'sms.pk as notice_id',
                'sms.communication_status as notice_status',
                
                'sm.pk as student_id',
                'sm.display_name as student_name'
            )
            ->get();
          
             $template_details = DB::table('student_memo_status as sms')
            ->leftJoin('student_notice_status as sns', 'sms.student_notice_status_pk', '=', 'sns.pk')
            ->leftJoin('timetable as t', 'sns.subject_topic', '=', 't.pk')
            ->leftJoin('venue_master as v', 't.venue_id', '=', 'v.venue_id')
            ->leftJoin('student_master as sm', 'sms.student_pk', '=', 'sm.pk')
            ->leftJoin('course_master as cm', 'sms.course_master_pk', '=', 'cm.pk')
            ->leftJoin('faculty_master as fm', DB::raw('fm.pk'), '=', DB::raw("CASE WHEN sns.faculty_master_pk LIKE '[%' THEN JSON_UNQUOTE(JSON_EXTRACT(sns.faculty_master_pk, '$[0]')) ELSE sns.faculty_master_pk END"))
            ->leftJoin('memo_notice_templates as mnt', function($join) {
                // Prefer the template pinned at send time; else the latest active Memo template for the course.
                $join->on(DB::raw('mnt.pk'), '=', DB::raw("COALESCE(sms.memo_notice_template_pk, (SELECT t2.pk FROM memo_notice_templates t2 WHERE t2.course_master_pk = sms.course_master_pk AND t2.memo_notice_type = 'Memo' AND t2.active_inactive = 1 AND t2.deleted_at IS NULL ORDER BY t2.pk DESC LIMIT 1))"));
            })
            ->where('sms.pk', $id)
            ->select(
                't.subject_topic',
                'v.venue_name',
                't.class_session as session_time',
                'sns.date_ as session_date',
                'sm.display_name',
                'sm.generated_OT_code',
                'cm.course_name',
                'fm.full_name as faculty_name',
                'mnt.content',
                'mnt.director_name',
                'mnt.director_designation',
                'mnt.signature_image',
                'sms.template_snapshot',
                'sms.memo_conclusion_master_pk as conclusion_type_pk',
                'sms.conclusion_remark',
                'sms.mark_of_deduction',
                'sms.communication_status as notice_current_status'
            )
            ->first();
            // Content frozen at send time wins over the live-joined (current) template.
            $template_details = apply_memo_notice_template_snapshot($template_details);

    }
// print_r($memoNotice);die;
    // Multiple distinct admins/faculty can post in the same conversation, so
    // resolve each sender's real name + role instead of collapsing them all
    // to a generic "Admin" label (same helper the Discipline memo chat uses).
    $memoNotice->transform(function ($item) {
        $identity = resolve_chat_sender_identity($item->created_by, $item->role_type);
        $item->display_name = $identity['display_name'];
        $item->role_name = $identity['role_name'];
        return $item;
    });

    $memo_conclusion_master = DB::table('memo_conclusion_master')->where('active_inactive', 1)->get();

    // Resolve the student PK reliably for both attendance-based and direct notices.
    // For direct notices course_student_attendance_pk = 0, so csa join yields NULL.
    // student_notice_status.student_pk is always set for direct notices.
    if ($type === 'notice') {
        $snsRow = DB::table('student_notice_status as sns')
            ->leftJoin('course_student_attendance as csa', 'sns.course_student_attendance_pk', '=', 'csa.pk')
            ->where('sns.pk', $id)
            ->select(DB::raw('COALESCE(csa.Student_master_pk, sns.student_pk) as student_pk'))
            ->first();
        $noticeStudentPk = (int) ($snsRow->student_pk ?? 0);
    } else {
        $noticeStudentPk = (int) DB::table('student_memo_status')->where('pk', $id)->value('student_pk');
    }

   return view('admin.courseAttendanceNoticeMap.chat', compact('id', 'memoNotice', 'type', 'template_details', 'memo_conclusion_master', 'noticeStudentPk'));
}
public function memo_notice_conversation_student(Request $request)
{
    $type = $request->input('type'); // 'memo' or 'notice'
    
    // Determine the correct status table based on the type
    $statusTable = $type === 'memo' ? 'student_memo_status' : 'student_notice_status';

    // Basic validation
    $validated = $request->validate([
        'memo_notice_id' => [
            'required',
            Rule::exists($statusTable, 'pk'), // Dynamically check existence in the correct table
        ],
        'message' => 'required|string|max:500',
        'student_id' => 'required|exists:student_master,pk',
        'document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        'type' => 'required|in:memo,notice',
    ]);

    // Handle file upload if present
    $filePath = null;
    if ($request->hasFile('document')) {
        $file = $request->file('document');
        $folder = $type === 'memo' ? 'memo_conversation_documents' : 'notice_documents';
        $filePath = $file->store($folder, 'public');
    }

    // Determine table to insert message
    $insertTable = $type === 'memo' ? 'memo_message_student_decip_incharge' : 'notice_message_student_decip_incharge';
    $foreignKeyField = $type === 'memo' ? 'student_memo_status_pk' : 'student_notice_status_pk';

    // Insert message (store created_date in UTC for correct chat order and display in Asia/Kolkata)
    $inserted = DB::table($insertTable)->insert([
        $foreignKeyField => $validated['memo_notice_id'],
        'student_decip_incharge_msg' => $validated['message'],
        'doc_upload' => $filePath,
        'role_type' => 's',
        'created_by' => $validated['student_id'],
        'created_date' => now('UTC'),
    ]);

    // Notify the admin/incharge who issued this notice/memo that the OT has replied.
    if ($inserted) {
        $this->sendMemoNoticeChatNotification(
            (int) $validated['memo_notice_id'],
            $type,
            's',
            $validated['message'] ?? null
        );

        return redirect()->back()->with('success', ucfirst($type) . ' message created successfully.');
    }

    return redirect()->back()->with('error', 'Failed to create ' . ucfirst($type) . ' message. Please try again.');
}

/**
 * Notify each OT (student) that a notice/memo was issued to them.
 * notice_memo / submission_type: 1 = Notice, 2 = Memo.
 *
 * @param  array<int, array<string, mixed>>  $insertedRows
 */
private function sendIssuedNoticeNotifications(array $insertedRows, int $submissionType, ?string $remark = null): void
{
    if ($insertedRows === []) {
        return;
    }

    try {
        $notificationService = app(NotificationService::class);
        $receiverService = app(NotificationReceiverService::class);

        $studentPks = array_values(array_unique(array_map(
            static fn (array $row) => (int) $row['student_pk'],
            $insertedRows
        )));

        $firstRow = $insertedRows[0];
        $topicId = $firstRow['subject_topic'] ?? null;
        $noticeDate = $firstRow['date_'] ?? null;
        $coursePk = $firstRow['course_master_pk'] ?? null;
        $subjectPk = $firstRow['subject_master_pk'] ?? null;

        $noticeQuery = DB::table('student_notice_status')
            ->whereIn('student_pk', $studentPks)
            ->select('pk', 'student_pk');

        if ($topicId !== null) {
            $noticeQuery->where('subject_topic', $topicId);
        }
        if ($noticeDate !== null) {
            $noticeQuery->where('date_', $noticeDate);
        }

        $insertedNotices = $noticeQuery
            ->get()
            ->keyBy(static fn ($row) => (int) $row->student_pk);

        $course = $coursePk ? CourseMaster::find($coursePk) : null;
        $subject = $subjectPk ? SubjectMaster::find($subjectPk) : null;
        $topicName = 'Topic';
        if ($topicId) {
            $topicName = DB::table('timetable')->where('pk', $topicId)->value('subject_topic') ?? 'Topic';
        }

        $noticeTypeLabel = $submissionType === 1 ? 'Notice' : 'Memo';
        $courseName = $course->course_name ?? 'Course';
        $subjectName = $subject->subject_name ?? 'Subject';
        $dateLabel = $noticeDate
            ? date('d M Y', strtotime((string) $noticeDate))
            : date('d M Y');

        $message = "A {$noticeTypeLabel} has been issued for {$courseName} - {$subjectName} ({$topicName}) on {$dateLabel}.";
        if (!empty($remark)) {
            $message .= " Remark: {$remark}";
        }

        foreach ($studentPks as $studentPk) {
            $receiverUserId = $receiverService->getStudentUserId($studentPk);
            if (!$receiverUserId) {
                continue;
            }

            $noticeRow = $insertedNotices->get($studentPk);
            if (!$noticeRow) {
                continue;
            }

            $notificationService->create(
                (int) $receiverUserId,
                'memo_notice',
                $noticeTypeLabel,
                (int) $noticeRow->pk,
                "{$noticeTypeLabel} Issued",
                $message
            );
        }
    } catch (\Exception $e) {
        \Log::error('Failed to send memo/notice notifications: ' . $e->getMessage());
    }
}

/**
 * Notify the OT that a memo was generated/sent for them.
 */
private function sendIssuedMemoNotification(
    int $memoPk,
    int $studentPk,
    int $coursePk,
    int $noticeStatusPk,
    string $memoDate,
    ?string $remark = null
): void {
    try {
        $receiverUserId = app(NotificationReceiverService::class)->getStudentUserId($studentPk);
        if (!$receiverUserId) {
            return;
        }

        $courseName = CourseMaster::where('pk', $coursePk)->value('course_name') ?? 'Course';

        $notice = DB::table('student_notice_status as sns')
            ->leftJoin('subject_master as sm', 'sns.subject_master_pk', '=', 'sm.pk')
            ->where('sns.pk', $noticeStatusPk)
            ->select('sns.subject_topic', 'sm.subject_name')
            ->first();

        $subjectName = $notice->subject_name ?? 'Subject';
        $topicName = 'Topic';
        if (!empty($notice->subject_topic)) {
            $topicName = DB::table('timetable')->where('pk', $notice->subject_topic)->value('subject_topic') ?? 'Topic';
        }

        $dateLabel = date('d M Y', strtotime($memoDate));
        $message = "A Memo has been issued for {$courseName} - {$subjectName} ({$topicName}) on {$dateLabel}.";
        if (!empty($remark)) {
            $message .= " Remark: {$remark}";
        }

        app(NotificationService::class)->create(
            (int) $receiverUserId,
            'memo_notice',
            'Memo',
            $memoPk,
            'Memo Issued',
            $message
        );
    } catch (\Exception $e) {
        \Log::error('Failed to send memo notification: ' . $e->getMessage());
    }
}

/**
 * Notify the OT about a memo lifecycle change (issued / updated).
 */
private function notifyStudentAboutMemo(
    int $memoPk,
    int $studentPk,
    int $coursePk,
    int $noticeStatusPk,
    string $memoDate,
    string $title,
    string $actionPhrase
): void {
    try {
        $receiverUserId = app(NotificationReceiverService::class)->getStudentUserId($studentPk);
        if (!$receiverUserId) {
            return;
        }

        $courseName = CourseMaster::where('pk', $coursePk)->value('course_name') ?? 'Course';

        $notice = DB::table('student_notice_status as sns')
            ->leftJoin('subject_master as sm', 'sns.subject_master_pk', '=', 'sm.pk')
            ->where('sns.pk', $noticeStatusPk)
            ->select('sns.subject_topic', 'sm.subject_name')
            ->first();

        $subjectName = $notice->subject_name ?? 'Subject';
        $topicName = 'Topic';
        if (!empty($notice->subject_topic)) {
            $topicName = DB::table('timetable')->where('pk', $notice->subject_topic)->value('subject_topic') ?? 'Topic';
        }

        $dateLabel = date('d M Y', strtotime($memoDate));

        app(NotificationService::class)->create(
            (int) $receiverUserId,
            'memo_notice',
            'Memo',
            $memoPk,
            $title,
            "A Memo for {$courseName} - {$subjectName} ({$topicName}) on {$dateLabel} {$actionPhrase}."
        );
    } catch (\Exception $e) {
        \Log::error("Failed to send memo notification ({$title}): " . $e->getMessage());
    }
}

/**
 * Notify the OT about a notice lifecycle change (updated / closed / deleted).
 *
 * @param  object  $notice  Row from student_notice_status
 * @param  string  $title   Bell notification title
 * @param  string  $actionPhrase  e.g. "has been updated. Please review"
 */
private function notifyStudentAboutNotice(object $notice, string $title, string $actionPhrase): void
{
    try {
        $studentPk = !empty($notice->student_pk)
            ? (int) $notice->student_pk
            : $this->resolveNoticeStudentPk((int) $notice->pk);

        if (!$studentPk) {
            return;
        }

        $receiverUserId = app(NotificationReceiverService::class)->getStudentUserId($studentPk);
        if (!$receiverUserId) {
            return;
        }

        $courseName = CourseMaster::where('pk', $notice->course_master_pk)->value('course_name') ?? 'Course';
        $subjectName = SubjectMaster::where('pk', $notice->subject_master_pk)->value('subject_name') ?? 'Subject';
        $topicName = 'Topic';
        if (!empty($notice->subject_topic)) {
            $topicName = DB::table('timetable')->where('pk', $notice->subject_topic)->value('subject_topic') ?? 'Topic';
        }
        $dateLabel = !empty($notice->date_)
            ? date('d M Y', strtotime((string) $notice->date_))
            : date('d M Y');

        app(NotificationService::class)->create(
            (int) $receiverUserId,
            'memo_notice',
            'Notice',
            (int) $notice->pk,
            $title,
            "A Notice for {$courseName} - {$subjectName} ({$topicName}) on {$dateLabel} {$actionPhrase}."
        );
    } catch (\Exception $e) {
        \Log::error("Failed to send notice notification ({$title}): " . $e->getMessage());
    }
}

/**
 * Resolve student_pk for a notice (attendance-based or direct).
 */
private function resolveNoticeStudentPk(int $noticePk): ?int
{
    $row = DB::table('student_notice_status as sns')
        ->leftJoin('course_student_attendance as csa', 'sns.course_student_attendance_pk', '=', 'csa.pk')
        ->where('sns.pk', $noticePk)
        ->select(DB::raw('COALESCE(csa.Student_master_pk, sns.student_pk) as student_pk'))
        ->first();

    if (!$row || $row->student_pk === null) {
        return null;
    }

    return (int) $row->student_pk;
}

/**
 * Notify the other party when a new notice/memo chat message is posted.
 * Admin/faculty (role f) → OT student; OT (role s) → incharge/admin faculty.
 */
private function sendMemoNoticeChatNotification(
    int $memoNoticeId,
    string $type,
    string $roleType,
    ?string $messagePreview = null
): void {
    try {
        $type = $type === 'memo' ? 'memo' : 'notice';
        $moduleLabel = $type === 'memo' ? 'Memo' : 'Notice';
        $preview = $messagePreview !== null && $messagePreview !== ''
            ? mb_substr($messagePreview, 0, 100)
            : '';

        if ($roleType === 's') {
            $receiverIds = $this->resolveMemoNoticeReceiverIds($memoNoticeId, $type);
            if ($receiverIds === []) {
                return;
            }

            $message = "A participant has replied to the {$moduleLabel}. Please review.";
            if ($preview !== '') {
                $message .= " Message: {$preview}";
            }

            app(NotificationService::class)->createMultiple(
                $receiverIds,
                'memo_notice',
                $moduleLabel,
                $memoNoticeId,
                "OT Replied to {$moduleLabel}",
                $message
            );

            return;
        }

        $studentPk = $this->resolveMemoNoticeStudentPk($memoNoticeId, $type);
        if (!$studentPk) {
            return;
        }

        $receiverUserId = app(NotificationReceiverService::class)->getStudentUserId($studentPk);
        if (!$receiverUserId) {
            return;
        }

        $message = "The incharge has sent a new message on your {$moduleLabel}.";
        if ($preview !== '') {
            $message .= " Message: {$preview}";
        }

        app(NotificationService::class)->create(
            (int) $receiverUserId,
            'memo_notice',
            $moduleLabel,
            $memoNoticeId,
            "New Message on Your {$moduleLabel}",
            $message
        );
    } catch (\Exception $e) {
        \Log::error('Failed to send memo/notice chat notification: ' . $e->getMessage());
    }
}

/**
 * Resolve the student_pk for a notice or memo conversation thread.
 */
private function resolveMemoNoticeStudentPk(int $id, string $type): ?int
{
    if ($type === 'memo') {
        $pk = DB::table('student_memo_status')->where('pk', $id)->value('student_pk');

        return $pk !== null ? (int) $pk : null;
    }

    $pk = DB::table('student_notice_status')->where('pk', $id)->value('student_pk');

    return $pk !== null ? (int) $pk : null;
}

/**
 * Resolve the bell notification receiver ids (user_credentials.user_id) for the admin/incharge
 * who issued a notice/memo. The notice's faculty_master_pk (scalar or JSON array like "[14,9]")
 * maps to faculty_master.employee_master_pk, which equals user_credentials.user_id used by the header bell.
 *
 * @return int[]
 */
private function resolveMemoNoticeReceiverIds($id, $type): array
{
    return app(NotificationReceiverService::class)->getMemoNoticeAdminReceivers((int) $id, (string) $type);
}

/**
 * Mark the current viewer's unread chat notifications for a given conversation as read.
 * Called when an admin opens a notice/memo conversation so the reply-icon badge and header
 * bell clear for messages they have now seen.
 */
private function markMemoNoticeChatRead($id, $type): void
{
    $user = auth()->user();
    if (!$user || empty($user->user_id)) {
        return;
    }

    $module = $type === 'memo' ? 'Memo' : 'Notice';

    try {
        DB::table('notifications')
            ->where('receiver_user_id', $user->user_id)
            ->where('type', 'memo_notice')
            ->where('module_name', $module)
            ->where('reference_pk', $id)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);
    } catch (\Exception $e) {
        \Log::error('Mark memo/notice chat read failed: ' . $e->getMessage());
    }
}

public function memo_notice_conversation_student_bkp(Request $request){
      $validated = $request->validate([
        'memo_notice_id' => 'required|exists:student_notice_status,pk',
        
        'message' => 'required|string|max:500',
        'student_id' => 'required|exists:student_master,pk',
        'document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
         ]);

    // Handle file upload
    $filePath = null;
    if ($request->hasFile('document')) {
        $file = $request->file('document');
        $filePath = $file->store('notice_documents', 'public'); // stored in /storage/app/public/notice_documents
    }

    // Insert into your table
   $data = DB::table('notice_message_student_decip_incharge')->insert([
        'student_notice_status_pk' => $validated['memo_notice_id'],
        'student_decip_incharge_msg' => $validated['message'],
        'doc_upload' => $filePath,
        'role_type' => 's',
        'created_by' => $validated['student_id'], // Replace with correct user ID
         ]);

   if ($data) {
        return redirect()->back()->with('success', 'Notice msg created successfully.');

        } else {
        return redirect()->back()->with('error', 'Failed to create Memo/Notice. Please try again.');
    }
}
public function get_conversation_model($id, $type, $user_type, Request $request)
{
    // If type invalid → default notice
    if (!in_array($type, ['memo', 'notice'])) {
        $type = 'notice';
    }

    // Opening the conversation clears the viewer's unread badge + bell for this thread.
    $this->markMemoNoticeChatRead($id, $type);

    if ($type == 'memo') {
        $conversations = DB::table('memo_message_student_decip_incharge as mmsdi')
            ->join('student_memo_status as sms', 'mmsdi.student_memo_status_pk', '=', 'sms.pk')
            ->join('student_master as sm', 'sms.student_pk', '=', 'sm.pk')
            ->where('mmsdi.student_memo_status_pk', $id)
            ->orderBy('mmsdi.created_date', 'asc')
            ->select(
                'mmsdi.*',
                'sms.pk as notice_id',
                'sms.communication_status as notice_status',
                'sm.pk as student_id',
                'sm.display_name as student_name'
            )
            ->get();
            // print_r($conversations);die;

    } else {
        // Use LEFT JOINs so direct notices (course_student_attendance_pk=0) are not excluded
        $conversations = DB::table('notice_message_student_decip_incharge as nmsdi')
            ->leftJoin('student_notice_status as sns', 'nmsdi.student_notice_status_pk', '=', 'sns.pk')
            ->leftJoin('course_student_attendance as csa', 'sns.course_student_attendance_pk', '=', 'csa.pk')
            ->leftJoin('student_master as sm', function ($join) {
                $join->whereRaw('sm.pk = COALESCE(csa.Student_master_pk, sns.student_pk)');
            })
            ->where('nmsdi.student_notice_status_pk', $id)
            ->orderBy('nmsdi.created_date', 'asc')
            ->select(
                'nmsdi.*',
                'sns.pk as notice_id',
                'sns.status as notice_status',
                'sm.pk as student_id',
                'sm.display_name as student_name'
            )
            ->get();
    }

    // Resolve notice status + student PK directly from the source record,
    // so the reply form works even when conversations collection is empty.
    if ($type === 'notice') {
        $snsRow = DB::table('student_notice_status as sns')
            ->leftJoin('course_student_attendance as csa', 'sns.course_student_attendance_pk', '=', 'csa.pk')
            ->where('sns.pk', $id)
            ->select('sns.status as notice_status', DB::raw('COALESCE(csa.Student_master_pk, sns.student_pk) as student_pk'))
            ->first();
        $noticeStatus = (int) ($snsRow->notice_status ?? 0);
        $studentPk    = (int) ($snsRow->student_pk ?? 0);
    } else {
        $smsRow = DB::table('student_memo_status')->where('pk', $id)->first();
        $noticeStatus = (int) ($smsRow->communication_status ?? 0);
        $studentPk    = (int) ($smsRow->student_pk ?? 0);
    }

    // Multiple distinct admins/faculty can post in the same conversation, so
    // resolve each sender's real name + role instead of collapsing them all
    // to a generic "Admin" label (same helper the Discipline memo chat uses).
    $conversations = $conversations->map(function ($item) {
        $identity = resolve_chat_sender_identity($item->created_by, $item->role_type);
        $item->display_name = $identity['display_name'];
        $item->role_name = $identity['role_name'];
        $item->user_type = $item->role_type == 'f' ? 'admin' : ($item->role_type == 's' ? 'student' : 'unknown');
        return $item;
    });

    return view('admin.courseAttendanceNoticeMap.conversation_model', compact('conversations', 'type', 'id', 'user_type', 'noticeStatus', 'studentPk'));
}

public function get_conversation_model_bkp($id,$type, Request $request)
{
    // $conversations = DB::table('notice_message_student_decip_incharge')
    //     ->where('student_notice_status_pk', $id)
    //     ->orderBy('created_date', 'asc')
    //     ->get()
        $conversations = DB::table('notice_message_student_decip_incharge as nmsdi')
    ->join('student_notice_status as sns', 'nmsdi.student_notice_status_pk', '=', 'sns.pk')
    ->join('course_student_attendance as csa', 'sns.course_student_attendance_pk', '=', 'csa.pk')
    ->join('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')
    ->where('nmsdi.student_notice_status_pk', $id)
    ->orderBy('nmsdi.created_date', 'asc')
    ->select(
        'nmsdi.*',
        'sns.pk as notice_id',
        'sns.status as notice_status',
        'sm.pk as student_id',
        'sm.display_name as student_name'
    )
    ->get()
        ->map(function ($item) {
            if ($item->role_type == 'f') {
                $user = DB::table('users')->find($item->created_by);
                $item->display_name = 'Admin';
                $item->user_type = 'admin';
            } elseif ($item->role_type == 's') {
                $student = DB::table('student_master')->where('pk', $item->created_by)->first();

                $item->display_name = $student->display_name ?? 'Student';
                $item->user_type = 'student';
            } else {
                $item->display_name = 'Unknown';
                $item->user_type = 'unknown';
            }
            return $item;
        });

    return view('admin.courseAttendanceNoticeMap.conversation_model', compact('conversations','type','id'));
}
public function memo_notice_conversation_model(Request $request){
    $isAjax = $request->ajax() || $request->wantsJson();

    try {
        $validated = $request->validate([
            'memo_notice_id' => 'required',
            'student_decip_incharge_msg' => 'required_without:document|nullable|string|max:500',
            'created_by' => 'required',
            'document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ], [
            'document.mimes'  => 'Only JPG, JPEG, PNG, and PDF files are allowed.',
            'document.max'    => 'Attachment must not exceed 2 MB.',
            'student_decip_incharge_msg.required_without' => 'Please enter a message or attach a file.',
            'student_decip_incharge_msg.max' => 'Message must not exceed 500 characters.',
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        $firstError = collect($e->errors())->flatten()->first() ?? 'Validation failed.';
        if ($isAjax) {
            return response()->json(['success' => false, 'message' => $firstError, 'errors' => $e->errors()], 422);
        }
        throw $e;
    }

    // Handle file upload
    $filePath = null;
    if ($request->hasFile('document')) {
        $file = $request->file('document');
        $filePath = $file->store('notice_documents', 'public'); // stored in /storage/app/public/notice_documents
    }

    $messageText = $validated['student_decip_incharge_msg'] ?? '';
    $chatType = in_array($request->type, ['memo', 'notice'], true) ? $request->type : 'notice';
    $roleType = $request->role_type === 's' ? 's' : 'f';

    if ($request->type == 'memo') {
        $data = DB::table('memo_message_student_decip_incharge')->insert([
            'student_memo_status_pk' => $validated['memo_notice_id'],
            'student_decip_incharge_msg' => $messageText,
            'doc_upload' => $filePath,
            'role_type' => $request->role_type,
            'created_by' => $validated['created_by'],
            'created_date' => now('UTC'),
        ]);
        if ($data) {
            $this->sendMemoNoticeChatNotification(
                (int) $validated['memo_notice_id'],
                $chatType,
                $roleType,
                $messageText !== '' ? $messageText : null
            );

            if ($isAjax) {
                return response()->json(['success' => true, 'message' => 'Memo msg created successfully.']);
            }
            return redirect()->back()->with('success', 'Memo msg created successfully.');
        }
        if ($isAjax) {
            return response()->json(['success' => false, 'message' => 'Failed to create Memo/Notice. Please try again.'], 500);
        }
        return redirect()->back()->with('error', 'Failed to create Memo/Notice. Please try again.');
    }

    if ($request->type == 'notice') {
        $data = DB::table('notice_message_student_decip_incharge')->insert([
            'student_notice_status_pk' => $validated['memo_notice_id'],
            'student_decip_incharge_msg' => $messageText,
            'doc_upload' => $filePath,
            'role_type' => $request->role_type,
            'created_by' => $validated['created_by'],
            'created_date' => now('UTC'),
        ]);
        if ($data) {
            $this->sendMemoNoticeChatNotification(
                (int) $validated['memo_notice_id'],
                $chatType,
                $roleType,
                $messageText !== '' ? $messageText : null
            );

            if ($isAjax) {
                return response()->json(['success' => true, 'message' => 'Notice msg created successfully.']);
            }
            return redirect()->back()->with('success', 'Notice msg created successfully.');
        }
        if ($isAjax) {
            return response()->json(['success' => false, 'message' => 'Failed to create Memo/Notice. Please try again.'], 500);
        }
        return redirect()->back()->with('error', 'Failed to create Memo/Notice. Please try again.');
    }

    if ($isAjax) {
        return response()->json(['success' => false, 'message' => 'Invalid type.'], 400);
    }
    return redirect()->back()->with('error', 'Invalid type.');
}
   public function getMemoData_bkp(Request $request)
{
    $memoId = $request->memo_notice_id;

    $memo = DB::table('student_notice_status')
    ->join('faculty_master as fm', 'student_notice_status.faculty_master_pk', '=', 'fm.pk')
    ->join('course_master as cm', 'student_notice_status.course_master_pk', '=', 'cm.pk')
    ->join('subject_master as subm', 'student_notice_status.subject_master_pk', '=', 'subm.pk')
        ->join('course_student_attendance as csa', 'student_notice_status.course_student_attendance_pk', '=', 'csa.pk')
        ->join('student_master as sm', 'csa.Student_master_pk', '=', 'sm.pk')
        ->join('timetable as t', 'student_notice_status.subject_topic', '=', 't.pk')
        ->select(
            'student_notice_status.*',
            'subm.subject_name',
            'sm.display_name as student_name',
            't.subject_topic as topic_name',
            'fm.full_name as faculty_name',
            'cm.course_name as course_name',

        )
        ->where('student_notice_status.pk', $memoId)
        ->first();

    if (!$memo) {
        return response()->json(['error' => 'Data not found!'], 404);
    }
   // hmko niklna hoga same student ne same course ke liye kitne memo le rkkhi h
    $memoCount = DB::table('student_notice_status')
        ->where('course_student_attendance_pk', $memo->course_student_attendance_pk)
        ->where('subject_master_pk', $memo->subject_master_pk)
        ->count();

    return response()->json([
        'course_master_name' => $memo->course_name,
        'course_master_pk' => $memo->course_master_pk,
        'student_notice_status_pk' => $memo->pk,
       
        'date_' => $memo->date_,
        'subject_master_name' => $memo->subject_name,
        'subject_master_pk' => $memo->subject_master_pk,
        'student_name' => $memo->student_name,
        'subject_topic' => $memo->topic_name,
        'class_session_master_pk' => $memo->class_session_master_pk,
        'faculty_name' => $memo->faculty_name,
        'memo_date' => $memo->date_, // or any other
    ]);
}
public function getMemoData(Request $request)
{
    $memoId = $request->memo_notice_id;

    $memo = DB::table('student_notice_status')
        ->leftJoin('faculty_master as fm', DB::raw('fm.pk'), '=', DB::raw("CASE WHEN student_notice_status.faculty_master_pk LIKE '[%' THEN JSON_UNQUOTE(JSON_EXTRACT(student_notice_status.faculty_master_pk, '$[0]')) ELSE student_notice_status.faculty_master_pk END"))
        ->leftJoin('course_master as cm', 'student_notice_status.course_master_pk', '=', 'cm.pk')
        ->leftJoin('subject_master as subm', 'student_notice_status.subject_master_pk', '=', 'subm.pk')
        ->leftJoin('student_master as sm', 'student_notice_status.student_pk', '=', 'sm.pk')
        ->leftJoin('timetable as t', 'student_notice_status.subject_topic', '=', 't.pk')
        ->select(
            'student_notice_status.*',
            'subm.subject_name',
            'sm.display_name as student_name',
            'sm.generated_OT_code as generated_OT_code',
            't.subject_topic as topic_name',
            'fm.full_name as faculty_name',
            'cm.course_name as course_name'
        )
        ->where('student_notice_status.pk', $memoId)
        ->first();

    if (!$memo) {
        return response()->json(['error' => 'Data not found!'], 404);
    }

    // ✅ Memo Count: same student, same subject, same course
    $memoCount = DB::table('student_memo_status')
        ->where('student_pk', $memo->student_pk)
        ->where('course_master_pk', $memo->course_master_pk)
        ->count();
        $memo_number = ($memo->course_name ?? '') . ' / ' . ($memoCount + 1) . ' / ' . ($memo->generated_OT_code ?? '');


    return response()->json([
        'course_master_name' => $memo->course_name ?? '',
        'course_master_pk' => $memo->course_master_pk,
        'student_pk' => $memo->student_pk,
        'student_notice_status_pk' => $memo->pk,
        'date_' => $memo->date_,
        'subject_master_name' => $memo->subject_name ?? '',
        'subject_master_pk' => $memo->subject_master_pk,
        'student_name' => $memo->student_name ?? '',
        'subject_topic' => $memo->topic_name ?? '',
        'class_session_master_pk' => $memo->class_session_master_pk,
        'session_name' => $memo->class_session_master_pk,
        'venue_id' => $memo->venue_id,
        'faculty_name' => $memo->faculty_name ?? '',
        'memo_date' => $memo->date_,
        'memo_count' => $memoCount,
        'memo_number' => $memo_number
    ]);
}

public function getGeneratedMemoData(Request $request)
{
    $memoId = $request->memo_id;

    $memo = DB::table('student_memo_status as sms')
        ->leftJoin('student_notice_status as sns', 'sms.student_notice_status_pk', '=', 'sns.pk')
        ->leftJoin('student_master as sm', 'sms.student_pk', '=', 'sm.pk')
        ->leftJoin('course_master as cm', 'sms.course_master_pk', '=', 'cm.pk')
        ->leftJoin('faculty_master as fm', DB::raw('fm.pk'), '=', DB::raw("CASE WHEN sns.faculty_master_pk LIKE '[%' THEN JSON_UNQUOTE(JSON_EXTRACT(sns.faculty_master_pk, '$[0]')) ELSE sns.faculty_master_pk END"))
        ->leftJoin('subject_master as subm', 'sns.subject_master_pk', '=', 'subm.pk')
        ->leftJoin('timetable as t', 'sns.subject_topic', '=', 't.pk')
        ->where('sms.pk', $memoId)
        ->select(
            'sms.*',
            'sns.date_ as notice_date',
            'sns.subject_topic',
            'sns.class_session_master_pk',
            'sns.faculty_master_pk',
            'sm.display_name as student_name',
            'sm.generated_OT_code',
            'cm.course_name',
            'fm.full_name as faculty_name',
            'subm.subject_name',
            't.subject_topic as topic_name'
        )
        ->first();

    if (!$memo) {
        return response()->json(['error' => 'Memo not found!'], 404);
    }

    // Calculate memo count
    $memoCount = DB::table('student_memo_status')
        ->where('student_pk', $memo->student_pk)
        ->where('course_master_pk', $memo->course_master_pk)
        ->count();

    $memo_number = ($memo->course_name ?? '') . ' / ' . ($memoCount) . ' / ' . ($memo->generated_OT_code ?? '');

    return response()->json([
        // Memo-specific fields
        'memo_type_master_pk' => $memo->memo_type_master_pk ?? null,
        'memo_notice_template_pk' => $memo->memo_notice_template_pk ?? null,
        'venue_master_pk' => $memo->venue_master_pk ?? null,
        'date' => $memo->date ?? null,
        'start_time' => $memo->start_time ?? null,
        'message' => $memo->message ?? null,
        // Notice-related fields
        'course_master_name' => $memo->course_name ?? '',
        'course_master_pk' => $memo->course_master_pk ?? null,
        'student_pk' => $memo->student_pk ?? null,
        'student_notice_status_pk' => $memo->student_notice_status_pk ?? null,
        'date_' => $memo->notice_date ?? $memo->date ?? '',
        'student_name' => $memo->student_name ?? '',
        'subject_topic' => $memo->topic_name ?? '',
        'subject_master_name' => $memo->subject_name ?? '',
        'class_session_master_pk' => $memo->class_session_master_pk ?? null,
        'faculty_name' => $memo->faculty_name ?? '',
        'memo_count' => $memoCount,
        'memo_number' => $memo_number,
        'session_name' => $memo->class_session_master_pk ?? ''
    ]);
}

public function store_memo_status(Request $request)
{
    // print_r($request->all());die;
    $validated = $request->validate([
        'student_notice_status_pk' => 'required|integer',
        'memo_type_master_pk'             => 'required|integer',
        'student_pk'                      => 'required|integer',
        'course_master_pk'                => 'required|integer',
        'course_master_name'             => 'required|string',
        'memo_count'                      => 'required|integer',
        'date_memo_notice'               => 'required|date',
        'venue'                          => 'required|integer',
        'meeting_time'                   => 'required|date_format:H:i',
        'memo_notice_template_pk'        => 'required|exists:memo_notice_templates,pk',
        'Remark'                         => 'nullable|string',
    ], [
        'memo_notice_template_pk.required' => 'No template is configured for the selected Memo Type on this course. Please configure one before generating the memo.',
        'memo_notice_template_pk.exists'   => 'The selected template is invalid. Please choose another one.',
    ]);

    $memoPk = DB::table('student_memo_status')->insertGetId([
        'student_notice_status_pk' => $validated['student_notice_status_pk'],
        'memo_type_master_pk'             => $validated['memo_type_master_pk'],
        'student_pk'                      => $validated['student_pk'],
        'course_master_pk'                => $validated['course_master_pk'],
        'memo_no'                         => $request->memo_number,
        'memo_count'                      => $validated['memo_count'],
        'venue_master_pk'                 => $validated['venue'],
        'date'                            => $validated['date_memo_notice'],
        'start_time'                      => $validated['meeting_time'],
        'message'                         => $validated['Remark'] ?? null,
        'memo_notice_template_pk'         => $validated['memo_notice_template_pk'] ?? null,
        // Freeze the chosen template's content now, so editing it later doesn't change what's already sent.
        'template_snapshot'               => build_memo_notice_template_snapshot($validated['memo_notice_template_pk'] ?? null),
        'created_date'                      => now(),
        'modified_date'                      => now(),
        'status'                      => 1,
        'communication_status' => 1, // Assuming 1 means 'active'
    ]);

    $this->sendIssuedMemoNotification(
        (int) $memoPk,
        (int) $validated['student_pk'],
        (int) $validated['course_master_pk'],
        (int) $validated['student_notice_status_pk'],
        $validated['date_memo_notice'],
        $validated['Remark'] ?? null
    );

    return redirect()->back()->with('success', 'Memo saved successfully.');

}

/**
 * Edit an already-generated memo: Memo Type, Template, participant (student),
 * Venue, Date, Meeting Time and Message are all correctable after the fact.
 * Does not touch the source student_notice_status row — this reassigns the
 * memo itself, not the original notice's history.
 */
public function updateMemoStatus(Request $request, $id)
{
    if (! $this->userCanManageMemoNotice()) {
        return response()->json(['success' => false, 'message' => 'You are not authorized to edit this memo.'], 403);
    }

    $memo = DB::table('student_memo_status')->where('pk', $id)->first();
    if (!$memo) {
        return response()->json(['success' => false, 'message' => 'Memo not found.'], 404);
    }
    if ($memo->status == 2) {
        return response()->json(['success' => false, 'message' => 'Closed memos cannot be edited.'], 422);
    }

    $validated = $request->validate([
        'student_pk'              => 'required|integer|exists:student_master,pk',
        'memo_type_master_pk'     => 'required|integer|exists:memo_type_master,pk',
        'memo_notice_template_pk' => 'required|exists:memo_notice_templates,pk',
        'venue'                   => 'required|integer',
        'date_memo_notice'        => 'required|date',
        'meeting_time'            => 'required|date_format:H:i',
        'Remark'                  => 'nullable|string',
    ], [
        'memo_notice_template_pk.required' => 'No template is configured for the selected Memo Type on this course. Please configure one before saving.',
        'memo_notice_template_pk.exists'   => 'The selected template is invalid. Please choose another one.',
    ]);

    // Reassignment must stay within the memo's own course.
    $belongsToCourse = DB::table('student_master_course__map')
        ->where('course_master_pk', $memo->course_master_pk)
        ->where('student_master_pk', $validated['student_pk'])
        ->where('active_inactive', 1)
        ->exists();
    if (!$belongsToCourse) {
        return response()->json(['success' => false, 'message' => 'Selected student is not enrolled in this course.'], 422);
    }

    DB::table('student_memo_status')->where('pk', $id)->update([
        'student_pk'              => $validated['student_pk'],
        'memo_type_master_pk'     => $validated['memo_type_master_pk'],
        'memo_notice_template_pk' => $validated['memo_notice_template_pk'] ?? null,
        // Re-pinning the template also re-freezes its content as of now.
        'template_snapshot'       => build_memo_notice_template_snapshot($validated['memo_notice_template_pk'] ?? null),
        'venue_master_pk'         => $validated['venue'],
        'date'                    => $validated['date_memo_notice'],
        'start_time'              => $validated['meeting_time'],
        'message'                 => $validated['Remark'] ?? null,
        'modified_date'           => now(),
    ]);

    $this->notifyStudentAboutMemo(
        (int) $id,
        (int) $validated['student_pk'],
        (int) $memo->course_master_pk,
        (int) $memo->student_notice_status_pk,
        $validated['date_memo_notice'],
        'Memo Updated',
        'has been updated. Please review'
    );

    return response()->json(['success' => true, 'message' => 'Memo updated successfully.']);
}

/**
 * Conclude (close) a notice/memo conversation from the chat panel's "End Chat" action.
 * Memo  → sets status/communication_status to closed and records the conclusion type + remark.
 * Notice → closes the notice (status = 2).
 */
public function endChat(Request $request)
{
    $validator = Validator::make($request->all(), [
        'id'                        => 'required|integer',
        'type'                      => 'required|in:notice,memo',
        'memo_conclusion_master_pk' => 'nullable|integer',
        'marks_deducted'            => 'nullable|numeric|min:0',
        'conclusion_remark'         => 'nullable|string',
    ]);

    // "Marks Deduction" is looked up by name, not a hardcoded pk — matches the
    // client-side toggleEndChatMarks() check in index.blade.php.
    $validator->sometimes('marks_deducted', 'required', function ($input) {
        if (empty($input->memo_conclusion_master_pk)) {
            return false;
        }
        return DB::table('memo_conclusion_master')
            ->where('pk', $input->memo_conclusion_master_pk)
            ->value('discussion_name') === 'Marks Deduction';
    });

    if ($validator->fails()) {
        return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
    }
    $validated = $validator->validated();

    try {
        if ($validated['type'] === 'memo') {
            DB::table('student_memo_status')
                ->where('pk', $validated['id'])
                ->update([
                    'status'                    => 2,
                    'communication_status'      => 2,
                    'memo_conclusion_master_pk' => $validated['memo_conclusion_master_pk'],
                    'mark_of_deduction'         => $validated['marks_deducted'] ?? null,
                    'conclusion_remark'         => $validated['conclusion_remark'],
                    'modified_date'             => now(),
                ]);

            $memoForNotify = DB::table('student_memo_status')->where('pk', $validated['id'])->first();
            if ($memoForNotify) {
                $this->notifyStudentAboutMemo(
                    (int) $memoForNotify->pk,
                    (int) $memoForNotify->student_pk,
                    (int) $memoForNotify->course_master_pk,
                    (int) $memoForNotify->student_notice_status_pk,
                    (string) ($memoForNotify->date ?? now()->format('Y-m-d')),
                    'Memo Closed',
                    'has been closed'
                );
            }
        } else {
            DB::table('student_notice_status')
                ->where('pk', $validated['id'])
                ->update([
                    'status'             => 2,
                    'conclusion_type_pk' => $validated['memo_conclusion_master_pk'],
                    'mark_of_deduction'  => $validated['marks_deducted'] ?? null,
                    'conclusion_remark'  => $validated['conclusion_remark'],
                ]);

            $noticeForNotify = DB::table('student_notice_status')->where('pk', $validated['id'])->first();
            if ($noticeForNotify) {
                if (empty($noticeForNotify->student_pk)) {
                    $noticeForNotify->student_pk = $this->resolveNoticeStudentPk((int) $validated['id']);
                }
                $this->notifyStudentAboutNotice(
                    $noticeForNotify,
                    'Notice Closed',
                    'has been closed'
                );
            }
        }

        return response()->json(['success' => true, 'message' => 'Conversation ended successfully.']);
    } catch (\Exception $e) {
        \Log::error('End chat failed: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Failed to end conversation.'], 500);
    }
}
public function send_direct_notice_save(Request $request)
    {
        $request->validate([
            'course_master_pk'     => 'required|exists:course_master,pk',
            'date_of_notice'       => 'required|date',
            'selected_student_list'=> 'required|array|min:1',
            'remark'               => 'nullable|string',
        ]);

        // This flow has no template picker, so pin + freeze whichever active Notice
        // template applies to the course (same fallback the live viewers use).
        $templatePk = resolve_default_memo_notice_template_pk($request->course_master_pk, 'Notice');
        $templateSnapshot = build_memo_notice_template_snapshot($templatePk);

        $rows = [];
        foreach ($request->selected_student_list as $studentPk) {
            $rows[] = [
                'course_master_pk'            => $request->course_master_pk,
                'date_'                       => $request->date_of_notice,
                'student_pk'                  => $studentPk,
                'message'                     => $request->remark,
                'notice_memo'                 => 1,
                'venue_id'                    => 0,
                'faculty_master_pk'           => '',
                'course_student_attendance_pk'=> 0,
                'status'                      => 1,
                'memo_notice_template_pk'     => $templatePk,
                'template_snapshot'           => $templateSnapshot,
            ];
        }

        DB::table('student_notice_status')->insert($rows);

        $this->sendIssuedNoticeNotifications($rows, 1, $request->remark);

        return redirect()->route('memo.notice.management.index')
            ->with('success', 'Notices sent successfully.');
    }

    public function getStudentsForNotice(Request $request)
    {
        $courseId = (int) $request->course_id;

        if (!$courseId) {
            return response()->json(['status' => false, 'message' => 'Course is required.']);
        }

        $students = DB::table('student_master_course__map as a')
            ->join('student_master as s', 'a.student_master_pk', '=', 's.pk')
            ->where('a.course_master_pk', $courseId)
            ->where('a.active_inactive', 1)
            ->whereNotNull('s.display_name')
            ->where('s.display_name', '!=', '')
            ->orderBy('s.display_name')
            ->select('s.pk', 's.display_name', 's.generated_OT_code')
            ->get()
            ->map(fn($s) => [
                'pk'                => (int) $s->pk,
                'display_name'      => $s->display_name,
                'generated_OT_code' => $s->generated_OT_code,
            ])
            ->values();

        return response()->json(['status' => true, 'students' => $students]);
    }

    function send_only_notice(Request $request){
        // Session dropdowns mirror the Attendance filter shell so the shared
        // get.attendance.list endpoint (page_context = send_notice) can be reused.
        $sessions = ClassSessionMaster::get();

        $maunalSessions = Timetable::select('class_session')
            ->where('class_session', 'REGEXP', '[0-9]{2}:[0-9]{2} [AP]M - [0-9]{2}:[0-9]{2} [AP]M')
            ->groupBy('class_session')
            ->get();

        // Only active courses, and only those assigned to this CC — Admin/Super
        // Admin/PA get an empty restriction list back (see all active courses).
        $data_course_id = get_Role_by_course();

        $courseMasters = CourseMaster::where('active_inactive', 1)
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            })
            ->when(!empty($data_course_id), function ($q) use ($data_course_id) {
                $q->whereIn('pk', $data_course_id);
            })
            ->orderBy('couse_short_name')
            ->select('couse_short_name', 'course_name', 'pk')
            ->get()
            ->toArray();

        return view('admin.courseAttendanceNoticeMap.send_only_notice', compact('courseMasters', 'sessions', 'maunalSessions'));
    }
function view_all_notice_list($group_pk, $course_pk, $timetable_pk)
    {
        try {
            
   $courseGroup = CourseGroupTimetableMapping::with([
                'course:pk,course_name',
                'timetable',
                'timetable.faculty:pk,full_name',
                'timetable.classSession:pk,start_time,end_time'
            ])
                ->where('group_pk', $group_pk)
                ->where('Programme_pk', $course_pk)
                ->where('timetable_pk', $timetable_pk)
                ->first();
                




                      $students = DB::table('student_course_group_map as scgm')
                ->join('student_master as sm', 'sm.pk', '=', 'scgm.student_master_pk')
                ->leftJoin('course_student_attendance as csa', function ($join) use ($course_pk, $group_pk, $timetable_pk) {
                    $join->on('csa.Student_master_pk', '=', 'scgm.student_master_pk')
                        ->where('csa.course_master_pk', '=', $course_pk)
                        ->where('csa.group_type_master_course_master_map_pk', '=', $group_pk)
                        ->where('csa.timetable_pk', '=', $timetable_pk);
                })
                ->where('scgm.group_type_master_course_master_map_pk', $group_pk)
                ->where('scgm.active_inactive', 1)
                ->select(
                    'csa.pk',
                    'csa.status',
                    'scgm.student_master_pk as Student_master_pk',
                    'sm.display_name',
                    'sm.pk as student_id',
                    'sm.generated_OT_code as generated_OT_code'
                )
                ->orderBy('sm.display_name')
                ->paginate(30);

            return view('admin.courseAttendanceNoticeMap.view_all_notice_list', compact('students','courseGroup', 'group_pk', 'course_pk', 'timetable_pk'));
} catch (\Exception $e) {
            \Log::error('Error fetching attendance data: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while fetching attendance data: ' . $e->getMessage());
        }
    }

    /**
     * Notice-list partial rendered inside the "Notice List" modal on the
     * Send Direct Notice page. Returns the full student roster for the
     * selected course-group + timetable session.
     */
    public function noticeListModal($group_pk, $course_pk, $timetable_pk)
    {
        $data = $this->resolveNoticeListData($group_pk, $course_pk, $timetable_pk);

        return view('admin.courseAttendanceNoticeMap.partials.notice_list_modal', $data);
    }

    /**
     * Full-page version of the notice list (replaces the in-page modal). Opened
     * directly from the "Notice" action on the Send Direct Notice table.
     */
    public function noticeListPage($group_pk, $course_pk, $timetable_pk)
    {
        $data = $this->resolveNoticeListData($group_pk, $course_pk, $timetable_pk);

        return view('admin.courseAttendanceNoticeMap.notice_list', $data);
    }

    /**
     * Shared roster + notice-template lookup for the notice list (modal + page).
     * Returns the student roster for the selected course-group + timetable session.
     */
    protected function resolveNoticeListData($group_pk, $course_pk, $timetable_pk)
    {
        $courseGroup = CourseGroupTimetableMapping::with([
                'course:pk,course_name',
                'timetable',
                'timetable.faculty:pk,full_name',
                'timetable.classSession:pk,start_time,end_time'
            ])
            ->where('group_pk', $group_pk)
            ->where('Programme_pk', $course_pk)
            ->where('timetable_pk', $timetable_pk)
            ->first();

        $students = DB::table('student_course_group_map as scgm')
            ->join('student_master as sm', 'sm.pk', '=', 'scgm.student_master_pk')
            ->leftJoin('course_student_attendance as csa', function ($join) use ($course_pk, $group_pk, $timetable_pk) {
                $join->on('csa.Student_master_pk', '=', 'scgm.student_master_pk')
                    ->where('csa.course_master_pk', '=', $course_pk)
                    ->where('csa.group_type_master_course_master_map_pk', '=', $group_pk)
                    ->where('csa.timetable_pk', '=', $timetable_pk);
            })
            ->where('scgm.group_type_master_course_master_map_pk', $group_pk)
            ->where('scgm.active_inactive', 1)
            ->select(
                'csa.pk',
                'csa.status',
                'scgm.student_master_pk as Student_master_pk',
                'sm.display_name',
                'sm.pk as student_id',
                'sm.generated_OT_code as generated_OT_code'
            )
            ->orderBy('sm.display_name')
            ->get();

        // Notice templates for this course — offered as a picker at send time.
        $noticeTemplates = DB::table('memo_notice_templates')
            ->where('course_master_pk', $course_pk)
            ->where('memo_notice_type', 'Notice')
            ->where('active_inactive', 1)
            ->whereNull('deleted_at')
            ->orderBy('title')
            ->get(['pk', 'title']);

        return compact('students', 'courseGroup', 'group_pk', 'course_pk', 'timetable_pk', 'noticeTemplates');
    }
    function notice_direct_save(Request $request){
       
        try{
    $validated = $request->validate([
            'course_master_pk' => 'required|exists:course_master,pk',
            'subject_master_id' => 'required|exists:subject_master,pk',
            'topic_id' => 'required|exists:timetable,pk',
            'venue_id' => 'required',
            'class_session_master_pk' => 'required',
            'faculty_master_pk' => 'required',
            'memo_notice_template_pk' => 'nullable|exists:memo_notice_templates,pk',
            'selected_student_list' => 'required|array',


        ]);

        // Fall back to the course's active Notice template when none was picked,
        // so this send still gets a pinned + frozen template like the others do.
        $templatePk = $validated['memo_notice_template_pk']
            ?? resolve_default_memo_notice_template_pk($validated['course_master_pk'], 'Notice');
        $templateSnapshot = build_memo_notice_template_snapshot($templatePk);

        foreach ($validated['selected_student_list'] as $studentId) {
            $data[] = [
                'course_master_pk'           => $validated['course_master_pk'],
                'student_pk'                 => $studentId,
                'date_'                      => now()->toDateString(),
                'subject_master_pk'          => $validated['subject_master_id'],
                'subject_topic'              => $validated['topic_id'],
                'venue_id'                   => $validated['venue_id'],
                'class_session_master_pk'    => $validated['class_session_master_pk'],
                'faculty_master_pk'          => $validated['faculty_master_pk'],
                'course_student_attendance_pk' =>$request->input('attendance_pk_'.$studentId),
                'memo_notice_template_pk'    => $templatePk,
                'template_snapshot'          => $templateSnapshot,
                'notice_memo'                => 1,
            ];

    }
    // print_r($data);die;
   $insertdata =  DB::table('student_notice_status')->insert($data);
   if($insertdata){
    $this->sendIssuedNoticeNotifications($data, 1);

    return redirect('admin/memo-notice-management')->with('success', 'Notice sent successfully.');
   }
}catch (\Exception $e) {
            \Log::error('Error saving notice data: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while saving notice data: ' . $e->getMessage());
        }


}

    public function globalSearch(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json(['results' => [], 'query' => $q]);
        }

        $like = '%' . $q . '%';
        $results = [];

        // --- Notices & Direct Notices ---
        $notices = DB::table('student_notice_status as sns')
            ->join('student_master as sm', 'sm.pk', '=', 'sns.student_pk')
            ->join('course_master as cm', 'cm.pk', '=', 'sns.course_master_pk')
            ->where(function ($q2) use ($like) {
                $q2->where('sm.display_name', 'like', $like)
                   ->orWhere('sm.generated_OT_code', 'like', $like)
                   ->orWhere('cm.course_name', 'like', $like);
            })
            ->select(
                'sns.pk',
                'sns.course_student_attendance_pk',
                'sns.status',
                'sns.date_ as notice_date',
                'sm.display_name as student_name',
                'sm.generated_OT_code',
                'cm.course_name'
            )
            ->orderByDesc('sns.date_')
            ->limit(20)
            ->get();

        foreach ($notices as $row) {
            $isDirect = ($row->course_student_attendance_pk == 0);
            $results[] = [
                'type'         => $isDirect ? 'Direct Notice' : 'Notice',
                'type_key'     => $isDirect ? 'direct_notice' : 'notice',
                'student_name' => $row->student_name,
                'ot_code'      => $row->generated_OT_code,
                'course_name'  => $row->course_name,
                'detail'       => '—',
                'date'         => $row->notice_date,
                'status'       => $row->status,
                'pk'           => $row->pk,
            ];
        }

        // --- Memos ---
        $memos = DB::table('student_memo_status as sms')
            ->join('student_master as sm', 'sm.pk', '=', 'sms.student_pk')
            ->join('course_master as cm', 'cm.pk', '=', 'sms.course_master_pk')
            ->where(function ($q2) use ($like) {
                $q2->where('sm.display_name', 'like', $like)
                   ->orWhere('sm.generated_OT_code', 'like', $like)
                   ->orWhere('cm.course_name', 'like', $like);
            })
            ->select(
                'sms.pk',
                'sms.status',
                'sms.date as memo_date',
                'sm.display_name as student_name',
                'sm.generated_OT_code',
                'cm.course_name'
            )
            ->orderByDesc('sms.date')
            ->limit(20)
            ->get();

        foreach ($memos as $row) {
            $results[] = [
                'type'         => 'Memo',
                'type_key'     => 'memo',
                'student_name' => $row->student_name,
                'ot_code'      => $row->generated_OT_code,
                'course_name'  => $row->course_name,
                'detail'       => '—',
                'date'         => $row->memo_date,
                'status'       => $row->status,
                'pk'           => $row->pk,
            ];
        }

        // --- Discipline Memos ---
        $disciplines = DB::table('discipline_memo_status as dms')
            ->join('student_master as sm', 'sm.pk', '=', 'dms.student_master_pk')
            ->join('course_master as cm', 'cm.pk', '=', 'dms.course_master_pk')
            ->leftJoin('discipline_master as dm', 'dm.pk', '=', 'dms.discipline_master_pk')
            ->where(function ($q2) use ($like) {
                $q2->where('sm.display_name', 'like', $like)
                   ->orWhere('sm.generated_OT_code', 'like', $like)
                   ->orWhere('cm.course_name', 'like', $like)
                   ->orWhere('dm.discipline_name', 'like', $like)
                   ->orWhere('dms.remarks', 'like', $like);
            })
            ->select(
                'dms.pk',
                'dms.status',
                'dms.date_of_infraction',
                'dms.remarks',
                'sm.display_name as student_name',
                'sm.generated_OT_code',
                'cm.course_name',
                'dm.discipline_name'
            )
            ->orderByDesc('dms.date_of_infraction')
            ->limit(20)
            ->get();

        foreach ($disciplines as $row) {
            $results[] = [
                'type'         => 'Discipline',
                'type_key'     => 'discipline',
                'student_name' => $row->student_name,
                'ot_code'      => $row->generated_OT_code,
                'course_name'  => $row->course_name,
                'detail'       => $row->discipline_name ?? '—',
                'date'         => $row->date_of_infraction,
                'status'       => $row->status,
                'pk'           => $row->pk,
            ];
        }

        // Sort all results by date descending
        usort($results, function ($a, $b) {
            return strcmp($b['date'] ?? '', $a['date'] ?? '');
        });

        return response()->json(['results' => array_slice($results, 0, 30), 'query' => $q]);
    }



}