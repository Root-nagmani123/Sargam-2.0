<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Examination Type is optional on an Examination Drive (COE feedback, Nov 2026).
 *
 * Only the NOT NULL is dropped; the foreign key to examination_type_master stays, so a
 * value that is set must still be a real type. Raw ALTER rather than ->change(), which
 * needs doctrine/dbal for a column with a foreign key on it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('examination_drives', 'examination_type_master_pk')) {
            return;
        }

        DB::statement('ALTER TABLE examination_drives MODIFY examination_type_master_pk BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('examination_drives', 'examination_type_master_pk')) {
            return;
        }

        // NOT NULL cannot come back while a drive has no type; refuse rather than invent one.
        if (DB::table('examination_drives')->whereNull('examination_type_master_pk')->exists()) {
            throw new RuntimeException('Some examination drives have no Examination Type; set one on each before rolling back.');
        }

        DB::statement('ALTER TABLE examination_drives MODIFY examination_type_master_pk BIGINT UNSIGNED NOT NULL');
    }
};
