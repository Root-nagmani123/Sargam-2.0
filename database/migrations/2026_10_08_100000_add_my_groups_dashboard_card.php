<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "My Groups" card on the OT dashboard.
 *
 * Every other dashboard card this release adds ships as a migration; this one
 * only had MyGroupsDashboardCardSeeder, which nothing runs on deploy, so after
 * `migrate` no card row existed and the card never rendered (PR #334 F-064).
 * This does what the seeder does. The seeder stays for environments that ran it.
 *
 * Assigned to the Spatie role "Officer Trainee": 'Student-OT', which the
 * dashboard checks with hasRole(), is a session-only pseudo-role that no
 * database row holds.
 */
return new class extends Migration
{
    private const KEY = 'my_groups';

    private const ROLE_NAME = 'Officer Trainee';

    public function up(): void
    {
        $cardId = DB::table('dashboard_cards')->where('key', self::KEY)->value('id');

        // An environment that already ran the seeder keeps its row as it is: an
        // admin may have relabelled or reordered the card since.
        if (! $cardId) {
            $cardId = DB::table('dashboard_cards')->insertGetId([
                'key' => self::KEY,
                'label' => 'My Groups',
                'icon' => 'groups',
                'color_class' => 'stat-icon-green',
                'sort_order' => (int) DB::table('dashboard_cards')->max('sort_order') + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $roleId = DB::table('roles')->where('name', self::ROLE_NAME)->value('id');
        if (! $roleId) {
            return;
        }

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

    /**
     * Deliberately a no-op. The row may predate this migration (created by the
     * seeder, or by hand in Roles & Permissions → Assign Dashboard), and up()
     * cannot tell which, so deleting it on rollback could remove a card an admin
     * set up. Re-running up() after a rollback is safe: it inserts nothing twice.
     */
    public function down(): void
    {
        //
    }
};
