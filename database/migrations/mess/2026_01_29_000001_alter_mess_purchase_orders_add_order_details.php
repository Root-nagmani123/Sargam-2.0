<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mess_purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('mess_purchase_orders', 'order_name')) {
                $table->string('order_name')->nullable()->after('po_number');
            }
            if (!Schema::hasColumn('mess_purchase_orders', 'payment_code')) {
                $table->string('payment_code')->nullable()->after('store_id');
            }
            if (!Schema::hasColumn('mess_purchase_orders', 'delivery_address')) {
                $table->text('delivery_address')->nullable()->after('payment_code');
            }
            if (!Schema::hasColumn('mess_purchase_orders', 'contact_number')) {
                $table->string('contact_number')->nullable()->after('delivery_address');
            }
        });
    }

    public function down()
    {
        Schema::table('mess_purchase_orders', function (Blueprint $table) {
            $columns = ['order_name', 'payment_code', 'delivery_address', 'contact_number'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('mess_purchase_orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
