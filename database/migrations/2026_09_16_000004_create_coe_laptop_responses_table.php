<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE — one Officer Trainee's laptop status (the "OT Laptop Status" screen).
 *
 * ── There is no drive ──
 * An earlier revision of this module wrapped the question in a per-course "laptop drive"
 * with its own name, window and publish/close lifecycle. The screens that replaced it
 * have none of that: Laptop Management holds exactly one page, a flat list of Officer
 * Trainees with their answer, and an Import dialog that takes course + batch + OT. So the
 * answer stands on its own and the key is the participant, not (drive, participant).
 *
 * Course, batch, service and roll number are NOT repeated here — every one of them
 * already hangs off student_master.pk, and the grid joins them. Storing a second copy is
 * how the list starts disagreeing with the Roll Numbers screen.
 *
 * ── What laptop_required means ──
 * The screen's column is "Laptop Required", answered Yes or No, and the Reason explains
 * a Yes ("Does not own a laptop", "Personal laptop not working"). So 1 = this trainee
 * needs a machine provided for the examination, and the reason says why; 0 = they do not,
 * and carries no reason. Note this is the OPPOSITE polarity to the old has_laptop column
 * it replaces — anything reading it must be re-read, not renamed.
 *
 * ── A row exists only once the question has been answered ──
 * Rows are NOT pre-created for every participant. The grid left-joins this table onto the
 * course's participants, so "no row" IS the pending state. That keeps a trainee who joins
 * the course later from being invisible, and it is why laptop_required can be NOT NULL:
 * an unanswered question has no row at all.
 *
 * ── Type mapping, checked against the live schema ──
 *   student_master.pk  bigint SIGNED → bigInteger() here, NOT unsignedBigInteger().
 * student_master predates Laravel's conventions and its primary key is signed; MySQL
 * rejects a foreign key whose signedness differs.
 *
 * ── Deployment ──
 *   Order        : migration first, then code. The table is new to the deployed app.
 *   Rollback     : down() drops the table. Rolling back after trainees have declared
 *                  loses their answers — re-collectable, but not reconstructable.
 *   Verification : open /admin/coe/laptop-availability, add one record through
 *                  "Import Laptop Status → Add Single Record", and confirm the row
 *                  appears with Updated By = Admin.
 */
return new class extends Migration
{
    private const TABLE = 'coe_laptop_responses';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id('pk');

            // Signed — matches student_master.pk. See the note above.
            $table->bigInteger('student_master_pk');

            $table->boolean('laptop_required')
                ->comment('1=Yes, this OT needs a laptop provided; 0=No. NOT the old has_laptop polarity');
            $table->string('reason', 500)->nullable()
                ->comment('Required when laptop_required is 1 — why the OT needs one');

            $table->tinyInteger('source')->default(1)
                ->comment("The screen's \"Updated By\" column. 1=Officer Trainee, 2=Administrator");
            $table->timestamp('responded_at')->nullable();
            $table->unsignedBigInteger('responded_by')->nullable()
                ->comment('user_credentials.pk of whoever recorded the answer — the trainee or the administrator');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_date')->nullable();

            // One answer per participant. Both write paths (the trainee's own form and
            // the administrator's import) go through this key, so a re-submit updates
            // rather than duplicating — enforced here and not only in the service,
            // because an exists()-then-insert check loses a double submit.
            $table->unique('student_master_pk', 'coe_lr_student_unique');
            // The grid's Yes / No counts and the seat allocator's split read this alone.
            $table->index('laptop_required', 'coe_lr_required_index');

            // restrict: a participant who has declared must not be deleted out from under
            // the seat allocation that reads the declaration.
            $table->foreign('student_master_pk', 'coe_lr_student_master_pk_foreign')
                ->references('pk')->on('student_master')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
