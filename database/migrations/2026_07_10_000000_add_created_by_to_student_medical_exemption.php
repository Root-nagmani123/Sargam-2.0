<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_medical_exemption', function (Blueprint $table) {
            if (! Schema::hasColumn('student_medical_exemption', 'created_by')) {
                // Stores the id of the user who created the record (Auth::id()).
                // Nullable so pre-existing rows remain valid.
                $table->unsignedBigInteger('created_by')->nullable()->after('employee_master_pk');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_medical_exemption', function (Blueprint $table) {
            if (Schema::hasColumn('student_medical_exemption', 'created_by')) {
                $table->dropColumn('created_by');
            }
        });
    }
};
