<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('menus')
            ->where('route', 'issue-reports')
            ->where('category_id', 1)
            ->where('group_id', 1)
            ->update([
                'route'      => 'my-reported-issues',
                'updated_at' => now(),
            ]);

        Cache::forget('sidebar_nav_route_index');
    }

    public function down(): void
    {
        DB::table('menus')
            ->where('route', 'my-reported-issues')
            ->where('category_id', 1)
            ->where('group_id', 1)
            ->update([
                'route'      => 'issue-reports',
                'updated_at' => now(),
            ]);

        Cache::forget('sidebar_nav_route_index');
    }
};
