<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds duplication/extension specific fields: id_card_valid_from, id_card_number, fir_receipt, payment_receipt
     */
    public function up(): void
    {
        $tableName = 'employee_i_d_card_requests';
        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (!Schema::hasColumn($tableName, 'id_card_valid_from')) {
                $table->string('id_card_valid_from')->nullable()->after('id_card_valid_upto');
            }
            if (!Schema::hasColumn($tableName, 'id_card_number')) {
                $table->string('id_card_number')->nullable()->after('id_card_valid_from');
            }
            if (!Schema::hasColumn($tableName, 'fir_receipt')) {
                $table->string('fir_receipt')->nullable()->after('documents');
            }
            if (!Schema::hasColumn($tableName, 'payment_receipt')) {
                $table->string('payment_receipt')->nullable()->after('fir_receipt');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_i_d_card_requests', function (Blueprint $table) {
            $table->dropColumn(['id_card_valid_from', 'id_card_number', 'fir_receipt', 'payment_receipt']);
        });
    }
};
