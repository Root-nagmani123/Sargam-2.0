<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE — one Unique Participant Code per participant (Master Data → Unique Participant Code).
 *
 * ── Type mapping, checked against the live schema ──
 *   student_master.pk  bigint SIGNED    → bigInteger(), as on coe_roll_numbers.
 *   course_master.pk   bigint UNSIGNED  → unsignedBigInteger(), as on coe_roll_number_allocations.
 *
 * ── Uniqueness ──
 *   student_master_pk  — one code per participant.
 *   unique_code        — the code stands in for the participant on its own, so two
 *                        participants must never share one, across courses too.
 */
return new class extends Migration
{
    private const TABLE = 'coe_participant_codes';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id('pk');

            // Signed — matches student_master.pk. See the note above.
            $table->bigInteger('student_master_pk');
            $table->unsignedBigInteger('course_master_pk');
            $table->integer('batch')->comment('Matches student_master.batch (year as int)');

            $table->string('unique_code', 50);

            $table->tinyInteger('status')->default(1)->comment('1=Pending, 2=Frozen');
            $table->timestamp('frozen_at')->nullable();
            $table->unsignedBigInteger('frozen_by')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_date')->nullable();

            $table->unique('student_master_pk', 'coe_pc_student_unique');
            $table->unique('unique_code', 'coe_pc_code_unique');
            $table->index(['course_master_pk', 'batch'], 'coe_pc_course_batch_index');

            $table->foreign('student_master_pk', 'coe_pc_student_fk')
                ->references('pk')->on('student_master');
            $table->foreign('course_master_pk', 'coe_pc_course_fk')
                ->references('pk')->on('course_master');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
