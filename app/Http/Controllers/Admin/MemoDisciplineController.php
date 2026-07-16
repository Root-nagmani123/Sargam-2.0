<?php
// app/Http/Controllers/Admin/MemoNoticeController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemoNoticeTemplate;
use App\Models\CourseMaster;
use App\Models\MemoDiscipline;
use App\Models\DisciplineMaster;
use App\Models\StudentMaster;
use App\Services\NotificationService;
use App\Exports\DisciplineMemoExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

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
    $categoryFilter      = $request->minor_major;

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

    // Page size (design-system footer "Showing [N] of M items" dropdown).
    // "all" is kept in the URL/dropdown as-is, but the actual paginate() call needs
    // a real integer — a large cap works fine since paginate() runs its own COUNT
    // query regardless, so it never returns more rows than actually match.
    $allowedPerPage = ['10', '25', '50', '100', '200', 'all'];
    $perPageParam = (string) $request->get('per_page', '10');
    if (!in_array($perPageParam, $allowedPerPage, true)) {
        $perPageParam = '10';
    }
    $perPage = $perPageParam === 'all' ? 100000 : (int) $perPageParam;

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
        ->when($categoryFilter !== null && $categoryFilter !== '', function ($q) use ($categoryFilter) {
            $q->where('minor_major', $categoryFilter);
        })
        ->when($searchFilter, function ($q) use ($searchFilter) {
            $q->where(function ($sub) use ($searchFilter) {
                $sub->whereHas('student', function ($s) use ($searchFilter) {
                        $s->where('display_name', 'like', "%{$searchFilter}%")
                          ->orWhere('generated_OT_code', 'like', "%{$searchFilter}%")
                          ->orWhereHas('cadre', function ($c) use ($searchFilter) {
                              $c->where('cadre_name', 'like', "%{$searchFilter}%");
                          });
                    })
                    ->orWhereHas('course', function ($c) use ($searchFilter) {
                        $c->where('course_name', 'like', "%{$searchFilter}%");
                    })
                    ->orWhereHas('discipline', function ($d) use ($searchFilter) {
                        $d->where('discipline_name', 'like', "%{$searchFilter}%");
                    })
                    ->orWhere('remarks', 'like', "%{$searchFilter}%")
                    ->orWhere('mark_deduction_submit', 'like', "%{$searchFilter}%")
                    ->orWhere('final_mark_deduction', 'like', "%{$searchFilter}%")
                    ->orWhere('date', 'like', "%{$searchFilter}%");
            });
        })
        ->when($fromDateFilter && $toDateFilter, function ($q) use ($fromDateFilter, $toDateFilter) {
            $q->whereBetween('date', [$fromDateFilter, $toDateFilter]);
        })
        ->whereHas('discipline', function ($q) {
            $q->where('active_inactive', 1);
        })
        ->orderBy('pk', 'desc')
        ->paginate($perPage)
        // Append the RESOLVED filter values (not just $request->all()) so every
        // pagination link reproduces the exact filtered view on a full reload —
        // otherwise the server-defaulted date filter is dropped and filters "reset".
        ->appends([
            'program_name'         => $programNameFilter ?? '',
            'discipline_master_pk' => $disciplineFilter ?? '',
            'status'               => $statusFilter ?? '',
            'minor_major'          => $categoryFilter ?? '',
            'search'               => $searchFilter ?? '',
            'from_date'            => $fromDateFilter ?? '',
            'to_date'              => $toDateFilter ?? '',
            'per_page'             => $perPageParam,
        ]);

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
        'categoryFilter',
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
    $categoryFilter    = $request->minor_major;

    // OT page defaults to their full history (no implicit "today" restriction).
    $fromDateFilter = $request->get('from_date') ?: null;
    $toDateFilter   = $request->get('to_date') ?: null;

    $disciplines = DisciplineMaster::where('active_inactive', 1)
        ->select('discipline_name')
        ->distinct()
        ->orderBy('discipline_name')
        ->get();

    // Page size (design-system footer "Showing [N] of M items" dropdown).
    $allowedPerPage = [10, 25, 50, 100, 200];
    $perPage = (int) $request->get('per_page', 10);
    if (!in_array($perPage, $allowedPerPage, true)) {
        $perPage = 10;
    }

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
        ->when($categoryFilter !== null && $categoryFilter !== '', function ($q) use ($categoryFilter) {
            $q->where('minor_major', $categoryFilter);
        })
        ->when($searchFilter, function ($q) use ($searchFilter) {
            $q->where(function ($sub) use ($searchFilter) {
                $sub->whereHas('course', function ($c) use ($searchFilter) {
                        $c->where('course_name', 'like', "%{$searchFilter}%");
                    })
                    ->orWhereHas('discipline', function ($d) use ($searchFilter) {
                        $d->where('discipline_name', 'like', "%{$searchFilter}%");
                    })
                    ->orWhere('remarks', 'like', "%{$searchFilter}%")
                    ->orWhere('mark_deduction_submit', 'like', "%{$searchFilter}%")
                    ->orWhere('final_mark_deduction', 'like', "%{$searchFilter}%")
                    ->orWhere('date', 'like', "%{$searchFilter}%");
            });
        })
        ->when($fromDateFilter && $toDateFilter, function ($q) use ($fromDateFilter, $toDateFilter) {
            $q->whereBetween('date', [$fromDateFilter, $toDateFilter]);
        })
        ->whereHas('discipline', function ($q) {
            $q->where('active_inactive', 1);
        })
        ->orderBy('pk', 'desc')
        ->paginate($perPage)
        // Append the RESOLVED filter values (not just $request->all()) so every
        // pagination link reproduces the exact filtered view on a full reload —
        // otherwise the server-defaulted date filter is dropped and filters "reset".
        ->appends([
            'program_name'         => $programNameFilter ?? '',
            'discipline_master_pk' => $disciplineFilter ?? '',
            'status'               => $statusFilter ?? '',
            'minor_major'          => $categoryFilter ?? '',
            'search'               => $searchFilter ?? '',
            'from_date'            => $fromDateFilter ?? '',
            'to_date'              => $toDateFilter ?? '',
            'per_page'             => $perPage,
        ]);

    return view('admin.memo_discipline.ot_index', compact(
        'memos',
        'courses',
        'disciplines',
        'programNameFilter',
        'statusFilter',
        'categoryFilter',
        'disciplineFilter',
        'searchFilter',
        'fromDateFilter',
        'toDateFilter'
    ));
}

