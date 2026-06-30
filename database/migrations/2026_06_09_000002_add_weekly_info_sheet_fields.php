<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backing fields for the "Course Information / Faculty for the Week" sheet:
     *  - Director / Joint Director (course-level personnel)
     *  - Participants Profile (course-level)
     *  - Mention of the Week (per course, per week)
     * All additive & nullable — existing workflows are unaffected.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('course_coordinator_master', function (Blueprint $table) {
            if (!Schema::hasColumn('course_coordinator_master', 'director_name')) {
                $table->string('director_name', 255)->nullable()->comment('Course Director (info sheet)');
            }
            if (!Schema::hasColumn('course_coordinator_master', 'joint_director_name')) {
                $table->string('joint_director_name', 255)->nullable()->comment('Joint Director (info sheet)');
            }
        });

        Schema::table('course_master', function (Blueprint $table) {
            if (!Schema::hasColumn('course_master', 'participants_profile')) {
                $table->text('participants_profile')->nullable()->comment('Participants profile (info sheet)');
            }
        });

        if (!Schema::hasTable('course_week_notes')) {
            Schema::create('course_week_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_master_pk');
                $table->date('week_start');
                $table->text('mention_of_week')->nullable();
                $table->timestamps();
                $table->unique(['course_master_pk', 'week_start'], 'course_week_notes_course_week_unique');
                $table->index('week_start');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('course_coordinator_master', function (Blueprint $table) {
            foreach (['director_name', 'joint_director_name'] as $column) {
                if (Schema::hasColumn('course_coordinator_master', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('course_master', function (Blueprint $table) {
            if (Schema::hasColumn('course_master', 'participants_profile')) {
                $table->dropColumn('participants_profile');
            }
        });

        Schema::dropIfExists('course_week_notes');
    }
};
