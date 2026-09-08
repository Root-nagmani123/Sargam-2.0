<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot behind Communications -> Club/ Society -> Club/ Society Programme Mapping.
 *
 * One row per (course, club) pair; the grid groups them back into one line per
 * course. No FK constraints: the sibling master tables in this app don't use
 * them, and course_master is written by several legacy paths.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('club_society_programme_mapping')) {
            return;
        }

        Schema::create('club_society_programme_mapping', function (Blueprint $table) {
            $table->bigIncrements('pk');
            $table->unsignedBigInteger('course_master_pk');
            $table->unsignedBigInteger('club_society_master_pk');
            $table->integer('active_inactive')->default(1);
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('updated_date')->useCurrent()->useCurrentOnUpdate();

            $table->unique(
                ['course_master_pk', 'club_society_master_pk'],
                'club_society_prog_map_unique'
            );
            $table->index('course_master_pk', 'club_society_prog_map_course_index');
            $table->index('club_society_master_pk', 'club_society_prog_map_club_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_society_programme_mapping');
    }
};
