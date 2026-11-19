<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Employee self-service cards on the Employee role's Home dashboard: the signed-in user's
 * own pending ID card / estate requests, unpaid mess bills and open issues.
 * Count and link: UserController::dashboard().
 */
return new class extends Migration
{
    /** key => [label, icon, colour] */
    private const CARDS = [
        'my_idcard_requests_pending' => ['My Pending ID Card Requests', 'badge', 'stat-icon-blue'],
        'my_estate_requests_pending' => ['My Pending Estate Requests', 'home_work', 'stat-icon-amber'],
        'my_mess_bills_unpaid' => ['My Unpaid Mess Bills (This Month)', 'receipt_long', 'stat-icon-rose'],
        'my_open_issues' => ['My Open Issues', 'support_agent', 'stat-icon-navy'],
    ];

    public function up(): void
    {
        $roleId = DB::table('roles')->where('name', 'Employee')->value('id');
        $homeId = DB::table('sidebar_categories')->where('slug', 'home')->whereNull('deleted_at')->value('id');

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

            if ($roleId && $homeId) {
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
