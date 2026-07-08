<?php
// app/Http/Controllers/Admin/MemoNoticeController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemoNoticeTemplate;
use App\Models\CourseMaster;
use App\Models\MemoDiscipline;
use App\Models\DisciplineMaster;
use App\Services\NotificationService;
use App\Services\NotificationReceiverService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class MemoDisciplineController extends Controller
{
 public function index(Request $request)
{
    // Officer Trainees are managed on their own dedicated page (view own records + chat),
    // not this admin management page.
    if (isOfficerTraineeUser()) {
        return redirect()->route('memo.discipline.ot_index');
    }

    $data_course_id = get_Role_by_course();

    // Courses
    if (hasRole('Student-OT')) {
        $courses = DB::table('student_master_course__map as smcm')
            ->join('course_master as cm', 'smcm.course_master_pk', '=', 'cm.pk')
            ->where('smcm.student_master_pk', Auth::user()->user_id)
            ->select('cm.*')
            ->get();
    } else {
        $courseQuery = CourseMaster::where('active_inactive', 1)
            ->where('end_date', '>', now());
        if (!empty($data_course_id)) {
            $courseQuery->whereIn('pk', $data_course_id);
        }
        $courses = $courseQuery->orderBy('course_name')->get();
    }

    // Filters
    $programNameFilter   = $request->program_name;
    $statusFilter        = $request->status;
    $searchFilter        = $request->search;
    $disciplineFilter    = $request->discipline_master_pk;

    // First load (no date params in URL) = show today's data; Clear Filters (empty date params) = show all data
    if (!$request->has('from_date') && !$request->has('to_date')) {
        $fromDateFilter = Carbon::today()->toDateString();
        $toDateFilter   = Carbon::today()->toDateString();
    } else {
        $fromDateFilter = $request->get('from_date') ?: null;
        $toDateFilter   = $request->get('to_date') ?: null;
    }

    $disciplines = DisciplineMaster::where('active_inactive', 1)
        ->select('discipline_name')
        ->distinct()
        ->orderBy('discipline_name')
        ->get();

    $memos = MemoDiscipline::with([
            'course:pk,course_name',
            'discipline:pk,discipline_name,active_inactive',
            'student:pk,display_name,generated_OT_code,cadre_master_pk',
            'student.cadre:pk,cadre_name',
        ])

        ->when(hasRole('Student-OT'), function ($q) use ($courses) {
            $q->where('student_master_pk', Auth::user()->user_id);
            $q->whereIn('course_master_pk', $courses->pluck('pk'));
        })
        ->when(!hasRole('Student-OT') && !empty($data_course_id ?? null), function ($q) use ($data_course_id) {
            $q->whereIn('course_master_pk', $data_course_id);
        })
        ->when($programNameFilter, function ($q) use ($programNameFilter) {
            $q->where('course_master_pk', $programNameFilter);
        })
        ->when($statusFilter !== null && $statusFilter !== '', function ($q) use ($statusFilter) {
            $q->where('status', $statusFilter);
        })
        ->when($disciplineFilter, function ($q) use ($disciplineFilter) {
            $q->whereHas('discipline', fn($d) => $d->where('discipline_name', $disciplineFilter));
        })
        ->when($searchFilter, function ($q) use ($searchFilter) {
            $q->where(function ($sub) use ($searchFilter) {
                $sub->whereHas('student', function ($s) use ($searchFilter) {
                        $s->where('display_name', 'like', "%{$searchFilter}%");
                    })
                    ->orWhereHas('discipline', function ($d) use ($searchFilter) {
                        $d->where('discipline_name', 'like', "%{$searchFilter}%");
                    })
                    ->orWhere('remarks', 'like', "%{$searchFilter}%");
            });
        })
        ->when($fromDateFilter && $toDateFilter, function ($q) use ($fromDateFilter, $toDateFilter) {
            $q->whereBetween('date', [$fromDateFilter, $toDateFilter]);
        })
        ->whereHas('discipline', function ($q) {
            $q->where('active_inactive', 1);
        })
        ->orderBy('pk', 'desc')
        ->paginate(10)
        ->appends($request->all());

    // Optional Session/Venue selects shown in the Generate Discipline Memo modal.
    $sessions = \App\Models\ClassSessionMaster::all();
    $venues   = \App\Models\VenueMaster::where('active_inactive', 1)->orderBy('venue_name')->get();

    return view('admin.memo_discipline.index', compact(
        'memos',
        'courses',
        'disciplines',
        'programNameFilter',
        'statusFilter',
        'disciplineFilter',
        'searchFilter',
        'fromDateFilter',
        'toDateFilter',
        'sessions',
        'venues'
    ));
}

/**
 * Officer Trainee view: the signed-in OT's own discipline memos only, read-only,
 * with the conversation (chat) offcanvas. No generate / edit / delete / send.
 */
public function otIndex(Request $request)
{
    $studentPk = Auth::user()->user_id;

    // Courses the OT is enrolled in — powers the Program Name filter dropdown.
    $courses = DB::table('student_master_course__map as smcm')
        ->join('course_master as cm', 'smcm.course_master_pk', '=', 'cm.pk')
        ->where('smcm.student_master_pk', $studentPk)
        ->select('cm.*')
        ->orderBy('cm.course_name')
        ->get();

    $programNameFilter = $request->program_name;
    $statusFilter      = $request->status;
    $searchFilter      = $request->search;
    $disciplineFilter  = $request->discipline_master_pk;

    // OT page defaults to their full history (no implicit "today" restriction).
    $fromDateFilter = $request->get('from_date') ?: null;
    $toDateFilter   = $request->get('to_date') ?: null;

    $disciplines = DisciplineMaster::where('active_inactive', 1)
        ->select('discipline_name')
        ->distinct()
        ->orderBy('discipline_name')
        ->get();

    $memos = MemoDiscipline::with([
            'course:pk,course_name',
            'discipline:pk,discipline_name,active_inactive',
        ])
        ->where('student_master_pk', $studentPk)
        ->when($programNameFilter, function ($q) use ($programNameFilter) {
            $q->where('course_master_pk', $programNameFilter);
        })
        ->when($statusFilter !== null && $statusFilter !== '', function ($q) use ($statusFilter) {
            $q->where('status', $statusFilter);
        })
        ->when($disciplineFilter, function ($q) use ($disciplineFilter) {
            $q->whereHas('discipline', fn($d) => $d->where('discipline_name', $disciplineFilter));
        })
        ->when($searchFilter, function ($q) use ($searchFilter) {
            $q->where(function ($sub) use ($searchFilter) {
                $sub->whereHas('discipline', function ($d) use ($searchFilter) {
                        $d->where('discipline_name', 'like', "%{$searchFilter}%");
                    })
                    ->orWhere('remarks', 'like', "%{$searchFilter}%");
            });
        })
        ->when($fromDateFilter && $toDateFilter, function ($q) use ($fromDateFilter, $toDateFilter) {
            $q->whereBetween('date', [$fromDateFilter, $toDateFilter]);
        })
        ->whereHas('discipline', function ($q) {
            $q->where('active_inactive', 1);
        })
        ->orderBy('pk', 'desc')
        ->paginate(10)
        ->appends($request->all());

    return view('admin.memo_discipline.ot_index', compact(
        'memos',
        'courses',
        'disciplines',
        'programNameFilter',
        'statusFilter',
        'disciplineFilter',
        'searchFilter',
        'fromDateFilter',
        'toDateFilter'
    ));
}

/**
 * Hard-delete a discipline memo along with its conversation messages and uploaded files.
 * OT is notified when a Recorded (1) or Memo Sent (2) memo is deleted.
 */
public function destroy($id)
{
    if (! (hasRole('Internal Faculty') || hasRole('Guest Faculty')
        || hasRole('Super Admin') || hasRole('Training Induction Admin') || hasRole('Training-Induction'))) {
        return response()->json(['success' => false, 'message' => 'You are not authorized to delete this record.'], 403);
    }

    $memo = MemoDiscipline::with([
        'course:pk,course_name',
        'discipline:pk,discipline_name',
    ])->find($id);

    if (! $memo) {
        return response()->json(['success' => false, 'message' => 'Discipline memo not found.'], 404);
    }

    $notifyOt = in_array((int) $memo->status, [1, 2], true);
    $memoPk = (int) $memo->pk;
    $studentPk = (int) $memo->student_master_pk;
    $courseName = $memo->course->course_name ?? 'your course';
    $disciplineName = $memo->discipline->discipline_name ?? 'discipline';
    $memoDate = $memo->date
        ? Carbon::parse($memo->date)->format('d M Y')
        : now()->format('d M Y');

    try {
        DB::transaction(function () use ($id) {
            $files = DB::table('discipline_message_student_decip_incharge')
                ->where('discipline_memo_status_pk', $id)
                ->pluck('doc_upload')
                ->filter();
            foreach ($files as $file) {
                if ($file && Storage::disk('public')->exists($file)) {
                    Storage::disk('public')->delete($file);
                }
            }

            DB::table('discipline_message_student_decip_incharge')->where('discipline_memo_status_pk', $id)->delete();
            DB::table('discipline_memo_status')->where('pk', $id)->delete();
        });

        if ($notifyOt) {
            try {
                $receiverUserId = app(NotificationReceiverService::class)->getStudentUserId($studentPk);
                if ($receiverUserId) {
                    app(NotificationService::class)->create(
                        (int) $receiverUserId,
                        'memo',
                        'MemoDiscipline',
                        $memoPk,
                        'Discipline Memo Deleted',
                        "A discipline memo for \"{$disciplineName}\" for {$courseName} dated {$memoDate} has been deleted."
                    );
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send discipline memo delete notification: ' . $e->getMessage());
            }
        }

        return response()->json(['success' => true, 'message' => 'Discipline memo deleted successfully.']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Failed to delete the discipline memo. Please try again.'], 500);
    }
}

/**
 * Download the Send Discipline Memo listing as a CSV.
 * Same filters/dataset as index() (minus pagination), in the mess-style layout:
 * a title block (report name + applied filters), then the column-header row, then data rows.
 */
public function exportCsv(Request $request)
{
    $data_course_id = get_Role_by_course();

    // Filters (identical to index)
    $programNameFilter = $request->program_name;
    $statusFilter      = $request->status;
    $searchFilter      = $request->search;
    $disciplineFilter  = $request->discipline_master_pk;

    if (!$request->has('from_date') && !$request->has('to_date')) {
        $fromDateFilter = Carbon::today()->toDateString();
        $toDateFilter   = Carbon::today()->toDateString();
    } else {
        $fromDateFilter = $request->get('from_date') ?: null;
        $toDateFilter   = $request->get('to_date') ?: null;
    }

    $studentCourses = null;
    if (hasRole('Student-OT')) {
        $studentCourses = DB::table('student_master_course__map')
            ->where('student_master_pk', Auth::user()->user_id)
            ->pluck('course_master_pk');
    }

    $memos = MemoDiscipline::with([
            'course:pk,course_name',
            'discipline:pk,discipline_name,active_inactive',
            'student:pk,display_name,generated_OT_code,cadre_master_pk',
            'student.cadre:pk,cadre_name',
        ])
        ->when(hasRole('Student-OT'), function ($q) use ($studentCourses) {
            $q->where('student_master_pk', Auth::user()->user_id);
            $q->whereIn('course_master_pk', $studentCourses ?? collect());
        })
        ->when(!hasRole('Student-OT') && !empty($data_course_id ?? null), function ($q) use ($data_course_id) {
            $q->whereIn('course_master_pk', $data_course_id);
        })
        ->when($programNameFilter, function ($q) use ($programNameFilter) {
            $q->where('course_master_pk', $programNameFilter);
        })
        ->when($statusFilter !== null && $statusFilter !== '', function ($q) use ($statusFilter) {
            $q->where('status', $statusFilter);
        })
        ->when($disciplineFilter, function ($q) use ($disciplineFilter) {
            $q->whereHas('discipline', fn($d) => $d->where('discipline_name', $disciplineFilter));
        })
        ->when($searchFilter, function ($q) use ($searchFilter) {
            $q->where(function ($sub) use ($searchFilter) {
                $sub->whereHas('student', function ($s) use ($searchFilter) {
                        $s->where('display_name', 'like', "%{$searchFilter}%");
                    })
                    ->orWhereHas('discipline', function ($d) use ($searchFilter) {
                        $d->where('discipline_name', 'like', "%{$searchFilter}%");
                    })
                    ->orWhere('remarks', 'like', "%{$searchFilter}%");
            });
        })
        ->when($fromDateFilter && $toDateFilter, function ($q) use ($fromDateFilter, $toDateFilter) {
            $q->whereBetween('date', [$fromDateFilter, $toDateFilter]);
        })
        ->whereHas('discipline', function ($q) {
            $q->where('active_inactive', 1);
        })
        ->orderBy('pk', 'desc')
        ->get();

    $courseName = $programNameFilter ? (optional(CourseMaster::find($programNameFilter))->course_name ?? 'All') : 'All';
    $dateRange = ($fromDateFilter || $toDateFilter)
        ? (($fromDateFilter ? Carbon::parse($fromDateFilter)->format('d-m-Y') : '—') . ' to ' . ($toDateFilter ? Carbon::parse($toDateFilter)->format('d-m-Y') : '—'))
        : 'All Dates';

    $headers = ['Name', 'OT/Participant Code', 'Cadre', 'Infraction', 'Date of Infraction', 'Remarks'];

    $rows = [];
    foreach ($memos as $memo) {
        $rows[] = [
            $memo->student->display_name ?? 'N/A',
            $memo->student->generated_OT_code ?? 'N/A',
            $memo->student->cadre->cadre_name ?? 'N/A',
            $memo->discipline->discipline_name ?? 'N/A',
            $memo->date ? Carbon::parse($memo->date)->format('d M Y') : 'N/A',
            $memo->remarks ?? '',
        ];
    }

    $titleBlock = [
        ['Discipline Memo'],
        ['Date Range', $dateRange, 'Program', $courseName],
        ['Generated On', now()->format('d-m-Y H:i:s')],
        [],
    ];

    $fileName = 'send-discipline-memo-' . now()->format('Y-m-d_His') . '.csv';

    return $this->streamCsv($fileName, $titleBlock, $headers, $rows);
}

/**
 * Stream a CSV download in the mess-style layout: title/meta rows, a header row, then data rows.
 * A UTF-8 BOM is prepended so Excel renders names/diacritics correctly.
 */
private function streamCsv(string $fileName, array $titleBlock, array $headers, array $rows)
{
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

    public function create()
    {
        $data_course_id = get_Role_by_course();

        $query = CourseMaster::where('active_inactive', 1)
            ->where('end_date', '>', now());

        if (!empty($data_course_id)) {
            $query->whereIn('pk', $data_course_id);
        }

        $activeCourses = $query->orderBy('course_name')->get();

            $disciplines = DisciplineMaster::where('active_inactive', 1)
                ->get();
              

        return view('admin.memo_discipline.create', compact('activeCourses', 'disciplines'));
    }
    function getStudentByCourse(Request $request){
        try {
        $courseId = $request->course_id;

        if (!$courseId) {
            return response()->json([
                'status' => false,
                'message' => 'Course is required.'
            ]);
        }

        // Cast courseId to integer to ensure proper comparison
        $courseId = (int) $courseId;

        // Query to get students with Late (2) or Absent (3) status
        // Handle both integer and string status values
        $attendance = DB::table('student_master_course__map as a')
                ->join('student_master as s', 'a.student_master_pk', '=', 's.pk')
                ->where('a.course_master_pk', $courseId)
                ->where('a.active_inactive', 1)
                ->whereNotNull('s.pk')
                ->whereNotNull('s.display_name')
                ->where('s.display_name', '!=', '')
                ->select(
                    'a.student_master_pk as student_pk',
                    's.pk as pk',
                    's.display_name as display_name',
                    's.generated_OT_code as generated_OT_code'
                )
                ->orderBy('s.display_name', 'asc')
                ->get();

              $discipline_master_data  = DB::table('discipline_master')->where('course_master_pk', $courseId)->where('active_inactive', 1)->get();


        // Format the attendance data
        $students = $attendance->map(function ($student) {
            return [
                'pk' => (int) $student->student_pk,
                'display_name' => $student->display_name,
                'generated_OT_code' => $student->generated_OT_code,
            ];
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Student list fetched successfully.',
            'students' => $students,
            'discipline_master_data' => $discipline_master_data
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
    function getMarkDeduction(Request $request){
        $discipline_master_pk = $request->discipline_master_pk;
        $course_id = $request->course_id;

        if (!$discipline_master_pk && !$course_id) {
            return response()->json('Discipline and Course are required.');
        }

        $discipline = DisciplineMaster::where('pk', $discipline_master_pk)->where('course_master_pk', $course_id)->where('active_inactive', 1)->first();

        if (!$discipline) {
            return response()->json('Discipline not found.');
        }

        return response()->json($discipline->mark_deduction);

    }

    /**
     * Templates offered when generating a discipline memo: active "Discipline Memo"
     * templates for the course that either target the chosen discipline or are
     * course-wide (discipline_master_pk null) as a fallback. Discipline-specific first.
     */
    function getTemplatesByDiscipline(Request $request)
    {
        $courseId     = $request->course_id;
        $disciplineId = $request->discipline_master_pk;

        if (!$courseId) {
            return response()->json([]);
        }

        $templates = MemoNoticeTemplate::query()
            ->where('memo_notice_type', 'Discipline Memo')
            ->where('active_inactive', 1)
            ->whereNull('deleted_at')
            ->where('course_master_pk', $courseId)
            ->where(function ($q) use ($disciplineId) {
                $q->whereNull('discipline_master_pk');
                if ($disciplineId) {
                    $q->orWhere('discipline_master_pk', $disciplineId);
                }
            })
            ->orderByRaw('discipline_master_pk IS NULL') // discipline-specific first, course-wide fallback last
            ->orderBy('title')
            ->get(['pk', 'title', 'content', 'director_name', 'director_designation', 'signature_image', 'discipline_master_pk']);

        return response()->json($templates);
    }

    function discipline_generate_memo_store(Request $request){
        // return $request->all();
          $validated = $request->validate([
        'course_master_pk' => 'required|exists:course_master,pk',
        'discipline_master_pk' => 'required|exists:discipline_master,pk',
        'memo_notice_template_pk' => 'nullable|exists:memo_notice_templates,pk',
        'date_of_memo' => 'required|date',
        'discipline_marks' => 'required|numeric|min:0',
        'selected_student_list' => 'required|array|min:1',
        'selected_student_list.*' => 'exists:student_master,pk',
        'Remark' => 'nullable|string|max:500',
         ]);

         if($validated){
            $courseName = optional(CourseMaster::find($request->course_master_pk))->course_name ?? 'your course';
            $disciplineName = optional(DisciplineMaster::find($request->discipline_master_pk))->discipline_name ?? 'discipline';
            $memoDate = $request->date_of_memo
                ? Carbon::parse($request->date_of_memo)->format('d M Y')
                : now()->format('d M Y');
            $receiverService = app(NotificationReceiverService::class);
            $notificationService = app(NotificationService::class);

            foreach($request->selected_student_list as $student_pk){
                // Insert memo record for each student
                $memoPk = DB::table('discipline_memo_status')->insertGetId([
                    'course_master_pk' => $request->course_master_pk,
                    'discipline_master_pk' => $request->discipline_master_pk,
                    'memo_notice_template_pk' => $request->memo_notice_template_pk ?: null,
                    'student_master_pk' => $student_pk,
                    'date' => $request->date_of_memo,
                    'mark_deduction_submit' => $request->discipline_marks,
                    'remarks' => $request->Remark,
                ], 'pk');

                try {
                    $receiverUserId = $receiverService->getStudentUserId((int) $student_pk);
                    if ($receiverUserId) {
                        $notificationService->create(
                            (int) $receiverUserId,
                            'memo',
                            'MemoDiscipline',
                            (int) $memoPk,
                            'Discipline Memo Issued',
                            "A discipline memo for \"{$disciplineName}\" has been issued for {$courseName} on {$memoDate}. Please review and respond."
                        );
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to send discipline memo notification: ' . $e->getMessage());
                }
            }
            return redirect()->route('memo.discipline.index')->with('success', 'Discipline memo(s) generated successfully.');
         }else{
            return redirect()->back()->withErrors($validated)->withInput();
         }
    }
    function sendMemo(Request $request){
        $validated = $request->validate([
            'discipline_pk' => 'required|exists:discipline_memo_status,pk',
        ]);

        if (!$validated) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.'
            ]);
        }

        $memo = MemoDiscipline::with([
            'course:pk,course_name',
            'discipline:pk,discipline_name',
        ])->find($request->discipline_pk);

        if (!$memo || $memo->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Memo not found or already sent.'
            ]);
        }

        $memo->status = 2;
        $memo->modified_date = now();
        $memo->save();

        try {
            $receiverService = app(NotificationReceiverService::class);
            $receiverUserId = $receiverService->getStudentUserId((int) $memo->student_master_pk);

            if ($receiverUserId) {
                $courseName = $memo->course->course_name ?? 'your course';
                $disciplineName = $memo->discipline->discipline_name ?? 'discipline';
                $memoDate = $memo->date
                    ? Carbon::parse($memo->date)->format('d M Y')
                    : now()->format('d M Y');

                app(NotificationService::class)->create(
                    (int) $receiverUserId,
                    'memo',
                    'MemoDiscipline',
                    (int) $memo->pk,
                    'Discipline Memo Issued',
                    "A discipline memo for \"{$disciplineName}\" has been issued for {$courseName} on {$memoDate}. Please review and respond."
                );
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send discipline memo notification: ' . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Memo sent successfully.'
        ]);
    }
    function getConversationModel(Request $request, $memoId,$type){
        // $memo = MemoDiscipline::with([
        //     'course:pk,course_name',
        //     'discipline:pk,discipline_name',
        //     'student:pk,display_name'
        // ])->find($memoId);
         $conversations = DB::table('discipline_message_student_decip_incharge as mmsdi')
          ->join('discipline_memo_status as sms', 'mmsdi.discipline_memo_status_pk', '=', 'sms.pk')
            ->leftjoin('student_master as sm', 'sms.student_master_pk', '=', 'sm.pk')
            ->where('mmsdi.discipline_memo_status_pk', $memoId)
            ->orderBy('mmsdi.created_date', 'asc')
            ->select(
                'mmsdi.*',
                'sms.pk as notice_id',
                'sms.status as notice_status',
                'sm.pk as student_id',
                'sm.display_name as student_name'
            )
            ->get();
            // print_r($conversations); exit;
             $conversations = $conversations->map(function ($item) {
        $identity = resolve_chat_sender_identity($item->created_by, $item->role_type);
        $item->display_name = $identity['display_name'];
        $item->role_name = $identity['role_name'];
        $item->user_type = $item->role_type == 's' ? 'OT' : ($item->role_type == 'f' ? 'admin' : 'unknown');
        return $item;
    });

        if (!$conversations) {
            return '<p class="text-danger text-center">Memo not found.</p>';
        }

        // Memo status drives the composer even when there are no messages yet
        // (2 = Memo Sent / open, 3 = Closed).
        $noticeStatus = (int) (DB::table('discipline_memo_status')->where('pk', $memoId)->value('status') ?? 0);

        return view('admin.memo_discipline.partials.conversation_model', compact('conversations','type','memoId','noticeStatus'))->render();
        
    }
    public function memoDisciplineConversationStore(Request $request)
{
    try{

    
    $request->validate([
        'memo_discipline_id' => 'required|exists:discipline_memo_status,pk',
        'student_decip_incharge_msg' => 'required|string|max:1000',
        'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:1024', // 1 MB
        'role_type' => 'required',
    ]);

    // Extra validation if closing memo
    if ($request->status == 2) {
        $request->validate([
            'conclusion_type' => 'required|exists:memo_conclusion_master,pk',
            'mark_of_deduction' => 'required|numeric|min:0',
            'conclusion_remark' => 'nullable|string|max:500',
        ]);
    }

    DB::beginTransaction();

    try {
        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')
                ->store('memo_discipline_attachments', 'public');
        }
        if($request->role_type == 'OT'){
           $request->role_type = 's';
        }

        DB::table('discipline_message_student_decip_incharge')->insert([
            'discipline_memo_status_pk' => $request->memo_discipline_id,
            'created_by' => Auth::user()->user_id,
            'role_type' => $request->role_type,
            'student_decip_incharge_msg' => $request->student_decip_incharge_msg,
            'doc_upload' => $attachmentPath,
            'created_date' => now(),
        ]);

        // Notify the other party about the new chat message
        $memo = MemoDiscipline::find($request->memo_discipline_id);
        if ($memo) {
            if ($request->role_type === 's') {
                // OT sent a message → notify Super Admin / Training Induction / prior thread admins.
                // (Old query used user_category F/A which does not exist in this DB — notifications never fired.)
                try {
                    $adminUserIds = app(NotificationReceiverService::class)
                        ->getDisciplineMemoAdminReceivers((int) $memo->pk, (int) $memo->course_master_pk);
                    $preview = mb_substr((string) $request->student_decip_incharge_msg, 0, 100);
                    $message = $preview !== ''
                        ? "OT replied to a discipline memo: \"{$preview}\""
                        : 'A student has replied to a discipline memo.';

                    if (!empty($adminUserIds)) {
                        app(NotificationService::class)->createMultiple(
                            $adminUserIds,
                            'memo',
                            'MemoDiscipline',
                            (int) $memo->pk,
                            'OT Replied to Discipline Memo',
                            $message
                        );
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to send discipline memo OT chat notification: ' . $e->getMessage());
                }
            } else {
                // Admin/Faculty sent a message → notify the OT student (skip if closing — close notification sent below)
                if ((int) $request->status !== 2) {
                    $receiverUserId = app(NotificationReceiverService::class)
                        ->getStudentUserId((int) $memo->student_master_pk);
                    if ($receiverUserId) {
                        app(NotificationService::class)->create(
                            (int) $receiverUserId,
                            'memo',
                            'MemoDiscipline',
                            (int) $memo->pk,
                            'New Message on Your Discipline Memo',
                            'The incharge has replied to your discipline memo.'
                        );
                    }
                }
            }
        }

        // Close memo if required
        if ($request->status == 2) {
            MemoDiscipline::where('pk', $request->memo_discipline_id)->update([
                'status' => 3,
                'final_mark_deduction' => $request->mark_of_deduction,
                'conclusion_remark' => $request->conclusion_remark,
                'conclusion_type_pk' => $request->conclusion_type,
                'modified_date' => now(),
            ]);

            try {
                $closedMemo = MemoDiscipline::with([
                    'course:pk,course_name',
                    'discipline:pk,discipline_name',
                ])->find($request->memo_discipline_id);

                if ($closedMemo) {
                    $receiverUserId = app(NotificationReceiverService::class)
                        ->getStudentUserId((int) $closedMemo->student_master_pk);

                    if ($receiverUserId) {
                        $courseName = $closedMemo->course->course_name ?? 'your course';
                        $disciplineName = $closedMemo->discipline->discipline_name ?? 'discipline';
                        $conclusionName = DB::table('memo_conclusion_master')
                            ->where('pk', $request->conclusion_type)
                            ->value('discussion_name');
                        $finalMarks = $request->mark_of_deduction;

                        $message = "Your discipline memo for \"{$disciplineName}\" ({$courseName}) has been closed.";
                        if ($conclusionName) {
                            $message .= " Conclusion: {$conclusionName}.";
                        }
                        if ($finalMarks !== null && $finalMarks !== '') {
                            $message .= " Final mark deduction: {$finalMarks}.";
                        }
                        if (!empty($request->conclusion_remark)) {
                            $message .= ' Remark: ' . $request->conclusion_remark;
                        }

                        app(NotificationService::class)->create(
                            (int) $receiverUserId,
                            'memo',
                            'MemoDiscipline',
                            (int) $closedMemo->pk,
                            'Discipline Memo Closed',
                            $message
                        );
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send discipline memo close notification: ' . $e->getMessage());
            }
        }

        DB::commit();

        // The chat composer sends via fetch and expects JSON so it can refresh the
        // conversation in place without a full page reload.
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Message sent successfully.']);
        }

        return back()->with('success', 'Message sent successfully.')->withInput();
    } catch (\Throwable $e) {
        DB::rollBack();
        \Log::error('Error in memoDisciplineConversationStore inner: ' . $e->getMessage());
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => 'Something went wrong.'], 500);
        }
        return back()->with('error', 'Something went wrong. '. $e->getMessage())->withInput();
    }
    } catch(\Exception $e) {
        \Log::error('Error in memoDisciplineConversationStore: ' . $e->getMessage());
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred.'], 500);
        }
        return back()->with('error', 'An unexpected error occurred.' . $e->getMessage())->withInput();
    }
}
    
    
    public function edit($id)
    {
        $memo = MemoDiscipline::with([
            'course:pk,course_name',
            'student:pk,display_name,generated_OT_code',
        ])->findOrFail($id);

        $disciplines = DisciplineMaster::where('course_master_pk', $memo->course_master_pk)
            ->where('active_inactive', 1)
            ->orderBy('discipline_name')
            ->get(['pk', 'discipline_name', 'mark_deduction']);

        return response()->json([
            'pk'                      => $memo->pk,
            'course_master_pk'        => $memo->course_master_pk,
            'course_name'             => $memo->course->course_name ?? 'N/A',
            'student_name'            => trim(($memo->student->generated_OT_code ? $memo->student->generated_OT_code . '- ' : '') . ($memo->student->display_name ?? 'N/A')),
            'date'                    => $memo->date,
            'discipline_master_pk'    => $memo->discipline_master_pk,
            'mark_deduction_submit'   => $memo->mark_deduction_submit,
            'remarks'                 => $memo->remarks ?? '',
            'memo_notice_template_pk' => $memo->memo_notice_template_pk,
            'disciplines'             => $disciplines,
        ]);
    }

    public function update(Request $request, $id)
    {
        $memo = MemoDiscipline::findOrFail($id);

        if ($memo->status == 3) {
            return response()->json(['success' => false, 'message' => 'Closed memos cannot be edited.'], 422);
        }

        $validated = $request->validate([
            'date'                    => 'required|date',
            'discipline_master_pk'    => 'required|exists:discipline_master,pk',
            'mark_deduction_submit'   => 'required|numeric|min:0',
            'remarks'                 => 'nullable|string|max:500',
            'memo_notice_template_pk' => 'nullable|exists:memo_notice_templates,pk',
        ]);

        // The Edit modal's Template field is populated (and re-populated on discipline
        // change) from the same discipline-scoped list as Generate, so normally the
        // user's pick arrives here directly. Only auto-resolve as a fallback — same
        // precedence as getTemplatesByDiscipline(): discipline-specific first,
        // course-wide (discipline_master_pk null) second — when nothing was submitted
        // (e.g. a stale pin from before this field existed, or no template configured
        // for this discipline yet).
        $templatePk = $validated['memo_notice_template_pk'] ?? null;
        if (!$templatePk) {
            $bestTemplate = MemoNoticeTemplate::query()
                ->where('memo_notice_type', 'Discipline Memo')
                ->where('active_inactive', 1)
                ->whereNull('deleted_at')
                ->where('course_master_pk', $memo->course_master_pk)
                ->where(function ($q) use ($validated) {
                    $q->whereNull('discipline_master_pk')
                      ->orWhere('discipline_master_pk', $validated['discipline_master_pk']);
                })
                ->orderByRaw('discipline_master_pk IS NULL')
                ->orderBy('title')
                ->first();
            $templatePk = $bestTemplate->pk ?? null;
        }

        $memo->update([
            'date'                    => $validated['date'],
            'discipline_master_pk'    => $validated['discipline_master_pk'],
            'mark_deduction_submit'   => $validated['mark_deduction_submit'],
            'remarks'                 => $validated['remarks'] ?? null,
            'memo_notice_template_pk' => $templatePk,
            'modified_date'           => now(),
        ]);

        try {
            $memo->load(['course:pk,course_name', 'discipline:pk,discipline_name']);
            $receiverUserId = app(NotificationReceiverService::class)
                ->getStudentUserId((int) $memo->student_master_pk);

            if ($receiverUserId) {
                $courseName = $memo->course->course_name ?? 'your course';
                $disciplineName = $memo->discipline->discipline_name ?? 'discipline';
                $memoDate = $memo->date
                    ? Carbon::parse($memo->date)->format('d M Y')
                    : now()->format('d M Y');

                app(NotificationService::class)->create(
                    (int) $receiverUserId,
                    'memo',
                    'MemoDiscipline',
                    (int) $memo->pk,
                    'Discipline Memo Updated',
                    "A discipline memo for \"{$disciplineName}\" has been updated for {$courseName} on {$memoDate}. Please review."
                );
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send discipline memo update notification: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Discipline memo updated successfully.']);
    }

    public function memo_show(Request $request, $id)
{
    $decryptedId = decrypt($id);
    $memo = MemoDiscipline::with([
        'course:pk,course_name',
        'discipline:pk,discipline_name',
        'student:pk,display_name',
        'messages',
        'template',        // course-level fallback
        'chosenTemplate',  // template pinned at send time
    ])->find($decryptedId);

    // Prefer the template chosen at send time; fall back to the course-level one for older memos.
    $template = $memo ? ($memo->chosenTemplate ?: $memo->template) : null;
    $memo_conclusion_master = DB::table('memo_conclusion_master')->where('active_inactive', 1)->get();
    $conclusion_type_name = null;
    if ($memo && $memo->conclusion_type_pk) {
        $conclusion_type_name = DB::table('memo_conclusion_master')
            ->where('pk', $memo->conclusion_type_pk)
            ->value('discussion_name');
    }
    if (!$memo) {
        return back()->with('error', 'Memo not found.');
    }

    // Resolve each message's real sender name + role — a conversation can involve
    // multiple distinct admins/faculty, so a generic "Admin" label isn't enough.
    foreach ($memo->messages as $message) {
        $identity = resolve_chat_sender_identity($message->created_by, $message->role_type);
        $message->display_name = $identity['display_name'];
        $message->role_name = $identity['role_name'];
    }

    return view(
        'admin.memo_discipline.template_show',
        compact('memo', 'memo_conclusion_master', 'conclusion_type_name', 'template')
    );
}

public function getNewMessages(Request $request, $id)
{
    $lastPk = (int) $request->query('last_pk', 0);

    $messages = DB::table('discipline_message_student_decip_incharge')
        ->where('discipline_memo_status_pk', $id)
        ->where('pk', '>', $lastPk)
        ->orderBy('pk', 'asc')
        ->get();

    $messages = $messages->map(function ($msg) {
        $identity = resolve_chat_sender_identity($msg->created_by, $msg->role_type);
        $msg->display_name = $identity['display_name'];
        $msg->role_name = $identity['role_name'];
        $msg->formatted_date = $msg->created_date
            ? \Carbon\Carbon::parse($msg->created_date)->format('d-m-Y h:i A')
            : '';
        return $msg;
    });

    return response()->json($messages);
}

}