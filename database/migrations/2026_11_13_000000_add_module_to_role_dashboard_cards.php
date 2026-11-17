<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dashboard cards are assigned per role AND per header module (a
 * sidebar_categories row: Home, Setup, Communications, ...), so the same card
 * can sit on several modules' dashboards for one role.
 *
 * Every existing assignment was a Home-dashboard assignment, so the new column
 * is backfilled with Home's id, and Home's id is also its default: any writer
 * that predates modules (older migrations, seeders, tests) keeps meaning Home.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('role_dashboard_cards', 'sidebar_category_id')) {
            return;
        }

        $homeId = $this->homeId();

        Schema::table('role_dashboard_cards', function (Blueprint $table) use ($homeId) {
            $table->unsignedBigInteger('sidebar_category_id')->default($homeId)->after('dashboard_card_id');
        });

        // One statement: the role FK needs an index led by role_id at every instant.
        DB::statement('ALTER TABLE role_dashboard_cards DROP PRIMARY KEY, ADD PRIMARY KEY (role_id, dashboard_card_id, sidebar_category_id)');

        Schema::table('role_dashboard_cards', function (Blueprint $table) {
            $table->foreign('sidebar_category_id', 'rdc_category_fk')
                ->references('id')->on('sidebar_categories')->cascadeOnDelete();
        });
    }

    /**
     * Before modules there was only the Home dashboard, so assignments to any
     * other module have nowhere to go and are removed.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('role_dashboard_cards', 'sidebar_category_id')) {
            return;
        }

        DB::table('role_dashboard_cards')->where('sidebar_category_id', '!=', $this->homeId())->delete();

        Schema::table('role_dashboard_cards', function (Blueprint $table) {
            $table->dropForeign('rdc_category_fk');
        });

        DB::statement('ALTER TABLE role_dashboard_cards DROP PRIMARY KEY, ADD PRIMARY KEY (role_id, dashboard_card_id)');

        Schema::table('role_dashboard_cards', function (Blueprint $table) {
            $table->dropColumn('sidebar_category_id');
        });
    }

    private function homeId(): int
    {
        $id = DB::table('sidebar_categories')->where('slug', 'home')->orderBy('id')->value('id');

        if (! $id) {
            throw new RuntimeException('sidebar_categories has no "home" row: existing dashboard assignments cannot be placed on a module.');
        }

        return (int) $id;
    }
};
