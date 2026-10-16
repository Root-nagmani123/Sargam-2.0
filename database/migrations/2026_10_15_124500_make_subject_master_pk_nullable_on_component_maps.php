<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * subject_master_pk was NOT NULL with no default, but the "Map Faculty to
 * Examination Drive" component table has no Subject column to populate it
 * from — every save failed with a DB error. Made nullable.
 *
 * Raw SQL rather than Schema::table()->change() — doctrine/dbal isn't
 * installed in this project, which that method requires.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE examination_drive_component_maps MODIFY subject_master_pk BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE examination_drive_component_maps MODIFY subject_master_pk BIGINT UNSIGNED NOT NULL');
    }
};
