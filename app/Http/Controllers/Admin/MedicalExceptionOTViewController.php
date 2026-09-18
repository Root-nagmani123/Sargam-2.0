<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LbsnaaTableExport;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\StudentMaster;
use App\Models\StudentMedicalExemption;

class MedicalExceptionOTViewController extends Controller
{
    public function index(Request $request)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to access this page.');
        }

        $user = Auth::user();
        
        // Check if user_category = 'S' (Student)
        $userCategory = DB::table('user_credentials')
            ->where('pk', $user->pk)
            ->value('user_category');
        
        if ($userCategory !== 'S') {
            // If not a student, only show admin view if user is NOT faculty (F)
            // Faculty should use faculty view, not OT view
            if ($userCategory === 'F') {
                return redirect()->back()->with('error', 'Access denied. Faculty members should use the Faculty View page.');
            }
            // Show admin view for other user categories (admin, etc.)
            return $this->adminView($request);
        }
        
        // Get user_id from user_credentials (which points to student_master.pk)
        $studentMasterPk = DB::table('user_credentials')
            ->where('pk', $user->pk)
            ->value('user_id');
        
        if (!$studentMasterPk) {
            return redirect()->back()->with('error', 'Student record not found.');
        }
        
        // Match with student_master table
        $student = StudentMaster::where('pk', $studentMasterPk)->first();
        
        if (!$student) {
            return redirect()->back()->with('error', 'Student record not found.');
        }
        
        // Count how many times the student exists in student_medical_exemption
        $exemptionCount = StudentMedicalExemption::where('student_master_pk', $studentMasterPk)
            ->count();
        
        $validExemptions = $this->studentExemptions((int) $studentMasterPk)->all();

        // Prepare data for view
        $studentData = [
            'student_name' => $student->display_name ?? ($student->first_name . ' ' . $student->last_name),
            'ot_code' => $student->generated_OT_code,
            'email' => $student->email,
            'total_exemption_count' => $exemptionCount, // Total count of all exceptions
            'exemptions' => $validExemptions, // Only valid exemptions (with active courses)
            'has_exemptions' => count($validExemptions) > 0, // Flag to check if student has any valid exemptions
        ];
        
        return view('admin.medical_exception.ot_view', compact('studentData'));
    }
    
    /**
     * One officer trainee's exemptions on a running course, newest first.
     *
     * Shared by the page and the download, so a downloaded sheet always lists
     * exactly the entries the page showed — which is also what makes ?entry=N
     * safe to address by position.
     *
     * The course filter is a join rather than a per-row CourseMaster::find, and
     * the doctor / category / speciality names come through eager-loaded
     * relations.
     */
    private function studentExemptions(int $studentMasterPk)
    {
        return StudentMedicalExemption::query()
            ->with(['employee', 'category', 'speciality'])
            ->join('course_master as cm', 'cm.pk', '=', 'student_medical_exemption.course_master_pk')
            ->where('student_medical_exemption.student_master_pk', $studentMasterPk)
            ->where('cm.active_inactive', 1)
            ->where('cm.end_date', '>=', now()->format('Y-m-d'))
            ->orderByDesc('student_medical_exemption.from_date')
            ->get(['student_medical_exemption.*', 'cm.course_name'])
            // from_date / to_date are datetime columns, so the date and the time
            // asked for are two views of one value, not two fields.
            ->map(fn ($exemption) => [
                'course_name' => $exemption->course_name,
                'doctor_name' => $this->doctorName($exemption->employee),
                'from_date' => $exemption->from_date,
                'to_date' => $exemption->to_date,
                'exemption_category' => $exemption->category->exemp_category_name ?? null,
                'medical_speciality' => $exemption->speciality->speciality_name ?? null,
                'opd_category' => $exemption->opd_category,
                'description' => $exemption->Description,
                'doc_upload' => $exemption->Doc_upload,
            ])
            ->values();
    }

    /**
     * Excel or PDF of the officer trainee's own medical exemptions.
     *
     * ?entry=N narrows it to a single exemption — the per-entry download button
     * on each card. The index is the position in the same list the page renders,
     * which is resolved server-side from the logged-in account, so it cannot be
     * pointed at another trainee's record.
     */
    public function export(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $userCategory = DB::table('user_credentials')->where('pk', $user->pk)->value('user_category');

        if ($userCategory !== 'S') {
            abort(403, 'This download is for officer trainees.');
        }

        $studentMasterPk = DB::table('user_credentials')->where('pk', $user->pk)->value('user_id');
        $student = $studentMasterPk ? StudentMaster::find($studentMasterPk) : null;

        if (! $student) {
            abort(404, 'Student record not found.');
        }

        $exemptions = $this->studentExemptions((int) $studentMasterPk);

        $entry = $request->query('entry');
        $single = false;
        if ($entry !== null && $entry !== '' && is_numeric($entry)) {
            $picked = $exemptions->values()->get((int) $entry);
            if (! $picked) {
                abort(404, 'That exemption is not on your record.');
            }
            $exemptions = collect([$picked]);
            $single = true;
        }

        $headings = ['S. No.', 'Course Name', 'Doctor Name', 'Date & Time From', 'Date & Time To',
            'Exemption Category', 'Medical Speciality', 'Diagnosis / Remarks'];
        $centreColumns = [0, 3, 4];

        $serial = 1;
        $rows = $exemptions->map(fn ($e) => [
            $serial++,
            $e['course_name'] ?: '-',
            $e['doctor_name'] ?: '-',
            $this->dateTimeLabel($e['from_date']),
            $this->dateTimeLabel($e['to_date'], 'Ongoing'),
            $e['exemption_category'] ?: '-',
            $e['medical_speciality'] ?: '-',
            $e['description'] ?: '-',
        ])->values();

        $name = $student->display_name ?: trim($student->first_name . ' ' . $student->last_name);
        $filterLine = trim($name . (filled($student->generated_OT_code) ? ' (' . $student->generated_OT_code . ')' : ''));
        $title = 'Medical Exemption';
        $baseName = 'Medical_Exemption_' . preg_replace('/[^A-Za-z0-9]+/', '_', $name)
            . ($single ? '_Entry' : '') . '_' . now()->format('Ymd_His');

        if (strtolower((string) $request->query('format')) === 'pdf') {
            @ini_set('memory_limit', '256M');
            @set_time_limit(120);

            return Pdf::loadView('admin.exports.table_pdf', [
                'headings' => $headings,
                'rows' => $rows,
                'reportTitle' => $title,
                'filterLine' => $filterLine,
                'centreColumns' => $centreColumns,
            ])->setPaper('a4', 'landscape')->download($baseName . '.pdf');
        }

        return Excel::download(
            new LbsnaaTableExport($rows, $headings, $title, $filterLine, $centreColumns),
            $baseName . '.xlsx'
        );
    }

    /** "10/09/2026 09:30 AM", or just the date where no time was recorded. */
    private function dateTimeLabel($value, string $emptyLabel = 'N/A'): string
    {
        if (blank($value)) {
            return $emptyLabel;
        }

        $date = Carbon::parse($value);

        return $date->format('H:i') === '00:00'
            ? $date->format('d/m/Y')
            : $date->format('d/m/Y h:i A');
    }

    /**
     * Treating doctor's display name — employee_master keeps it as first/last,
     * unlike faculty_master which has a full_name.
     */
    private function doctorName($employee): ?string
    {
        if (! $employee) {
            return null;
        }

        return trim(implode(' ', array_filter([
            $employee->first_name ?? '',
            $employee->last_name ?? '',
        ]))) ?: null;
    }

    /**
     * Admin view for non-student users (original functionality)
     */
    private function adminView(Request $request)
    {
        // Get filter parameters
        $studentFilter = $request->get('student_filter');
        $courseFilter = $request->get('course_filter');
        
        // Get all active students
        $studentsQuery = StudentMaster::where('status', 1);
        
        if ($studentFilter) {
            $studentsQuery->where(function($q) use ($studentFilter) {
                $q->where('generated_OT_code', 'like', '%' . $studentFilter . '%')
                  ->orWhere('display_name', 'like', '%' . $studentFilter . '%');
            });
        }
        
        $students = $studentsQuery->orderBy('display_name')->get(['pk', 'generated_OT_code', 'display_name']);
        
        // Build the data structure with exemption counts
        $studentData = [];
        
        foreach ($students as $student) {
            // Count total medical exceptions for this student
            $exemptionQuery = StudentMedicalExemption::where('student_master_pk', $student->pk)
                ->where('active_inactive', 1);
            
            // Filter by course if specified
            if ($courseFilter) {
                $exemptionQuery->where('course_master_pk', $courseFilter);
            }
            
            $exemptionCount = $exemptionQuery->count();
            
            // Get exemption details
            $exemptions = $exemptionQuery->with(['course', 'category', 'speciality'])
                ->orderBy('from_date', 'desc')
                ->get();
            
            $studentData[] = [
                'student_id' => $student->pk,
                'ot_code' => $student->generated_OT_code,
                'student_name' => $student->display_name,
                'exemption_count' => $exemptionCount,
                'exemptions' => $exemptions,
            ];
        }
        
        // Filter out students with 0 exemptions if no filters are applied
        if (!$studentFilter && !$courseFilter) {
            $studentData = array_filter($studentData, function($item) {
                return $item['exemption_count'] > 0;
            });
        }
        
        // Get all active courses for filter dropdown (active_inactive = 1 and end_date > current date)
        $currentDate = now()->format('Y-m-d');
        $allCourses = DB::table('course_master')
            ->where('active_inactive', 1)
            ->where('end_date', '>', $currentDate)
            ->orderBy('course_name')
            ->get(['pk', 'course_name']);
        
        return view('admin.medical_exception.ot_view', compact(
            'studentData',
            'allCourses',
            'studentFilter',
            'courseFilter'
        ));
    }
}

