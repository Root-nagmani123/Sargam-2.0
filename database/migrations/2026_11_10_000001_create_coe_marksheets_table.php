<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE → Document Management → Marksheet Generation / Verified Marksheet.
 *
 * One marksheet per OT per course, written by MarksheetService once the OT is
 * ranked in Merit & Ranking with a Pass / Fail (Generated), then Verified &
 * Published by COE. Publishing freezes the marksheet as it was printed in
 * `snapshot` (marks, result, signatures used), so later changes to marks or
 * Signature Master do not alter a published marksheet.
 *
 * ── Type mapping ──
 *   course_master_pk / student_master_pk  unsigned bigint, as elsewhere in COE. No FK,
 *                                         like coe_evaluation_marks.
 *   published_by                          user_credentials.user_id, as approved_by.
 *
 * ── Deployment ──
 *   Rollback     : down() drops the table; marksheets are then generated afresh
 *                  (new numbers) and every publication is lost.
 *   Verification : open /admin/coe/marksheet-generation; ranked OTs with a result
 *                  show a Marksheet Number.
 */
return new class extends Migration
{
    private const TABLE = 'coe_marksheets';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('course_master_pk');
            $table->unsignedBigInteger('student_master_pk');
            $table->unsignedInteger('serial')->comment('Running number within the course');
            $table->string('marksheet_number', 50)->unique();
            $table->unsignedTinyInteger('status')->default(1)->comment('1=Generated, 2=Published');
            $table->timestamp('generated_at')->useCurrent();
            $table->unsignedBigInteger('published_by')->nullable()->comment('user_credentials.user_id');
            $table->timestamp('published_at')->nullable();
            $table->json('snapshot')->nullable()->comment('The marksheet as published');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_date')->nullable();

            $table->unique(['course_master_pk', 'student_master_pk']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
