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

        // Insert only when missing: an existing card keeps the label and order an
        // admin gave it, and a re-run creates no duplicate (PR #334 F-036).
        if (! DB::table('dashboard_cards')->where('key', self::KEY)->exists()) {
            DB::table('dashboard_cards')->insert([
                'key' => self::KEY,
                'label' => self::LABEL,
                'icon' => 'home_work',
                'color_class' => 'stat-icon-rose',
                'sort_order' => $sortOrder,
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

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

    /**
     * Forward-fix only (PR #334 F-027 / F-036).
     *
     * up() leaves alone any row that already exists, and records nothing about what
     * it inserted, so down() cannot tell a row this migration created from one an
     * administrator created or edited before it ran. Deleting by name or key — what
     * this method used to do — removed pre-existing menus, permissions, role grants,
     * dashboard cards or role links on rollback. It therefore changes nothing. If
     * these rows must go after a code rollback, remove them by hand from the list in
     * the PR #334 release and rollback plan, then flush the permission cache
     * (php artisan permission:cache-reset).
     */
    public function down(): void
    {
        //
    }
};
