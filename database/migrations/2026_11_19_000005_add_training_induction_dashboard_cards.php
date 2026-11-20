<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Training-Induction cards: pending leave approvals and today's medical exemptions on
 * Home (where Leave Approval and Student Medical Exemption sit), open discipline memos
 * on Academics (where Send Discipline Memo sits). medical_exemptions_today already
 * exists (Doctor). Count and link: UserController::dashboard().
 */
return new class extends Migration
{
    /** key => [label, icon, colour, tab slug] */
    private const CARDS = [
        'pending_leave_approvals' => ['Pending Leave Approvals', 'event_busy', 'stat-icon-amber', 'home'],
        'medical_exemptions_today' => ['Medical Exemptions Today', 'medical_services', 'stat-icon-rose', 'home'],
        'open_discipline_memos' => ['Open Discipline Memos', 'gavel', 'stat-icon-navy', 'academics'],
    ];

    public function up(): void
    {
        // Cards are placed per tab: needs the column 2026_11_13_000000_add_module_to_role_dashboard_cards adds.
        if (! Schema::hasColumn('role_dashboard_cards', 'sidebar_category_id')) {
            throw new RuntimeException('Run 2026_11_13_000000_add_module_to_role_dashboard_cards first: role_dashboard_cards has no sidebar_category_id column yet.');
        }

        $roleId = DB::table('roles')->where('name', 'Training-Induction')->value('id');
        $tabs = DB::table('sidebar_categories')->when(Schema::hasColumn('sidebar_categories', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))->pluck('id', 'slug');

        foreach (self::CARDS as $key => [$label, $icon, $colour, $tab]) {
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

            if ($roleId && isset($tabs[$tab])) {
                DB::table('role_dashboard_cards')->insertOrIgnore([
                    'role_id' => $roleId,
                    'dashboard_card_id' => $cardId,
                    'sidebar_category_id' => $tabs[$tab],
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
