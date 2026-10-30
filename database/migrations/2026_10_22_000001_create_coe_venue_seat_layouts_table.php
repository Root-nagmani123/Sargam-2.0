<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE → Laptop & Scriber Seats Master.
 *
 * Which desks in an examination room are laptop desks and which are kept for
 * participants with a scriber. The admin picks them on a seat map, so the row
 * stores the seat NUMBERS (1..venue_master.capacity), not just counts — the
 * seat allocator puts a laptop candidate on a laptop desk and a scriber
 * candidate on a scriber desk.
 *
 * One row per room. venue_master.laptop_capacity is kept equal to the number of
 * laptop seats on every save, so the plain capacity figures stay right for the
 * screens that only read venue_master.
 *
 * ── Type mapping, checked against the live schema ──
 *   venue_master.venue_id  bigint UNSIGNED → unsignedBigInteger here
 */
return new class extends Migration
{
    private const TABLE = 'coe_venue_seat_layouts';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->bigIncrements('pk');
            $table->unsignedBigInteger('venue_id');
            $table->json('laptop_seats')->nullable()->comment('Sorted list of seat numbers that are laptop desks');
            $table->json('scriber_seats')->nullable()->comment('Sorted list of seat numbers kept for participants with a scriber');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_date')->nullable();

            $table->unique('venue_id', 'coe_vsl_venue_unique');
            $table->foreign('venue_id', 'coe_vsl_venue_id_foreign')
                ->references('venue_id')->on('venue_master')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
