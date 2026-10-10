<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registers the OT dashboard's "Total Marks Deducted in Discipline" card.
 *
 * Same shape as the faculty cards: a dashboard_cards row attached to the role,
 * with the count and link supplied by UserController::dashboard's
 * $cardDefinitions under this key.
 */
return new class extends Migration
{
    private const CARD_KEY = 'discipline_marks_deducted';

    /** Whichever of these exist — only "Officer Trainee" is present today. */
    private const OT_ROLES = ['Officer Trainee', 'Student-OT'];

    public function up(): void
    {
        $sortOrder = (int) DB::table('dashboard_cards')->max('sort_order') + 1;

        // Insert only when missing: an existing card keeps the label and order an
        // admin gave it, and a re-run creates no duplicate (PR #334 F-036).
        if (! DB::table('dashboard_cards')->where('key', self::CARD_KEY)->exists()) {
            DB::table('dashboard_cards')->insert([
                'key' => self::CARD_KEY,
                'label' => 'Total Marks Deducted in Discipline',
                'icon' => 'gavel',
                'color_class' => 'stat-icon-rose',
                'sort_order' => $sortOrder,
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

        $cardId = DB::table('dashboard_cards')->where('key', self::CARD_KEY)->value('id');

        $roleIds = DB::table('roles')->whereIn('name', self::OT_ROLES)->pluck('id');

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
