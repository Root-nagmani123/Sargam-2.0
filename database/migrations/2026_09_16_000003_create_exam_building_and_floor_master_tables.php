<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * COE Examination - dedicated Building and Floor masters for exam venues.
 *
 * building_master / floor_master are not reused: every one of their 11 buildings
 * is building_type = 'Hostel' (SILVERWOOD, KAVERI, ...) and their floors are
 * hostel floors. Exam venues sit in academic blocks (Adhar Shila, Gyanshila),
 * so the COE needs its own lists rather than borrowing the hostel ones.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exam_building_master')) {
            Schema::create('exam_building_master', function (Blueprint $table) {
                $table->bigIncrements('pk');
                $table->string('building_name', 150);
                $table->string('building_short_name', 50)->nullable();
                $table->string('description', 255)->nullable();
                $table->integer('active_inactive')->default(1);
                $table->timestamp('created_date')->useCurrent();
                $table->timestamp('modified_date')->useCurrent()->useCurrentOnUpdate();

                $table->index('active_inactive');
            });
        }

        if (! Schema::hasTable('exam_floor_master')) {
            Schema::create('exam_floor_master', function (Blueprint $table) {
                $table->bigIncrements('pk');
                $table->string('floor_name', 100);
                $table->integer('display_order')->nullable();
                $table->integer('active_inactive')->default(1);
                $table->timestamp('created_date')->useCurrent();
                $table->timestamp('modified_date')->useCurrent()->useCurrentOnUpdate();

                $table->index('active_inactive');
            });
        }

        /*
         * venue_master.building_master_pk / floor_master_pk were introduced pointing
         * at the hostel masters. They now reference exam_building_master /
         * exam_floor_master, so any value carried over from the old lists would be a
         * wrong reference. Clear them; no real venue data is lost because these
         * columns were added empty in the previous migration.
         */
        if (Schema::hasColumn('venue_master', 'building_master_pk')) {
            DB::table('venue_master')
                ->whereNotNull('building_master_pk')
                ->orWhereNotNull('floor_master_pk')
                ->update([
                    'building_master_pk' => null,
                    'floor_master_pk' => null,
                ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_floor_master');
        Schema::dropIfExists('exam_building_master');
    }
};
