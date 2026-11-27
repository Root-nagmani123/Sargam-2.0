<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * The 23 dashboard cards PR #356 adds, and their role / header-tab assignments.
 *
 * A card renders only when a dashboard_cards row with its key exists and is assigned to one of
 * the viewer's roles on that tab; the count and link live in UserController::dashboardCardDefinitions().
 *
 * Insert-only (PR #356 F-008): a card whose key exists is left as it is (an admin may have
 * relabelled or reordered it), an existing assignment stays, and a role or tab missing from the
 * environment is skipped and reported (see $skipped). Nothing is updated or deleted, unlike the removed
 * align migration (F-003).
 * Tests seed through this same up() (tests/Feature/Concerns/SeedsModuleDashboardCards.php).
 */
return new class extends Migration
{
    /** key => [label, icon, colour, [[role, tab slug], ...]] */
    public const CARDS = [
        'medical_exemptions_today' => ['Medical Exemptions Today', 'medical_services', 'stat-icon-rose', [['Doctor', 'home'], ['Training-Induction', 'home']]],
        'mess_pending_purchase_orders' => ['Purchase Orders Pending Approval', 'pending_actions', 'stat-icon-amber', [['Mess Admin', 'home'], ['Super Admin', 'home']]],
        'mess_low_stock_items' => ['Low Stock Items', 'inventory_2', 'stat-icon-rose', [['Mess Admin', 'home'], ['Mess Billing', 'home'], ['Super Admin', 'home']]],
        'mess_unpaid_bills' => ['Unpaid Mess Bills (This Month)', 'receipt_long', 'stat-icon-navy', [['Mess Admin', 'home'], ['Mess Billing', 'home']]],
        'mess_items_sold_today' => ['Items Sold Today', 'point_of_sale', 'stat-icon-green', [['Mess Admin', 'home'], ['Mess Billing', 'home']]],
        'estate_pending_requests' => ['Pending Estate Requests', 'home_work', 'stat-icon-amber', [['Estate Admin', 'setup'], ['Super Admin', 'home'], ['Super Admin', 'setup']]],
        'estate_put_in_hac' => ['Requests to Put in HAC', 'move_to_inbox', 'stat-icon-blue', [['Estate Admin', 'setup'], ['Super Admin', 'setup']]],
        'estate_hac_pending' => ['Pending in HAC', 'pending_actions', 'stat-icon-rose', [['Estate Admin', 'setup'], ['Estate HAC', 'setup'], ['HAC Person', 'home'], ['Super Admin', 'home'], ['Super Admin', 'setup']]],
        'estate_vacant_houses' => ['Vacant Houses', 'night_shelter', 'stat-icon-green', [['Estate Admin', 'setup'], ['Super Admin', 'home'], ['Super Admin', 'setup']]],
        'estate_pending_meter_readings' => ['Pending Meter Readings (This Month)', 'electric_meter', 'stat-icon-navy', [['Estate Admin', 'setup'], ['Super Admin', 'setup']]],
        'centcom_open_complaints' => ['Open Complaints', 'support_agent', 'stat-icon-rose', [['Centcom Admin', 'setup']]],
        'centcom_reported_complaints' => ['Reported (Not Started)', 'report', 'stat-icon-amber', [['Centcom Admin', 'setup']]],
        'centcom_all_open_complaints' => ['All Open Complaints', 'support_agent', 'stat-icon-rose', [['Super Admin', 'home'], ['Super Admin', 'setup']]],
        'pending_leave_approvals' => ['Pending Leave Approvals', 'event_busy', 'stat-icon-amber', [['Training-Induction', 'home']]],
        'open_discipline_memos' => ['Open Discipline Memos', 'gavel', 'stat-icon-navy', [['Training-Induction', 'academics']]],
        'fc_registration_applications' => ['FC Registration Applications', 'how_to_reg', 'stat-icon-blue', [['FC-Sec-Audit', 'setup'], ['Super Admin', 'setup']]],
        'fc_exemption_applications' => ['FC Exemption Applications', 'assignment_late', 'stat-icon-amber', [['FC-Sec-Audit', 'setup'], ['Super Admin', 'setup']]],
        'course_repository_folders' => ['Course Repository Folders', 'folder_open', 'stat-icon-blue', [['CR-Admin', 'setup'], ['Super Admin', 'setup']]],
        'pending_feedback_students' => ['Pending Feedback Students', 'rate_review', 'stat-icon-rose', [['PA', 'home'], ['TA and RF', 'home']]],
        'my_idcard_requests_pending' => ['My Pending ID Card Requests', 'badge', 'stat-icon-blue', [['Employee', 'home']]],
        'my_estate_requests_pending' => ['My Pending Estate Requests', 'home_work', 'stat-icon-amber', [['Employee', 'home']]],
        'my_mess_bills_unpaid' => ['My Unpaid Mess Bills (This Month)', 'receipt_long', 'stat-icon-rose', [['Employee', 'home']]],
        'my_open_issues' => ['My Open Issues', 'support_agent', 'stat-icon-navy', [['Employee', 'home']]],
    ];

    /** Existing Security cards, assigned (not created) on Home for both approver roles. */
    public const SECURITY_APPROVER_CARDS = [
        'pending_permanent_id', 'pending_contractual_id', 'duplicate_permanent_id',
        'duplicate_contractual_id', 'requested_family_id', 'requested_vehicle_pass',
    ];

    /** "role|card key|tab" for every assignment the last up() skipped because the role or tab is missing here. */
    public array $skipped = [];

    public function up(): void
    {
        if (! Schema::hasColumn('role_dashboard_cards', 'sidebar_category_id')) {
            throw new RuntimeException('Run 2026_11_13_000000_add_module_to_role_dashboard_cards first: assignments are per header tab.');
        }

        $this->skipped = [];
        $roles = DB::table('roles')->pluck('id', 'name');
        $tabs = DB::table('sidebar_categories')->whereNull('deleted_at')->pluck('id', 'slug');
        // The primary key is (role, card, tab), so an existing assignment is ignored.
        $assign = function (int $cardId, string $key, string $role, string $tab) use ($roles, $tabs) {
            if (! isset($roles[$role], $tabs[$tab])) {
                $this->skipped[] = "$role|$key|$tab";

                return;
            }
            DB::table('role_dashboard_cards')->insertOrIgnore([
                'role_id' => $roles[$role], 'dashboard_card_id' => $cardId, 'sidebar_category_id' => $tabs[$tab],
            ]);
        };

        foreach (self::CARDS as $key => [$label, $icon, $colour, $assignments]) {
            $cardId = DB::table('dashboard_cards')->where('key', $key)->value('id')
                ?? DB::table('dashboard_cards')->insertGetId([
                    'key' => $key, 'label' => $label, 'icon' => $icon, 'color_class' => $colour,
                    'sort_order' => (int) DB::table('dashboard_cards')->max('sort_order') + 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            foreach ($assignments as [$role, $tab]) {
                $assign($cardId, $key, $role, $tab);
            }
        }

        foreach (DB::table('dashboard_cards')->whereIn('key', self::SECURITY_APPROVER_CARDS)->pluck('id', 'key') as $key => $cardId) {
            $assign($cardId, $key, 'Security Approver II', 'home');
            $assign($cardId, $key, 'Security Approver III', 'home');
        }

        // A skipped assignment leaves that role without the card while the cards themselves exist, so a
        // count of the card rows cannot show it (PR #356 F-015). Say so where the deploy sees it.
        if ($this->skipped !== []) {
            $message = 'add_module_dashboard_cards: '.count($this->skipped).' assignment(s) skipped, role or tab missing: '.implode(', ', $this->skipped);
            Log::warning($message);
            if (! app()->runningUnitTests() && defined('STDERR')) {
                fwrite(STDERR, $message.PHP_EOL);
            }
        }
    }

    /**
     * Deliberately a no-op: up() cannot tell a row it created from one that predates it (testsargam8
     * got these cards from the removed 2026-11-19 migrations), so deleting here could remove a card an
     * admin set up. Re-running up() is safe.
     *
     * Rolling the release back therefore takes a manual step (PR #356 F-015): revert the code, then
     * delete the CARDS keys from dashboard_cards; their assignments go with them (ON DELETE CASCADE).
     * Without it the previous code shows each of these cards as "00" with no link, because it has no
     * definition for their keys:
     *
     *   DELETE FROM dashboard_cards WHERE `key` IN ('medical_exemptions_today', 'mess_pending_purchase_orders',
     *     'mess_low_stock_items', 'mess_unpaid_bills', 'mess_items_sold_today', 'estate_pending_requests',
     *     'estate_put_in_hac', 'estate_hac_pending', 'estate_vacant_houses', 'estate_pending_meter_readings',
     *     'centcom_open_complaints', 'centcom_reported_complaints', 'centcom_all_open_complaints',
     *     'pending_leave_approvals', 'open_discipline_memos', 'fc_registration_applications',
     *     'fc_exemption_applications', 'course_repository_folders', 'pending_feedback_students',
     *     'my_idcard_requests_pending', 'my_estate_requests_pending', 'my_mess_bills_unpaid', 'my_open_issues');
     */
    public function down(): void
    {
        //
    }
};
