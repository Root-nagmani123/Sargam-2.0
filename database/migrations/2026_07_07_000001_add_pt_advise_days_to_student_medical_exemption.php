<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_medical_exemption', function (Blueprint $table) {
            if (! Schema::hasColumn('student_medical_exemption', 'pt_outdoor_advise')) {
                $table->string('pt_outdoor_advise', 255)->nullable()->after('opd_category');
            }
            if (! Schema::hasColumn('student_medical_exemption', 'days')) {
                $table->integer('days')->nullable()->after('pt_outdoor_advise');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_medical_exemption', function (Blueprint $table) {
            foreach (['pt_outdoor_advise', 'days'] as $col) {
                if (Schema::hasColumn('student_medical_exemption', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
