<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE › Result Management › OMR Upload: the CSV an OMR scanner exports, one row
 * per answer sheet, kept as it was read.
 *
 *   coe_omr_uploads    one uploaded file (who, when, how many sheets, read errors)
 *   coe_omr_responses  one answer sheet: NCS header, barcode, roll number,
 *                      booklet series, the marked answers and, when the file
 *                      carries it, the score the scanner computed (kept as
 *                      written, negative included)
 *
 * ── roll_number / student_master_pk ──
 *   The export's ENR_NO column carries the participant's roll number. It is stored
 *   as read — it can be blank or carry '*' where the bubbles were not read — and
 *   student_master_pk is set when it equals a coe_roll_numbers.roll_number
 *   (OmrParticipantMatcher); otherwise NULL with the reason in match_remarks.
 *   student_master.pk is signed, hence bigInteger(), as on coe_roll_numbers.
 *
 * ── answers ──
 *   JSON list in question order: 'A'..'Z' as marked, '' left blank, '*' more than
 *   one bubble marked. The question count is per file (question_count), not fixed.
 *
 * ── file_hash ──
 *   SHA-256 of the file, unique: the same export cannot be uploaded twice. Deleting
 *   the upload frees it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('coe_omr_uploads')) {
            Schema::create('coe_omr_uploads', function (Blueprint $table) {
                $table->id('pk');
                $table->string('original_file_name', 255);
                $table->char('file_hash', 64)->comment('SHA-256 of the uploaded file');
                $table->string('remarks', 255)->nullable();

                $table->unsignedSmallInteger('question_count');
                $table->tinyInteger('has_score')->default(0)->comment('1 = the file carried a score column');
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('error_rows')->default(0)->comment('Sheets with a read problem');

                $table->unsignedBigInteger('created_by')->nullable()->comment('user_credentials.pk');
                $table->timestamp('created_date')->useCurrent();

                $table->unique('file_hash', 'coe_omr_uploads_hash_unique');
            });
        }

        if (! Schema::hasTable('coe_omr_responses')) {
            Schema::create('coe_omr_responses', function (Blueprint $table) {
                $table->id('pk');
                $table->unsignedBigInteger('coe_omr_upload_pk');
                $table->unsignedInteger('row_no')->comment('Line in the uploaded file');

                $table->string('ncs_header', 100)->nullable();
                $table->string('barcode', 30)->nullable();
                $table->string('roll_number', 50)->nullable()->comment('ENR_NO column of the export: the roll number as read');
                // Signed — matches student_master.pk, as on coe_roll_numbers.
                $table->bigInteger('student_master_pk')->nullable()
                    ->comment('Participant whose coe_roll_numbers.roll_number this is; NULL = not matched');
                $table->string('match_remarks', 100)->nullable();
                $table->string('booklet_series', 5)->nullable();
                $table->json('answers');

                $table->unsignedSmallInteger('attempted_count')->default(0);
                $table->unsignedSmallInteger('blank_count')->default(0);
                $table->unsignedSmallInteger('multi_marked_count')->default(0);
                $table->smallInteger('score')->nullable()->comment('As computed by the scanner; may be negative');

                $table->tinyInteger('has_error')->default(0);
                $table->string('error_remarks', 255)->nullable();

                $table->timestamp('created_date')->useCurrent();

                $table->unique(['coe_omr_upload_pk', 'row_no'], 'coe_omr_resp_row_unique');
                $table->index('roll_number', 'coe_omr_resp_roll_index');
                $table->index('barcode', 'coe_omr_resp_barcode_index');
                $table->index('student_master_pk', 'coe_omr_resp_student_index');
                // The sheets list of one upload (100k+ rows) filters, counts and lists
                // distinct values on these; each starts with the upload.
                $table->index(['coe_omr_upload_pk', 'student_master_pk'], 'coe_omr_resp_upload_student_index');
                $table->index(['coe_omr_upload_pk', 'booklet_series'], 'coe_omr_resp_upload_booklet_index');
                $table->index(['coe_omr_upload_pk', 'has_error'], 'coe_omr_resp_upload_error_index');
                $table->index(['coe_omr_upload_pk', 'score'], 'coe_omr_resp_upload_score_index');

                $table->foreign('coe_omr_upload_pk', 'coe_omr_resp_upload_fk')
                    ->references('pk')->on('coe_omr_uploads')->cascadeOnDelete();
                $table->foreign('student_master_pk', 'coe_omr_resp_student_fk')
                    ->references('pk')->on('student_master')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coe_omr_responses');
        Schema::dropIfExists('coe_omr_uploads');
    }
};
