<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE → Venue Allocation → Add Invigilators: who invigilates each mapped room.
 *
 * One row per room of a seating plan (coe_seat_plan_venues), one column per role, as the
 * design has one person per role per room. Chief / Invigilator / Assistant are faculty
 * (faculty_master); MTS is an employee (employee_master, designation MULTI TASKING STAFF).
 * A component examined "Offline Objective" takes no invigilators; that is enforced in
 * VenueAllocationController, not here.
 *
 * Type mapping, checked against the live schema:
 *   coe_seat_plan_venues.pk  bigint UNSIGNED
 *   faculty_master.pk        bigint UNSIGNED
 *   employee_master.pk       bigint UNSIGNED
 *
 * Deployment: new table only, so the running application is unaffected; migration first,
 * then code. Rollback drops the table and its assignments.
 */
return new class extends Migration
{
    private const TABLE = 'coe_seat_plan_venue_invigilators';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('seat_plan_venue_pk');
            $table->unsignedBigInteger('chief_faculty_pk')->nullable()->comment('Chief Invigilator (faculty_master)');
            $table->unsignedBigInteger('invigilator_faculty_pk')->nullable()->comment('Invigilator (faculty_master)');
            $table->unsignedBigInteger('assistant_faculty_pk')->nullable()->comment('Assistant Invigilator (faculty_master)');
            $table->unsignedBigInteger('mts_employee_pk')->nullable()->comment('MTS (employee_master, Multi Tasking Staff)');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_date')->nullable();

            // One set of invigilators per room of a plan.
            $table->unique('seat_plan_venue_pk', 'coe_spvi_plan_venue_unique');

            // Removing the room from the plan takes its invigilators with it.
            $table->foreign('seat_plan_venue_pk', 'coe_spvi_plan_venue_foreign')
                ->references('pk')->on('coe_seat_plan_venues')->cascadeOnDelete();
            // A person deleted from a master leaves the role empty rather than blocking the delete.
            $table->foreign('chief_faculty_pk', 'coe_spvi_chief_foreign')
                ->references('pk')->on('faculty_master')->nullOnDelete();
            $table->foreign('invigilator_faculty_pk', 'coe_spvi_invigilator_foreign')
                ->references('pk')->on('faculty_master')->nullOnDelete();
            $table->foreign('assistant_faculty_pk', 'coe_spvi_assistant_foreign')
                ->references('pk')->on('faculty_master')->nullOnDelete();
            $table->foreign('mts_employee_pk', 'coe_spvi_mts_foreign')
                ->references('pk')->on('employee_master')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
