<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->tinyInteger('exclude_from_admin')->default(0)->after('is_active');
        });

        // Menu 253 (my-reported-issues) is user-facing only — hide from Super Admin
        DB::table('menus')->where('id', 253)->update(['exclude_from_admin' => 1]);

        Cache::forget('sidebar_nav_route_index');
    }

    public function down(): void
    {
        DB::table('menus')->where('id', 253)->update(['exclude_from_admin' => 0]);

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('exclude_from_admin');
        });

        Cache::forget('sidebar_nav_route_index');
    }
};
