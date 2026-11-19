<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Course Repository Folders" card on CR-Admin's Setup tab (where Course Repository is):
 * the root folders the repository page lists. Count and link: UserController::dashboard().
 */
return new class extends Migration
{
    private const KEY = 'course_repository_folders';

    public function up(): void
    {
        $cardId = DB::table('dashboard_cards')->where('key', self::KEY)->value('id')
            ?? DB::table('dashboard_cards')->insertGetId([
                'key' => self::KEY,
                'label' => 'Course Repository Folders',
                'icon' => 'folder_open',
                'color_class' => 'stat-icon-blue',
                'sort_order' => (int) DB::table('dashboard_cards')->max('sort_order') + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $roleId = DB::table('roles')->where('name', 'CR-Admin')->value('id');
        $setupId = DB::table('sidebar_categories')->where('slug', 'setup')->whereNull('deleted_at')->value('id');
        if ($roleId && $setupId) {
            DB::table('role_dashboard_cards')->insertOrIgnore([
                'role_id' => $roleId,
                'dashboard_card_id' => $cardId,
                'sidebar_category_id' => $setupId,
            ]);
        }
    }

    /** Forward-fix only, like the other dashboard-card migrations (PR #334 F-027 / F-036). */
    public function down(): void
    {
        //
    }
};
