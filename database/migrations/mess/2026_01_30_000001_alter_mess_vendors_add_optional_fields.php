<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mess_vendors', function (Blueprint $table) {
            $table->string('gst_number')->nullable()->after('address');
            $table->string('bank_name')->nullable()->after('gst_number');
            $table->string('ifsc_code', 20)->nullable()->after('bank_name');
            $table->string('account_number', 50)->nullable()->after('ifsc_code');
        });
    }

    public function down(): void
    {
        Schema::table('mess_vendors', function (Blueprint $table) {
            $table->dropColumn(['gst_number', 'bank_name', 'ifsc_code', 'account_number']);
        });
    }
};
