<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\DataTables\ExaminationDriveDataTable;
use App\Exports\ExaminationDriveExport;
use App\Models\CourseMaster;
use App\Models\ExaminationDrive;
use App\Models\ExaminationTypeMaster;
use App\Models\TermMaster;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ExaminationDriveController extends Controller
{
    public function index(ExaminationDriveDataTable $dataTable)
    {
        $examinationTypes = ExaminationTypeMaster::active()->orderBy('exam_type_name')->pluck('exam_type_name', 'pk');
        $terms = TermMaster::active()->orderBy('term_name')->pluck('term_name', 'pk');

        $data_course_id = get_Role_by_course();
        // Only current/upcoming courses (end_date not passed yet), matching the Programme page's "Active" filter.
        $coursesQuery = CourseMaster::where('end_date', '>=', Carbon::now()->format('Y-m-d'));
        if (!empty($data_course_id)) {
            $coursesQuery->whereIn('pk', $data_course_id);
        }
        $courses = $coursesQuery->orderBy('course_name')->pluck('couse_short_name', 'pk');

        $phases = ExaminationDrive::PHASES;
        $statuses = ExaminationDrive::STATUS_LABELS;

        return $dataTable->render('admin.examination_drive.index', compact('examinationTypes', 'terms', 'courses', 'phases', 'statuses'));
    }

    /**
     * Course filter options for the Active/Archived tabs (mirrors Programme's endpoint,
     * but keyed by couse_short_name to match this page's dropdown labels).
     */
    public function getCoursesByStatus(Request $request)
    {
        $status = $request->input('status', 'active');
        $data_course_id = get_Role_by_course();
        $currentDate = Carbon::now()->format('Y-m-d');

        $query = $status === 'archive'
            ? CourseMaster::where('end_date', '<', $currentDate)
            : CourseMaster::where('end_date', '>=', $currentDate);

        if (!empty($data_course_id)) {
            $query->whereIn('pk', $data_course_id);
        }

        $courses = $query->orderBy('course_name')->pluck('couse_short_name', 'pk');

        return response()->json(['success' => true, 'courses' => $courses]);
    }

    public function store(Request $request)
    {
        $id = $request->id ? decrypt($request->id) : null;

        $validated = $request->validate([
            'examination_type_master_pk' => 'required|exists:examination_type_master,pk',
            'term_master_pk' => 'required|exists:term_master,pk',
            'course_master_pk' => 'required|exists:course_master,pk',
            'phase' => 'required|in:' . implode(',', ExaminationDrive::PHASES),
            'academic_session' => 'required|digits:4|integer|min:2000|max:2100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:' . implode(',', array_keys(ExaminationDrive::STATUS_LABELS)),
        ]);

        $drive = $id ? ExaminationDrive::findOrFail($id) : new ExaminationDrive();
        $drive->fill($validated);

        if (!$id) {
            $drive->created_by = Auth::id();
            $drive->created_date = now();
        }
        $drive->modified_date = now();
        $drive->save();

        $message = $id ? 'Examination drive updated successfully.' : 'Examination drive created successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.examination.drive.index')->with('success', $message);
    }

    /** Same role scope + Active/Archived + Course filters the grid applies, reused by every export format. */
    private function filteredDrivesQuery(Request $request)
    {
        $query = ExaminationDrive::with(['examinationType', 'term', 'course']);

        // Mirror the grid: an export must never widen what the user can see.
        $data_course_id = get_Role_by_course();
        if (!empty($data_course_id)) {
            $query->whereIn('course_master_pk', $data_course_id);
        }

        $statusFilter = $request->query('status_filter');
        $currentDate = Carbon::now()->format('Y-m-d');

        if ($statusFilter === 'archive') {
            $query->where('end_date', '<', $currentDate);
        } elseif ($statusFilter === 'active' || !$statusFilter) {
            $query->where('end_date', '>=', $currentDate);
        }

        $courseFilter = $request->query('course_filter');
        if (!empty($courseFilter)) {
            $query->where('course_master_pk', $courseFilter);
        }

        return $query->orderByDesc('id');
    }

    public function export(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'pdf'));
        abort_unless(in_array($format, ['print', 'pdf', 'excel'], true), 404);

        $rows = $this->filteredDrivesQuery($request)->get();
        $exportDate = now()->format('d-m-Y h:i A');
        $stamp = now()->format('YmdHis');

        if ($format === 'print') {
            return view('admin.examination_drive.export_print', compact('rows', 'exportDate'));
        }

        if ($format === 'excel') {
            return Excel::download(new ExaminationDriveExport($rows), 'ExaminationDrive_' . $stamp . '.xlsx');
        }

        return Pdf::loadView('admin.examination_drive.export_pdf', compact('rows', 'exportDate'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
            ])
            ->download('ExaminationDrive_' . $stamp . '.pdf');
    }

    public function destroy($id)
    {
        $drive = ExaminationDrive::findOrFail(decrypt($id));
        $drive->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Examination drive deleted successfully.']);
        }

        return redirect()->route('master.examination.drive.index')->with('success', 'Examination drive deleted successfully.');
    }
}
