<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Medical Exemptions Today" card on the Doctor's Home dashboard: OTs whose
 * medical exemption covers today. Count and link: UserController::dashboard().
 */
return new class extends Migration
{
    private const KEY = 'medical_exemptions_today';

    public function up(): void
    {
        $cardId = DB::table('dashboard_cards')->where('key', self::KEY)->value('id')
            ?? DB::table('dashboard_cards')->insertGetId([
                'key' => self::KEY,
                'label' => 'Medical Exemptions Today',
                'icon' => 'medical_services',
                'color_class' => 'stat-icon-rose',
                'sort_order' => (int) DB::table('dashboard_cards')->max('sort_order') + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $roleId = DB::table('roles')->where('name', 'Doctor')->value('id');
        $homeId = DB::table('sidebar_categories')->where('slug', 'home')->whereNull('deleted_at')->value('id');
        if ($roleId && $homeId) {
            DB::table('role_dashboard_cards')->insertOrIgnore([
                'role_id' => $roleId,
                'dashboard_card_id' => $cardId,
                'sidebar_category_id' => $homeId,
            ]);
        }
    }

    /** Forward-fix only, like the other dashboard-card migrations (PR #334 F-027 / F-036). */
    public function down(): void
    {
        //
    }
};
