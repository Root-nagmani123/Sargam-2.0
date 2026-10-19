<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Registers the "My Groups" dashboard card and shows it to Officer Trainees.
 *
 * The card's count and link come from UserController::dashboard()'s $cardDefinitions,
 * keyed by 'my_groups' — but a card only renders if a dashboard_cards row with that key
 * exists AND is assigned to one of the viewer's roles. This seeder does both, so the
 * feature is not left half-wired.
 *
 * The role is the Spatie role "Officer Trainee". 'Student-OT' — which the controller
 * checks with hasRole() — is a session-only pseudo-role that is never assigned in the
 * database, so it cannot be used here (see hasRole() / isOfficerTraineeUser() in
 * app/helpers.php).
 *
 * Idempotent: safe to re-run, and equivalent to creating the card and ticking the role
 * in Roles & Permissions → Assign Dashboard.
 *
 *   php artisan db:seed --class=MyGroupsDashboardCardSeeder
 */
class MyGroupsDashboardCardSeeder extends Seeder
{
    /** Must match the $cardDefinitions key in UserController::dashboard(). */
    private const CARD_KEY = 'my_groups';

    private const ROLE_NAME = 'Officer Trainee';

    public function run(): void
    {
        $now = now();

        $attributes = [
            'label' => 'My Groups',
            'icon' => 'groups',
            'color_class' => 'stat-icon-green',
        ];

        $card = DB::table('dashboard_cards')->where('key', self::CARD_KEY)->first();

        if ($card) {
            // Leave sort_order alone — an admin may have reordered the card by hand.
            DB::table('dashboard_cards')
                ->where('id', $card->id)
                ->update($attributes + ['updated_at' => $now]);

            $cardId = $card->id;
        } else {
            $cardId = DB::table('dashboard_cards')->insertGetId($attributes + [
                'key' => self::CARD_KEY,
                'sort_order' => (int) DB::table('dashboard_cards')->max('sort_order') + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleId = DB::table('roles')->where('name', self::ROLE_NAME)->value('id');

        if (! $roleId) {
            $this->command?->warn(sprintf(
                'Role "%s" not found — card created but not assigned to any role.',
                self::ROLE_NAME
            ));

            return;
        }

        $alreadyAssigned = DB::table('role_dashboard_cards')
            ->where('role_id', $roleId)
            ->where('dashboard_card_id', $cardId)
            ->exists();

        if (! $alreadyAssigned) {
            DB::table('role_dashboard_cards')->insert([
                'role_id' => $roleId,
                'dashboard_card_id' => $cardId,
            ]);
        }

        $this->command?->info(sprintf(
            'My Groups card (id %d) is enabled for "%s".',
            $cardId,
            self::ROLE_NAME
        ));
    }
}
