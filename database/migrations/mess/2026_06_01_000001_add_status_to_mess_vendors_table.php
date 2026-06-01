<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mess_vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('mess_vendors', 'status')) {
                $table->string('status')->default('active')->after('licence_document');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mess_vendors', function (Blueprint $table) {
            if (Schema::hasColumn('mess_vendors', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
