<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_master', function (Blueprint $table) {
            // Add appellation column as FK to appellation_master (int, nullable)
            if (!Schema::hasColumn('employee_master', 'appellation')) {
                $table->integer('appellation')->nullable()->comment('Appellation FK to AppellationMaster')->after('title');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_master', function (Blueprint $table) {
            if (Schema::hasColumn('employee_master', 'appellation')) {
                $table->dropColumn('appellation');
            }
        });
    }
};
