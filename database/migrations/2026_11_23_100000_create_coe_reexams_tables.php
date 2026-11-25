<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE Re-exam: OTs who failed or were absent in a subject of an examination drive
 * sit that subject again on a new drive.
 *
 * ── Why two new tables and no new column on examination_drives ──
 * A re-exam IS an examination drive: a normal row in examination_drives, with its own
 * faculty maps, component maps and question papers, so faculty setup, CC approval,
 * question paper and Rajbhasha work on it unchanged. What makes it a re-exam is kept
 * here, so the old tables keep their structure:
 *
 *   coe_reexams            main drive -> re-exam drive, numbered 1, 2, 3 ...
 *                          main_drive_id is always the MAIN drive, also for Re-exam 2
 *                          (flat, never a chain), so one indexed query finds them all.
 *   coe_reexam_candidates  who sits it: one row per re-exam + subject + OT, with the
 *                          reason (1 Failed, 2 Absent) and the score % of the result
 *                          it was created from (kept, not recalculated).
 *
 * ── Type mapping ──
 *   examination_drives.id  bigint UNSIGNED
 *   subject_master.pk      bigint UNSIGNED
 *   student_master.pk      bigint SIGNED -> bigInteger() (same as coe_evaluation_marks)
 *
 * ── Deletes ──
 *   main drive    restricted while it has re-exams (the controller refuses first).
 *   re-exam drive its coe_reexams row and candidates go with it (cascade).
 *
 * Rollback: down() drops both tables; re-exam drives themselves stay as normal drives.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('coe_reexams')) {
            Schema::create('coe_reexams', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('main_drive_id')->comment('examination_drives.id of the main drive');
                $table->unsignedBigInteger('reexam_drive_id')->comment('examination_drives.id of this re-exam');
                $table->unsignedTinyInteger('reexam_no')->comment('1, 2, 3 ... per main drive');
                $table->unsignedBigInteger('created_by')->nullable()->comment('user_credentials.user_id');
                $table->timestamp('created_at')->nullable();

                $table->unique('reexam_drive_id', 'coe_rx_reexam_drive_unique');
                $table->unique(['main_drive_id', 'reexam_no'], 'coe_rx_main_no_unique');

                $table->foreign('main_drive_id', 'coe_rx_main_drive_fk')
                    ->references('id')->on('examination_drives')->restrictOnDelete();
                $table->foreign('reexam_drive_id', 'coe_rx_reexam_drive_fk')
                    ->references('id')->on('examination_drives')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('coe_reexam_candidates')) {
            Schema::create('coe_reexam_candidates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reexam_id')->comment('coe_reexams.id');
                $table->unsignedBigInteger('subject_master_pk');
                $table->bigInteger('student_master_pk');
                $table->unsignedTinyInteger('reason')->comment('1=Failed, 2=Absent');
                $table->decimal('score_percent', 5, 2)->nullable()->comment('Subject marks / max x 100 when created');
                $table->timestamp('created_at')->nullable();

                $table->unique(['reexam_id', 'subject_master_pk', 'student_master_pk'], 'coe_rxc_unique');
                $table->index('student_master_pk', 'coe_rxc_student_index');

                $table->foreign('reexam_id', 'coe_rxc_reexam_fk')
                    ->references('id')->on('coe_reexams')->cascadeOnDelete();
                $table->foreign('subject_master_pk', 'coe_rxc_subject_fk')
                    ->references('pk')->on('subject_master');
                $table->foreign('student_master_pk', 'coe_rxc_student_fk')
                    ->references('pk')->on('student_master');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coe_reexam_candidates');
        Schema::dropIfExists('coe_reexams');
    }
};
