<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Role-wise dashboard alignment (Sargam-Role-Dashboard-Alignment, 19-11-2026):
 * which existing card each role sees on which header tab.
 *
 * Only cards that already have a $cardDefinitions entry. Left out on purpose:
 *  - the six security cards for Security Approver II / III, and requested_family_id /
 *    requested_vehicle_pass for Super Admin: their counts only recognise roles named
 *    "Security Card" / "Admin Security", which no role holds, so they would always read 0.
 *  - cards that need new count code ([NEW] in the document) and the protocol cards
 *    (their definitions are on main, not this branch).
 *
 * A role, tab or card missing from an environment is skipped, not created.
 */
return new class extends Migration
{
    private const WIDGETS = ['widget_notices', 'widget_admin_summary', 'widget_todays_birthdays', 'widget_calendar', 'widget_campus_tweets'];

    private const ACADEMY_STATS = ['total_active_courses', 'upcoming_courses', 'upcoming_events', 'total_inhouse_faculty', 'total_guest_faculty', 'widget_admin_summary'];

    /** role => tab slug => card keys */
    private function additions(): array
    {
        return [
            'Officer Trainee' => ['home' => ['medical_exception', 'ot_mdo_escort']],
            'Employee' => ['home' => ['widget_admin_summary', 'widget_campus_tweets']],
            'Faculty' => ['home' => ['widget_admin_summary']],
            'Director' => ['home' => ['pending_id_approval1', 'pending_dup_id_approval1', 'widget_campus_tweets']],
            'Training-Induction' => ['home' => ['upcoming_courses', 'upcoming_events', 'total_guest_faculty', 'total_students', 'widget_admin_summary']],
            'Training MCTP Admin' => ['home' => self::ACADEMY_STATS],
            'Training IST' => ['home' => self::ACADEMY_STATS],
            'PA' => ['home' => ['total_inhouse_faculty', 'total_guest_faculty', 'widget_admin_summary']],
            'TA and RF' => ['home' => ['total_inhouse_faculty', 'total_guest_faculty', 'widget_campus_tweets']],
            'Mess Admin' => ['home' => ['widget_admin_summary']],
            'Mess Billing' => ['home' => ['widget_campus_tweets']],
            'Estate Admin' => ['home' => ['widget_notices']],
            'FC-Sec-Audit' => ['home' => self::WIDGETS],
            'FC Reports Viewer' => ['home' => self::WIDGETS],
            'Super Admin' => ['home' => array_merge(self::WIDGETS, [
                'total_active_courses', 'upcoming_courses', 'total_students',
                'total_inhouse_faculty', 'total_guest_faculty', 'pending_id_approval1',
            ])],
        ];
    }

    /** role => tab slug => card keys */
    private function removals(): array
    {
        return [
            // Gated off for trainees (the academy scope refuses them).
            'Officer Trainee' => ['home' => ['academic_timetable']],
            // Academy-admin numbers; Employee menus are self-service.
            'Employee' => ['home' => ['total_active_courses', 'upcoming_courses', 'total_inhouse_faculty']],
            'Faculty' => ['academics' => array_merge(
                // Security counts
                ['pending_permanent_id', 'pending_contractual_id', 'duplicate_permanent_id', 'duplicate_contractual_id',
                    'requested_family_id', 'requested_vehicle_pass', 'pending_id_approval1', 'pending_dup_id_approval1'],
                // OT cards
                ['my_groups', 'discipline_marks_deducted', 'pending_feedback', 'medical_exception', 'ot_mdo_escort'],
                // Already on Faculty's Home
                ['total_sessions', 'total_feedback', 'academic_timetable', 'my_counsellees', 'house_wise_details',
                    'whos_who', 'widget_house_performance'],
                self::WIDGETS,
            )],
            // Faculty / OT cards: hidden from a Super Admin login by the gate anyway.
            'Super Admin' => [
                'setup' => ['total_feedback', 'my_timetable', 'my_counsellees'],
                'communications' => ['total_sessions'],
                'academics' => ['academic_timetable', 'house_wise_details', 'whos_who', 'pending_feedback', 'my_groups'],
            ],
        ];
    }

    public function up(): void
    {
        $roles = DB::table('roles')->pluck('id', 'name');
        $tabs = DB::table('sidebar_categories')->whereNull('deleted_at')->pluck('id', 'slug');
        $cards = DB::table('dashboard_cards')->pluck('id', 'key');

        $rows = function (array $plan) use ($roles, $tabs, $cards): array {
            $out = [];
            foreach ($plan as $role => $byTab) {
                foreach ($byTab as $tab => $keys) {
                    foreach ($keys as $key) {
                        if (isset($roles[$role], $tabs[$tab], $cards[$key])) {
                            $out[] = ['role_id' => $roles[$role], 'dashboard_card_id' => $cards[$key], 'sidebar_category_id' => $tabs[$tab]];
                        }
                    }
                }
            }

            return $out;
        };

        DB::transaction(function () use ($rows) {
            // insertOrIgnore: the primary key is (role, card, tab), so a row an admin
            // already switched on stays as it is.
            DB::table('role_dashboard_cards')->insertOrIgnore($rows($this->additions()));

            foreach ($rows($this->removals()) as $row) {
                DB::table('role_dashboard_cards')->where($row)->delete();
            }
        });
    }

    /**
     * Forward-fix only, like the other dashboard-card migrations (PR #334 F-027 / F-036):
     * up() cannot tell which of these rows an admin set by hand, so rollback changes nothing.
     */
    public function down(): void
    {
        //
    }
};
