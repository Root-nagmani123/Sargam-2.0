<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * faculty_unique_codes: the participants' Unique Participant Codes
 * (coe_participant_codes.unique_code) given to each faculty on the Map Faculty
 * setup's Multi Faculty tab, keyed by faculty_master.full_name, e.g.
 * {"Pawan Kumar Sain": ["UPC0001", "UPC0002"]}.
 *
 * faculty_codes keeps the roll numbers (coe_roll_numbers.roll_number) picked in
 * Select Roll Number, so the two lists stay apart.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guarded: shared/developer databases may already have the column.
        if (Schema::hasColumn('examination_drive_component_maps', 'faculty_unique_codes')) {
            return;
        }

        Schema::table('examination_drive_component_maps', function (Blueprint $table) {
            $table->json('faculty_unique_codes')->nullable()->after('faculty_codes');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('examination_drive_component_maps', 'faculty_unique_codes')) {
            Schema::table('examination_drive_component_maps', function (Blueprint $table) {
                $table->dropColumn('faculty_unique_codes');
            });
        }
    }
};
