<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add FK constraints on course_master_pk (course_master.pk is typically signed INT;
        // if a type mismatch error occurs, remove the ->foreign() lines and add FKs manually).
        Schema::table('fc_ot_details', function (Blueprint $table) {
            $table->dropColumn('course');
        });

        Schema::table('fc_pre_history', function (Blueprint $table) {
            $table->dropColumn('course');
        });
    }

    public function down(): void
    {
        Schema::table('fc_ot_details', function (Blueprint $table) {
            $table->string('course', 120)->nullable()->after('otcode');
        });

        Schema::table('fc_pre_history', function (Blueprint $table) {
            $table->string('course', 120)->nullable()->after('status');
        });
    }
};
