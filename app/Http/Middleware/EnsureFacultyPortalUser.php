<?php

namespace App\Http\Middleware;

use App\Services\FacultyFeedbackReportService;
use App\Support\FeedbackReportRouteRegistry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureFacultyPortalUser
{
    public function __construct(
        private FacultyFeedbackReportService $reportService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->reportService->assertFacultyRole();

        $facultyPk = $this->reportService->resolveFacultyPk();
        if (! $facultyPk) {
            abort(403, 'Your account is not linked to a faculty profile.');
        }

        $courseIds = $this->reportService->getAccessibleCourseIds($facultyPk)->all();

        $request->attributes->set('is_faculty_feedback_report', true);
        $request->attributes->set('faculty_report_faculty_pk', $facultyPk);
        $request->attributes->set('faculty_report_course_ids', $courseIds);

        $routes = FeedbackReportRouteRegistry::faculty();

        View::share('isFacultySessionFeedbackReport', true);
        View::share('feedbackReportRoutes', $routes);
        View::share('fr', $routes);
        View::share('hidePendingFeedbackAdminLink', true);

        return $next($request);
    }
}