/**
 * Hard-delete a discipline memo along with its conversation messages and uploaded files.
 */
public function destroy($id)
{
    if (! (hasRole('Internal Faculty') || hasRole('Guest Faculty')
        || hasRole('Super Admin') || hasRole('Training Induction Admin') || hasRole('Training-Induction'))) {
        return response()->json(['success' => false, 'message' => 'You are not authorized to delete this record.'], 403);
    }

    $memo = MemoDiscipline::find($id);
    if (! $memo) {
        return response()->json(['success' => false, 'message' => 'Discipline memo not found.'], 404);
    }

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

        return response()->json(['success' => true, 'message' => 'Discipline memo deleted successfully.']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Failed to delete the discipline memo. Please try again.'], 500);
    }
}

/**
 * Download the Send Discipline Memo listing as a styled Excel report.
 * Same filters/sort/dataset as index() (minus pagination).
 */
public function exportCsv(Request $request)
{
    ['memos' => $memos, 'filters' => $filters] = $this->buildDisciplineExportData($request);

    $fileName = 'send-discipline-memo-' . now()->format('Y-m-d_His') . '.xlsx';

    return Excel::download(new DisciplineMemoExport($memos, $filters, now()->format('d-m-Y H:i:s')), $fileName);
}

/**
 * Download the same listing as a PDF, using the same LBSNAA-branded layout as the Excel export.
 */
