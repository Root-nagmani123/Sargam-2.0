<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Estate cards: Estate Admin's menus all sit on the Setup tab, so its cards go there.
 * "Pending in HAC" also goes to the HAC roles, who see the same queue
 * (EstateHacApprovedDataTable: HAC Person / Estate HAC). Count and link: UserController::dashboard().
 */
return new class extends Migration
{
    /** key => [label, icon, colour, [role => tab slug]] */
    private const CARDS = [
        'estate_pending_requests' => ['Pending Estate Requests', 'home_work', 'stat-icon-amber', ['Estate Admin' => 'setup']],
        'estate_put_in_hac' => ['Requests to Put in HAC', 'move_to_inbox', 'stat-icon-blue', ['Estate Admin' => 'setup']],
        'estate_hac_pending' => ['Pending in HAC', 'pending_actions', 'stat-icon-rose', ['Estate Admin' => 'setup', 'Estate HAC' => 'setup', 'HAC Person' => 'home']],
        'estate_vacant_houses' => ['Vacant Houses', 'night_shelter', 'stat-icon-green', ['Estate Admin' => 'setup']],
        'estate_pending_meter_readings' => ['Pending Meter Readings (This Month)', 'electric_meter', 'stat-icon-navy', ['Estate Admin' => 'setup']],
    ];

    public function up(): void
    {
        // Cards are placed per tab: needs the column 2026_11_13_000000_add_module_to_role_dashboard_cards adds.
        if (! Schema::hasColumn('role_dashboard_cards', 'sidebar_category_id')) {
            throw new RuntimeException('Run 2026_11_13_000000_add_module_to_role_dashboard_cards first: role_dashboard_cards has no sidebar_category_id column yet.');
        }

        $tabs = DB::table('sidebar_categories')->when(Schema::hasColumn('sidebar_categories', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))->pluck('id', 'slug');
        $roles = DB::table('roles')->pluck('id', 'name');

        foreach (self::CARDS as $key => [$label, $icon, $colour, $assignments]) {
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

            foreach ($assignments as $role => $tab) {
                if (isset($roles[$role], $tabs[$tab])) {
                    DB::table('role_dashboard_cards')->insertOrIgnore([
                        'role_id' => $roles[$role],
                        'dashboard_card_id' => $cardId,
                        'sidebar_category_id' => $tabs[$tab],
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
