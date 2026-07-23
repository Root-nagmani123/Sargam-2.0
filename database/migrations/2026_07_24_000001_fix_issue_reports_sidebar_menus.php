<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Revert admin menu route back to 'issue-reports'
        //    (a previous migration had changed it to 'my-reported-issues')
        DB::table('menus')
            ->where('route', 'my-reported-issues')
            ->where('category_id', 1)
            ->where('group_id', 1)
            ->update([
                'route'      => 'issue-reports',
                'updated_at' => now(),
            ]);

        // 2. Insert a user-facing "Reported Issues" menu entry pointing to my-reported-issues
        //    permission_name='dashboard' so all logged-in users can see it in their sidebar
        $exists = DB::table('menus')
            ->where('route', 'my-reported-issues')
            ->where('category_id', 1)
            ->where('group_id', 1)
            ->exists();

        if (! $exists) {
            DB::table('menus')->insert([
                'name'            => 'Reported Issues',
                'route'           => 'my-reported-issues',
                'icon'            => 'report_problem',
                'category_id'     => 1,
                'group_id'        => 1,
                'parent_id'       => null,
                'order'           => 125,
                'target'          => '0',
                'is_active'       => 1,
                'permission_name' => 'dashboard',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        Cache::forget('sidebar_nav_route_index');
    }

    public function down(): void
    {
        // Remove the user-facing menu
        DB::table('menus')
            ->where('route', 'my-reported-issues')
            ->where('category_id', 1)
            ->where('group_id', 1)
            ->delete();

        // Restore admin menu back to my-reported-issues
        DB::table('menus')
            ->where('route', 'issue-reports')
            ->where('name', 'Reported Issues')
            ->where('category_id', 1)
            ->where('group_id', 1)
            ->update([
                'route'      => 'my-reported-issues',
                'updated_at' => now(),
            ]);

        Cache::forget('sidebar_nav_route_index');
    }
};
