<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE → OMR: scanned OMR answer sheets, one row per sheet, with the OT's score.
 *
 * A copy of the `sheets` table of the LBSNAA OMR application (database `lbsnaa_omr`):
 * the same columns, types and indexes, nothing added. Named `coe_exam_omr` to follow the
 * COE table naming. The OMR database's foreign keys (exam_id → exams, reviewed_by → users)
 * are left out because those tables are not in Sargam; the indexes are kept.
 *
 * status (as the OMR app writes it): pending, processing, processed, review, failed, verified.
 *
 * Deployment: new table only, so the running application is unaffected. Rollback drops it.
 */
return new class extends Migration
{
    private const TABLE = 'coe_exam_omr';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id');
            $table->string('upload_name', 191);
            $table->string('path', 191);
            $table->string('file_hash', 40)->nullable();
            $table->unsignedSmallInteger('page')->default(1);
            $table->string('candidate_name', 191)->nullable();
            $table->string('name_source', 10)->nullable();
            $table->string('name_image', 191)->nullable();
            $table->string('name_read', 60)->nullable();
            $table->string('roll_number', 20)->nullable();
            $table->json('roll_detail')->nullable();
            $table->string('ot_code', 12)->nullable();
            $table->json('ot_detail')->nullable();
            $table->string('series', 1)->nullable();
            $table->string('upload_series', 1)->nullable();
            $table->json('answers')->nullable();
            $table->json('fills')->nullable();
            $table->json('review')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('error')->nullable();
            $table->unsignedSmallInteger('correct')->default(0);
            $table->unsignedSmallInteger('wrong')->default(0);
            $table->unsignedSmallInteger('multiple')->default(0);
            $table->unsignedSmallInteger('blank')->default(0);
            $table->unsignedSmallInteger('max_questions')->default(0);
            $table->decimal('score', 8, 2)->default(0);
            $table->decimal('percentage', 6, 2)->default(0);
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('exam_id', 'coe_exam_omr_exam_id_index');
            $table->index('reviewed_by', 'coe_exam_omr_reviewed_by_index');
            $table->index('roll_number', 'coe_exam_omr_roll_number_index');
            $table->index('status', 'coe_exam_omr_status_index');
            $table->index('file_hash', 'coe_exam_omr_file_hash_index');
            $table->index('ot_code', 'coe_exam_omr_ot_code_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
