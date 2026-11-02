<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exemption_master', function (Blueprint $table) {
            $table->decimal('max_exemption_per_month', 5, 1)->default(0)->after('exemption_days');
        });
    }

    public function down(): void
    {
        Schema::table('exemption_master', function (Blueprint $table) {
            $table->dropColumn('max_exemption_per_month');
        });
    }
};
