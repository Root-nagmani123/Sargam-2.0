<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The rest of the faculty dashboard's cards.
 *
 * Cards are data, not markup: admin/dashboard.blade.php renders whatever
 * dashboard_cards rows are attached to the viewer's role, and
 * UserController::dashboard supplies the count and link per key. So a new card
 * needs a row here plus a $cardDefinitions entry — no view change.
 *
 *   Academic Timetable   -> the whole Academy's timetable (no count)
 *   My Timetable         -> the viewer's own classes (no count)
 *   My Counsellees       -> OT / Participants list, their counsellees
 *   House Wise Details   -> the same list, opened house-wise
 *
 * It also renames "Total Feedback", which counts feedback on running courses
 * only, to say so.
 */
return new class extends Migration
{
    /** key => [label, icon, color_class] — icons/colours from the existing card set. */
    private const CARDS = [
        'academic_timetable' => ['Academic Timetable', 'calendar_view_month', 'stat-icon-navy'],
        'my_timetable' => ['My Timetable', 'schedule', 'stat-icon-blue'],
        'my_counsellees' => ['My Counsellees', 'groups', 'stat-icon-amber'],
        'house_wise_details' => ['House Wise Details', 'home_work', 'stat-icon-rose'],
    ];

    private const RENAMES = [
        'total_feedback' => ['Total Running Courses Feedback', 'Total Feedback'],
    ];

    /** Whichever of these exist — only "Faculty" is present today. */
    private const FACULTY_ROLES = ['Faculty', 'Internal Faculty', 'Guest Faculty', 'CC', 'ACC'];

    public function up(): void
    {
        $sortOrder = (int) DB::table('dashboard_cards')->max('sort_order');

        $roleIds = DB::table('roles')->whereIn('name', self::FACULTY_ROLES)->pluck('id');

        foreach (self::CARDS as $key => [$label, $icon, $colorClass]) {
            // updateOrInsert, not insert: re-running the migration on an environment
            // that already has the card must not create a duplicate key.
            DB::table('dashboard_cards')->updateOrInsert(
                ['key' => $key],
                [
                    'label' => $label,
                    'icon' => $icon,
                    'color_class' => $colorClass,
                    'sort_order' => ++$sortOrder,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

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

        foreach (self::RENAMES as $key => [$to, $from]) {
            DB::table('dashboard_cards')
                ->where('key', $key)
                ->where('label', $from)
                ->update(['label' => $to, 'updated_at' => now()]);
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
