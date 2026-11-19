<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mess cards on the Home dashboard of Mess Admin and Mess Billing. Count and link:
 * UserController::dashboard(). Pending purchase orders go to Mess Admin only: only Mess
 * Admin (or Super Admin) may approve one (PurchaseOrderController::canApprovePurchaseOrders).
 */
return new class extends Migration
{
    /** key => [label, icon, colour, roles] */
    private const CARDS = [
        'mess_pending_purchase_orders' => ['Purchase Orders Pending Approval', 'pending_actions', 'stat-icon-amber', ['Mess Admin']],
        'mess_low_stock_items' => ['Low Stock Items', 'inventory_2', 'stat-icon-rose', ['Mess Admin', 'Mess Billing']],
        'mess_unpaid_bills' => ['Unpaid Mess Bills (This Month)', 'receipt_long', 'stat-icon-navy', ['Mess Admin', 'Mess Billing']],
        'mess_items_sold_today' => ['Items Sold Today', 'point_of_sale', 'stat-icon-green', ['Mess Admin', 'Mess Billing']],
    ];

    public function up(): void
    {
        $homeId = DB::table('sidebar_categories')->where('slug', 'home')->whereNull('deleted_at')->value('id');
        $roles = DB::table('roles')->pluck('id', 'name');

        foreach (self::CARDS as $key => [$label, $icon, $colour, $roleNames]) {
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

            foreach ($roleNames as $role) {
                if ($homeId && isset($roles[$role])) {
                    DB::table('role_dashboard_cards')->insertOrIgnore([
                        'role_id' => $roles[$role],
                        'dashboard_card_id' => $cardId,
                        'sidebar_category_id' => $homeId,
                    ]);
                }
            }
        }
    }

    /** Forward-fix only, like the other dashboard-card migrations (PR #334 F-027 / F-036). */
    public function down(): void
    {
        //
    }
};
