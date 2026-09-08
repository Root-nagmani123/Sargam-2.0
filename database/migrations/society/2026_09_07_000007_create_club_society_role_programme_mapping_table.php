<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roles defined for a club/society within a course:
 * Communications -> Club/ Society -> Club/ Society Role Programme Mapping.
 *
 * One row per (course, club, role) with that role's post count and nomination
 * settings. The grid groups back to one line per (course, club).
 * No FK constraints — the sibling tables in this module don't use them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('club_society_role_programme_mapping')) {
            return;
        }

        Schema::create('club_society_role_programme_mapping', function (Blueprint $table) {
            $table->bigIncrements('pk');
            $table->unsignedBigInteger('course_master_pk');
            $table->unsignedBigInteger('club_society_master_pk');
            $table->unsignedBigInteger('club_society_role_master_pk');
            $table->unsignedInteger('number_of_post')->default(1);
            // 1 = nominations required for this post, 0 = not required.
            $table->boolean('required_nomination')->default(0);
            // Only meaningful when required_nomination = 1; null otherwise.
            $table->unsignedInteger('number_of_nomination')->nullable();
            $table->integer('active_inactive')->default(1);
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('updated_date')->useCurrent()->useCurrentOnUpdate();

            $table->unique(
                ['course_master_pk', 'club_society_master_pk', 'club_society_role_master_pk'],
                'cs_role_prog_map_unique'
            );
            $table->index(['course_master_pk', 'club_society_master_pk'], 'cs_role_prog_map_group_index');
            $table->index('club_society_role_master_pk', 'cs_role_prog_map_role_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_society_role_programme_mapping');
    }
};
