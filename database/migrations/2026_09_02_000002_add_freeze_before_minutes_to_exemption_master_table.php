<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exemption_master', function (Blueprint $table) {
            $table->unsignedInteger('freeze_before_minutes')->default(0)->after('apply_cutoff_time');
        });
    }

    public function down(): void
    {
        Schema::table('exemption_master', function (Blueprint $table) {
            $table->dropColumn('freeze_before_minutes');
        });
    }
};
