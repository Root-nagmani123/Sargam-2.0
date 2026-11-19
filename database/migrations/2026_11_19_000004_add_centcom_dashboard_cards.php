<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Centcom cards on Centcom Admin's Setup tab (where its Centcom menus are).
 * Count and link: UserController::dashboard().
 */
return new class extends Migration
{
    /** key => [label, icon, colour] */
    private const CARDS = [
        'centcom_open_complaints' => ['Open Complaints', 'support_agent', 'stat-icon-rose'],
        'centcom_reported_complaints' => ['Reported (Not Started)', 'report', 'stat-icon-amber'],
    ];

    public function up(): void
    {
        $roleId = DB::table('roles')->where('name', 'Centcom Admin')->value('id');
        $setupId = DB::table('sidebar_categories')->where('slug', 'setup')->whereNull('deleted_at')->value('id');

        foreach (self::CARDS as $key => [$label, $icon, $colour]) {
            $cardId = DB::table('dashboard_cards')->where('key', $key)->value('id')
                ?? DB::table('dashboard_cards')->insertGetId([
                    'key' => $key,
                    'label' => $label,
                    'icon' => $icon,
                    'color_class' => $colour,
                    'sort_order' => (int) DB::table('dashboard_cards')->max('sort_order') + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($roleId && $setupId) {
                DB::table('role_dashboard_cards')->insertOrIgnore([
                    'role_id' => $roleId,
                    'dashboard_card_id' => $cardId,
                    'sidebar_category_id' => $setupId,
                ]);
            }
        }
    }

    /** Forward-fix only, like the other dashboard-card migrations (PR #334 F-027 / F-036). */
    public function down(): void
    {
        //
    }
};
