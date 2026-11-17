<?php

use App\Services\SidebarMenu\MenuService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 31 sidebar menus had no icon and rendered SidebarController::menuIcon()'s
 * neutral 'label' fallback, so they looked unlike every other entry. Each gets
 * a Material Symbols Rounded icon in line with its section's neighbours
 * (Discipline -> gavel, Exemption -> medical_services, Feedback -> rate_review…).
 *
 * Rows are matched on route + name (ids differ between environments), and only
 * while the icon is still unset or the 'label' placeholder, so an icon an admin
 * has picked in the Menus screen is never overwritten.
 */
return new class extends Migration
{
    /** [route, name, icon] */
    private const ICONS = [
        // Discipline
        ['admin/memo-notice-management/user', 'memo notice action', 'gavel'],
        ['memo/discipline', 'Descipline Memo action', 'gavel'],
        // FC Forms > Fc-reg Admin
        ['fc-reg/admin/forms', 'Form Management', 'dynamic_form'],
        ['fc-reg/admin/activities', 'Activities', 'local_activity'],
        ['fc-reg/admin/activities/status', 'Activity Status', 'pending_actions'],
        ['fc-reg/admin/activities/reports/summary', 'Activity Report', 'summarize'],
        ['fc-reg/admin/activities/medical', 'Medical Activity', 'medical_services'],
        ['fc-reg/admin/activity-setup/departments', 'Department Setup', 'apartment'],
        ['fc-reg/admin/activity-setup/masters', 'Activity Master', 'list_alt'],
        ['admin/travel', 'Travel Plan Report', 'flight_takeoff'],
        ['fc-reg/admin/sample-documents', 'Sample Document Master', 'description'],
        ['admin/reports/descriptive-roll', 'Descriptive Report', 'assignment'],
        ['admin/reports/health-risk', 'Fc Health Report', 'health_and_safety'],
        ['admin/reports/descriptive-data', 'Descriptive Report (excel)', 'table_view'],
        ['admin/reports/special-assistant', 'Special Assistant Report', 'accessible'],
        ['admin/reports/vision-statement', 'Vision Statement Report', 'visibility'],
        ['admin/reports/pre-medical-history', 'Pre Medical report', 'medical_information'],
        ['admin/reports/bank-report', 'Fc Bank Details', 'account_balance'],
        // FC Forms > General
        ['fc-reg/admin/sms', 'Bulk SMS/Email', 'forward_to_inbox'],
        // Feedback
        ['feedback/student-feedback', 'My Session feedback', 'rate_review'],
        ['/faculty-internal-feedback', 'My Session Feedback-F', 'rate_review'],
        // General
        ['leave/apply', 'Apply Leave', 'event_available'],
        ['leave/my-leave', 'My Leave', 'event_note'],
        ['/faculty-leave-approval', 'Leave Approval', 'approval'],
        ['issue-reports', 'Report Issues', 'bug_report'],
        // HR Management > Faculty
        ['admin/appellation', 'Appellation', 'title'],
        // Time Table
        ['attendance', 'My Attendance', 'fact_check'],
        ['ot-mdo-escrot-exemption-view', 'MDO/ Escort Duty', 'supervisor_account'],
        ['admin/pt-exemption-master', 'PT Exemption Master', 'fitness_center'],
        ['admin/stationed-leave-master', 'Stationed Leave Master', 'event_note'],
        // Training Setup > Exemption
        ['/medical-exemption-report', 'Medical Exemption Report', 'medical_services'],
    ];

    public function up(): void
    {
        foreach (self::ICONS as [$route, $name, $icon]) {
            DB::table('menus')
                ->where('route', $route)
                ->where('name', $name)
                ->where(fn ($q) => $q->whereNull('icon')->orWhere('icon', '')->orWhere('icon', 'label'))
                ->update(['icon' => $icon, 'updated_at' => now()]);
        }

        $this->clearSidebarCaches();
    }

    /** Only rows still carrying this migration's icon go back to unset (the 'label' fallback). */
    public function down(): void
    {
        foreach (self::ICONS as [$route, $name, $icon]) {
            DB::table('menus')
                ->where('route', $route)
                ->where('name', $name)
                ->where('icon', $icon)
                ->update(['icon' => null, 'updated_at' => now()]);
        }

        $this->clearSidebarCaches();
    }

    private function clearSidebarCaches(): void
    {
        Cache::forget('sidebar_nav_route_index');
        MenuService::clearStructureCache();
    }
};
