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

        // updateOrInsert, not insert: re-running on an environment that already has
        // the card must not create a duplicate key.
        DB::table('dashboard_cards')->updateOrInsert(
            ['key' => self::CARD_KEY],
            [
                'label' => 'Total Marks Deducted in Discipline',
                'icon' => 'gavel',
                'color_class' => 'stat-icon-rose',
                'sort_order' => $sortOrder,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

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

    public function down(): void
    {
        $cardId = DB::table('dashboard_cards')->where('key', self::CARD_KEY)->value('id');

        if ($cardId) {
            DB::table('role_dashboard_cards')->where('dashboard_card_id', $cardId)->delete();
            DB::table('dashboard_cards')->where('id', $cardId)->delete();
        }
    }
};
