<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Communications -> Club/ Society -> Create Election Drive.
 *
 *  election_drive          one election per nomination drive (the grid row).
 *                          The course is reached through the nomination drive.
 *  election_drive_society  the societies polling in that election, each with its
 *                          own date/time when the drive does not share one.
 *
 * The modal's "Number of Post" column is derived at read time from the
 * nomination drive's accepted nominations vs the role programme mapping's
 * number_of_post, so nothing is stored for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('election_drive')) {
            Schema::create('election_drive', function (Blueprint $table) {
                $table->bigIncrements('pk');
                $table->string('election_drive_name', 255);
                $table->unsignedBigInteger('nomination_drive_pk');
                $table->date('drive_date');
                $table->time('start_time');
                $table->time('end_time');
                $table->boolean('same_date_for_all')->default(1);
                $table->boolean('same_time_for_all')->default(1);
                // 0 = Pending, 1 = Live / Published
                $table->boolean('election_publish_status')->default(0);
                $table->boolean('result_status')->default(0);
                $table->integer('active_inactive')->default(1);
                $table->timestamp('created_date')->useCurrent();
                $table->timestamp('updated_date')->useCurrent()->useCurrentOnUpdate();

                $table->index('nomination_drive_pk', 'election_drive_nomination_index');
                $table->index('election_drive_name', 'election_drive_name_index');
            });
        }

        if (! Schema::hasTable('election_drive_society')) {
            Schema::create('election_drive_society', function (Blueprint $table) {
                $table->bigIncrements('pk');
                $table->unsignedBigInteger('election_drive_pk');
                $table->unsignedBigInteger('club_society_master_pk');
                // Null means "use the drive's own date / time".
                $table->date('drive_date')->nullable();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->timestamp('created_date')->useCurrent();

                $table->unique(['election_drive_pk', 'club_society_master_pk'], 'election_drive_society_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('election_drive_society');
        Schema::dropIfExists('election_drive');
    }
};
