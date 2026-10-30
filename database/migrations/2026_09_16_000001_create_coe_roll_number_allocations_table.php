<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE — one row per roll-number generation run (Examination module doc, "ROLL NUMBER
 * ALLOCATION": roll numbers are generated course-wise and the administrator configures
 * prefix, starting number, number format, course and batch).
 *
 * The run is kept apart from the numbers themselves (coe_roll_numbers) because the
 * configuration is per course+batch while the roll number is per participant, and the
 * doc's "if the conditions are not fulfilled, then it be regenerated" means the child
 * rows get replaced while this row — and the settings the admin typed — survive.
 *
 * ── Type mapping, checked against the live schema ──
 *   course_master.pk    bigint UNSIGNED  → unsignedBigInteger here
 *   student_master.batch int             → integer here (batch is a year, not a string)
 * MySQL refuses a foreign key whose signedness differs from the referenced column, so
 * these are not interchangeable with Laravel's foreignId()/id() defaults.
 *
 * ── Deployment ──
 *   Order        : migration first, then code. Both tables are new, so the currently
 *                  deployed application runs unchanged against the new schema.
 *   Rollback     : down() drops both tables (child first, in its own migration).
 *                  Rolling back after a generation run loses the allocations — they are
 *                  reproducible by re-running Generate, as no marks or results hang off
 *                  them yet.
 *   Verification : open /admin/coe/roll-numbers, generate for one course+batch, confirm
 *                  the grid shows the rendered roll numbers and the Frozen tab fills
 *                  after a Freeze.
 */
return new class extends Migration
{
    private const TABLE = 'coe_roll_number_allocations';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id('pk');

            // Signedness follows course_master.pk (bigint unsigned) — see the note above.
            $table->unsignedBigInteger('course_master_pk');
            // NOT NULL on purpose: MySQL lets NULLs repeat inside a UNIQUE index, so a
            // nullable batch would silently allow two allocations for the same course.
            $table->integer('batch')->comment('Matches student_master.batch (year as int)');

            // ── Number format: prefix + separator + zero-padded serial, e.g. FC101-001 ──
            $table->string('prefix', 20);
            $table->unsignedInteger('start_number')->default(1)
                ->comment('Numeric value the first serial starts at');
            $table->unsignedTinyInteger('number_padding')->default(3)
                ->comment('Digits the serial is zero-padded to; derived from the digits the admin typed, so "0001" means 4');
            $table->string('number_separator', 5)->default('-')
                ->comment('Between prefix and serial; not exposed on the form yet, the doc lists number format as configurable');

            $table->string('strategy', 50)->default('allocation_rules');

            // Three named rules rather than a JSON blob: each one is a documented
            // requirement, and a separate column stays queryable and readable in a diff.
            // A fourth rule costs one small migration, which is the cheaper trade here.
            $table->boolean('rule_avoid_same_service')->default(true)
                ->comment('Do not give consecutive roll numbers to participants of the same service');
            $table->boolean('rule_avoid_consecutive_theatre')->default(true)
                ->comment('FC Phase-I/II theatre rule. Stored but NOT applied: this schema has no theatre/stream mapping yet');
            $table->boolean('rule_alternate_gender')->default(false)
                ->comment('Alternate female/male where possible');

            $table->unsignedInteger('participant_count')->default(0)
                ->comment('Roll numbers written by the run that produced this row');

            $table->tinyInteger('status')->default(1)
                ->comment('1=Generated, 2=Frozen');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('frozen_at')->nullable();
            $table->unsignedBigInteger('frozen_by')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_date')->nullable();

            // One allocation per course+batch. Enforced here and not only in the
            // controller: an exists()-then-insert check loses a double submit.
            $table->unique(['course_master_pk', 'batch'], 'coe_rna_course_batch_unique');
            // The grid filters by status (the All / Generated / Frozen tabs).
            $table->index('status', 'coe_rna_status_index');

            // restrict, not cascade: deleting a course must not silently take the roll
            // numbers of everyone on it with it.
            $table->foreign('course_master_pk', 'coe_rna_course_master_pk_foreign')
                ->references('pk')->on('course_master')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        // coe_roll_numbers holds the child foreign key and drops itself first — its
        // migration has the later timestamp, so rollback runs it before this one.
        Schema::dropIfExists(self::TABLE);
    }
};
