<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE — one roll number per participant (Examination module doc, "roll_numbers").
 *
 * ── Type mapping, checked against the live schema ──
 *   student_master.pk  bigint SIGNED  → bigInteger() here, NOT unsignedBigInteger().
 * student_master predates Laravel's conventions and its primary key is signed; MySQL
 * rejects a foreign key whose signedness differs, so foreignId()/unsignedBigInteger()
 * would fail at migrate time. The parent key on coe_roll_number_allocations is a
 * Laravel id('pk') and IS unsigned — the two columns here deliberately differ.
 *
 * ── Uniqueness ──
 * Both unique keys are real invariants, not belt-and-braces:
 *   student_master_pk  — a participant sits one course+batch, so one roll number. This
 *                        is also what lets the grid left-join on the participant alone.
 *   roll_number        — the doc identifies a participant by the bare roll number in
 *                        marksheets, QR verification and audit logs ("Roll No:
 *                        FC101-025"), with no course beside it. Two courses sharing a
 *                        prefix would make those references ambiguous, so the collision
 *                        is rejected at write time instead of surfacing in a result book.
 *
 * ── Deployment ──
 * See the allocations migration for order, rollback and post-deploy verification.
 */
return new class extends Migration
{
    private const TABLE = 'coe_roll_numbers';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id('pk');

            $table->unsignedBigInteger('roll_number_allocation_pk');
            // Signed — matches student_master.pk. See the note above.
            $table->bigInteger('student_master_pk');

            $table->unsignedInteger('serial_number')
                ->comment('Numeric part of the roll number; the doc maps faculty to ranges such as 001-010');
            $table->string('roll_number', 50)
                ->comment('Rendered value, e.g. FC101-001');

            $table->tinyInteger('status')->default(1)
                ->comment('1=Generated, 2=Frozen');
            $table->timestamp('frozen_at')->nullable();
            $table->unsignedBigInteger('frozen_by')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_date')->nullable();

            $table->unique('student_master_pk', 'coe_rn_student_unique');
            $table->unique('roll_number', 'coe_rn_roll_number_unique');
            // Ordered listing and the faculty roll-number ranges both read the run in
            // serial order, so the two columns are indexed together rather than apart.
            $table->index(['roll_number_allocation_pk', 'serial_number'], 'coe_rn_allocation_serial_index');
            // The All / Generated / Frozen tabs filter on status alone.
            $table->index('status', 'coe_rn_status_index');

            // Regeneration replaces a run's numbers wholesale, and deleting the run
            // should take its numbers with it — cascade is the intended behaviour here.
            $table->foreign('roll_number_allocation_pk', 'coe_rn_allocation_pk_foreign')
                ->references('pk')->on('coe_roll_number_allocations')
                ->onDelete('cascade');

            // restrict: a participant with a roll number must not be deleted out from
            // under an examination that already references it.
            $table->foreign('student_master_pk', 'coe_rn_student_master_pk_foreign')
                ->references('pk')->on('student_master')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
