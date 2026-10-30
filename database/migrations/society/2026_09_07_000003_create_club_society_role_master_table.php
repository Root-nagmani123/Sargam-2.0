<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master table behind Communications -> Club/ Society -> Define Club/ Society Role.
 *
 * Mirrors club_society_master: `pk` primary key, `active_inactive` status flag,
 * and DB-managed created_date / updated_date rather than Eloquent timestamps.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('club_society_role_master')) {
            return;
        }

        Schema::create('club_society_role_master', function (Blueprint $table) {
            $table->bigIncrements('pk');
            $table->string('club_society_role_name', 255)->nullable();
            $table->integer('active_inactive')->default(1);
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('updated_date')->useCurrent()->useCurrentOnUpdate();

            $table->index('club_society_role_name', 'club_society_role_master_name_index');
        });
    }

    public function down(): void
    {
        // up() skips a table that already exists, so this migration may not have
        // created it. Never drop one that holds rows: check them all before the
        // first drop so a refused rollback leaves nothing half-done (PR #340 F-008).
        foreach (['club_society_role_master'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new \RuntimeException(
                    "Refusing to roll back: table {$table} has rows. Back it up and empty it with the DBA first."
                );
            }
        }

        Schema::dropIfExists('club_society_role_master');
    }
};
