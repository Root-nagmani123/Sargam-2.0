<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\{
    StudentMasterFirst, StudentMasterSecond, StudentMaster,
    SessionMaster, ServiceMaster, StateMaster, CategoryMaster,
    NewRegistrationBankDetailsMaster, FcJoiningRelatedDocumentsDetailsMaster,
    StudentConfirmMaster, StudentMasterQualificationDetails,
    StudentMasterLanguageKnown, StudentMasterEmploymentDetails,
    FcJoiningRelatedDocumentsMaster
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // ── 1. Registration Overview (all students, progress status) ─────
    public function overview(Request $request)
    {
        $sessions = SessionMaster::orderByDesc('id')->get();
        $services = ServiceMaster::orderBy('service_name')->get();
        $states   = StateMaster::orderBy('state_name')->get();

        $query = StudentMaster::with([
                'session',
            ])
            ->leftJoin('student_master_firsts as s1','student_masters.username','=','s1.username')
            ->leftJoin('service_masters as svc','s1.service_id','=','svc.id')
            ->leftJoin('state_masters as st','s1.allotted_state_id','=','st.id')
            ->leftJoin('student_confirm_masters as sc','student_masters.username','=','sc.username')
            ->select(
                'student_masters.username',
                'student_masters.status',
                'student_masters.step1_done',
                'student_masters.step2_done',
                'student_masters.step3_done',
                'student_masters.bank_done',
                'student_masters.travel_done',
                'student_masters.docs_done',
                'student_masters.session_id',
                's1.full_name',
                's1.gender',
                's1.mobile_no',
                's1.email',
                's1.date_of_birth',
                'svc.service_name',
                'svc.service_code',
                's1.cadre',
                'st.state_name as allotted_state',
                'sc.declaration_accepted',
                'sc.confirmed_at',
                DB::raw('(CASE WHEN student_masters.step1_done=1 THEN 1 ELSE 0 END +
                          CASE WHEN student_masters.step2_done=1 THEN 1 ELSE 0 END +
                          CASE WHEN student_masters.step3_done=1 THEN 1 ELSE 0 END +
                          CASE WHEN student_masters.bank_done=1 THEN 1 ELSE 0 END +
                          CASE WHEN student_masters.travel_done=1 THEN 1 ELSE 0 END +
                          CASE WHEN student_masters.docs_done=1 THEN 1 ELSE 0 END) as steps_done')
            );

        // Filters
        if ($request->filled('session_id')) {
            $query->where('student_masters.session_id', $request->session_id);
        }
        if ($request->filled('status')) {
            $query->where('student_masters.status', $request->status);
        }
        if ($request->filled('service_id')) {
            $query->where('s1.service_id', $request->service_id);
        }
        if ($request->filled('state_id')) {
            $query->where('s1.allotted_state_id', $request->state_id);
        }
        if ($request->filled('gender')) {
            $query->where('s1.gender', $request->gender);
        }
        if ($request->filled('search')) {
            $s = '%'.$request->search.'%';
            $query->where(function($q) use ($s) {
                $q->where('student_masters.username','like',$s)
                  ->orWhere('s1.full_name','like',$s)
                  ->orWhere('s1.mobile_no','like',$s)
                  ->orWhere('s1.email','like',$s);
            });
        }

        $students = $query->orderBy('s1.full_name')->paginate(50)->withQueryString();

        // Summary counts
        $summary = [
            'total'      => StudentMaster::count(),
            'submitted'  => StudentMaster::where('status','SUBMITTED')->count(),
            'confirmed'  => StudentConfirmMaster::where('declaration_accepted',1)->count(),
            'incomplete' => StudentMaster::where('status','INCOMPLETE')->count(),
            'step1_done' => StudentMaster::where('step1_done',1)->count(),
            'step2_done' => StudentMaster::where('step2_done',1)->count(),
            'step3_done' => StudentMaster::where('step3_done',1)->count(),
            'bank_done'   => StudentMaster::where('bank_done', 1)->count(),
            'travel_done' => StudentMaster::where('travel_done', 1)->count(),
            'docs_done'   => StudentMaster::where('docs_done', 1)->count(),
        ];

        return view('fc.report.overview', compact(
            'students','summary','sessions','services','states'
        ));
    }

    // ── 2. Student Detail View ────────────────────────────────────────
    public function studentDetail(string $username)
    {
        $step1        = StudentMasterFirst::where('username',$username)->with(['session','service','allottedState'])->first();
        $step2        = StudentMasterSecond::where('username',$username)->with(['category','religion','permState','fatherProfession'])->first();
        $master       = StudentMaster::where('username',$username)->first();
        $bank         = NewRegistrationBankDetailsMaster::where('username',$username)->first();
        $documents    = FcJoiningRelatedDocumentsDetailsMaster::where('username',$username)->with('documentMaster')->get();
        $confirmation = StudentConfirmMaster::where('username',$username)->first();
        $qualifications = DB::table('student_master_qualification_details')
                           ->leftJoin('qualification_masters','student_master_qualification_details.qualification_id','=','qualification_masters.id')
                           ->leftJoin('board_name_masters','student_master_qualification_details.board_id','=','board_name_masters.id')
                           ->where('student_master_qualification_details.username',$username)
                           ->select('student_master_qualification_details.*','qualification_masters.qualification_name','board_name_masters.board_name')
                           ->get();
        $employments  = DB::table('student_master_employment_details')
                          ->leftJoin('job_type_masters','student_master_employment_details.job_type_id','=','job_type_masters.id')
                          ->where('student_master_employment_details.username',$username)
                          ->select('student_master_employment_details.*','job_type_masters.job_type_name')
                          ->get();
        $languages    = DB::table('student_master_language_knowns')
                          ->leftJoin('language_masters','student_master_language_knowns.language_id','=','language_masters.id')
                          ->where('student_master_language_knowns.username',$username)
                          ->select('student_master_language_knowns.*','language_masters.language_name')
                          ->get();

        abort_unless($step1, 404, "Student '{$username}' not found.");

        return view('fc.report.student-detail', compact(
            'username','step1','step2','master','bank',
            'documents','confirmation','qualifications','employments','languages'
        ));
    }

    // ── 3. Service-wise Report ────────────────────────────────────────
    public function byService(Request $request)
    {
        $sessions = SessionMaster::orderByDesc('id')->get();

        $data = DB::table('student_master_firsts as s1')
            ->join('service_masters as svc','s1.service_id','=','svc.id')
            ->leftJoin('student_masters as sm','s1.username','=','sm.username')
            ->leftJoin('student_master_seconds as s2','s1.username','=','s2.username')
            ->leftJoin('category_masters as cat','s2.category_id','=','cat.id')
            ->leftJoin('state_masters as st','s1.allotted_state_id','=','st.id')
            ->when($request->session_id, fn($q,$v) => $q->where('sm.session_id',$v))
            ->when($request->service_id, fn($q,$v) => $q->where('s1.service_id',$v))
            ->select(
                'svc.service_name','svc.service_code',
                DB::raw('COUNT(s1.username) as total'),
                DB::raw('SUM(CASE WHEN s1.gender="Male" THEN 1 ELSE 0 END) as male'),
                DB::raw('SUM(CASE WHEN s1.gender="Female" THEN 1 ELSE 0 END) as female'),
                DB::raw('SUM(CASE WHEN sm.status="SUBMITTED" THEN 1 ELSE 0 END) as submitted'),
                DB::raw('SUM(CASE WHEN sm.docs_done=1 THEN 1 ELSE 0 END) as docs_done')
            )
            ->groupBy('svc.id','svc.service_name','svc.service_code')
            ->orderBy('svc.service_name')
            ->get();

        $services = ServiceMaster::orderBy('service_name')->get();
        return view('fc.report.by-service', compact('data','sessions','services'));
    }

    // ── 4. State-wise Report ──────────────────────────────────────────
    public function byState(Request $request)
    {
        $sessions = SessionMaster::orderByDesc('id')->get();

        $data = DB::table('student_master_firsts as s1')
            ->join('state_masters as st','s1.allotted_state_id','=','st.id')
            ->leftJoin('student_masters as sm','s1.username','=','sm.username')
            ->leftJoin('service_masters as svc','s1.service_id','=','svc.id')
            ->when($request->session_id, fn($q,$v) => $q->where('sm.session_id',$v))
            ->select(
                'st.state_name','st.state_code',
                DB::raw('COUNT(s1.username) as total'),
                DB::raw('SUM(CASE WHEN s1.gender="Male" THEN 1 ELSE 0 END) as male'),
                DB::raw('SUM(CASE WHEN s1.gender="Female" THEN 1 ELSE 0 END) as female'),
                DB::raw('SUM(CASE WHEN sm.status="SUBMITTED" THEN 1 ELSE 0 END) as submitted')
            )
            ->groupBy('st.id','st.state_name','st.state_code')
            ->orderBy('st.state_name')
            ->get();

        return view('fc.report.by-state', compact('data','sessions'));
    }

    // ── 5. Document Checklist Report ──────────────────────────────────
    public function documents(Request $request)
    {
        $sessions  = SessionMaster::orderByDesc('id')->get();
        $docMasters = FcJoiningRelatedDocumentsMaster::where('is_active',1)->orderBy('display_order')->get();

        $docUploadedSql = '(d.is_uploaded = 1 OR (d.file_path IS NOT NULL AND d.file_path != \'\'))';

        $students = DB::table('student_master_firsts as s1')
            ->leftJoin('student_masters as sm','s1.username','=','sm.username')
            ->leftJoin('service_masters as svc','s1.service_id','=','svc.id')
            ->when($request->session_id, fn($q,$v) => $q->where('sm.session_id',$v))
            ->when($request->filled('doc_status'), function($q) use ($request, $docUploadedSql) {
                $totalMandatory = FcJoiningRelatedDocumentsMaster::where('is_active',1)->where('is_mandatory',1)->count();
                if ($request->doc_status === 'complete') {
                    $q->whereRaw("(SELECT COUNT(*) FROM fc_joining_related_documents_details_masters d
                                   WHERE d.username=s1.username AND {$docUploadedSql}
                                   AND d.document_master_id IN (SELECT id FROM fc_joining_related_documents_masters WHERE is_mandatory=1))
                                   = {$totalMandatory}");
                } else {
                    $q->whereRaw("(SELECT COUNT(*) FROM fc_joining_related_documents_details_masters d
                                   WHERE d.username=s1.username AND {$docUploadedSql}
                                   AND d.document_master_id IN (SELECT id FROM fc_joining_related_documents_masters WHERE is_mandatory=1))
                                   < {$totalMandatory}");
                }
            })
            ->select('s1.username','s1.full_name','svc.service_code','s1.cadre')
            ->orderBy('s1.full_name')
            ->get();

        $usernames = $students->pluck('username')->filter()->unique()->values();
        // Treat as uploaded if flagged OR a file path exists (legacy / partial saves).
        $allUploaded = $usernames->isEmpty()
            ? collect()
            : FcJoiningRelatedDocumentsDetailsMaster::whereIn('username', $usernames)
                ->where(function ($q) {
                    $q->where('is_uploaded', 1)
                        ->orWhere(function ($q2) {
                            $q2->whereNotNull('file_path')->where('file_path', '!=', '');
                        });
                })
                ->get()
                ->groupBy(fn ($row) => (string) $row->username);

        return view('fc.report.documents', compact(
            'students','docMasters','allUploaded','sessions'
        ));
    }

    // ── 6. Bank Details Report ────────────────────────────────────────
    public function bankDetails(Request $request)
    {
        $sessions = SessionMaster::orderByDesc('id')->get();

        $students = DB::table('student_master_firsts as s1')
            ->leftJoin('student_masters as sm','s1.username','=','sm.username')
            ->leftJoin('new_registration_bank_details_masters as b','s1.username','=','b.username')
            ->leftJoin('service_masters as svc','s1.service_id','=','svc.id')
            ->when($request->session_id, fn($q,$v) => $q->where('sm.session_id',$v))
            ->when($request->filled('bank_status'), function($q) use ($request) {
                if ($request->bank_status === 'filled') $q->whereNotNull('b.account_no');
                else $q->whereNull('b.account_no');
            })
            ->when($request->filled('search'), function($q) use ($request) {
                $s = '%'.$request->search.'%';
                $q->where(fn($qq) => $qq->where('s1.full_name','like',$s)->orWhere('s1.username','like',$s));
            })
            ->select(
                's1.username','s1.full_name','svc.service_code','s1.cadre',
                'b.bank_name','b.branch_name','b.ifsc_code',
                'b.account_no','b.account_holder_name','b.account_type','b.is_verified'
            )
            ->orderBy('s1.full_name')
            ->paginate(50)->withQueryString();

        return view('fc.report.bank-details', compact('students','sessions'));
    }

    // ── Export to CSV ─────────────────────────────────────────────────
    public function exportCsv(Request $request, string $type)
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=fc_{$type}_".now()->format('Ymd_His').'.csv',
            'Pragma'              => 'no-cache',
        ];

        $callback = match($type) {
            'overview' => fn() => $this->exportOverviewCsv(),
            'service'  => fn() => $this->exportServiceCsv(),
            'state'    => fn() => $this->exportStateCsv(),
            'bank'     => fn() => $this->exportBankCsv(),
            default    => abort(404),
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportOverviewCsv(): void
    {
        $out = fopen('php://output','w');
        fputcsv($out, ['S.No','Username','Full Name','Gender','Service','Service Code','Cadre',
                       'Allotted State','Mobile','Email','Step1','Step2','Step3','Bank','Travel','Docs','Status','Confirmed']);

        DB::table('student_master_firsts as s1')
          ->leftJoin('student_masters as sm','s1.username','=','sm.username')
          ->leftJoin('service_masters as svc','s1.service_id','=','svc.id')
          ->leftJoin('state_masters as st','s1.allotted_state_id','=','st.id')
          ->leftJoin('student_confirm_masters as sc','s1.username','=','sc.username')
          ->select('s1.*','svc.service_name','svc.service_code','st.state_name',
                   'sm.status','sm.step1_done','sm.step2_done','sm.step3_done',
                   'sm.bank_done','sm.travel_done','sm.docs_done','sc.declaration_accepted')
          ->orderBy('s1.full_name')
          ->chunk(200, function($rows) use ($out) {
              static $i = 0;
              foreach ($rows as $r) {
                  fputcsv($out, [
                      ++$i, $r->username, $r->full_name, $r->gender ?? '',
                      $r->service_name ?? '', $r->service_code ?? '', $r->cadre ?? '',
                      $r->state_name ?? '', $r->mobile_no ?? '', $r->email ?? '',
                      $r->step1_done ? 'Yes':'No', $r->step2_done ? 'Yes':'No',
                      $r->step3_done ? 'Yes':'No', $r->bank_done ? 'Yes':'No',
                      $r->travel_done ? 'Yes':'No', $r->docs_done ? 'Yes':'No', $r->status ?? 'INCOMPLETE',
                      $r->declaration_accepted ? 'Yes':'No',
                  ]);
              }
          });
        fclose($out);
    }

    private function exportServiceCsv(): void
    {
        $out = fopen('php://output','w');
        fputcsv($out, ['Service','Code','Total','Male','Female','Submitted','Docs Done']);
        DB::table('student_master_firsts as s1')
          ->join('service_masters as svc','s1.service_id','=','svc.id')
          ->leftJoin('student_masters as sm','s1.username','=','sm.username')
          ->select('svc.service_name','svc.service_code',
                   DB::raw('COUNT(*) as total'),
                   DB::raw('SUM(s1.gender="Male") as male'),
                   DB::raw('SUM(s1.gender="Female") as female'),
                   DB::raw('SUM(sm.status="SUBMITTED") as submitted'),
                   DB::raw('SUM(sm.docs_done=1) as docs_done'))
          ->groupBy('svc.id','svc.service_name','svc.service_code')
          ->orderBy('svc.service_name')
          ->each(fn($r) => fputcsv($out, [$r->service_name,$r->service_code,$r->total,$r->male,$r->female,$r->submitted,$r->docs_done]));
        fclose($out);
    }

    private function exportStateCsv(): void
    {
        $out = fopen('php://output','w');
        fputcsv($out, ['State','Code','Total','Male','Female','Submitted']);
        DB::table('student_master_firsts as s1')
          ->join('state_masters as st','s1.allotted_state_id','=','st.id')
          ->leftJoin('student_masters as sm','s1.username','=','sm.username')
          ->select('st.state_name','st.state_code',
                   DB::raw('COUNT(*) as total'),
                   DB::raw('SUM(s1.gender="Male") as male'),
                   DB::raw('SUM(s1.gender="Female") as female'),
                   DB::raw('SUM(sm.status="SUBMITTED") as submitted'))
          ->groupBy('st.id','st.state_name','st.state_code')
          ->orderBy('st.state_name')
          ->each(fn($r) => fputcsv($out, [$r->state_name,$r->state_code,$r->total,$r->male,$r->female,$r->submitted]));
        fclose($out);
    }

    private function exportBankCsv(): void
    {
        $out = fopen('php://output','w');
        fputcsv($out, ['Username','Full Name','Service','Bank Name','Branch','IFSC','Account No','Holder Name','Type','Verified']);
        DB::table('student_master_firsts as s1')
          ->leftJoin('service_masters as svc','s1.service_id','=','svc.id')
          ->leftJoin('new_registration_bank_details_masters as b','s1.username','=','b.username')
          ->whereNotNull('b.account_no')
          ->select('s1.username','s1.full_name','svc.service_code','b.*')
          ->orderBy('s1.full_name')
          ->each(fn($r) => fputcsv($out, [
              $r->username,$r->full_name,$r->service_code ?? '',
              $r->bank_name ?? '',$r->branch_name ?? '',$r->ifsc_code ?? '',
              $r->account_no ?? '',$r->account_holder_name ?? '',
              $r->account_type ?? '',$r->is_verified ? 'Yes':'No',
          ]));
        fclose($out);
    }
}
