<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $route = 'issue-reports';

        if (DB::table('menus')->where('route', $route)->exists()) {
            return;
        }

        $now = now();

        // Shift existing menus with order >= 124 down by 1 to make room
        DB::table('menus')
            ->where('category_id', 1)
            ->where('group_id', 1)
            ->where('order', '>=', 124)
            ->increment('order');

        DB::table('menus')->insert([
            'category_id' => 1,
            'group_id'    => 1,
            'parent_id'   => null,
            'name'        => 'Reported Issues',
            'route'       => $route,
            'icon'        => 'flag',
            'order'       => 124,
            'is_active'   => 1,
            'target'      => '0',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('menus')->where('route', 'issue-reports')->delete();
    }
};
