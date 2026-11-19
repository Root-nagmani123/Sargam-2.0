<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Pending Feedback Students" card on the Home dashboard of PA and TA and RF (both hold the
 * Pending Feedback Students menu). Count and link: UserController::dashboard().
 */
return new class extends Migration
{
    private const KEY = 'pending_feedback_students';

    public function up(): void
    {
        $cardId = DB::table('dashboard_cards')->where('key', self::KEY)->value('id')
            ?? DB::table('dashboard_cards')->insertGetId([
                'key' => self::KEY,
                'label' => 'Pending Feedback Students',
                'icon' => 'rate_review',
                'color_class' => 'stat-icon-rose',
                'sort_order' => (int) DB::table('dashboard_cards')->max('sort_order') + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $homeId = DB::table('sidebar_categories')->where('slug', 'home')->whereNull('deleted_at')->value('id');
        foreach (DB::table('roles')->whereIn('name', ['PA', 'TA and RF'])->pluck('id') as $roleId) {
            if ($homeId) {
                DB::table('role_dashboard_cards')->insertOrIgnore([
                    'role_id' => $roleId,
                    'dashboard_card_id' => $cardId,
                    'sidebar_category_id' => $homeId,
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
