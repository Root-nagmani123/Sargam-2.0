<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * store() records issue_logger as NULL for a login that is no employee (PR #337 F-010),
 * but 2026_01_22_000005 created the column NOT NULL (F-011). Live databases have drifted
 * from that migration (testsargam8 holds a signed, nullable bigint), so this only drops
 * NOT NULL and keeps whatever type and comment the column already has.
 */
return new class extends Migration
{
    public function up(): void
    {
        // SHOW COLUMNS, not information_schema: it also sees a session TEMPORARY table, which the test uses.
        $column = DB::selectOne("SHOW FULL COLUMNS FROM issue_log_management WHERE Field = 'issue_logger'");

        if ($column === null || $column->Null === 'YES') {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE issue_log_management MODIFY issue_logger %s NULL COMMENT %s',
            $column->Type,
            DB::getPdo()->quote($column->Comment)
        ));
    }

    public function down(): void
    {
        // Left nullable: store() writes NULL loggers once this has run, and NOT NULL would reject them.
    }
};
