<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * faculty_codes: the "Unique Codes of OT's" value chosen per sub-coordinator on
 * faculty Complete Setup (Multi Faculty tab), keyed by faculty_master.full_name,
 * e.g. {"Pawan Kumar Sain": "24"}. Shown as OT's Roll Number on the coordinates page.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guarded: shared/developer databases may already have the column.
        if (Schema::hasColumn('examination_drive_component_maps', 'faculty_codes')) {
            return;
        }

        Schema::table('examination_drive_component_maps', function (Blueprint $table) {
            $table->json('faculty_codes')->nullable()->after('sub_faculty_names');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('examination_drive_component_maps', 'faculty_codes')) {
            Schema::table('examination_drive_component_maps', function (Blueprint $table) {
                $table->dropColumn('faculty_codes');
            });
        }
    }
};
