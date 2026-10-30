<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Course Repository: whether a video may be downloaded from the user side.
 *
 * The flag sits on course_repository_details, next to the videolink it governs —
 * one video per detail, so one switch per video. Admin sets it when uploading or
 * editing; the user side shows the download action only while it is on. Viewing
 * and playing are unaffected: this controls taking a copy away, not watching.
 *
 * Defaults to 1 so every video already in the repository keeps behaving exactly
 * as it does today — the switch is something an admin turns OFF, not a
 * permission everyone has to be granted after the fact.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('course_repository_details', 'video_download_enabled')) {
            return;
        }

        Schema::table('course_repository_details', function (Blueprint $table) {
            $table->boolean('video_download_enabled')
                ->default(1)
                ->after('videolink');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('course_repository_details', 'video_download_enabled')) {
            return;
        }

        Schema::table('course_repository_details', function (Blueprint $table) {
            $table->dropColumn('video_download_enabled');
        });
    }
};
