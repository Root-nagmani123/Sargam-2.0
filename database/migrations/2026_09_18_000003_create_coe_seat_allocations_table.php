<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE — where one participant sits (Examination module doc, "seat_allocations" in DATABASE
 * DESIGN; "AUTOMATIC Exam ROOM ALLOCATION"; and "VENUE NOTIFICATION", which prints
 * Venue and Seat to the participant).
 *
 * ── A row exists only once the participant has been seated ──
 * Rows are NOT pre-created when a plan is drafted. The screens left-join this table onto
 * the course's participants, so "no row" IS the unallocated state — the same shape
 * coe_laptop_responses uses, and for the same reason: a participant who joins the course
 * after the plan was drawn up is then visible as unallocated rather than invisible.
 *
 * ── Why the seat is (venue, seat_number) and not one running number ──
 * The doc prints "Venue: Computer Lab – Room 101 / Seat: 25", so a seat is addressed
 * within its room. Every room therefore restarts at the plan's seat_start_number, and the
 * unique key is scoped to the room, not the plan.
 *
 * ── Type mapping, checked against the live schema ──
 *   student_master.pk      bigint SIGNED    → bigInteger() here, NOT unsignedBigInteger().
 *   venue_master.venue_id  bigint UNSIGNED  → unsignedBigInteger here
 *   coe_roll_numbers.pk    bigint UNSIGNED  → unsignedBigInteger here
 * student_master predates Laravel's conventions and its primary key is signed; MySQL
 * rejects a foreign key whose signedness differs, so foreignId()/unsignedBigInteger()
 * would fail at migrate time. The other three keys here ARE unsigned — the difference is
 * deliberate, not an oversight.
 *
 * ── Deployment ──
 * See the plans migration for order, rollback and post-deploy verification.
 */
return new class extends Migration
{
    private const TABLE = 'coe_seat_allocations';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id('pk');

            $table->unsignedBigInteger('seat_plan_pk');
            // Signed — matches student_master.pk. See the note above.
            $table->bigInteger('student_master_pk');

            /*
             * The roll number this seat was allocated against.
             *
             * Denormalised on purpose, and nullable. The doc allocates BY roll number and
             * the printed seating plan is read by roll number, so the plan must remember
             * which one it used — a roll number corrected after the allocation should not
             * silently re-order a seating chart that has already been posted on a door.
             * Null covers a participant seated by hand before their number was issued.
             */
            $table->unsignedBigInteger('roll_number_pk')->nullable();

            $table->unsignedBigInteger('venue_id');
            $table->unsignedInteger('seat_number');

            $table->boolean('requires_laptop')->default(false)
                ->comment('What the plan decided about this participant at allocation time, from the drive and the plan laptop_requirement setting');

            /*
             * The doc's "Allow manual override by authorized administrator".
             *
             * A manual seat is honoured by the NEXT run rather than being overwritten:
             * an override exists precisely because the automatic answer was wrong, so
             * re-running after three more participants join must not undo it.
             */
            $table->boolean('is_manual')->default(false);

            $table->timestamp('allocated_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_date')->nullable();

            // A participant sits in one seat for one examination.
            $table->unique(['seat_plan_pk', 'student_master_pk'], 'coe_sa_plan_student_unique');
            /*
             * And a seat holds one participant. This one is load-bearing rather than
             * defensive: the manual-override screen lets an administrator type a seat
             * number, and two people sent to the same desk is the single worst outcome
             * this table can produce. The database refuses it.
             */
            $table->unique(['seat_plan_pk', 'venue_id', 'seat_number'], 'coe_sa_plan_venue_seat_unique');
            // The room-wise seating chart and the attendance sheet both read one room in
            // seat order.
            $table->index(['seat_plan_pk', 'venue_id', 'seat_number'], 'coe_sa_plan_room_seat_index');

            // Re-allocating replaces a plan's seats wholesale, and deleting the plan
            // should take its seats with it — cascade is the intended behaviour here.
            $table->foreign('seat_plan_pk', 'coe_sa_plan_pk_foreign')
                ->references('pk')->on('coe_seat_plans')
                ->onDelete('cascade');

            // restrict: a participant who has been given a seat must not be deleted out
            // from under the examination that seated them.
            $table->foreign('student_master_pk', 'coe_sa_student_master_pk_foreign')
                ->references('pk')->on('student_master')
                ->onDelete('restrict');

            // restrict: same reasoning as the plan's room selection.
            $table->foreign('venue_id', 'coe_sa_venue_id_foreign')
                ->references('venue_id')->on('venue_master')
                ->onDelete('restrict');

            /*
             * SET NULL: the roll number is a reference the seat was cut from, not its
             * parent. If a roll number is deleted the seat is still a real seat, and the
             * screens fall back to reading the participant's current number.
             */
            $table->foreign('roll_number_pk', 'coe_sa_roll_number_pk_foreign')
                ->references('pk')->on('coe_roll_numbers')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
