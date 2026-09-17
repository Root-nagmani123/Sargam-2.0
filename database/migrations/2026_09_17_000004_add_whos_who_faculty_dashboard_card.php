<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Who's Who" card on the faculty dashboard.
 *
 * Cards are data, not markup: admin/dashboard.blade.php renders whatever
 * dashboard_cards rows are attached to the viewer's role, and
 * UserController::dashboard supplies the link per key. So this needs a row here
 * plus a $cardDefinitions entry — no view change.
 *
 * It opens Setup > HR Management > Faculty > Who's Who, the OT directory. Like
 * the two timetable cards it carries no count: the page opens on a course
 * picker, so there is no single number the tile could honestly show.
 */
return new class extends Migration
{
    private const KEY = 'whos_who';

    /** Whichever of these exist — only "Faculty" is present today. */
    private const FACULTY_ROLES = ['Faculty', 'Internal Faculty', 'Guest Faculty', 'CC', 'ACC'];

    public function up(): void
    {
        $sortOrder = (int) DB::table('dashboard_cards')->max('sort_order');

        // updateOrInsert, not insert: re-running on an environment that already
        // has the card must not create a duplicate key.
        DB::table('dashboard_cards')->updateOrInsert(
            ['key' => self::KEY],
            [
                'label' => "Who's Who",
                'icon' => 'contacts',
                'color_class' => 'stat-icon-blue',
                'sort_order' => $sortOrder + 1,
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
