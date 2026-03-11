<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mess_purchase_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('mess_purchase_order_items', 'item_subcategory_id')) {
                $table->unsignedBigInteger('item_subcategory_id')->nullable()->after('purchase_order_id');
                $table->foreign('item_subcategory_id')->references('id')->on('mess_item_subcategories')->onDelete('set null');
            }
            if (!Schema::hasColumn('mess_purchase_order_items', 'tax_percent')) {
                $table->decimal('tax_percent', 5, 2)->default(0)->after('unit_price');
            }
        });

        if (Schema::hasColumn('mess_purchase_order_items', 'inventory_id')) {
            \DB::statement('ALTER TABLE mess_purchase_order_items MODIFY inventory_id BIGINT UNSIGNED NULL');
        }
    }

    public function down()
    {
        Schema::table('mess_purchase_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('mess_purchase_order_items', 'item_subcategory_id')) {
                $table->dropForeign(['item_subcategory_id']);
                $table->dropColumn('item_subcategory_id');
            }
            if (Schema::hasColumn('mess_purchase_order_items', 'tax_percent')) {
                $table->dropColumn('tax_percent');
            }
        });

        if (Schema::hasColumn('mess_purchase_order_items', 'inventory_id')) {
            \DB::statement('ALTER TABLE mess_purchase_order_items MODIFY inventory_id BIGINT UNSIGNED NOT NULL');
        }
    }
};
