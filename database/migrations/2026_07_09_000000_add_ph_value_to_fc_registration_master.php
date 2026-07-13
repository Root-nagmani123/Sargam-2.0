<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fc_registration_master', function (Blueprint $table) {
            if (! Schema::hasColumn('fc_registration_master', 'ph_value')) {
                // decimal pH reading (e.g. 7.35); range 0.00 - 14.00, stored to 2 dp
                $table->decimal('ph_value', 4, 2)->nullable()->after('web_auth');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fc_registration_master', function (Blueprint $table) {
            if (Schema::hasColumn('fc_registration_master', 'ph_value')) {
                $table->dropColumn('ph_value');
            }
        });
    }
};
