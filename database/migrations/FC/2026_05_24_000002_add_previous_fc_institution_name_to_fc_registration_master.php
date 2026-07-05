<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fc_registration_master', function (Blueprint $table) {
            $table->string('previous_fc_institution_name', 255)->nullable()->after('previous_fc_course_name');
        });
    }

    public function down(): void
    {
        Schema::table('fc_registration_master', function (Blueprint $table) {
            $table->dropColumn('previous_fc_institution_name');
        });
    }
};
