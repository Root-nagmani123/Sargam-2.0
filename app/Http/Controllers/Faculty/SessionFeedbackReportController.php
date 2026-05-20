<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Controller;
use App\Services\FacultyFeedbackReportService;
use App\Support\FeedbackReportRouteRegistry;
use Illuminate\Http\Request;

class SessionFeedbackReportController extends Controller
{
    public function __construct(
        private FacultyFeedbackReportService $reportService
    ) {
        $this->middleware('auth');
        $this->middleware(\App\Http\Middleware\EnsureFacultyPortalUser::class);
    }

    private function feedback(): FeedbackController
    {
        return app(FeedbackController::class);
    }

    public function commentsIndex(Request $request)
    {
        $facultyPk = (int) $request->attributes->get('faculty_report_faculty_pk');
        $courseType = $request->input('course_type', 'current');
        if (! in_array($courseType, ['current', 'archived'], true)) {
            $courseType = 'current';
        }

        $programs = $this->reportService->getPrograms($facultyPk, $courseType);
        $currentProgram = $courseType === 'current'
            ? $this->reportService->getDefaultProgramId($facultyPk)
            : $this->defaultArchivedProgramId($facultyPk);

        return view('admin.feedback.faculty_view', [
            'programs' => $programs,
            'currentProgram' => $currentProgram,
            'feedbackReportRoutes' => FeedbackReportRouteRegistry::faculty(),
            'isFacultySessionFeedbackReport' => true,
        ]);
    }

    public function details(Request $request)
    {
        return $this->feedback()->pendingStudents();
    }

    public function detailsGrouped(Request $request)
    {
        return $this->feedback()->pendingStudentsGroupedData($request);
    }

    public function detailsSessionsByCourse(Request $request)
    {
        return $this->feedback()->getSessionsByCourse($request);
    }

    public function detailsExportPdf(Request $request)
    {
        return $this->feedback()->exportPendingStudentsPDF($request);
    }

    public function detailsExportExcel(Request $request)
    {
        return $this->feedback()->exportPendingStudentsExcel($request);
    }

    public function detailsExportExcelDetailed(Request $request)
    {
        return $this->feedback()->exportPendingStudentsExcelDetailed($request);
    }

    public function detailsPrint(Request $request)
    {
        return $this->feedback()->printPendingStudents($request);
    }

    public function comments(Request $request)
    {
        return $this->feedback()->facultyView($request);
    }

    public function commentsExport(Request $request)
    {
        return $this->feedback()->exportFacultyFeedback($request);
    }

    public function commentsPrint(Request $request)
    {
        return $this->feedback()->printFacultyFeedback($request);
    }

    public function commentsSuggestions(Request $request)
    {
        return $this->feedback()->getFacultySuggestions($request);
    }

    public function average(Request $request)
    {
        return $this->feedback()->showFacultyAverage($request);
    }

    public function averageExportExcel(Request $request)
    {
        return $this->feedback()->exportExcel($request);
    }

    public function averageExportPdf(Request $request)
    {
        return $this->feedback()->exportPdf($request);
    }

    public function averagePrint(Request $request)
    {
        return $this->feedback()->printFacultyAverage($request);
    }

    public function database(Request $request)
    {
        return $this->feedback()->database($request);
    }

    public function databaseCourses(Request $request)
    {
        return $this->feedback()->getDatabaseCourses($request);
    }

    public function databaseData(Request $request)
    {
        return $this->feedback()->getDatabaseData($request);
    }

    public function databasePrint(Request $request)
    {
        return $this->feedback()->printFeedbackDatabase($request);
    }

    public function databaseExportPdf(Request $request)
    {
        return $this->feedback()->exportFeedbackDatabasePdf($request);
    }

    public function databaseExportExcel(Request $request)
    {
        return $this->feedback()->exportFeedbackDatabaseExcel($request);
    }

    private function defaultArchivedProgramId(int $facultyPk): ?int
    {
        $ids = $this->reportService->getAccessibleCourseIds($facultyPk);

        if ($ids->isEmpty()) {
            return null;
        }

        return \App\Models\CourseMaster::query()
            ->whereIn('pk', $ids)
            ->where(function ($q) {
                $q->where('active_inactive', 0)
                    ->orWhereDate('end_date', '<', now());
            })
            ->orderByDesc('end_date')
            ->value('pk');
    }
}
