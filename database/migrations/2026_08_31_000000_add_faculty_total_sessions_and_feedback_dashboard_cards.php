<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registers the faculty dashboard's "Total Sessions" and "Total Feedback" cards.
 *
 * Cards are data, not markup: admin/dashboard.blade.php renders whatever
 * dashboard_cards rows are attached to the viewer's role, and
 * UserController::dashboard supplies the count and link per key. So a new card
 * needs a row here plus a $cardDefinitions entry — no view change.
 */
return new class extends Migration
{
    /** key => [label, icon, color_class] — icons/colours from the existing card set. */
    private const CARDS = [
        'total_sessions' => ['Total Sessions', 'calendar_month', 'stat-icon-blue'],
        'total_feedback' => ['Total Feedback', 'reviews', 'stat-icon-green'],
    ];

    /** Whichever of these exist — only "Faculty" is present today. */
    private const FACULTY_ROLES = ['Faculty', 'Internal Faculty', 'Guest Faculty', 'CC', 'ACC'];

    public function up(): void
    {
        $sortOrder = (int) DB::table('dashboard_cards')->max('sort_order');

        $roleIds = DB::table('roles')->whereIn('name', self::FACULTY_ROLES)->pluck('id');

        foreach (self::CARDS as $key => [$label, $icon, $colorClass]) {
            // Insert only when missing: an existing card keeps the label and order an
            // admin gave it, and a re-run creates no duplicate (PR #334 F-036).
            if (! DB::table('dashboard_cards')->where('key', $key)->exists()) {
                DB::table('dashboard_cards')->insert([
                    'key' => $key,
                    'label' => $label,
                    'icon' => $icon,
                    'color_class' => $colorClass,
                    'sort_order' => ++$sortOrder,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }

            $cardId = DB::table('dashboard_cards')->where('key', $key)->value('id');

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
