<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Officer Trainee dashboard's remaining cards.
 *
 * Cards are data, not markup: admin/dashboard.blade.php renders whatever
 * dashboard_cards rows are attached to the viewer's role, and
 * UserController::dashboard supplies the count and link per key.
 *
 *   Pending Feedback   -> the OT's own Session Feedback page (no count)
 *   My Timetable       -> their own classes (no count)
 *   Academic Timetable -> the whole Academy's timetable (no count)
 *
 * The last two already exist for faculty, so this only attaches them to the OT
 * role; the links differ per viewer and are resolved in the controller.
 */
return new class extends Migration
{
    /** key => [label, icon, color_class]. Only new keys are created. */
    private const NEW_CARDS = [
        'pending_feedback' => ['Pending Feedback', 'rate_review', 'stat-icon-amber'],
    ];

    /** Existing keys the OT role also gets. */
    private const SHARED_CARDS = ['my_timetable', 'academic_timetable'];

    private const OT_ROLES = ['Officer Trainee', 'Student-OT'];

    public function up(): void
    {
        $sortOrder = (int) DB::table('dashboard_cards')->max('sort_order');

        foreach (self::NEW_CARDS as $key => [$label, $icon, $colorClass]) {
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
        }

        $roleIds = DB::table('roles')->whereIn('name', self::OT_ROLES)->pluck('id');

        $cardIds = DB::table('dashboard_cards')
            ->whereIn('key', array_merge(array_keys(self::NEW_CARDS), self::SHARED_CARDS))
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($cardIds as $cardId) {
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
