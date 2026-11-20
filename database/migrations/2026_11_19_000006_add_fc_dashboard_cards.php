<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FC cards on FC-Sec-Audit's Setup tab (where its FC menus are): registration and exemption
 * application totals from Applications Registration Exemption. Count and link:
 * UserController::dashboard().
 */
return new class extends Migration
{
    /** key => [label, icon, colour] */
    private const CARDS = [
        'fc_registration_applications' => ['FC Registration Applications', 'how_to_reg', 'stat-icon-blue'],
        'fc_exemption_applications' => ['FC Exemption Applications', 'assignment_late', 'stat-icon-amber'],
    ];

    public function up(): void
    {
        // Cards are placed per tab: needs the column 2026_11_13_000000_add_module_to_role_dashboard_cards adds.
        if (! Schema::hasColumn('role_dashboard_cards', 'sidebar_category_id')) {
            throw new RuntimeException('Run 2026_11_13_000000_add_module_to_role_dashboard_cards first: role_dashboard_cards has no sidebar_category_id column yet.');
        }

        $roleId = DB::table('roles')->where('name', 'FC-Sec-Audit')->value('id');
        $setupId = DB::table('sidebar_categories')->where('slug', 'setup')->when(Schema::hasColumn('sidebar_categories', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))->value('id');

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
