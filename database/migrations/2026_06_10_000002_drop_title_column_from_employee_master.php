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
            // Drop the title column as we've migrated to using appellation
            if (Schema::hasColumn('employee_master', 'title')) {
                $table->dropColumn('title');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_master', function (Blueprint $table) {
            // Restore the title column if needed
            if (!Schema::hasColumn('employee_master', 'title')) {
                $table->string('title', 5)->nullable()->after('pk_old');
            }
        });
    }
};
