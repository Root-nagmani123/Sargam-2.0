<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE — which rooms one seating plan may use (Examination module doc, "VENUE / CLASSROOM
 * MANAGEMENT": Assign Exam Venue, then a table of Building / Floor / Venue / Capacity).
 *
 * ── Why this is not derived from the seats ──
 * "Which rooms is this examination using" is a decision the administrator makes BEFORE
 * the allocation runs, and it has to survive the allocation. Reading it back from
 * coe_seat_allocations would lose any room that ended up with nobody in it, and would
 * leave a re-run starting from a blank selection — so the selection is stored in its own
 * right and the allocator reads it.
 *
 * ── Why purpose is stored per row, not taken from the venue ──
 * venue_master.laptop_capacity says what a room CAN do; this says what it is being used
 * for in THIS examination. A room with 30 laptop seats may be deliberately kept for the
 * pen-and-paper half of a cohort on one day and used for laptops on the next, which is
 * exactly the doc's Room 101 / 102 / 103 worked example.
 *
 * ── Type mapping, checked against the live schema ──
 *   venue_master.venue_id  bigint UNSIGNED  → unsignedBigInteger here
 * venue_master's primary key is venue_id, not pk — it predates the pk convention the
 * other masters follow, so the foreign key names the column explicitly.
 *
 * ── Deployment ──
 * See the plans migration for order, rollback and post-deploy verification.
 */
return new class extends Migration
{
    private const TABLE = 'coe_seat_plan_venues';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id('pk');

            $table->unsignedBigInteger('seat_plan_pk');
            $table->unsignedBigInteger('venue_id');

            $table->tinyInteger('purpose')->default(3)
                ->comment('What this room is used for in THIS examination: 1=Laptop seats only, 2=Non-laptop seats only, 3=Either');

            /*
             * What the allocator is allowed to put in this room, when it differs from the
             * master.
             *
             * Nullable, and null means "use the venue's own capacity" — which is the
             * normal case. A value here is the administrator saying "only use 20 of this
             * room's 30 desks today" (an invigilator short, a row under repair), and the
             * master must not be edited to say that, because it is true for one
             * examination only.
             */
            $table->unsignedInteger('capacity_override')->nullable();

            $table->unsignedInteger('display_order')->default(0)
                ->comment('The order rooms are filled in, and the order they print in. Ties fall back to venue name');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();

            // A room is in a plan once or not at all. Enforced here and not only in the
            // controller: the Add Venue dialog can be double-submitted.
            $table->unique(['seat_plan_pk', 'venue_id'], 'coe_spv_plan_venue_unique');
            // The allocator and the room-wise grid both read a plan's rooms in fill order.
            $table->index(['seat_plan_pk', 'display_order'], 'coe_spv_plan_order_index');

            // Deleting a plan takes its room selection with it — the selection means
            // nothing without the examination it was made for.
            $table->foreign('seat_plan_pk', 'coe_spv_plan_pk_foreign')
                ->references('pk')->on('coe_seat_plans')
                ->onDelete('cascade');

            // restrict: a room that an examination is already seated in must not be
            // deleted out from under the seating plan that prints it.
            $table->foreign('venue_id', 'coe_spv_venue_id_foreign')
                ->references('venue_id')->on('venue_master')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
