<?php
// app/Http/Controllers/Admin/MemoNoticeController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemoNoticeTemplate;
use App\Models\CourseMaster;
use App\Models\MemoDiscipline;
use App\Models\DisciplineMaster;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class MemoDisciplineController extends Controller
{
 public function index(Request $request)
{
    // Courses
    if (hasRole('Student-OT')) {
        $courses = DB::table('student_master_course__map as smcm')
            ->join('course_master as cm', 'smcm.course_master_pk', '=', 'cm.pk')
            ->where('smcm.student_master_pk', Auth::user()->user_id)
            ->select('cm.*')
            ->get();
    } else {
        $courses = CourseMaster::where('active_inactive', 1)
            ->where('end_date', '>', now())
            ->get();
    }

    // Filters
    $programNameFilter = $request->program_name;
    $statusFilter      = $request->status;
    $searchFilter      = $request->search;
    $fromDateFilter    = $request->get('from_date');
    $toDateFilter      = $request->get('to_date');

    // Default today date
    if (!$fromDateFilter && !$toDateFilter) {
        $fromDateFilter = Carbon::today()->toDateString();
        $toDateFilter   = Carbon::today()->toDateString();
    }

    $memos = MemoDiscipline::with([
            'course:pk,course_name',
            'discipline:pk,discipline_name,active_inactive',
            'student:pk,display_name'
        ])

        ->when(hasRole('Student-OT'), function ($q) use ($courses) {
            $q->where('student_master_pk', Auth::user()->user_id);
            
            $q->whereIn('course_master_pk', $courses->pluck('pk'));
        })
        ->when($programNameFilter, function ($q) use ($programNameFilter) {
            $q->where('course_master_pk', $programNameFilter);
        })
        ->when($statusFilter !== null && $statusFilter !== '', function ($q) use ($statusFilter) {
            $q->where('status', $statusFilter);
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

    return view('admin.memo_discipline.index', compact(
        'memos',
        'courses',
        'programNameFilter',
        'statusFilter',
        'searchFilter',
        'fromDateFilter',
        'toDateFilter'
    ));
}

    public function create()
    {
        $activeCourses = CourseMaster::where('active_inactive', 1)
            ->where('end_date', '>', now())
            ->get();

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


        // If no students found, return empty array instead of error
        // This allows the UI to handle empty state gracefully
        if ($attendance->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No students found for this course.',
                'students' => []
            ]);
        }

        // Format the attendance data
        $students = $attendance->map(function ($student) {
            return [
                'pk' => (int) $student->student_pk,
                'display_name' => $student->display_name,
                'generated_OT_code' => $student->generated_OT_code,
            ];
        })->values(); // Reset array keys

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

        if (!$discipline_master_pk) {
            return response()->json(['success' => false, 'message' => 'Discipline is required.', 'mark_deduction' => null]);
        }

        // Fetch by primary key only so the selected discipline's mark is always returned
        $discipline = DisciplineMaster::find($discipline_master_pk);

        if (!$discipline || $discipline->active_inactive != 1) {
            return response()->json(['success' => false, 'message' => 'Discipline not found.', 'mark_deduction' => null]);
        }

        return response()->json([
            'success' => true,
            'mark_deduction' => $discipline->mark_deduction !== null && $discipline->mark_deduction !== '' ? (float) $discipline->mark_deduction : 0
        ]);
    }
    function discipline_generate_memo_store(Request $request){
        // return $request->all();
          $validated = $request->validate([
        'course_master_pk' => 'required|exists:course_master,pk',
        'discipline_master_pk' => 'required|exists:discipline_master,pk',
        
        'date_of_memo' => 'required|date',
        'discipline_marks' => 'required|numeric|min:0',
        'selected_student_list' => 'required|array|min:1',
        'selected_student_list.*' => 'exists:student_master,pk',
        'Remark' => 'nullable|string|max:500',
         ]);

         if($validated){
            foreach($request->selected_student_list as $student_pk){
                // Insert memo record for each student
                DB::table('discipline_memo_status')->insert([
                    'course_master_pk' => $request->course_master_pk,
                    'discipline_master_pk' => $request->discipline_master_pk,
                    'student_master_pk' => $student_pk,
                    'date' => $request->date_of_memo,
                    'mark_deduction_submit' => $request->discipline_marks,
                    'remarks' => $request->Remark,
                ]);
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

        if ($validated) {
            $memo = MemoDiscipline::find($request->discipline_pk);
            if ($memo && $memo->status != 2) { // Ensure memo is not already sent
                $memo->status = 2; // Update status to 'sent'
                $memo->modified_date = now();
                $memo->save();

                return response()->json([
                    'status' => true,
                    'message' => 'Memo sent successfully.'
                ]);
                

            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Memo not found or already sent.'
                ]);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.'
            ]);
        }
        
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
        if ($item->role_type == 'f') {
            $user = DB::table('users')->find($item->created_by);
            $item->display_name = $user->name ?? 'Admin';
            $item->user_type = 'admin';

        } elseif ($item->role_type == 's') {
            $student = DB::table('student_master')->where('pk', $item->created_by)->first();
            $item->display_name = $student->display_name ?? 'OT';
            $item->user_type = 'OT';

        } else {
            $item->display_name = 'Unknown';
            $item->user_type = 'unknown';
        }
        return $item;
    });

        if (!$conversations) {
            return '<p class="text-danger text-center">Memo not found.</p>';
        }

        return view('admin.memo_discipline.partials.conversation_model', compact('conversations','type','memoId'))->render();
        
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
            'conclusion_remarks' => 'nullable|string|max:500',
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
            'created_by' => Auth::user()->user_id, // 🔐 SAFE
            'role_type' => $request->role_type,
            'student_decip_incharge_msg' => $request->student_decip_incharge_msg,
            'doc_upload' => $attachmentPath,
            'created_date' => now(),
        ]);

        // Close memo if required
        if ($request->status == 2) {
            MemoDiscipline::where('pk', $request->memo_discipline_id)->update([
                'status' => 3,
                'final_mark_deduction' => $request->mark_of_deduction,
                'conclusion_remark' => $request->conclusion_remarks,
                'conclusion_type_pk' => $request->conclusion_type,
                'modified_date' => now(),
            ]);
        }

        DB::commit();
// return redirect()
//     ->route('memo.discipline.index')
//     ->with('success', 'Message sent successfully.');
    return back()->with('success', 'Message sent successfully.')->withInput();

        // return back()->with('success', 'Message sent successfully.');
    } catch (\Throwable $e) {
        DB::rollBack();
        \Log::error('Error in memoDisciplineConversationStore inner: ' . $e->getMessage());
        return back()->with('error', 'Something went wrong. '. $e->getMessage())->withInput();
    }
    }catch(\Exception $e){
        \Log::error('Error in memoDisciplineConversationStore: ' . $e->getMessage());
        return back()->with('error', 'An unexpected error occurred.' . $e->getMessage())->withInput();
    }
}
    
    
    public function memo_show(Request $request, $id)
{
    $decryptedId = decrypt($id);
    $memo = MemoDiscipline::with([
        'course:pk,course_name',
        'discipline:pk,discipline_name',
        'student:pk,display_name',
        'messages.student:pk,display_name',
        'template:course_master_pk,content,director_name,director_designation'
    ])->find($decryptedId);
 $memo_conclusion_master = DB::table('memo_conclusion_master')->where('active_inactive', 1)->get();
    if (!$memo) {
        return back()->with('error', 'Memo not found.');
    }

    return view(
        'admin.memo_discipline.template_show',
        compact('memo','memo_conclusion_master')
    );
}
    

}