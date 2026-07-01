<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exemption_master', function (Blueprint $table) {
            $table->time('apply_cutoff_time')->nullable()->after('exemption_days');
        });

        Schema::table('stationed_leave_master', function (Blueprint $table) {
            $table->time('apply_cutoff_time')->nullable()->after('effective_from');
        });
    }

    public function down(): void
    {
        Schema::table('exemption_master', function (Blueprint $table) {
            $table->dropColumn('apply_cutoff_time');
        });

        Schema::table('stationed_leave_master', function (Blueprint $table) {
            $table->dropColumn('apply_cutoff_time');
        });
    }
};
