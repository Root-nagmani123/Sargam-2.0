<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The six Security cards on the Home dashboard of Security Approver II and III.
 * 2026_11_19_000000_align_role_dashboard_cards left them out because their counts only
 * recognised 'Security Card' / 'Admin Security'; hasRole() now reads those as these roles,
 * so each card counts the New Request tab at the login's level
 * (UserController::dashboardCardDefinitions()). The cards already exist; a missing key,
 * role or tab is skipped, never created.
 */
return new class extends Migration
{
    private const CARDS = [
        'pending_permanent_id', 'pending_contractual_id', 'duplicate_permanent_id',
        'duplicate_contractual_id', 'requested_family_id', 'requested_vehicle_pass',
    ];

    public function up(): void
    {
        // Cards are placed per tab: needs the column 2026_11_13_000000_add_module_to_role_dashboard_cards adds.
        if (! Schema::hasColumn('role_dashboard_cards', 'sidebar_category_id')) {
            throw new RuntimeException('Run 2026_11_13_000000_add_module_to_role_dashboard_cards first: role_dashboard_cards has no sidebar_category_id column yet.');
        }

        $homeId = DB::table('sidebar_categories')->where('slug', 'home')->when(Schema::hasColumn('sidebar_categories', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))->value('id');
        if (! $homeId) {
            return;
        }

        $roleIds = DB::table('roles')->whereIn('name', ['Security Approver II', 'Security Approver III'])->pluck('id');
        $cardIds = DB::table('dashboard_cards')->whereIn('key', self::CARDS)->pluck('id');

        $rows = [];
        foreach ($roleIds as $roleId) {
            foreach ($cardIds as $cardId) {
                $rows[] = ['role_id' => $roleId, 'dashboard_card_id' => $cardId, 'sidebar_category_id' => $homeId];
            }
        }

        // insertOrIgnore: the primary key is (role, card, tab), so a row an admin already set stays.
        DB::table('role_dashboard_cards')->insertOrIgnore($rows);
    }

    /** Forward-fix only, like the other dashboard-card migrations (PR #334 F-027 / F-036). */
    public function down(): void
    {
        //
    }
};
