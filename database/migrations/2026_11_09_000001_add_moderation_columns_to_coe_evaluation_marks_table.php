<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE → Result Records and Marks Moderation: the SOP COE/11 moderated marks of an
 * approved mark, saved by ResultMarksService::moderate(). Until these exist the
 * screen works the marks out but cannot save them (moderationEnabled()).
 *
 * ── Type mapping, checked against the live schema ──
 *   moderated_marks  decimal(6,2) NULL, as marks — it stands in for them in results.
 *   moderated_by     bigint UNSIGNED NULL = user_credentials.user_id, as approved_by.
 *                    No FK: user_credentials.user_id is not unique.
 *   moderated_at     timestamp NULL, as approved_at.
 *
 * Idempotent: a column already added (e.g. by DBA DDL) is left as it is.
 */
return new class extends Migration
{
    private const TABLE = 'coe_evaluation_marks';

    public function up(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table) {
            if (! Schema::hasColumn(self::TABLE, 'moderated_marks')) {
                $table->decimal('moderated_marks', 6, 2)->nullable()->after('marks');
            }
            if (! Schema::hasColumn(self::TABLE, 'moderated_by')) {
                $table->unsignedBigInteger('moderated_by')->nullable()->comment('user_credentials.user_id')->after('approved_at');
            }
            if (! Schema::hasColumn(self::TABLE, 'moderated_at')) {
                $table->timestamp('moderated_at')->nullable()->after('moderated_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table) {
            foreach (['moderated_at', 'moderated_by', 'moderated_marks'] as $column) {
                if (Schema::hasColumn(self::TABLE, $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
