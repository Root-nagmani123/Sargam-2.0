<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE Re-exam, step 7: the re-exam marksheet (Pass / Fail only, no marks).
 *
 * One row per re-exam + OT, written by ReexamMarksheetService once every re-exam subject
 * of the OT has approved marks (ReexamService::result). Signed like the main marksheet:
 * UDC → AAO → COE; the COE's signature publishes it and freezes it in `snapshot`.
 * coe_marksheets (the main marksheet) is not touched.
 *
 * ── Type mapping ──
 *   reexam_id                             coe_reexams.id (bigint UNSIGNED); deleting the re-exam
 *                                         drive cascades its link, and so its marksheets.
 *   course_master_pk / student_master_pk  unsigned bigint, no FK, as coe_marksheets.
 *   published_by / created_by / modified_by  user_credentials.user_id.
 *   signed                                {"UDC": {"signature": <coe_signature_master.pk>, "by": <user_id>, "at": "Y-m-d H:i:s"}, ...}
 *
 * ── Deployment ──
 *   Run only this file: php artisan migrate --path=database/migrations/2026_11_24_100000_create_coe_reexam_marksheets_table.php
 *   Rollback     : down() drops the table; re-exam marksheets are generated afresh (new
 *                  numbers) and their signatures are lost.
 *   Verification : Marksheet Generation → Re-exam tab lists the OTs whose re-exam result is in.
 */
return new class extends Migration
{
    private const TABLE = 'coe_reexam_marksheets';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('reexam_id')->comment('coe_reexams.id');
            $table->unsignedBigInteger('course_master_pk');
            $table->unsignedBigInteger('student_master_pk');
            $table->unsignedInteger('serial')->comment('Running number of re-exam marksheets within the course');
            $table->string('marksheet_number', 50)->unique();
            $table->unsignedTinyInteger('status')->default(1)->comment('1=Generated, 2=Published');
            $table->json('signed')->nullable()->comment('Signatures put on the marksheet, by designation');
            $table->timestamp('generated_at')->useCurrent();
            $table->unsignedBigInteger('published_by')->nullable()->comment('user_credentials.user_id');
            $table->timestamp('published_at')->nullable();
            $table->json('snapshot')->nullable()->comment('The marksheet as published');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_date')->nullable();

            $table->unique(['reexam_id', 'student_master_pk'], 'coe_rxms_reexam_student_unique');
            $table->index('course_master_pk', 'coe_rxms_course_index');
            $table->foreign('reexam_id', 'coe_rxms_reexam_fk')
                ->references('id')->on('coe_reexams')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
