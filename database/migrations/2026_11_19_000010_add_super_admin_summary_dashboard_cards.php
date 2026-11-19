<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Super Admin's Home: an Estate / Mess / Centcom summary. The estate and mess cards already
 * exist (their pages show Super Admin everything); "All Open Complaints" is new because the
 * Centcom Admin card counts only the viewer's own assigned queue.
 * Count and link: UserController::dashboard().
 */
return new class extends Migration
{
    private const EXISTING = [
        'estate_pending_requests', 'estate_hac_pending', 'estate_vacant_houses',
        'mess_pending_purchase_orders', 'mess_low_stock_items',
    ];

    public function up(): void
    {
        $cardIds = DB::table('dashboard_cards')->whereIn('key', self::EXISTING)->pluck('id')->all();

        $cardIds[] = DB::table('dashboard_cards')->where('key', 'centcom_all_open_complaints')->value('id')
            ?? DB::table('dashboard_cards')->insertGetId([
                'key' => 'centcom_all_open_complaints',
                'label' => 'All Open Complaints',
                'icon' => 'support_agent',
                'color_class' => 'stat-icon-rose',
                'sort_order' => (int) DB::table('dashboard_cards')->max('sort_order') + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $roleId = DB::table('roles')->where('name', 'Super Admin')->value('id');
        $homeId = DB::table('sidebar_categories')->where('slug', 'home')->whereNull('deleted_at')->value('id');
        if (! $roleId || ! $homeId) {
            return;
        }

        DB::table('role_dashboard_cards')->insertOrIgnore(array_map(fn ($cardId) => [
            'role_id' => $roleId,
            'dashboard_card_id' => $cardId,
            'sidebar_category_id' => $homeId,
        ], $cardIds));
    }

    /** Forward-fix only, like the other dashboard-card migrations (PR #334 F-027 / F-036). */
    public function down(): void
    {
        //
    }
};