public function exportPdf(Request $request)
{
    ['memos' => $memos, 'filters' => $filters] = $this->buildDisciplineExportData($request);

    @ini_set('memory_limit', '256M');
    @set_time_limit(120);

    $logoPath = public_path('images/lbsnaa_logo.jpg');
    $logo = (is_file($logoPath) && is_readable($logoPath))
        ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath))
        : null;

    $headings = ['Program Name', 'Student Name', 'OT/Participant Code', 'Cadre', 'Date of Infraction',
        'Infraction', 'Submitted Marks', 'Final Marks', 'Remarks', 'Conclusion Remark', 'Created Date', 'Status'];

    $statusLabels = ['1' => 'Recorded', '2' => 'Memo Sent', '3' => 'Closed'];
    $rows = $memos->map(function ($memo) use ($statusLabels) {
        return [
            $memo->course->course_name ?? 'N/A',
            $memo->student->display_name ?? 'N/A',
            $memo->student->generated_OT_code ?? 'N/A',
            $memo->student->cadre->cadre_name ?? 'N/A',
            $memo->date ? Carbon::parse($memo->date)->format('d M Y') : 'N/A',
            $memo->discipline->discipline_name ?? 'N/A',
            $memo->mark_deduction_submit ?? '',
            $memo->final_mark_deduction ?? '',
            $memo->remarks ?? '',
            $memo->conclusion_remark ?? '',
            $memo->created_date ? Carbon::parse($memo->created_date)->format('d M Y') : 'N/A',
            $statusLabels[(string) $memo->status] ?? 'Closed',
        ];
    });

    $filterLine = 'Program: ' . $filters['program']
        . '  |  Discipline: ' . $filters['discipline']
        . '  |  Status: ' . $filters['status']
        . '  |  Category: ' . $filters['category']
        . '  |  Period: ' . $filters['period'];

    $pdf = Pdf::loadView('admin.memo_discipline.export_pdf', [
        'headings'    => $headings,
        'rows'        => $rows,
        'filterLine'  => $filterLine,
        'printedOn'   => now()->format('d-m-Y H:i'),
        'reportTitle' => 'Discipline Memo Report',
        'logo'        => $logo,
    ])
        ->setPaper('a4', 'landscape')
        ->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isPhpEnabled' => true,
            'dpi' => 96,
        ]);

    return $pdf->download('send-discipline-memo-' . now()->format('Y-m-d_His') . '.pdf');
}

/**
 * Bulk-download one PDF per selected memo — same content as memo_show() (template +
 * conversation thread) — bundled into a single ZIP so each student stays a separate file.
 */
public function exportPdfZip(Request $request)
{
    $ids = array_values(array_filter((array) $request->input('ids', []), fn ($id) => is_numeric($id)));
    if (empty($ids)) {
        return back()->with('error', 'No records selected.');
    }

    @ini_set('memory_limit', '512M');
    @set_time_limit(300);

    $logoPath = public_path('images/lbsnaa_logo.jpg');
    $logo = (is_file($logoPath) && is_readable($logoPath))
        ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath))
        : null;

    $memos = MemoDiscipline::with([
        'course:pk,course_name',
        'discipline:pk,discipline_name',
        'student:pk,display_name,generated_OT_code',
        'messages',
        'template',
        'chosenTemplate',
    ])->whereIn('pk', $ids)->get();

    if ($memos->isEmpty()) {
        return back()->with('error', 'No matching records found.');
    }

    $tmpPath = tempnam(sys_get_temp_dir(), 'disc_memo_pdf_zip_');
    $zip = new \ZipArchive();
    if ($zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
        return back()->with('error', 'Could not create ZIP archive.');
    }

    $usedNames = [];
    $added = 0;

    foreach ($memos as $memo) {
        $template = null;
        if ($memo->template_snapshot) {
            $snapshot = json_decode($memo->template_snapshot, true);
            if (is_array($snapshot)) {
                $template = (object) $snapshot;
            }
        }
        if (!$template) {
            $template = $memo->chosenTemplate ?: $memo->template;
        }

        $conclusionTypeName = null;
        if ($memo->conclusion_type_pk) {
            $conclusionTypeName = DB::table('memo_conclusion_master')
                ->where('pk', $memo->conclusion_type_pk)->value('discussion_name');
        }

        foreach ($memo->messages as $message) {
            $identity = resolve_chat_sender_identity($message->created_by, $message->role_type);
            $message->display_name = $identity['display_name'];
            $message->role_name = $identity['role_name'];
        }

        $signature = null;
        if ($template && !empty($template->signature_image)) {
            $sigPath = public_path('storage/' . $template->signature_image);
            if (is_file($sigPath) && is_readable($sigPath)) {
                $ext = strtolower(pathinfo($sigPath, PATHINFO_EXTENSION));
                $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
                $signature = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($sigPath));
            }
        }

        $html = view('admin.memo_discipline.memo_show_pdf', [
            'memo'                 => $memo,
            'template'             => $template,
            'conclusion_type_name' => $conclusionTypeName,
            'logo'                 => $logo,
            'signature'            => $signature,
        ])->render();

        $bytes = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'dpi' => 96,
            ])
            ->output();

        // Name + date of infraction, so a batch of downloaded PDFs is identifiable at a
        // glance in the file explorer without opening each one.
        $namePart = trim((string) preg_replace('/[^A-Za-z0-9_\-]+/', '_', $memo->student->display_name ?? ''), '_');
        $datePart = $memo->date ? Carbon::parse($memo->date)->format('d-M-Y') : '';
        $baseName = trim($namePart . ($datePart ? '_' . $datePart : ''), '_');
        $baseName = $baseName !== '' ? $baseName : ('memo_' . $memo->pk);
        $name = $baseName;
        $suffix = 1;
        while (isset($usedNames[$name])) {
            $name = $baseName . '_' . (++$suffix);
        }
        $usedNames[$name] = true;

        $zip->addFromString($name . '.pdf', $bytes);
        $added++;
    }

    $zip->close();

    if ($added === 0) {
        @unlink($tmpPath);
        return back()->with('error', 'Could not generate any PDFs.');
    }

    $filename = 'discipline-memos-' . now()->format('Y-m-d_His') . '.zip';

    return response()->download($tmpPath, $filename, ['Content-Type' => 'application/zip'])
        ->deleteFileAfterSend(true);
}

