<?php

namespace App\Support;

final class FeedbackReportRouteRegistry
{
    /**
     * @return array<string, string>
     */
    public static function admin(): array
    {
        return [
            'details' => route('admin.feedback.feedback_details'),
            'details_export' => route('admin.feedback.feedback_details.export'),
            'comments' => route('admin.feedback.faculty_view'),
            'comments_submit' => route('admin.feedback.faculty_view'),
            'comments_export' => route('admin.feedback.faculty_view.export'),
            'comments_print' => route('admin.feedback.faculty_view.print'),
            'comments_suggestions' => route('feedback.faculty_suggestions'),
            'average' => route('feedback.average'),
            'average_export_excel' => route('feedback.average.export.excel'),
            'average_export_pdf' => route('feedback.average.export.pdf'),
            'average_print' => route('feedback.average.print'),
            'database' => route('admin.feedback.database'),
            'database_data' => route('admin.feedback.database.data'),
            'database_courses' => route('admin.feedback.database.courses'),
            'database_print' => route('admin.feedback.database.print'),
            'database_export_pdf' => route('admin.feedback.database.export.pdf'),
            'database_export_excel' => route('admin.feedback.database.export.excel'),
            'pending_grouped' => route('admin.feedback.pending.grouped'),
            'pending_sessions_by_course' => route('admin.get.sessions.by.course'),
            'pending_export_pdf' => route('admin.feedback.export.pdf'),
            'pending_export_excel' => route('admin.feedback.export.excel'),
            'pending_export_excel_detailed' => route('admin.feedback.export.excel.detailed'),
            'pending_print' => route('admin.feedback.print'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function faculty(): array
    {
        return [
            'details' => route('faculty.session_feedback.details'),
            'details_export' => route('faculty.session_feedback.details.export.pdf'),
            'comments' => route('faculty.session_feedback.comments'),
            'comments_submit' => route('faculty.session_feedback.comments.submit'),
            'comments_export' => route('faculty.session_feedback.comments.export'),
            'comments_print' => route('faculty.session_feedback.comments.print'),
            'comments_suggestions' => route('faculty.session_feedback.comments.suggestions'),
            'average' => route('faculty.session_feedback.average'),
            'average_export_excel' => route('faculty.session_feedback.average.export.excel'),
            'average_export_pdf' => route('faculty.session_feedback.average.export.pdf'),
            'average_print' => route('faculty.session_feedback.average.print'),
            'database' => route('faculty.session_feedback.database'),
            'database_data' => route('faculty.session_feedback.database.data'),
            'database_courses' => route('faculty.session_feedback.database.courses'),
            'database_print' => route('faculty.session_feedback.database.print'),
            'database_export_pdf' => route('faculty.session_feedback.database.export.pdf'),
            'database_export_excel' => route('faculty.session_feedback.database.export.excel'),
            'pending_grouped' => route('faculty.session_feedback.details.grouped'),
            'pending_sessions_by_course' => route('faculty.session_feedback.details.sessions_by_course'),
            'pending_export_pdf' => route('faculty.session_feedback.details.export.pdf'),
            'pending_export_excel' => route('faculty.session_feedback.details.export.excel'),
            'pending_export_excel_detailed' => route('faculty.session_feedback.details.export.excel_detailed'),
            'pending_print' => route('faculty.session_feedback.details.print'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function forRequest(): array
    {
        return request()->attributes->get('is_faculty_feedback_report')
            ? self::faculty()
            : self::admin();
    }

    /**
     * @return array<string, string>
     */
    public static function pendingForRequest(): array
    {
        $routes = self::forRequest();

        return [
            'grouped' => $routes['pending_grouped'],
            'sessions_by_course' => $routes['pending_sessions_by_course'],
            'export_pdf' => $routes['pending_export_pdf'],
            'export_excel' => $routes['pending_export_excel'],
            'export_excel_detailed' => $routes['pending_export_excel_detailed'],
            'print' => $routes['pending_print'],
        ];
    }
}
