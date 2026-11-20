<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Super Admin's Setup dashboard: the Setup admin cards (Estate, Centcom, FC, Course
 * Repository), so the Setup tab opens on a dashboard and its pages carry the Dashboard
 * Preview. The pages these cards open show Super Admin every row. Centcom uses "All Open
 * Complaints": the Centcom Admin cards count only the viewer's own assigned queue.
 * Count and link: UserController::dashboardCardDefinitions(). The cards already exist;
 * a key missing here is skipped, never created.
 */
return new class extends Migration
{
    private const CARDS = [
        'estate_pending_requests', 'estate_put_in_hac', 'estate_hac_pending', 'estate_vacant_houses',
        'estate_pending_meter_readings', 'centcom_all_open_complaints',
        'fc_registration_applications', 'fc_exemption_applications', 'course_repository_folders',
    ];

    public function up(): void
    {
        // Cards are placed per tab: needs the column 2026_11_13_000000_add_module_to_role_dashboard_cards adds.
        if (! Schema::hasColumn('role_dashboard_cards', 'sidebar_category_id')) {
            throw new RuntimeException('Run 2026_11_13_000000_add_module_to_role_dashboard_cards first: role_dashboard_cards has no sidebar_category_id column yet.');
        }

        $roleId = DB::table('roles')->where('name', 'Super Admin')->value('id');
        $setupId = DB::table('sidebar_categories')->where('slug', 'setup')->when(Schema::hasColumn('sidebar_categories', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))->value('id');
        if (! $roleId || ! $setupId) {
            return;
        }

        $cardIds = DB::table('dashboard_cards')->whereIn('key', self::CARDS)->pluck('id')->all();

        DB::table('role_dashboard_cards')->insertOrIgnore(array_map(fn ($cardId) => [
            'role_id' => $roleId,
            'dashboard_card_id' => $cardId,
            'sidebar_category_id' => $setupId,
        ], $cardIds));
    }

    /** Forward-fix only, like the other dashboard-card migrations (PR #334 F-027 / F-036). */
    public function down(): void
    {
        //
    }
};