/**
 * Shared filtered/sorted dataset for the discipline memo exports (Excel + PDF) —
 * same filters and sort as index(), just unpaginated.
 */
private function buildDisciplineExportData(Request $request): array
{
    $data_course_id = get_Role_by_course();

    // Filters (identical to index)
    $programNameFilter = $request->program_name;
    $statusFilter      = $request->status;
    $searchFilter      = $request->search;
    $disciplineFilter  = $request->discipline_master_pk;
    $categoryFilter    = $request->minor_major;

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

    // Export order must match whatever order the list page is showing on screen
    // (the "Download" link carries sort_col/sort_dir from the DataTable's current
    // sort — see index.blade.php). Related-table columns sort via a scalar
    // subquery so the base query stays a plain, non-joined MemoDiscipline query.
    $sortCol = $request->get('sort_col');
    $sortDir = strtolower((string) $request->get('sort_dir')) === 'desc' ? 'desc' : 'asc';
    $sortableColumns = [
        'name' => fn () => StudentMaster::select('display_name')
            ->whereColumn('pk', 'discipline_memo_status.student_master_pk')->limit(1),
        'program' => fn () => CourseMaster::select('course_name')
            ->whereColumn('pk', 'discipline_memo_status.course_master_pk')->limit(1),
        'ot_code' => fn () => StudentMaster::select('generated_OT_code')
            ->whereColumn('pk', 'discipline_memo_status.student_master_pk')->limit(1),
        'cadre' => fn () => DB::table('student_master as sm')
            ->join('cadre_master as cm', 'cm.pk', '=', 'sm.cadre_master_pk')
            ->whereColumn('sm.pk', 'discipline_memo_status.student_master_pk')
            ->select('cm.cadre_name')->limit(1),
        'infraction' => fn () => DisciplineMaster::select('discipline_name')
            ->whereColumn('pk', 'discipline_memo_status.discipline_master_pk')->limit(1),
        'date'              => 'date',
        'submitted'         => 'mark_deduction_submit',
        'final'             => 'final_mark_deduction',
        'remarks'           => 'remarks',
        'conclusion_remark' => 'conclusion_remark',
        'created_date'      => 'created_date',
        'status'            => 'status',
    ];

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
        ->when($categoryFilter !== null && $categoryFilter !== '', function ($q) use ($categoryFilter) {
            $q->where('minor_major', $categoryFilter);
        })
        ->when($searchFilter, function ($q) use ($searchFilter) {
            $q->where(function ($sub) use ($searchFilter) {
                $sub->whereHas('student', function ($s) use ($searchFilter) {
                        $s->where('display_name', 'like', "%{$searchFilter}%")
                          ->orWhere('generated_OT_code', 'like', "%{$searchFilter}%")
                          ->orWhereHas('cadre', function ($c) use ($searchFilter) {
                              $c->where('cadre_name', 'like', "%{$searchFilter}%");
                          });
                    })
                    ->orWhereHas('course', function ($c) use ($searchFilter) {
                        $c->where('course_name', 'like', "%{$searchFilter}%");
                    })
                    ->orWhereHas('discipline', function ($d) use ($searchFilter) {
                        $d->where('discipline_name', 'like', "%{$searchFilter}%");
                    })
                    ->orWhere('remarks', 'like', "%{$searchFilter}%")
                    ->orWhere('mark_deduction_submit', 'like', "%{$searchFilter}%")
                    ->orWhere('final_mark_deduction', 'like', "%{$searchFilter}%")
                    ->orWhere('date', 'like', "%{$searchFilter}%");
            });
        })
        ->when($fromDateFilter && $toDateFilter, function ($q) use ($fromDateFilter, $toDateFilter) {
            $q->whereBetween('date', [$fromDateFilter, $toDateFilter]);
        })
        ->whereHas('discipline', function ($q) {
            $q->where('active_inactive', 1);
        })
        ->when($sortCol && array_key_exists($sortCol, $sortableColumns), function ($q) use ($sortableColumns, $sortCol, $sortDir) {
            $column = $sortableColumns[$sortCol];
            $q->orderBy(is_callable($column) ? $column() : $column, $sortDir);
        }, function ($q) {
            $q->orderBy('pk', 'desc');
        })
        ->get();

    $courseName = $programNameFilter ? (optional(CourseMaster::find($programNameFilter))->course_name ?? 'All') : 'All';
    $dateRange = ($fromDateFilter || $toDateFilter)
        ? (($fromDateFilter ? Carbon::parse($fromDateFilter)->format('d-m-Y') : '—') . ' to ' . ($toDateFilter ? Carbon::parse($toDateFilter)->format('d-m-Y') : '—'))
        : 'All Dates';
    $statusLabels = ['1' => 'Recorded', '2' => 'Memo Sent', '3' => 'Closed'];
    $categoryLabels = ['1' => 'Minor', '2' => 'Major'];

    $filters = [
        'program'    => $courseName,
        'discipline' => $disciplineFilter ?: 'All',
        'status'     => $statusLabels[$statusFilter] ?? 'All',
        'category'   => $categoryLabels[$categoryFilter] ?? 'All',
        'period'     => $dateRange,
    ];

    return ['memos' => $memos, 'filters' => $filters];
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

        // Prior major/minor tally per student for this course, so the picker can
        // surface each defaulter's discipline history while selecting them.
        $majorMinorCounts = DB::table('discipline_memo_status')
            ->where('course_master_pk', $courseId)
            ->whereIn('student_master_pk', $attendance->pluck('student_pk'))
            ->selectRaw('student_master_pk, SUM(CASE WHEN minor_major = 2 THEN 1 ELSE 0 END) as major_count, SUM(CASE WHEN minor_major = 1 THEN 1 ELSE 0 END) as minor_count')
            ->groupBy('student_master_pk')
            ->get()
            ->keyBy('student_master_pk');

        // Format the attendance data
        $students = $attendance->map(function ($student) use ($majorMinorCounts) {
            $counts = $majorMinorCounts->get($student->student_pk);
            return [
                'pk' => (int) $student->student_pk,
                'display_name' => $student->display_name,
                'generated_OT_code' => $student->generated_OT_code,
                'major_count' => $counts ? (int) $counts->major_count : 0,
                'minor_count' => $counts ? (int) $counts->minor_count : 0,
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
            // Fall back to the course/discipline's active template when none was picked,
            // so this send still gets a pinned + frozen template.
            $templatePk = $request->memo_notice_template_pk
                ?: resolve_default_discipline_memo_template_pk($request->course_master_pk, $request->discipline_master_pk);
            $templateSnapshot = build_memo_notice_template_snapshot($templatePk);

            foreach($request->selected_student_list as $student_pk){
                // Insert memo record for each student
                DB::table('discipline_memo_status')->insert([
                    'course_master_pk' => $request->course_master_pk,
                    'discipline_master_pk' => $request->discipline_master_pk,
                    'memo_notice_template_pk' => $templatePk,
                    'template_snapshot' => $templateSnapshot,
                    'student_master_pk' => $student_pk,
                    'date' => $request->date_of_memo,
                    'mark_deduction_submit' => $request->discipline_marks,
                    'minor_major' => 1, // defaults to Minor until an incharge marks it Major during edit
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
            if ($memo && $memo->status != 2) {
                $memo->status = 2;
                $memo->modified_date = now();
                $memo->save();

                // Notify the OT student
                $credential = DB::table('user_credentials')
                    ->where('user_id', $memo->student_master_pk)
                    ->where('user_category', 'S')
                    ->first();

                if ($credential) {
                    app(NotificationService::class)->create(
                        $credential->pk,
                        'memo',
                        'MemoDiscipline',
                        $memo->pk,
                        'Discipline Memo Generated',
                        'A discipline memo has been issued to you. Please review and respond.'
                    );
                }

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
                // OT sent a message → notify admins (sender_user_id = current OT credential pk)
                // Find admin users who manage this course — notify a general admin channel via reference
                // For now: notify the sender's counterpart (incharge) — stored as Admin role users
                // We create a notification for the Admin group using receiver_user_id = 0 as broadcast placeholder
                // Better approach: notify all active admin users watching this memo
                $adminCredentials = DB::table('user_credentials')
                    ->where('user_category', '!=', 'S')
                    ->whereIn('user_category', ['F', 'A'])
                    ->limit(20)
                    ->pluck('pk')
                    ->toArray();
                if (!empty($adminCredentials)) {
                    app(NotificationService::class)->createMultiple(
                        $adminCredentials,
                        'memo',
                        'MemoDiscipline',
                        $memo->pk,
                        'OT Replied to Discipline Memo',
                        'A student has replied to a discipline memo.'
                    );
                }
            } else {
                // Admin/Faculty sent a message → notify the OT student
                $credential = DB::table('user_credentials')
                    ->where('user_id', $memo->student_master_pk)
                    ->where('user_category', 'S')
                    ->first();
                if ($credential) {
                    app(NotificationService::class)->create(
                        $credential->pk,
                        'memo',
                        'MemoDiscipline',
                        $memo->pk,
                        'New Message on Your Discipline Memo',
                        'The incharge has replied to your discipline memo.'
                    );
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

        // Prior major/minor tally for this participant WITHIN this course (excluding the
        // memo being edited itself), so the incharge can see their history on this
        // program while deciding the category for this memo.
        $majorCount = MemoDiscipline::where('student_master_pk', $memo->student_master_pk)
            ->where('course_master_pk', $memo->course_master_pk)
            ->where('pk', '!=', $memo->pk)
            ->where('minor_major', 2)->count();
        $minorCount = MemoDiscipline::where('student_master_pk', $memo->student_master_pk)
            ->where('course_master_pk', $memo->course_master_pk)
            ->where('pk', '!=', $memo->pk)
            ->where('minor_major', 1)->count();

        return response()->json([
            'pk'                      => $memo->pk,
            'course_master_pk'        => $memo->course_master_pk,
            'course_name'             => $memo->course->course_name ?? 'N/A',
            'student_name'            => trim(($memo->student->generated_OT_code ? $memo->student->generated_OT_code . '- ' : '') . ($memo->student->display_name ?? 'N/A')),
            'date'                    => $memo->date,
            'discipline_master_pk'    => $memo->discipline_master_pk,
            'mark_deduction_submit'   => $memo->mark_deduction_submit,
            'minor_major'             => $memo->minor_major,
            'remarks'                 => $memo->remarks ?? '',
            'memo_notice_template_pk' => $memo->memo_notice_template_pk,
            'disciplines'             => $disciplines,
            'major_count'             => $majorCount,
            'minor_count'             => $minorCount,
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
            'minor_major'             => 'required|in:1,2',
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
            'minor_major'             => $validated['minor_major'],
            'remarks'                 => $validated['remarks'] ?? null,
            'memo_notice_template_pk' => $templatePk,
            // Re-pinning the template also re-freezes its content as of now.
            'template_snapshot'       => build_memo_notice_template_snapshot($templatePk),
            'modified_date'           => now(),
        ]);

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

    // Prefer the content frozen at send time, so a later template edit doesn't change
    // what's shown here. Memos sent before this feature existed have no snapshot yet,
    // so they fall back to the template pinned/resolved live, as before.
    $template = null;
    if ($memo && $memo->template_snapshot) {
        $snapshot = json_decode($memo->template_snapshot, true);
        if (is_array($snapshot)) {
            $template = (object) $snapshot;
        }
    }
    if (!$template && $memo) {
        $template = $memo->chosenTemplate ?: $memo->template;
    }
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