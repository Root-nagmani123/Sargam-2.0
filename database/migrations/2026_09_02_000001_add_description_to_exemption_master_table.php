<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exemption_master', function (Blueprint $table) {
            $table->text('description')->nullable()->after('max_exemption_per_month');
        });
    }

    public function down(): void
    {
        Schema::table('exemption_master', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
