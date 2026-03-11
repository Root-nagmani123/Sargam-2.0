<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Alters mess_vendor_item_mappings for Vendor Mapping: mapping_type, item_category_id, item_subcategory_id.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mess_vendor_item_mappings', function (Blueprint $table) {
            if (Schema::hasColumn('mess_vendor_item_mappings', 'inventory_id')) {
                $table->dropColumn('inventory_id');
            }
            if (Schema::hasColumn('mess_vendor_item_mappings', 'rate')) {
                $table->dropColumn('rate');
            }
            if (Schema::hasColumn('mess_vendor_item_mappings', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });

        Schema::table('mess_vendor_item_mappings', function (Blueprint $table) {
            if (!Schema::hasColumn('mess_vendor_item_mappings', 'mapping_type')) {
                $table->string('mapping_type', 32)->after('vendor_id'); // 'item_category' | 'item_sub_category'
            }
            if (!Schema::hasColumn('mess_vendor_item_mappings', 'item_category_id')) {
                $table->unsignedBigInteger('item_category_id')->nullable()->after('mapping_type');
                $table->foreign('item_category_id')->references('id')->on('mess_item_categories')->onDelete('cascade');
            }
            if (!Schema::hasColumn('mess_vendor_item_mappings', 'item_subcategory_id')) {
                $table->unsignedBigInteger('item_subcategory_id')->nullable()->after('item_category_id');
                $table->foreign('item_subcategory_id')->references('id')->on('mess_item_subcategories')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mess_vendor_item_mappings', function (Blueprint $table) {
            if (Schema::hasColumn('mess_vendor_item_mappings', 'item_subcategory_id')) {
                $table->dropForeign(['item_subcategory_id']);
                $table->dropColumn('item_subcategory_id');
            }
            if (Schema::hasColumn('mess_vendor_item_mappings', 'item_category_id')) {
                $table->dropForeign(['item_category_id']);
                $table->dropColumn('item_category_id');
            }
            if (Schema::hasColumn('mess_vendor_item_mappings', 'mapping_type')) {
                $table->dropColumn('mapping_type');
            }
        });

        Schema::table('mess_vendor_item_mappings', function (Blueprint $table) {
            if (!Schema::hasColumn('mess_vendor_item_mappings', 'inventory_id')) {
                $table->unsignedBigInteger('inventory_id')->nullable()->after('vendor_id');
                $table->index('inventory_id');
            }
            if (!Schema::hasColumn('mess_vendor_item_mappings', 'rate')) {
                $table->decimal('rate', 10, 2)->nullable()->after('inventory_id');
            }
            if (!Schema::hasColumn('mess_vendor_item_mappings', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('rate');
            }
        });
    }
};
