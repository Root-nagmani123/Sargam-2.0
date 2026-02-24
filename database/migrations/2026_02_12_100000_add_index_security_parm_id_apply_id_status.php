<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add index on id_status for faster list filtering on employee-idcard index page.
     */
    public function up(): void
    {
        Schema::table('security_parm_id_apply', function (Blueprint $table) {
            $table->index('id_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('security_parm_id_apply', function (Blueprint $table) {
            $table->dropIndex(['id_status']);
        });
    }
};
