<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Communications -> Club/ Society -> Create Nomination Drive.
 *
 *  nomination_drive               one drive per course (the grid row)
 *  nomination_drive_society       the societies open in that drive, with their
 *                                 own window when "Mark Same Date for All" = No
 *  nomination_drive_society_post  which posts are open for that society
 *  nomination                     an OT nominating someone for a post; powers
 *                                 the drive's View screen
 *
 * No FK constraints — the sibling tables in this module don't use them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nomination_drive')) {
            Schema::create('nomination_drive', function (Blueprint $table) {
                $table->bigIncrements('pk');
                $table->string('drive_name', 255);
                $table->unsignedBigInteger('course_master_pk');
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('self_nomination_allow')->default(1);
                // The two independently togglable switches on the grid row.
                $table->boolean('nomination_accept_status')->default(1);
                $table->boolean('nomination_withdraw_status')->default(1);
                $table->integer('active_inactive')->default(1);
                $table->timestamp('created_date')->useCurrent();
                $table->timestamp('updated_date')->useCurrent()->useCurrentOnUpdate();

                $table->index('course_master_pk', 'nomination_drive_course_index');
                $table->index('drive_name', 'nomination_drive_name_index');
            });
        }

        if (! Schema::hasTable('nomination_drive_society')) {
            Schema::create('nomination_drive_society', function (Blueprint $table) {
                $table->bigIncrements('pk');
                $table->unsignedBigInteger('nomination_drive_pk');
                $table->unsignedBigInteger('club_society_master_pk');
                // Null when the drive uses one window for every society.
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->timestamp('created_date')->useCurrent();

                $table->unique(['nomination_drive_pk', 'club_society_master_pk'], 'nom_drive_society_unique');
            });
        }

        if (! Schema::hasTable('nomination_drive_society_post')) {
            Schema::create('nomination_drive_society_post', function (Blueprint $table) {
                $table->bigIncrements('pk');
                $table->unsignedBigInteger('nomination_drive_society_pk');
                $table->unsignedBigInteger('club_society_role_master_pk');

                $table->unique(
                    ['nomination_drive_society_pk', 'club_society_role_master_pk'],
                    'nom_drive_society_post_unique'
                );
            });
        }

        if (! Schema::hasTable('nomination')) {
            Schema::create('nomination', function (Blueprint $table) {
                $table->bigIncrements('pk');
                $table->unsignedBigInteger('nomination_drive_pk');
                $table->unsignedBigInteger('club_society_master_pk');
                $table->unsignedBigInteger('club_society_role_master_pk');
                // Both reference student_master.pk (the OTs).
                $table->unsignedBigInteger('nominee_student_pk');
                $table->unsignedBigInteger('nominated_by_student_pk');
                // pending | accepted | withdrawn
                $table->string('status', 20)->default('pending');
                $table->timestamp('created_date')->useCurrent();
                $table->timestamp('updated_date')->useCurrent()->useCurrentOnUpdate();

                // One nominator may back a given nominee for a given post once.
                $table->unique(
                    ['nomination_drive_pk', 'club_society_master_pk', 'club_society_role_master_pk', 'nominee_student_pk', 'nominated_by_student_pk'],
                    'nomination_unique'
                );
                $table->index(['nomination_drive_pk', 'status'], 'nomination_drive_status_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nomination');
        Schema::dropIfExists('nomination_drive_society_post');
        Schema::dropIfExists('nomination_drive_society');
        Schema::dropIfExists('nomination_drive');
    }
};
