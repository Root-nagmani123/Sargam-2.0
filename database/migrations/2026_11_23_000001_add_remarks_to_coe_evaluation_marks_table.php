<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE marks remarks: faculty remarks on Result Entry, and the Director's overall remarks
 * on Evaluation & Marks (Director Assessment), which cannot be saved without it.
 *
 * The column first came from DBA DDL with no migration, so servers where that DDL was
 * never run (staging, 23 Nov 2026) failed with "Unknown column 'remarks'". This mirrors
 * that DDL exactly, as on the dev database:
 *
 *   ALTER TABLE coe_evaluation_marks
 *     ADD COLUMN remarks varchar(500) NULL DEFAULT NULL AFTER is_absent;
 *
 * Idempotent: a column already added by the DBA is left as it is.
 */
return new class extends Migration
{
    private const TABLE = 'coe_evaluation_marks';

    public function up(): void
    {
        if (Schema::hasColumn(self::TABLE, 'remarks')) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->string('remarks', 500)->nullable()->after('is_absent');
        });
    }

    /**
     * Left in place: on most servers the DBA added this column, and it holds faculty and
     * Director remarks; rolling back must not drop them. Drop it by hand if ever needed.
     */
    public function down(): void
    {
    }
};
