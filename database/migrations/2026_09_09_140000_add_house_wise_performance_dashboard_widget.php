<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The faculty dashboard's "House wise Performance" panel.
 *
 * A widget_* card, not a stat card: admin/dashboard.blade.php renders the panel
 * when the key is attached to the viewer's role, and UserController::dashboard
 * builds its rows. So it needs a row here plus the blade block — the panel has
 * no count of its own.
 */
return new class extends Migration
{
    private const KEY = 'widget_house_performance';

    private const LABEL = 'House wise Performance Panel';

    /** Whichever of these exist — only "Faculty" is present today. */
    private const FACULTY_ROLES = ['Faculty', 'Internal Faculty', 'Guest Faculty', 'CC', 'ACC'];

    public function up(): void
    {
        $sortOrder = (int) DB::table('dashboard_cards')->max('sort_order') + 1;

        // updateOrInsert, not insert: re-running the migration on an environment
        // that already has the panel must not create a duplicate key.
        DB::table('dashboard_cards')->updateOrInsert(
            ['key' => self::KEY],
            [
                'label' => self::LABEL,
                'icon' => 'home_work',
                'color_class' => 'stat-icon-rose',
                'sort_order' => $sortOrder,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $cardId = DB::table('dashboard_cards')->where('key', self::KEY)->value('id');

        $roleIds = DB::table('roles')->whereIn('name', self::FACULTY_ROLES)->pluck('id');

        foreach ($roleIds as $roleId) {
            $attached = DB::table('role_dashboard_cards')
                ->where('role_id', $roleId)
                ->where('dashboard_card_id', $cardId)
                ->exists();

            if (! $attached) {
                DB::table('role_dashboard_cards')->insert([
                    'role_id' => $roleId,
                    'dashboard_card_id' => $cardId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $cardId = DB::table('dashboard_cards')->where('key', self::KEY)->value('id');

        if ($cardId) {
            DB::table('role_dashboard_cards')->where('dashboard_card_id', $cardId)->delete();
            DB::table('dashboard_cards')->where('id', $cardId)->delete();
        }
    }
};
