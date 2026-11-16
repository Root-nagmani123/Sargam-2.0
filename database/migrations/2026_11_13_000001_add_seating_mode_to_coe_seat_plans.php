<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE → Venue Allocation: System generated / Manual seating and the seating rules.
 *
 *   coe_seat_plans.rule_avoid_same_service / rule_avoid_same_cadre / rule_alternate_gender
 *       — the Map Seating Plan rules. Seats are handed out in sequence, so two OTs next
 *       to each other in the order sit next to each other in the room.
 *   coe_seat_plan_venues.seating_mode — 1 = System generated (the room takes whoever the
 *       allocator gives it), 2 = Manual (the room takes exactly the OTs listed for it in
 *       coe_seat_plan_venue_ots).
 *   coe_seat_plan_venue_ots — which OTs a Manual room seats. One OT, one room per plan.
 *
 * All additive with defaults: rooms mapped before this are System generated and old plans
 * re-seat with the rules off, exactly as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coe_seat_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('coe_seat_plans', 'rule_avoid_same_service')) {
                $table->boolean('rule_avoid_same_service')->default(false)->after('seat_gap')
                    ->comment('Two OTs of the same service are not seated next to each other');
            }
            if (! Schema::hasColumn('coe_seat_plans', 'rule_avoid_same_cadre')) {
                $table->boolean('rule_avoid_same_cadre')->default(false)->after('rule_avoid_same_service')
                    ->comment('Two OTs of the same cadre are not seated next to each other');
            }
            if (! Schema::hasColumn('coe_seat_plans', 'rule_alternate_gender')) {
                $table->boolean('rule_alternate_gender')->default(false)->after('rule_avoid_same_cadre')
                    ->comment('Female and male OTs alternate where possible');
            }
        });

        Schema::table('coe_seat_plan_venues', function (Blueprint $table) {
            if (! Schema::hasColumn('coe_seat_plan_venues', 'seating_mode')) {
                $table->tinyInteger('seating_mode')->default(1)->after('purpose')
                    ->comment('1=System generated, 2=Manual (seats only the OTs in coe_seat_plan_venue_ots)');
            }
        });

        if (! Schema::hasTable('coe_seat_plan_venue_ots')) {
            Schema::create('coe_seat_plan_venue_ots', function (Blueprint $table) {
                $table->id('pk');
                $table->unsignedBigInteger('seat_plan_venue_pk');
                $table->unsignedBigInteger('seat_plan_pk');
                $table->bigInteger('student_master_pk');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('created_date')->useCurrent();

                $table->unique(['seat_plan_pk', 'student_master_pk'], 'coe_spvo_plan_student_unique');
                $table->index('seat_plan_venue_pk', 'coe_spvo_plan_venue_index');

                $table->foreign('seat_plan_venue_pk', 'coe_spvo_plan_venue_foreign')
                    ->references('pk')->on('coe_seat_plan_venues')
                    ->cascadeOnDelete();
                $table->foreign('seat_plan_pk', 'coe_spvo_plan_foreign')
                    ->references('pk')->on('coe_seat_plans')
                    ->cascadeOnDelete();
                $table->foreign('student_master_pk', 'coe_spvo_student_foreign')
                    ->references('pk')->on('student_master')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coe_seat_plan_venue_ots');

        Schema::table('coe_seat_plan_venues', function (Blueprint $table) {
            if (Schema::hasColumn('coe_seat_plan_venues', 'seating_mode')) {
                $table->dropColumn('seating_mode');
            }
        });

        Schema::table('coe_seat_plans', function (Blueprint $table) {
            foreach (['rule_alternate_gender', 'rule_avoid_same_cadre', 'rule_avoid_same_service'] as $column) {
                if (Schema::hasColumn('coe_seat_plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
