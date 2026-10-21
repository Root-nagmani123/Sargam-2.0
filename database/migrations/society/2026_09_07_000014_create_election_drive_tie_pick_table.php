<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Communications -> Club/ Society -> Election result: random tie-break.
 *
 *  election_drive_tie_pick  one row per nominee chosen by the random draw when
 *                           nominees tie at the last seat of a post. The draw is
 *                           made once, before the result is published, so the
 *                           winner never changes on reload. picked_by / picked_at
 *                           record who ran it; tied_nominee_pks the candidates.
 *
 * DDL for DBA approval (PR #340, F-007 tie decision).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('election_drive_tie_pick')) {
            Schema::create('election_drive_tie_pick', function (Blueprint $table) {
                $table->bigIncrements('pk');
                $table->unsignedBigInteger('election_drive_pk');
                $table->unsignedBigInteger('club_society_master_pk');
                $table->unsignedBigInteger('club_society_role_master_pk');
                // student_master.pk of the nominee the draw seated.
                $table->unsignedBigInteger('nominee_student_pk');
                // Comma-separated student_master.pk of every nominee in the draw.
                $table->string('tied_nominee_pks', 1000);
                // user_credentials.pk of the account that ran the draw.
                $table->unsignedBigInteger('picked_by');
                $table->timestamp('picked_at')->useCurrent();

                $table->unique(
                    ['election_drive_pk', 'club_society_master_pk', 'club_society_role_master_pk', 'nominee_student_pk'],
                    'election_tie_pick_unique'
                );
            });
        }
    }

    public function down(): void
    {
        // Same guard as the other society/ migrations (PR #340 F-008): never drop
        // a table that holds rows, since up() may not have created it.
        if (Schema::hasTable('election_drive_tie_pick') && DB::table('election_drive_tie_pick')->exists()) {
            throw new \RuntimeException(
                'Refusing to roll back: table election_drive_tie_pick has rows. Back it up and empty it with the DBA first.'
            );
        }

        Schema::dropIfExists('election_drive_tie_pick');
    }
};
