<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add same fields as Selling Voucher (client type, payment, issue date, etc.) - stored in sv_date_range_reports.
     */
    public function up()
    {
        Schema::table('sv_date_range_reports', function (Blueprint $table) {
            $table->string('client_type_slug', 50)->nullable()->after('remarks')->comment('employee, ot, course, section, other');
            $table->unsignedBigInteger('client_type_pk')->nullable()->after('client_type_slug')->comment('FK mess_client_types');
            $table->string('client_name')->nullable()->after('client_type_pk');
            $table->tinyInteger('payment_type')->default(1)->after('client_name')->comment('0=Cash, 1=Credit, 2=Online');
            $table->date('issue_date')->nullable()->after('payment_type');
        });
    }

    public function down()
    {
        Schema::table('sv_date_range_reports', function (Blueprint $table) {
            $table->dropColumn(['client_type_slug', 'client_type_pk', 'client_name', 'payment_type', 'issue_date']);
        });
    }
};
